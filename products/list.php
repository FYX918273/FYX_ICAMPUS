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
    $where = "status = 'on'";
    $params = [];
    if ($kw !== '') {
        $where .= " AND (title LIKE :kw OR description LIKE :kw)";
        $params['kw'] = '%' . $kw . '%';
    }
    $totalRow = $db->queryOne("SELECT COUNT(*) AS c FROM products WHERE {$where}", $params);
    $total = (int)($totalRow['c'] ?? 0);
    $pager = paginate($total, $page, $pageSize);
    $rows = $db->query(
        "SELECT * FROM products WHERE {$where} ORDER BY created_at DESC LIMIT {$pager['pageSize']} OFFSET {$pager['offset']}",
        $params
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- 顶部：页面标题 + 发布入口 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">二手交易</h2>
        <?php if (isLoggedIn()): ?>
            <a class="btn btn-primary btn-sm" href="/products/publish.php">发布二手</a>
        <?php else: ?>
            <a class="btn btn-outline-primary btn-sm"
               href="/login.php?redirect=<?= urlencode('/products/publish.php') ?>">登录后发布</a>
        <?php endif; ?>
    </div>

    <form class="row g-2 align-items-center mb-3" method="get" action="list.php">
        <div class="col-sm-9">
            <input type="text" class="form-control" name="kw" value="<?= h($kw) ?>" placeholder="搜索标题/描述">
        </div>
        <div class="col-sm-3 d-grid">
            <button class="btn btn-outline-dark" type="submit">搜索</button>
        </div>
    </form>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info small">
            <?= $kw !== '' ? '没有找到匹配的二手商品。' : '暂时没有二手商品。' ?>
        </div>
    <?php else: ?>
        <div class="small text-muted mb-2">
            共 <?= h((string)$total) ?> 条，第 <?= h((string)$pager['page']) ?>/<?= h((string)$pager['pages']) ?> 页
        </div>
        <div class="marketGoodsListGrid">
            <?php foreach ($rows as $p): ?>
                <a class="marketGoodsCard" href="detail.php?id=<?= (int)$p['id'] ?>">
                    <?php if (!empty($p['image_url'])): ?>
                        <img class="marketGoodsPic" src="<?= h($p['image_url']) ?>" alt="商品图片">
                    <?php else: ?>
                        <div class="marketGoodsPic"></div>
                    <?php endif; ?>
                    <div class="marketGoodsBody">
                        <div class="marketGoodsTitle"><?= h($p['title'] ?? '') ?></div>
                        <div class="marketGoodsDesc"><?= h((string)($p['description'] ?? '暂无描述。')) ?></div>
                        <div class="marketGoodsMeta">
                            <div class="marketGoodsPrice">￥<?= h(number_format((float)($p['price'] ?? 0), 2)) ?></div>
                            <div class="marketGoodsSub"><?= h(substr((string)($p['created_at'] ?? ''), 0, 10)) ?></div>
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

