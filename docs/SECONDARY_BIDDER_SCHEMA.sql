-- Secondary Bidder Auto-Offer Schema Additions
-- Run these migrations to support auction order cancellations with automatic secondary bidder offers
-- Secondary bidder feature uses a dedicated link table for auction-specific state
-- Add columns to existing `auction_order_links` table to track offers


ALTER TABLE auction_order_links 
	ADD COLUMN IF NOT EXISTS secondary_offer_expires_at DATETIME NULL AFTER user_id,
	ADD COLUMN IF NOT EXISTS original_winner_user_id INT NULL AFTER secondary_offer_expires_at,
	ADD COLUMN IF NOT EXISTS status VARCHAR(30) NOT NULL DEFAULT 'linked' AFTER original_winner_user_id;

-- Allow multiple rows per auction so secondary offers can be created for fallback bidders
SET @drop_auction_index := (
	SELECT COUNT(*)
	FROM information_schema.statistics
	WHERE table_schema = DATABASE()
		AND table_name = 'auction_order_links'
		AND index_name = 'uniq_auction_order_links_auction'
);
SET @drop_auction_index_sql := IF(
	@drop_auction_index > 0,
	'ALTER TABLE auction_order_links DROP INDEX uniq_auction_order_links_auction',
	'SELECT 1'
);
PREPARE drop_auction_index_stmt FROM @drop_auction_index_sql;
EXECUTE drop_auction_index_stmt;
DEALLOCATE PREPARE drop_auction_index_stmt;
CREATE INDEX IF NOT EXISTS idx_aol_auction_id ON auction_order_links(auction_id);

-- Add indexes for faster lookups
CREATE INDEX IF NOT EXISTS idx_aol_auction_status ON auction_order_links(auction_id, status);
CREATE INDEX IF NOT EXISTS idx_aol_offer_expires ON auction_order_links(secondary_offer_expires_at);

-- Optional: keep orders table clean; auction-specific metadata is stored in auction_order_links
