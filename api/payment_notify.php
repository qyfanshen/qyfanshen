<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/wechat_pay.php';
try{$payload=json_decode(file_get_contents('php://input')?:'',true);if(!is_array($payload))throw new RuntimeException('无效通知');(new WechatPay())->handleNotification($payload);http_response_code(200);echo json_encode(['code'=>'SUCCESS','message'=>'成功'],JSON_UNESCAPED_UNICODE);}catch(Throwable $e){error_log('Payment notify failed: '.$e->getMessage());http_response_code(500);echo json_encode(['code'=>'FAIL','message'=>'处理失败'],JSON_UNESCAPED_UNICODE);}
