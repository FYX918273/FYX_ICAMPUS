<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

startSession();

$db = Database::getInstance();
$error = '';
$redirectTo = isset($_GET['redirect']) ? trim((string)$_GET['redirect']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $redirectTo = trim((string)($_POST['redirect'] ?? $redirectTo));

    if ($username === '' || $password === '') {
        $error = '用户名和密码不能为空。';
    } elseif ($db->isAvailable()) {
        $row = $db->queryOne(
            "SELECT * FROM users WHERE username = :u LIMIT 1",
            ['u' => $username]
        );

        if ($row && (int)($row['status'] ?? 1) !== 1) {
            $error = '该账号已被禁用，请联系管理员。';
        } elseif ($row && password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_role'] = $row['role'] ?? 'user';
            $_SESSION['username'] = $row['username'];
            $_SESSION['name'] = $row['name'] ?? '';

            // 登录成功后优先跳回来源页（仅允许站内相对路径）
            if ($redirectTo !== '' && str_starts_with($redirectTo, '/')) {
                redirect($redirectTo);
            }
            redirect('/index.php');
        } else {
            $error = '用户名或密码错误。';
        }
    } else {
        $error = '数据库连接失败，请稍后再试。';
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 420px;">
    <h2 class="mb-3 mt-3">用户登录</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?php echo h($error); ?></div>
    <?php endif; ?>

    <form method="post" action="login.php<?= $redirectTo !== '' ? ('?redirect=' . urlencode($redirectTo)) : '' ?>" class="border rounded p-3 bg-white">
        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="redirect" value="<?= h($redirectTo) ?>">
        <div class="mb-3">
            <label class="form-label">用户名</label>
            <input type="text" name="username" class="form-control" value="<?php echo h($_POST['username'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">密码</label>
            <input type="password" name="password" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary w-100">登录</button>
        <div class="mt-3 small text-center">
            还没有账号？<a href="register.php">去注册</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

