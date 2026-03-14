<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireAdmin();

$db = Database::getInstance();
$kw = trim($_GET['kw'] ?? '');
$rows = [];
$error = '';

function activityStatusLabel(string $s): string
{
    return match ($s) {
        'draft' => '草稿',
        'published' => '已发布',
        'closed' => '已结束',
        default => $s,
    };
}

if (isPost()) {
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } else {
        $action = $_POST['action'] ?? '';
        $aid = (int)($_POST['id'] ?? 0);
        if ($aid > 0) {
            if ($action === 'status') {
                $st = trim($_POST['status'] ?? '');
                if (in_array($st, ['draft', 'published', 'closed'], true)) {
                    $db->execute("UPDATE activities SET status = :s WHERE id = :id LIMIT 1", ['s' => $st, 'id' => $aid]);
                    flash('success', '状态已更新。');
                    redirect('/admin/activities.php');
                }
            } elseif ($action === 'delete') {
                $db->execute("DELETE FROM activities WHERE id = :id LIMIT 1", ['id' => $aid]);
                flash('success', '活动已删除。');
                redirect('/admin/activities.php');
            }
        }
    }
}

if ($db->isAvailable()) {
    $where = "1=1";
    $params = [];
    if ($kw !== '') {
        $where .= " AND (a.title LIKE :kw OR a.location LIKE :kw)";
        $params['kw'] = '%' . $kw . '%';
    }
    $rows = $db->query(
        "SELECT a.*, u.username, u.name AS creator_name
         FROM activities a
         JOIN users u ON u.id = a.creator_id
         WHERE {$where}
         ORDER BY a.created_at DESC
         LIMIT 200",
        $params
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1100px;">
    <!-- 顶部：页面标题 + 右侧按钮 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">活动管理</h2>
        <div class="rowFlexSimple gapSmall2x">
            <a class="btn btn-outline-secondary btn-sm" href="/admin/index.php">返回后台首页</a>
            <a class="btn btn-outline-dark btn-sm" href="/activities/publish.php">发布活动</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <form class="row g-2 align-items-center mb-3" method="get" action="/admin/activities.php">
        <div class="col-sm-9">
            <input type="text" class="form-control" name="kw" value="<?= h($kw) ?>" placeholder="搜索标题/地点">
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
                <th>状态</th>
                <th>时间</th>
                <th>地点</th>
                <th>发布者</th>
                <th style="width:320px;">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $a): ?>
                <tr>
                    <td><?= h((string)$a['id']) ?></td>
                    <td>
                        <a href="/activities/detail.php?id=<?= (int)$a['id'] ?>" target="_blank"><?= h($a['title'] ?? '') ?></a>
                    </td>
                    <td><?= h(activityStatusLabel((string)($a['status'] ?? ''))) ?></td>
                    <td><?= h(substr((string)($a['start_time'] ?? ''), 0, 16)) ?></td>
                    <td><?= h($a['location'] ?? '') ?></td>
                    <td><?= h(($a['creator_name'] ?? '') !== '' ? ($a['creator_name'] ?? '') : ($a['username'] ?? '')) ?></td>
                    <td>
                        <!-- 操作按钮：编辑/改状态/删除 -->
                        <div class="rowFlexSimple gapSmall2x rowFlexWrap">
                            <a class="btn btn-outline-dark btn-sm" href="/activities/publish.php?id=<?= (int)$a['id'] ?>">编辑</a>
                            <form method="post" action="/admin/activities.php">
                                <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                <input type="hidden" name="action" value="status">
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="draft" <?= ($a['status'] ?? '') === 'draft' ? 'selected' : '' ?>>草稿</option>
                                    <option value="published" <?= ($a['status'] ?? '') === 'published' ? 'selected' : '' ?>>已发布</option>
                                    <option value="closed" <?= ($a['status'] ?? '') === 'closed' ? 'selected' : '' ?>>已结束</option>
                                </select>
                            </form>
                            <form method="post" action="/admin/activities.php" onsubmit="return confirm('确定删除该活动吗？');">
                                <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
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

