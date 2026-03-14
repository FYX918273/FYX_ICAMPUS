<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireLogin();

$db = Database::getInstance();
$rows = [];

if ($db->isAvailable()) {
    $rows = $db->query(
        "SELECT f.created_at AS fav_time, p.*
         FROM favorites f
         JOIN products p ON p.id = f.product_id
         WHERE f.user_id = :uid
         ORDER BY f.created_at DESC",
        ['uid' => currentUserId()]
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- 顶部：页面标题 + 返回入口 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">我的二手收藏</h2>
        <a class="btn btn-outline-secondary btn-sm" href="/profile.php">返回个人中心</a>
    </div>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info small">你还没有收藏任何二手商品。</div>
        <a class="btn btn-primary btn-sm" href="/products/list.php">去逛二手</a>
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
                        <p class="itemListMetaText mb-0">
                            收藏时间：<?= h(substr((string)($p['fav_time'] ?? ''), 0, 16)) ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

