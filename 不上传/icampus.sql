-- ------------------------------------------------------
-- iCampus 数据库初始化脚本
-- 直接在 phpMyAdmin 导入本文件即可
-- MySQL 5.7+ / 字符集 utf8mb4 / InnoDB
-- ------------------------------------------------------

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `icampus`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `icampus`;

-- ------------------------------------------------------
-- users：用户表（普通用户 + 管理员）
-- ------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL COMMENT '登录用户名（学号/自定义）',
  `password` VARCHAR(100) NOT NULL COMMENT 'bcrypt 加密密码',
  `name` VARCHAR(50) DEFAULT NULL COMMENT '姓名',
  `student_id` VARCHAR(20) DEFAULT NULL COMMENT '学号',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
  `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像地址',
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user' COMMENT '角色',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态：1正常 0禁用',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_username` (`username`),
  KEY `idx_users_student_id` (`student_id`),
  KEY `idx_users_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

INSERT INTO `users` (`id`,`username`,`password`,`name`,`student_id`,`phone`,`avatar`,`role`,`status`,`created_at`) VALUES
(1,'admin','$2y$10$f8v1JtvzmjYwhciHMqAmeepXoIJchAInXtR/jXl3/pKUBtEoLSPsW','管理员',NULL,NULL,NULL,'admin',1,NOW()),
(2,'user1','$2y$10$0E15jFzDmjokwbd3bfW8c.Iil8u60/PCFCp4c6IiOs3IxrU0WHlj.','林同学','20260001','13800000001',NULL,'user',1,NOW()),
(3,'user2','$2y$10$JSdQmMFW6yI27Un4CMr9KeEpmsBMMfwoYmLtqdnJ.rmoUSzHTiFuO','周同学','20260002','13800000002',NULL,'user',1,NOW()),
(4,'user3','$2y$10$0r8r8WnGKW90EefgalmYsulaFQv9Hp1HyruIy45Ejf3EoRKcztl16','陈同学','20260003','13800000003',NULL,'user',1,NOW());

-- ------------------------------------------------------
-- products：二手商品表
-- ------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT '发布者用户ID',
  `title` VARCHAR(100) NOT NULL COMMENT '标题',
  `description` TEXT DEFAULT NULL COMMENT '描述',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '价格',
  `image_url` VARCHAR(255) DEFAULT NULL COMMENT '图片地址',
  `category` VARCHAR(50) DEFAULT NULL COMMENT '分类',
  `status` ENUM('on','off') NOT NULL DEFAULT 'on' COMMENT '状态：on上架 off下架',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_products_user_id` (`user_id`),
  KEY `idx_products_status` (`status`),
  CONSTRAINT `fk_products_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='二手商品';

-- ------------------------------------------------------
-- favorites：收藏表（用户收藏的二手商品）
-- ------------------------------------------------------
DROP TABLE IF EXISTS `favorites`;
CREATE TABLE `favorites` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_favorites_user_product` (`user_id`,`product_id`),
  KEY `idx_favorites_product_id` (`product_id`),
  CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_fav_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收藏表';

-- ------------------------------------------------------
-- lost_found：失物招领表
-- ------------------------------------------------------
DROP TABLE IF EXISTS `lost_found`;
CREATE TABLE `lost_found` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT '发布者',
  `title` VARCHAR(100) NOT NULL COMMENT '标题',
  `content` TEXT DEFAULT NULL COMMENT '详情',
  `type` ENUM('lost','found') NOT NULL COMMENT 'lost丢失 found捡到',
  `status` ENUM('open','closed') NOT NULL DEFAULT 'open' COMMENT '状态',
  `place` VARCHAR(100) DEFAULT NULL COMMENT '地点',
  `contact` VARCHAR(100) DEFAULT NULL COMMENT '联系方式',
  `happen_time` DATETIME DEFAULT NULL COMMENT '丢失/捡到时间',
  `image_url` VARCHAR(255) DEFAULT NULL COMMENT '图片',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lost_found_user_id` (`user_id`),
  KEY `idx_lost_found_type` (`type`),
  KEY `idx_lost_found_status` (`status`),
  CONSTRAINT `fk_lost_found_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='失物招领';

-- ------------------------------------------------------
-- activities：活动表
-- ------------------------------------------------------
DROP TABLE IF EXISTS `activities`;
CREATE TABLE `activities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(100) NOT NULL COMMENT '活动标题',
  `description` TEXT DEFAULT NULL COMMENT '活动说明',
  `location` VARCHAR(100) DEFAULT NULL COMMENT '地点',
  `start_time` DATETIME DEFAULT NULL COMMENT '开始时间',
  `end_time` DATETIME DEFAULT NULL COMMENT '结束时间',
  `signup_deadline` DATETIME DEFAULT NULL COMMENT '报名截止时间',
  `max_participants` INT UNSIGNED DEFAULT NULL COMMENT '人数上限，NULL表示不限',
  `status` ENUM('draft','published','closed') NOT NULL DEFAULT 'published' COMMENT '状态',
  `creator_id` BIGINT UNSIGNED NOT NULL COMMENT '发布者（通常是管理员）',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activities_status` (`status`),
  KEY `idx_activities_creator_id` (`creator_id`),
  CONSTRAINT `fk_activities_creator` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='活动';

-- ------------------------------------------------------
-- signups：活动报名表
-- ------------------------------------------------------
DROP TABLE IF EXISTS `signups`;
CREATE TABLE `signups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `activity_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending' COMMENT '状态',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_signups_activity_user` (`activity_id`,`user_id`),
  KEY `idx_signups_user_id` (`user_id`),
  CONSTRAINT `fk_signups_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_signups_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='活动报名';

-- ------------------------------------------------------
-- forum：帖子（信息流）相关
-- ------------------------------------------------------
-- posts：帖子表
-- ------------------------------------------------------
DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT '发帖人',
  `title` VARCHAR(100) NOT NULL COMMENT '标题',
  `content` TEXT NOT NULL COMMENT '内容',
  `image_url` VARCHAR(255) DEFAULT NULL COMMENT '配图（可选）',
  `views` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览量',
  `likes` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
  `is_top` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否置顶',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态：1正常 0删除/屏蔽',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_posts_user_id` (`user_id`),
  KEY `idx_posts_created_at` (`created_at`),
  KEY `idx_posts_hot` (`likes`,`views`,`created_at`),
  CONSTRAINT `fk_posts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='帖子';

-- post_images：帖子多图表（最多 9 张，按 sort_order 排序）
DROP TABLE IF EXISTS `post_images`;
CREATE TABLE `post_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post_images_post_id` (`post_id`),
  CONSTRAINT `fk_post_images_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='帖子图片';

INSERT INTO `posts` (`id`,`user_id`,`title`,`content`,`image_url`,`views`,`likes`,`is_top`,`status`,`created_at`) VALUES
(1,2,'今天食堂的糖醋里脊太顶了','有没有同学也觉得二食堂这周的糖醋里脊比上周好吃？我已经连续三天打卡了。',NULL,128,2,1,1,NOW() - INTERVAL 2 DAY),
(2,3,'求推荐：高数复习方法','期末快到了，高数总是学了就忘。大家有没有适合突击的复习路线？',NULL,86,1,0,1,NOW() - INTERVAL 1 DAY),
(3,4,'自习室占座现状','今天图书馆三楼又是满的，我 8 点到已经没位置了……有没有更稳的自习点？',NULL,64,0,0,1,NOW() - INTERVAL 20 HOUR),
(4,2,'晚霞真好看（随手拍）','刚刚操场的晚霞，真的绝了。（发帖支持配图后这里会显示图片）', NULL, 33, 1,0,1,NOW() - INTERVAL 6 HOUR),
(5,3,'你们都用什么记笔记 App？','想换一个能同步、好检索的笔记工具，求安利。',NULL,21,0,0,1,NOW() - INTERVAL 2 HOUR);

-- ------------------------------------------------------
-- post_likes：点赞去重表（按账号）
-- ------------------------------------------------------
DROP TABLE IF EXISTS `post_likes`;
CREATE TABLE `post_likes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_post_likes_post_user` (`post_id`,`user_id`),
  KEY `idx_post_likes_user_id` (`user_id`),
  CONSTRAINT `fk_post_likes_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_post_likes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='帖子点赞（按账号去重）';

INSERT INTO `post_likes` (`post_id`,`user_id`,`created_at`) VALUES
(1,3,NOW() - INTERVAL 1 DAY),
(1,4,NOW() - INTERVAL 12 HOUR),
(2,2,NOW() - INTERVAL 12 HOUR),
(4,3,NOW() - INTERVAL 1 HOUR);

-- ------------------------------------------------------
-- comments：评论表
-- ------------------------------------------------------
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `content` TEXT NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态：1正常 0删除/屏蔽',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comments_post_id` (`post_id`),
  KEY `idx_comments_user_id` (`user_id`),
  CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='帖子评论';

INSERT INTO `comments` (`post_id`,`user_id`,`content`,`status`,`created_at`) VALUES
(1,3,'同意！我也觉得这周的味道更正了。',1,NOW() - INTERVAL 30 HOUR),
(2,4,'建议先把例题吃透：课后题分类型刷一遍，最后再做真题。',1,NOW() - INTERVAL 18 HOUR),
(3,2,'可以试试教学楼空教室，或者体育馆旁边那栋晚上人少。',1,NOW() - INTERVAL 10 HOUR),
(4,4,'这张好看！操场那边确实很出片。',1,NOW() - INTERVAL 3 HOUR);

-- ------------------------------------------------------
-- shop_products：校内超市商品表
-- ------------------------------------------------------
DROP TABLE IF EXISTS `shop_products`;
CREATE TABLE `shop_products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT '商品名称',
  `description` TEXT DEFAULT NULL COMMENT '商品描述',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '价格',
  `stock` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '库存',
  `category` VARCHAR(50) DEFAULT NULL COMMENT '分类',
  `image_url` VARCHAR(255) DEFAULT NULL COMMENT '图片',
  `status` ENUM('on','off') NOT NULL DEFAULT 'on' COMMENT '状态',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_shop_products_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='超市商品';

-- ------------------------------------------------------
-- cart：购物车表
-- ------------------------------------------------------
DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'shop_products.id',
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cart_user_product` (`user_id`,`product_id`),
  KEY `idx_cart_product_id` (`product_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='购物车';

-- ------------------------------------------------------
-- orders：订单表
-- ------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` VARCHAR(32) NOT NULL COMMENT '订单号',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT '下单用户',
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '总金额',
  `status` ENUM('pending','paid','shipped','completed','cancelled') NOT NULL DEFAULT 'pending' COMMENT '订单状态',
  `address` VARCHAR(255) DEFAULT NULL COMMENT '收货地址',
  `remark` VARCHAR(255) DEFAULT NULL COMMENT '备注',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_orders_order_no` (`order_no`),
  KEY `idx_orders_user_id` (`user_id`),
  KEY `idx_orders_status` (`status`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单';

-- ------------------------------------------------------
-- order_items：订单明细表
-- ------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL COMMENT 'shop_products.id',
  `product_name` VARCHAR(100) NOT NULL COMMENT '下单时的商品名快照',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '单价快照',
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '小计',
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order_id` (`order_id`),
  KEY `idx_order_items_product_id` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `shop_products` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单明细';

SET FOREIGN_KEY_CHECKS = 1;

