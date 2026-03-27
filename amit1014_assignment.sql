-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2026-03-27 09:43:03
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `amit1014_assignment`
--

-- --------------------------------------------------------

--
-- 表的结构 `member`
--

CREATE TABLE `member` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Member') DEFAULT 'Member',
  `profile_photo` varchar(255) DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `lockout_time` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `member`
--

INSERT INTO `member` (`id`, `username`, `email`, `password`, `role`, `profile_photo`, `failed_attempts`, `lockout_time`, `remember_token`, `reset_token`, `reset_expires`) VALUES
(1, 'M11', 'member1@gmail.com', '$2y$10$kIYukWfBJ.oqKQ33Nn6cc.qj6TsJ3gVf2v62kyFNQXqKyJykTkA9.', 'Member', 'user_1_1774203347.jpg', 4, '2026-03-23 16:12:36', NULL, '274a37ccfb5ba08b665c075fd5bb414d07efa1a94e00961f4ef5854425ca0ff0', '2026-03-24 00:28:28'),
(2, 'M2', 'member2@gmail.com', '$2y$10$w4vW1UGoqafLdorI328ssuKPE9P.ZDltCjm4EFRRcJVBmoNJDoirO', 'Member', NULL, 0, NULL, NULL, NULL, NULL),
(3, 'ahkong', 'ahkong463@gmail.com', '$2y$10$7dtlwj9QUSObj.Jcf8Obre6.GuwwriGT8.JYx5a6kKhUhUJ7DPNWW', 'Member', 'user_3_1774282447.jpg', 0, NULL, NULL, 'dfb296aa6e8dcc4541ed15a5a57ccb863535d59a7e743466aee7b9c11318f91d', '2026-03-24 01:15:47');

-- --------------------------------------------------------

--
-- 表的结构 `username`
--

CREATE TABLE `username` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Admin','Member') DEFAULT 'Member',
  `profile_photo` varchar(255) DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `lockout_time` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 表的索引 `username`
--
ALTER TABLE `username`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `member`
--
ALTER TABLE `member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `username`
--
ALTER TABLE `username`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
