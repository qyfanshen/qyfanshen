<?php
require_once '../../includes/csrf.php';
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/rate_limit.php'; checkRateLimit('payment_status',60,60);
require_once __DIR__.'/wechat_pay.php';
$number=preg_replace('/[^A-Z0-9]/','',(string)($_GET['order_no']??'')); $order=$number?DB::paymentOrder($number):null;
if(!$order){http_response_code(404);exit(json_encode(['ok'=>false,'message'=>'订单不存在'],JSON_UNESCAPED_UNICODE));}
try{$remote=(new WechatPay())->query($number);$status=(string)($remote['trade_state']??$order['status']);$transaction=(string)($remote['transaction_id']??'');DB::updatePaymentOrder($number,$status,$transaction);echo json_encode(['ok'=>true,'status'=>$status]);}catch(Throwable $e){echo json_encode(['ok'=>true,'status'=>$order['status']]);}
