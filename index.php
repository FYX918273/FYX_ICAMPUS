<?php
// 这个文件就是整个站的首页，大概就是把四个模块最新的数据各捞一条出来，做成概览。
// 下面几个数组就是首页四个小卡片的“摘要”来源。

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

startSession();

// 用我们封装的 Database 单例拿连接，如果数据库还没配置好，就直接走“空数据”的兜底逻辑。
$db = Database::getInstance();

// 这里先给四个模块准备好空数组，这样即使数据库连不上，模板也不会报错，最多就是显示“暂无数据”。
$latestActivities = [];
$latestProducts = [];
$latestLostFound = [];
$latestPosts = [];

if ($db->isAvailable()) {
    // 活动这块就随便取几个最新的，实际首页只拿第一条展示。
    $latestActivities = $db->query(
        "SELECT id, title, location, start_time 
         FROM activities 
         WHERE status = 'published' 
         ORDER BY start_time DESC 
         LIMIT 4"
    );

    // 二手同理，只看状态为上架的。
    $latestProducts = $db->query(
        "SELECT id, title, price, created_at 
         FROM products 
         WHERE status = 'on' 
         ORDER BY created_at DESC 
         LIMIT 4"
    );

    // 失物招领这边就不区分类型了，按时间倒序来。
    $latestLostFound = $db->query(
        "SELECT id, title, type, status, created_at 
         FROM lost_found 
         ORDER BY created_at DESC 
         LIMIT 4"
    );

    // 论坛帖子这边要过滤掉没审核过的，置顶的排前面。
    $latestPosts = $db->query(
        "SELECT id, title, created_at 
         FROM posts 
         WHERE status = 1 
         ORDER BY is_top DESC, created_at DESC 
         LIMIT 4"
    );
}

// 把公共头部拉进来，后面只写首页自己的内容区域。
include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row g-4">
        <div class="col-md-6">
            <section class="homeBlockArea">
                <div class="homeInfoCardBox">
                    <div class="homeInfoCardHeader">
                        <h2 class="homeInfoCardTitle mb-0">最新校园活动</h2>
                        <a href="/activities/list.php" class="homeInfoCardMore text-primary text-decoration-none">
                            查看全部
                        </a>
                    </div>
                    <?php if (!empty($latestActivities)): ?>
                        <?php $item = $latestActivities[0]; ?>
                        <p class="homeInfoCardText mb-0">
                            <?= h(substr((string)($item['title'] ?? ''), 0, 36)) ?>，
                            <?= h($item['location'] ?? '地点待定') ?>，
                            <?= h(substr((string)($item['start_time'] ?? ''), 0, 10)) ?>。
                        </p>
                    <?php else: ?>
                        <p class="homeInfoCardText mb-0">暂无活动，敬请期待。</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
        <div class="col-md-6">
            <section class="homeBlockArea">
                <div class="homeInfoCardBox">
                    <div class="homeInfoCardHeader">
                        <h2 class="homeInfoCardTitle mb-0">最新二手</h2>
                        <a href="/products/list.php" class="homeInfoCardMore text-primary text-decoration-none">
                            进入二手市场
                        </a>
                    </div>
                    <?php if (!empty($latestProducts)): ?>
                        <?php $item = $latestProducts[0]; ?>
                        <p class="homeInfoCardText mb-0">
                            <?= h(substr((string)($item['title'] ?? ''), 0, 36)) ?>，
                            ￥<?= h(number_format((float)($item['price'] ?? 0), 2)) ?>，
                            <?= h(substr((string)($item['created_at'] ?? ''), 0, 10)) ?> 发布。
                        </p>
                    <?php else: ?>
                        <p class="homeInfoCardText mb-0">暂无二手商品，快去发布一条吧。</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <section class="homeBlockArea">
                <div class="homeInfoCardBox">
                    <div class="homeInfoCardHeader">
                        <h2 class="homeInfoCardTitle mb-0">失物招领</h2>
                        <a href="/lostfound/list.php" class="homeInfoCardMore text-primary text-decoration-none">
                            查看更多
                        </a>
                    </div>
                    <?php if (!empty($latestLostFound)): ?>
                        <?php
                        $item = $latestLostFound[0];
                        $typeLabel = ($item['type'] ?? '') === 'found' ? '招领' : '寻物';
                        $status = $item['status'] ?? 'open';
                        $statusLabel = '进行中';
                        if ($status === 'closed') {
                            $statusLabel = '已解决';
                        } elseif ($status === 'urgent') {
                            $statusLabel = '紧急';
                        }
                        ?>
                        <p class="homeInfoCardText mb-0">
                            <?= h($typeLabel) ?> · <?= h($statusLabel) ?>，
                            <?= h(substr((string)($item['title'] ?? ''), 0, 36)) ?>，
                            <?= h(substr((string)($item['created_at'] ?? ''), 0, 10)) ?>。
                        </p>
                    <?php else: ?>
                        <p class="homeInfoCardText mb-0">暂时没有失物/招领信息。</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
        <div class="col-md-6">
            <section class="homeBlockArea">
                <div class="homeInfoCardBox">
                    <div class="homeInfoCardHeader">
                        <h2 class="homeInfoCardTitle mb-0">论坛热帖</h2>
                        <a href="/forum/index.php" class="homeInfoCardMore text-primary text-decoration-none">
                            前往论坛
                        </a>
                    </div>
                    <?php if (!empty($latestPosts)): ?>
                        <?php $item = $latestPosts[0]; ?>
                        <p class="homeInfoCardText mb-0">
                            <?= h(substr((string)($item['title'] ?? ''), 0, 44)) ?>，
                            <?= h(substr((string)($item['created_at'] ?? ''), 0, 10)) ?>。
                        </p>
                    <?php else: ?>
                        <p class="homeInfoCardText mb-0">暂无帖子，欢迎抢先发言。</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/includes/footer.php';
