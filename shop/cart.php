<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

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
        $pid = (int)($_POST['product_id'] ?? 0);
        if ($pid > 0) {
            if ($action === 'inc') {
                $db->execute(
                    "UPDATE cart SET quantity = quantity + 1, updated_at = NOW()
                     WHERE user_id = :uid AND product_id = :pid LIMIT 1",
                    ['uid' => currentUserId(), 'pid' => $pid]
                );
                redirect('/shop/cart.php');
            } elseif ($action === 'dec') {
                $db->execute(
                    "UPDATE cart SET quantity = GREATEST(1, quantity - 1), updated_at = NOW()
                     WHERE user_id = :uid AND product_id = :pid LIMIT 1",
                    ['uid' => currentUserId(), 'pid' => $pid]
                );
                redirect('/shop/cart.php');
            } elseif ($action === 'del') {
                $db->execute(
                    "DELETE FROM cart WHERE user_id = :uid AND product_id = :pid LIMIT 1",
                    ['uid' => currentUserId(), 'pid' => $pid]
                );
                flash('success', '已移出购物车。');
                redirect('/shop/cart.php');
            }
        }
    }
}

if ($db->isAvailable()) {
    $rows = $db->query(
        "SELECT c.product_id, c.quantity, sp.name, sp.price, sp.stock, sp.image_url
         FROM cart c
         JOIN shop_products sp ON sp.id = c.product_id
         WHERE c.user_id = :uid
         ORDER BY c.updated_at DESC",
        ['uid' => currentUserId()]
    );
}

$total = 0.0;
foreach ($rows as $r) {
    $total += (float)($r['price'] ?? 0) * (int)($r['quantity'] ?? 0);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 980px;">
    <!-- 顶部：页面标题 + 右侧按钮 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">购物车</h2>
        <div class="rowFlexSimple gapSmall2x">
            <a class="btn btn-outline-secondary btn-sm" href="/shop/index.php">继续购物</a>
            <a class="btn btn-outline-dark btn-sm" href="/shop/orders.php">我的订单</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info small">购物车还是空的。</div>
        <a class="btn btn-primary btn-sm" href="/shop/index.php">去逛超市</a>
    <?php else: ?>
        <div class="table-responsive bg-white border rounded">
            <table class="table mb-0 align-middle">
                <thead>
                <tr>
                    <th>商品</th>
                    <th style="width:120px;">单价</th>
                    <th style="width:180px;">数量</th>
                    <th style="width:140px;">小计</th>
                    <th style="width:120px;">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php
                    $qty = (int)($r['quantity'] ?? 1);
                    $price = (float)($r['price'] ?? 0);
                    $sub = $qty * $price;
                    ?>
                    <tr>
                        <td>
                            <!-- 商品信息：图片 + 名称 + 库存 -->
                            <div class="rowFlexSimple gapSmall2x rowFlexMiddle">
                                <?php if (!empty($r['image_url'])): ?>
                                    <img src="<?= h($r['image_url']) ?>" alt="图片" style="width:44px;height:44px;object-fit:cover;border-radius:8px;">
                                <?php endif; ?>
                                <div>
                                    <div class="fw-bold"><?= h($r['name'] ?? '') ?></div>
                                    <div class="small text-muted">库存：<?= h((string)($r['stock'] ?? 0)) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>￥<?= h(number_format($price, 2)) ?></td>
                        <td>
                            <!-- 数量调整：减一 / 当前数量 / 加一 -->
                            <div class="rowFlexSimple gapSmall2x">
                                <form method="post" action="/shop/cart.php">
                                    <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="product_id" value="<?= (int)$r['product_id'] ?>">
                                    <input type="hidden" name="action" value="dec">
                                    <button class="btn btn-outline-dark btn-sm" type="submit">-</button>
                                </form>
                                <div class="px-2 py-1 border rounded small bg-light"><?= h((string)$qty) ?></div>
                                <form method="post" action="/shop/cart.php">
                                    <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="product_id" value="<?= (int)$r['product_id'] ?>">
                                    <input type="hidden" name="action" value="inc">
                                    <button class="btn btn-outline-dark btn-sm" type="submit">+</button>
                                </form>
                            </div>
                        </td>
                        <td>￥<?= h(number_format($sub, 2)) ?></td>
                        <td>
                            <form method="post" action="/shop/cart.php" onsubmit="return confirm('确定移除该商品吗？');">
                                <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                <input type="hidden" name="product_id" value="<?= (int)$r['product_id'] ?>">
                                <input type="hidden" name="action" value="del">
                                <button class="btn btn-outline-danger btn-sm" type="submit">移除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="fw-bold">合计：￥<?= h(number_format($total, 2)) ?></div>
            <a class="btn btn-primary" href="/shop/checkout.php">去结算</a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

