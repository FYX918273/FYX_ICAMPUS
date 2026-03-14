<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

startSession();
$db = Database::getInstance();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = null;
$comments = [];
$images = [];
$error = '';
$liked = false;

if ($id > 0 && $db->isAvailable()) {
    $db->execute("UPDATE posts SET views = views + 1 WHERE id = :id LIMIT 1", ['id' => $id]);
    $post = $db->queryOne(
        "SELECT p.*, u.name AS user_name, u.username
         FROM posts p
         JOIN users u ON u.id = p.user_id
         WHERE p.id = :id AND p.status = 1
         LIMIT 1",
        ['id' => $id]
    );
    if ($post) {
        try {
            $images = $db->query(
                "SELECT image_url FROM post_images WHERE post_id = :pid ORDER BY sort_order ASC, id ASC",
                ['pid' => $id]
            );
            $images = array_values(array_filter(array_map(fn($r) => (string)($r['image_url'] ?? ''), $images)));
        } catch (PDOException $e) {
            $images = [];
        }
        if (empty($images) && !empty($post['image_url'])) {
            $images = [(string)$post['image_url']];
        }
        $comments = $db->query(
            "SELECT c.*, u.name AS user_name, u.username
             FROM comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.post_id = :pid AND c.status = 1
             ORDER BY c.created_at ASC",
            ['pid' => $id]
        );
    }
}

if (isPost()) {
    requireLogin();
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } elseif (!$post) {
        $error = '未找到该帖子。';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'comment') {
            $content = trim($_POST['content'] ?? '');
            if ($content === '') {
                $error = '评论内容不能为空。';
            } else {
                $db->execute(
                    "INSERT INTO comments (post_id,user_id,content,status,created_at)
                     VALUES (:pid,:uid,:c,1,NOW())",
                    ['pid' => $id, 'uid' => currentUserId(), 'c' => $content]
                );
                flash('success', '评论已发布。');
                redirect('/forum/post.php?id=' . $id);
            }
        } elseif ($action === 'like') {
            try {
                $db->beginTransaction();
                $db->execute(
                    "INSERT INTO post_likes (post_id,user_id,created_at) VALUES (:pid,:uid,NOW())",
                    ['pid' => $id, 'uid' => currentUserId()]
                );
                $db->execute("UPDATE posts SET likes = likes + 1 WHERE id = :id LIMIT 1", ['id' => $id]);
                $db->commit();
                flash('success', '已点赞。');
                redirect('/forum/post.php?id=' . $id);
            } catch (PDOException $e) {
                $db->rollBack();
                flash('info', '你已经点过赞了。');
                redirect('/forum/post.php?id=' . $id);
            }
        } elseif ($action === 'delete_post' && isAdmin()) {
            // 删除帖子：同时清理关联图片文件（uploads），数据库侧直接物理删除帖子记录
            $toDelete = [];
            try {
                $rows = $db->query(
                    "SELECT image_url FROM post_images WHERE post_id = :pid",
                    ['pid' => $id]
                );
                foreach ($rows as $r) {
                    $u = (string)($r['image_url'] ?? '');
                    if ($u !== '') {
                        $toDelete[] = $u;
                    }
                }
            } catch (PDOException $e) {
                $toDelete = [];
            }
            if (!empty($post['image_url'])) {
                $toDelete[] = (string)$post['image_url'];
            }
            $toDelete = array_values(array_unique(array_filter($toDelete)));

            $db->beginTransaction();
            try {
                // 由于 post_images / post_likes / comments 都有 ON DELETE CASCADE，
                // 这里直接删除 posts 记录即可，相关记录由数据库自动级联删除。
                $db->execute("DELETE FROM posts WHERE id = :id LIMIT 1", ['id' => $id]);
                $db->commit();
            } catch (PDOException $e) {
                $db->rollBack();
                flash('danger', '删除失败：数据库操作异常。');
                redirect('/forum/post.php?id=' . $id);
            }

            // 文件删除放在事务之后：即使失败也不影响帖子已删除
            deleteUploadedFiles($toDelete);

            flash('success', '帖子已删除。');
            redirect('/forum/index.php');
        } elseif ($action === 'delete_comment' && isAdmin()) {
            $cid = (int)($_POST['comment_id'] ?? 0);
            if ($cid > 0) {
                $db->execute(
                    "UPDATE comments SET status = 0 WHERE id = :cid AND post_id = :pid LIMIT 1",
                    ['cid' => $cid, 'pid' => $id]
                );
                flash('success', '评论已删除。');
                redirect('/forum/post.php?id=' . $id);
            }
        }
    }
}

if (isLoggedIn() && $db->isAvailable() && $id > 0) {
    $row = $db->queryOne(
        "SELECT 1 AS v FROM post_likes WHERE post_id = :pid AND user_id = :uid LIMIT 1",
        ['pid' => $id, 'uid' => currentUserId()]
    );
    $liked = !empty($row);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 900px;">
    <?php if (!$post): ?>
        <h2 class="mb-3 mt-3">帖子详情</h2>
        <div class="alert alert-warning small">未找到该帖子。</div>
    <?php else: ?>
        <!-- 顶部：帖子标题信息 + 右侧操作按钮 -->
        <div class="rowFlexBetweenTop mtTop3x mbBottom2x">
            <div>
                <h2 class="mb-1"><?php echo h($post['title'] ?? '帖子详情'); ?></h2>
                <div class="small text-muted">
                    作者：<?= h(($post['user_name'] ?? '') !== '' ? ($post['user_name'] ?? '') : ($post['username'] ?? '')) ?> ·
                    发表于 <?= h(substr((string)($post['created_at'] ?? ''), 0, 16)); ?>
                </div>
            </div>
            <div class="rowFlexSimple gapSmall2x">
                <a href="/forum/index.php" class="btn btn-outline-secondary btn-sm">返回广场</a>
                <?php if (isAdmin()): ?>
                    <form method="post" action="/forum/post.php?id=<?= (int)$id ?>" onsubmit="return confirm('确定删除该帖子吗？');">
                        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                        <input type="hidden" name="action" value="delete_post">
                        <button class="btn btn-outline-danger btn-sm" type="submit">删除帖子</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="homeInfoCardBox bg-white mb-3">
            <p class="homeInfoCardText">
                <?php echo nl2br(h($post['content'] ?? '')); ?>
            </p>
            <?php $n = min(9, count($images)); ?>
            <?php if ($n === 1): ?>
                <div class="pengyouquanBlock pengyouquanBlockSingle mt-2">
                    <a href="<?= h($images[0]) ?>" class="pengyouquanImgLink" target="_blank" rel="noopener">
                        <img src="<?= h($images[0]) ?>" alt="配图" class="pengyouquanImgOne rounded" loading="lazy">
                    </a>
                </div>
            <?php elseif ($n > 1): ?>
                <?php $colsClass = ($n === 2 || $n === 4) ? 'cols-2' : 'cols-3'; ?>
                <div class="pengyouquanBlock pengyouquanImgGrid mt-2 <?= h($colsClass) ?>">
                    <?php foreach (array_slice($images, 0, 9) as $src): ?>
                        <a href="<?= h($src) ?>" class="pengyouquanImgItem rounded" target="_blank" rel="noopener">
                            <img src="<?= h($src) ?>" alt="配图" loading="lazy">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="d-flex gap-3 small text-muted">
                <span>浏览：<?= h((string)($post['views'] ?? 0)) ?></span>
                <span>点赞：<?= h((string)($post['likes'] ?? 0)) ?></span>
            </div>
            <div class="mt-2">
                <?php if (!isLoggedIn()): ?>
                    <a class="btn btn-outline-primary btn-sm" href="/login.php">登录后点赞</a>
                <?php else: ?>
                    <form method="post" action="/forum/post.php?id=<?= (int)$id ?>" style="display:inline;">
                        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                        <input type="hidden" name="action" value="like">
                        <button class="btn btn-outline-primary btn-sm" type="submit" <?= $liked ? 'disabled' : '' ?>>
                            <?= $liked ? '已点赞' : '点赞' ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <h5 class="mb-2">评论</h5>
        <?php if (empty($comments)): ?>
            <div class="alert alert-info small">还没有评论。</div>
        <?php else: ?>
            <ul class="list-group mb-3">
                <?php foreach ($comments as $c): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div style="flex:1;">
                                <p class="mb-1 small"><?php echo nl2br(h($c['content'] ?? '')); ?></p>
                                <small class="text-muted">
                                    <?= h(($c['user_name'] ?? '') !== '' ? ($c['user_name'] ?? '') : ($c['username'] ?? '')) ?>
                                    · <?= h(substr((string)($c['created_at'] ?? ''), 0, 16)); ?>
                                </small>
                            </div>
                            <?php if (isAdmin()): ?>
                                <form method="post" action="/forum/post.php?id=<?= (int)$id ?>" onsubmit="return confirm('确定删除该评论吗？');">
                                    <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete_comment">
                                    <input type="hidden" name="comment_id" value="<?= (int)$c['id'] ?>">
                                    <button class="btn btn-outline-danger btn-sm" type="submit">删除</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="border rounded p-3 bg-white mb-3">
            <h6 class="mb-2">发表评论</h6>
            <?php if (!isLoggedIn()): ?>
                <a class="btn btn-primary btn-sm" href="/login.php">登录后评论</a>
            <?php else: ?>
                <form method="post" action="/forum/post.php?id=<?= (int)$id ?>">
                    <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">
                    <input type="hidden" name="action" value="comment">
                    <textarea name="content" class="form-control mb-2" rows="3" placeholder="说点什么..."><?= h($_POST['content'] ?? '') ?></textarea>
                    <button class="btn btn-primary btn-sm" type="submit">发布评论</button>
                </form>
            <?php endif; ?>
        </div>

        <a href="/forum/index.php" class="btn btn-outline-secondary btn-sm">返回广场</a>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

