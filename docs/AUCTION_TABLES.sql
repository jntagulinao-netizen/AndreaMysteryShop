-- Auction module tables for AndreaMysteryShop
-- Run this once in your MySQL database before using auction admin pages.

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
  CONSTRAINT fk_auction_listings_admin FOREIGN KEY (admin_user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_auction_listings_category FOREIGN KEY (category_id) REFERENCES categories (category_id) ON DELETE SET NULL,
  CONSTRAINT fk_auction_listings_winner FOREIGN KEY (winner_user_id) REFERENCES users (user_id) ON DELETE SET NULL,
  CONSTRAINT fk_auction_listings_source_draft FOREIGN KEY (source_draft_id) REFERENCES auction_drafts (draft_id) ON DELETE SET NULL
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
