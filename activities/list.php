<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
$db = Database::getInstance();
$rows = [];
$kw = trim($_GET['kw'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 10;
$pager = paginate(0, $page, $pageSize);
$total = 0;

if ($db->isAvailable()) {
    $where = "a.status = 'published'";
    $params = [];
    if ($kw !== '') {
        $where .= " AND (a.title LIKE :kw OR a.description LIKE :kw OR a.location LIKE :kw)";
        $params['kw'] = '%' . $kw . '%';
    }
    $totalRow = $db->queryOne("SELECT COUNT(*) AS c FROM activities a WHERE {$where}", $params);
    $total = (int)($totalRow['c'] ?? 0);
    $pager = paginate($total, $page, $pageSize);
    $rows = $db->query(
        "SELECT a.*,
                (SELECT COUNT(*) FROM signups s WHERE s.activity_id = a.id AND s.status <> 'cancelled') AS signup_count
         FROM activities a
         WHERE {$where}
         ORDER BY a.start_time DESC
         LIMIT {$pager['pageSize']} OFFSET {$pager['offset']}",
        $params
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- 顶部：页面标题 + 发布入口（管理员） -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">校园活动</h2>
        <?php if (isAdmin()): ?>
            <a class="btn btn-outline-dark btn-sm" href="/activities/publish.php">发布活动</a>
        <?php endif; ?>
    </div>

    <form class="row g-2 align-items-center mb-3" method="get" action="list.php">
        <div class="col-sm-9">
            <input type="text" class="form-control" name="kw" value="<?= h($kw) ?>" placeholder="搜索标题/地点/介绍">
        </div>
        <div class="col-sm-3 d-grid">
            <button class="btn btn-outline-dark" type="submit">搜索</button>
        </div>
    </form>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info small">暂时没有正在进行的活动。</div>
    <?php else: ?>
        <div class="small text-muted mb-2">
            共 <?= h((string)$total) ?> 条，第 <?= h((string)$pager['page']) ?>/<?= h((string)$pager['pages']) ?> 页
        </div>
        <div class="feedListBox">
            <?php foreach ($rows as $a): ?>
                <div class="feedItemCard">
                    <div class="feedItemHead">
                        <h3 class="feedItemTitle">
                            <a href="detail.php?id=<?= (int)$a['id'] ?>">
                                <?= h($a['title'] ?? '') ?>
                            </a>
                        </h3>
                        <div class="feedItemTags">
                            <?php if (!empty($a['start_time'])): ?>
                                <span class="badge bg-light text-dark border"><?= h(substr((string)($a['start_time'] ?? ''), 0, 10)) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($a['location'])): ?>
                                <span class="badge bg-light text-dark border"><?= h($a['location'] ?? '') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($a['description'])): ?>
                        <p class="feedItemText"><?= h(mb_substr((string)$a['description'], 0, 120)) ?>...</p>
                    <?php endif; ?>
                    <div class="feedItemFoot">
                        <span>报名：<?= h((string)($a['signup_count'] ?? 0)) ?></span>
                        <?php if (!empty($a['signup_deadline'])): ?>
                            <span>截止：<?= h(substr((string)($a['signup_deadline'] ?? ''), 0, 16)) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($a['max_participants'])): ?>
                            <span>上限：<?= h((string)$a['max_participants']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($pager['pages'] > 1): ?>
            <nav class="mt-2" aria-label="分页">
                <ul class="pagination pagination-sm">
                    <li class="page-item <?= $pager['page'] <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= h(buildUrlWithQuery(['page' => max(1, $pager['page'] - 1)])) ?>">上一页</a>
                    </li>
                    <?php
                    $start = max(1, $pager['page'] - 2);
                    $end = min($pager['pages'], $pager['page'] + 2);
                    ?>
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= $i === $pager['page'] ? 'active' : '' ?>">
                            <a class="page-link" href="<?= h(buildUrlWithQuery(['page' => $i])) ?>"><?= h((string)$i) ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $pager['page'] >= $pager['pages'] ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= h(buildUrlWithQuery(['page' => min($pager['pages'], $pager['page'] + 1)])) ?>">下一页</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

