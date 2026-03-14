<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
$db = Database::getInstance();

$posts = [];
$kw = trim($_GET['kw'] ?? '');
$sort = $_GET['sort'] ?? 'latest'; // latest | hot

if ($db->isAvailable()) {
    $where = "p.status = 1";
    $params = [];
    if ($kw !== '') {
        $where .= " AND (p.title LIKE :kw OR p.content LIKE :kw)";
        $params['kw'] = '%' . $kw . '%';
    }

    $orderBy = "p.is_top DESC, p.created_at DESC";
    if ($sort === 'hot') {
        $orderBy = "p.is_top DESC, p.likes DESC, p.views DESC, p.created_at DESC";
    } else {
        $sort = 'latest';
    }

    try {
        $posts = $db->query(
            "SELECT p.*, u.name AS user_name, u.username,
                    GROUP_CONCAT(pi.image_url ORDER BY pi.sort_order SEPARATOR '\n') AS images
             FROM posts p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN post_images pi ON pi.post_id = p.id
             WHERE {$where}
             GROUP BY p.id
             ORDER BY {$orderBy}
             LIMIT 20",
            $params
        );
    } catch (PDOException $e) {
        // 兼容未升级数据库（没有 post_images 表）的情况
        $posts = $db->query(
            "SELECT p.*, u.name AS user_name, u.username
             FROM posts p
             JOIN users u ON u.id = p.user_id
             WHERE {$where}
             ORDER BY {$orderBy}
             LIMIT 20",
            $params
        );
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- 顶部：页面标题 + 右侧操作入口 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">校园广场</h2>
        <div class="rowFlexSimple gapSmall2x">
            <?php if (isLoggedIn()): ?>
                <a class="btn btn-outline-secondary btn-sm" href="/forum/my.php">我的帖子</a>
                <a class="btn btn-primary btn-sm" href="/forum/new.php">发帖</a>
            <?php else: ?>
                <a class="btn btn-outline-primary btn-sm"
                   href="/login.php?redirect=<?= urlencode('/forum/new.php') ?>">登录后发帖</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link<?= $sort === 'latest' ? ' active' : '' ?>"
                   href="/forum/index.php?sort=latest&kw=<?= urlencode($kw) ?>">
                    最新
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?= $sort === 'hot' ? ' active' : '' ?>"
                   href="/forum/index.php?sort=hot&kw=<?= urlencode($kw) ?>">
                    热门
                </a>
            </li>
        </ul>
        <div class="small text-muted">公开信息流</div>
    </div>

    <form class="row g-2 align-items-center mb-3" method="get" action="/forum/index.php">
        <input type="hidden" name="sort" value="<?= h($sort) ?>">
        <div class="col-sm-9">
            <input type="text" class="form-control" name="kw" value="<?= h($kw) ?>" placeholder="搜索帖子标题/内容">
        </div>
        <div class="col-sm-3 d-grid">
            <button class="btn btn-outline-dark" type="submit">搜索</button>
        </div>
    </form>

    <?php if (empty($posts)): ?>
        <div class="alert alert-info small">暂时没有帖子。</div>
    <?php else: ?>
        <div class="feedListBox">
            <?php foreach ($posts as $p): ?>
                <div class="feedItemCard">
                    <div class="feedItemHead">
                        <h3 class="feedItemTitle">
                            <a href="/forum/post.php?id=<?= (int)$p['id'] ?>">
                                <?= h($p['title'] ?? '') ?>
                            </a>
                        </h3>
                        <div class="feedItemTags">
                            <?php if (!empty($p['is_top'])): ?>
                                <span class="badge bg-danger">置顶</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="feedItemText"><?= h(mb_substr((string)($p['content'] ?? ''), 0, 160)) ?>...</p>
                    <?php
                    $imgsRaw = (string)($p['images'] ?? '');
                    $imgs = $imgsRaw !== '' ? array_values(array_filter(explode("\n", $imgsRaw))) : [];
                    if (empty($imgs) && !empty($p['image_url'])) {
                        $imgs = [(string)$p['image_url']];
                    }
                    ?>
                    <?php $n = min(9, count($imgs)); ?>
                    <?php if ($n === 1): ?>
                        <div class="pengyouquanBlock pengyouquanBlockSingle mt-2">
                            <a href="<?= h($imgs[0]) ?>" class="pengyouquanImgLink" target="_blank" rel="noopener">
                                <img src="<?= h($imgs[0]) ?>" alt="配图" class="pengyouquanImgOne rounded" loading="lazy">
                            </a>
                        </div>
                    <?php elseif ($n > 1): ?>
                        <?php $colsClass = ($n === 2 || $n === 4) ? 'cols-2' : 'cols-3'; ?>
                        <div class="pengyouquanBlock pengyouquanImgGrid mt-2 <?= h($colsClass) ?>">
                            <?php foreach (array_slice($imgs, 0, 9) as $src): ?>
                                <a href="<?= h($src) ?>" class="pengyouquanImgItem rounded" target="_blank" rel="noopener">
                                    <img src="<?= h($src) ?>" alt="配图" loading="lazy">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="feedItemFoot">
                        <span><?= h(($p['user_name'] ?? '') !== '' ? ($p['user_name'] ?? '') : ($p['username'] ?? '')) ?></span>
                        <span><?= h(substr((string)($p['created_at'] ?? ''), 0, 16)); ?></span>
                        <span>浏览 <?= h((string)($p['views'] ?? 0)) ?></span>
                        <span>点赞 <?= h((string)($p['likes'] ?? 0)) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

