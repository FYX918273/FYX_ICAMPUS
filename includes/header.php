<?php
// 这里是整个站统一用的头部：负责把 session 拉起来，顺便算一下登录状态，
// 然后把导航栏、首页大横幅那一坨 HTML 输出出去，业务页面只管填内容就行。

require_once __DIR__ . '/functions.php';
startSession();

// 这些变量就是后面模板里用来判断“是不是登录了、是不是管理员”等等。
$loggedIn = isLoggedIn();
$role = currentUserRole();
$user = $loggedIn ? currentUser() : null;

// 顶部那些一次性的提示（比如“登录成功”“发布成功”）就在这里统一读一读。
$flashes = consumeFlashes();

// 看一下当前是不是首页，用来决定导航条要不要透明 + 要不要显示 Banner。
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$isHome = ($path === '/' || $path === '/index.php');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>iCampus 校园一站式服务平台</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<header class="shadow-sm">
    <nav id="mainNavbar"
         class="navbar navbar-expand-lg navbar-dark top-nav <?= $isHome ? 'transparent' : 'solid' ?>">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/index.php">
                iCampus
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="/index.php">首页</a></li>
                    <li class="nav-item"><a class="nav-link" href="/products/list.php">二手交易</a></li>
                    <li class="nav-item"><a class="nav-link" href="/lostfound/list.php">失物招领</a></li>
                    <li class="nav-item"><a class="nav-link" href="/activities/list.php">校园活动</a></li>
                    <li class="nav-item"><a class="nav-link" href="/forum/index.php">校园论坛</a></li>
                    <li class="nav-item"><a class="nav-link" href="/shop/index.php">校园超市</a></li>
                    <?php if ($loggedIn): ?>
                        <?php
                        $displayName = $user['name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? '个人中心';
                        ?>
                        <li class="nav-item nav-user">
                            <a class="nav-link nav-username" href="/profile.php">
                                <?= h((string)$displayName) ?>
                            </a>
                            <div class="nav-user-menu" role="menu" aria-label="用户菜单">
                                <a class="nav-user-menu-item" href="/profile.php" role="menuitem">个人中心</a>
                                <a class="nav-user-menu-item" href="/products/my.php" role="menuitem">我的二手</a>
                                <a class="nav-user-menu-item" href="/activities/my.php" role="menuitem">我的活动</a>
                                <a class="nav-user-menu-item" href="/forum/my.php" role="menuitem">我的帖子</a>
                                <a class="nav-user-menu-item" href="/shop/orders.php" role="menuitem">我的订单</a>
                                <a class="nav-user-menu-item" href="/logout.php" role="menuitem">退出</a>
                            </div>
                        </li>
                        <?php if ($role === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/admin/index.php">后台管理</a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">登录</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link nav-small-button ms-lg-2" href="/register.php">注册</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <?php if ($isHome): ?>
        <section class="home-banner home-banner-bg">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-md-8">
                        <div class="home-banner-text-box">
                            <h1 class="home-banner-big-title mb-3">iCampus 校园一站式服务平台</h1>
                            <p class="home-banner-small-title mb-4">
                                在这里发布二手、寻找失物、报名活动、逛校园超市、聊聊校园日常，一站解决校园生活需求。
                            </p>
                            <div class="d-flex flex-wrap gap-3">
                                <a href="/activities/list.php" class="btn btn-outline-light btn-lg banner-button-outline">
                                    查看校园活动
                                </a>
                                <a href="/products/list.php" class="btn btn-outline-light btn-lg banner-button-outline">
                                    逛二手市场
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
</header>
<main class="main-container <?= $isHome ? 'main-home' : 'main-inner' ?>">

<?php if (!empty($flashes)): ?>
    <div class="container mt-3" style="max-width: 980px;">
        <?php foreach ($flashes as $f): ?>
            <?php
            $t = $f['type'] ?? 'info';
            $map = [
                'success' => 'success',
                'error' => 'danger',
                'warning' => 'warning',
                'info' => 'info',
            ];
            $cls = $map[$t] ?? 'info';
            ?>
            <div class="alert alert-<?= h($cls) ?> py-2 small mb-2 flashMsgBox flashMsgShow" role="alert">
                <?= h((string)($f['message'] ?? '')) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

