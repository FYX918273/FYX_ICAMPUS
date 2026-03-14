<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireLogin();

$db = Database::getInstance();
$rows = [];

if ($db->isAvailable()) {
    $rows = $db->query(
        "SELECT p.*
         FROM posts p
         WHERE p.user_id = :uid AND p.status = 1
         ORDER BY p.created_at DESC",
        ['uid' => currentUserId()]
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- 顶部：页面标题 + 右侧按钮 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">我的论坛帖子</h2>
        <div class="rowFlexSimple gapSmall2x">
            <a class="btn btn-outline-secondary btn-sm" href="/profile.php">返回个人中心</a>
            <a class="btn btn-primary btn-sm" href="/forum/new.php">发新帖</a>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info small">你还没有发布过帖子。</div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($rows as $p): ?>
                <a href="/forum/post.php?id=<?= (int)$p['id'] ?>" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="fw-bold"><?= h($p['title'] ?? '') ?></div>
                        </div>
                        <div class="small text-muted text-end">
                            <div><?= h(substr((string)($p['created_at'] ?? ''), 0, 16)) ?></div>
                            <div>浏览 <?= h((string)($p['views'] ?? 0)) ?> · 点赞 <?= h((string)($p['likes'] ?? 0)) ?></div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

