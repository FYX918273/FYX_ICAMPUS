<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireAdmin();

$db = Database::getInstance();
$kw = trim($_GET['kw'] ?? '');
$rows = [];
$error = '';

function orderStatusLabel(string $s): string
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

if (isPost()) {
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } else {
        $oid = (int)($_POST['id'] ?? 0);
        $st = trim($_POST['status'] ?? '');
        if ($oid > 0 && in_array($st, ['pending', 'paid', 'shipped', 'completed', 'cancelled'], true)) {
            $db->execute("UPDATE orders SET status = :s WHERE id = :id LIMIT 1", ['s' => $st, 'id' => $oid]);
            flash('success', '订单状态已更新。');
            redirect('/admin/orders.php');
        }
    }
}

if ($db->isAvailable()) {
    $where = "1=1";
    $params = [];
    if ($kw !== '') {
        $where .= " AND (o.order_no LIKE :kw OR u.username LIKE :kw OR u.name LIKE :kw OR u.student_id LIKE :kw OR u.phone LIKE :kw OR o.address LIKE :kw)";
        $params['kw'] = '%' . $kw . '%';
    }
    $rows = $db->query(
        "SELECT o.*, u.username, u.name AS user_name, u.student_id, u.phone
         FROM orders o
         JOIN users u ON u.id = o.user_id
         WHERE {$where}
         ORDER BY o.created_at DESC
         LIMIT 200",
        $params
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1100px;">
    <!-- 顶部：页面标题 + 返回入口 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">订单管理</h2>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/index.php">返回后台首页</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <form class="row g-2 align-items-center mb-3" method="get" action="/admin/orders.php">
        <div class="col-sm-9">
            <input type="text" class="form-control" name="kw" value="<?= h($kw) ?>" placeholder="搜索订单号/用户名/姓名">
        </div>
        <div class="col-sm-3 d-grid">
            <button class="btn btn-outline-dark" type="submit">搜索</button>
        </div>
    </form>

    <div class="table-responsive border rounded bg-white">
        <table class="table table-sm mb-0 align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>订单号</th>
                <th>用户</th>
                <th>学号</th>
                <th>手机号</th>
                <th>金额</th>
                <th>状态</th>
                <th>地址</th>
                <th>创建时间</th>
                <th style="width:260px;">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $o): ?>
                <tr>
                    <td><?= h((string)$o['id']) ?></td>
                    <td><?= h($o['order_no'] ?? '') ?></td>
                    <td><?= h(($o['user_name'] ?? '') !== '' ? ($o['user_name'] ?? '') : ($o['username'] ?? '')) ?></td>
                    <td><?= h($o['student_id'] ?? '') ?></td>
                    <td><?= h($o['phone'] ?? '') ?></td>
                    <td>￥<?= h(number_format((float)($o['total_amount'] ?? 0), 2)) ?></td>
                    <td><?= h(orderStatusLabel((string)($o['status'] ?? ''))) ?></td>
                    <td><?= h($o['address'] ?? '') ?></td>
                    <td><?= h(substr((string)($o['created_at'] ?? ''), 0, 16)) ?></td>
                    <td>
                        <!-- 修改订单状态：下拉选择 + 更新按钮 -->
                        <form method="post" action="/admin/orders.php" class="rowFlexSimple gapSmall2x">
                            <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                            <select name="status" class="form-select form-select-sm">
                                <?php $cur = $o['status'] ?? 'pending'; ?>
                                <?php foreach (['pending','paid','shipped','completed','cancelled'] as $st): ?>
                                    <option value="<?= h($st) ?>" <?= $cur === $st ? 'selected' : '' ?>><?= h(orderStatusLabel($st)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-dark btn-sm" type="submit">更新</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

