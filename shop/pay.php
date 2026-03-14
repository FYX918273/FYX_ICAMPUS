<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireLogin();

// 这里是下单成功之后的“支付宝支付彩蛋”页面，用个人收款码简单模拟一下真实支付。
$db = Database::getInstance();
$orderNo = trim((string)($_GET['order_no'] ?? ''));
$order = null;

if ($db->isAvailable() && $orderNo !== '') {
    $order = $db->queryOne(
        "SELECT id, order_no, total_amount, status, created_at 
         FROM orders 
         WHERE order_no = :no AND user_id = :uid 
         LIMIT 1",
        [
            'no' => $orderNo,
            'uid' => currentUserId(),
        ]
    );
}

// 把下面这个链接换成自己支付宝里复制出来的“收钱码链接”就行
$alipayDonateUrl = 'https://qr.alipay.com/fkx10575efjg4pnvnxa9p1f';

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 720px;">
    <div class="mtTop3x mbBottom2x text-center">
        <h2 class="mb-2">订单支付（支付宝彩蛋）</h2>
        <p class="text-muted small mb-0">
            这里只是简单模拟一下线上支付流程，真实项目应该接入商户版支付宝接口。
        </p>
    </div>

    <?php if ($order): ?>
        <div class="border rounded bg-white p-3 mb-3">
            <h6 class="mb-2">订单信息</h6>
            <p class="mb-1 small text-muted">订单号：<?= h($order['order_no'] ?? '') ?></p>
            <p class="mb-0 fw-bold">
                订单金额：￥<?= h(number_format((float)($order['total_amount'] ?? 0), 2)) ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="border rounded bg-white p-3 mb-3 text-center">
        <p class="small text-muted mb-2">
            点击下面的按钮，会在手机上打开支付宝收款页，相当于给作者打赏，作为“支付成功”的效果演示。
        </p>
        <a class="btn btn-primary mb-2" href="<?= h($alipayDonateUrl) ?>" target="_blank">
            去支付宝完成支付彩蛋
        </a>
        <p class="small text-muted mb-0">
            如果没有自动拉起，可以复制链接到支付宝里打开。
        </p>
    </div>

    <div class="text-center mbBottom3x">
        <a class="btn btn-outline-secondary btn-sm" href="/shop/orders.php">
            我已经体验完成，去看订单
        </a>
    </div>
</div>

<script>
    // 简单做一个 1 秒后的自动跳转，方便直接看到唤起效果
    (function () {
        var url = <?= json_encode($alipayDonateUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        if (!url) return;
        setTimeout(function () {
            window.location.href = url;
        }, 1000);
    })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

