<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
$db = Database::getInstance();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;
$signupCount = 0;
$mySignup = null;
$error = '';

if ($id > 0 && $db->isAvailable()) {
    $row = $db->queryOne(
        "SELECT a.*, u.username, u.name AS creator_name
         FROM activities a
         JOIN users u ON u.id = a.creator_id
         WHERE a.id = :id LIMIT 1",
        ['id' => $id]
    );
    if ($row) {
        $cnt = $db->queryOne(
            "SELECT COUNT(*) AS c FROM signups WHERE activity_id = :aid AND status <> 'cancelled'",
            ['aid' => $id]
        );
        $signupCount = (int)($cnt['c'] ?? 0);
        if (isLoggedIn()) {
            $mySignup = $db->queryOne(
                "SELECT * FROM signups WHERE activity_id = :aid AND user_id = :uid LIMIT 1",
                ['aid' => $id, 'uid' => currentUserId()]
            );
        }
    }
}

if (isPost()) {
    requireLogin();
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } elseif (!$row) {
        $error = '未找到该活动。';
    } else {
        $action = $_POST['action'] ?? '';
        $status = $row['status'] ?? 'published';
        $deadline = $row['signup_deadline'] ?? null;
        $max = $row['max_participants'] ?? null;
        $now = date('Y-m-d H:i:s');

        if ($status !== 'published') {
            $error = '该活动当前不可报名。';
        } elseif ($deadline && $now > $deadline) {
            $error = '报名已截止。';
        } else {
            $cnt = $db->queryOne(
                "SELECT COUNT(*) AS c FROM signups WHERE activity_id = :aid AND status <> 'cancelled'",
                ['aid' => $id]
            );
            $signupCount = (int)($cnt['c'] ?? 0);
            if ($max !== null && $max !== '' && $signupCount >= (int)$max && $action === 'signup') {
                $error = '人数已满，无法报名。';
            } else {
                if ($action === 'signup') {
                    $db->execute(
                        "INSERT INTO signups (activity_id,user_id,status,created_at)
                         VALUES (:aid,:uid,'confirmed',NOW())
                         ON DUPLICATE KEY UPDATE status = 'confirmed'",
                        ['aid' => $id, 'uid' => currentUserId()]
                    );
                    flash('success', '报名成功。');
                    redirect('/activities/detail.php?id=' . $id);
                } elseif ($action === 'cancel') {
                    $db->execute(
                        "UPDATE signups SET status = 'cancelled' WHERE activity_id = :aid AND user_id = :uid LIMIT 1",
                        ['aid' => $id, 'uid' => currentUserId()]
                    );
                    flash('success', '已取消报名。');
                    redirect('/activities/detail.php?id=' . $id);
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 800px;">
    <?php if (!$row): ?>
        <h2 class="mb-3 mtTop3x">活动详情</h2>
        <div class="alert alert-warning small">未找到该活动。</div>
    <?php else: ?>
        <!-- 顶部：活动标题 + 右侧按钮 -->
        <div class="rowFlexBetweenTop mtTop3x mbBottom2x">
            <h2 class="mbBottom0x"><?php echo h($row['title'] ?? '活动详情'); ?></h2>
            <div class="rowFlexSimple gapSmall2x">
                <a href="/activities/list.php" class="btn btn-outline-secondary btn-sm">返回列表</a>
                <?php if (isAdmin()): ?>
                    <a href="/activities/publish.php?id=<?= (int)$id ?>" class="btn btn-outline-dark btn-sm">编辑活动</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="homeInfoCardBox bg-white mb-3">
            <p class="homeInfoCardText">
                <?php echo nl2br(h($row['description'] ?? '暂无活动介绍。')); ?>
            </p>
            <p class="itemListMetaText mb-1">
                时间：<?php echo h(substr((string)($row['start_time'] ?? ''), 0, 16)); ?>
            </p>
            <p class="itemListMetaText mb-1">
                地点：<?php echo h($row['location'] ?? '待定'); ?>
            </p>
            <p class="itemListMetaText mb-1">
                人数限制：<?php echo $row['max_participants'] === null ? '不限' : h((string)$row['max_participants']); ?>
            </p>
            <p class="itemListMetaText mb-0">
                当前状态：<?php echo h($row['status'] ?? ''); ?> · 已报名：<?= h((string)$signupCount) ?>
            </p>
            <p class="itemListMetaText mb-0 mt-2">
                报名截止：<?= h(substr((string)($row['signup_deadline'] ?? ''), 0, 16)) ?> · 发布者：<?= h(($row['creator_name'] ?? '') !== '' ? ($row['creator_name'] ?? '') : ($row['username'] ?? '')) ?>
            </p>
        </div>

        <!-- 报名区：根据登录状态显示按钮 -->
        <div class="rowFlexSimple gapSmall2x rowFlexWrap">
            <?php if (!isLoggedIn()): ?>
                <a class="btn btn-primary btn-sm" href="/login.php">登录后报名</a>
            <?php else: ?>
                <?php
                $myStatus = $mySignup['status'] ?? null;
                $canSignup = ($row['status'] ?? 'published') === 'published';
                ?>
                <?php if ($myStatus === 'confirmed' || $myStatus === 'pending'): ?>
                    <form method="post" action="/activities/detail.php?id=<?= (int)$id ?>" onsubmit="return confirm('确定取消报名吗？');">
                        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                        <input type="hidden" name="action" value="cancel">
                        <button class="btn btn-outline-danger btn-sm" type="submit">取消报名</button>
                    </form>
                    <a class="btn btn-outline-secondary btn-sm" href="/activities/my.php">查看我的报名</a>
                <?php else: ?>
                    <form method="post" action="/activities/detail.php?id=<?= (int)$id ?>">
                        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                        <input type="hidden" name="action" value="signup">
                        <button class="btn btn-primary btn-sm" type="submit">立即报名</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

