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

        if ($title === '') {
            $error = '标题不能为空。';
        } elseif ($content === '') {
            $error = '内容不能为空。';
        } else {
            $imgs = uploadImages('images', 'forum', 9, 8388608, 1600);
            if (!$imgs['ok']) {
                $error = $imgs['error'] ?? '上传失败。';
            } else {
                $paths = $imgs['paths'] ?? [];
                $cover = $paths[0] ?? null;

                try {
                    $db->beginTransaction();
                    $db->execute(
                        "INSERT INTO posts (user_id,title,content,image_url,views,likes,is_top,status,created_at)
                         VALUES (:uid,:t,:c,:img,0,0,0,1,NOW())",
                        [
                            'uid' => currentUserId(),
                            't' => $title,
                            'c' => $content,
                            'img' => $cover,
                        ]
                    );
                    $newId = $db->lastInsertId();

                    if (!empty($paths)) {
                        foreach ($paths as $order => $p) {
                            $db->execute(
                                "INSERT INTO post_images (post_id,image_url,sort_order,created_at)
                                 VALUES (:pid,:url,:ord,NOW())",
                                ['pid' => $newId, 'url' => $p, 'ord' => $order]
                            );
                        }
                    }

                    $db->commit();
                    flash('success', '发布成功。');
                    redirect('/forum/post.php?id=' . $newId);
                } catch (PDOException $e) {
                    $db->rollBack();
                    $error = '发布失败：图片信息保存异常。请确认已执行数据库更新脚本。';
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 860px;">
    <!-- 顶部：页面标题 + 返回论坛 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">发新帖</h2>
        <a class="btn btn-outline-secondary btn-sm" href="/forum/index.php">返回论坛</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/forum/new.php" class="border rounded p-3 bg-white" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">

        <div class="mb-3">
            <label class="form-label">标题</label>
            <input type="text" name="title" class="form-control" value="<?= h($_POST['title'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">内容</label>
            <textarea name="content" class="form-control" rows="8"><?= h($_POST['content'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">图片（可选，最多 9 张，系统会自动压缩，jpg/png/gif/webp）</label>
            <input class="form-control" type="file" name="images[]" accept="image/*" multiple>
        </div>

        <button class="btn btn-primary" type="submit">发布</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

