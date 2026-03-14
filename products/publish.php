<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

startSession();
requireLogin();

$db = Database::getInstance();
$error = '';

if (isPost()) {
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = trim($_POST['price'] ?? '0');
        $category = trim($_POST['category'] ?? '');

        if ($title === '') {
            $error = '标题不能为空。';
        } elseif (!is_numeric($price) || (float)$price < 0) {
            $error = '价格格式不正确。';
        } else {
            $img = uploadImage('image', 'products');
            if (!$img['ok']) {
                $error = $img['error'] ?? '图片上传失败。';
            } else {
                $db->execute(
                    "INSERT INTO products (user_id,title,description,price,image_url,category,status,created_at)
                     VALUES (:uid,:t,:d,:p,:img,:c,'on',NOW())",
                    [
                        'uid' => currentUserId(),
                        't' => $title,
                        'd' => $description !== '' ? $description : null,
                        'p' => (float)$price,
                        'img' => $img['path'] ?? null,
                        'c' => $category !== '' ? $category : null,
                    ]
                );
                flash('success', '发布成功。');
                redirect('/products/my.php');
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 760px;">
    <!-- 顶部：页面标题 + 返回列表 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">发布二手</h2>
        <a class="btn btn-outline-secondary btn-sm" href="/products/list.php">返回列表</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/products/publish.php" class="border rounded p-3 bg-white" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">

        <div class="mb-3">
            <label class="form-label">标题</label>
            <input type="text" name="title" class="form-control" value="<?= h($_POST['title'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">分类（可选）</label>
            <input type="text" name="category" class="form-control" value="<?= h($_POST['category'] ?? '') ?>" placeholder="如：数码/书籍/生活用品">
        </div>
        <div class="mb-3">
            <label class="form-label">价格</label>
            <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= h($_POST['price'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">描述（可选）</label>
            <textarea name="description" class="form-control" rows="5"><?= h($_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">图片（可选，≤2MB）</label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>

        <button class="btn btn-primary" type="submit">发布</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

