-- Auction publish + bidding + status automation support updates
-- Run after docs/AUCTION_TABLES.sql

-- Improve listing query performance for buyer pages and scheduler checks.
CREATE INDEX idx_auction_listings_status_end
ON auction_listings (auction_status, end_at);

CREATE INDEX idx_auction_listings_status_start_end
ON auction_listings (auction_status, start_at, end_at);

-- Speeds up getting latest/highest valid bid per auction.
CREATE INDEX idx_auction_bids_auction_status_amount
ON auction_bids (auction_id, bid_status, bid_amount, bid_id);

-- Ensure each auction has only one media path per type+sort slot.
CREATE UNIQUE INDEX uniq_auction_listing_media_slot
ON auction_listing_media (auction_id, media_type, sort_order);

-- Optional data hygiene: normalize any invalid bid increments.
UPDATE auction_listings
SET bid_increment = 1.00
WHERE bid_increment IS NULL OR bid_increment <= 0;

-- Optional data hygiene: align current_bid with highest valid bid if mismatched.
UPDATE auction_listings l
JOIN (
  SELECT auction_id, MAX(bid_amount) AS top_bid
  FROM auction_bids
  WHERE bid_status = 'valid'
  GROUP BY auction_id
) t ON t.auction_id = l.auction_id
SET l.current_bid = t.top_bid
WHERE l.current_bid IS NULL OR l.current_bid < t.top_bid;
