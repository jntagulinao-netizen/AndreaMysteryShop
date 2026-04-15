<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: LogIn.php');
    exit;
}
$role = $_SESSION['user_role'] ?? 'user';
if ($role !== 'admin') {
    header('Location: user_dashboard.php');
    exit;
}

require_once 'dbConnection.php';

$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$ordersByStatus = [
    'shipped' => [],
    'pickup' => []
];

$sql = 'SELECT 
    o.order_id,
    o.order_date,
    o.status,
    o.delivery_type,
    o.schedule_date,
    o.schedule_slot,
    o.payment_method,
    o.total_amount,
    u.full_name AS customer_name,
    (
      SELECT p.product_name
      FROM order_items oi
      LEFT JOIN products p ON oi.product_id = p.product_id
      WHERE oi.order_id = o.order_id
      ORDER BY oi.order_item_id ASC
      LIMIT 1
    ) AS product_name
  FROM orders o
  LEFT JOIN users u ON o.user_id = u.user_id
  WHERE o.archived = 0
    AND o.binned = 0
    AND o.status IN ("shipped", "pickup")
  ORDER BY o.order_date DESC, o.order_id DESC';

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $status = $row['status'] ?? '';
        if (!isset($ordersByStatus[$status])) {
            continue;
        }
        $ordersByStatus[$status][] = $row;
    }
    $result->close();
}

$statusTitles = [
    'shipped' => 'To Receive',
    'pickup' => 'Pickups'
];

function formatSchedule(array $order): string {
    $parts = [];
    if (!empty($order['schedule_date'])) {
        $parts[] = date('M j, Y', strtotime($order['schedule_date']));
    }
    if (!empty($order['schedule_slot'])) {
        $parts[] = date('g:i A', strtotime($order['schedule_slot']));
    }
    return !empty($parts) ? implode(' • ', $parts) : 'No schedule';
}

function formatPeso($amount): string {
    return '₱' . number_format((float)$amount, 2);
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Receive & Pickup Orders - Andrea Mystery Shop</title>
  <link rel="stylesheet" href="main.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { min-height: 100%; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6fb; color: #1f2937; }
    .page-container { width: calc(100% - 32px); max-width: none; margin: 0 auto; padding: 110px 0 24px; }
    .page-header {
      position: fixed;
      top: 16px;
      left: 50%;
      transform: translateX(-50%);
      width: calc(100% - 32px);
      max-width: none;
      background: #ffffff;
      z-index: 120;
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 14px 20px;
      border-radius: 18px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 14px 50px rgba(15, 23, 42, 0.08);
    }
    .back-arrow { cursor: pointer; font-size: 24px; color: #111827; padding: 6px; line-height: 1; }
    .header-title { font-size: 20px; font-weight: 700; color: #111827; }
    .dashboard-grid { display: grid; grid-template-columns: 320px minmax(0, 1fr); gap: 26px; }
    .panel { background: #ffffff; border-radius: 24px; border: 1px solid #e5e7eb; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06); }
    .panel-padding { padding: 24px; }
    .calendar-panel { display: flex; flex-direction: column; gap: 20px; }
    .calendar-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .calendar-title { font-size: 16px; font-weight: 700; color: #111827; }
    .calendar-subtitle { font-size: 13px; color: #6b7280; }
    .calendar-nav { display: flex; gap: 8px; }
    .nav-button { width: 38px; height: 38px; border-radius: 12px; border: 1px solid #e5e7eb; background: #fff; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; color: #374151; }
    .nav-button:hover { border-color: #cbd5e1; }
    .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-top: 14px; }
    .calendar-day-label { font-size: 11px; text-transform: uppercase; color: #9ca3af; text-align: center; letter-spacing: .08em; }
    .calendar-cell { min-height: 46px; border-radius: 14px; display: grid; place-items: center; font-size: 14px; color: #374151; cursor: pointer; border: 1px solid transparent; transition: all .18s ease; position: relative; }
    .calendar-cell.enabled:hover { background: #eff6ff; border-color: #dbeafe; }
    .calendar-cell.current, .calendar-cell.active { background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; border-color: #2563eb; box-shadow: 0 12px 22px rgba(59, 130, 246, 0.22); }
    .calendar-cell.disabled { cursor: default; color: #d1d5db; }
    .calendar-cell.has-receive::after { content: ''; width: 6px; height: 6px; border-radius: 999px; background: #2563eb; position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%); }
    .calendar-cell.has-pickup::after { content: ''; width: 6px; height: 6px; border-radius: 999px; background: #ec4899; position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%); }
    .calendar-cell.has-receive.has-pickup::after { content: ''; width: 12px; height: 6px; border-radius: 999px; background: linear-gradient(to right, #2563eb 50%, #ec4899 50%); position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%); }
    .calendar-cell.current.has-receive::after { background: rgba(255,255,255,0.9); }
    .calendar-cell.current.has-pickup::after { background: rgba(255,255,255,0.9); }
    .calendar-cell.current.has-receive.has-pickup::after { background: linear-gradient(to right, rgba(255,255,255,0.9) 50%, rgba(255,255,255,0.9) 50%); }
    .calendar-footer { display: grid; gap: 12px; }
    .group-list { display: grid; gap: 10px; }
    .group-item { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #374151; }
    .group-color { width: 12px; height: 12px; border-radius: 999px; display: inline-block; }
    .group-label { color: #374151; }
    .schedule-panel { display: flex; flex-direction: column; gap: 16px; }
    .day-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; }
    .day-title-wrap { display: flex; flex-direction: column; gap: 6px; }
    .day-label { font-size: 12px; color: #6b7280; letter-spacing: .08em; text-transform: uppercase; }
    .day-title { font-size: 28px; font-weight: 800; color: #111827; line-height: 1; }
    .day-subtitle { color: #6b7280; font-size: 13px; }
    .stats-strip { display: flex; gap: 12px; flex-wrap: wrap; }
    .stat-chip { border-radius: 999px; background: #eff6ff; padding: 10px 14px; color: #1d4ed8; font-size: 13px; font-weight: 700; }
    .schedule-list { display: grid; gap: 14px; }
    .schedule-card { border-radius: 22px; padding: 18px 20px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.06); background: #f8fafc; border: 1px solid #e2e8f0; cursor: pointer; transition: transform .18s ease, border-color .18s ease; }
    .schedule-card:hover { transform: translateY(-2px); border-color: #cbd5e1; }
    .schedule-card.pickup { background: linear-gradient(135deg, rgba(236, 72, 153, 0.12), rgba(251, 191, 36, 0.12)); border-color: rgba(236, 72, 153, 0.18); }
    .schedule-card.receive { background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(16, 185, 129, 0.12)); border-color: rgba(59, 130, 246, 0.18); }
    .schedule-card-header { display: flex; justify-content: space-between; gap: 14px; margin-bottom: 12px; }
    .schedule-card-title { font-size: 16px; font-weight: 700; color: #111827; }
    .schedule-card-badge { padding: 8px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
    .badge-pickup { background: rgba(236, 72, 153, 0.16); color: #be123c; }
    .badge-shipped { background: rgba(59, 130, 246, 0.16); color: #1d4ed8; }
    .schedule-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; font-size: 13px; color: #475569; }
    .schedule-meta strong { color: #111827; font-weight: 700; }
    .schedule-actions { margin-top: 14px; display: flex; gap: 10px; flex-wrap: wrap; }
    .btn { display: inline-flex; align-items: center; justify-content: center; border: none; border-radius: 12px; padding: 10px 16px; font-size: 13px; font-weight: 700; cursor: pointer; }
    .btn-primary { background: #2563eb; color: #fff; }
    .btn-secondary { background: #e5e7eb; color: #374151; }
    .calendar-empty { color: #9ca3af; font-size: 13px; text-align: center; padding: 14px 0; }
    @media (max-width: 1024px) {
      .dashboard-grid { grid-template-columns: 1fr; }
      .page-container { padding-top: 90px; }
    }
    @media (max-width: 720px) {
      .page-container { width: calc(100% - 20px); padding-top: 80px; }
      .page-header { flex-wrap: wrap; gap: 12px; }
      .calendar-header, .day-header { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body>
  <?php
    $currentDate = new DateTime("$selectedYear-$selectedMonth-01");
    $calendarYear = $selectedYear;
    $calendarMonth = $selectedMonth;
    $calendarLabel = $currentDate->format('F Y');
    $today = new DateTime();
    $currentDay = (int)$today->format('j');
    $currentMonth = (int)$today->format('n');
    $currentYear = (int)$today->format('Y');
    $isCurrentMonth = $calendarMonth === $currentMonth && $calendarYear === $currentYear;
    $currentWeekday = $isCurrentMonth ? $today->format('l') : $currentDate->format('l');
    $currentDayLabel = $isCurrentMonth ? $today->format('jS F Y') : $currentDate->format('jS F Y');
    $firstOfMonth = new DateTime("$calendarYear-$calendarMonth-01");
    $startOffset = (int)$firstOfMonth->format('N');
    $daysInMonth = (int)$currentDate->format('t');
    $scheduleItems = [];
    $scheduledDates = [];
    foreach ($ordersByStatus as $statusKey => $orders) {
      foreach ($orders as $order) {
        $date = $order['schedule_date'] ?: $order['order_date'];
        $time = $order['schedule_slot'] ?: date('H:i', strtotime($order['order_date']));
        $timestamp = strtotime($date . ' ' . $time);
        $dateKey = date('Y-m-d', strtotime($date));
        if (!isset($scheduledDates[$dateKey])) {
          $scheduledDates[$dateKey] = [];
        }
        $scheduledDates[$dateKey][] = $statusKey;
        $scheduleItems[] = [
          'order_id' => $order['order_id'],
          'product_name' => $order['product_name'] ?: 'Order #' . intval($order['order_id']),
          'customer_name' => $order['customer_name'],
          'status' => $statusKey,
          'label' => $statusKey === 'pickup' ? 'Pickup' : 'To Receive',
          'when' => date('g:i A', strtotime($time)),
          'date' => date('F j, Y', strtotime($date)),
          'date_key' => $dateKey,
          'delivery_type' => ucfirst($order['delivery_type']),
          'amount' => formatPeso($order['total_amount']),
          'timestamp' => $timestamp,
          'schedule' => formatSchedule($order)
        ];
      }
    }
    usort($scheduleItems, function ($a, $b) {
      return $a['timestamp'] <=> $b['timestamp'];
    });
  ?>
  <div class="page-container">
    <div class="page-header">
      <div class="back-arrow" onclick="window.location.href='admin_dashboard.php'">‹</div>
      <div class="header-title">Receive & Pickup Orders</div>
    </div>

    <div class="dashboard-grid">
      <section class="panel calendar-panel">
        <div class="panel-padding">
          <div class="calendar-header">
            <div>
              <div class="calendar-title">Calendar</div>
              <div class="calendar-subtitle">Schedule overview</div>
            </div>
            <div class="calendar-nav">
              <select id="yearSelect" class="nav-button" style="width: auto; padding: 8px 12px; font-size: 14px;">
                <?php for($y = 2020; $y <= 2030; $y++): ?>
                  <option value="<?php echo $y; ?>" <?php echo $y == $selectedYear ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
              </select>
              <button class="nav-button" id="prevMonth" aria-label="Previous month">‹</button>
              <button class="nav-button" id="nextMonth" aria-label="Next month">›</button>
            </div>
          </div>
          <div class="calendar-subtitle"><?php echo $calendarLabel; ?></div>
          <div class="calendar-grid">
            <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $label): ?>
              <div class="calendar-day-label"><?php echo $label; ?></div>
            <?php endforeach; ?>
            <?php for ($blank = 1; $blank < $startOffset; $blank++): ?>
              <div></div>
            <?php endfor; ?>
            <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
              <?php $isCurrent = $day === $currentDay && $isCurrentMonth; ?>
              <?php $isActive = $isCurrent || (!$isCurrentMonth && $day === 1); ?>
              <?php $dateKey = sprintf('%04d-%02d-%02d', $calendarYear, $calendarMonth, $day); ?>
              <?php $statuses = $scheduledDates[$dateKey] ?? []; ?>
              <div class="calendar-cell enabled <?php echo $isCurrent ? 'current ' : ''; ?><?php echo $isActive ? 'active' : ''; ?> <?php echo in_array('shipped', $statuses) ? 'has-receive' : ''; ?> <?php echo in_array('pickup', $statuses) ? 'has-pickup' : ''; ?>" data-date="<?php echo $dateKey; ?>" role="button" tabindex="0">
                <?php echo $day; ?>
              </div>
            <?php endfor; ?>
          </div>
          <div class="calendar-footer">
            <div class="group-list">
              <div class="group-item"><span class="group-color" style="background:#2563eb"></span><span class="group-label">To Receive</span></div>
              <div class="group-item"><span class="group-color" style="background:#ec4899"></span><span class="group-label">Pickup</span></div>
            </div>
            <div class="calendar-empty">Tap any day to view schedule details.</div>
          </div>
        </div>
      </section>

      <section class="panel schedule-panel">
        <div class="panel-padding">
          <div class="day-header">
            <div class="day-title-wrap">
              <div id="selectedWeekday" class="day-label"><?php echo $currentWeekday; ?></div>
              <div id="selectedDateLabel" class="day-title"><?php echo $currentDayLabel; ?></div>
              <div id="selectedScheduleCount" class="day-subtitle">Total events: <?php echo count($scheduleItems); ?></div>
            </div>
          </div>

          <div class="schedule-list">
            <div class="schedule-card empty-state" style="display: <?php echo empty($scheduleItems) ? 'block' : 'none'; ?>;"><div class="schedule-card-title">No orders yet</div></div>
            <?php if (!empty($scheduleItems)): ?>
              <?php foreach ($scheduleItems as $item): ?>
                <div class="schedule-card <?php echo $item['status'] === 'pickup' ? 'pickup' : 'receive'; ?>" data-schedule-date="<?php echo htmlspecialchars($item['date_key']); ?>" data-href="admin_orders.php?order_id=<?php echo intval($item['order_id']); ?>">
                  <div class="schedule-card-header">
                    <div class="schedule-card-title"><?php echo htmlspecialchars($item['product_name']); ?></div>
                    <div class="schedule-card-badge <?php echo $item['status'] === 'pickup' ? 'badge-pickup' : 'badge-shipped'; ?>"><?php echo htmlspecialchars($item['label']); ?></div>
                  </div>
                  <div class="schedule-meta">
                    <div><strong>Time</strong><br><?php echo htmlspecialchars($item['when']); ?></div>
                    <div><strong>Date</strong><br><?php echo htmlspecialchars($item['date']); ?></div>
                    <div><strong>Delivery</strong><br><?php echo htmlspecialchars($item['delivery_type']); ?></div>
                    <div><strong>Total</strong><br><?php echo htmlspecialchars($item['amount']); ?></div>
                  </div>
                  <div class="schedule-actions">
                    <button class="btn btn-primary" onclick="window.location.href='admin_orders.php?order_id=<?php echo intval($item['order_id']); ?>'">Open Order</button>
                    <button class="btn btn-secondary" onclick="window.location.href='admin_orders.php?status=<?php echo $item['status'] === 'pickup' ? 'pickup' : 'shipped'; ?>'">View <?php echo htmlspecialchars($item['status'] === 'pickup' ? 'Pickup' : 'To Receive'); ?></button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </section>
    </div>
  </div>
  <script>
    (function() {
      const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
      const selectedWeekdayEl = document.getElementById('selectedWeekday');
      const selectedDateLabelEl = document.getElementById('selectedDateLabel');
      const selectedScheduleCountEl = document.getElementById('selectedScheduleCount');
      const noItemsMessage = document.querySelector('.calendar-empty');
      const emptyState = document.querySelector('.empty-state');
      const cells = Array.from(document.querySelectorAll('.calendar-cell.enabled'));
      const cards = Array.from(document.querySelectorAll('.schedule-card[data-schedule-date]'));

      function formatLabel(dateString) {
        const dt = new Date(dateString + 'T00:00:00');
        const day = dt.getDate();
        const month = dt.toLocaleString('en-US', { month: 'long' });
        const year = dt.getFullYear();
        const suffix = (day % 10 === 1 && day !== 11) ? 'st' : (day % 10 === 2 && day !== 12) ? 'nd' : (day % 10 === 3 && day !== 13) ? 'rd' : 'th';
        return `${day}${suffix} ${month} ${year}`;
      }

      function setSelected(dateString) {
        cells.forEach(cell => {
          cell.classList.toggle('active', cell.dataset.date === dateString);
        });

        const dt = new Date(dateString + 'T00:00:00');
        selectedWeekdayEl.textContent = dayNames[dt.getDay()];
        selectedDateLabelEl.textContent = formatLabel(dateString);

        let visibleCount = 0;
        cards.forEach(card => {
          const visible = card.dataset.scheduleDate === dateString;
          card.style.display = visible ? '' : 'none';
          if (visible) visibleCount += 1;
        });

        selectedScheduleCountEl.textContent = `Total events: ${visibleCount}`;
        if (noItemsMessage) {
          noItemsMessage.textContent = visibleCount === 0 ? 'No orders yet' : 'Tap any day to view schedule details.';
        }
        if (emptyState) {
          emptyState.style.display = visibleCount === 0 ? '' : 'none';
        }
      }

      cells.forEach(cell => {
        cell.addEventListener('click', () => setSelected(cell.dataset.date));
        cell.addEventListener('keydown', event => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            setSelected(cell.dataset.date);
          }
        });
      });

      cards.forEach(card => {
        card.addEventListener('click', event => {
          if (event.target.closest('button')) {
            return;
          }
          if (card.dataset.href) {
            window.location.href = card.dataset.href;
          }
        });
      });

      const initialDate = document.querySelector('.calendar-cell.active')?.dataset.date || cells[0]?.dataset.date;
      if (initialDate) {
        setSelected(initialDate);
      }

      // Navigation
      document.getElementById('prevMonth').addEventListener('click', () => {
        let month = <?php echo $selectedMonth; ?>;
        let year = <?php echo $selectedYear; ?>;
        month--;
        if (month < 1) {
          month = 12;
          year--;
        }
        window.location.href = `?month=${month}&year=${year}`;
      });

      document.getElementById('nextMonth').addEventListener('click', () => {
        let month = <?php echo $selectedMonth; ?>;
        let year = <?php echo $selectedYear; ?>;
        month++;
        if (month > 12) {
          month = 1;
          year++;
        }
        window.location.href = `?month=${month}&year=${year}`;
      });

      document.getElementById('yearSelect').addEventListener('change', (e) => {
        const year = e.target.value;
        window.location.href = `?month=<?php echo $selectedMonth; ?>&year=${year}`;
      });
    })();
  </script>
