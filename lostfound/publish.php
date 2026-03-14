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
        $content = trim($_POST['content'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $place = trim($_POST['place'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $happen_time = trim($_POST['happen_time'] ?? '');

        if ($title === '') {
            $error = '标题不能为空。';
        } elseif (!in_array($type, ['lost', 'found'], true)) {
            $error = '请选择类型。';
        } else {
            $img = uploadImage('image', 'lostfound');
            if (!$img['ok']) {
                $error = $img['error'] ?? '图片上传失败。';
            } else {
                $db->execute(
                    "INSERT INTO lost_found (user_id,title,content,type,status,place,contact,happen_time,image_url,created_at)
                     VALUES (:uid,:t,:c,:type,'open',:p,:contact,:ht,:img,NOW())",
                    [
                        'uid' => currentUserId(),
                        't' => $title,
                        'c' => $content !== '' ? $content : null,
                        'type' => $type,
                        'p' => $place !== '' ? $place : null,
                        'contact' => $contact !== '' ? $contact : null,
                        'ht' => $happen_time !== '' ? $happen_time : null,
                        'img' => $img['path'] ?? null,
                    ]
                );
                flash('success', '发布成功。');
                redirect('/lostfound/my.php');
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 760px;">
    <!-- 顶部：页面标题 + 返回列表 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">发布失物/招领</h2>
        <a class="btn btn-outline-secondary btn-sm" href="/lostfound/list.php">返回列表</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/lostfound/publish.php" class="border rounded p-3 bg-white" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">

        <div class="mb-3">
            <label class="form-label">标题</label>
            <input type="text" name="title" class="form-control" value="<?= h($_POST['title'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">类型</label>
            <select class="form-select" name="type">
                <option value="">请选择</option>
                <option value="lost" <?= ($_POST['type'] ?? '') === 'lost' ? 'selected' : '' ?>>寻物（丢失）</option>
                <option value="found" <?= ($_POST['type'] ?? '') === 'found' ? 'selected' : '' ?>>招领（捡到）</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">地点（可选）</label>
            <input type="text" name="place" class="form-control" value="<?= h($_POST['place'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">时间（可选）</label>
            <input type="datetime-local" name="happen_time" class="form-control" value="<?= h($_POST['happen_time'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">联系方式（可选）</label>
            <input type="text" name="contact" class="form-control" value="<?= h($_POST['contact'] ?? '') ?>" placeholder="手机号/微信/QQ 等">
        </div>
        <div class="mb-3">
            <label class="form-label">详情（可选）</label>
            <textarea name="content" class="form-control" rows="5"><?= h($_POST['content'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">图片（可选，≤2MB）</label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>

        <button class="btn btn-primary" type="submit">发布</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

