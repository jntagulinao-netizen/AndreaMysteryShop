# Secondary Bidder Auto-Offer Implementation Guide

## Overview
When an auction winner cancels their checkout order, the system automatically:
1. Marks the original order as "cancelled"
2. Frees up the delivery slot
3. Creates a new "secondary_offer" for the next highest bidder
4. Sends automatic notifications
5. If declined, moves to the next bidder
6. If all decline, marks auction for re-listing

## Database Schema Changes

### Required Alterations
Run the SQL migrations in `docs/SECONDARY_BIDDER_SCHEMA.sql`:

```sql
-- Add tracking columns to orders table
ALTER TABLE orders ADD COLUMN IF NOT EXISTS auction_id INT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS secondary_offer_expires_at DATETIME NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS original_winner_user_id INT NULL;

-- Add foreign key constraint
ALTER TABLE orders ADD CONSTRAINT fk_orders_auction FOREIGN KEY (auction_id) 
  REFERENCES auction_listings(auction_id) ON DELETE CASCADE;

-- Add indexes for performance
CREATE INDEX idx_orders_auction_status ON orders(auction_id, status);
CREATE INDEX idx_orders_secondary_offer_expires ON orders(secondary_offer_expires_at);
```

### New Order Statuses
| Status | Meaning |
|--------|---------|
| `pending` | Normal order waiting for completion |
| `secondary_offer` | Offer to a secondary bidder (48-hour expiration) |
| `secondary_offer_declined` | Bidder declined, moved to next |
| `cancelled` | Order cancelled by user |

## API Endpoints

### 1. Cancel Auction Order
**Endpoint:** `POST /api/cancel-auction-order.php`

**Request:**
```json
{
  "order_id": 123,
  "reason": "Changed my mind"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Auction order cancelled successfully",
  "cancelled_order_id": 123,
  "secondary_offer_created": true,
  "secondary_offer_id": 124,
  "next_bidder_id": 456,
  "notification": "The winning bidder for 'Item Name' has cancelled..."
}
```

**Process:**
1. Validates user owns the order
2. Finds next highest bidder from auction_bids table
3. Creates secondary_offer order for next bidder
4. Sends automatic message notification
5. Frees delivery slot
6. Returns within 48 hours for bidder acceptance

---

### 2. Handle Secondary Offer
**Endpoint:** `POST /api/handle-secondary-offer.php`

**Request:**
```json
{
  "offer_id": 124,
  "action": "accept"
}
```

OR

```json
{
  "offer_id": 124,
  "action": "decline"
}
```

**Response (Accept):**
```json
{
  "success": true,
  "message": "Offer accepted! Proceed to checkout.",
  "offer_id": 124,
  "auction_id": 50,
  "new_status": "pending"
}
```

**Response (Decline):**
```json
{
  "success": true,
  "message": "Offer declined. Moving to next bidder.",
  "offer_id": 124,
  "next_bidder_id": 789
}
```

**If Accept:**
- Updates offer status to "pending"
- Redirects user to checkout
- Updates auction winner_user_id
- Notifies original winner

**If Decline:**
- Marks offer as "secondary_offer_declined"
- Finds next bidder
- Creates new offer or marks auction for re-listing

---

### 3. Cron Job - Process Expired Offers
**Endpoint:** `GET/POST /api/cron-process-expired-auction-offers.php`

**Response:**
```json
{
  "success": true,
  "message": "Cron job completed",
  "processed_count": 5,
  "moved_to_next_bidder": 4,
  "unclaimed_auctions": 1
}
```

**Schedule:** Run every 1 hour via cron job
```bash
# Add to crontab:
0 * * * * curl -s https://your-domain.com/api/cron-process-expired-auction-offers.php > /dev/null 2>&1
```

**Process:**
1. Finds all orders with status = "secondary_offer" and expiration < NOW()
2. For each expired offer:
   - Finds next bidder
   - Creates new secondary_offer for them
   - OR marks auction as "relisting" if no bidders left
3. Sends notifications accordingly

---

## User Flow Diagrams

### Winner Cancellation Flow
```
Winner clicks "Cancel Order"
    ↓
cancel-auction-order.php
    ↓
┌─────────────────────────────────────────┐
│ 1. Order → "cancelled"                   │
│ 2. Free delivery slot                    │
│ 3. Get next highest bidder               │
└─────────────────────────────────────────┘
    ↓
   ┌─────────────────────────────────────┐
   │ YES: Next Bidder?                   │
   └─────────────────────────────────────┘
    ↙                                   ↘
   YES                                  NO
    ↓                                    ↓
Create secondary_offer      Mark auction "relisting"
Send notification          Notify owner
48-hour timer starts

┌─────────────────────────────────────────┐
│ Bidder Receives "New Offer Available"   │
│ Message in inbox                        │
└─────────────────────────────────────────┘
```

### Secondary Bidder Response Flow
```
Bidder sees offer notification
    ↓
handle-secondary-offer.php (accept OR decline)
    ↓
IF ACCEPT:
├─ Order → "pending"
├─ Redirect to checkout
├─ Update auction winner
└─ Notify original winner
    ↓
Bidder completes checkout normally

IF DECLINE:
├─ Order → "secondary_offer_declined"
├─ Find next bidder
├─ YES: Create new secondary_offer
│       Send notification to next bidder
│       (repeat flow)
└─ NO: Mark auction "relisting"
       Notify owner
```

### Cron Cleanup Flow
```
cron-process-expired-auction-offers.php runs hourly
    ↓
Find all secondary_offers where expires_at < NOW()
    ↓
For each expired offer:
    ├─ YES: Next bidder exists?
    │  ├─ Create new secondary_offer for next bidder
    │  ├─ Auto-decline current bidder
    │  └─ Send notifications
    │
    └─ NO: All bidders declined
       ├─ Mark auction "relisting"
       ├─ Notify owner
       └─ Update original order to "secondary_offer_declined"
```

---

## Integration Points

### 1. Update checkout-auction.php
Add auction_id to order creation:
```php
// In checkout-auction.php, after order insert:
$updateAuctionIdStmt = $conn->prepare('UPDATE orders SET auction_id = ? WHERE order_id = ?');
$updateAuctionIdStmt->bind_param('ii', $auctionId, $orderId);
$updateAuctionIdStmt->execute();
```

### 2. Add Cancel Button to Order Pages
In purchase_history.php or order_detail pages:
```html
<!-- Show cancel button only for pending auction orders within 1 hour of purchase -->
<button class="btn btn-danger" onclick="cancelAuctionOrder(<?php echo $orderId; ?>)">
  Cancel Order
</button>

<script>
function cancelAuctionOrder(orderId) {
  if (!confirm('Are you sure? You cannot undo this. Offer will go to next bidder.')) return;
  
  fetch('/api/cancel-auction-order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ order_id: orderId })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert('Order cancelled. Next bidder has been notified.');
      location.reload();
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(err => alert('Error: ' + err));
}
</script>
```

### 3. Add Secondary Offer Handling to Messages
In messages.php or message notifications:
```html
<!-- When secondary_offer message is received -->
<div class="secondary-offer-card">
  <h4>🎯 New Auction Opportunity</h4>
  <p>The auction winner cancelled. Your bid is now active!</p>
  <p class="countdown">Offer expires in: <span id="countdown"></span></p>
  
  <button class="btn btn-success" onclick="respondToOffer(<?php echo $offerId; ?>, 'accept')">
    Accept Offer
  </button>
  <button class="btn btn-secondary" onclick="respondToOffer(<?php echo $offerId; ?>, 'decline')">
    Decline Offer
  </button>
</div>

<script>
function respondToOffer(offerId, action) {
  fetch('/api/handle-secondary-offer.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ offer_id: offerId, action: action })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      if (action === 'accept') {
        window.location.href = '/auction.php?auction_id=' + data.auction_id + '&checkout=1';
      } else {
        alert('Offer declined. Next bidder will be notified.');
        location.reload();
      }
    }
  });
}
</script>
```

---

## Cron Setup Instructions

### Option 1: cpanel/WHM
1. Go to Cron Jobs
2. Add New Cron Job
3. Command: `curl -s https://yourdomain.com/api/cron-process-expired-auction-offers.php`
4. Minute: `0`
5. Hour: `*` (every hour)

### Option 2: Linux/Windows Task Scheduler
**Linux crontab:**
```bash
crontab -e
# Add line:
0 * * * * curl -s https://yourdomain.com/api/cron-process-expired-auction-offers.php > /dev/null 2>&1
```

**Windows Task Scheduler:**
1. Create new task
2. Trigger: Repeat every 1 hour
3. Action: Run `curl https://yourdomain.com/api/cron-process-expired-auction-offers.php`

---

## Security & Validation

### Implemented Checks:
✅ User authentication (session_start validation)  
✅ Order ownership verification (user_id match)  
✅ Order status validation (pending/secondary_offer only)  
✅ Offer expiration timestamp checking  
✅ Transaction atomicity (rollback on error)  
✅ SQL injection prevention (prepared statements)  
✅ Bidder verification (rank by bid_amount DESC)

### Best Practices Applied:
- All database operations wrapped in transactions
- Prepared statements for all SQL queries
- Error logging to server logs
- Proper HTTP status codes (400, 401, 500)
- JSON response formatting for API consistency

---

## Testing Checklist

- [ ] Database migrations run without error
- [ ] Winner can cancel order within 1 hour
- [ ] Next highest bidder receives notification
- [ ] Secondary bidder can accept offer
- [ ] Secondary bidder can decline offer
- [ ] Declining moves to next bidder
- [ ] Cron job processes expired offers correctly
- [ ] All bidders decline → auction goes to re-listing
- [ ] Delivery slot freed correctly when order cancelled
- [ ] Notifications sent to all parties
- [ ] Order statuses transition correctly
- [ ] No race conditions under load

---

## Status Values Reference

| Status | Created By | Created When | Can Proceed to | Next Action |
|--------|-----------|--------------|-----------------|-------------|
| `pending` | User / Secondary Offer Accept | Normal checkout / Accepted offer | Checkout form | Pay & complete |
| `secondary_offer` | cancel-auction-order.php | Winner cancels | Accept/Decline/Timeout | handle-secondary-offer.php |
| `secondary_offer_declined` | handle-secondary-offer.php | Bidder declines | Move to next bidder or relisting | Automatic via cron |
| `cancelled` | cancel-auction-order.php | Winner cancels | Closed (no action) | End state |

---

## Example: Complete Cancellation Flow

```
Time 0:00 - Winner clicks "Cancel Order" for auction #50
├─ cancel-auction-order.php executes
├─ Original order #123 → status = 'cancelled'
├─ Delivery slot count decremented
├─ Next highest bidder found: user_id = 456
├─ Secondary offer order #124 created
│  ├─ status = 'secondary_offer'
│  ├─ secondary_offer_expires_at = NOW() + 48 hours
│  ├─ original_winner_user_id = 789 (the winner who cancelled)
├─ User 456 sent message: "The winning bidder cancelled. You have 48 hours to accept."

Time 0:30 - User 456 checks messages
├─ Sees offer notification
├─ Clicks "Accept Offer"
├─ handle-secondary-offer.php executes
├─ Order #124 → status = 'pending'
├─ User 456 redirected to checkout
├─ Proceeds with payment
├─ Order complete

OR

Time 1:00 - User 456 declines offer
├─ handle-secondary-offer.php (decline)
├─ Order #124 → status = 'secondary_offer_declined'
├─ Next bidder found: user_id = 789
├─ Secondary offer order #125 created for user 789
├─ User 789 notified of new offer

Time 48:00+ - If user 456 never responded (expired)
├─ cron-process-expired-auction-offers.php runs
├─ Finds order #124 with expires_at < NOW()
├─ Moves to next bidder automatically
├─ Or marks auction for re-listing if no more bidders
```

---

## Error Handling

### Common Error Scenarios

1. **Order not found**
   - Response: 400 Bad Request
   - Message: "Order not found or cannot be cancelled"

2. **Offer expired**
   - Response: 400 Bad Request
   - Message: "Offer has expired"

3. **No authorization**
   - Response: 401 Unauthorized
   - Message: "Unauthorized"

4. **Database error**
   - Response: 500 Internal Server Error
   - Logged to server error log
   - Transaction rolled back automatically

---

## Performance Considerations

- **Indexes Added:**
  - `idx_orders_auction_status` - Fast lookup by auction + status
  - `idx_orders_secondary_offer_expires` - Fast cron job query

- **Query Optimization:**
  - Cron job processes max 100 expired offers per run
  - FOR UPDATE locking prevents race conditions
  - Single transaction per operation minimizes lock time

- **Scaling:**
  - Cron job can run hourly without performance impact
  - Secondary offer creation is sub-second operation
  - Notification sending is async-friendly

---

## Monitoring & Logging

Check error logs for:
```bash
tail -f /path/to/php/error.log | grep "cancel-auction\|secondary-offer\|cron-process"
```

Key log entries:
- "Cron: Created secondary offer for bidder X for auction Y"
- "Cron: No more bidders for auction Y - marked for relisting"
- "Error in cancel-auction-order.php: ..."

---

## Future Enhancements

1. **Payment Verification** - Validate payment method before creating secondary offer
2. **Reputation Scoring** - Track who accepts/declines offers
3. **Auto-Decline Policy** - Admin setting for how many times a bidder can decline
4. **Auction Analytics** - Dashboard showing cancellation rates, secondary offer acceptance rates
5. **Email Notifications** - In addition to in-app messages
6. **Bid Reserve Logic** - If reserve price not met, re-list instead of offering to next bidder
