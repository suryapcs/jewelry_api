-- Migration: Add Name and Phone columns to admins table
-- Run this on the live server (pcstech.in MySQL) and local MySQL
-- Safe to run multiple times (uses IF NOT EXISTS via IGNORE)

-- Step 1: Add Name column (combines FirstName + LastName)
ALTER TABLE `admins`
  ADD COLUMN IF NOT EXISTS `Name`  varchar(100) COLLATE utf8mb4_unicode_ci NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `Phone` varchar(20)  COLLATE utf8mb4_unicode_ci NULL AFTER `Email`,
  MODIFY COLUMN `FirstName` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  MODIFY COLUMN `LastName`  varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';

-- Step 2: Back-fill Name from existing FirstName + LastName
UPDATE `admins`
SET `Name` = TRIM(CONCAT(TRIM(`FirstName`), ' ', TRIM(`LastName`)))
WHERE `Name` IS NULL OR `Name` = '';

-- Step 3: Make Email nullable (because some admins may use Phone only)
ALTER TABLE `admins`
  MODIFY COLUMN `Email` varchar(100) COLLATE utf8mb4_unicode_ci NULL,
  DROP INDEX IF EXISTS `Email`,
  ADD UNIQUE KEY `uq_email` (`Email`),
  ADD UNIQUE KEY `uq_phone` (`Phone`);

-- Verify result
SELECT id, Name, Email, Phone, Role FROM admins;
