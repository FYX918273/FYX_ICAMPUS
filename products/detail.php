<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
$db = Database::getInstance();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;
$isFav = false;
$error = '';

if ($id > 0 && $db->isAvailable()) {
    $row = $db->queryOne(
        "SELECT p.*, u.username, u.name AS user_name 
         FROM products p 
         JOIN users u ON u.id = p.user_id 
         WHERE p.id = :id LIMIT 1",
        ['id' => $id]
    );
    if ($row && isLoggedIn()) {
        $fav = $db->queryOne(
            "SELECT id FROM favorites WHERE user_id = :uid AND product_id = :pid LIMIT 1",
            ['uid' => currentUserId(), 'pid' => $id]
        );
        $isFav = (bool)$fav;
    }
}

if (isPost()) {
    requireLogin();
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } elseif (!$row) {
        $error = '未找到该商品。';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'fav') {
            $db->execute(
                "INSERT IGNORE INTO favorites (user_id, product_id, created_at) VALUES (:uid,:pid,NOW())",
                ['uid' => currentUserId(), 'pid' => $id]
            );
            flash('success', '已收藏。');
            redirect('/products/detail.php?id=' . $id);
        } elseif ($action === 'unfav') {
            $db->execute(
                "DELETE FROM favorites WHERE user_id = :uid AND product_id = :pid LIMIT 1",
                ['uid' => currentUserId(), 'pid' => $id]
            );
            flash('success', '已取消收藏。');
            redirect('/products/detail.php?id=' . $id);
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 800px;">
    <?php if (!$row): ?>
        <h2 class="mb-3 mtTop3x">二手商品详情</h2>
        <div class="alert alert-warning small">未找到该商品。</div>
    <?php else: ?>
        <!-- 顶部：商品标题 + 右侧操作按钮 -->
        <div class="rowFlexBetweenTop mtTop3x mbBottom2x">
            <h2 class="mbBottom0x"><?php echo h($row['title'] ?? '二手商品详情'); ?></h2>
            <div class="rowFlexSimple gapSmall2x">
                <a href="/products/list.php" class="btn btn-outline-secondary btn-sm">返回列表</a>
                <?php if (isLoggedIn()): ?>
                    <form method="post" action="detail.php?id=<?= (int)$id ?>">
                        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                        <?php if ($isFav): ?>
                            <input type="hidden" name="action" value="unfav">
                            <button class="btn btn-outline-danger btn-sm" type="submit">取消收藏</button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="fav">
                            <button class="btn btn-outline-primary btn-sm" type="submit">收藏</button>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <a class="btn btn-outline-primary btn-sm" href="/login.php">登录后收藏</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="homeInfoCardBox bg-white mb-3">
            <?php if (!empty($row['image_url'])): ?>
                <img src="<?= h($row['image_url']) ?>" alt="商品图片" class="img-fluid rounded mb-3">
            <?php endif; ?>
            <p class="homeInfoCardText">
                <?php echo nl2br(h($row['description'] ?? '暂无描述。')); ?>
            </p>
            <p class="itemListMetaText mb-1">
                价格：￥<?php echo isset($row['price']) ? number_format((float)$row['price'], 2) : '0.00'; ?>
            </p>
            <p class="itemListMetaText mb-1">
                发布时间：<?php echo h(substr((string)($row['created_at'] ?? ''), 0, 16)); ?>
            </p>
            <p class="itemListMetaText mb-0">
                状态：<?php echo h($row['status'] ?? ''); ?>
            </p>
            <p class="itemListMetaText mb-0 mt-2">
                发布者：<?= h(($row['user_name'] ?? '') !== '' ? ($row['user_name'] ?? '') : ($row['username'] ?? '')) ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

