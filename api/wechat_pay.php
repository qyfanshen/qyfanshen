<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

final class WechatPay {
    private array $config;
    public function __construct() {
        $this->config = [
            'enabled'=>DB::setting('pay_enabled','0'), 'name'=>DB::setting('pay_name','微信支付'),
            'appid'=>DB::setting('pay_appid'), 'mchid'=>DB::setting('pay_mchid'), 'serial'=>DB::setting('pay_serial'),
            'private_key'=>self::decrypt(DB::setting('pay_private_key')), 'api_v3_key'=>self::decrypt(DB::setting('pay_api_v3_key')),
            'notify_url'=>DB::setting('pay_notify_url'), 'note'=>DB::setting('pay_note')
        ];
    }
    public function publicConfig(): array { return ['enabled'=>$this->config['enabled']==='1','name'=>$this->config['name'],'note'=>$this->config['note']]; }
    public function assertReady(): void {
        if ($this->config['enabled']!=='1') throw new RuntimeException('支付功能尚未启用');
        foreach(['appid','mchid','serial','private_key','api_v3_key','notify_url'] as $key) if(trim($this->config[$key])==='') throw new RuntimeException('微信支付配置不完整');
        if (!extension_loaded('curl') || !extension_loaded('openssl')) throw new RuntimeException('服务器缺少 CURL 或 OpenSSL 扩展');
    }
    public function createNative(string $number, int $amount, string $description): string {
        $this->assertReady();
        $body=json_encode(['appid'=>$this->config['appid'],'mchid'=>$this->config['mchid'],'description'=>mb_substr($description,0,127,'UTF-8'),'out_trade_no'=>$number,'notify_url'=>$this->config['notify_url'],'amount'=>['total'=>$amount,'currency'=>'CNY']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $data=$this->request('POST','/v3/pay/transactions/native',$body ?: '');
        if(empty($data['code_url'])) throw new RuntimeException('微信支付未返回二维码');
        return (string)$data['code_url'];
    }
    public function query(string $number): array {
        $this->assertReady();
        return $this->request('GET','/v3/pay/transactions/out-trade-no/'.rawurlencode($number).'?mchid='.rawurlencode($this->config['mchid']),'');
    }
    public function refund(array $order,string $refundNo,string $reason): array {
        $this->assertReady();$amount=(int)$order['amount'];
        $body=json_encode(['out_trade_no'=>$order['out_trade_no'],'out_refund_no'=>$refundNo,'reason'=>mb_substr($reason,0,80,'UTF-8'),'amount'=>['refund'=>$amount,'total'=>$amount,'currency'=>'CNY']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        return $this->request('POST','/v3/refund/domestic/refunds',$body?:'');
    }
    public function queryRefund(string $refundNo): array {
        $this->assertReady();return $this->request('GET','/v3/refund/domestic/refunds/'.rawurlencode($refundNo),'');
    }
    public function handleNotification(array $payload): void {
        $resource=$payload['resource']??[]; $cipher=base64_decode((string)($resource['ciphertext']??''),true);
        if($cipher===false || strlen($cipher)<17) throw new RuntimeException('无效支付通知');
        $plain=openssl_decrypt(substr($cipher,0,-16),'aes-256-gcm',$this->config['api_v3_key'],OPENSSL_RAW_DATA,(string)($resource['nonce']??''),substr($cipher,-16),(string)($resource['associated_data']??''));
        $data=json_decode($plain?:'',true);
        $refundNo=preg_replace('/[^A-Z0-9]/','',(string)($data['out_refund_no']??''));
        if($refundNo!==''){
            $remote=$this->queryRefund($refundNo);DB::updateRefund($refundNo,(string)($remote['status']??'PROCESSING'),(string)($remote['refund_id']??''));return;
        }
        $number=preg_replace('/[^A-Z0-9]/','',(string)($data['out_trade_no']??''));if($number==='')throw new RuntimeException('支付通知缺少订单号');
        $remote=$this->query($number);$status=(string)($remote['trade_state']??'');if($status==='SUCCESS')DB::updatePaymentOrder($number,'SUCCESS',(string)($remote['transaction_id']??''));
    }
    private function request(string $method,string $path,string $body): array {
        $timestamp=(string)time(); $nonce=bin2hex(random_bytes(16)); $message=$method."\n".$path."\n".$timestamp."\n".$nonce."\n".$body."\n";
        $key=openssl_pkey_get_private($this->config['private_key']); if(!$key) throw new RuntimeException('商户 API 私钥格式无效');
        if(!openssl_sign($message,$signature,$key,OPENSSL_ALGO_SHA256)) throw new RuntimeException('支付请求签名失败');
        $token=sprintf('WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%s",serial_no="%s",signature="%s"',$this->config['mchid'],$nonce,$timestamp,$this->config['serial'],base64_encode($signature));
        $ch=curl_init('https://api.mch.weixin.qq.com'.$path); curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_USERAGENT=>'qyfanshen.com-wechatpay/1.0',CURLOPT_HTTPHEADER=>['Accept: application/json','Content-Type: application/json','Authorization: '.$token],CURLOPT_POSTFIELDS=>$method==='POST'?$body:null]);
        $raw=curl_exec($ch); $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $error=curl_error($ch); curl_close($ch);
        if($raw===false || $error!=='') throw new RuntimeException('无法连接微信支付服务');
        $data=json_decode($raw,true); if($status<200 || $status>=300) { error_log('Wechat Pay error: '.$raw); throw new RuntimeException((string)($data['message']??'微信支付请求失败')); }
        return is_array($data)?$data:[];
    }
    public static function encrypt(string $plain): string {
        if($plain==='') return ''; if(!extension_loaded('openssl')) throw new RuntimeException('服务器缺少 OpenSSL 扩展');
        $key=self::storageKey(); $iv=random_bytes(12); $tag=''; $cipher=openssl_encrypt($plain,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);
        if($cipher===false) throw new RuntimeException('支付配置加密失败'); return base64_encode($iv.$tag.$cipher);
    }
    private static function decrypt(string $encoded): string {
        if($encoded==='') return ''; if(!extension_loaded('openssl')) return '';
        $raw=base64_decode($encoded,true); if($raw===false || strlen($raw)<29) return '';
        $plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',self::storageKey(),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16)); return $plain===false?'':$plain;
    }
    private static function storageKey(): string {
        $file=dirname(__DIR__).'/storage/payment.key';
        if(!is_file($file)){ $key=random_bytes(32); if(file_put_contents($file,$key,LOCK_EX)===false) throw new RuntimeException('无法创建支付配置加密密钥'); @chmod($file,0600); }
        $key=file_get_contents($file); if($key===false || strlen($key)!==32) throw new RuntimeException('支付配置加密密钥损坏'); return $key;
    }
}
