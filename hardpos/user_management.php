<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Set theme from session or default to dark
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'dark';
}
 $currentTheme = $_SESSION['theme'];

 $displayName = $_SESSION['user']['username'] ?? $_SESSION['username'] ?? 'Guest';

// Get current user's data including photo
 $currentUserId = $_SESSION['user']['id'] ?? 0;
 $currentUser = null;
if ($currentUserId) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentUser = $result->fetch_assoc();
}

// Create uploads directory if it doesn't exist
 $uploadDir = 'uploads/users/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* --- CRUD Actions --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $conn->real_escape_string($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role     = $conn->real_escape_string($_POST['role']);
        
        // Handle photo upload
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['photo']['tmp_name'];
            $fileName = $_FILES['photo']['name'];
            $fileSize = $_FILES['photo']['size'];
            $fileType = $_FILES['photo']['type'];
            
            // Check if file is an image
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($fileType, $allowedTypes) && $fileSize < 5000000) { // 5MB limit
                $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid() . '.' . $fileExt;
                $destPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $photoPath = $destPath;
                }
            }
        }
        
        $conn->query("INSERT INTO users (username, password, role, photo) VALUES ('$username', '$password', '$role', '$photoPath')");
    }
    if (isset($_POST['delete_user'])) {
        $id = intval($_POST['id']);
        
        // Get photo path before deletion
        $result = $conn->query("SELECT photo FROM users WHERE id=$id");
        if ($result && $row = $result->fetch_assoc()) {
            $photoPath = $row['photo'];
            // Delete photo file if it exists
            if ($photoPath && file_exists($photoPath)) {
                unlink($photoPath);
            }
        }
        
        $conn->query("DELETE FROM users WHERE id=$id");
    }
}
 $users = $conn->query("SELECT * FROM users ORDER BY id ASC");

// Get recent transactions for right panel
 $recentTransactions = $conn->query("
    SELECT s.id, s.total_amount, s.created_at, c.name as customer_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    ORDER BY s.created_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Management - POS</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Global - same as dashboard */
    :root{
      /* Dark mode variables (default) */
      --bg: #1F1F23;       /* main background */
      --sidebar: #1B1E22;  /* sidebar background */
      --card: #26292E;     /* card background */
      --accent: #F25F3A;   /* orange accent */
      --muted: #8C8D91;
      --text-light: #C7C8CE;
      --text-dark: #ffffff;
      --border-light: rgba(255,255,255,0.03);
      --shadow-light: rgba(255,255,255,0.08);
      --shadow-dark: rgba(0,0,0,0.45);
      --neu-shadow: 6px 6px 12px rgba(0,0,0,0.6), -6px -6px 12px rgba(255,255,255,0.02);
      --neu-active-shadow: -4px -4px 6px rgba(255,255,255,0.08), 4px 4px 6px rgba(0,0,0,0.45);
      --sidebar-icon: #9a9ca0;
      --sidebar-hover: #ffffff;
      --tooltip-bg: rgba(0, 0, 0, 0.85);
      --modal-bg: rgba(0,0,0,0.7);
    }

    /* Light mode variables */
    body.light-mode {
      --bg: #f5f5f5;       /* main background */
      --sidebar: #ffffff;  /* sidebar background */
      --card: #ffffff;     /* card background */
      --accent: #F25F3A;   /* orange accent */
      --muted: #666666;
      --text-light: #333333;
      --text-dark: #ffffff;
      --border-light: rgba(0,0,0,0.1);
      --shadow-light: rgba(255,255,255,0.8);
      --shadow-dark: rgba(0,0,0,0.1);
      --neu-shadow: 6px 6px 12px rgba(0,0,0,0.1), -6px -6px 12px rgba(255,255,255,0.8);
      --neu-active-shadow: -4px -4px 6px rgba(255,255,255,0.8), 4px 4px 6px rgba(0,0,0,0.1);
      --sidebar-icon: #666666;
      --sidebar-hover: #333333;
      --tooltip-bg: rgba(0, 0, 0, 0.8);
      --modal-bg: rgba(0,0,0,0.5);
    }
    
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family: "Segoe UI", Roboto, sans-serif;
      background:var(--bg);
      color:var(--text-light);
      display:flex;
      transition: background-color 0.3s, color 0.3s;
    }

    /* ---- Sidebar (thin) ---- */
    .sidebar{
      width:80px;
      background:var(--sidebar);
      position:fixed;
      top:0; bottom:0;
      padding-top:20px;
      display:flex;
      flex-direction:column;
      align-items:center;
      border:none;
      z-index: 20;
      transition: background-color 0.3s;
      box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }
    .sidebar .logo {
      margin-bottom:34px;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .sidebar .logo img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      object-fit: cover;
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }
    .sidebar ul{list-style:none;padding:0;margin:0;width:100%;}
    .sidebar li{width:100%;}
    .sidebar li a{
      display:flex;
      justify-content:center;
      align-items:center;
      padding:18px 0;
      color:var(--sidebar-icon);
      transition: color .15s ease, background .15s ease;
      font-size:20px;
      position:relative; /* needed for pseudo */
    }
    .sidebar li a i{display:block;}
    .sidebar li a:hover{ color:var(--sidebar-hover); background:transparent; }

    /* ACTIVE style: orange icon + vertical rounded bar to right of sidebar */
    .sidebar li a.active{ 
      color: var(--accent);
      box-shadow: var(--neu-active-shadow);
    } 
    .sidebar li a.active::after {
      content: "";
      position: absolute;
      left: 100%;
      top: 50%;
      transform: translateY(-50%);
      width: 12px;
      height: 48px;
      background: #F25F3A;
      border-radius: 10px;
      box-shadow: var(--neu-active-shadow);
    }

/* Tooltip styles */
.sidebar li a {
  position: relative; /* Needed for tooltip positioning */
}

.sidebar li a .tooltip {
  position: absolute;
  left: 100%; /* Position to the right of the icon */
  top: 50%;
  transform: translateY(-50%);
  margin-left: 15px; /* Space between icon and tooltip */
  padding: 6px 12px;
  background-color: var(--tooltip-bg);
  color: white;
  border-radius: 6px;
  white-space: nowrap;
  font-size: 14px;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s, visibility 0.3s;
  z-index: 100;
  pointer-events: none; /* Prevent tooltip from interfering with hover */
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
}

/* Show tooltip on hover */
.sidebar li a:hover .tooltip {
  opacity: 1;
  visibility: visible;
}

/* Add a small arrow to the tooltip */
.sidebar li a .tooltip::before {
  content: "";
  position: absolute;
  top: 50%;
  left: -5px;
  transform: translateY(-50%);
  border-width: 5px 5px 5px 0;
  border-style: solid;
  border-color: transparent var(--tooltip-bg) transparent transparent;
}

/* Prevent tooltip from going off-screen on right side */
.sidebar li a .tooltip {
  max-width: 200px;
  white-space: normal;
  text-align: center;
}

    /* Layout: shift entire content to account for 80px sidebar */
    .wrapper{
      margin-left:80px;
      flex:1;
      display:flex;
      min-height:100vh;
    }

    /* Main column */
    .main{
      flex:1;
      padding:20px;
      display:flex;
      flex-direction:column;
    }

    /* Neumorphic base for cards */
    .neu{
      background: var(--card);
      border-radius:16px;
      box-shadow: var(--neu-shadow);
      transition: all 0.18s ease;
    }

    /* Topbar */
    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:22px;
    }
    .topbar h1{font-size:22px;margin:0;font-weight:600;color:var(--text-light);}
    .clock-card{
      padding:8px 18px;
      font-size:15px;
      color:var(--accent);
      font-weight:600;
      text-align:center;
    }

    /* Theme toggle button */
    .theme-toggle {
      background: none;
      border: none;
      color: var(--text-light);
      font-size: 20px;
      cursor: pointer;
      padding: 8px;
      margin-right: 15px;
      border-radius: 50%;
      transition: background 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
    }

    .theme-toggle:hover {
      background: var(--border-light);
    }

    /* Form styling */
    .add-user-form {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 20px;
      padding: 16px;
      background: var(--card);
      border-radius:16px;
      box-shadow: var(--neu-shadow);
    }
    
    .form-group {
      display: flex;
      flex-direction: column;
    }
    
    .form-group label {
      font-size: 12px;
      margin-bottom: 4px;
      color: var(--muted);
    }
    
    /* Enhanced Neumorphic Inputs */
    .neu-input, .neu-select {
      padding: 10px 12px;
      border: none;
      border-radius: 12px;
      font-size: 14px;
      background: var(--bg);
      color: var(--text-light);
      box-shadow: 
        inset 4px 4px 8px rgba(0,0,0,0.4),
        inset -4px -4px 8px rgba(255,255,255,0.05);
      transition: all 0.2s ease;
      outline: none;
      min-width: 180px;
    }
    
    .neu-input:focus, .neu-select:focus {
      box-shadow: 
        inset 2px 2px 4px rgba(0,0,0,0.4),
        inset -2px -2px 4px rgba(255,255,255,0.05),
        0 0 0 1px var(--accent);
    }
    
    /* Special styling for select */
    .neu-select {
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23C7C8CE' d='M10.293 3.293L6 7.586 1.707 3.293A1 1 0 00.293 4.707l5 5a1 1 0 001.414 0l5-5a1 1 0 10-1.414-1.414z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      padding-right: 30px;
    }
    
    /* Photo upload styling */
    .photo-upload {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
    }
    
    .photo-preview {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: var(--bg);
      border: 2px dashed var(--muted);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      box-shadow: 
        inset 4px 4px 8px rgba(0,0,0,0.4),
        inset -4px -4px 8px rgba(255,255,255,0.05);
    }
    
    .photo-preview img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .photo-preview i {
      font-size: 24px;
      color: var(--muted);
    }
    
    .file-input-wrapper {
      position: relative;
      overflow: hidden;
      display: inline-block;
    }
    
    .file-input-wrapper input[type=file] {
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
    }
    
    .file-input-label {
      padding: 6px 12px;
      background: var(--accent);
      color: white;
      border-radius: 8px;
      font-size: 12px;
      cursor: pointer;
      box-shadow: 
        4px 4px 8px rgba(0,0,0,0.3),
       -4px -4px 8px rgba(255,255,255,0.02);
      transition: all 0.2s;
    }
    
    .file-input-label:hover {
      background: #e04a2a;
    }
    
    /* Neumorphic buttons */
    .btn {
      padding: 10px 16px;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.2s;
      box-shadow: 
        6px 6px 12px rgba(0,0,0,0.3),
       -6px -6px 12px rgba(255,255,255,0.02);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      height: fit-content;
      align-self: flex-end;
    }
    
    .btn:hover {
      box-shadow: 
        inset 3px 3px 6px rgba(0,0,0,0.3),
        inset -3px -3px 6px rgba(255,255,255,0.02);
    }
    
    .btn-add {
      background: var(--accent);
    }
    
    .btn-add:hover {
      background: #e04a2a;
    }
    
    .btn-delete {
      background: #e74c3c;
    }
    
    .btn-delete:hover {
      background: #c0392b;
    }

    /* Table styling */
    .table-container {
      background: var(--card);
      border-radius:16px;
      box-shadow: var(--neu-shadow);
      padding: 20px;
      overflow: hidden;
    }
    
    .table-container h2 {
      margin-top: 0;
      margin-bottom: 16px;
      color: var(--text-light);
      font-size: 18px;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
    }
    
    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid var(--border-light);
    }
    
    th {
      color: var(--muted);
      font-weight: 600;
      font-size: 14px;
    }
    
    tr:hover {
      background: rgba(255,255,255,0.03);
    }
    
    /* User photo in table */
    .user-photo {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      box-shadow: var(--neu-shadow);
    }

    /* Badge styling */
    .badge {
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: bold;
      color: #fff;
      display: inline-block;
    }

    .badge.admin {
      background: #e74c3c; /* red */
    }

    .badge.cashier {
      background: #3498db; /* blue */
    }

    /* Right Panel */
    .right-panel{
      width:300px;
      padding:22px;
      border-left:1px solid var(--border-light);
      display:flex;
      flex-direction:column;
      gap:18px;
    }
    .profile-card{ 
      padding:18px; 
      text-align:center;
      background: var(--card);
      border-radius:16px;
      box-shadow: var(--neu-shadow);
    }
    .profile-card h3{ 
      margin:6px 0 0; 
      font-size:16px; 
      color:var(--text-light); 
    }
    .profile-card .role {
      color:var(--muted);
      font-size:13px;
      margin-top:6px;
    }
    .profile-photo {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      object-fit: cover;
      margin: 0 auto 8px;
      box-shadow: var(--neu-shadow);
      background: var(--bg);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .profile-photo i {
      color: var(--muted);
      font-size: 24px;
    }
    .events-card{ 
      padding:12px; 
      font-size:14px;
      background: var(--card);
      border-radius:16px;
      box-shadow: var(--neu-shadow);
    }
    .events-card h4{ 
      margin:0 0 10px; 
      color:var(--text-light); 
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .events-card ul{ 
      list-style:none; 
      padding:0; 
      margin:0; 
    }
    .events-card li{ 
      padding:10px 0; 
      border-bottom:1px solid var(--border-light); 
      color:var(--text-light); 
    }
    .events-card li small {
      color: var(--muted);
      font-size: 12px;
    }

    /* Responsive */
    @media(max-width:1000px){
      .right-panel{ display:none; }
      .add-user-form {
        flex-direction: column;
      }
      
      .neu-input, .neu-select {
        min-width: 100%;
      }
    }
    
    @media(max-width:600px){
      .table-container {
        padding: 15px;
      }
      
      th, td {
        padding: 8px;
        font-size: 13px;
      }
      
      .user-photo {
        width: 30px;
        height: 30px;
      }
    }
  </style>
</head>
<body class="<?php echo $currentTheme === 'light' ? 'light-mode' : ''; ?>">

<!-- Sidebar -->
<nav class="sidebar">
  <div class="logo">
    <img src="uploads/logo.png" alt="POS System Logo">
  </div>
  <ul>
    <li>
      <a href="dashboard.php" >
        <i class="fa fa-home"></i>
        <span class="tooltip">Dashboard</span>
      </a>
    </li>
    <li>
      <a href="inventory.php">
        <i class="fa fa-box"></i>
        <span class="tooltip">Inventory</span>
      </a>
    </li>
    <li>
      <a href="sales.php">
        <i class="fa fa-credit-card"></i>
        <span class="tooltip">Sales</span>
      </a>
    </li>
    <li>
      <a href="sales_history.php">
        <i class="fa fa-history"></i>
        <span class="tooltip">Sales History</span>
      </a>
    </li>
    <li>
      <a href="sales_report.php">
        <i class="fa fa-chart-line"></i>
        <span class="tooltip">Sales Report</span>
      </a>
    </li>
    <li>
      <a href="customers.php">
        <i class="fa fa-users"></i>
        <span class="tooltip">Customer Credit</span>
      </a>
    </li>
    <li>
      <a href="customer_loyalty.php" >
        <i class="fa fa-star"></i>
        <span class="tooltip">Loyalty Program</span>
      </a>
    </li>
    <li>
      <a href="user_management.php" class="active">
        <i class="fa fa-user-cog"></i>
        <span class="tooltip">User Management</span>
      </a>
    </li>
    <li>
      <a href="logout.php">
        <i class="fa fa-sign-out-alt"></i>
        <span class="tooltip">Logout</span>
      </a>
    </li>
  </ul>
</nav>

<div class="wrapper">
  <main class="main">
    <div class="topbar">
      <h1>User Management</h1>
      <div style="display: flex; align-items: center;">
        <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
          <i class="fas fa-<?php echo $currentTheme === 'light' ? 'sun' : 'moon'; ?>"></i>
        </button>
        <div class="clock-card neu">
          <span id="clock"></span>
        </div>
      </div>
    </div>

    <!-- Add User Form -->
    <form class="add-user-form" method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" class="neu-input" required>
      </div>
      
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" class="neu-input" required>
      </div>
      
      <div class="form-group">
        <label for="role">Role</label>
        <select id="role" name="role" class="neu-select">
          <option value="cashier">Cashier</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>Photo (optional)</label>
        <div class="photo-upload">
          <div class="photo-preview" id="photoPreview">
            <i class="fas fa-camera"></i>
          </div>
          <div class="file-input-wrapper">
            <label for="photo" class="file-input-label">Choose Photo</label>
            <input type="file" id="photo" name="photo" accept="image/*">
          </div>
        </div>
      </div>
      
      <button type="submit" name="add_user" class="btn btn-add">
        <i class="fas fa-user-plus"></i> Add User
      </button>
    </form>

    <!-- Users Table -->
    <div class="table-container">
      <h2><i class="fas fa-users-cog"></i> Users</h2>
      <table>
        <thead>
          <tr>
            <th>Photo</th>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Created At</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while($u=$users->fetch_assoc()): ?>
          <tr>
            <td>
              <?php if(!empty($u['photo']) && file_exists($u['photo'])): ?>
                <img src="<?= htmlspecialchars($u['photo']) ?>" alt="User Photo" class="user-photo">
              <?php else: ?>
                <div class="user-photo" style="background: var(--bg); display: flex; align-items: center; justify-content: center;">
                  <i class="fas fa-user" style="color: var(--muted);"></i>
                </div>
              <?php endif; ?>
            </td>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td>
              <?php if ($u['role'] === 'admin'): ?>
                <span class="badge admin">Admin</span>
              <?php elseif ($u['role'] === 'cashier'): ?>
                <span class="badge cashier">Cashier</span>
              <?php else: ?>
                <?= htmlspecialchars($u['role']) ?>
              <?php endif; ?>
            </td>
            <td><?= $u['created_at'] ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button type="submit" name="delete_user" class="btn btn-delete" onclick="return confirm('Delete this user?')">
                  <i class="fas fa-trash"></i> Delete
                </button>
              </form>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </main>

  <!-- Right Panel -->
  <aside class="right-panel">
    <div class="profile-card neu">
      <?php if ($currentUser && !empty($currentUser['photo']) && file_exists($currentUser['photo'])): ?>
        <img src="<?= htmlspecialchars($currentUser['photo']) ?>" alt="Profile Photo" class="profile-photo">
      <?php else: ?>
        <div class="profile-photo">
          <i class="fas fa-user"></i>
        </div>
      <?php endif; ?>
      <h3>Welcome, <?=htmlspecialchars($displayName)?></h3>
      <div class="role"><?= htmlspecialchars($currentUser['role'] ?? 'User') ?></div>
    </div>

    <div class="events-card neu">
      <h4><i class="fas fa-history"></i> Recent Transactions</h4>
      <ul>
        <?php while ($row = $recentTransactions->fetch_assoc()): ?>
          <li>
            #<?=$row['id']?> — ₱<?=number_format($row['total_amount'],2)?>
            <?php if($row['customer_name']): ?>
              <br><small><?=htmlspecialchars($row['customer_name'])?></small>
            <?php endif; ?>
            <br><small><?=date('M d, H:i', strtotime($row['created_at']))?></small>
          </li>
        <?php endwhile; ?>
      </ul>
    </div>
  </aside>
</div>

<script>
/* live clock */
function updateClock(){
  document.getElementById('clock').textContent =
    new Date().toLocaleString('en-PH',{hour12:false});
}
setInterval(updateClock,1000);
updateClock();

/* Theme toggle functionality */
const themeToggle = document.getElementById('theme-toggle');
const body = document.body;

themeToggle.addEventListener('click', () => {
  const isLight = body.classList.contains('light-mode');
  const newTheme = isLight ? 'dark' : 'light';
  
  // Make AJAX request to update the theme in the session
  fetch('set_theme.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'theme=' + newTheme
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Toggle the body class
      body.classList.toggle('light-mode');
      // Update the toggle icon
      themeToggle.innerHTML = '<i class="fas fa-' + (newTheme === 'light' ? 'sun' : 'moon') + '"></i>';
    }
  })
  .catch(error => console.error('Error setting theme:', error));
});

// Photo preview functionality
document.getElementById('photo').addEventListener('change', function(e) {
  const file = e.target.files[0];
  const preview = document.getElementById('photoPreview');
  
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
    }
    reader.readAsDataURL(file);
  } else {
    preview.innerHTML = '<i class="fas fa-camera"></i>';
  }
});
</script>
</body>
</html>