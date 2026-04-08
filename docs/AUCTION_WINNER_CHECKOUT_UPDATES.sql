-- Adds winner checkout support for auction listings already deployed.

ALTER TABLE auction_listings
  ADD COLUMN auction_product_id INT(11) NULL AFTER admin_user_id,
  ADD KEY idx_auction_listings_product (auction_product_id),
  ADD CONSTRAINT fk_auction_listings_product FOREIGN KEY (auction_product_id) REFERENCES products(product_id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS auction_order_links (
  auction_order_id INT(11) NOT NULL AUTO_INCREMENT,
  auction_id INT(11) NOT NULL,
  order_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (auction_order_id),
  UNIQUE KEY uniq_auction_order_links_auction (auction_id),
  UNIQUE KEY uniq_auction_order_links_order (order_id),
  KEY idx_auction_order_links_user (user_id),
  CONSTRAINT fk_auction_order_links_auction FOREIGN KEY (auction_id) REFERENCES auction_listings (auction_id) ON DELETE CASCADE,
  CONSTRAINT fk_auction_order_links_order FOREIGN KEY (order_id) REFERENCES orders (order_id) ON DELETE CASCADE,
  CONSTRAINT fk_auction_order_links_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
