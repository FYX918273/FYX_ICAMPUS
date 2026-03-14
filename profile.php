<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

startSession();
requireLogin();

$db = Database::getInstance();
$user = null;
$error = '';

if ($db->isAvailable()) {
    $user = $db->queryOne(
        "SELECT * FROM users WHERE id = :id LIMIT 1",
        ['id' => currentUserId()]
    );
}

if (isPost()) {
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } elseif (!$user) {
        $error = '未找到当前用户信息。';
    } else {
        $name = trim($_POST['name'] ?? '');
        $student_id = trim($_POST['student_id'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($name === '') {
            $error = '姓名不能为空。';
        } else {
            $db->execute(
                "UPDATE users SET name = :name, student_id = :sid, phone = :phone WHERE id = :id LIMIT 1",
                [
                    'name' => $name,
                    'sid' => $student_id !== '' ? $student_id : null,
                    'phone' => $phone !== '' ? $phone : null,
                    'id' => currentUserId(),
                ]
            );
            refreshCurrentUserCache();
            $_SESSION['name'] = $name;
            flash('success', '个人信息已更新。');
            redirect('/profile.php');
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 980px;">
    <h2 class="mb-3 mt-3">个人中心</h2>
    <?php if (!$user): ?>
        <div class="alert alert-warning small">未找到当前用户信息。</div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-md-7">
                <div class="border rounded p-3 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">基本信息</h5>
                        <span class="badge bg-secondary">账号：<?= h($user['username'] ?? '') ?></span>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 small mb-2"><?= h($error) ?></div>
                    <?php endif; ?>

                    <form method="post" action="/profile.php">
                        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                        <div class="mb-3">
                            <label class="form-label">姓名</label>
                            <input type="text" name="name" class="form-control" value="<?= h($_POST['name'] ?? ($user['name'] ?? '')) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">学号</label>
                            <input type="text" name="student_id" class="form-control" value="<?= h($_POST['student_id'] ?? ($user['student_id'] ?? '')) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">手机号</label>
                            <input type="text" name="phone" class="form-control" value="<?= h($_POST['phone'] ?? ($user['phone'] ?? '')) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">保存修改</button>
                    </form>
                </div>
            </div>

            <div class="col-md-5">
                <div class="border rounded p-3 bg-white">
                    <h5 class="mb-2">我的功能</h5>
                    <div class="list-group">
                        <a class="list-group-item list-group-item-action" href="/products/my.php">我的二手发布</a>
                        <a class="list-group-item list-group-item-action" href="/products/favorites.php">我的二手收藏</a>
                        <a class="list-group-item list-group-item-action" href="/lostfound/my.php">我的失物招领</a>
                        <a class="list-group-item list-group-item-action" href="/activities/my.php">我的活动报名</a>
                        <a class="list-group-item list-group-item-action" href="/shop/orders.php">我的超市订单</a>
                        <a class="list-group-item list-group-item-action" href="/forum/my.php">我的论坛帖子</a>
                    </div>
                    <div class="small text-muted mt-2">
                        说明：以上入口会在后续模块开发时逐步补齐为可用页面。
                    </div>
                </div>

                <?php if (($user['role'] ?? 'user') === 'admin'): ?>
                    <div class="border rounded p-3 bg-white mt-3">
                        <h5 class="mb-2">管理员</h5>
                        <a class="btn btn-outline-dark btn-sm" href="/admin/index.php">进入后台管理</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

