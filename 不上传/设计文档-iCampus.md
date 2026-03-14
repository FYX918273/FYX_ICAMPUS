## iCampus 开发设计文档（PHP 版本，使用公司官网视觉风格）

---

### 0. 项目定位、总体思路与约束

- **项目定位**：校园一站式服务平台（毕业设计），包含二手交易、失物招领、校园活动、论坛和校园超市等核心模块。
- **视觉要求**：整体视觉效果（导航样式、Banner、大背景图、卡片布局、阴影与排版）**参考公司官网 `Old/公司网站8.29`**，做到“看起来像那个站”，但**所有文案与业务内容都是 iCampus 的**。
- **技术栈**：LAMP（Linux + Apache/Nginx + PHP + MySQL）。
- **代码风格约束（非常重要）**：
  - 只使用**最基础的 PHP + PDO + 少量 JS** 实现功能；
  - 不写 SEO、OG、Twitter Card、微信分享等高级 Meta 配置；
  - 不搞复杂框架和过度封装，代码力求“直观、好读、好讲解”，适合毕业设计答辩；
  - 可以参考公司官网的写法，但**不强制复用其目录结构和所有工具函数**，只按本项目需要“挑简单的抄”。
- **数据库原则**：表结构以 `icampus.sql` 为准，核心范围内不再频繁改表，只在必要时通过新版设计文档扩展。
- **命名风格**：文件名、路由名、变量名、字段名全部使用**简单英文单词**，便于当场翻译，如 `list`, `detail`, `publish`, `cart`, `order`, `title`, `price`, `status` 等。
- 这是个毕业设计，不能用高级的做法，尽量用基础的方式实现，整个页面全中文，然后注释方面，要以一个学生到处学到处复制的感觉去做，页面代码最好也用最简单的，然后到处都是零散的注释写这段是干什么的那种感觉
- 前端静态资源（CSS/JS）这边也走“看一眼就懂”的路子，直接用固定路径，不再搞 `?v=时间戳` 之类的版本参数，方便在代码里一眼能定位到真实文件。

---

## 1. 目录与架构设计

### 1.1 运行环境

- Web 服务器：Nginx。
- PHP：7.4+ 或 8.x。
- MySQL：5.7+，字符集 `utf8mb4`。

### 1.2 项目目录规划（不强制跟公司站一致）

项目以 `icampus-app/` 作为 Web 根目录，采用**简单清晰的分层结构**：

- 根目录 `icampus-app/`
  - `index.php`：首页（iCampus 总入口）。
  - `login.php` / `register.php` / `logout.php` / `profile.php`：通用用户模块页面。
  - `config/`
    - `database.php`：PDO 单例类，连接 `icampus` 数据库，提供最基础的 `query` / `queryOne` / `execute` / `insert` 等方法。
    - `app.php`（可选）：站点名、基础 URL 等简单配置。
  - `includes/`
    - `functions.php`：通用函数（`h()`、`redirect()`、分页等），只保留和本项目直接相关的基础工具。
    - `auth.php`：登录状态与权限函数（`startSession()`、`isLoggedIn()`、`currentUser()`、`isAdmin()`、`requireLogin()`、`requireAdmin()`）。
    - `header.php` / `footer.php`：公共头部和底部，**HTML 结构和样式风格参考公司官网，但内容完全是 iCampus**。
    - `upload.php`：图片上传工具（限制类型、大小和保存目录）。
  - 业务模块目录：
    - `products/`：二手交易模块。
      - `list.php`, `detail.php`, `publish.php`, `my.php`。
    - `lostfound/`：失物招领模块。
      - `list.php`, `detail.php`, `publish.php`。
    - `activities/`：活动模块。
      - `list.php`, `detail.php`, `publish.php`, `my.php`。
    - `forum/`：论坛模块。
      - `index.php`, `post.php`, `new.php`。
    - `shop/`：校园超市模块。
      - `index.php`, `cart.php`, `checkout.php`, `orders.php`。
    - `admin/`：管理后台。
      - `index.php`, `users.php`, `products.php`, `lostfound.php`, `activities.php`, `posts.php`, `orders.php`。
  - 静态资源：
    - `css/`：项目自定义样式（核心为 `css/style.css`），负责导航、首页 Banner、卡片、移动端样式等视觉统一。
    - `js/`：项目自定义脚本（如 `js/utils.js`），仅做必要交互（导航高亮、少量 UI 交互等）。
    - `assets/`：第三方静态资源（**本地化存放，避免大陆网络访问外部 CDN 超时/403**）。
      - `assets/css/bootstrap.min.css`
      - `assets/js/bootstrap.bundle.min.js`
    - `favicon.ico`：站点图标，放在 Web 根目录，避免浏览器请求 `/favicon.ico` 返回 404。
  - `uploads/`
    - `products/`, `lostfound/`, `activities/`, `shop/` 等子目录，用于存放上传图片。

> 说明：公司官网的目录 `Old/公司网站8.29` 只作为**视觉和代码写法的参考仓库**，最终线上跑的是 `icampus-app/` 这一套结构。

---

## 2. 页面布局与 UI 设计

### 2.1 整体布局风格（“皮肤”来自公司官网）

- **整体骨架**：
  - 顶部固定导航栏 + 左侧 Logo / 站点名 + 右侧导航菜单；
  - 首页大 Banner（全屏背景图 + 左侧文字 + 两个按钮）；
  - 下方多块内容区域（卡片网格 + 标题 + 简短说明）；
  - 底部统一页脚。
- **视觉风格**：
  - 颜色、字体、卡片阴影等统一参考公司官网当前版本（主色 `#232323`、背景 `#f8f2ef` 等）；
  - CSS 命名风格尽量简单，基本按照“中文 → 英文直译”的方式命名，例如：
    - `home-banner`（首页横幅）、`home-card`（首页卡片）、`home-card-title`（卡片标题）、`item-list`（列表）、`item-row`（每行）等；
  - hover 效果、卡片放大、按钮样式等**直接套用**公司站的 CSS，只改类名对应关系（不做复杂 BEM 体系）；
  - 尽量使用已有组件：导航栏、Hero 区、卡片、Section 标题等。

### 2.2 首页布局（iCampus 内容）

- **Banner 区（Hero）**：
  - 标题：`iCampus 校园一站式服务平台`；
  - 副标题：一句概括 iCampus 功能的长句（例如“二手交易 · 失物招领 · 校园活动 · 论坛与校园超市，一站解决校园生活需求”）；
  - 主按钮：
    - 「查看校园活动」 → `activities/list.php`；
    - 「逛二手市场」 → `products/list.php`。
- **下方四个核心模块区块**（沿用“精选案例 + 设备展示”两段的布局）：
  - **最新校园活动**：以“精选案例”的卡片区域为模板，展示 `activities` 的若干记录；
  - **最新二手**：以另一个卡片区域展示 `products`；
  - **失物招领**：可用列表或卡片方式展示最近的 `lost_found` 记录；
  - **论坛热帖 / 校园超市推荐**：根据时间和精力选择其一做成卡片区。

### 2.3 列表页与详情页

- **列表页通用原则**：
  - 使用类似“案例列表”的卡片排布：每个卡片包含标题、关键字段（价格/时间/状态等）和一小段简介；
  - 顶部可选放一条简单筛选或搜索栏（如关键字输入框）。
- **详情页通用原则**：
  - 顶部：大标题 + 关键信息（时间、地点、价格、联系人等）；
  - 中间：主要内容（文字 + 图片）；
  - 底部：操作按钮（报名、收藏、留言等）。

### 2.4 Header / Footer 设计

- **Header**：
  - 左侧：站点名 `iCampus`（可配合简洁 Logo）；
  - 右侧导航：
    - 菜单：`首页`、`二手交易`、`失物招领`、`校园活动`、`校园论坛`、`校园超市`；
    - 未登录时显示「登录 / 注册」（注册使用细边框圆角按钮）；
    - 登录后右上角显示用户名，点击跳转个人中心，鼠标悬浮时出现下拉菜单（`个人中心` / `退出`），类名类似 `nav-user`、`nav-user-menu`。
  - 移动端：
    - 使用 Bootstrap 的 `navbar-collapse` 折叠菜单；
    - 展开时显示为“深色磨砂弹层面板”（圆角、描边、阴影），与导航主色 `#232323` 保持一致，避免出现突兀的蓝色大块背景。
- **Footer**：
  - 左侧为平台简介；
  - 中间为主要功能链接快速入口（与导航对应）；
  - 右侧为联系方式（学校、邮箱、备注说明）；
  - 最底部一行：`© 2026 iCampus 校园一站式服务平台 | 本系统为毕业设计，仅用于学习与演示 | 作者：范宇轩 3505210118`。

---

## 3. 功能模块设计

> 本节描述的是「业务功能 + 需要的页面」，数据库表结构以 `icampus.sql` 为准。

### 3.1 用户模块（users）

- **功能**：
  - 用户注册：用户名、密码、姓名、学号、手机号。
  - 用户登录/退出：Session 维持登录状态。
  - 个人中心：查看（可选编辑）个人基本信息，入口统一在导航栏“个人中心”。
- **主要页面**：
  - `register.php`：注册表单 + 处理逻辑（参考 `user/register.php` 重写）。
  - `login.php`：登录表单 + 处理逻辑（参考 `user/login.php`）。
  - `logout.php`：登出逻辑（参考 `user/logout.php`）。
  - `profile.php`：用户资料展示，可放在项目根或 `user/` 下，视路由统一情况而定。
- **权限**：
  - 所有写操作都要求登录。
  - 管理后台入口需要管理员角色。

### 3.2 二手交易模块（products, favorites）

- **功能**：
  - 二手商品浏览、搜索。
  - 商品详情查看。
  - 登录用户发布商品、查看“我的发布”、下架商品。
  - 可选实现收藏功能（favorites）。
- **页面**：
  - `products/list.php`：列表 + 分页 + 搜索。
  - `products/detail.php?id=`：详情页。
  - `products/publish.php`：发布表单，带图片上传。
  - `products/my.php`：我的发布管理。

### 3.3 失物招领模块（lost_found）

- **功能**：
  - 失物 / 招领信息发布与浏览。
  - 根据类型与状态筛选。
  - 发布者标记“已解决”。
- **页面**：
  - `lostfound/list.php`。
  - `lostfound/detail.php?id=`。
  - `lostfound/publish.php`（需登录）。

### 3.4 活动模块（activities, signups）

- **功能**：
  - 活动发布、浏览、报名与取消报名。
  - 报名人数与状态管理。
- **页面**：
  - `activities/list.php`。
  - `activities/detail.php?id=`。
  - `activities/publish.php`（管理员）。
  - `activities/my.php`（我的报名）。

### 3.5 论坛模块（forum_sections, posts, comments）

- **功能**：
  - 版块划分 + 帖子列表。
  - 帖子详情、评论、点赞（按时间允许可选）。
  - 管理员删帖 / 删评。
- **页面**：
  - `forum/index.php`。
  - `forum/post.php?id=`。
  - `forum/new.php`。

### 3.6 校园超市模块（shop_products, cart, orders, order_items）

- **功能**：
  - 校内商品展示。
  - 购物车与下单流程（模拟支付）。
  - 个人订单列表、订单状态流转。
- **页面**：
  - `shop/index.php`。
  - `shop/cart.php`。
  - `shop/checkout.php`。
  - `shop/orders.php`。

### 3.7 管理后台模块（admin）

- **功能**：
  - 管理员登录与退出。
  - 用户、二手、失物、活动、帖子、订单的基础管理。
- **页面**（可逐步从 `fyx/` 迁移重命名）：
  - `admin/index.php`：仪表盘。
  - `admin/users.php`。
  - `admin/products.php`。
  - `admin/lostfound.php`。
  - `admin/activities.php`。
  - `admin/posts.php`。
  - `admin/orders.php`。

---

## 4. 数据库表设计（仍以 `icampus.sql` 为最终版）

以下表结构来自 `icampus.sql`（与你之前设计一致），本次只调整**实现方式**，不调整表定义：

- `users`：用户表（学生 + 管理员）。
- `products`：二手商品。
- `favorites`：二手收藏（可选实现）。
- `lost_found`：失物招领。
- `activities`：活动。
- `signups`：活动报名。
- `forum_sections`：论坛版块。
- `posts`：帖子。
- `comments`：帖子评论。
- `shop_products`：超市商品。
- `cart`：购物车。
- `orders`：订单。
- `order_items`：订单明细。

> 后端只做数据读写与业务逻辑，不再频繁调整表结构，本次重构重点是“基于公司官网结构重新实现 PHP 页面”。

---

## 5. 权限与 Session 设计

- **角色**：
  - 普通用户：`users.role = 'user'`。
  - 管理员：`users.role = 'admin'`。
- **Session 字段**：
  - `$_SESSION['user_id']`。
  - `$_SESSION['user_role']`。
- **权限函数**：
  - 可以在 `config/functions.php` 或单独的 `includes/auth.php` 中实现：
    - `startSession()`。
    - `isLoggedIn()`。
    - `currentUser()`。
    - `isAdmin()`。
    - `requireLogin()`。
    - `requireAdmin()`。

---

## 6. 实现风格与安全策略

- **数据库访问**：统一通过 `Database::getInstance()`（从公司官网 `config/database.php` 精简改造而来）获取 PDO，然后提供简单封装方法。
- **模板输出**：所有用户可控内容在输出前使用 `htmlspecialchars` 包装（例如封装为 `h()`）。
- **密码安全**：使用 `password_hash()` + `password_verify()`。
- **表单处理模式**：沿用公司官网风格，同一 PHP 文件根据 `$_SERVER['REQUEST_METHOD']` 区分 GET / POST。
- **文件上传**：
  - 统一存入 `uploads/模块名/` 目录。
  - 限制为图片类型，限制大小（如 2MB）。
- **命名规则**：
  - 文件与字段优先使用短单词，如 `list`, `detail`, `publish`, `status`, `type`, `title`, `content`, `price`, `time`, `place`, `total`。

---

## 7. 开发顺序（基于公司官网的执行计划）

1. **迁移基础工程**
  - 将 `Old/公司网站8.29` 完整复制到项目根目录。
  - 调整 `config/database.php`，连接到本地 `icampus` 数据库。
  - 确保首页、静态资源、后台登录在原业务逻辑下能正常运行（此时仍是“公司官网”业务）。
2. **导航与基础文案校园化**
  - 修改 `includes/header.php` / `includes/footer.php`：
    - 替换站点名称、导航菜单文字与链接路径为 iCampus 相关。
    - 页脚添加“毕业设计说明 + 作者信息”。
  - 首页 `index.php`：
    - Hero 文案与按钮指向 iCampus 核心模块。
3. **接入 iCampus 数据库表**
  - 导入 `icampus.sql` 到 MySQL，确认表结构正确。
  - 在首页和部分列表页中，替换原有查询语句为对 `activities`、`products`、`lost_found`、`posts` 等表的读取。
4. **按模块改造前台功能**
  - 先实现“只读版”：
    - 二手、失物、活动、论坛、超市的列表与详情页，只读展示数据库内容。
  - 再补充“写操作”：
    - 发布二手、发布失物、发帖、报名活动、购物车与下单等功能。
5. **改造后台（基于 `fyx/`）**
  - 将后台登录与菜单改造为 iCampus 管理后台。
  - 逐个页面把“案例/设备/预约”等对应到 iCampus 的用户、活动、二手、订单、帖子管理。
6. **样式统一与体验优化**
  - 全站检查：字体、颜色、间距、按钮样式统一。
  - 做适当的移动端适配（响应式布局）。
7. **撰写答辩用文档与截图**
  - 基于本设计文档，补充：
    - 关键页面截图。
    - 功能流程图（登录、发帖、下单、后台审核等）。
    - 中英对照术语表。

---

## 8. 当前实际进度与 TODO（基于本次“重开”）

> 由于你已经“把库直接删了”，以下进度描述的是**从公司官网代码重新起步**的状态。

### 8.1 当前状态

- 仓库中保留了：
  - `Old/公司网站8.29`：公司官网完整源码（前台 + 后台）。
  - `icampus.sql`：iCampus 目标数据库脚本。
- 新的 PHP 版本 iCampus 还**没有重新搭建**，本设计文档是后续开发的路线图。

### 8.2 下一步开发 TODO（建议执行顺序）

1. **复制公司官网代码到根目录**
  - 从 `Old/公司网站8.29` 拷贝到项目根（或设定为 Web 根目录）。
  - 检查首页是否能在本地环境跑起来（仍然是公司官网业务）。
2. **配置数据库与基础函数**
  - 修改 `config/database.php`，连接 `icampus` 数据库。
  - 整理 `config/functions.php`，加入 `h()`、`redirect()`、Session 启动、权限判断等函数。
3. **重命名与调整导航**
  - 更新 `includes/header.php` / `includes/footer.php` 文案为 iCampus。
  - 先保证首页 + 登录 / 注册 / 后台入口可用。
4. **逐个模块迁移业务**
  - 按顺序实现：
    - 用户模块。
    - 二手 + 失物。
    - 活动。
    - 论坛。
    - 校园超市。
  - 最后统一接上后台管理改造。
5. **整理最终展示材料**
  - 按毕业设计要求，输出：
    - 设计文档（本文件）。
    - 使用说明。
    - 数据库结构说明。
    - 系统截图与功能说明。

---

本设计文档是**“公司官网 → iCampus” 二次开发版本的蓝图**：在不推翻原有成熟工程结构的前提下，通过数据库切换 + 模块映射 + 文案与样式调整，完成对校园一站式服务平台的功能实现，并且便于你在答辩时清晰解释“代码来源、二次开发思路以及最终成品”。

## iCampus 开发设计文档（PHP 版本）

### 0. 项目定位、总体思路与个人偏好

- **项目定位**：校园一站式服务平台（毕业设计），包含二手交易、失物招领、活动、论坛和校园超市等模块。
- **技术栈**：LAMP（Linux + Apache/Nginx + PHP + MySQL），前端以现有 iCampus UI 为基础，整体布局和信息结构参考你之前的公司官网（`Old/公司网站8.29`），在此基础上做简化和优化。
- **后端风格**：不再使用 Node.js / 独立 API，改为「PHP 页面 + 直接连接 MySQL」，数据库访问采用公司官网中已经写好的 `Database` + PDO 单例模式（简洁、够用、不显得过于高级）。
- **数据库原则**：**一次设计到位**。本次文档中列出的表结构就是后续实现要用到的全部表结构（核心功能范围内不再改字段、不再新增表；扩展功能如果要新增表，会单独再拉一版设计）。
- **命名风格偏好**：所有文件名、路由名、变量名、字段名，除极少数标准术语外，全部使用**简单、直白、容易翻译的英文单词**（例如 `list`, `detail`, `publish`, `cart`, `order` 等），尽量避免复杂或学术化的英文词汇，方便在答辩时直接口头翻译。
- **布局偏好**：整体布局、板块结构尽量借鉴 `Old/公司网站8.29`（公司官网）的页面风格和排版方式，只在此基础上做轻量优化与校园化改造，这样既能体现复用经验，也便于解释设计来源。

---

## 1. 目录与架构设计

### 1.1 运行环境

- Web 服务器：Apache 或 Nginx（二选一）。
- PHP：7.4+ 或 8.x。
- MySQL：5.7+（字符集 `utf8mb4`）。
- [http://ic.tjxyy.top](http://ic.tjxyy.top)

### 1.2 项目目录规划（PHP 版本）

- 根目录：
  - `index.php`：首页。
  - `login.php` / `register.php` / `logout.php` / `profile.php`：通用用户模块。
  - `config/`
    - `database.php`：PDO 单例类（从 `Old/公司网站8.29/config/database.php` 精简改造，改为连接 `icampus` 库）。
    - `app.php`：全局配置（站点名、基础 URL、环境开关等，必要时添加）。
  - `includes/`
    - `functions.php`：通用函数（`h()`、`startSession()`、`paginate()`、简单工具函数等），会参考并合并公司官网的 `config/functions.php` 中适合本项目的部分。
    - `auth.php`：封装权限相关函数（`isLoggedIn()`、`currentUser()`、`isAdmin()`、`requireLogin()`、`requireAdmin()`）。
    - `header.php` / `footer.php`：公共头部和底部布局。
    - `upload.php`：简单上传工具函数（封装图片上传逻辑）。
  - 业务模块目录：
    - `products/`：二手交易模块。
      - `list.php`、`detail.php`、`publish.php`、`my.php`。
    - `lostfound/`：失物招领模块。
      - `list.php`、`detail.php`、`publish.php`。
    - `activities/`：活动模块。
      - `list.php`、`detail.php`、`publish.php`、`my.php`（报名记录）。
    - `forum/`：论坛模块。
      - `index.php`、`post.php`、`new.php`。
    - `shop/`：校内超市模块。
      - `index.php`、`cart.php`、`checkout.php`、`orders.php`。
    - `admin/`：管理后台。
      - `index.php`、`users.php`、`products.php`、`activities.php`、`posts.php`、`orders.php`（视时间精简）。
  - 静态资源：
    - `css/`：沿用现有 `style.css`，在此基础上微调。
    - `js/`：保留通用交互（如 `utils.js`），去掉强依赖 `/api` 的部分。
    - `uploads/`：用户上传图片目录（下设 `products/`、`lostfound/`、`activities/` 等）。

---

## 2. 页面布局与 UI 设计原则

### 2.1 整体布局风格

- **总体风格**：延续公司官网（`Old/公司网站8.29/index.php`）的布局结构：
  - 顶部导航栏 + Logo。
  - 大幅 Banner / Hero 区域（背景大图 + 标题 + 副标题 + 主按钮）。
  - 下方分区块内容（类似「精选案例」、「专业设备」、「联系我们」等）。
- **针对 iCampus 的调整**：
  - **首页**：
    - Hero 区域改为「校园一站式服务」主题，背景可以是校园照片或插画。
    - 下方区块改为 4 个核心模块：
      - 最新活动（cards 样式）。
      - 最新二手。
      - 失物招领。
      - 论坛热帖。
    - 区块布局沿用公司官网卡片式设计：带阴影、hover 提升、标题 + 简要描述。
  - **模块页面**：
    - 列表页：参考公司官网的「案例列表」样式，每行 / 每卡片结构统一：标题、时间/价格/状态等信息清晰排列。
    - 详情页：标题 + 关键信息（地点/时间/价格/联系人）置于上方，正文内容在下方，配合图片区域。

### 2.2 Header / Footer 设计

- **Header**：
  - 导航包含：首页、二手、失物、活动、论坛、超市、个人中心（未登录时显示登录/注册）。
  - 结构和响应式菜单参考公司官网 `includes/header.php`，但样式用 iCampus 的颜色方案。
- **Footer**：
  - 简单版权信息 + 学校/作者信息。
  - 可选：放置快速链接和联系方式。

---

## 3. 功能模块设计

下面模块设计对应数据库中的表，并指定所需页面。**这些模块会在当前毕业设计范围内全部实现**。

### 3.1 用户模块（users）

- **功能**：
  - 用户注册：用户名、密码、姓名、学号、手机号。
  - 用户登录/退出：Session 维持登录状态。
  - 个人中心：查看基本信息（可选修改）。
- **主要页面**：
  - `register.php`：
    - GET：显示注册表单。
    - POST：校验必填字段 → 检查用户名是否重复 → `password_hash` 加密 → 插入 `users` → 跳转 `login.php`。
  - `login.php`：
    - GET：显示登录表单。
    - POST：查询用户名 → `password_verify` → 登录成功写入 Session（`user_id`、`user_role`）→ 跳转首页。
  - `logout.php`：清空 Session 后跳转首页。
  - `profile.php`：显示当前登录用户的信息。
- **权限**：
  - 所有写操作（发二手、发失物、发帖、报名活动、下单）都要求 `requireLogin()`。
  - 管理后台要求 `requireAdmin()`。

### 3.2 二手交易模块（products, favorites）

- **功能**：
  - 浏览二手商品列表（支持关键词搜索）。
  - 商品详情查看。
  - 登录后发布商品。
  - 「我的发布」列表。
  - 商品状态管理：下架（`status='off'`）。
  - 收藏（favorites 表）可以作为可选加分项实现。
- **页面**：
  - `products/list.php`：
    - 支持 `kw` 查询参数。
    - 列出 `status='on'` 的商品，分页显示，每个商品展示：标题、价格、发布时间。
  - `products/detail.php?id=`：
    - 显示商品详情，包含标题、描述、价格、图片、发布时间、发布者信息（可选）。
  - `products/publish.php`（需登录）：
    - GET：表单（标题、描述、价格、分类、上传图片）。
    - POST：写入 `products` 表，图片通过 `uploads/products/` 保存路径。
  - `products/my.php`：
    - 列出当前用户发布的商品，提供下架按钮（更新 `status`）。

### 3.3 失物招领模块（lost_found）

- **功能**：
  - 发布失物/招领信息。
  - 按类型浏览列表（丢失 / 捡到）。
  - 详情中展示时间、地点和联系方式。
  - 发布者可将信息标记为已解决（`status='closed'`）。
- **页面**：
  - `lostfound/list.php`：
    - 支持 `type`（lost/found）和 `status` 筛选。
  - `lostfound/detail.php?id=`：
    - 显示单条信息详情。
  - `lostfound/publish.php`（需登录）：
    - GET：表单（标题、类型、地点、时间、详情、联系方式、图片）。
    - POST：插入 `lost_found`。

### 3.4 活动模块（activities, signups）

- **功能**：
  - 浏览活动列表。
  - 活动详情页展示时间、地点、人数限制、状态。
  - 登录用户可以报名/取消报名。
  - 管理员可以发布/关闭活动。
- **页面**：
  - `activities/list.php`：
    - 按 `status`（已发布）显示活动列表。
  - `activities/detail.php?id=`：
    - 显示活动详情 + 当前报名人数 / 上限。
    - 登录状态下显示「报名/取消报名」按钮。
  - `activities/publish.php`（管理员）：
    - 管理员发布或编辑活动。
  - `activities/my.php`：
    - 显示当前用户报名过的活动列表。

### 3.5 论坛模块（forum_sections, posts, comments）

- **功能**：
  - 版块列表 + 各版块下的帖子列表。
  - 帖子详情 + 评论列表。
  - 登录用户发帖和评论。
  - 管理员删除帖子/评论。
- **页面**：
  - `forum/index.php`：
    - 左侧（或顶部）显示版块列表。
    - 选择版块后显示该版块的帖子列表（置顶在前）。
  - `forum/post.php?id=`：
    - 帖子详情 + 评论列表。
    - 底部评论表单（登录后可用）。
  - `forum/new.php`：
    - 选择版块 + 输入标题内容，发新帖。

### 3.6 校园超市模块（shop_products, cart, orders, order_items）

- **功能**：
  - 浏览校内超市商品。
  - 加入购物车。
  - 提交订单（模拟下单流程，无真实支付）。
  - 查看个人订单列表与详情。
- **页面**：
  - `shop/index.php`：商品列表（支持分类和搜索）。
  - `shop/cart.php`：购物车页面（增减数量、删除条目）。
  - `shop/checkout.php`：
    - GET：确认订单信息。
    - POST：生成 `orders` + `order_items`，清空 `cart`。
  - `shop/orders.php`：当前登录用户的订单列表 + 状态。

### 3.7 管理后台模块（admin）

- **功能（最小集合）**：
  - 管理员登录后访问后台首页。
  - 用户管理：列表 + 禁用用户（修改 `status`）。
  - 二手/失物/活动/帖子/超市商品的简单列表与删除/关闭功能。
- **页面（按时间可适当精简）**：
  - `admin/index.php`：仪表盘，快速统计。
  - `admin/users.php`：用户列表 + 状态切换。
  - `admin/products.php`：二手商品列表 + 管理。
  - `admin/activities.php`：活动列表 + 状态管理。
  - `admin/posts.php`：帖子列表 + 删除。
  - `admin/orders.php`：订单列表（只读或可修改状态）。

---

## 4. 数据库表设计（最终版）

以下为项目**核心范围内实际会使用到的所有表**，全部来自当前 `icampus.sql`，不再新增/修改结构（除非以后扩展新大模块，届时会单独出新版设计文档）。

- `users`：用户表（学生 + 管理员）。
- `products`：二手商品。
- `favorites`：二手收藏（如时间紧张可不实现收藏功能，但表结构保留）。
- `lost_found`：失物招领。
- `activities`：活动。
- `signups`：活动报名。
- `forum_sections`：论坛版块。
- `posts`：帖子。
- `comments`：帖子评论。
- `shop_products`：超市商品。
- `cart`：购物车。
- `orders`：订单。
- `order_items`：订单明细。

> 说明：以上表结构已经在 `icampus.sql` 中完整定义（含外键约束和索引），后续实现时只做数据读写，不再对表结构做变动。

---

## 5. 权限与 Session 设计

- **角色**：
  - 普通用户：`users.role = 'user'`。
  - 管理员：`users.role = 'admin'`。
- **Session 字段**：
  - `$_SESSION['user_id']`：当前登录用户 ID。
  - `$_SESSION['user_role']`：角色（user/admin）。
- **权限函数（在 `includes/auth.php` 或 `includes/functions.php`）**：
  - `startSession()`：封装 `session_start()`，防止重复调用报错。
  - `isLoggedIn()`：是否已登录。
  - `currentUser()`：返回当前用户完整记录（从 `users` 读取一次后缓存）。
  - `isAdmin()`：是否管理员。
  - `requireLogin()`：未登录则 `redirect('login.php')`。
  - `requireAdmin()`：非管理员则跳转或提示无权限。

---

## 6. 实现风格与安全策略

- **数据库访问**：统一使用 `Database::getInstance()` 获取 PDO 连接，通过 `query` / `queryOne` / `insert` / `execute` 等方法操作数据库。
- **模板输出**：所有用户可控内容输出前使用 `h()` 进行 `htmlspecialchars` 处理，防止 XSS。
- **密码安全**：注册时用 `password_hash()`，登录时用 `password_verify()`。
- **表单处理模式**：同一 PHP 文件内区分 GET / POST，逻辑清晰易懂，不搞复杂路由。
- **文件上传**：单独封装上传函数，限制类型（图片）、限制大小（例如 2MB），保存到 `uploads/模块名/` 目录。
- **命名规则**：所有新加文件、函数、字段一律优先使用简单单词或短语，例如：
  - 页面：`list.php`, `detail.php`, `publish.php`, `my.php`, `cart.php`, `orders.php`。
  - 字段：`title`, `content`, `price`, `name`, `time`, `place`, `status`, `type`, `number`, `total`。
  - 避免使用难以直接中文翻译的复杂词（例如 `repository`, `orchestrator` 等）。

---

## 7. 开发顺序（执行计划）

1. **基础架构搭建**
  - 创建 `config/database.php`、`includes/functions.php`、`includes/header.php`、`includes/footer.php`。
  - 把 `index.html` 改为 `index.php`，通过 PHP 模板引入头尾。
2. **用户模块**
  - 实现注册/登录/退出/个人中心，打通 Session 与 `users` 表。
3. **只读功能迁移**
  - 首页四个「最新」区块由 PHP 直连数据库渲染。
  - 二手、失物、活动、论坛的列表和详情页全部改为 PHP 只读版本。
4. **写操作模块**
  - 发布二手、发布失物、发帖、活动发布和报名、超市下单等。
5. **管理后台**
  - 完成最小可用的后台页面。
6. **样式微调与文档整理**
  - 根据公司官网布局，对 iCampus 界面进行统一视觉调整。
  - 补充截图和说明，形成毕业设计文档的一部分。

---

## 8. 当前实际进度与待办列表（截至最近一次开发）

> 说明：本节用于记录实际完成情况和之后要做的事情，方便换账号或换助理时快速接力。

### 8.1 已完成功能与文件（截至 2026-03-11）

- **基础架构**
  - `config/database.php`：基于 PDO 的 `Database` 单例类，连接 `icampus` 数据库，封装 `query` / `queryOne` / `insert` / `execute` / `count` 等方法。
  - `includes/functions.php`：通用函数：
    - 会话与权限：`startSession()`（自动启动 Session）、`isLoggedIn()`, `currentUser()`, `isAdmin()`, `requireLogin()`, `requireAdmin()`。
    - 工具：`h()`（安全输出）、`redirect()`、`paginate()`。
  - `includes/upload.php`：图片上传工具，限制图片类型与大小（默认 2MB），支持按模块子目录保存到 `uploads/模块名/`。
  - `includes/header.php` / `includes/footer.php`：
    - 统一头部与底部布局，顶部导航根据 `currentUser()` 显示登录状态和后台入口。
    - 页脚展示作者信息：`© 2026 Designed by 范宇轩3505210118`。
    - 前端资源加载为“可在中国大陆稳定访问”的方式：Bootstrap 改为本地 `assets/` 引用，避免外部 CDN 超时/403。
  - `js/utils.js`：
    - 保留 `$`, `$all`, `setActiveNav`, `bindGlobalSearch`, `toast` 等基础工具。
    - `mountLayout()` 只做导航高亮和全局搜索绑定。
  - `js/author-banner.js`：
    - 页面加载时在浏览器 Console 输出彩色渐变大字 `FYX`。
    - 输出英文作者标识：`Designed by FanYuXuan 3505210118 | Graduation Project 2026`。
    - 通过 Base64（拆段存储）隐藏同一条作者签名，再用 `atob()` 解码并输出，作为“水印”式签名。
- **首页**
  - `index.php`：
    - 使用 `includes/header.php` / `includes/footer.php` 包裹整体布局。
    - 通过 `Database::getInstance()` 直接从数据库读取并渲染 4 个最新区块：
      - `activities`：`title`, `location`, `start_time`。
      - `products`：`title`, `price`, `created_at`。
      - `lost_found`：`title`, `type`, `status`。
      - `posts`：`title`, `created_at`（只取 `status = 1`，按 `is_top` + 时间排序）。
- **用户模块（users）**
  - `register.php`：注册表单 + 处理逻辑，支持用户名、密码（两次）、姓名、学号、手机；检查用户名唯一；使用 `password_hash()` 存储密码，默认角色 `user`，`status = 1`。
  - `login.php`：根据用户名从 `users` 查库，用 `password_verify()` 校验；成功后写入 `$_SESSION['user_id']` 与 `$_SESSION['user_role']`。
  - `logout.php`：清空 Session 与 Session cookie，跳转登录页。
  - `profile.php`：登录后查看当前用户基本信息（用户名、姓名、学号、手机号、角色等）。
- **二手模块（products, favorites）**
  - 目录 `products/` 已创建：
    - `products/list.php`：支持 `kw` 搜索；分页展示 `status='on'` 的商品；列表展示标题、价格、发布时间、可选封面图。
    - `products/detail.php`：根据 `id` 展示详情；展示标题、描述、价格、状态、发布时间、发布者信息和商品图片；登录用户可进行收藏/取消收藏（`favorites` 表，使用 `INSERT IGNORE`）。
    - `products/publish.php`：仅登录用户可访问；表单 + 处理逻辑，写入 `products`，支持图片上传到 `uploads/products/`。
    - `products/my.php`：当前用户发布列表；支持将商品状态修改为 `off`（下架）。
- **失物招领模块（lost_found）**
  - 目录 `lostfound/` 已创建：
    - `lostfound/list.php`：支持按 `type`（lost/found）、`status`、关键词筛选；分页展示最新记录。
    - `lostfound/detail.php`：详情页展示标题、类型、状态、时间、地点、联系方式、图片等；发布者或管理员可以将状态标记为 `closed`。
    - `lostfound/publish.php`：仅登录用户可访问；表单 + 处理逻辑，写入 `lost_found`，支持图片上传到 `uploads/lostfound/`。
- **活动模块（activities, signups）**
  - 目录 `activities/` 已创建：
    - `activities/list.php`：展示 `status='published'` 的活动列表，支持关键词搜索与分页。
    - `activities/detail.php`：详情页展示时间、地点、人数上限、当前报名人数、状态等；登录用户可以报名/取消报名（`signups` 表），后端会校验报名截止时间与人数上限。
    - `activities/publish.php`：仅管理员可访问；支持新建与编辑活动（标题、时间、地点、人数上限、报名截止时间、状态等）。
    - `activities/my.php`：当前登录用户报名过的活动列表。
- **论坛模块（forum_sections, posts, comments）**
  - 目录 `forum/` 已创建：
    - `forum/index.php`：左侧显示版块列表（`forum_sections`），右侧根据选中版块展示该版块的帖子列表（置顶优先，按时间排序）。
    - `forum/post.php`：帖子详情页；展示帖子内容、作者、版块、浏览量、点赞数；支持点赞；展示评论列表；登录用户可发表评论；管理员可以删除帖子和评论（`status` 置 0）。
    - `forum/new.php`：发帖页面；登录用户选择版块，填写标题与内容，写入 `posts` 表。
- **校园超市模块（shop_products, cart, orders, order_items）**
  - 目录 `shop/` 已创建：
    - `shop/index.php`：校内超市商品列表；支持关键词搜索；登录用户可“一键加购”到 `cart` 表。
    - `shop/cart.php`：购物车页面；展示当前用户购物车条目，可对数量进行 +1/-1 操作或删除条目；显示合计金额。
    - `shop/checkout.php`：模拟下单流程；在事务中校验库存并扣减 `shop_products.stock`，创建 `orders` + `order_items` 记录，并清空当前用户 `cart`。
    - `shop/orders.php`：当前用户订单列表；展示金额、状态、时间、收货地址和备注；支持根据状态取消订单或确认收货。
- **管理后台模块（admin）**
  - 目录 `admin/` 已创建，所有页面均要求 `requireAdmin()`：
    - `admin/index.php`：仪表盘；统计用户数、二手商品数、活动数、订单数，并提供各管理子模块入口。
    - `admin/users.php`：用户管理；支持按用户名/姓名/学号搜索；可对非管理员用户进行禁用/启用和删除操作。
    - `admin/products.php`：二手商品管理；展示商品基本信息，支持上下架与删除。
    - `admin/lostfound.php`：失物招领管理；支持将记录标记为 `closed` 或直接删除。
    - `admin/activities.php`：活动管理；支持查看活动列表、调整活动状态（`draft` / `published` / `closed`）、删除活动，并可跳转到前台详情与编辑页面。
    - `admin/posts.php`：帖子管理；显示帖子列表（含所属版块与作者），支持删除（软删除 `status=0`）。
    - `admin/orders.php`：订单管理；展示订单金额、状态、用户信息等；支持修改订单状态（`pending` / `paid` / `shipped` / `completed` / `cancelled`）。
- **旧实现清理**
  - 已删除原前后端分离版本中的所有 `.html` 模板页面与 `js/api.js`，避免继续依赖 `/api` 路由。
  - 已移除旧的 Node.js 后端目录 `backend/`（包含 `.env` 与所有路由文件），确认当前项目仅保留 PHP + MySQL 实现。

### 8.2 后续可选优化与文档补充

> 以下内容为**可选加分项**或文档增强，当前核心功能已经全部实现。

1. **界面与交互细节优化**
  - 根据指导老师/答辩反馈，对部分列表和表单页面做视觉统一与微调（间距、对齐、空状态提示等）。
  - 对移动端访问体验做进一步优化（如列表卡片在小屏幕上的排布）。
2. **功能增强（可选）**
  - `favorites` 收藏功能在二手详情页完整打通，并在个人中心增加“我的收藏”入口。
  - 对论坛帖子增加简单的搜索或按版块筛选功能。
3. **监控与日志**
  - 为关键写操作（发帖、下单、发布活动等）增加简单操作日志（可写入单独表或文件），用于答辩展示“可追溯性”。
4. **最终文档与展示材料**
  - 在本设计文档中补充：
    - 各模块关键页面截图（首页、二手列表/详情、失物列表/详情、活动详情、论坛帖子、超市下单、后台仪表盘等）。
    - 关键流程图（登录流程、发帖流程、下单流程、后台审核流程等）。
    - 简短中英对照表（模块名、字段名及其含义），便于英文答辩时快速说明。
  - 总结一节专门说明“作者标识与防篡改设计”（Console Banner、Base64 签名、页脚署名），作为个人特色亮点。

---

本设计文档作为后续开发的「蓝图」，并且记录了当前阶段的**实际完成情况与 TODO 列表**，方便在更换账号或更换助理时，任何人都可以按此文档继续开发，而无需重新理解整体方案或数据库结构。