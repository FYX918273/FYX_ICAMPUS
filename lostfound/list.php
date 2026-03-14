<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
$db = Database::getInstance();
$rows = [];
$kw = trim($_GET['kw'] ?? '');
$type = trim($_GET['type'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 10;
$pager = paginate(0, $page, $pageSize);
$total = 0;

if ($db->isAvailable()) {
    $where = "1=1";
    $params = [];
    if ($kw !== '') {
        $where .= " AND (title LIKE :kw OR content LIKE :kw OR place LIKE :kw)";
        $params['kw'] = '%' . $kw . '%';
    }
    if (in_array($type, ['lost', 'found'], true)) {
        $where .= " AND type = :type";
        $params['type'] = $type;
    } else {
        $type = '';
    }
    // 体验优化：默认只展示“进行中”，已解决需要手动筛选查看
    if ($status === '') {
        $status = 'open';
    }
    if (in_array($status, ['open', 'closed'], true)) {
        $where .= " AND status = :status";
        $params['status'] = $status;
    } else {
        $status = '';
    }

    $totalRow = $db->queryOne("SELECT COUNT(*) AS c FROM lost_found WHERE {$where}", $params);
    $total = (int)($totalRow['c'] ?? 0);
    $pager = paginate($total, $page, $pageSize);
    $rows = $db->query(
        "SELECT * FROM lost_found WHERE {$where} ORDER BY created_at DESC LIMIT {$pager['pageSize']} OFFSET {$pager['offset']}",
        $params
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- 顶部：页面标题 + 发布入口 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">失物招领</h2>
        <?php if (isLoggedIn()): ?>
            <a class="btn btn-primary btn-sm" href="/lostfound/publish.php">发布信息</a>
        <?php else: ?>
            <a class="btn btn-outline-primary btn-sm"
               href="/login.php?redirect=<?= urlencode('/lostfound/publish.php') ?>">登录后发布</a>
        <?php endif; ?>
    </div>

    <form class="row g-2 align-items-center mb-3" method="get" action="list.php">
        <div class="col-md-5">
            <input type="text" class="form-control" name="kw" value="<?= h($kw) ?>" placeholder="搜索标题/地点/内容">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="type">
                <option value="">全部类型</option>
                <option value="lost" <?= $type === 'lost' ? 'selected' : '' ?>>寻物（丢失）</option>
                <option value="found" <?= $type === 'found' ? 'selected' : '' ?>>招领（捡到）</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="status">
                <option value="">全部状态</option>
                <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>进行中</option>
                <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>已解决</option>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-outline-dark" type="submit">筛选</button>
        </div>
    </form>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info small">暂时没有失物或招领信息。</div>
    <?php else: ?>
        <div class="small text-muted mb-2">
            共 <?= h((string)$total) ?> 条，第 <?= h((string)$pager['page']) ?>/<?= h((string)$pager['pages']) ?> 页
        </div>
        <div class="marketGoodsListGrid">
            <?php foreach ($rows as $item): ?>
                <a class="marketGoodsCard" href="detail.php?id=<?= (int)$item['id'] ?>">
                    <?php if (!empty($item['image_url'])): ?>
                        <img class="marketGoodsPic" src="<?= h($item['image_url']) ?>" alt="图片">
                    <?php else: ?>
                        <div class="marketGoodsPic"></div>
                    <?php endif; ?>
                    <div class="marketGoodsBody">
                        <div class="marketGoodsTitle"><?= h($item['title'] ?? '') ?></div>
                        <div class="marketGoodsDesc"><?= h((string)($item['content'] ?? '暂无详细说明。')) ?></div>
                        <div class="marketGoodsMeta">
                            <div class="marketGoodsPrice"><?= (($item['type'] ?? '') === 'found') ? '招领' : '寻物' ?></div>
                            <div class="marketGoodsSub"><?= h(substr((string)($item['created_at'] ?? ''), 0, 10)) ?></div>
                        </div>
                    </div>
                </a>
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

