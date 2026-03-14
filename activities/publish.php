<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();
requireAdmin();

$db = Database::getInstance();
$error = '';

$id = (int)($_GET['id'] ?? 0);
$row = null;

if ($id > 0 && $db->isAvailable()) {
    $row = $db->queryOne("SELECT * FROM activities WHERE id = :id LIMIT 1", ['id' => $id]);
}

if (isPost()) {
    verifyCsrfOrDie();
    if (!$db->isAvailable()) {
        $error = '数据库连接失败，请稍后再试。';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $start_time = trim($_POST['start_time'] ?? '');
        $end_time = trim($_POST['end_time'] ?? '');
        $signup_deadline = trim($_POST['signup_deadline'] ?? '');
        $max_participants = trim($_POST['max_participants'] ?? '');
        $status = trim($_POST['status'] ?? 'published');

        if ($title === '') {
            $error = '标题不能为空。';
        } elseif (!in_array($status, ['draft', 'published', 'closed'], true)) {
            $error = '状态不合法。';
        } else {
            $max = null;
            if ($max_participants !== '') {
                if (!ctype_digit($max_participants) || (int)$max_participants < 1) {
                    $error = '人数上限必须为正整数，或留空表示不限。';
                } else {
                    $max = (int)$max_participants;
                }
            }
        }

        if ($error === '') {
            if ($id > 0) {
                $db->execute(
                    "UPDATE activities
                     SET title=:t, description=:d, location=:l, start_time=:st, end_time=:et,
                         signup_deadline=:sd, max_participants=:mp, status=:s
                     WHERE id=:id LIMIT 1",
                    [
                        't' => $title,
                        'd' => $description !== '' ? $description : null,
                        'l' => $location !== '' ? $location : null,
                        'st' => $start_time !== '' ? $start_time : null,
                        'et' => $end_time !== '' ? $end_time : null,
                        'sd' => $signup_deadline !== '' ? $signup_deadline : null,
                        'mp' => $max,
                        's' => $status,
                        'id' => $id,
                    ]
                );
                flash('success', '活动已更新。');
                redirect('/activities/detail.php?id=' . $id);
            } else {
                $db->execute(
                    "INSERT INTO activities (title,description,location,start_time,end_time,signup_deadline,max_participants,status,creator_id,created_at)
                     VALUES (:t,:d,:l,:st,:et,:sd,:mp,:s,:cid,NOW())",
                    [
                        't' => $title,
                        'd' => $description !== '' ? $description : null,
                        'l' => $location !== '' ? $location : null,
                        'st' => $start_time !== '' ? $start_time : null,
                        'et' => $end_time !== '' ? $end_time : null,
                        'sd' => $signup_deadline !== '' ? $signup_deadline : null,
                        'mp' => $max,
                        's' => $status,
                        'cid' => currentUserId(),
                    ]
                );
                $newId = $db->lastInsertId();
                flash('success', '活动已发布。');
                redirect('/activities/detail.php?id=' . $newId);
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 860px;">
    <!-- 顶部：发布/编辑标题 + 返回入口 -->
    <div class="rowFlexBetweenMid mtTop3x mbBottom2x">
        <h2 class="mbBottom0x"><?= $id > 0 ? '编辑活动' : '发布活动' ?></h2>
        <a class="btn btn-outline-secondary btn-sm" href="/activities/list.php">返回列表</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/activities/publish.php<?= $id > 0 ? ('?id=' . (int)$id) : '' ?>" class="border rounded p-3 bg-white">
        <input type="hidden" name="_csrf" value="<?= h(csrfToken()) ?>">

        <div class="mb-3">
            <label class="form-label">标题</label>
            <input type="text" name="title" class="form-control"
                   value="<?= h($_POST['title'] ?? ($row['title'] ?? '')) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">活动说明（可选）</label>
            <textarea name="description" class="form-control" rows="5"><?= h($_POST['description'] ?? ($row['description'] ?? '')) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">地点（可选）</label>
            <input type="text" name="location" class="form-control"
                   value="<?= h($_POST['location'] ?? ($row['location'] ?? '')) ?>">
        </div>
        <div class="row g-2">
            <div class="col-md-6 mb-3">
                <label class="form-label">开始时间（可选）</label>
                <input type="datetime-local" name="start_time" class="form-control"
                       value="<?= h($_POST['start_time'] ?? (!empty($row['start_time']) ? str_replace(' ', 'T', substr($row['start_time'], 0, 16)) : '')) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">结束时间（可选）</label>
                <input type="datetime-local" name="end_time" class="form-control"
                       value="<?= h($_POST['end_time'] ?? (!empty($row['end_time']) ? str_replace(' ', 'T', substr($row['end_time'], 0, 16)) : '')) ?>">
            </div>
        </div>
        <div class="row g-2">
            <div class="col-md-6 mb-3">
                <label class="form-label">报名截止（可选）</label>
                <input type="datetime-local" name="signup_deadline" class="form-control"
                       value="<?= h($_POST['signup_deadline'] ?? (!empty($row['signup_deadline']) ? str_replace(' ', 'T', substr($row['signup_deadline'], 0, 16)) : '')) ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">人数上限（可选，留空不限）</label>
                <input type="number" min="1" name="max_participants" class="form-control"
                       value="<?= h($_POST['max_participants'] ?? ($row['max_participants'] ?? '')) ?>">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">状态</label>
            <?php $curStatus = $_POST['status'] ?? ($row['status'] ?? 'published'); ?>
            <select name="status" class="form-select">
                <option value="draft" <?= $curStatus === 'draft' ? 'selected' : '' ?>>草稿</option>
                <option value="published" <?= $curStatus === 'published' ? 'selected' : '' ?>>已发布</option>
                <option value="closed" <?= $curStatus === 'closed' ? 'selected' : '' ?>>已关闭</option>
            </select>
        </div>

        <button class="btn btn-primary" type="submit"><?= $id > 0 ? '保存' : '发布' ?></button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

