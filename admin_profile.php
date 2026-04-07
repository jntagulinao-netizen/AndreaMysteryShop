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
$userId = intval($_SESSION['user_id']);

$createProfileTableSql = "CREATE TABLE IF NOT EXISTS `admin_profiles` (
  `user_id` int(11) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_admin_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
$conn->query($createProfileTableSql);

$updateMessage = '';
$updateError = '';
if (!empty($_SESSION['admin_profile_notice'])) {
  $updateMessage = (string)$_SESSION['admin_profile_notice'];
  unset($_SESSION['admin_profile_notice']);
}

define('PROFILE_PICTURES_DIR', __DIR__ . '/profile_pictures/');
if (!is_dir(PROFILE_PICTURES_DIR)) {
    mkdir(PROFILE_PICTURES_DIR, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_picture') {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedType = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!in_array($detectedType, $allowed, true)) {
            $updateError = 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.';
        } elseif ($file['size'] > $maxSize) {
            $updateError = 'File size exceeds 5MB limit.';
        } else {
            try {
                $oldPicStmt = $conn->prepare('SELECT profile_picture FROM admin_profiles WHERE user_id = ?');
                $oldPicStmt->bind_param('i', $userId);
                $oldPicStmt->execute();
                $oldPicResult = $oldPicStmt->get_result();
                $oldPic = $oldPicResult->fetch_assoc();
                $oldPicStmt->close();

                if ($oldPic && !empty($oldPic['profile_picture'])) {
                    $oldPath = PROFILE_PICTURES_DIR . basename($oldPic['profile_picture']);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $extMap = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/webp' => 'webp',
                ];
                $ext = $extMap[$detectedType] ?? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if ($ext === '') {
                    $ext = 'jpg';
                }

                $newFilename = 'admin_' . $userId . '_' . time() . '.' . $ext;
                $newPath = PROFILE_PICTURES_DIR . $newFilename;

                if (move_uploaded_file($file['tmp_name'], $newPath)) {
                    $conn->begin_transaction();

                    $checkStmt = $conn->prepare('SELECT user_id FROM admin_profiles WHERE user_id = ?');
                    $checkStmt->bind_param('i', $userId);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    $hasProfile = $checkResult->num_rows > 0;
                    $checkStmt->close();

                    if ($hasProfile) {
                        $picUpdateStmt = $conn->prepare('UPDATE admin_profiles SET profile_picture = ? WHERE user_id = ?');
                        $picUpdateStmt->bind_param('si', $newFilename, $userId);
                        $picUpdateStmt->execute();
                        $picUpdateStmt->close();
                    } else {
                        $picInsertStmt = $conn->prepare('INSERT INTO admin_profiles (user_id, profile_picture) VALUES (?, ?)');
                        $picInsertStmt->bind_param('is', $userId, $newFilename);
                        $picInsertStmt->execute();
                        $picInsertStmt->close();
                    }

                    $conn->commit();
                    $updateMessage = 'Profile picture uploaded successfully!';
                } else {
                    $updateError = 'Error uploading file.';
                }
            } catch (Exception $e) {
                if ($conn->errno) {
                    $conn->rollback();
                }
                $updateError = 'Error: ' . $e->getMessage();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');

    if ($fullName === '') {
        $updateError = 'Full name is required.';
    } elseif ($email === '') {
        $updateError = 'Email is required.';
    } else {
        try {
            $conn->begin_transaction();

            $userUpdateStmt = $conn->prepare('UPDATE users SET full_name = ?, email = ? WHERE user_id = ?');
            $userUpdateStmt->bind_param('ssi', $fullName, $email, $userId);
            $userUpdateStmt->execute();
            $userUpdateStmt->close();

            $checkStmt = $conn->prepare('SELECT user_id FROM admin_profiles WHERE user_id = ?');
            $checkStmt->bind_param('i', $userId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $hasProfile = $checkResult->num_rows > 0;
            $checkStmt->close();

            if ($hasProfile) {
                $profileUpdateStmt = $conn->prepare('UPDATE admin_profiles SET phone_number = ?, gender = ?, birthday = ? WHERE user_id = ?');
                $profileUpdateStmt->bind_param('sssi', $phone, $gender, $birthday, $userId);
                $profileUpdateStmt->execute();
                $profileUpdateStmt->close();
            } else {
                $profileInsertStmt = $conn->prepare('INSERT INTO admin_profiles (user_id, phone_number, gender, birthday) VALUES (?, ?, ?, ?)');
                $profileInsertStmt->bind_param('isss', $userId, $phone, $gender, $birthday);
                $profileInsertStmt->execute();
                $profileInsertStmt->close();
            }

            $conn->commit();
            $updateMessage = 'Profile updated successfully!';
            $_SESSION['user_name'] = $fullName;
        } catch (Exception $e) {
            if ($conn->errno) {
                $conn->rollback();
            }
            $updateError = 'Error updating profile: ' . $e->getMessage();
        }
    }
}

$userStmt = $conn->prepare('SELECT full_name, email FROM users WHERE user_id = ?');
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc() ?: ['full_name' => 'Admin', 'email' => ''];
$userStmt->close();

$profileStmt = $conn->prepare('SELECT phone_number, gender, birthday, profile_picture FROM admin_profiles WHERE user_id = ?');
$profileStmt->bind_param('i', $userId);
$profileStmt->execute();
$profileResult = $profileStmt->get_result();
$profile = $profileResult->fetch_assoc() ?: ['phone_number' => '', 'gender' => '', 'birthday' => '', 'profile_picture' => ''];
$profileStmt->close();

$isOwnerAdmin = false;
$isOwnerStmt = $conn->prepare("SELECT is_owner FROM users WHERE user_id = ? AND LOWER(role) = 'admin' LIMIT 1");
if ($isOwnerStmt) {
  $isOwnerStmt->bind_param('i', $userId);
  $isOwnerStmt->execute();
  $isOwnerResult = $isOwnerStmt->get_result();
  if ($isOwnerResult && ($isOwnerRow = $isOwnerResult->fetch_assoc())) {
    $isOwnerAdmin = ((int)($isOwnerRow['is_owner'] ?? 0) === 1);
  }
  $isOwnerStmt->close();
}
$showEditForm = ($updateError !== '');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Profile - Andrea Mystery Shop</title>
  <link rel="stylesheet" href="main.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding-bottom: 78px; }
    .page-container { width: calc(100% - 48px); margin: 0 auto; padding: 82px 0 16px; }

    .page-header {
      position: fixed;
      top: 16px;
      left: 50%;
      transform: translateX(-50%);
      width: calc(100% - 48px);
      background: #fff;
      z-index: 120;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      min-height: 58px;
      border-radius: 12px;
      border: 1px solid #eee;
    }
    .back-arrow { cursor: pointer; font-size: 24px; color: #333; padding: 4px; line-height: 1; }
    .header-title { font-size: 18px; font-weight: 600; color: #333; flex: 1; }
    .header-meta { font-size: 12px; color: #777; }

    .topbar-menu { position: relative; }
    .menu-trigger {
      width: 34px;
      height: 34px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background: #fff;
      color: #333;
      font-size: 18px;
      cursor: pointer;
      line-height: 1;
    }
    .menu-dropdown {
      position: absolute;
      top: calc(100% + 6px);
      right: 0;
      min-width: 170px;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
      display: none;
      z-index: 130;
      overflow: hidden;
    }
    .menu-dropdown.active { display: block; }
    .menu-dropdown a {
      display: block;
      padding: 10px 12px;
      color: #333;
      text-decoration: none;
      font-size: 13px;
      border-bottom: 1px solid #f0f0f0;
    }
    .menu-dropdown a:last-child { border-bottom: none; }
    .menu-dropdown a:hover { background: #f8f8f8; }

    .profile-card {
      position: fixed;
      top: 84px;
      left: 50%;
      transform: translateX(-50%);
      width: calc(100% - 48px);
      z-index: 110;
      background: #fff;
      border: 1px solid #eee;
      border-radius: 14px;
      box-shadow: 0 8px 20px rgba(0,0,0,.05);
      padding: 16px;
    }
    .profile-card.expanded {
      bottom: 68px;
      overflow-y: auto;
    }

    .alert { padding: 12px 14px; border-radius: 8px; margin-bottom: 12px; font-size: 13px; }
    .alert-success { background: #e6f9fd; border-left: 4px solid #0f9c71; color: #0f5541; }
    .alert-error { background: #fff1f1; border-left: 4px solid #c13030; color: #8b0000; }

    .profile-top {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 14px;
      flex-wrap: wrap;
    }
    .profile-picture-container { position: relative; width: 100px; height: 100px; }
    .profile-picture {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #e22a39;
      background: #f0f0f0;
    }
    #profilePictureInput {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      opacity: 0;
      cursor: pointer;
    }
    .profile-meta h2 { font-size: 22px; color: #333; margin-bottom: 4px; }
    .profile-meta p { font-size: 13px; color: #666; }
    .btn-edit {
      margin-top: 8px;
      border: 1px solid #e22a39;
      color: #e22a39;
      background: #fff;
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
    }
    .btn-owner-admin {
      margin-top: 8px;
      border: 1px solid #1f6fb2;
      color: #1f6fb2;
      background: #eef6ff;
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }

    .profile-form-dropdown {
      display: none;
      margin-top: 4px;
    }
    .profile-form-dropdown.show {
      display: block;
    }

    .profile-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }
    .profile-field { padding: 12px; background: #f9f9f9; border-radius: 10px; border: 1px solid #f0f0f0; }
    .profile-field label { display: block; font-size: 12px; font-weight: 600; color: #666; margin-bottom: 6px; text-transform: uppercase; }
    .profile-field input,
    .profile-field select {
      width: 100%;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 8px 10px;
      font-size: 14px;
      box-sizing: border-box;
      background: #fff;
    }
    .profile-field input:focus,
    .profile-field select:focus {
      outline: none;
      border-color: #e22a39;
      box-shadow: 0 0 0 2px rgba(226, 42, 57, 0.1);
    }

    .profile-actions { display: flex; gap: 10px; margin-top: 14px; }
    .profile-actions button {
      border: none;
      border-radius: 8px;
      padding: 10px 14px;
      font-weight: 700;
      font-size: 13px;
      cursor: pointer;
    }
    .btn-save { background: #e22a39; color: #fff; }
    .btn-save:hover { background: #c20000; }
    .btn-cancel { background: #f0f0f0; color: #333; border: 1px solid #ddd; }

    .mobile-bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; z-index: 999; background: #fff; border-top: 1px solid #ddd; }
    .mobile-bottom-nav.fixed { display: flex; }
    .mobile-nav-inner { display: flex; justify-content: space-around; align-items: center; padding: 0 6px; width: 100%; height: 50px; }
    .mobile-nav-inner a { text-decoration: none; color: #555; font-size: 11px; display: flex; flex-direction: column; align-items: center; gap: 4px; }
    .mobile-nav-inner a svg { width: 20px; height: 20px; stroke-width: 1.5; }
    .mobile-nav-inner a.active { color: #e22a39; }

    @media (max-width: 768px) {
      .page-container { width: calc(100% - 24px); padding-top: 74px; }
      .page-header { top: 8px; width: calc(100% - 24px); }
      .profile-card {
        top: 74px;
        width: calc(100% - 24px);
      }
      .profile-card.expanded {
        bottom: 88px;
      }
      .profile-grid { grid-template-columns: 1fr; }
      .header-meta { display: none; }
      .profile-top { align-items: flex-start; }
    }
  </style>
</head>
<body>
  <div class="page-container">
    <div class="page-header">
      <div class="back-arrow" onclick="window.location.href='admin_dashboard.php'">‹</div>
      <div class="header-title">Admin Profile</div>
      <div class="header-meta">Updated <?php echo date('d/m/Y H:i:s'); ?></div>
      <div class="topbar-menu">
        <button type="button" class="menu-trigger" onclick="toggleTopbarMenu(event)">...</button>
        <div class="menu-dropdown" id="topbarMenuDropdown">
          <a href="admin_dashboard.php">Admin Dashboard</a>
          <a href="messages.php">Messages</a>
          <a href="admin_orders.php">Admin Orders</a>
          <a href="admin_my_products.php">My Products</a>
          <a href="admin_product_drafts.php">Product Drafts</a>
          <a href="admin_my_products.php?view=archived">Archived Products</a>
          <a href="admin_manage_reviews.php">Manage Reviews</a>
          <a href="admin_profile.php">Admin Profile</a>
          <a href="logout.php">Logout</a>
        </div>
      </div>
    </div>

    <section id="profileCard" class="profile-card <?php echo $showEditForm ? 'expanded' : ''; ?>">
      <?php if ($updateMessage): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($updateMessage); ?></div>
      <?php endif; ?>

      <?php if ($updateError): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($updateError); ?></div>
      <?php endif; ?>

      <div class="profile-top">
        <div class="profile-picture-container">
          <form id="pictureUploadForm" method="POST" enctype="multipart/form-data" style="position:absolute;inset:0;z-index:2;">
            <input type="hidden" name="action" value="upload_picture">
            <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" onchange="this.form.submit()">
          </form>
          <img class="profile-picture" src="<?php
            $picPath = $profile['profile_picture'] ? 'profile_pictures/' . htmlspecialchars($profile['profile_picture']) : '';
            echo $picPath ? $picPath : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23ccc%22%3E%3Cpath d=%22M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z%22/%3E%3C/svg%3E';
          ?>" alt="Profile Picture">
        </div>
        <div class="profile-meta">
          <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
          <p><?php echo htmlspecialchars($user['email']); ?></p>
          <p>Tap profile photo to upload</p>
          <button type="button" class="btn-edit" id="editProfileToggle" onclick="toggleProfileForm()">Edit Profile</button>
          <?php if ($isOwnerAdmin): ?>
            <a href="owner_admin_access.php" class="btn-owner-admin">Administrative Page Access</a>
          <?php endif; ?>
        </div>
      </div>

      <div id="profileFormDropdown" class="profile-form-dropdown <?php echo $showEditForm ? 'show' : ''; ?>">
        <form method="POST">
          <input type="hidden" name="action" value="update_profile">
          <div class="profile-grid">
            <div class="profile-field">
              <label>Full Name</label>
              <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>
            <div class="profile-field">
              <label>Email Address</label>
              <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="profile-field">
              <label>Phone Number</label>
              <input type="tel" name="phone_number" value="<?php echo htmlspecialchars($profile['phone_number']); ?>" placeholder="Not provided">
            </div>
            <div class="profile-field">
              <label>Gender</label>
              <select name="gender">
                <option value="">Not specified</option>
                <option value="Male" <?php echo $profile['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo $profile['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo $profile['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
              </select>
            </div>
            <div class="profile-field">
              <label>Birthday</label>
              <input type="date" name="birthday" value="<?php echo htmlspecialchars($profile['birthday']); ?>">
            </div>
          </div>

          <div class="profile-actions">
            <button type="submit" class="btn-save">Save Changes</button>
            <button type="button" class="btn-cancel" onclick="toggleProfileForm(false)">Cancel</button>
          </div>
        </form>
      </div>
    </section>
  </div>

  <nav class="mobile-bottom-nav fixed">
    <div class="mobile-nav-inner">
      <a href="admin_dashboard.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10.5z" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        <span>Home</span>
      </a>
      <a href="admin_orders.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 8V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v1"></path><rect x="3" y="8" width="18" height="11" rx="2" ry="2"></rect></svg>
        <span>Orders</span>
      </a>
      <a href="admin_my_products.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 7l9-4 9 4-9 4-9-4z"></path><path d="M3 17l9 4 9-4"></path><path d="M3 12l9 4 9-4"></path></svg>
        <span>Products</span>
      </a>
      <a href="messages.php">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        <span>Messages</span>
      </a>
      <a href="admin_profile.php" class="active">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12c2.5 0 4.5-2 4.5-4.5S14.5 3 12 3 7.5 5 7.5 7.5 9.5 12 12 12z"></path><path d="M4 21c0-4.5 4-8 8-8s8 3.5 8 8"></path></svg>
        <span>Profile</span>
      </a>
    </div>
  </nav>

  <script>
    function toggleProfileForm(forceOpen) {
      const dropdown = document.getElementById('profileFormDropdown');
      const toggleButton = document.getElementById('editProfileToggle');
      const card = document.getElementById('profileCard');
      if (!dropdown || !toggleButton || !card) {
        return;
      }

      const shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : !dropdown.classList.contains('show');
      dropdown.classList.toggle('show', shouldOpen);
      card.classList.toggle('expanded', shouldOpen);
      toggleButton.textContent = shouldOpen ? 'Hide Edit Form' : 'Edit Profile';
    }

    document.addEventListener('DOMContentLoaded', function () {
      const dropdown = document.getElementById('profileFormDropdown');
      const toggleButton = document.getElementById('editProfileToggle');
      const card = document.getElementById('profileCard');
      if (dropdown && toggleButton && card) {
        card.classList.toggle('expanded', dropdown.classList.contains('show'));
        toggleButton.textContent = dropdown.classList.contains('show') ? 'Hide Edit Form' : 'Edit Profile';
      }

      const successAlert = document.querySelector('.alert-success');
      if (successAlert) {
        window.setTimeout(function () {
          successAlert.style.transition = 'opacity 0.3s ease';
          successAlert.style.opacity = '0';
          window.setTimeout(function () {
            if (successAlert.parentNode) {
              successAlert.parentNode.removeChild(successAlert);
            }
          }, 320);
        }, 2500);
      }
    });

    function toggleTopbarMenu(event) {
      event.stopPropagation();
      const dropdown = document.getElementById('topbarMenuDropdown');
      if (dropdown) {
        dropdown.classList.toggle('active');
      }
    }

    document.addEventListener('click', (event) => {
      const dropdown = document.getElementById('topbarMenuDropdown');
      const menu = document.querySelector('.topbar-menu');
      if (dropdown && menu && !menu.contains(event.target)) {
        dropdown.classList.remove('active');
      }
    });
  </script>
</body>
</html>
