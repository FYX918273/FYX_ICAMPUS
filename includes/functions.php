<?php

// 放一些到处都会用到的小工具函数，比如登录状态、跳转、分页之类的。
// 后面所有页面基本都要 include 这个文件，相当于整个站的“公共小工具库”。

require_once __DIR__ . '/../config/database.php';

// 简单包一层，避免每个页面都去自己判断 session_status。
// 以后想换 session 策略，也只用改这里。
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// 为了防止直接 echo 用户输入把页面干烂，这里统一做一次 htmlspecialchars。
// 写模板的时候看到 h() 基本就可以理解成“放心输出”。
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// 简单封一下 Location 跳转，顺手把 exit 写一起，免得每个页面都写一遍。
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// 简单给“返回上一页”用的，如果没有 referrer 就退回首页。
function backUrl(): string
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '/index.php';
    return $ref !== '' ? $ref : '/index.php';
}

// 懒得每次都写 $_SERVER 判断，就弄个函数，看当前是不是 POST 提交。
function isPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

// 看看 session 里有没有 user_id，有的话就当作已经登录。
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

// 拿当前登录用户的 id，没登录直接给 0，方便强转 int 使用。
function currentUserId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

// 从 session 里读一下角色，比如 admin / user 之类的。
function currentUserRole(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

// 就是个语义更清楚的封装，看当前是不是管理员。
function isAdmin(): bool
{
    return currentUserRole() === 'admin';
}

// 某些页面必须登录才能看，就直接先调这个。
// 如果没登录，会塞一条提示，然后把当前访问地址拼到 redirect 里带给登录页。
function requireLogin(): void
{
    if (!isLoggedIn()) {
        flash('error', '请先登录后再继续。');
        // 默认回跳当前地址
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/index.php';
        $qs = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?: '';
        $target = $path . ($qs ? ('?' . $qs) : '');
        redirect('/login.php?redirect=' . urlencode($target));
    }
}

// 后台管理相关的页面就用这个。
// 先保证人是登录的，再看是不是 admin，不是的话直接给一个“无权限”的小页面。
function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        include __DIR__ . '/header.php';
        echo '<div class="container" style="max-width: 760px;">';
        echo '<h2 class="mb-3 mt-3">无权限访问</h2>';
        echo '<div class="alert alert-warning small">该页面仅管理员可访问。</div>';
        echo '<a class="btn btn-outline-secondary btn-sm" href="/index.php">返回首页</a>';
        echo '</div>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

// 想拿当前登录用户的完整信息就用这个，内部自己去 users 表查一次。
// 为了不每次都查数据库，这里顺手丢到 session 里做个简易缓存。
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    if (isset($_SESSION['_current_user']) && is_array($_SESSION['_current_user'])) {
        return $_SESSION['_current_user'];
    }
    $db = Database::getInstance();
    if (!$db->isAvailable()) {
        return null;
    }
    $u = $db->queryOne("SELECT * FROM users WHERE id = :id LIMIT 1", ['id' => currentUserId()]);
    if (!$u) {
        return null;
    }
    $_SESSION['_current_user'] = $u;
    return $u;
}

// 有些页面（比如改个人资料）更新了 users 表，需要把刚才的缓存干掉，就调一下这个。
function refreshCurrentUserCache(): void
{
    unset($_SESSION['_current_user']);
}

// 顶部那种“xxx 成功 / 失败”的提示就是用这个塞进 session 的。
// type 简单当成 success / error / warning / info 就行。
function flash(string $type, string $message): void
{
    startSession();
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

// header 里会统一把这些 flash 信息读出来显示一下，然后就清空，属于一次性的。
function consumeFlashes(): array
{
    startSession();
    $list = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($list) ? $list : [];
}

// 这里简单生成一个 token 丢进 session，用来防止表单被乱 POST。
// 模板里直接用 csrfToken() 渲染个隐藏字段就完事了。
function csrfToken(): string
{
    startSession();
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }
    return (string)$_SESSION['_csrf'];
}

// 表单处理页一进来就调一下这个：
// 如果是 POST，就顺带帮你把 _csrf 校验掉，不对的话直接给个错误提示页面。
function verifyCsrfOrDie(): void
{
    if (!isPost()) {
        return;
    }
    $token = (string)($_POST['_csrf'] ?? '');
    if ($token === '' || !hash_equals((string)($_SESSION['_csrf'] ?? ''), $token)) {
        http_response_code(400);
        include __DIR__ . '/header.php';
        echo '<div class="container" style="max-width: 760px;">';
        echo '<h2 class="mb-3 mt-3">请求无效</h2>';
        echo '<div class="alert alert-danger small">安全校验失败，请刷新页面后重试。</div>';
        echo '<a class="btn btn-outline-secondary btn-sm" href="' . h(backUrl()) . '">返回</a>';
        echo '</div>';
        include __DIR__ . '/footer.php';
        exit;
    }
}

// 统一算分页用的，不想每个列表页都自己手搓 total / pages / offset。
function paginate(int $total, int $page, int $pageSize): array
{
    $pageSize = max(1, min(100, $pageSize));
    $pages = max(1, (int)ceil($total / $pageSize));
    $page = max(1, min($pages, $page));
    $offset = ($page - 1) * $pageSize;
    return [
        'total' => $total,
        'page' => $page,
        'pageSize' => $pageSize,
        'pages' => $pages,
        'offset' => $offset,
    ];
}

// 小工具：在当前链接的 query 上做一点小改动（比如只改 page / sort），然后返回新的 URL。
// 做分页按钮、筛选条件那一堆链接的时候会挺好用。
function buildUrlWithQuery(array $query): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $current = [];
    parse_str(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?: '', $current);
    $merged = array_merge($current, $query);
    foreach ($merged as $k => $v) {
        if ($v === null || $v === '') {
            unset($merged[$k]);
        }
    }
    $qs = http_build_query($merged);
    return $path . ($qs ? ('?' . $qs) : '');
}

