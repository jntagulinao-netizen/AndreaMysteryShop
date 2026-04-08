-- Full setup script for auction module
-- Portable version for phpMyAdmin/MySQL clients (no SOURCE command required).

CREATE TABLE IF NOT EXISTS auction_drafts (
	draft_id INT(11) NOT NULL AUTO_INCREMENT,
	admin_user_id INT(11) NOT NULL,
	category_id INT(11) DEFAULT NULL,
	item_name VARCHAR(180) DEFAULT NULL,
	item_description TEXT DEFAULT NULL,
	condition_grade VARCHAR(40) DEFAULT NULL,
	starting_bid DECIMAL(10,2) DEFAULT NULL,
	reserve_price DECIMAL(10,2) DEFAULT NULL,
	bid_increment DECIMAL(10,2) DEFAULT NULL,
	start_at DATETIME DEFAULT NULL,
	end_at DATETIME DEFAULT NULL,
	draft_status ENUM('draft','archived') NOT NULL DEFAULT 'draft',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (draft_id),
	KEY idx_auction_drafts_admin (admin_user_id),
	KEY idx_auction_drafts_status (draft_status),
	KEY idx_auction_drafts_end (end_at),
	CONSTRAINT fk_auction_drafts_admin FOREIGN KEY (admin_user_id) REFERENCES users (user_id) ON DELETE CASCADE,
	CONSTRAINT fk_auction_drafts_category FOREIGN KEY (category_id) REFERENCES categories (category_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS auction_draft_media (
	media_id INT(11) NOT NULL AUTO_INCREMENT,
	draft_id INT(11) NOT NULL,
	media_type ENUM('image','video') NOT NULL,
	file_path VARCHAR(255) NOT NULL,
	sort_order INT(11) NOT NULL DEFAULT 0,
	is_pinned TINYINT(1) NOT NULL DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (media_id),
	KEY idx_auction_draft_media_draft (draft_id),
	KEY idx_auction_draft_media_type (media_type),
	CONSTRAINT fk_auction_draft_media_draft FOREIGN KEY (draft_id) REFERENCES auction_drafts (draft_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS auction_listings (
	auction_id INT(11) NOT NULL AUTO_INCREMENT,
	source_draft_id INT(11) DEFAULT NULL,
	admin_user_id INT(11) NOT NULL,
	auction_product_id INT(11) DEFAULT NULL,
	category_id INT(11) DEFAULT NULL,
	item_name VARCHAR(180) NOT NULL,
	item_description TEXT DEFAULT NULL,
	condition_grade VARCHAR(40) DEFAULT NULL,
	starting_bid DECIMAL(10,2) NOT NULL,
	current_bid DECIMAL(10,2) DEFAULT NULL,
	reserve_price DECIMAL(10,2) DEFAULT NULL,
	bid_increment DECIMAL(10,2) NOT NULL DEFAULT 1.00,
	start_at DATETIME NOT NULL,
	end_at DATETIME NOT NULL,
	auction_status ENUM('scheduled','active','ended','cancelled','sold') NOT NULL DEFAULT 'scheduled',
	winner_user_id INT(11) DEFAULT NULL,
	sold_price DECIMAL(10,2) DEFAULT NULL,
	published_at DATETIME DEFAULT NULL,
	closed_at DATETIME DEFAULT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (auction_id),
	KEY idx_auction_listings_status (auction_status),
	KEY idx_auction_listings_time (start_at, end_at),
	KEY idx_auction_listings_admin (admin_user_id),
	KEY idx_auction_listings_product (auction_product_id),
	CONSTRAINT fk_auction_listings_admin FOREIGN KEY (admin_user_id) REFERENCES users (user_id) ON DELETE CASCADE,
	CONSTRAINT fk_auction_listings_product FOREIGN KEY (auction_product_id) REFERENCES products (product_id) ON DELETE SET NULL,
	CONSTRAINT fk_auction_listings_category FOREIGN KEY (category_id) REFERENCES categories (category_id) ON DELETE SET NULL,
	CONSTRAINT fk_auction_listings_winner FOREIGN KEY (winner_user_id) REFERENCES users (user_id) ON DELETE SET NULL,
	CONSTRAINT fk_auction_listings_source_draft FOREIGN KEY (source_draft_id) REFERENCES auction_drafts (draft_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE IF NOT EXISTS auction_listing_media (
	media_id INT(11) NOT NULL AUTO_INCREMENT,
	auction_id INT(11) NOT NULL,
	media_type ENUM('image','video') NOT NULL,
	file_path VARCHAR(255) NOT NULL,
	sort_order INT(11) NOT NULL DEFAULT 0,
	is_pinned TINYINT(1) NOT NULL DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (media_id),
	KEY idx_auction_listing_media_auction (auction_id),
	KEY idx_auction_listing_media_type (media_type),
	CONSTRAINT fk_auction_listing_media_auction FOREIGN KEY (auction_id) REFERENCES auction_listings (auction_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS auction_bids (
	bid_id INT(11) NOT NULL AUTO_INCREMENT,
	auction_id INT(11) NOT NULL,
	user_id INT(11) NOT NULL,
	bid_amount DECIMAL(10,2) NOT NULL,
	bid_status ENUM('valid','outbid','cancelled') NOT NULL DEFAULT 'valid',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (bid_id),
	KEY idx_auction_bids_auction (auction_id),
	KEY idx_auction_bids_user (user_id),
	KEY idx_auction_bids_time (created_at),
	CONSTRAINT fk_auction_bids_auction FOREIGN KEY (auction_id) REFERENCES auction_listings (auction_id) ON DELETE CASCADE,
	CONSTRAINT fk_auction_bids_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE INDEX idx_auction_listings_status_end
ON auction_listings (auction_status, end_at);

CREATE INDEX idx_auction_listings_status_start_end
ON auction_listings (auction_status, start_at, end_at);

CREATE INDEX idx_auction_bids_auction_status_amount
ON auction_bids (auction_id, bid_status, bid_amount, bid_id);

CREATE UNIQUE INDEX uniq_auction_listing_media_slot
ON auction_listing_media (auction_id, media_type, sort_order);

UPDATE auction_listings
SET bid_increment = 1.00
WHERE bid_increment IS NULL OR bid_increment <= 0;

UPDATE auction_listings l
JOIN (
	SELECT auction_id, MAX(bid_amount) AS top_bid
	FROM auction_bids
	WHERE bid_status = 'valid'
	GROUP BY auction_id
) t ON t.auction_id = l.auction_id
SET l.current_bid = t.top_bid
WHERE l.current_bid IS NULL OR l.current_bid < t.top_bid;
