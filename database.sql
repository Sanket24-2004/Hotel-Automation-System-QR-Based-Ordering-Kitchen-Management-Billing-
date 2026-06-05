-- ═══════════════════════════════════════════════════════════════
-- GOLDEN STONE HOTEL — Database Schema
-- Version: 1.0
-- Engine: MySQL 8.0+ / MariaDB 10.5+
-- Charset: utf8mb4 (full Unicode support for Hindi/Marathi)
-- ═══════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS `golden_stone_hotel`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `golden_stone_hotel`;

-- ───────────────────────────────────────────────────────────────
-- TABLE 1: menu_items
-- Master menu catalog. Each item has an explicit category.
-- Future: add branch_id, is_available, stock_qty for inventory.
-- ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id`            INT UNSIGNED        PRIMARY KEY AUTO_INCREMENT,
  `item_code`     VARCHAR(20)         NOT NULL UNIQUE COMMENT 'Internal code e.g. STR-001',
  `name_en`       VARCHAR(150)        NOT NULL COMMENT 'English name',
  `name_hi`       VARCHAR(150)        DEFAULT NULL COMMENT 'Hindi name',
  `name_mr`       VARCHAR(150)        DEFAULT NULL COMMENT 'Marathi name',
  `category`      ENUM(
                    'starter',
                    'main_course',
                    'bread',
                    'rice_biryani',
                    'beverage',
                    'dessert',
                    'salad',
                    'side_dish',
                    'water'
                  )                   NOT NULL,
  `price`         DECIMAL(8,2)        NOT NULL DEFAULT 0.00,
  `image_path`    VARCHAR(255)        DEFAULT NULL COMMENT 'Relative path to item image',
  `is_available`  TINYINT(1)          NOT NULL DEFAULT 1 COMMENT '1=available, 0=out of stock',
  `is_veg`        TINYINT(1)          NOT NULL DEFAULT 1 COMMENT '1=veg, 0=non-veg',
  `prep_time_min` SMALLINT UNSIGNED   NOT NULL DEFAULT 5 COMMENT 'Estimated preparation time in minutes',
  `sort_order`    SMALLINT UNSIGNED   NOT NULL DEFAULT 0 COMMENT 'Display order within category',
  `created_at`    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_category`     (`category`),
  INDEX `idx_available`    (`is_available`),
  INDEX `idx_sort`         (`category`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ───────────────────────────────────────────────────────────────
-- TABLE 2: orders
-- One row per table session. Supports incremental additions.
-- customer_notes stores free-text notes like "Less Spicy, No Onion".
-- Future: add branch_id, payment_method, discount_amount, waiter_id.
-- ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `orders` (
  `id`              INT UNSIGNED      PRIMARY KEY AUTO_INCREMENT,
  `order_ref`       VARCHAR(30)       NOT NULL UNIQUE COMMENT 'Human-readable ref e.g. ORD-260603-T5-001',
  `table_no`        VARCHAR(10)       NOT NULL,
  `persons`         TINYINT UNSIGNED  NOT NULL DEFAULT 1,
  `status`          ENUM(
                      'new',
                      'preparing',
                      'ready',
                      'served'
                    )                 NOT NULL DEFAULT 'new',
  `customer_notes`  TEXT              DEFAULT NULL COMMENT 'Free-text: Less Spicy, No Onion, Extra Butter, etc.',
  `subtotal`        DECIMAL(10,2)     NOT NULL DEFAULT 0.00,
  `gst_percent`     DECIMAL(4,2)      NOT NULL DEFAULT 5.00 COMMENT 'GST rate applied',
  `gst_amount`      DECIMAL(10,2)     NOT NULL DEFAULT 0.00,
  `total_amount`    DECIMAL(10,2)     NOT NULL DEFAULT 0.00,
  `prep_started_at` DATETIME          DEFAULT NULL,
  `ready_at`        DATETIME          DEFAULT NULL,
  `served_at`       DATETIME          DEFAULT NULL,
  `created_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX `idx_status`       (`status`),
  INDEX `idx_table_no`     (`table_no`),
  INDEX `idx_created_at`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ───────────────────────────────────────────────────────────────
-- TABLE 3: order_items
-- Each item line per order batch.
-- batch_id groups items added in a single "Confirm Order" click.
-- is_new = 1 until kitchen dashboard acknowledges the item.
-- Future: add special_instructions per item, discount per item.
-- ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`            INT UNSIGNED        PRIMARY KEY AUTO_INCREMENT,
  `order_id`      INT UNSIGNED        NOT NULL,
  `batch_id`      VARCHAR(50)         NOT NULL COMMENT 'UUID per Confirm Order click',
  `menu_item_id`  INT UNSIGNED        NOT NULL,
  `item_name`     VARCHAR(150)        NOT NULL COMMENT 'Snapshot of item name at order time',
  `category`      ENUM(
                    'starter',
                    'main_course',
                    'bread',
                    'rice_biryani',
                    'beverage',
                    'dessert',
                    'salad',
                    'side_dish',
                    'water'
                  )                   NOT NULL,
  `unit_price`    DECIMAL(8,2)        NOT NULL,
  `qty`           SMALLINT UNSIGNED   NOT NULL DEFAULT 1,
  `line_total`    DECIMAL(10,2)       GENERATED ALWAYS AS (`unit_price` * `qty`) STORED,
  `is_new`        TINYINT(1)          NOT NULL DEFAULT 1 COMMENT '1=new badge shown, 0=acknowledged',
  `added_at`      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT `fk_oi_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT `fk_oi_menu`
    FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,

  INDEX `idx_order_id`   (`order_id`),
  INDEX `idx_batch_id`   (`batch_id`),
  INDEX `idx_menu_item`  (`menu_item_id`),
  INDEX `idx_is_new`     (`is_new`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ───────────────────────────────────────────────────────────────
-- TABLE 4: order_status_history
-- Complete audit trail of every status change.
-- Supports the Order Timeline popup on the kitchen dashboard.
-- Future: add changed_by (waiter_id / system) for accountability.
-- ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `id`          INT UNSIGNED      PRIMARY KEY AUTO_INCREMENT,
  `order_id`    INT UNSIGNED      NOT NULL,
  `status`      ENUM(
                  'new',
                  'preparing',
                  'ready',
                  'served'
                )                 NOT NULL,
  `note`        VARCHAR(255)      DEFAULT NULL COMMENT 'Optional context e.g. Order received, Delayed due to rush',
  `changed_at`  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT `fk_osh_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,

  INDEX `idx_order_id`   (`order_id`),
  INDEX `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ═══════════════════════════════════════════════════════════════
-- NOTES FOR FUTURE SCALABILITY
-- ═══════════════════════════════════════════════════════════════
--
-- Owner Dashboard / Daily Revenue:
--   SELECT DATE(created_at) AS day, SUM(total_amount) AS revenue
--   FROM orders WHERE status = 'served' GROUP BY day;
--
-- GST Reports:
--   SELECT DATE(created_at) AS day, SUM(gst_amount) AS gst_collected
--   FROM orders WHERE status = 'served' GROUP BY day;
--
-- Top Selling Items:
--   SELECT item_name, SUM(qty) AS total_sold
--   FROM order_items oi JOIN orders o ON oi.order_id = o.id
--   WHERE o.status = 'served' GROUP BY item_name ORDER BY total_sold DESC;
--
-- Multi-Branch:
--   ALTER TABLE menu_items ADD COLUMN branch_id INT UNSIGNED DEFAULT 1;
--   ALTER TABLE orders ADD COLUMN branch_id INT UNSIGNED DEFAULT 1;
--
-- Inventory:
--   ALTER TABLE menu_items ADD COLUMN stock_qty INT DEFAULT -1; -- -1 = unlimited
--
-- ═══════════════════════════════════════════════════════════════
