<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

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
        $lid = (int)($_POST['id'] ?? 0);
        if ($lid > 0) {
            if ($action === 'close') {
                $db->execute("UPDATE lost_found SET status = 'closed' WHERE id = :id LIMIT 1", ['id' => $lid]);
                flash('success', '已标记为已解决。');
                redirect('/admin/lostfound.php');
            } elseif ($action === 'delete') {
                $img = null;
                $row = $db->queryOne(
                    "SELECT image_url FROM lost_found WHERE id = :id LIMIT 1",
                    ['id' => $lid]
                );
                if ($row && !empty($row['image_url'])) {
                    $img = (string)$row['image_url'];
                }
                $db->execute("DELETE FROM lost_found WHERE id = :id LIMIT 1", ['id' => $lid]);
                if ($img) {
                    deleteUploadedFile($img);
                }
                flash('success', '已删除记录。');
                redirect('/admin/lostfound.php');
            }
        }
    }
}

if ($db->isAvailable()) {
    $where = "1=1";
    $params = [];
    if ($kw !== '') {
        $where .= " AND (lf.title LIKE :kw OR lf.content LIKE :kw OR lf.place LIKE :kw OR u.username LIKE :kw OR u.name LIKE :kw)";
        $params['kw'] = '%' . $kw . '%';
    }
    $rows = $db->query(
        "SELECT lf.*, u.username, u.name AS user_name
         FROM lost_found lf
         JOIN users u ON u.id = lf.user_id
         WHERE {$where}
         ORDER BY lf.created_at DESC
         LIMIT 200",
        $params
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1100px;">
    <!-- 顶部：页面标题 + 返回入口 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">失物招领管理</h2>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/index.php">返回后台首页</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <form class="row g-2 align-items-center mb-3" method="get" action="/admin/lostfound.php">
        <div class="col-sm-9">
            <input type="text" class="form-control" name="kw" value="<?= h($kw) ?>" placeholder="搜索标题/地点/发布者">
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
                <th>类型</th>
                <th>状态</th>
                <th>地点</th>
                <th>发布者</th>
                <th>创建时间</th>
                <th style="width:240px;">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $lf): ?>
                <tr>
                    <td><?= h((string)$lf['id']) ?></td>
                    <td>
                        <a href="/lostfound/detail.php?id=<?= (int)$lf['id'] ?>" target="_blank"><?= h($lf['title'] ?? '') ?></a>
                    </td>
                    <td><?= h(($lf['type'] ?? '') === 'found' ? '招领' : '寻物') ?></td>
                    <td><?= h(($lf['status'] ?? '') === 'closed' ? '已解决' : '进行中') ?></td>
                    <td><?= h($lf['place'] ?? '') ?></td>
                    <td><?= h(($lf['user_name'] ?? '') !== '' ? ($lf['user_name'] ?? '') : ($lf['username'] ?? '')) ?></td>
                    <td><?= h(substr((string)($lf['created_at'] ?? ''), 0, 16)) ?></td>
                    <td>
                        <!-- 操作按钮：关闭/删除 -->
                        <div class="rowFlexSimple gapSmall2x rowFlexWrap">
                            <?php if (($lf['status'] ?? 'open') !== 'closed'): ?>
                                <form method="post" action="/admin/lostfound.php">
                                    <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$lf['id'] ?>">
                                    <input type="hidden" name="action" value="close">
                                    <button class="btn btn-outline-success btn-sm" type="submit">关闭</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="/admin/lostfound.php" onsubmit="return confirm('确定删除该记录吗？');">
                                <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$lf['id'] ?>">
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

