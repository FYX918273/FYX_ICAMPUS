<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
$db = Database::getInstance();
$rows = [];
$kw = trim($_GET['kw'] ?? '');
$cat = trim($_GET['category'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = 12;
$pager = paginate(0, $page, $pageSize);
$total = 0;
$error = '';

if ($db->isAvailable()) {
    $where = "status = 'on'";
    $params = [];
    if ($kw !== '') {
        $where .= " AND (name LIKE :kw OR description LIKE :kw)";
        $params['kw'] = '%' . $kw . '%';
    }
    if ($cat !== '') {
        $where .= " AND category = :cat";
        $params['cat'] = $cat;
    }
    $totalRow = $db->queryOne("SELECT COUNT(*) AS c FROM shop_products WHERE {$where}", $params);
    $total = (int)($totalRow['c'] ?? 0);
    $pager = paginate($total, $page, $pageSize);
    $rows = $db->query(
        "SELECT * FROM shop_products WHERE {$where} ORDER BY created_at DESC LIMIT {$pager['pageSize']} OFFSET {$pager['offset']}",
        $params
    );
}

if (isPost()) {
    requireLogin();
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } else {
        $pid = (int)($_POST['id'] ?? 0);
        if ($pid > 0) {
            $sp = $db->queryOne("SELECT * FROM shop_products WHERE id = :id AND status = 'on' LIMIT 1", ['id' => $pid]);
            if (!$sp) {
                $error = '商品不存在或已下架。';
            } elseif ((int)($sp['stock'] ?? 0) <= 0) {
                $error = '库存不足。';
            } else {
                $db->execute(
                    "INSERT INTO cart (user_id,product_id,quantity,created_at,updated_at)
                     VALUES (:uid,:pid,1,NOW(),NOW())
                     ON DUPLICATE KEY UPDATE quantity = quantity + 1, updated_at = NOW()",
                    ['uid' => currentUserId(), 'pid' => $pid]
                );
                flash('success', '已加入购物车。');
                redirect('/shop/cart.php');
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!-- 顶部：页面标题 + 购物车入口 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">校园超市</h2>
        <?php if (isLoggedIn()): ?>
            <a class="btn btn-outline-dark btn-sm" href="/shop/cart.php">购物车</a>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <form class="row g-2 align-items-center mb-3" method="get" action="index.php">
        <div class="col-md-7">
            <input type="text" class="form-control" name="kw" value="<?= h($kw) ?>" placeholder="搜索商品名称/描述">
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control" name="category" value="<?= h($cat) ?>" placeholder="分类（可选）">
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-outline-dark" type="submit">搜索</button>
        </div>
    </form>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info small">暂时没有上架的商品。</div>
    <?php else: ?>
        <div class="small text-muted mb-2">
            共 <?= h((string)$total) ?> 条，第 <?= h((string)$pager['page']) ?>/<?= h((string)$pager['pages']) ?> 页
        </div>
        <div class="marketGoodsListGrid">
            <?php foreach ($rows as $sp): ?>
                <div>
                    <div class="marketGoodsCard">
                        <?php if (!empty($sp['image_url'])): ?>
                            <img class="marketGoodsPic" src="<?= h($sp['image_url']) ?>" alt="商品图片">
                        <?php else: ?>
                            <div class="marketGoodsPic"></div>
                        <?php endif; ?>
                        <div class="marketGoodsBody">
                            <div class="marketGoodsTitle"><?= h($sp['name'] ?? '') ?></div>
                            <div class="marketGoodsDesc"><?= h((string)($sp['description'] ?? '')) ?></div>
                            <div class="marketGoodsMeta mb-2">
                                <div class="marketGoodsPrice">￥<?= h(number_format((float)($sp['price'] ?? 0), 2)) ?></div>
                                <div class="marketGoodsSub">
                                    库存 <?= h((string)($sp['stock'] ?? 0)) ?>
                                    <?php if (!empty($sp['category'])): ?> · <?= h($sp['category']) ?><?php endif; ?>
                                </div>
                            </div>

                            <?php if (!isLoggedIn()): ?>
                                <a class="btn btn-outline-primary btn-sm w-100" href="/login.php">登录后加购</a>
                            <?php else: ?>
                                <form method="post" action="/shop/index.php<?= h(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ? ('?' . (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY))) : '') ?>">
                                    <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$sp['id'] ?>">
                                    <button class="btn btn-primary btn-sm w-100" type="submit" <?= ((int)($sp['stock'] ?? 0) <= 0) ? 'disabled' : '' ?>>
                                        <?= ((int)($sp['stock'] ?? 0) <= 0) ? '库存不足' : '加入购物车' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
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

