<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

startSession();

$db = Database::getInstance();
$error = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();
    $username = trim($_POST['username'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $password2  = trim($_POST['password2'] ?? '');
    $name       = trim($_POST['name'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');

    if (
        $username === '' ||
        $password === '' ||
        $password2 === '' ||
        $name === '' ||
        $student_id === '' ||
        $phone === ''
    ) {
        $error = '所有字段均为必填，请完整填写。';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        $error = '密码须至少 8 位，且同时包含大写字母、小写字母和数字，例如：Abc12345。';
    } elseif ($password !== $password2) {
        $error = '两次输入的密码不一致。';
    } elseif (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } else {
        $exists = $db->queryOne(
            "SELECT id FROM users WHERE username = :u LIMIT 1",
            ['u' => $username]
        );
        if ($exists) {
            $error = '该用户名已经存在，请换一个。';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username,password,name,student_id,phone,role,status,created_at)
                    VALUES (:u,:p,:n,:s,:ph,'user',1,NOW())";
            $db->execute($sql, [
                'u' => $username,
                'p' => $hash,
                'n' => $name,
                's' => $student_id,
                'ph' => $phone,
            ]);
            $ok = '注册成功，请使用新账号登录。';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 480px;">
    <h2 class="mb-3 mt-3">用户注册</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?php echo h($error); ?></div>
    <?php elseif ($ok): ?>
        <div class="alert alert-success py-2 small"><?php echo h($ok); ?></div>
    <?php endif; ?>

    <form method="post" action="register.php" class="border rounded p-3 bg-white">
        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
        <div class="mb-3">
            <label class="form-label">用户名（登录账号）</label>
            <input type="text" name="username" class="form-control" required
                   value="<?php echo h($_POST['username'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">密码</label>
            <input type="password" name="password" class="form-control" required
                   placeholder="至少 8 位，需包含大小写字母和数字，例如 Abc12345">
        </div>
        <div class="mb-3">
            <label class="form-label">确认密码</label>
            <input type="password" name="password2" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">学生姓名（仅展示）</label>
            <input type="text" name="name" class="form-control" required
                   value="<?php echo h($_POST['name'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">学号</label>
            <input type="text" name="student_id" class="form-control" required
                   value="<?php echo h($_POST['student_id'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">手机号</label>
            <input type="text" name="phone" class="form-control" required
                   value="<?php echo h($_POST['phone'] ?? ''); ?>">
        </div>
        <button type="submit" class="btn btn-primary w-100">注册</button>
        <div class="mt-3 small text-center">
            已有账号？<a href="login.php">去登录</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

