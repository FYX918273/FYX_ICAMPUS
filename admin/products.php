<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

startSession();
requireAdmin();

$db = Database::getInstance();
$kw = trim($_GET['kw'] ?? '');
$rows = [];
$error = '';

function onOffLabel(string $s): string
{
    return match ($s) {
        'on' => '上架',
        'off' => '下架',
        default => $s,
    };
}

if (isPost()) {
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } else {
        $action = $_POST['action'] ?? '';
        $pid = (int)($_POST['id'] ?? 0);
        if ($pid > 0) {
            if ($action === 'off') {
                $db->execute("UPDATE products SET status = 'off' WHERE id = :id LIMIT 1", ['id' => $pid]);
                flash('success', '已下架商品。');
                redirect('/admin/products.php');
            } elseif ($action === 'on') {
                $db->execute("UPDATE products SET status = 'on' WHERE id = :id LIMIT 1", ['id' => $pid]);
                flash('success', '已上架商品。');
                redirect('/admin/products.php');
            } elseif ($action === 'delete') {
                $img = null;
                $row = $db->queryOne(
                    "SELECT image_url FROM products WHERE id = :id LIMIT 1",
                    ['id' => $pid]
                );
                if ($row && !empty($row['image_url'])) {
                    $img = (string)$row['image_url'];
                }
                $db->execute("DELETE FROM products WHERE id = :id LIMIT 1", ['id' => $pid]);
                if ($img) {
                    deleteUploadedFile($img);
                }
                flash('success', '已删除商品。');
                redirect('/admin/products.php');
            }
        }
    }
}

if ($db->isAvailable()) {
    $where = "1=1";
    $params = [];
    if ($kw !== '') {
        $where .= " AND (p.title LIKE :kw OR p.description LIKE :kw OR u.username LIKE :kw OR u.name LIKE :kw)";
        $params['kw'] = '%' . $kw . '%';
    }
    $rows = $db->query(
        "SELECT p.*, u.username, u.name AS user_name
         FROM products p
         JOIN users u ON u.id = p.user_id
         WHERE {$where}
         ORDER BY p.created_at DESC
         LIMIT 200",
        $params
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1100px;">
    <!-- 顶部：页面标题 + 返回入口 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">二手管理</h2>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/index.php">返回后台首页</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <form class="row g-2 align-items-center mb-3" method="get" action="/admin/products.php">
        <div class="col-sm-9">
            <input type="text" class="form-control" name="kw" value="<?= h($kw) ?>" placeholder="搜索标题/描述/发布者">
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
                <th>标题</th>
                <th>价格</th>
                <th>发布者</th>
                <th>状态</th>
                <th>创建时间</th>
                <th style="width:240px;">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $p): ?>
                <tr>
                    <td><?= h((string)$p['id']) ?></td>
                    <td>
                        <a href="/products/detail.php?id=<?= (int)$p['id'] ?>" target="_blank"><?= h($p['title'] ?? '') ?></a>
                    </td>
                    <td>￥<?= h(number_format((float)($p['price'] ?? 0), 2)) ?></td>
                    <td><?= h(($p['user_name'] ?? '') !== '' ? ($p['user_name'] ?? '') : ($p['username'] ?? '')) ?></td>
                    <td><?= h(onOffLabel((string)($p['status'] ?? ''))) ?></td>
                    <td><?= h(substr((string)($p['created_at'] ?? ''), 0, 16)) ?></td>
                    <td>
                        <!-- 操作按钮：上架/下架/删除 -->
                        <div class="rowFlexSimple gapSmall2x rowFlexWrap">
                            <?php if (($p['status'] ?? 'on') === 'on'): ?>
                                <form method="post" action="/admin/products.php">
                                    <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <input type="hidden" name="action" value="off">
                                    <button class="btn btn-outline-dark btn-sm" type="submit">下架</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="/admin/products.php">
                                    <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <input type="hidden" name="action" value="on">
                                    <button class="btn btn-outline-primary btn-sm" type="submit">上架</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="/admin/products.php" onsubmit="return confirm('确定删除该商品吗？');">
                                <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button class="btn btn-outline-danger btn-sm" type="submit">删除</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

