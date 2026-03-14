<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireLogin();

$db = Database::getInstance();
$orders = [];
$itemsByOrder = [];
$error = '';

if (isPost()) {
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } else {
        $action = $_POST['action'] ?? '';
        $oid = (int)($_POST['order_id'] ?? 0);
        if ($oid > 0) {
            $order = $db->queryOne(
                "SELECT * FROM orders WHERE id = :id AND user_id = :uid LIMIT 1",
                ['id' => $oid, 'uid' => currentUserId()]
            );
            if (!$order) {
                $error = '订单不存在。';
            } else {
                $st = $order['status'] ?? 'pending';
                if ($action === 'cancel' && $st === 'pending') {
                    $db->execute(
                        "UPDATE orders SET status = 'cancelled' WHERE id = :id AND user_id = :uid LIMIT 1",
                        ['id' => $oid, 'uid' => currentUserId()]
                    );
                    flash('success', '订单已取消。');
                    redirect('/shop/orders.php');
                } elseif ($action === 'confirm' && $st === 'shipped') {
                    $db->execute(
                        "UPDATE orders SET status = 'completed' WHERE id = :id AND user_id = :uid LIMIT 1",
                        ['id' => $oid, 'uid' => currentUserId()]
                    );
                    flash('success', '已确认收货。');
                    redirect('/shop/orders.php');
                }
            }
        }
    }
}

if ($db->isAvailable()) {
    $orders = $db->query(
        "SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC",
        ['uid' => currentUserId()]
    );
    if (!empty($orders)) {
        $ids = array_map(fn($o) => (int)$o['id'], $orders);
        $in = implode(',', $ids);
        $items = $db->query("SELECT * FROM order_items WHERE order_id IN ({$in}) ORDER BY id ASC");
        foreach ($items as $it) {
            $oid = (int)$it['order_id'];
            $itemsByOrder[$oid][] = $it;
        }
    }
}

function statusLabel(string $s): string
{
    return match ($s) {
        'pending' => '待支付（模拟）',
        'paid' => '已支付',
        'shipped' => '已发货',
        'completed' => '已完成',
        'cancelled' => '已取消',
        default => $s,
    };
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 980px;">
    <!-- 顶部：页面标题 + 返回入口 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">我的订单</h2>
        <a class="btn btn-outline-secondary btn-sm" href="/profile.php">返回个人中心</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info small">你还没有下过单。</div>
        <a class="btn btn-primary btn-sm" href="/shop/index.php">去逛超市</a>
    <?php else: ?>
        <?php foreach ($orders as $o): ?>
            <?php
            $oid = (int)$o['id'];
            $st = (string)($o['status'] ?? 'pending');
            $items = $itemsByOrder[$oid] ?? [];
            ?>
            <div class="border rounded bg-white p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-bold">订单号：<?= h($o['order_no'] ?? '') ?></div>
                        <div class="small text-muted">
                            下单时间：<?= h(substr((string)($o['created_at'] ?? ''), 0, 16)) ?> ·
                            状态：<?= h(statusLabel($st)) ?>
                        </div>
                        <div class="small text-muted">地址：<?= h($o['address'] ?? '') ?></div>
                        <?php if (!empty($o['remark'])): ?>
                            <div class="small text-muted">备注：<?= h($o['remark'] ?? '') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">￥<?= h(number_format((float)($o['total_amount'] ?? 0), 2)) ?></div>
                        <!-- 订单操作按钮（根据状态显示） -->
                        <div class="rowFlexSimple gapSmall2x rowFlexRight mtTop3x rowFlexWrap">
                            <?php if ($st === 'pending'): ?>
                                <form method="post" action="/shop/orders.php" onsubmit="return confirm('确定取消该订单吗？');">
                                    <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="order_id" value="<?= $oid ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button class="btn btn-outline-danger btn-sm" type="submit">取消订单</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($st === 'shipped'): ?>
                                <form method="post" action="/shop/orders.php" onsubmit="return confirm('确认收货吗？');">
                                    <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="order_id" value="<?= $oid ?>">
                                    <input type="hidden" name="action" value="confirm">
                                    <button class="btn btn-outline-success btn-sm" type="submit">确认收货</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($items)): ?>
                    <div class="mt-3">
                        <div class="small text-muted mb-1">商品明细</div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                <tr>
                                    <th>商品</th>
                                    <th style="width:120px;">单价</th>
                                    <th style="width:90px;">数量</th>
                                    <th style="width:140px;">小计</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($items as $it): ?>
                                    <tr>
                                        <td><?= h($it['product_name'] ?? '') ?></td>
                                        <td>￥<?= h(number_format((float)($it['price'] ?? 0), 2)) ?></td>
                                        <td><?= h((string)($it['quantity'] ?? 0)) ?></td>
                                        <td>￥<?= h(number_format((float)($it['subtotal'] ?? 0), 2)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

