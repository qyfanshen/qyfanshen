<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
requireLogin();
require_once dirname(__DIR__) . '/api/db.php';

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function productUpload(?array $file): string {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int) $file['size'] > 5 * 1024 * 1024) throw new RuntimeException('图片上传失败或超过 5MB');
    $info = @getimagesize($file['tmp_name']);
    $mime = $info['mime'] ?? '';
    $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$mime] ?? null;
    if (!$ext) throw new RuntimeException('仅支持 JPG、PNG、WebP 或 GIF 图片');
    $dir = dirname(__DIR__) . '/uploads/products';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('无法创建图片目录');
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('图片保存失败');
    return 'uploads/products/' . $name;
}
function removeProductImage(string $path): void {
    if (str_starts_with($path, 'uploads/products/')) @unlink(dirname(__DIR__) . '/' . $path);
}

$error = '';
$edit = isset($_GET['edit']) ? DB::product((int) $_GET['edit']) : null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim(strip_tags((string) ($_POST['name'] ?? '')));
            $description = trim(strip_tags((string) ($_POST['description'] ?? '')));
            $price = (float) ($_POST['price'] ?? 0);
            $status = ($_POST['status'] ?? 'inactive') === 'active' ? 'active' : 'inactive';
            if ($name === '' || mb_strlen($name, 'UTF-8') > 120 || $price < 0 || $description === '') throw new RuntimeException('请填写商品名称、非负价格和商品描述');
            $old = $id > 0 ? DB::product($id) : null;
            $image = productUpload($_FILES['image'] ?? null);
            if ($image === '') $image = (string) ($old['image_path'] ?? '');
            DB::saveProduct(['name'=>$name,'price'=>number_format($price, 2, '.', ''),'description'=>$description,'image_path'=>$image,'status'=>$status], $id);
            if ($old && $image !== $old['image_path']) removeProductImage((string) $old['image_path']);
            header('Location: products.php?saved=1'); exit;
        }
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0); $old = DB::product($id);
            if ($id > 0) DB::deleteProduct($id);
            if ($old) removeProductImage((string) $old['image_path']);
            header('Location: products.php'); exit;
        }
    } catch (Throwable $e) { $error = $e->getMessage(); }
}
$products = DB::products();
?><!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>商城管理 · 梵燊科技</title><style>
:root{--bg:#080c16;--card:#0d1320;--line:rgba(255,255,255,.1);--text:#e2e8f0;--muted:#94a3b8;--accent:#38bdf8}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","Microsoft YaHei",sans-serif}.wrap{max-width:1200px;margin:auto;padding:30px 20px}h1{margin:0 0 8px}.sub{color:var(--muted)}.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px}.top a{color:var(--accent);text-decoration:none}.layout{display:grid;grid-template-columns:minmax(280px,360px) 1fr;gap:20px}.card{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:20px}label{display:block;color:var(--muted);font-size:.85rem;margin:14px 0 6px}input,textarea,select{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:6px;background:#111827;color:var(--text);font:inherit}textarea{min-height:110px;resize:vertical}.btn{margin-top:18px;padding:10px 16px;border:0;border-radius:6px;background:#2563eb;color:#fff;cursor:pointer}.cancel{margin-left:12px;color:var(--muted);text-decoration:none}.error{padding:10px;background:#451a1a;color:#fca5a5;border-radius:6px;margin-bottom:14px}.products{display:grid;gap:12px}.row{display:grid;grid-template-columns:80px 1fr auto;gap:15px;align-items:center;border-bottom:1px solid var(--line);padding:12px 0}.row:last-child{border:0}.thumb{width:80px;height:60px;object-fit:cover;background:#111827;border-radius:5px}.meta h2{font-size:1rem;margin:0 0 5px}.meta p{margin:0;color:var(--muted);font-size:.85rem}.actions{display:flex;gap:10px;align-items:center}.actions a,.actions button{font-size:.82rem;color:var(--accent);background:none;border:0;text-decoration:none;cursor:pointer}.actions .danger{color:#f87171}@media(max-width:760px){.layout{grid-template-columns:1fr}.row{grid-template-columns:60px 1fr}.thumb{width:60px;height:48px}.actions{grid-column:2}}
</style></head><body><div class="wrap"><div class="top"><div><h1>商城管理</h1><p class="sub">管理官网梵燊商城的商品内容</p></div><div><a href="payment.php">支付管理</a> · <a href="messages.php">留言管理</a> · <a href="../shop.php" target="_blank">查看商城</a> · <a href="logout.php">退出</a></div></div>
<?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?><div class="layout"><section class="card"><h2><?= $edit ? '编辑商品' : '上架商品' ?></h2><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>"><label>商品名称</label><input name="name" maxlength="120" required value="<?= e((string) ($edit['name'] ?? '')) ?>"><label>价格（元）</label><input name="price" type="number" min="0" step="0.01" required value="<?= e((string) ($edit['price'] ?? '0.00')) ?>"><label>商品描述</label><textarea name="description" required><?= e((string) ($edit['description'] ?? '')) ?></textarea><label>商品图片（最大 5MB）</label><input name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif"><label>状态</label><select name="status"><option value="active" <?= (($edit['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>上架</option><option value="inactive" <?= (($edit['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>下架</option></select><button class="btn" type="submit">保存商品</button><?php if ($edit): ?><a class="cancel" href="products.php">取消编辑</a><?php endif; ?></form></section><section class="card"><h2>商品列表（<?= count($products) ?>）</h2><div class="products"><?php if (!$products): ?><p class="sub">暂无商品，请先上架。</p><?php else: foreach($products as $p): ?><div class="row"><?php if ($p['image_path']): ?><img class="thumb" src="../<?= e((string)$p['image_path']) ?>" alt=""><?php else: ?><div class="thumb"></div><?php endif; ?><div class="meta"><h2><?= e((string)$p['name']) ?> · ¥<?= e(number_format((float)$p['price'],2)) ?></h2><p><?= $p['status'] === 'active' ? '已上架' : '已下架' ?> · <?= e(mb_strimwidth((string)$p['description'],0,70,'...','UTF-8')) ?></p></div><div class="actions"><a href="?edit=<?= (int)$p['id'] ?>">编辑</a><form method="post" onsubmit="return confirm('确定删除这个商品？')"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="danger" type="submit">删除</button></form></div></div><?php endforeach; endif; ?></div></section></div></div></body></html>
