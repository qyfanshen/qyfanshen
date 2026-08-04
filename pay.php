<?php
declare(strict_types=1);
require_once __DIR__ . '/api/wechat_pay.php';
require_once __DIR__ . '/api/user_auth.php';

$user = requireUser();
$productId = (int)($_GET['product'] ?? 0);
$product = $productId > 0 ? DB::product($productId) : null;
if ($product && $product['status'] !== 'active') $product = null;
$cfg = (new WechatPay())->publicConfig();
$csrf = userCsrfToken();
?><!doctype html>
<html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($product ? (string)$product['name'] : $cfg['name']) ?> · 梵燊商城</title>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<style>
*{box-sizing:border-box}body{margin:0;background:#080c16;color:#e2e8f0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","Microsoft YaHei",sans-serif;min-height:100vh}.top{padding:18px 24px;border-bottom:1px solid rgba(255,255,255,.1);display:flex;justify-content:space-between}.top a{color:#fff;text-decoration:none;font-weight:700}.top .account{color:#67e8f9;font-weight:500}.wrap{max-width:460px;margin:50px auto;padding:0 20px}.panel{background:#0d1320;border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:28px}h1{font-size:1.6rem;margin-top:0}.sub{color:#94a3b8;line-height:1.7}.product{display:flex;gap:14px;align-items:center;padding:14px;background:#111827;border-radius:7px;margin:18px 0}.product img{width:76px;height:66px;object-fit:cover;border-radius:5px}.product-name{font-weight:700}.product-price{color:#67e8f9;font-size:1.25rem;margin-top:6px}.btn{width:100%;margin-top:22px;padding:13px;border:0;border-radius:6px;background:#16a34a;color:#fff;font-size:1rem;cursor:pointer}.btn:disabled{opacity:.5}.qr{display:none;text-align:center}.qr.active{display:block}.qr-box{width:236px;height:236px;background:#fff;padding:10px;margin:20px auto}.qr-box img{margin:auto}.amount{font-size:1.8rem;font-weight:700}.status{color:#94a3b8}.success{color:#4ade80;font-size:1.2rem;font-weight:700}.error{color:#fca5a5;margin-top:15px}.back{color:#38bdf8;cursor:pointer;background:none;border:0;margin-top:16px}
</style></head><body>
<header class="top"><a href="shop.php">梵燊商城</a><a class="account" href="account.php"><?= htmlspecialchars((string)$user['nickname']) ?> · 用户中心</a></header>
<main class="wrap"><section class="panel"><div id="formView"><h1>确认购买</h1>
<?php if (!$product): ?><p class="error">请选择有效商品后再进行支付。</p><a class="back" href="shop.php">返回商城</a>
<?php elseif (!$cfg['enabled']): ?><p class="error">支付功能暂未启用</p>
<?php else: ?><p class="sub"><?= htmlspecialchars($cfg['note'] ?: '请确认商品和金额后生成微信支付二维码。') ?></p>
<div class="product"><?php if ($product['image_path']): ?><img src="<?= htmlspecialchars((string)$product['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt=""><?php endif; ?><div><div class="product-name"><?= htmlspecialchars((string)$product['name']) ?></div><div class="product-price">¥<?= number_format((float)$product['price'], 2) ?></div></div></div>
<button class="btn" id="payBtn">微信扫码支付</button><p class="error" id="error"></p><?php endif; ?></div>
<div class="qr" id="qrView"><div class="amount" id="amountText"></div><div class="qr-box" id="qrcode"></div><p class="status" id="status">请使用微信扫码支付</p><button class="back" onclick="location.reload()">重新生成</button></div>
</section></main>
<script>
const productId=<?= json_encode($productId) ?>,productPrice=<?= json_encode($product ? number_format((float)$product['price'],2,'.','') : '0.00') ?>,csrf=<?= json_encode($csrf) ?>;
const btn=document.getElementById('payBtn');
if(btn)btn.onclick=async()=>{const err=document.getElementById('error');err.textContent='';btn.disabled=true;try{const r=await fetch('api/payment_create.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:JSON.stringify({product_id:productId})});const d=await r.json();if(!r.ok||!d.ok)throw new Error(d.message||'创建支付失败');document.getElementById('formView').style.display='none';document.getElementById('qrView').classList.add('active');document.getElementById('amountText').textContent='¥'+productPrice;new QRCode(document.getElementById('qrcode'),{text:d.code_url,width:216,height:216});poll(d.order_no);}catch(e){err.textContent=e.message;btn.disabled=false;}};
function poll(no){let count=0;const timer=setInterval(async()=>{if(++count>120){clearInterval(timer);document.getElementById('status').textContent='二维码已超时，请重新生成';return;}try{const r=await fetch('api/payment_status.php?order_no='+encodeURIComponent(no));const d=await r.json();if(d.status==='SUCCESS'){clearInterval(timer);document.getElementById('status').className='success';document.getElementById('status').textContent='支付成功';}}catch(e){}},3000);}
</script><footer style="max-width:460px;margin:26px auto 40px;padding:0 20px;color:#64748b;text-align:center;font-size:.8rem;line-height:1.8">© 2026 清远梵燊人工智能科技有限公司<br><a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener" style="color:#38bdf8">粤ICP备2026080863号</a> | 公安联网备案号待取得后补充</footer></body></html>
