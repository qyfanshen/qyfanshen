<?php
declare(strict_types=1);
require_once __DIR__ . '/api/db.php';

try {
    $products = DB::products(true);
} catch (Throwable $e) {
    error_log('Shop database error: ' . $e->getMessage());
    $products = [];
}
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function money(mixed $value): string { return number_format((float) $value, 2); }
?><!doctype html>
<html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>梵燊商城 · 梵燊科技</title>
<link rel="stylesheet" href="css/user-auth.css?v=20260731a">
<style>
:root{--bg:#080c16;--panel:#0f172a;--line:rgba(255,255,255,.1);--text:#e2e8f0;--muted:#94a3b8;--accent:#38bdf8}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","Microsoft YaHei",sans-serif}.top{border-bottom:1px solid var(--line);background:rgba(8,12,22,.92);position:sticky;top:0;z-index:2}.top-inner{max-width:1180px;margin:auto;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{color:#fff;text-decoration:none;font-weight:700;font-size:1.2rem}.brand b{display:inline-flex;width:34px;height:34px;align-items:center;justify-content:center;background:#2563eb;border-radius:8px;margin-right:8px}.back{color:var(--muted);text-decoration:none}.back:hover{color:#fff}.hero{max-width:1180px;margin:auto;padding:70px 24px 38px}.hero h1{font-size:clamp(2rem,5vw,3.6rem);margin:0 0 12px}.hero p{color:var(--muted);max-width:620px;line-height:1.8}.grid{max-width:1180px;margin:auto;padding:10px 24px 80px;display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:22px}.product{background:var(--panel);border:1px solid var(--line);border-radius:10px;overflow:hidden}.product img{width:100%;aspect-ratio:4/3;object-fit:cover;background:#111827;display:block}.placeholder{aspect-ratio:4/3;display:grid;place-items:center;color:#64748b;background:#111827}.body{padding:20px}.name{font-size:1.1rem;font-weight:700;margin:0 0 10px}.price{color:#67e8f9;font-size:1.3rem;font-weight:700}.desc{color:var(--muted);line-height:1.7;white-space:pre-wrap;margin:14px 0 0;font-size:.92rem}.buy{display:block;margin-top:18px;padding:11px;text-align:center;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;font-weight:700}.buy:hover{background:#1d4ed8}.empty{text-align:center;grid-column:1/-1;color:var(--muted);padding:70px 0}.foot{text-align:center;color:#64748b;padding:30px}
</style></head><body>
<header class="top"><div class="top-inner"><a class="brand" href="/"><b>F</b>梵燊科技</a><div class="user-nav"><span data-auth-guest><button type="button" data-open-login>登录</button></span><span data-auth-guest><button type="button" class="user-register" data-open-register>注册</button></span><span data-auth-user hidden><a class="user-account" href="account.php"><span data-user-name></span> · 用户中心</a></span><span data-auth-user hidden><button type="button" data-user-logout>退出</button></span><a class="back" href="/">返回官网</a></div></div></header>
<main><section class="hero"><h1>梵燊商城</h1><p>精选 AI 企业服务与产业升级产品，为企业提供可落地的智能化解决方案。</p></section>
<section class="grid"><?php if (!$products): ?><div class="empty">商城正在准备中，敬请期待。</div><?php else: foreach ($products as $product): ?><article class="product">
<?php if ($product['image_path'] !== ''): ?><img src="<?= h((string) $product['image_path']) ?>" alt="<?= h((string) $product['name']) ?>"><?php else: ?><div class="placeholder">梵燊商城</div><?php endif; ?><div class="body"><h2 class="name"><?= h((string) $product['name']) ?></h2><div class="price">¥<?= money($product['price']) ?></div><?php if ((string) $product['description'] !== ''): ?><p class="desc"><?= h((string) $product['description']) ?></p><?php endif; ?><a class="buy" data-require-auth href="pay.php?product=<?= (int)$product['id'] ?>">立即购买</a></div></article><?php endforeach; endif; ?></section></main>
<footer class="foot">© 2026 清远梵燊人工智能科技有限公司<br><a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener">粤ICP备2026080863号</a> | 公安联网备案号待取得后补充</footer><script src="js/user-auth.js?v=20260731a"></script></body></html>
