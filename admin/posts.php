<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireAdmin();

$db = Database::getInstance();
$kw = trim($_GET['kw'] ?? '');
$rows = [];
$error = '';

if (isPost()) {
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } else {
        $action = $_POST['action'] ?? '';
        $pid = (int)($_POST['id'] ?? 0);
        if ($pid > 0) {
            if ($action === 'delete') {
                $db->execute("UPDATE posts SET status = 0 WHERE id = :id LIMIT 1", ['id' => $pid]);
                flash('success', '帖子已删除（软删除）。');
                redirect('/admin/posts.php');
            } elseif ($action === 'top') {
                $v = (int)($_POST['value'] ?? 0) === 1 ? 1 : 0;
                $db->execute("UPDATE posts SET is_top = :v WHERE id = :id LIMIT 1", ['v' => $v, 'id' => $pid]);
                flash('success', '置顶状态已更新。');
                redirect('/admin/posts.php');
            }
        }
    }
}

if ($db->isAvailable()) {
    $where = "p.status = 1";
    $params = [];
    if ($kw !== '') {
        $where .= " AND (p.title LIKE :kw OR p.content LIKE :kw OR u.username LIKE :kw OR u.name LIKE :kw)";
        $params['kw'] = '%' . $kw . '%';
    }
    $rows = $db->query(
        "SELECT p.*, u.username, u.name AS user_name
         FROM posts p
         JOIN users u ON u.id = p.user_id
         WHERE {$where}
         ORDER BY p.is_top DESC, p.created_at DESC
         LIMIT 200",
        $params
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1100px;">
    <!-- 顶部：页面标题 + 返回入口 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">帖子管理</h2>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/index.php">返回后台首页</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <form class="row g-2 align-items-center mb-3" method="get" action="/admin/posts.php">
        <div class="col-sm-9">
            <input type="text" class="form-control" name="kw" value="<?= h($kw) ?>" placeholder="搜索标题/内容/作者">
        </div>
        <div class="col-sm-3 d-grid">
            <button class="btn btn-outline-dark" type="submit">搜索</button>
        </div>
    </form>

    <div class="table-responsive border rounded bg-white">
        <table class="table table-sm mb-0 align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>标题</th>
                <th>作者</th>
                <th>浏览</th>
                <th>点赞</th>
                <th>置顶</th>
                <th>创建时间</th>
                <th style="width:260px;">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $p): ?>
                <tr>
                    <td><?= h((string)$p['id']) ?></td>
                    <td><a href="/forum/post.php?id=<?= (int)$p['id'] ?>" target="_blank"><?= h($p['title'] ?? '') ?></a></td>
                    <td><?= h(($p['user_name'] ?? '') !== '' ? ($p['user_name'] ?? '') : ($p['username'] ?? '')) ?></td>
                    <td><?= h((string)($p['views'] ?? 0)) ?></td>
                    <td><?= h((string)($p['likes'] ?? 0)) ?></td>
                    <td><?= !empty($p['is_top']) ? '是' : '否' ?></td>
                    <td><?= h(substr((string)($p['created_at'] ?? ''), 0, 16)) ?></td>
                    <td>
                        <!-- 操作按钮：置顶/取消置顶 + 删除 -->
                        <div class="rowFlexSimple gapSmall2x rowFlexWrap">
                            <form method="post" action="/admin/posts.php">
                                <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <input type="hidden" name="action" value="top">
                                <input type="hidden" name="value" value="<?= !empty($p['is_top']) ? 0 : 1 ?>">
                                <button class="btn btn-outline-dark btn-sm" type="submit">
                                    <?= !empty($p['is_top']) ? '取消置顶' : '置顶' ?>
                                </button>
                            </form>
                            <form method="post" action="/admin/posts.php" onsubmit="return confirm('确定删除该帖子吗？');">
                                <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-outline-danger btn-sm" type="submit">删除</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

