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

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $slot_id = intval($_POST['slot_id'] ?? 0);
        $slot_date = trim($_POST['edit_slot_date'] ?? '');
        $slot_time = trim($_POST['edit_slot_time'] ?? '');
        $max_orders = intval($_POST['edit_max_orders'] ?? 5);

        if ($slot_id <= 0 || empty($slot_date) || empty($slot_time)) {
            $message = 'Invalid data for update.';
            $messageType = 'error';
        } elseif ($max_orders < 1 || $max_orders > 50) {
            $message = 'Maximum orders must be between 1 and 50.';
            $messageType = 'error';
        } else {
            // Check if another slot exists with same date/time
            $stmt = $conn->prepare('SELECT slot_id FROM delivery_slots WHERE slot_date = ? AND slot_time = ? AND slot_id != ?');
            $stmt->bind_param('ssi', $slot_date, $slot_time, $slot_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $message = 'Another slot with this date and time already exists.';
                $messageType = 'error';
            } else {
                // Update
                $stmt = $conn->prepare('UPDATE delivery_slots SET slot_date = ?, slot_time = ?, max_orders = ? WHERE slot_id = ?');
                $stmt->bind_param('ssii', $slot_date, $slot_time, $max_orders, $slot_id);
                if ($stmt->execute()) {
                    $message = 'Delivery slot updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update delivery slot.';
                    $messageType = 'error';
                }
            }
            $stmt->close();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $slot_id = intval($_POST['slot_id'] ?? 0);

        if ($slot_id <= 0) {
            $message = 'Invalid slot selected for deletion.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare('DELETE FROM delivery_slots WHERE slot_id = ?');
            $stmt->bind_param('i', $slot_id);
            if ($stmt->execute()) {
                $message = 'Delivery slot deleted successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete delivery slot.';
                $messageType = 'error';
            }
            $stmt->close();
        }
    } else {
        // Add logic (existing)
        $slot_date = trim($_POST['slot_date'] ?? '');
        $slot_time = trim($_POST['slot_time'] ?? '');
        $max_orders = intval($_POST['max_orders'] ?? 5);

        if (empty($slot_date) || empty($slot_time)) {
            $message = 'Please provide both date and time.';
            $messageType = 'error';
        } elseif ($max_orders < 1 || $max_orders > 50) {
            $message = 'Maximum orders must be between 1 and 50.';
            $messageType = 'error';
        } else {
            // Check if slot already exists
            $stmt = $conn->prepare('SELECT slot_id FROM delivery_slots WHERE slot_date = ? AND slot_time = ?');
            $stmt->bind_param('ss', $slot_date, $slot_time);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $message = 'A delivery slot with this date and time already exists.';
                $messageType = 'error';
            } else {
                // Insert new slot
                $stmt = $conn->prepare('INSERT INTO delivery_slots (slot_date, slot_time, max_orders) VALUES (?, ?, ?)');
                $stmt->bind_param('ssi', $slot_date, $slot_time, $max_orders);
                if ($stmt->execute()) {
                    $message = 'Delivery slot added successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to add delivery slot. Please try again.';
                    $messageType = 'error';
                }
            }
            $stmt->close();
        }
    }

    $_SESSION['delivery_slots_flash'] = [
        'type' => $messageType,
        'text' => $message,
    ];

    $redirectQuery = [];
    $postedFilterMonth = intval($_POST['filter_month'] ?? 0);
    $postedFilterYear = intval($_POST['filter_year'] ?? 0);
    if ($postedFilterMonth > 0) {
        $redirectQuery['filter_month'] = $postedFilterMonth;
    }
    if ($postedFilterYear > 0) {
        $redirectQuery['filter_year'] = $postedFilterYear;
    }
    $redirectUrl = 'admin_delivery_slots.php';
    if (!empty($redirectQuery)) {
        $redirectUrl .= '?' . http_build_query($redirectQuery);
    }

    header('Location: ' . $redirectUrl);
    exit;
}

if (isset($_SESSION['delivery_slots_flash'])) {
    $messageType = $_SESSION['delivery_slots_flash']['type'];
    $message = $_SESSION['delivery_slots_flash']['text'];
    unset($_SESSION['delivery_slots_flash']);
}

$filterMonth = intval($_GET['filter_month'] ?? 0);
$filterYear = intval($_GET['filter_year'] ?? 0);
$currentPage = max(1, intval($_GET['page'] ?? 1));
$slotsPerPage = 6;

$months = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December',
];

$yearOptions = [];
$yearResult = $conn->query('SELECT DISTINCT YEAR(slot_date) AS year FROM delivery_slots ORDER BY year DESC');
if ($yearResult) {
    while ($row = $yearResult->fetch_assoc()) {
        $yearOptions[] = intval($row['year']);
    }
    $yearResult->close();
}
if (empty($yearOptions)) {
    $yearOptions[] = intval(date('Y'));
}

function buildPageUrl($page, $filterMonth, $filterYear) {
    $query = ['page' => $page];
    if ($filterMonth > 0) {
        $query['filter_month'] = $filterMonth;
    }
    if ($filterYear > 0) {
        $query['filter_year'] = $filterYear;
    }
    return 'admin_delivery_slots.php?' . http_build_query($query);
}

$conditions = [];
$params = [];
$types = '';
if ($filterMonth > 0) {
    $conditions[] = 'MONTH(slot_date) = ?';
    $params[] = $filterMonth;
    $types .= 'i';
}
if ($filterYear > 0) {
    $conditions[] = 'YEAR(slot_date) = ?';
    $params[] = $filterYear;
    $types .= 'i';
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$countSql = 'SELECT COUNT(DISTINCT slot_date) AS total FROM delivery_slots ' . $where;
$stmt = $conn->prepare($countSql);
if ($stmt) {
    if ($types !== '') {
        $bindNames = array_merge([$types], $params);
        $refs = [];
        foreach ($bindNames as $key => $value) {
            $refs[$key] = &$bindNames[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    $stmt->execute();
    $countResult = $stmt->get_result();
    $totalRows = $countResult ? intval($countResult->fetch_assoc()['total'] ?? 0) : 0;
    $stmt->close();
} else {
    $totalRows = 0;
}

$totalPages = max(1, (int)ceil($totalRows / $slotsPerPage));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $slotsPerPage;

$dateSql = 'SELECT DISTINCT slot_date FROM delivery_slots ' . $where . ' ORDER BY slot_date ASC LIMIT ?, ?';
$stmt = $conn->prepare($dateSql);
$pageDates = [];
if ($stmt) {
    $bindParams = $params;
    $bindTypes = $types . 'ii';
    $bindParams[] = $offset;
    $bindParams[] = $slotsPerPage;
    $bindNames = array_merge([$bindTypes], $bindParams);
    $refs = [];
    foreach ($bindNames as $key => $value) {
        $refs[$key] = &$bindNames[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
    $stmt->execute();
    $dateResult = $stmt->get_result();
    if ($dateResult) {
        while ($row = $dateResult->fetch_assoc()) {
            $pageDates[] = $row['slot_date'];
        }
        $dateResult->close();
    }
    $stmt->close();
}

$slotsByDate = [];
if (!empty($pageDates)) {
    $placeholders = implode(',', array_fill(0, count($pageDates), '?')); 
    $fetchSql = 'SELECT * FROM delivery_slots WHERE slot_date IN (' . $placeholders . ') ORDER BY slot_date ASC, slot_time ASC';
    $stmt = $conn->prepare($fetchSql);
    if ($stmt) {
        $bindTypes = str_repeat('s', count($pageDates));
        $bindNames = array_merge([$bindTypes], $pageDates);
        $refs = [];
        foreach ($bindNames as $key => $value) {
            $refs[$key] = &$bindNames[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $escapedDates = array_map(function($date) use ($conn) {
            return "'" . $conn->real_escape_string($date) . "'";
        }, $pageDates);
        $result = $conn->query('SELECT * FROM delivery_slots WHERE slot_date IN (' . implode(',', $escapedDates) . ') ORDER BY slot_date ASC, slot_time ASC');
    }
} else {
    $result = false;
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $date = $row['slot_date'];
        if (!isset($slotsByDate[$date])) {
            $slotsByDate[$date] = [];
        }
        $slotsByDate[$date][] = $row;
    }
    $result->close();
}

// Categorize slots
$activeSlots = [];
$fullSlots = [];
foreach ($slotsByDate as $date => $daySlots) {
    $activeDay = [];
    $fullDay = [];
    foreach ($daySlots as $slot) {
        if ($slot['current_orders'] >= $slot['max_orders']) {
            $fullDay[] = $slot;
        } elseif ($slot['is_active']) {
            $activeDay[] = $slot;
        }
    }
    if (!empty($activeDay)) {
        $activeSlots[$date] = $activeDay;
    }
    if (!empty($fullDay)) {
        $fullSlots[$date] = $fullDay;
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Delivery Slots - Andrea Mystery Shop</title>
  <link rel="stylesheet" href="main.css">
  <link rel="stylesheet" href="assets/css/local_swal.css">
  <script src="assets/js/local_swal.js"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding-bottom: 78px; }

    .page-container { width: calc(100% - 48px); max-width: none; margin: 0 auto; padding: 98px 0 16px; }

    .page-header {
      position: fixed;
      top: 16px;
      left: 50%;
      transform: translateX(-50%);
      width: calc(100% - 48px);
      max-width: none;
      background: #fff;
      z-index: 120;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      border-radius: 12px;
      border: 1px solid #eee;
    }
    .back-arrow { cursor: pointer; font-size: 24px; color: #333; padding: 4px; line-height: 1; }
    .header-title { font-size: 18px; font-weight: 600; color: #333; flex: 1; }
    .header-meta { font-size: 12px; color: #777; }

    .content-section {
      background: #fff;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 16px;
      border: 1px solid #e5e7eb;
    }

    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-weight: 600; color: #333; margin-bottom: 4px; }
    .form-input {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
    }
    .form-input:focus { outline: none; border-color: #e22a39; }

    .btn {
      padding: 10px 16px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-primary { background: #e22a39; color: #fff; }
    .btn-primary:hover { background: #c81e2f; }
    .btn { background: #f5f5f5; color: #333; }
    .btn:hover { background: #e5e5e5; }

    .message {
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 16px;
      font-weight: 600;
    }
    .message.success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .message.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    .hidden { display: none; }

    .slots-list { margin-top: 24px; }
    .slots-list h3 { margin-bottom: 12px; font-size: 16px; color: #333; }
    .filter-buttons { display: flex; gap: 8px; margin-bottom: 16px; }
    .btn-filter {
      padding: 8px 16px;
      border: 1px solid #ddd;
      border-radius: 6px;
      background: #fff;
      color: #555;
      cursor: pointer;
      font-size: 14px;
      transition: background 0.2s;
    }
    .btn-filter.active { background: #e22a39; color: #fff; border-color: #e22a39; }
    .btn-filter:hover { background: #f5f5f5; }
    .btn-filter.active:hover { background: #c81e2f; }
    .filter-panel { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; margin-bottom: 16px; }
    .filter-form { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
    .filter-form .form-group { margin-bottom: 0; }
    .filter-select { min-width: 150px; }
    .pagination { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 18px; justify-content: flex-end; align-items: center; }
    .pagination-info { font-size: 12px; color: #64748b; margin-right: auto; }
    .pagination-link, .pagination-button { padding: 8px 14px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; color: #111827; text-decoration: none; cursor: pointer; transition: background 0.2s, border-color 0.2s; }
    .pagination-link:hover, .pagination-button:hover { background: #f9fafb; border-color: #cbd5e1; }
    .pagination-link.active { background: #e22a39; color: #fff; border-color: #e22a39; }
    .slots-section { display: none; }
    .slots-section.active { display: block; }
    .slot-item.full { background: #ffe6e6; border-color: #ff9999; }
    .slot-item {
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding: 16px;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      margin-bottom: 14px;
      background: #fff;
      box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
    }
    .slot-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      flex-wrap: wrap;
    }
    .slot-info {
      display: flex;
      flex-direction: column;
      gap: 10px;
      font-size: 14px;
      color: #374151;
      width: 100%;
      min-width: 0;
    }
    .slot-date-title {
      font-size: 15px;
      color: #111827;
    }
    .slot-select-wrapper {
      position: relative;
      width: 100%;
      max-width: 520px;
    }
    .slot-select-summary {
      display: flex;
      align-items: center;
      min-height: 44px;
      padding: 10px 14px;
      border: 1px solid #d1d5db;
      border-radius: 10px;
      background: #f8fafc;
      font-size: 13px;
      color: #111827;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .slot-select-summary::after {
      content: '\25BE';
      margin-left: auto;
      font-size: 12px;
      color: #888;
    }
    .slot-select {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      opacity: 0;
      cursor: pointer;
    }
    .slot-actions {
      display: flex;
      gap: 8px;
      align-items: center;
      justify-content: flex-end;
      margin-top: 0;
    }
    .btn-small {
      padding: 8px 14px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      font-size: 13px;
      cursor: pointer;
      background: #ffffff;
      color: #111827;
      transition: background 0.2s, border-color 0.2s;
    }
    .btn-small:hover {
      background: #f9fafb;
      border-color: #cbd5e1;
    }

    .edit-form-inline {
      margin-top: 14px;
      border-top: 1px solid #e5e7eb;
      padding-top: 14px;
    }
    .inline-row {
      display: grid;
      grid-template-columns: repeat(4, minmax(180px, 1fr));
      gap: 12px;
      align-items: end;
    }
    .inline-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .inline-input {
      width: 100%;
    }
    .inline-actions {
      display: flex;
      gap: 8px;
      align-items: center;
      justify-content: flex-end;
      min-width: 160px;
    }

    @media (max-width: 768px) {
      .page-container { width: calc(100% - 24px); }
      .slot-item {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
      }
      .slot-info {
        width: 100%;
        min-width: auto;
      }
      .slot-select-wrapper {
        max-width: 100%;
      }
      .slot-actions {
        width: 100%;
        justify-content: flex-start;
        flex-wrap: wrap;
      }
      .inline-row {
        grid-template-columns: 1fr;
      }
      .inline-actions {
        justify-content: flex-start;
      }
      .btn-small {
        flex: 1 1 48%;
        min-width: 120px;
      }
    }
  </style>
</head>
<body>
  <div class="page-container">
    <div class="page-header">
      <div class="back-arrow" onclick="window.location.href='admin_dashboard.php'">‹</div>
      <div class="header-title">Manage Delivery Slots</div>
    </div>

    <div class="content-section">
      <h2>Add New Delivery Slot</h2>
      <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="filter_month" value="<?php echo $filterMonth; ?>">
        <input type="hidden" name="filter_year" value="<?php echo $filterYear; ?>">
        <div class="form-group">
          <label class="form-label" for="slot_date">Delivery Date</label>
          <input type="date" id="slot_date" name="slot_date" class="form-input" required min="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="slot_time">Delivery Time</label>
          <input type="time" id="slot_time" name="slot_time" class="form-input" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="max_orders">Maximum Orders</label>
          <input type="number" id="max_orders" name="max_orders" class="form-input" min="1" max="50" value="5" required>
        </div>
        <button type="button" class="btn btn-primary" onclick="confirmAddSlot(this)">Add Slot</button>
      </form>
    </div>

    <form method="post" id="deleteForm" class="hidden">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="slot_id" id="delete_slot_id">
      <input type="hidden" name="filter_month" value="<?php echo $filterMonth; ?>">
      <input type="hidden" name="filter_year" value="<?php echo $filterYear; ?>">
    </form>

    <div class="content-section slots-list">
      <h3>Existing Delivery Slots</h3>
      <div class="filter-panel">
        <form method="get" class="filter-form">
          <div class="form-group">
            <label class="form-label" for="filter_month">Month</label>
            <select id="filter_month" name="filter_month" class="form-input filter-select" onchange="this.form.submit()">
              <option value="0">All months</option>
              <?php foreach ($months as $monthValue => $monthLabel): ?>
                <option value="<?php echo $monthValue; ?>" <?php echo $filterMonth === $monthValue ? 'selected' : ''; ?>><?php echo $monthLabel; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="filter_year">Year</label>
            <select id="filter_year" name="filter_year" class="form-input filter-select" onchange="this.form.submit()">
              <option value="0">All years</option>
              <?php foreach ($yearOptions as $yearOption): ?>
                <option value="<?php echo $yearOption; ?>" <?php echo $filterYear === $yearOption ? 'selected' : ''; ?>><?php echo $yearOption; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Filter</button>
          <button type="button" class="btn" onclick="window.location.href='admin_delivery_slots.php'">Reset</button>
        </form>
      </div>
      <div class="filter-buttons">
        <button class="btn-filter active" data-filter="all">All Slots</button>
        <button class="btn-filter" data-filter="active">Active Slots</button>
        <button class="btn-filter" data-filter="full">Full Slots</button>
      </div>

      <div class="slots-section active" id="all-slots">
        <h4>All Slots</h4>
        <?php if (empty($slotsByDate)): ?>
          <p>No delivery slots found.</p>
        <?php else: ?>
          <?php foreach ($slotsByDate as $date => $daySlots): ?>
            <div class="slot-item" data-date="<?php echo $date; ?>">
              <div class="slot-top">
                <div class="slot-info">
                  <strong class="slot-date-title"><?php echo date('M j, Y', strtotime($date)); ?></strong>
                  <div class="slot-select-wrapper">
                    <div class="slot-select-summary"></div>
                    <select class="slot-select" data-date="<?php echo $date; ?>" data-section="all" onchange="updateSlotSummary(this)">
                      <?php foreach ($daySlots as $slot): ?>
                        <option value="<?php echo $slot['slot_id']; ?>" data-time="<?php echo $slot['slot_time']; ?>" data-max="<?php echo $slot['max_orders']; ?>" data-active="<?php echo $slot['is_active']; ?>" data-summary="<?php echo date('g:i A', strtotime($slot['slot_time'])); ?> - Max: <?php echo $slot['max_orders']; ?>, Current: <?php echo $slot['current_orders']; ?>, Status: <?php echo $slot['is_active'] ? 'Active' : 'Inactive'; ?>">
                          <?php echo date('g:i A', strtotime($slot['slot_time'])); ?> - Max: <?php echo $slot['max_orders']; ?>, Current: <?php echo $slot['current_orders']; ?>, Status: <?php echo $slot['is_active'] ? 'Active' : 'Inactive'; ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="slot-actions">
                  <button class="btn-small" type="button" onclick="editSlot(getSelectedSlotId('<?php echo $date; ?>', 'all'))">Edit</button>
                  <button class="btn-small" type="button" onclick="deleteSlot(getSelectedSlotId('<?php echo $date; ?>', 'all'))">Delete</button>
                </div>
              </div>
              <div class="edit-form-inline hidden">
                <form method="post" class="inline-update-form">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="slot_id" class="edit_slot_id">
                  <input type="hidden" name="filter_month" value="<?php echo $filterMonth; ?>">
                  <input type="hidden" name="filter_year" value="<?php echo $filterYear; ?>">
                  <div class="inline-row">
                    <div class="form-group inline-group">
                      <label class="form-label">Delivery Date</label>
                      <input type="date" name="edit_slot_date" class="form-input inline-input" required>
                    </div>
                    <div class="form-group inline-group">
                      <label class="form-label">Delivery Time</label>
                      <input type="time" name="edit_slot_time" class="form-input inline-input" required>
                    </div>
                    <div class="form-group inline-group">
                      <label class="form-label">Maximum Orders</label>
                      <input type="number" name="edit_max_orders" class="form-input inline-input" min="1" max="50" required>
                    </div>
                    <div class="inline-actions">
                      <button type="button" class="btn btn-primary btn-small" onclick="confirmSaveSlot(this)">Save</button>
                      <button type="button" class="btn btn-small" onclick="cancelInlineEdit(this)">Cancel</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="slots-section" id="active-slots">
        <h4>Active Slots</h4>
        <?php if (empty($activeSlots)): ?>
          <p>No active delivery slots found.</p>
        <?php else: ?>
          <?php foreach ($activeSlots as $date => $daySlots): ?>
            <div class="slot-item" data-date="<?php echo $date; ?>">
              <div class="slot-top">
                <div class="slot-info">
                  <strong class="slot-date-title"><?php echo date('M j, Y', strtotime($date)); ?></strong>
                  <div class="slot-select-wrapper">
                    <div class="slot-select-summary"></div>
                    <select class="slot-select" data-date="<?php echo $date; ?>" data-section="active" onchange="updateSlotSummary(this)">
                      <?php foreach ($daySlots as $slot): ?>
                        <option value="<?php echo $slot['slot_id']; ?>" data-time="<?php echo $slot['slot_time']; ?>" data-max="<?php echo $slot['max_orders']; ?>" data-active="<?php echo $slot['is_active']; ?>" data-summary="<?php echo date('g:i A', strtotime($slot['slot_time'])); ?> - Max: <?php echo $slot['max_orders']; ?>, Current: <?php echo $slot['current_orders']; ?>, Status: Active">
                          <?php echo date('g:i A', strtotime($slot['slot_time'])); ?> - Max: <?php echo $slot['max_orders']; ?>, Current: <?php echo $slot['current_orders']; ?>, Status: Active
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="slot-actions">
                  <button class="btn-small" type="button" onclick="editSlot(getSelectedSlotId('<?php echo $date; ?>', 'active'))">Edit</button>
                  <button class="btn-small" type="button" onclick="deleteSlot(getSelectedSlotId('<?php echo $date; ?>', 'active'))">Delete</button>
                </div>
              </div>
              <div class="edit-form-inline hidden">
                <form method="post" class="inline-update-form">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="slot_id" class="edit_slot_id">
                  <input type="hidden" name="filter_month" value="<?php echo $filterMonth; ?>">
                  <input type="hidden" name="filter_year" value="<?php echo $filterYear; ?>">
                  <div class="inline-row">
                    <div class="form-group inline-group">
                      <label class="form-label">Delivery Date</label>
                      <input type="date" name="edit_slot_date" class="form-input inline-input" required>
                    </div>
                    <div class="form-group inline-group">
                      <label class="form-label">Delivery Time</label>
                      <input type="time" name="edit_slot_time" class="form-input inline-input" required>
                    </div>
                    <div class="form-group inline-group">
                      <label class="form-label">Maximum Orders</label>
                      <input type="number" name="edit_max_orders" class="form-input inline-input" min="1" max="50" required>
                    </div>
                    <div class="inline-actions">
                      <button type="button" class="btn btn-primary btn-small" onclick="confirmSaveSlot(this)">Save</button>
                      <button type="button" class="btn btn-small" onclick="cancelInlineEdit(this)">Cancel</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="slots-section" id="full-slots">
        <h4>Full Slots</h4>
        <?php if (empty($fullSlots)): ?>
          <p>No full delivery slots found.</p>
        <?php else: ?>
          <?php foreach ($fullSlots as $date => $daySlots): ?>
            <div class="slot-item full" data-date="<?php echo $date; ?>">
              <div class="slot-top">
                <div class="slot-info">
                  <strong class="slot-date-title"><?php echo date('M j, Y', strtotime($date)); ?></strong>
                  <div class="slot-select-wrapper">
                    <div class="slot-select-summary"></div>
                    <select class="slot-select" data-date="<?php echo $date; ?>" data-section="full" onchange="updateSlotSummary(this)">
                      <?php foreach ($daySlots as $slot): ?>
                        <option value="<?php echo $slot['slot_id']; ?>" data-time="<?php echo $slot['slot_time']; ?>" data-max="<?php echo $slot['max_orders']; ?>" data-active="<?php echo $slot['is_active']; ?>" data-summary="<?php echo date('g:i A', strtotime($slot['slot_time'])); ?> - Max: <?php echo $slot['max_orders']; ?>, Current: <?php echo $slot['current_orders']; ?>, Status: Full">
                          <?php echo date('g:i A', strtotime($slot['slot_time'])); ?> - Max: <?php echo $slot['max_orders']; ?>, Current: <?php echo $slot['current_orders']; ?>, Status: Full
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="slot-actions">
                  <button class="btn-small" type="button" onclick="editSlot(getSelectedSlotId('<?php echo $date; ?>', 'full'))">Edit</button>
                  <button class="btn-small" type="button" onclick="deleteSlot(getSelectedSlotId('<?php echo $date; ?>', 'full'))">Delete</button>
                </div>
              </div>
              <div class="edit-form-inline hidden">
                <form method="post" class="inline-update-form">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="slot_id" class="edit_slot_id">
                  <input type="hidden" name="filter_month" value="<?php echo $filterMonth; ?>">
                  <input type="hidden" name="filter_year" value="<?php echo $filterYear; ?>">
                  <div class="inline-row">
                    <div class="form-group inline-group">
                      <label class="form-label">Delivery Date</label>
                      <input type="date" name="edit_slot_date" class="form-input inline-input" required>
                    </div>
                    <div class="form-group inline-group">
                      <label class="form-label">Delivery Time</label>
                      <input type="time" name="edit_slot_time" class="form-input inline-input" required>
                    </div>
                    <div class="form-group inline-group">
                      <label class="form-label">Maximum Orders</label>
                      <input type="number" name="edit_max_orders" class="form-input inline-input" min="1" max="50" required>
                    </div>
                    <div class="inline-actions">
                      <button type="button" class="btn btn-primary btn-small" onclick="confirmSaveSlot(this)">Save</button>
                      <button type="button" class="btn btn-small" onclick="cancelInlineEdit(this)">Cancel</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      </div>
      <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <div class="pagination-info">Showing page <?php echo $currentPage; ?> of <?php echo $totalPages; ?> • <?php echo $totalRows; ?> dates</div>
          <?php if ($currentPage > 1): ?>
            <a class="pagination-link" href="<?php echo buildPageUrl($currentPage - 1, $filterMonth, $filterYear); ?>">Previous</a>
          <?php endif; ?>
          <?php for ($page = 1; $page <= $totalPages; $page++): ?>
            <a class="pagination-link <?php echo $page === $currentPage ? 'active' : ''; ?>" href="<?php echo buildPageUrl($page, $filterMonth, $filterYear); ?>"><?php echo $page; ?></a>
          <?php endfor; ?>
          <?php if ($currentPage < $totalPages): ?>
            <a class="pagination-link" href="<?php echo buildPageUrl($currentPage + 1, $filterMonth, $filterYear); ?>">Next</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function getSelectedSlotId(date, section) {
      const select = document.querySelector(`.slot-select[data-date="${date}"][data-section="${section}"]`);
      return select ? select.value : null;
    }

    function editSlot(slotId) {
      if (!slotId) {
        if (window.localSwalAlert) {
          window.localSwalAlert('warning', 'Select a Slot', 'Please select a slot first.');
        } else {
          alert('Please select a slot first.');
        }
        return;
      }
      const option = document.querySelector(`option[value="${slotId}"]`);
      if (!option) return;
      const slotItem = option.closest('.slot-item');
      if (!slotItem) return;
      const time = option.getAttribute('data-time');
      const max = option.getAttribute('data-max');
      const editForm = slotItem.querySelector('.edit-form-inline');
      if (!editForm) return;

      // Check if the form is already visible (not hidden)
      if (!editForm.classList.contains('hidden')) {
        // If visible, hide it (close the edit form)
        editForm.classList.add('hidden');
        return;
      }

      // Otherwise, show it
      document.querySelectorAll('.edit-form-inline').forEach(form => form.classList.add('hidden'));
      editForm.classList.remove('hidden');
      editForm.querySelector('.edit_slot_id').value = slotId;
      editForm.querySelector('[name="edit_slot_date"]').value = slotItem.getAttribute('data-date');
      editForm.querySelector('[name="edit_slot_time"]').value = time;
      editForm.querySelector('[name="edit_max_orders"]').value = max;
      editForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function cancelInlineEdit(button) {
      const formWrapper = button.closest('.edit-form-inline');
      if (formWrapper) {
        formWrapper.classList.add('hidden');
      }
    }

    function deleteSlot(slotId) {
      if (!slotId) {
        if (window.localSwalAlert) {
          window.localSwalAlert('warning', 'Select a Slot', 'Please select a slot first.');
        } else {
          alert('Please select a slot first.');
        }
        return;
      }

      const confirmMessage = 'Delete this delivery slot? This action cannot be undone.';
      if (window.localSwalConfirm) {
        window.localSwalConfirm('Confirm Delete', confirmMessage, 'Delete').then((confirmed) => {
          if (confirmed) {
            document.getElementById('delete_slot_id').value = slotId;
            document.getElementById('deleteForm').submit();
          }
        });
      } else if (confirm(confirmMessage)) {
        document.getElementById('delete_slot_id').value = slotId;
        document.getElementById('deleteForm').submit();
      }
    }

    function confirmSaveSlot(button) {
      const form = button.closest('form');
      const confirmMessage = 'Are you sure you want to update this delivery slot?';
      if (window.localSwalConfirm) {
        window.localSwalConfirm('Confirm Update', confirmMessage, 'Update').then((confirmed) => {
          if (confirmed) {
            form.submit();
          }
        });
      } else if (confirm(confirmMessage)) {
        form.submit();
      }
    }

    function confirmAddSlot(button) {
      const form = button.closest('form');
      const confirmMessage = 'Are you sure you want to add this new delivery slot?';
      if (window.localSwalConfirm) {
        window.localSwalConfirm('Confirm Add', confirmMessage, 'Add').then((confirmed) => {
          if (confirmed) {
            form.submit();
          }
        });
      } else if (confirm(confirmMessage)) {
        form.submit();
      }
    }

    const pageMessage = <?php echo json_encode(['type' => $messageType, 'text' => $message]); ?>;

    document.addEventListener('DOMContentLoaded', function() {
      const filterButtons = document.querySelectorAll('.btn-filter');
      const sections = document.querySelectorAll('.slots-section');

      filterButtons.forEach(button => {
        button.addEventListener('click', function() {
          const filter = this.getAttribute('data-filter');

          // Update active button
          filterButtons.forEach(btn => btn.classList.remove('active'));
          this.classList.add('active');

          // Show/hide sections
          sections.forEach(section => {
            if (section.id === filter + '-slots') {
              section.classList.add('active');
            } else {
              section.classList.remove('active');
            }
          });
        });
      });

      document.querySelectorAll('.slot-select').forEach(select => updateSlotSummary(select));

      if (pageMessage && pageMessage.text) {
        if (window.localSwalAlert) {
          window.localSwalAlert(
            pageMessage.type === 'success' ? 'success' : 'error',
            pageMessage.type === 'success' ? 'Success' : 'Error',
            pageMessage.text
          );
          const messageElement = document.querySelector('.message');
          if (messageElement) {
            messageElement.style.display = 'none';
          }
        }
      }
    });

    function updateSlotSummary(select) {
      const wrapper = select.closest('.slot-select-wrapper');
      if (!wrapper) return;
      const summary = wrapper.querySelector('.slot-select-summary');
      const option = select.selectedOptions[0];
      if (!summary || !option) return;
      summary.textContent = option.dataset.summary || '';
    }
  </script>
</body>
</html>