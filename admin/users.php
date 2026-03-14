<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireAdmin();

$db = Database::getInstance();
$kw = trim($_GET['kw'] ?? '');
$rows = [];
$error = '';

function userRoleLabel(string $r): string
{
    return match ($r) {
        'admin' => '管理员',
        'user' => '普通用户',
        default => $r,
    };
}

if (isPost()) {
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } else {
        $action = $_POST['action'] ?? '';
        $uid = (int)($_POST['id'] ?? 0);
        if ($uid > 0) {
            $u = $db->queryOne("SELECT * FROM users WHERE id = :id LIMIT 1", ['id' => $uid]);
            if ($u && ($u['role'] ?? 'user') !== 'admin') {
                if ($action === 'disable') {
                    $db->execute("UPDATE users SET status = 0 WHERE id = :id LIMIT 1", ['id' => $uid]);
                    flash('success', '已禁用用户。');
                    redirect('/admin/users.php');
                } elseif ($action === 'enable') {
                    $db->execute("UPDATE users SET status = 1 WHERE id = :id LIMIT 1", ['id' => $uid]);
                    flash('success', '已启用用户。');
                    redirect('/admin/users.php');
                } elseif ($action === 'delete') {
                    $db->execute("DELETE FROM users WHERE id = :id LIMIT 1", ['id' => $uid]);
                    flash('success', '已删除用户。');
                    redirect('/admin/users.php');
                }
            }
        }
    }
}

if ($db->isAvailable()) {
    $where = "1=1";
    $params = [];
    if ($kw !== '') {
        $where .= " AND (username LIKE :kw OR name LIKE :kw OR student_id LIKE :kw OR phone LIKE :kw)";
        $params['kw'] = '%' . $kw . '%';
    }
    $rows = $db->query("SELECT * FROM users WHERE {$where} ORDER BY created_at DESC LIMIT 200", $params);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1100px;">
    <!-- 顶部：页面标题 + 返回入口 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">用户管理</h2>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/index.php">返回后台首页</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <form class="row g-2 align-items-center mb-3" method="get" action="/admin/users.php">
        <div class="col-sm-9">
            <input type="text" class="form-control" name="kw" value="<?= h($kw) ?>" placeholder="搜索用户名/姓名/学号/手机号">
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
                <th>用户名</th>
                <th>姓名</th>
                <th>学号</th>
                <th>手机号</th>
                <th>角色</th>
                <th>状态</th>
                <th>创建时间</th>
                <th style="width:200px;">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $u): ?>
                <tr>
                    <td><?= h((string)$u['id']) ?></td>
                    <td><?= h($u['username'] ?? '') ?></td>
                    <td><?= h($u['name'] ?? '') ?></td>
                    <td><?= h($u['student_id'] ?? '') ?></td>
                    <td><?= h($u['phone'] ?? '') ?></td>
                    <td><?= h(userRoleLabel((string)($u['role'] ?? ''))) ?></td>
                    <td><?= (int)($u['status'] ?? 1) === 1 ? '正常' : '禁用' ?></td>
                    <td><?= h(substr((string)($u['created_at'] ?? ''), 0, 16)) ?></td>
                    <td>
                        <?php if (($u['role'] ?? 'user') === 'admin'): ?>
                            <span class="text-muted small">管理员不可操作</span>
                        <?php else: ?>
                            <!-- 操作按钮：禁用/启用/删除 -->
                            <div class="rowFlexSimple gapSmall2x rowFlexWrap">
                                <?php if ((int)($u['status'] ?? 1) === 1): ?>
                                    <form method="post" action="/admin/users.php" onsubmit="return confirm('确定禁用该用户吗？');">
                                        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="action" value="disable">
                                        <button class="btn btn-outline-warning btn-sm" type="submit">禁用</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="/admin/users.php">
                                        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="action" value="enable">
                                        <button class="btn btn-outline-success btn-sm" type="submit">启用</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="/admin/users.php" onsubmit="return confirm('确定删除该用户吗？相关数据会级联删除。');">
                                    <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button class="btn btn-outline-danger btn-sm" type="submit">删除</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

