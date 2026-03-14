## 1. 项目概述
iCampus 是一个校园一站式综合服务平台（毕业设计），包含二手交易、失物招领、活动、论坛、校内超市与后台管理等模块。当前版本为 **PHP + MySQL**（PHP 页面直连数据库，Session 维持登录态）。

## 2. 技术栈
- **后端**：PHP 7.4+ / 8.x（原生 PHP 页面）
- **数据库**：MySQL 5.7+（字符集 `utf8mb4`）
- **前端**：原生 HTML/CSS/JS（`js/utils.js` 仅用于导航高亮与全局搜索等轻交互）

## 3. 项目目录结构（当前实际）
```
iCampus/
├── index.php
├── login.php / register.php / logout.php / profile.php
├── config/
│   └── database.php                # PDO 单例 + 查询封装
├── includes/
│   ├── functions.php               # h()/redirect()/paginate()/鉴权等
│   ├── upload.php                  # 图片上传（≤2MB，jpg/png/gif/webp）
│   ├── header.php / footer.php
├── products/                       # 二手
│   ├── list.php / detail.php / publish.php / my.php
├── lostfound/                      # 失物招领
│   ├── list.php / detail.php / publish.php
├── activities/                     # 活动
│   ├── list.php / detail.php / publish.php / my.php
├── forum/                          # 论坛
│   ├── index.php / post.php / new.php
├── shop/                           # 超市
│   ├── index.php / cart.php / checkout.php / orders.php
├── admin/                          # 后台（需管理员）
│   ├── index.php / users.php / products.php / lostfound.php / activities.php / posts.php / orders.php
├── uploads/                        # 上传目录（按模块子目录分类）
├── css/style.css
└── js/utils.js
```

## 4. 数据库
- **数据库名**：`icampus`
- **表结构**：见 `icampus.sql`（包含 `users`, `products`, `favorites`, `lost_found`, `activities`, `signups`, `forum_sections`, `posts`, `comments`, `shop_products`, `cart`, `orders`, `order_items`）。

### 4.1 管理员账号
`users.role = 'admin'` 为管理员。可手动在 `users` 表插入/修改一条管理员账号用于登录后台。

## 5. 本地运行
1. 将 `icampus.sql` 导入 MySQL，并确保 `config/database.php` 的连接信息正确。
2. 在项目根目录启动 PHP 内置服务器：

```bash
php -S 127.0.0.1:8000 -t .
```

3. 访问 `http://127.0.0.1:8000/index.php`。

## 6. 部署到服务器（开发阶段建议）

### 6.1 环境要求
- PHP：7.4+ / 8.x
- MySQL：5.7+（utf8mb4）
- Web 服务器：Nginx / Apache（二选一）

### 6.2 数据库初始化
- 在 MySQL 中导入 `icampus.sql`
- 默认数据库名为 `icampus`

### 6.3 数据库连接配置（推荐用环境变量）
`config/database.php` 支持以下环境变量：
- `ICAMPUS_DB_HOST`
- `ICAMPUS_DB_NAME`
- `ICAMPUS_DB_USER`
- `ICAMPUS_DB_PASS`

如果不配置环境变量，会使用 `config/database.php` 文件内的默认值（建议你在服务器上改成自己的账号密码）。

### 6.4 uploads 目录权限
需要确保 Web 进程对 `uploads/` 有写权限，否则发布二手/失物等上传图片会失败：
- `uploads/`
  - `products/`
  - `lostfound/`

### 6.5 创建管理员账号
后台入口：`/admin/index.php`（必须管理员登录）

将某个用户设为管理员：
```sql
UPDATE users SET role='admin' WHERE username='你的用户名' LIMIT 1;
```

禁用/启用用户：
```sql
UPDATE users SET status=0 WHERE id=1;
UPDATE users SET status=1 WHERE id=1;
```

### 6.6 重要说明
- 本项目为毕业设计开发阶段，已启用基础安全策略：
  - 输出转义 `h()`
  - 表单 CSRF 校验（`_csrf`）
  - 写操作强制登录/管理员校验
- 如果服务器启用了缓存/CDN，发布图片后若不显示，优先检查 `uploads/` 路径是否可被 Web 访问。

