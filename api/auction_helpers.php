<?php

if (!defined('AUCTION_TIMEZONE_SET')) {
    date_default_timezone_set('Asia/Manila');
    define('AUCTION_TIMEZONE_SET', true);
}

function auction_now_string(): string {
    return date('Y-m-d H:i:s');
}

function auction_sync_single(mysqli $conn, int $auctionId): void {
    $now = auction_now_string();

    $stmt = $conn->prepare('SELECT auction_id, reserve_price, start_at, end_at, auction_status, current_bid FROM auction_listings WHERE auction_id = ? LIMIT 1');
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('i', $auctionId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return;
    }

    $status = (string)($row['auction_status'] ?? 'scheduled');
    $startAt = (string)($row['start_at'] ?? '');
    $endAt = (string)($row['end_at'] ?? '');
    if ($startAt === '' || $endAt === '') {
        return;
    }

    if ($status === 'scheduled' && $startAt <= $now && $endAt > $now) {
        $activateStmt = $conn->prepare('UPDATE auction_listings SET auction_status = \'active\', published_at = COALESCE(published_at, ?) WHERE auction_id = ?');
        if ($activateStmt) {
            $activateStmt->bind_param('si', $now, $auctionId);
            $activateStmt->execute();
            $activateStmt->close();
        }
        return;
    }

    if (($status === 'scheduled' || $status === 'active') && $endAt <= $now) {
        $topBidStmt = $conn->prepare('SELECT user_id, bid_amount FROM auction_bids WHERE auction_id = ? AND bid_status = \'valid\' ORDER BY bid_amount DESC, bid_id DESC LIMIT 1');
        $winnerUserId = null;
        $winningBid = null;

        if ($topBidStmt) {
            $topBidStmt->bind_param('i', $auctionId);
            $topBidStmt->execute();
            $topRes = $topBidStmt->get_result();
            $topRow = $topRes ? $topRes->fetch_assoc() : null;
            if ($topRow) {
                $winnerUserId = (int)$topRow['user_id'];
                $winningBid = (float)$topRow['bid_amount'];
            }
            $topBidStmt->close();
        }

        $reserve = isset($row['reserve_price']) ? (float)$row['reserve_price'] : null;
        $isSold = $winningBid !== null && ($reserve === null || $winningBid >= $reserve);

        if ($isSold) {
            $closeSoldStmt = $conn->prepare('UPDATE auction_listings SET auction_status = \'sold\', winner_user_id = ?, sold_price = ?, current_bid = ?, published_at = COALESCE(published_at, start_at), closed_at = COALESCE(closed_at, ?) WHERE auction_id = ?');
            if ($closeSoldStmt) {
                $closeSoldStmt->bind_param('iddsi', $winnerUserId, $winningBid, $winningBid, $now, $auctionId);
                $closeSoldStmt->execute();
                $closeSoldStmt->close();
            }
        } else {
            $closeEndedStmt = $conn->prepare('UPDATE auction_listings SET auction_status = \'ended\', winner_user_id = NULL, sold_price = NULL, published_at = COALESCE(published_at, start_at), closed_at = COALESCE(closed_at, ?) WHERE auction_id = ?');
            if ($closeEndedStmt) {
                $closeEndedStmt->bind_param('si', $now, $auctionId);
                $closeEndedStmt->execute();
                $closeEndedStmt->close();
            }
        }
    }
}

function auction_sync_statuses(mysqli $conn, ?int $auctionId = null): void {
    if ($auctionId !== null && $auctionId > 0) {
        auction_sync_single($conn, $auctionId);
        return;
    }

    $now = auction_now_string();

    $scheduledStmt = $conn->prepare('SELECT auction_id FROM auction_listings WHERE auction_status = \'scheduled\' AND start_at <= ? AND end_at > ?');
    if ($scheduledStmt) {
        $scheduledStmt->bind_param('ss', $now, $now);
        $scheduledStmt->execute();
        $scheduledRes = $scheduledStmt->get_result();
        while ($row = $scheduledRes->fetch_assoc()) {
            auction_sync_single($conn, (int)$row['auction_id']);
        }
        $scheduledStmt->close();
    }

    $endingStmt = $conn->prepare('SELECT auction_id FROM auction_listings WHERE auction_status IN (\'scheduled\', \'active\') AND end_at <= ?');
    if ($endingStmt) {
        $endingStmt->bind_param('s', $now);
        $endingStmt->execute();
        $endingRes = $endingStmt->get_result();
        while ($row = $endingRes->fetch_assoc()) {
            auction_sync_single($conn, (int)$row['auction_id']);
        }
        $endingStmt->close();
    }
}

function auction_has_schedule_conflict(mysqli $conn, string $startAt, string $endAt, int $draftId = 0): bool {
    if ($startAt === '' || $endAt === '') {
        return false;
    }

    $draftSql = 'SELECT draft_id FROM auction_drafts WHERE draft_status = \'draft\' AND start_at < ? AND end_at > ?';
    if ($draftId > 0) {
        $draftSql .= ' AND draft_id <> ?';
    }
    $draftSql .= ' LIMIT 1';

    $draftStmt = $conn->prepare($draftSql);
    if ($draftStmt) {
        if ($draftId > 0) {
            $draftStmt->bind_param('ssi', $endAt, $startAt, $draftId);
        } else {
            $draftStmt->bind_param('ss', $endAt, $startAt);
        }
        $draftStmt->execute();
        $draftRes = $draftStmt->get_result();
        $draftRow = $draftRes ? $draftRes->fetch_assoc() : null;
        $draftStmt->close();
        if ($draftRow) {
            return true;
        }
    }

    $listingSql = 'SELECT auction_id FROM auction_listings WHERE auction_status IN (\'scheduled\', \'active\') AND start_at < ? AND end_at > ? LIMIT 1';
    $listingStmt = $conn->prepare($listingSql);
    if ($listingStmt) {
        $listingStmt->bind_param('ss', $endAt, $startAt);
        $listingStmt->execute();
        $listingRes = $listingStmt->get_result();
        $listingRow = $listingRes ? $listingRes->fetch_assoc() : null;
        $listingStmt->close();
        if ($listingRow) {
            return true;
        }
    }

    return false;
}

function auction_cover_image(mysqli $conn, int $auctionId): string {
    $stmt = $conn->prepare('SELECT file_path FROM auction_listing_media WHERE auction_id = ? AND media_type = \'image\' ORDER BY is_pinned DESC, sort_order ASC, media_id ASC LIMIT 1');
    if (!$stmt) {
        return '';
    }
    $stmt->bind_param('i', $auctionId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return (string)($row['file_path'] ?? '');
}

function auction_cover_video(mysqli $conn, int $auctionId): string {
    $stmt = $conn->prepare('SELECT file_path FROM auction_listing_media WHERE auction_id = ? AND media_type = \'video\' ORDER BY is_pinned DESC, sort_order ASC, media_id ASC LIMIT 1');
    if (!$stmt) {
        return '';
    }
    $stmt->bind_param('i', $auctionId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return (string)($row['file_path'] ?? '');
}

function auction_mask_name(string $name): string {
    $clean = trim($name);
    if ($clean === '') {
        return 'Bidder';
    }
    if (strlen($clean) <= 2) {
        return substr($clean, 0, 1) . '*';
    }
    return substr($clean, 0, 1) . str_repeat('*', max(1, strlen($clean) - 2)) . substr($clean, -1);
}
