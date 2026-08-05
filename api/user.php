<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/user_auth.php';
require_once __DIR__ . '/rate_limit.php';

function respond(array $data, int $status = 200): void { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }

$action = (string)($_GET['action'] ?? 'status');
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'status') {
    respond(['ok'=>true,'user'=>currentUser(),'csrf_token'=>userCsrfToken()]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['ok'=>false,'message'=>'请求方式不正确'],405);

try {
    verifyUserRequest();
    $data=json_decode(file_get_contents('php://input')?:'',true);if(!is_array($data))$data=[];
    if ($action === 'register') {
        checkRateLimit('user_register',5,600);
        $phone=preg_replace('/\s+/','',(string)($data['phone']??''));$nickname=trim(strip_tags((string)($data['nickname']??'')));$password=(string)($data['password']??'');
        if(!preg_match('/^1[3-9]\d{9}$/',$phone))throw new RuntimeException('请输入正确的手机号码');
        if(mb_strlen($nickname,'UTF-8')<2||mb_strlen($nickname,'UTF-8')>30)throw new RuntimeException('昵称需为 2-30 个字符');
        if(strlen($password)<8||strlen($password)>72)throw new RuntimeException('密码需为 8-72 个字符');
        if(empty($data['consent']))throw new RuntimeException('请先同意用户协议和隐私政策');
        if(DB::userByPhone($phone))throw new RuntimeException('该手机号已经注册');
        $id=DB::createUser($phone,$nickname,password_hash($password,PASSWORD_DEFAULT),(string)($_SERVER['REMOTE_ADDR']??''));loginUser($id);
        respond(['ok'=>true,'message'=>'注册成功','user'=>currentUser(),'csrf_token'=>userCsrfToken()]);
    }
    if ($action === 'login') {
        checkRateLimit('user_login',10,600);
        $phone=preg_replace('/\s+/','',(string)($data['phone']??''));$password=(string)($data['password']??'');$user=DB::userByPhone($phone);
        if(!$user||!password_verify($password,(string)$user['password_hash']))throw new RuntimeException('手机号或密码错误');
        loginUser((int)$user['id']);respond(['ok'=>true,'message'=>'登录成功','user'=>currentUser(),'csrf_token'=>userCsrfToken()]);
    }
    if ($action === 'logout') { logoutUser();respond(['ok'=>true,'message'=>'已退出登录','csrf_token'=>userCsrfToken()]); }
    $user=currentUser();if(!$user)respond(['ok'=>false,'message'=>'请先登录'],401);
    if ($action === 'profile') {
        $nickname=trim(strip_tags((string)($data['nickname']??'')));if(mb_strlen($nickname,'UTF-8')<2||mb_strlen($nickname,'UTF-8')>30)throw new RuntimeException('昵称需为 2-30 个字符');
        DB::updateUserProfile((int)$user['id'],$nickname);respond(['ok'=>true,'message'=>'资料已更新','user'=>currentUser()]);
    }
    if ($action === 'password') {
        $record=DB::userByPhone((string)$user['phone']);$current=(string)($data['current_password']??'');$next=(string)($data['new_password']??'');
        if(!$record||!password_verify($current,(string)$record['password_hash']))throw new RuntimeException('当前密码错误');
        if(strlen($next)<8||strlen($next)>72)throw new RuntimeException('新密码需为 8-72 个字符');
        DB::updateUserPassword((int)$user['id'],password_hash($next,PASSWORD_DEFAULT));respond(['ok'=>true,'message'=>'密码已修改']);
    }
    if ($action === 'delete') {
        checkRateLimit('user_delete',3,3600);
        $record=DB::userByPhone((string)$user['phone']);$password=(string)($data['password']??'');
        if(!$record||!password_verify($password,(string)$record['password_hash']))throw new RuntimeException('密码验证失败');
        if(empty($data['confirm']))throw new RuntimeException('请确认注销账号');
        if(DB::userHasPendingOrders((int)$user['id']))throw new RuntimeException('存在支付中或退款中的订单，暂时不能注销');
        DB::deleteUser((int)$user['id']);logoutUser();respond(['ok'=>true,'message'=>'账号已注销','csrf_token'=>userCsrfToken()]);
    }
    respond(['ok'=>false,'message'=>'未知操作'],404);
} catch (PDOException $e) {
    if((string)$e->getCode()==='23000')respond(['ok'=>false,'message'=>'该手机号已经注册'],422);
    error_log('User API database error: '.$e->getMessage());respond(['ok'=>false,'message'=>'服务器处理失败'],500);
} catch (Throwable $e) { respond(['ok'=>false,'message'=>$e->getMessage()],422); }
