<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireLogin();

$db = Database::getInstance();
$rows = [];
$error = '';
$total = 0.0;
$user = null;
$userPhone = '';

if ($db->isAvailable()) {
    $user = currentUser();
    $userPhone = trim((string)($user['phone'] ?? ''));
    $rows = $db->query(
        "SELECT c.product_id, c.quantity, sp.name, sp.price, sp.stock
         FROM cart c
         JOIN shop_products sp ON sp.id = c.product_id
         WHERE c.user_id = :uid
         ORDER BY c.updated_at DESC",
        ['uid' => currentUserId()]
    );
    foreach ($rows as $r) {
        $total += (float)($r['price'] ?? 0) * (int)($r['quantity'] ?? 0);
    }
}

function genOrderNo(): string
{
    return date('YmdHis') . bin2hex(random_bytes(4));
}

if (isPost()) {
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } elseif (empty($rows)) {
        $error = '购物车为空，无法结算。';
    } else {
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? $userPhone);
        $remark = trim($_POST['remark'] ?? '');

        if ($address === '') {
            $error = '收货地址不能为空。';
        } elseif ($phone === '') {
            $error = '手机号不能为空。';
        } elseif (!preg_match('/^1\d{10}$/', $phone)) {
            $error = '手机号格式不正确（应为 11 位手机号）。';
        } else {
            // 如果用户档案没有手机号，则在下单时补齐，方便后台查看注册信息
            if ($userPhone === '' || $userPhone !== $phone) {
                $db->execute(
                    "UPDATE users SET phone = :p WHERE id = :id LIMIT 1",
                    ['p' => $phone, 'id' => currentUserId()]
                );
                refreshCurrentUserCache();
                $userPhone = $phone;
            }

            $db->beginTransaction();
            try {
                // 逐个锁定库存并校验
                foreach ($rows as $r) {
                    $pid = (int)$r['product_id'];
                    $need = (int)$r['quantity'];
                    $sp = $db->queryOne("SELECT stock, status, name, price FROM shop_products WHERE id = :id FOR UPDATE", ['id' => $pid]);
                    if (!$sp || ($sp['status'] ?? 'off') !== 'on') {
                        throw new Exception('存在已下架商品，请返回购物车调整。');
                    }
                    $stock = (int)($sp['stock'] ?? 0);
                    if ($stock < $need) {
                        throw new Exception('库存不足：' . ($sp['name'] ?? '商品'));
                    }
                }

                $orderNo = genOrderNo();
                $db->execute(
                    "INSERT INTO orders (order_no,user_id,total_amount,status,address,remark,created_at)
                     VALUES (:no,:uid,:total,'pending',:addr,:rm,NOW())",
                    [
                        'no' => $orderNo,
                        'uid' => currentUserId(),
                        'total' => $total,
                        'addr' => $address,
                        'rm' => $remark !== '' ? $remark : null,
                    ]
                );
                $orderId = $db->lastInsertId();

                foreach ($rows as $r) {
                    $pid = (int)$r['product_id'];
                    $qty = (int)$r['quantity'];
                    $sp = $db->queryOne("SELECT name, price FROM shop_products WHERE id = :id FOR UPDATE", ['id' => $pid]);
                    $name = (string)($sp['name'] ?? $r['name'] ?? '');
                    $price = (float)($sp['price'] ?? $r['price'] ?? 0);
                    $subtotal = $price * $qty;

                    $db->execute(
                        "UPDATE shop_products SET stock = stock - :q WHERE id = :id LIMIT 1",
                        ['q' => $qty, 'id' => $pid]
                    );

                    $db->execute(
                        "INSERT INTO order_items (order_id,product_id,product_name,price,quantity,subtotal)
                         VALUES (:oid,:pid,:name,:price,:qty,:sub)",
                        [
                            'oid' => $orderId,
                            'pid' => $pid,
                            'name' => $name,
                            'price' => $price,
                            'qty' => $qty,
                            'sub' => $subtotal,
                        ]
                    );
                }

                $db->execute("DELETE FROM cart WHERE user_id = :uid", ['uid' => currentUserId()]);

                $db->commit();
                // 这里开始走“支付宝打赏彩蛋”流程：先下单，再跳到支付彩蛋页
                flash('success', '下单成功，接下来进入支付宝支付彩蛋。');
                redirect('/shop/pay.php?order_no=' . urlencode($orderNo));
            } catch (Throwable $e) {
                $db->rollBack();
                $error = $e->getMessage() ?: '下单失败，请稍后再试。';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 980px;">
    <!-- 顶部：页面标题 + 返回购物车 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">确认订单</h2>
        <a class="btn btn-outline-secondary btn-sm" href="/shop/cart.php">返回购物车</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info small">购物车为空。</div>
        <a class="btn btn-primary btn-sm" href="/shop/index.php">去逛超市</a>
    <?php else: ?>
        <div class="border rounded bg-white p-3 mb-3">
            <h6 class="mb-2">商品清单</h6>
            <ul class="list-group mb-2">
                <?php foreach ($rows as $r): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= h($r['name'] ?? '') ?> × <?= h((string)($r['quantity'] ?? 0)) ?></span>
                        <span>￥<?= h(number_format((float)($r['price'] ?? 0) * (int)($r['quantity'] ?? 0), 2)) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="fw-bold text-end">合计：￥<?= h(number_format($total, 2)) ?></div>
        </div>

        <form method="post" action="/shop/checkout.php" class="border rounded bg-white p-3">
            <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
            <div class="mb-3">
                <label class="form-label">手机号</label>
                <input type="text" name="phone" class="form-control"
                       value="<?= h($_POST['phone'] ?? $userPhone) ?>"
                       placeholder="用于联系与订单查询（11 位手机号）">
                <?php if ($userPhone === ''): ?>
                    <div class="form-text">你未填写过手机号，本次下单会自动保存到个人资料。</div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">收货地址</label>
                <input type="text" name="address" class="form-control" value="<?= h($_POST['address'] ?? '') ?>" placeholder="如：宿舍楼-房间号">
            </div>
            <div class="mb-3">
                <label class="form-label">备注（可选）</label>
                <input type="text" name="remark" class="form-control" value="<?= h($_POST['remark'] ?? '') ?>">
            </div>
            <button class="btn btn-primary" type="submit">提交订单</button>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

