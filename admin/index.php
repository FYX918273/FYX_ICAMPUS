<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireAdmin();

$db = Database::getInstance();
$stats = [
    'users' => 0,
    'products' => 0,
    'lostfound' => 0,
    'activities' => 0,
    'posts' => 0,
    'shop_products' => 0,
    'orders' => 0,
];

if ($db->isAvailable()) {
    $stats['users'] = (int)($db->queryOne("SELECT COUNT(*) AS c FROM users")['c'] ?? 0);
    $stats['products'] = (int)($db->queryOne("SELECT COUNT(*) AS c FROM products")['c'] ?? 0);
    $stats['lostfound'] = (int)($db->queryOne("SELECT COUNT(*) AS c FROM lost_found")['c'] ?? 0);
    $stats['activities'] = (int)($db->queryOne("SELECT COUNT(*) AS c FROM activities")['c'] ?? 0);
    $stats['posts'] = (int)($db->queryOne("SELECT COUNT(*) AS c FROM posts WHERE status = 1")['c'] ?? 0);
    $stats['shop_products'] = (int)($db->queryOne("SELECT COUNT(*) AS c FROM shop_products")['c'] ?? 0);
    $stats['orders'] = (int)($db->queryOne("SELECT COUNT(*) AS c FROM orders")['c'] ?? 0);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 980px;">
    <!-- 顶部：后台标题 + 返回前台 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">后台管理</h2>
        <a class="btn btn-outline-secondary btn-sm" href="/index.php">返回前台</a>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="border rounded bg-white p-3">
                <div class="small text-muted">用户数</div>
                <div class="fs-4 fw-bold"><?= h((string)$stats['users']) ?></div>
                <a class="btn btn-outline-dark btn-sm mt-2" href="/admin/users.php">用户管理</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded bg-white p-3">
                <div class="small text-muted">二手商品</div>
                <div class="fs-4 fw-bold"><?= h((string)$stats['products']) ?></div>
                <a class="btn btn-outline-dark btn-sm mt-2" href="/admin/products.php">二手管理</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded bg-white p-3">
                <div class="small text-muted">失物招领</div>
                <div class="fs-4 fw-bold"><?= h((string)$stats['lostfound']) ?></div>
                <a class="btn btn-outline-dark btn-sm mt-2" href="/admin/lostfound.php">失物管理</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded bg-white p-3">
                <div class="small text-muted">活动数</div>
                <div class="fs-4 fw-bold"><?= h((string)$stats['activities']) ?></div>
                <a class="btn btn-outline-dark btn-sm mt-2" href="/admin/activities.php">活动管理</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded bg-white p-3">
                <div class="small text-muted">帖子数（正常）</div>
                <div class="fs-4 fw-bold"><?= h((string)$stats['posts']) ?></div>
                <a class="btn btn-outline-dark btn-sm mt-2" href="/admin/posts.php">帖子管理</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded bg-white p-3">
                <div class="small text-muted">超市商品</div>
                <div class="fs-4 fw-bold"><?= h((string)$stats['shop_products']) ?></div>
                <a class="btn btn-outline-dark btn-sm mt-2" href="/admin/shop_products.php">商品上架</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded bg-white p-3">
                <div class="small text-muted">订单数</div>
                <div class="fs-4 fw-bold"><?= h((string)$stats['orders']) ?></div>
                <a class="btn btn-outline-dark btn-sm mt-2" href="/admin/orders.php">订单管理</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

