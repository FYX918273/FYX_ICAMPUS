<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

startSession();
requireAdmin();

$db = Database::getInstance();
$kw = trim($_GET['kw'] ?? '');
$cat = trim($_GET['category'] ?? '');
$status = trim($_GET['status'] ?? '');
$editId = (int)($_GET['edit'] ?? 0);
$rows = [];
$error = '';
$editing = null;

function onOffLabel(string $s): string
{
    return match ($s) {
        'on' => '上架',
        'off' => '下架',
        default => $s,
    };
}

if ($db->isAvailable() && $editId > 0) {
    $editing = $db->queryOne("SELECT * FROM shop_products WHERE id = :id LIMIT 1", ['id' => $editId]);
    if (!$editing) {
        $editId = 0;
    }
}

if (isPost()) {
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);

        if ($action === 'save') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);
            $category = trim($_POST['category'] ?? '');
            $newStatus = ($_POST['status'] ?? 'on') === 'off' ? 'off' : 'on';

            if ($name === '') {
                $error = '商品名称不能为空。';
            } elseif ($price < 0) {
                $error = '价格不能为负数。';
            } elseif ($stock < 0) {
                $error = '库存不能为负数。';
            } else {
                $img = uploadImage('image', 'shop');
                if (!$img['ok']) {
                    $error = $img['error'] ?? '上传失败。';
                } else {
                    $imageUrl = $img['path'] ?? null;
                    if ($id > 0) {
                        $setImg = $imageUrl ? ", image_url = :image_url" : "";
                        $params = [
                            'id' => $id,
                            'name' => $name,
                            'description' => $description !== '' ? $description : null,
                            'price' => $price,
                            'stock' => $stock,
                            'category' => $category !== '' ? $category : null,
                            'status' => $newStatus,
                        ];
                        if ($imageUrl) $params['image_url'] = $imageUrl;

                        $db->execute(
                            "UPDATE shop_products
                             SET name = :name,
                                 description = :description,
                                 price = :price,
                                 stock = :stock,
                                 category = :category,
                                 status = :status
                                 {$setImg}
                             WHERE id = :id
                             LIMIT 1",
                            $params
                        );
                        flash('success', '商品已更新。');
                        redirect('/admin/shop_products.php');
                    } else {
                        $db->execute(
                            "INSERT INTO shop_products (name,description,price,stock,category,image_url,status,created_at)
                             VALUES (:name,:description,:price,:stock,:category,:image_url,:status,NOW())",
                            [
                                'name' => $name,
                                'description' => $description !== '' ? $description : null,
                                'price' => $price,
                                'stock' => $stock,
                                'category' => $category !== '' ? $category : null,
                                'image_url' => $imageUrl,
                                'status' => $newStatus,
                            ]
                        );
                        flash('success', '商品已新增并上架。');
                        redirect('/admin/shop_products.php');
                    }
                }
            }
        } elseif ($id > 0 && $action === 'on') {
            $db->execute("UPDATE shop_products SET status = 'on' WHERE id = :id LIMIT 1", ['id' => $id]);
            flash('success', '已上架商品。');
            redirect('/admin/shop_products.php');
        } elseif ($id > 0 && $action === 'off') {
            $db->execute("UPDATE shop_products SET status = 'off' WHERE id = :id LIMIT 1", ['id' => $id]);
            flash('success', '已下架商品。');
            redirect('/admin/shop_products.php');
        } elseif ($id > 0 && $action === 'delete') {
            $img = null;
            $row = $db->queryOne(
                "SELECT image_url FROM shop_products WHERE id = :id LIMIT 1",
                ['id' => $id]
            );
            if ($row && !empty($row['image_url'])) {
                $img = (string)$row['image_url'];
            }
            $ref = $db->queryOne(
                "SELECT COUNT(*) AS c FROM order_items WHERE product_id = :id",
                ['id' => $id]
            );
            $refCount = (int)($ref['c'] ?? 0);
            if ($refCount > 0) {
                flash('error', '该商品已产生订单记录，不能删除。建议先下架保留历史订单。');
                redirect('/admin/shop_products.php');
            }

            try {
                $db->execute("DELETE FROM shop_products WHERE id = :id LIMIT 1", ['id' => $id]);
                if ($img) {
                    deleteUploadedFile($img);
                }
                flash('success', '已删除商品。');
                redirect('/admin/shop_products.php');
            } catch (PDOException $e) {
                flash('error', '删除失败：该商品可能已被订单引用或数据受保护。');
                redirect('/admin/shop_products.php');
            }
        }
    }
}

if ($db->isAvailable()) {
    $where = "1=1";
    $params = [];
    if ($kw !== '') {
        $where .= " AND (name LIKE :kw OR description LIKE :kw)";
        $params['kw'] = '%' . $kw . '%';
    }
    if ($cat !== '') {
        $where .= " AND category = :cat";
        $params['cat'] = $cat;
    }
    if ($status === 'on' || $status === 'off') {
        $where .= " AND status = :st";
        $params['st'] = $status;
    } else {
        $status = '';
    }
    $rows = $db->query(
        "SELECT * FROM shop_products
         WHERE {$where}
         ORDER BY created_at DESC
         LIMIT 300",
        $params
    );
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 1100px;">
    <!-- 顶部：页面标题 + 返回入口 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x">超市商品管理</h2>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/index.php">返回后台首页</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="border rounded bg-white p-3">
                <!-- 新增/编辑：标题 + 取消编辑按钮 -->
                <div class="rowFlexBetweenMid mbBottom2x">
                    <h5 class="mbBottom0x"><?= $editId > 0 ? '编辑商品' : '新增商品' ?></h5>
                    <?php if ($editId > 0): ?>
                        <a class="btn btn-outline-secondary btn-sm" href="/admin/shop_products.php">取消编辑</a>
                    <?php endif; ?>
                </div>

                <form method="post" action="/admin/shop_products.php<?= $editId > 0 ? ('?edit=' . (int)$editId) : '' ?>" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">

                    <div class="mb-2">
                        <label class="form-label">商品名称</label>
                        <input class="form-control" name="name" value="<?= h($_POST['name'] ?? ($editing['name'] ?? '')) ?>" placeholder="例如：矿泉水 550ml">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">分类（可选）</label>
                        <input class="form-control" name="category" value="<?= h($_POST['category'] ?? ($editing['category'] ?? '')) ?>" placeholder="例如：饮料/零食/日用品">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">价格</label>
                            <input class="form-control" name="price" inputmode="decimal" value="<?= h((string)($_POST['price'] ?? ($editing['price'] ?? '0.00'))) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">库存</label>
                            <input class="form-control" name="stock" inputmode="numeric" value="<?= h((string)($_POST['stock'] ?? ($editing['stock'] ?? 0))) ?>">
                        </div>
                    </div>
                    <div class="mb-2 mt-2">
                        <label class="form-label">状态</label>
                        <?php $st = ($_POST['status'] ?? ($editing['status'] ?? 'on')) === 'off' ? 'off' : 'on'; ?>
                        <select class="form-select" name="status">
                            <option value="on" <?= $st === 'on' ? 'selected' : '' ?>>上架</option>
                            <option value="off" <?= $st === 'off' ? 'selected' : '' ?>>下架</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">商品描述（可选）</label>
                        <textarea class="form-control" name="description" rows="4" placeholder="简单描述一下商品信息"><?= h($_POST['description'] ?? ($editing['description'] ?? '')) ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">商品图片（可选，jpg/png/gif/webp）</label>
                        <input class="form-control" type="file" name="image" accept="image/*">
                        <?php if (!empty($editing['image_url'])): ?>
                            <div class="small text-muted mt-2">当前图片：</div>
                            <img src="<?= h($editing['image_url']) ?>" alt="商品图片" class="img-fluid rounded mt-1">
                        <?php endif; ?>
                    </div>

                    <button class="btn btn-primary" type="submit">保存</button>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <form class="row g-2 align-items-center mb-3" method="get" action="/admin/shop_products.php">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="kw" value="<?= h($kw) ?>" placeholder="搜索名称/描述">
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="category" value="<?= h($cat) ?>" placeholder="分类">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="" <?= $status === '' ? 'selected' : '' ?>>全部状态</option>
                        <option value="on" <?= $status === 'on' ? 'selected' : '' ?>>上架</option>
                        <option value="off" <?= $status === 'off' ? 'selected' : '' ?>>下架</option>
                    </select>
                </div>
                <div class="col-12 d-grid">
                    <button class="btn btn-outline-dark" type="submit">筛选</button>
                </div>
            </form>

            <div class="table-responsive border rounded bg-white">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>商品</th>
                        <th>价格</th>
                        <th>库存</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th style="width:280px;">操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $sp): ?>
                        <tr>
                            <td><?= h((string)$sp['id']) ?></td>
                            <td>
                                <div class="fw-bold"><?= h($sp['name'] ?? '') ?></div>
                                <div class="small text-muted">
                                    <?= !empty($sp['category']) ? ('分类：' . h($sp['category'])) : '未分类' ?>
                                </div>
                            </td>
                            <td>￥<?= h(number_format((float)($sp['price'] ?? 0), 2)) ?></td>
                            <td><?= h((string)($sp['stock'] ?? 0)) ?></td>
                            <td><?= h(onOffLabel((string)($sp['status'] ?? ''))) ?></td>
                            <td><?= h(substr((string)($sp['created_at'] ?? ''), 0, 16)) ?></td>
                            <td>
                                <!-- 操作按钮：编辑/上架/下架/删除 -->
                                <div class="rowFlexSimple gapSmall2x rowFlexWrap">
                                    <a class="btn btn-outline-secondary btn-sm" href="/admin/shop_products.php?edit=<?= (int)$sp['id'] ?>">编辑</a>
                                    <?php if (($sp['status'] ?? 'on') === 'on'): ?>
                                        <form method="post" action="/admin/shop_products.php">
                                            <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                            <input type="hidden" name="action" value="off">
                                            <input type="hidden" name="id" value="<?= (int)$sp['id'] ?>">
                                            <button class="btn btn-outline-dark btn-sm" type="submit">下架</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="/admin/shop_products.php">
                                            <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                            <input type="hidden" name="action" value="on">
                                            <input type="hidden" name="id" value="<?= (int)$sp['id'] ?>">
                                            <button class="btn btn-outline-primary btn-sm" type="submit">上架</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="/admin/shop_products.php" onsubmit="return confirm('确定删除该商品吗？');">
                                        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$sp['id'] ?>">
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
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

