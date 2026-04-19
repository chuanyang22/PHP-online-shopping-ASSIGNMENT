-- Saved carts for logged-in members (survives logout / new session).
-- Import this into the same database as in lib/db.php (e.g. amit1014_assignment).

CREATE TABLE IF NOT EXISTS `member_cart` (
  `member_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`member_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `member_cart_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`id`) ON DELETE CASCADE,
  CONSTRAINT `member_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
