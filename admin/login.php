<?php declare(strict_types=1); require_once 'auth.php';
if (isLoggedIn()) { header('Location: messages.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $attempts = (int) ($_SESSION['login_attempts'] ?? 0);
    $blockedUntil = (int) ($_SESSION['login_blocked_until'] ?? 0);
    if ($blockedUntil > time()) {
        $error = '尝试次数过多，请稍后再试';
    } elseif (doLogin($_POST['password'] ?? '')) {
        unset($_SESSION['login_attempts'], $_SESSION['login_blocked_until']);
        header('Location: messages.php'); exit;
    } else {
        $attempts++;
        $_SESSION['login_attempts'] = $attempts;
        if ($attempts >= 5) $_SESSION['login_blocked_until'] = time() + 300;
        $error = '密码错误';
    }
}?>
<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="robots" content="noindex"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>管理员登录 - 清远凡神</title>
<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,"Microsoft YaHei",sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}.login-box{background:#fff;border-radius:12px;padding:40px;width:360px;box-shadow:0 20px 60px rgba(0,0,0,0.3)}.login-box h1{text-align:center;font-size:22px;color:#333;margin-bottom:8px}.login-box .subtitle{text-align:center;font-size:13px;color:#999;margin-bottom:30px}.login-box input[type="password"]{width:100%;padding:12px 16px;border:2px solid #e0e0e0;border-radius:8px;font-size:16px;outline:none}.login-box input[type="password"]:focus{border-color:#667eea}.login-box button{width:100%;padding:12px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border:none;border-radius:8px;font-size:16px;cursor:pointer;margin-top:20px}.error{background:#fff0f0;color:#c00;padding:10px;border-radius:6px;margin-bottom:16px;text-align:center;font-size:14px}</style>
</head><body><div class="login-box"><h1>管理员登录</h1><div class="subtitle">清远凡神人工智能 · 后台管理</div>
<?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<form method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>"><input type="password" name="password" placeholder="请输入管理密码" required autofocus><button type="submit">登 录</button></form>
<a href="/" style="display:block;text-align:center;margin-top:20px;color:#999;text-decoration:none;font-size:13px">← 返回首页</a>
</div></body></html>
