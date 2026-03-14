<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

startSession();
requireLogin();

$db = Database::getInstance();
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
            if ($action === 'off') {
                $db->execute(
                    "UPDATE products SET status = 'off' WHERE id = :id AND user_id = :uid LIMIT 1",
                    ['id' => $pid, 'uid' => currentUserId()]
                );
                flash('success', '已下架。');
                redirect('/products/my.php');
            } elseif ($action === 'on') {
                $db->execute(
                    "UPDATE products SET status = 'on' WHERE id = :id AND user_id = :uid LIMIT 1",
                    ['id' => $pid, 'uid' => currentUserId()]
                );
                flash('success', '已上架。');
                redirect('/products/my.php');
            } elseif ($action === 'delete') {
                $img = null;
                $row = $db->queryOne(
                    "SELECT image_url FROM products WHERE id = :id AND user_id = :uid LIMIT 1",
                    ['id' => $pid, 'uid' => currentUserId()]
                );
                if ($row && !empty($row['image_url'])) {
                    $img = (string)$row['image_url'];
                }
                $db->execute(
                    "DELETE FROM products WHERE id = :id AND user_id = :uid LIMIT 1",
                    ['id' => $pid, 'uid' => currentUserId()]
                );
                if ($img) {
                    deleteUploadedFile($img);
                }
                flash('success', '已删除。');
                redirect('/products/my.php');
            }
        }
    }
}

if ($db->isAvailable()) {
    $rows = $db->query(
        "SELECT * FROM products WHERE user_id = :uid ORDER BY created_at DESC",
        ['uid' => currentUserId()]
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- 顶部：页面标题 + 右侧按钮 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">我的二手发布</h2>
        <div class="rowFlexSimple gapSmall2x">
            <a class="btn btn-outline-secondary btn-sm" href="/profile.php">返回个人中心</a>
            <a class="btn btn-primary btn-sm" href="/products/publish.php">发布二手</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info small">你还没有发布过二手商品。</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($rows as $p): ?>
                <div class="col-md-6 mb-3">
                    <div class="homeInfoCardBox bg-white">
                        <div class="homeInfoCardHeader">
                            <h5 class="homeInfoCardTitle mb-0">
                                <a href="/products/detail.php?id=<?= (int)$p['id'] ?>" class="itemListTitleLink">
                                    <?= h($p['title'] ?? '') ?>
                                </a>
                            </h5>
                            <span class="itemListMetaText">￥<?= h(number_format((float)($p['price'] ?? 0), 2)) ?></span>
                        </div>
                        <p class="itemListMetaText mb-2">状态：<?= h($p['status'] ?? '') ?></p>
                        <!-- 操作按钮：上架/下架 -->
                        <form method="post" action="/products/my.php" class="rowFlexSimple gapSmall2x rowFlexWrap">
                            <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <?php if (($p['status'] ?? 'on') === 'on'): ?>
                                <input type="hidden" name="action" value="off">
                                <button class="btn btn-outline-dark btn-sm" type="submit">下架</button>
                            <?php else: ?>
                                <input type="hidden" name="action" value="on">
                                <button class="btn btn-outline-primary btn-sm" type="submit">上架</button>
                            <?php endif; ?>
                        </form>

                        <form method="post" action="/products/my.php" class="mt-2" onsubmit="return confirm('确定删除该商品吗？');">
                            <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button class="btn btn-outline-danger btn-sm" type="submit">删除</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

