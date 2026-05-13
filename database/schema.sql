-- ============================================================
-- Jwell Project – MySQL Schema
-- Run this once to create the database and all tables
-- ============================================================

CREATE DATABASE IF NOT EXISTS `jwelleryshop`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `jwelleryshop`;

-- ─────────────────────────────────────────────
-- admins
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admins` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `FirstName`  VARCHAR(50)  NOT NULL,
  `LastName`   VARCHAR(50)  NOT NULL,
  `Email`      VARCHAR(100) NOT NULL UNIQUE,
  `Password`   VARCHAR(255) NOT NULL,
  `Role`       VARCHAR(20)  NOT NULL DEFAULT 'admin',
  `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- customers
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `customers` (
  `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `customerId`            VARCHAR(30)  NOT NULL UNIQUE,
  `name`                  VARCHAR(100) NOT NULL,
  `phone_number`          VARCHAR(20)  NOT NULL UNIQUE,
  `aadhar_number`         VARCHAR(20)  NOT NULL,
  `address`               TEXT         NOT NULL,
  `customerImage`         VARCHAR(255) DEFAULT NULL,
  `currentAvailableValue` TINYINT      NOT NULL DEFAULT 0,
  `created_at`            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- customer_items  (replaces embedded "items" array)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `customer_items` (
  `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `customer_id`           INT UNSIGNED NOT NULL,
  `code`                  VARCHAR(30)  NOT NULL,
  `item`                  ENUM(
    'Chain','Dollar Chain','Earring','Ring','Ear Matti','Dollar',
    'Necklace','Bracelet','Stone Earring','Titanic Earring',
    'Baby Ring','Mookuthi'
  ) NOT NULL,
  `weight`                DECIMAL(10,3) NOT NULL,
  `pricePerWeight`        DECIMAL(12,2) NOT NULL,
  `loanAmount`            DECIMAL(12,2) NOT NULL,
  `currentLoanAmount`     DECIMAL(12,2) NOT NULL,
  `interest`              DECIMAL(5,2)  NOT NULL,
  `currentAvailableValue` TINYINT       NOT NULL DEFAULT 0,
  `created_at`            TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_customer_code` (`customer_id`, `code`)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- interest_details
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `interest_details` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `customerId`      VARCHAR(30)   NOT NULL,
  `itemCode`        VARCHAR(30)   NOT NULL,
  `month`           TINYINT       NOT NULL,
  `year`            SMALLINT      NOT NULL,
  `paidDate`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `monthlyInterest` DECIMAL(12,2) NOT NULL,
  `paidAmount`      DECIMAL(12,2) NOT NULL,
  `created_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_interest_customer` (`customerId`),
  INDEX `idx_interest_item`     (`itemCode`)
) ENGINE=InnoDB;
