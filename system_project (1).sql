-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1:3386
-- 產生時間： 2026-04-10 16:31:45
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `system_project`
--

-- --------------------------------------------------------

--
-- 資料表結構 `accounts`
--

CREATE TABLE `accounts` (
  `u_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `accounts` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `r_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `accounts`
--

INSERT INTO `accounts` (`u_id`, `name`, `accounts`, `password`, `role_id`, `r_id`, `created_at`) VALUES
(1, '管理員', 'admin', '123', 1, NULL, '2026-04-10 14:31:39'),
(2, '店員', 'staff', '456', 2, 2, '2026-04-10 14:31:39'),
(3, '學生A', '411401020', '789', 3, NULL, '2026-04-10 14:31:39'),
(4, '學生B', '411401021', 'xyz', 3, NULL, '2026-04-10 14:31:39');

-- --------------------------------------------------------

--
-- 資料表結構 `permissions`
--

CREATE TABLE `permissions` (
  `role_id` int(11) NOT NULL,
  `role` varchar(20) NOT NULL,
  `p_record` tinyint(1) DEFAULT 0,
  `p_comment` tinyint(1) DEFAULT 0,
  `p_manage` tinyint(1) DEFAULT 0,
  `p_audit` tinyint(1) DEFAULT 0,
  `system_permissions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `permissions`
--

INSERT INTO `permissions` (`role_id`, `role`, `p_record`, `p_comment`, `p_manage`, `p_audit`, `system_permissions`) VALUES
(1, 'admin', 1, 1, 1, 1, '最高權限：審核公共/店家評論、處理報錯、管理所有資料'),
(2, 'staff', 0, 0, 1, 0, '營運者：更新菜單價格、發布公告、觀看自家評論'),
(3, 'student', 1, 1, 0, 0, '核心用戶：紀錄攝取量、設定目標、參與實名評論'),
(4, 'visitor', 0, 0, 0, 0, '僅能操作計算機與轉盤，無法儲存資料或評論');

-- --------------------------------------------------------

--
-- 資料表結構 `restaurants`
--

CREATE TABLE `restaurants` (
  `r_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `location` varchar(20) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `restaurants`
--

INSERT INTO `restaurants` (`r_id`, `name`, `location`, `description`) VALUES
(1, '巧瑋鬆餅屋', '心園', '甜味鬆餅、黑可可系列、鹹味鬆餅'),
(2, '心園麵店', '心園', '麵食、飯類、水餃、湯品、滷味'),
(3, '心園自助餐', '心園', '自助餐、主食、肉類、蔬菜、蛋白質料理'),
(4, '辛蔬料理', '理園', '素食、咖哩飯、義大利麵、乾麵'),
(5, '澳門華記', '理園', '港式燒臘、雙拼飯、牛河、特色料理'),
(6, '娃子早餐店 (WAZI)', '理園', '早餐、漢堡、蛋餅、吐司、鐵板麵'),
(7, 'Hey!覓朵朵 舒芙蕾', '理園', '舒芙蕾、甜點、飯捲'),
(8, '豪客來牛排', '理園', '牛排、排餐、鐵板麵、雙拼'),
(9, '阿珠媽', '理園', '韓式料理、韓式泡麵、便當、鍋物'),
(10, '熊賀炒飯', '理園', '炒飯、炒麵、牛肉、豬肉、海鮮'),
(11, '食福簡餐炒飯專賣', '輔園', '炒飯套餐、涼麵、湯品'),
(12, '深川味私房小館', '輔園', '川菜、麻辣料理、牛肉飯、鴨血'),
(13, '埃及教父', '輔園', '沙威瑪、捲餅、雞肉飯、異國料理'),
(14, '八方雲集', '輔園', '鍋貼、水餃、麵食、湯品'),
(15, '奇奇海南雞飯', '輔園', '海南雞飯、雞肉料理、套餐'),
(16, '新羅韓國料理', '輔園', '韓式料理、烤肉、拌飯、鍋物'),
(17, '雲瀚哨子麵、咖哩飯', '輔園', '哨子麵、咖哩飯、套餐'),
(18, '新東家早餐滷味', '輔園', '早餐、蛋餅、吐司、滷味'),
(19, '瑪納社企 CAFÉ', '輔園', '簡餐、雞肉、豬肉、蔬食、咖啡');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`u_id`),
  ADD UNIQUE KEY `accounts` (`accounts`),
  ADD KEY `fk_acc_role` (`role_id`),
  ADD KEY `fk_acc_res` (`r_id`);

--
-- 資料表索引 `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`role_id`);

--
-- 資料表索引 `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`r_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `accounts`
--
ALTER TABLE `accounts`
  MODIFY `u_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `r_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `fk_acc_res` FOREIGN KEY (`r_id`) REFERENCES `restaurants` (`r_id`),
  ADD CONSTRAINT `fk_acc_role` FOREIGN KEY (`role_id`) REFERENCES `permissions` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
