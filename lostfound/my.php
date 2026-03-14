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
        $lid = (int)($_POST['id'] ?? 0);
        if ($lid > 0) {
            if ($action === 'close') {
                $db->execute(
                    "UPDATE lost_found SET status = 'closed' WHERE id = :id AND user_id = :uid LIMIT 1",
                    ['id' => $lid, 'uid' => currentUserId()]
                );
                flash('success', '已标记为已解决。');
                redirect('/lostfound/my.php');
            } elseif ($action === 'delete') {
                $img = null;
                $row = $db->queryOne(
                    "SELECT image_url FROM lost_found WHERE id = :id AND user_id = :uid LIMIT 1",
                    ['id' => $lid, 'uid' => currentUserId()]
                );
                if ($row && !empty($row['image_url'])) {
                    $img = (string)$row['image_url'];
                }
                $db->execute(
                    "DELETE FROM lost_found WHERE id = :id AND user_id = :uid LIMIT 1",
                    ['id' => $lid, 'uid' => currentUserId()]
                );
                if ($img) {
                    deleteUploadedFile($img);
                }
                flash('success', '已删除。');
                redirect('/lostfound/my.php');
            }
        }
    }
}

if ($db->isAvailable()) {
    $rows = $db->query(
        "SELECT * FROM lost_found WHERE user_id = :uid ORDER BY created_at DESC",
        ['uid' => currentUserId()]
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- 顶部：页面标题 + 右侧按钮 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">我的失物招领</h2>
        <div class="rowFlexSimple gapSmall2x">
            <a class="btn btn-outline-secondary btn-sm" href="/profile.php">返回个人中心</a>
            <a class="btn btn-primary btn-sm" href="/lostfound/publish.php">发布信息</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info small">你还没有发布过失物/招领信息。</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($rows as $item): ?>
                <div class="col-md-6 mb-3">
                    <div class="homeInfoCardBox bg-white">
                        <div class="homeInfoCardHeader">
                            <h5 class="homeInfoCardTitle mb-0">
                                <a href="/lostfound/detail.php?id=<?= (int)$item['id'] ?>" class="itemListTitleLink">
                                    <?= h($item['title'] ?? '') ?>
                                </a>
                            </h5>
                            <span class="itemListMetaText">
                                <?= h(($item['type'] ?? '') === 'found' ? '招领' : '寻物') ?>
                            </span>
                        </div>
                        <p class="itemListMetaText mb-2">状态：<?= h(($item['status'] ?? '') === 'closed' ? '已解决' : '进行中') ?></p>

                        <!-- 操作按钮：标记已解决 / 删除 -->
                        <div class="rowFlexSimple gapSmall2x rowFlexWrap">
                            <?php if (($item['status'] ?? 'open') !== 'closed'): ?>
                                <form method="post" action="/lostfound/my.php" onsubmit="return confirm('确认标记为已解决吗？');">
                                    <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                    <input type="hidden" name="action" value="close">
                                    <button class="btn btn-outline-success btn-sm" type="submit">标记已解决</button>
                                </form>
                            <?php endif; ?>

                            <form method="post" action="/lostfound/my.php" onsubmit="return confirm('确定删除该记录吗？');">
                                <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-outline-danger btn-sm" type="submit">删除</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

