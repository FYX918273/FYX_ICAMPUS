<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
$db = Database::getInstance();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;
$error = '';

if ($id > 0 && $db->isAvailable()) {
    $row = $db->queryOne(
        "SELECT lf.*, u.username, u.name AS user_name 
         FROM lost_found lf 
         JOIN users u ON u.id = lf.user_id
         WHERE lf.id = :id LIMIT 1",
        ['id' => $id]
    );
}

if (isPost()) {
    requireLogin();
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } elseif (!$row) {
        $error = '未找到该记录。';
    } else {
        $action = $_POST['action'] ?? '';
        $isOwner = ((int)($row['user_id'] ?? 0) === currentUserId());
        if ($action === 'close' && ($isOwner || isAdmin())) {
            $db->execute(
                "UPDATE lost_found SET status = 'closed' WHERE id = :id LIMIT 1",
                ['id' => $id]
            );
            flash('success', '已标记为已解决。');
            redirect('/lostfound/detail.php?id=' . $id);
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 800px;">
    <?php if (!$row): ?>
        <h2 class="mb-3 mtTop3x">失物招领详情</h2>
        <div class="alert alert-warning small">未找到该记录。</div>
    <?php else: ?>
        <!-- 顶部：标题 + 右侧操作按钮 -->
        <div class="rowFlexBetweenTop mtTop3x mbBottom2x">
            <h2 class="mbBottom0x"><?php echo h($row['title'] ?? '失物招领详情'); ?></h2>
            <div class="rowFlexSimple gapSmall2x">
                <a href="/lostfound/list.php" class="btn btn-outline-secondary btn-sm">返回列表</a>
                <?php
                $isOwner = isLoggedIn() && ((int)($row['user_id'] ?? 0) === currentUserId());
                $canClose = ($row['status'] ?? 'open') !== 'closed' && ($isOwner || isAdmin());
                ?>
                <?php if ($canClose): ?>
                    <form method="post" action="detail.php?id=<?= (int)$id ?>">
                        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                        <input type="hidden" name="action" value="close">
                        <button class="btn btn-outline-success btn-sm" type="submit" onclick="return confirm('确认标记为已解决吗？');">
                            标记已解决
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="homeInfoCardBox bg-white mb-3">
            <?php if (!empty($row['image_url'])): ?>
                <img src="<?= h($row['image_url']) ?>" alt="图片" class="img-fluid rounded mb-3">
            <?php endif; ?>
            <p class="homeInfoCardText">
                <?php echo nl2br(h($row['content'] ?? '暂无详细说明。')); ?>
            </p>
            <p class="itemListMetaText mb-1">
                类型：<?php echo h(($row['type'] ?? '') === 'found' ? '招领（捡到）' : '寻物（丢失）'); ?>
            </p>
            <p class="itemListMetaText mb-1">
                状态：<?php echo h(($row['status'] ?? '') === 'closed' ? '已解决' : '进行中'); ?>
            </p>
            <p class="itemListMetaText mb-1">
                时间：<?php echo h(substr((string)($row['happen_time'] ?? $row['created_at'] ?? ''), 0, 16)); ?>
            </p>
            <p class="itemListMetaText mb-0">
                联系方式：<?php echo h($row['contact'] ?? ''); ?>
            </p>
            <p class="itemListMetaText mb-0 mt-2">
                地点：<?= h($row['place'] ?? '未填写') ?> · 发布者：<?= h(($row['user_name'] ?? '') !== '' ? ($row['user_name'] ?? '') : ($row['username'] ?? '')) ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

