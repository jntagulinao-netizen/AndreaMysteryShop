# Auction Cron Scheduler Setup

This project now includes a scheduler endpoint that updates auction statuses even when there is no user traffic.

Endpoint:
- `api/cron-auction-status.php`

## Security
Set an environment variable named `AUCTION_CRON_TOKEN`.

Example value:
- `a_long_random_secret_token_here`

Web requests to endpoint must include:
- `?token=YOUR_TOKEN`

Example:
- `https://your-domain.com/api/cron-auction-status.php?token=YOUR_TOKEN`

If token is missing or invalid, endpoint returns HTTP 401.

## What it does
On each run it:
- moves `scheduled` auctions to `active` when start time has passed
- finalizes `scheduled/active` auctions to `sold` or `ended` when end time is reached
- returns JSON with before/after status counts

## Recommended schedule
Run every minute.

## Windows Task Scheduler example
Use a browser-style hit command every minute:

```powershell
powershell -NoProfile -Command "Invoke-WebRequest -UseBasicParsing 'https://your-domain.com/api/cron-auction-status.php?token=YOUR_TOKEN' | Out-Null"
```

## Linux cron example

```bash
* * * * * /usr/bin/curl -fsS "https://your-domain.com/api/cron-auction-status.php?token=YOUR_TOKEN" >/dev/null
```

## Optional CLI mode
If executed from CLI (`php api/cron-auction-status.php`), token is not required.
