<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireLogin();

$db = Database::getInstance();
$rows = [];

if ($db->isAvailable()) {
    $rows = $db->query(
        "SELECT s.status AS signup_status, s.created_at AS signup_time, a.*
         FROM signups s
         JOIN activities a ON a.id = s.activity_id
         WHERE s.user_id = :uid AND s.status <> 'cancelled'
         ORDER BY s.created_at DESC",
        ['uid' => currentUserId()]
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- 顶部：页面标题 + 返回入口 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">我的活动报名</h2>
        <a class="btn btn-outline-secondary btn-sm" href="/profile.php">返回个人中心</a>
    </div>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info small">你还没有报名任何活动。</div>
        <a class="btn btn-primary btn-sm" href="/activities/list.php">去看看活动</a>
    <?php else: ?>
        <div class="row">
            <?php foreach ($rows as $a): ?>
                <div class="col-md-6 mb-3">
                    <div class="homeInfoCardBox bg-white">
                        <div class="homeInfoCardHeader">
                            <h5 class="homeInfoCardTitle mb-0">
                                <a href="/activities/detail.php?id=<?= (int)$a['id'] ?>" class="itemListTitleLink">
                                    <?= h($a['title'] ?? '') ?>
                                </a>
                            </h5>
                            <span class="itemListMetaText"><?= h(substr((string)($a['start_time'] ?? ''), 0, 10)) ?></span>
                        </div>
                        <p class="homeInfoCardText mb-1">地点：<?= h($a['location'] ?? '待定') ?></p>
                        <p class="itemListMetaText mb-0">报名时间：<?= h(substr((string)($a['signup_time'] ?? ''), 0, 16)) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

