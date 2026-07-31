<?php
require_once '../../includes/csrf.php';
declare(strict_types=1);

require_once 'auth.php';
requireLogin();

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'db.php';

// 数据库错误处理
try {
    DB::conn();
} catch (\Throwable $e) {
    $cfg = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'config.php';
    $isDebug = !empty($cfg['debug']);
    if (!$isDebug) {
        http_response_code(500);
        error_log('Database connection failed: ' . $e->getMessage());
        die('<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><title>数据库连接失败</title></head><body><h1>数据库连接失败</h1><p>请检查服务器数据库配置后重试。</p></body></html>');
    }
}

// 处理操作
$action = $_POST['action'] ?? '';
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') verifyCsrf();

if ($action === 'status' && $id > 0) {
    $status = $_POST['status'] ?? '';
    if (!in_array($status, ['new', 'read', 'replied'], true)) {
        http_response_code(422);
        exit('无效状态');
    }
    DB::updateStatus($id, $status);
    header('Location: messages.php');
    exit;
}
if ($action === 'delete' && $id > 0) {
    DB::delete($id);
    header('Location: messages.php');
    exit;
}

// 查询参数
$search = trim($_GET['q'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));

$result = DB::select($search, $filterStatus, $page, 15);
$rows = $result['rows'];
$total = $result['total'];
$totalPages = max(1, (int) ceil($total / $result['perPage']));
$stats = DB::stats();

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
function statusCn(string $s): string {
    return ['new' => '🆕 新提交', 'read' => '👁 已查看', 'replied' => '✅ 已回复'][$s] ?? $s;
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>咨询留言管理 · 梵燊科技</title>
  <style>
    :root { --bg: #080c16; --card: #0d1320; --border: rgba(255,255,255,.06); --text: #e2e8f0; --muted: #64748b; --accent: #3b82f6; --cyan: #06b6d4; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI","Microsoft YaHei",sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }
    .wrap { max-width: 1300px; margin: 0 auto; padding: 28px 20px; }
    h1 { font-size: 22px; margin-bottom: 8px; }
    .sub { color: var(--muted); font-size: .88rem; margin-bottom: 24px; }

    /* Stats */
    .stats { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
    .stat-card { flex: 1; min-width: 120px; background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 16px; text-align: center; }
    .stat-card .n { font-size: 28px; font-weight: 700; }
    .stat-card .l { font-size: .8rem; color: var(--muted); margin-top: 4px; }
    .stat-card.total .n { color: var(--text); }
    .stat-card.new .n { color: #f59e0b; }
    .stat-card.read .n { color: var(--accent); }
    .stat-card.replied .n { color: #10b981; }

    /* Search */
    .toolbar { display: flex; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; align-items: center; }
    .toolbar input, .toolbar select { padding: 9px 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,.12); background: #0f172a; color: #e2e8f0; font-size: .9rem; }
    .toolbar input { width: 260px; }
    .toolbar input::placeholder { color: var(--muted); }
    .toolbar button { padding: 9px 18px; border: 0; border-radius: 8px; background: linear-gradient(135deg, var(--accent), var(--cyan)); color: #fff; font-weight: 600; cursor: pointer; font-size: .9rem; }
    .toolbar a { color: var(--cyan); font-size: .85rem; text-decoration: none; }

    /* Table */
    .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
    .empty-state { padding: 60px 20px; text-align: center; color: var(--muted); }
    table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    th { text-align: left; padding: 12px 14px; background: rgba(255,255,255,.03); color: #93c5fd; font-weight: 600; font-size: .82rem; white-space: nowrap; border-bottom: 1px solid var(--border); }
    td { padding: 14px; border-bottom: 1px solid var(--border); vertical-align: top; color: #cbd5e1; }
    tr:hover td { background: rgba(59,130,246,.05); }
    .msg-cell { max-width: 320px; white-space: pre-wrap; line-height: 1.6; }
    .actions { white-space: nowrap; }
    .actions form { display: inline; }
    .actions button { border: 0; background: none; padding: 0; color: var(--cyan); font-size: .8rem; cursor: pointer; margin-right: 8px; }
    .actions button.danger { color: #f87171; }
    .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: .75rem; font-weight: 600; }
    .badge-new { background: rgba(245,158,11,.15); color: #f59e0b; }
    .badge-read { background: rgba(59,130,246,.15); color: #60a5fa; }
    .badge-replied { background: rgba(16,185,129,.15); color: #34d399; }

    /* Pagination */
    .pager { display: flex; justify-content: center; gap: 6px; padding: 20px; }
    .pager a, .pager span { display: inline-block; padding: 6px 14px; border-radius: 6px; font-size: .85rem; text-decoration: none; }
    .pager a { background: rgba(255,255,255,.06); color: var(--cyan); }
    .pager a:hover { background: rgba(59,130,246,.2); }
    .pager .cur { background: var(--accent); color: #fff; font-weight: 700; }
    .pager .gap { color: var(--muted); }

    @media (max-width: 780px) {
      thead { display: none; }
      tr { display: block; border-bottom: 2px solid var(--border); padding: 10px 0; }
      td { display: block; border: 0; padding: 5px 14px; }
      td::before { content: attr(data-label); display: block; color: #93c5fd; font-size: .75rem; margin-bottom: 2px; font-weight: 600; }
      .msg-cell { max-width: none; }
    }
  </style>
</head>
<body>
<div class="wrap">
  <h1>📋 咨询留言管理</h1>
  <p class="sub">梵燊科技官网 — 用户提交的咨询资料　<a href="products.php" style="color:var(--cyan);text-decoration:none;">商城管理</a>　<a href="payment.php" style="color:var(--cyan);text-decoration:none;">支付管理</a>　<a href="../shop.php" target="_blank" style="color:var(--cyan);text-decoration:none;">查看商城</a></p>

  <!-- 统计卡片 -->
  <div class="stats">
    <div class="stat-card total"><div class="n"><?= $stats['total'] ?></div><div class="l">全部留言</div></div>
    <div class="stat-card new"><div class="n"><?= $stats['new_count'] ?></div><div class="l">🆕 待处理</div></div>
    <div class="stat-card read"><div class="n"><?= $stats['read_count'] ?></div><div class="l">👁 已查看</div></div>
    <div class="stat-card replied"><div class="n"><?= $stats['replied_count'] ?></div><div class="l">✅ 已回复</div></div>
  </div>

  <!-- 搜索/筛选 -->
  <form class="toolbar" method="get">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="搜索姓名 / 电话 / 公司...">
    <select name="status">
      <option value="">全部状态</option>
      <option value="new" <?= $filterStatus === 'new' ? 'selected' : '' ?>>🆕 新提交</option>
      <option value="read" <?= $filterStatus === 'read' ? 'selected' : '' ?>>👁 已查看</option>
      <option value="replied" <?= $filterStatus === 'replied' ? 'selected' : '' ?>>✅ 已回复</option>
    </select>
    <button type="submit">筛选</button>
    <?php if ($search !== '' || $filterStatus !== ''): ?>
      <a href="messages.php">清除筛选</a>
    <?php endif; ?>
  </form>

  <!-- 表格 -->
  <div class="card">
    <?php if (!$rows): ?>
      <div class="empty-state">暂无留言数据</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>ID</th><th>提交时间</th><th>姓名</th><th>手机</th><th>公司</th><th>邮箱</th><th>关注方向</th><th>需求描述</th><th>状态</th><th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td data-label="ID"><?= $r['id'] ?></td>
            <td data-label="提交时间"><?= e($r['created_at']) ?></td>
            <td data-label="姓名"><?= e($r['name']) ?></td>
            <td data-label="手机"><?= e($r['phone']) ?></td>
            <td data-label="公司"><?= e($r['company'] ?: '-') ?></td>
            <td data-label="邮箱"><?= e($r['email'] ?: '-') ?></td>
            <td data-label="关注方向"><?= e($r['interest'] ?: '-') ?></td>
            <td data-label="需求描述" class="msg-cell"><?= e($r['message']) ?></td>
            <td data-label="状态"><span class="badge badge-<?= $r['status'] ?>"><?= statusCn($r['status']) ?></span></td>
            <td data-label="操作" class="actions">
              <?php if ($r['status'] === 'new'): ?>
                <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="status" value="read"><button type="submit">标记已读</button></form>
              <?php elseif ($r['status'] === 'read'): ?>
                <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="status" value="replied"><button type="submit">标记已回复</button></form>
              <?php endif; ?>
              <?php if ($r['status'] !== 'new'): ?>
                <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="status" value="new"><button type="submit">重置</button></form>
              <?php endif; ?>
              <form method="post" onsubmit="return confirm('确定删除这条记录？')"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button type="submit" class="danger">删除</button></form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- 分页 -->
      <?php if ($totalPages > 1): ?>
      <div class="pager">
        <?php
        $params = http_build_query(['q' => $search, 'status' => $filterStatus]);
        $prev = max(1, $page - 1);
        $next = min($totalPages, $page + 1);
        ?>
        <?php if ($page > 1): ?><a href="?<?= $params ?>&page=<?= $prev ?>">← 上一页</a><?php endif; ?>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <?php if ($p === $page): ?><span class="cur"><?= $p ?></span>
          <?php elseif ($p === 1 || $p === $totalPages || abs($p - $page) <= 2): ?>
            <a href="?<?= $params ?>&page=<?= $p ?>"><?= $p ?></a>
          <?php elseif ($p === 2 || $p === $totalPages - 1): ?>
            <span class="gap">…</span>
          <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="?<?= $params ?>&page=<?= $next ?>">下一页 →</a><?php endif; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- 底部信息 -->
  <div style="text-align:center;padding:16px;color:var(--muted);font-size:.75rem;">
    引擎: <?= e(DB::driver() === 'mysql' ? 'MariaDB' : 'SQLite（本地开发）') ?> | 梵燊科技 © <?= date('Y') ?>
  </div>
</div>
</body>
</html>
