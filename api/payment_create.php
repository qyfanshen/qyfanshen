<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/rate_limit.php'; checkRateLimit('payment_create',10,60);
require_once __DIR__.'/wechat_pay.php';
require_once __DIR__.'/user_auth.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit(json_encode(['ok'=>false,'message'=>'仅支持 POST']));}
try{verifyUserRequest();$user=currentUser();if(!$user){http_response_code(401);exit(json_encode(['ok'=>false,'message'=>'请先登录'],JSON_UNESCAPED_UNICODE));}}catch(Throwable $e){http_response_code(403);exit(json_encode(['ok'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE));}
$data=json_decode(file_get_contents('php://input')?:'',true)?:[];
$productId=(int)($data['product_id']??0);$product=$productId>0?DB::product($productId):null;
if(!$product || $product['status']!=='active'){http_response_code(404);exit(json_encode(['ok'=>false,'message'=>'商品不存在或已下架'],JSON_UNESCAPED_UNICODE));}
$amount=(int)round((float)$product['price']*100);
if($amount<1 || $amount>100000000){http_response_code(422);exit(json_encode(['ok'=>false,'message'=>'商品价格无效'],JSON_UNESCAPED_UNICODE));}
$number='FS'.date('YmdHis').strtoupper(bin2hex(random_bytes(4)));$description='梵燊商城-'.mb_substr((string)$product['name'],0,120,'UTF-8');
try{$pay=new WechatPay();$pay->assertReady();DB::createPaymentOrder($number,$amount,$description,(int)$user['id'],$productId,(string)$product['name']);$url=$pay->createNative($number,$amount,$description);echo json_encode(['ok'=>true,'order_no'=>$number,'code_url'=>$url],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}catch(Throwable $e){error_log('Payment create failed: '.$e->getMessage());http_response_code(500);echo json_encode(['ok'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
