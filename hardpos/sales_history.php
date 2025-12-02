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

/* ---------- Date-Range Filtering ---------- */
 $start = $_GET['start_date'] ?? '';
 $end   = $_GET['end_date'] ?? '';

 $where = '';
if ($start && $end) {
    $s = $conn->real_escape_string($start);
    $e = $conn->real_escape_string($end);
    $where = "WHERE DATE(s.created_at) BETWEEN '$s' AND '$e'";
}

/* ---------- Main Query with Operator ---------- */
 $sql = "
  SELECT s.id,
         COALESCE(c.name,'Walk-in') AS customer_name,
         s.total_amount,
         s.paid_amount,
         s.payment_method,
         s.created_at,
         u.username AS operator_name
  FROM sales s
  LEFT JOIN customers c ON s.customer_id = c.id
  LEFT JOIN users u ON s.user_id = u.id
  $where
  ORDER BY s.created_at DESC";
 $result = $conn->query($sql) or die($conn->error);

/* ---------- Total in Range ---------- */
 $totalQ = $conn->query("SELECT SUM(total_amount) AS total FROM sales s $where");
 $totalSales = $totalQ->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Sales History - Hardware POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* Global */
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
  --input-bg: #0e0e0e;
  --input-shadow: inset 2px 2px 5px rgba(0,0,0,0.3), inset -2px -2px 5px rgba(255,255,255,0.05);
  --input-focus-shadow: inset 2px 2px 5px rgba(0,0,0,0.3), inset -2px -2px 5px rgba(255,255,255,0.05), 0 0 0 2px rgba(242, 95, 58, 0.3);
  --scrollbar-thumb: #444;
  --placeholder-bg: rgba(255,255,255,0.05);
}

/* Light mode variables */
body.light-mode {
  --bg: #f5f5f5;       /* main background */
  --sidebar: #ffffff;  /* sidebar background */
  --card: #ffffff;     /* card background */
  --accent: #F25F3A;   /* orange accent */
  --muted: #666666;
  --text-light: #333333;
  --text-dark: #333333;
  --border-light: rgba(0,0,0,0.1);
  --shadow-light: rgba(255,255,255,0.8);
  --shadow-dark: rgba(0,0,0,0.1);
  --neu-shadow: 6px 6px 12px rgba(0,0,0,0.1), -6px -6px 12px rgba(255,255,255,0.8);
  --neu-active-shadow: -4px -4px 6px rgba(255,255,255,0.8), 4px 4px 6px rgba(0,0,0,0.1);
  --sidebar-icon: #666666;
  --sidebar-hover: #333333;
  --tooltip-bg: rgba(0, 0, 0, 0.8);
  --modal-bg: rgba(0,0,0,0.5);
  --input-bg: #ffffff;
  --input-shadow: inset 2px 2px 5px rgba(0,0,0,0.1), inset -2px -2px 5px rgba(255,255,255,0.8);
  --input-focus-shadow: inset 2px 2px 5px rgba(0,0,0,0.1), inset -2px -2px 5px rgba(255,255,255,0.8), 0 0 0 2px rgba(242, 95, 58, 0.3);
  --scrollbar-thumb: #cccccc;
  --placeholder-bg: rgba(0,0,0,0.05);
}

*{box-sizing:border-box}
body{
  margin:0;
  font-family: "Segoe UI", Roboto, sans-serif;
  background:var(--bg);
  color:var(--text-light);
  display:flex;
  overflow-x: hidden; /* Prevent horizontal scrolling */
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
  width: calc(100% - 80px); /* Ensure wrapper doesn't exceed viewport width */
}

/* Main column */
.main{
  flex:1;
  padding:20px;
  display:flex;
  flex-direction:column;
  max-width: 100%; /* Prevent main content from exceeding viewport */
  height: calc(100vh - 40px); /* Full height minus topbar */
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
.topbar h1{font-size:22px;margin:0;font-weight:600;color:var(--text-dark);}
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

/* Top Section - Filter and Summary Side by Side */
.top-section {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
}

/* Filter Card */
.filter-card {
  flex: 2;
  padding: 20px;
}

.filter-card h2 {
  margin-top: 0;
  margin-bottom: 15px;
  color: var(--text-dark);
  font-size: 18px;
  border-bottom: 1px solid var(--border-light);
  padding-bottom: 10px;
}

.filter-bar {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
  align-items: flex-end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.filter-group label {
  font-size: 14px;
  color: var(--text-light);
}

.filter-group input {
  padding: 10px 15px;
  border: none;
  border-radius: 10px;
  background: var(--input-bg);
  color: var(--text-light);
  box-shadow: var(--input-shadow);
  outline: none;
}

.filter-actions {
  display: flex;
  gap: 10px;
  align-items: flex-end;
}

.filter-actions button, .filter-actions a {
  padding: 10px 20px;
  border: none;
  border-radius: 10px;
  background: var(--card);
  color: var(--text-light);
  font-weight: 600;
  cursor: pointer;
  box-shadow: var(--neu-shadow);
  transition: all 0.2s ease;
  text-decoration: none;
}

.filter-actions button:hover, .filter-actions a:hover {
  box-shadow: var(--neu-active-shadow);
}

/* Summary Card */
.summary-card {
  flex: 1;
  padding: 20px;
  text-align: center;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.summary-card h3 {
  margin: 0 0 5px;
  font-size: 28px;
  color: var(--accent);
}

.summary-card p {
  margin: 0;
  color: var(--muted);
  font-size: 14px;
}

/* Scrollable Table Container */
.table-scroll-container {
  flex: 1;
  background: var(--card);
  border-radius: 16px;
  padding: 20px;
  overflow: hidden;
  box-shadow: var(--neu-shadow);
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.table-header {
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--border-light);
}

.table-header h2 {
  margin: 0;
  color: var(--text-dark);
  font-size: 18px;
}

.table-wrapper {
  flex: 1;
  overflow-y: auto;
  /* Custom scrollbar */
  scrollbar-width: thin;
  scrollbar-color: var(--scrollbar-thumb) transparent;
}

.table-wrapper::-webkit-scrollbar {
  width: 8px;
}

.table-wrapper::-webkit-scrollbar-track {
  background: transparent;
}

.table-wrapper::-webkit-scrollbar-thumb {
  background-color: var(--scrollbar-thumb);
  border-radius: 4px;
}

.table-wrapper::-webkit-scrollbar-thumb:hover {
  background-color: var(--text-light);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid var(--border-light);
}

th {
  position: sticky;
  top: 0;
  background: var(--card);
  color: var(--accent);
  font-weight: 600;
  z-index: 10;
  box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

tr:last-child td {
  border-bottom: none;
}

tr:hover {
  background: var(--border-light);
}

tr.selected {
  background: rgba(242, 95, 58, 0.2);
}

tr.selected td {
  border-bottom: 1px solid rgba(242, 95, 58, 0.3);
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
  color:var(--text-dark); 
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
.sale-details-card{ 
  padding:18px; 
  font-size:14px;
  background: var(--card);
  border-radius:16px;
  box-shadow: var(--neu-shadow);
  height: 400px;
  display: flex;
  flex-direction: column;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
}
.sale-details-card:hover {
  box-shadow: 
    8px 8px 16px rgba(0,0,0,0.7),
   -8px -8px 16px rgba(255,255,255,0.03);
}
.sale-details-card h4{ 
  margin:0 0 15px; 
  color:var(--text-dark); 
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
}
.sale-details-card .receipt {
  background: linear-gradient(to bottom, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
  border-radius: 12px;
  padding: 15px;
  flex: 1;
  display: flex;
  flex-direction: column;
  border: 1px dashed var(--border-light);
  position: relative;
}
.sale-details-card .receipt::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 20px;
  background: repeating-linear-gradient(
    45deg,
    rgba(255,255,255,0.05),
    rgba(255,255,255,0.05) 5px,
    rgba(255,255,255,0.08) 5px,
    rgba(255,255,255,0.08) 10px
  );
  border-top-left-radius: 12px;
  border-top-right-radius: 12px;
}
.sale-details-card .receipt-header {
  text-align: center;
  margin-bottom: 15px;
  padding-top: 10px;
}
.sale-details-card .receipt-title {
  font-size: 14px;
  font-weight: bold;
  color: var(--accent);
  margin-bottom: 5px;
}
.sale-details-card .receipt-id {
  font-size: 12px;
  color: var(--muted);
}
.sale-details-card .receipt-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.sale-details-card .receipt-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.sale-details-card .receipt-label {
  color: var(--muted);
  font-size: 12px;
}
.sale-details-card .receipt-value {
  font-weight: 500;
  font-size: 13px;
}
.sale-details-card .receipt-divider {
  height: 1px;
  background: var(--border-light);
  margin: 10px 0;
}
.sale-details-card .receipt-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
  padding-top: 10px;
  border-top: 1px dashed var(--border-light);
}
.sale-details-card .receipt-total-label {
  font-weight: bold;
  font-size: 14px;
}
.sale-details-card .receipt-total-value {
  color: var(--accent);
  font-weight: bold;
  font-size: 16px;
}
.sale-details-card .receipt-footer {
  text-align: center;
  margin-top: 15px;
  font-size: 10px;
  color: var(--muted);
}
.sale-details-card .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: var(--muted);
  text-align: center;
}
.sale-details-card .empty-state i {
  font-size: 32px;
  margin-bottom: 10px;
}
.sale-details-card .click-hint {
  position: absolute;
  bottom: 10px;
  right: 10px;
  font-size: 10px;
  color: var(--muted);
  display: flex;
  align-items: center;
  gap: 4px;
  background: rgba(0,0,0,0.2);
  padding: 4px 8px;
  border-radius: 10px;
}

/* Modal */
.modal{
  display:none;
  position:fixed;
  z-index:999;
  left:0;
  top:0;
  width:100%;
  height:100%;
  background-color: var(--modal-bg);
  backdrop-filter: blur(4px);
}
.modal-content{
  margin: 5% auto;
  padding:25px;
  max-width:800px;
  max-width:90%;
  max-height:80vh;
  overflow:auto;
  background: var(--card);
  border-radius:16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
  animation: modalopen 0.4s;
}
@keyframes modalopen {
  from {opacity: 0; transform: translateY(-50px);}
  to {opacity: 1; transform: translateY(0);}
}
.modal-close{
  float:right;
  font-size:24px;
  cursor:pointer;
  color:var(--accent);
}
.modal h2 {
  margin-top:0;
  color:var(--text-dark);
  text-align:center;
  margin-bottom:20px;
}
.modal h3 {
  color:var(--accent);
  border-bottom:1px solid var(--border-light);
  padding-bottom:10px;
  margin-top:20px;
  margin-bottom:15px;
}
.modal .sale-info {
  display:grid;
  grid-template-columns: repeat(2, 1fr);
  gap:15px;
  margin-bottom:20px;
}
.modal .info-row {
  display:flex;
  justify-content:space-between;
}
.modal .info-label {
  color:var(--muted);
}
.modal .info-value {
  font-weight:500;
}
.modal .items-table {
  width:100%;
  border-collapse:collapse;
  margin-top:15px;
}
.modal .items-table th, 
.modal .items-table td {
  padding:12px 15px;
  text-align:left;
  border-bottom:1px solid var(--border-light);
}
.modal .items-table th {
  background:rgba(255,255,255,0.05);
  color:var(--accent);
  font-weight:600;
}
.modal .items-table tr:last-child td {
  border-bottom:none;
}
.modal .items-table .item-price {
  color:var(--accent);
  font-weight:bold;
}
.modal .total-row {
  display:flex;
  justify-content:space-between;
  margin-top:15px;
  padding-top:15px;
  border-top:1px solid var(--border-light);
  font-weight:bold;
  font-size:18px;
}
.modal .total-amount {
  color:var(--accent);
}

/* Table info footer */
.table-info {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid var(--border-light);
  font-size: 12px;
  color: var(--muted);
  text-align: center;
}

/* Loading spinner */
.loading {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255,255,255,0.3);
  border-radius: 50%;
  border-top-color: var(--accent);
  animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Responsive adjustments */
@media (max-width: 1200px) {
  .right-panel {
    display: none;
  }
}

@media (max-width: 768px) {
  .top-section {
    flex-direction: column;
  }
  
  .filter-bar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filter-actions {
    justify-content: stretch;
  }
  
  .filter-actions button, .filter-actions a {
    flex: 1;
    text-align: center;
  }
  
  .table-wrapper {
    overflow-x: auto;
  }
  
  .modal .sale-info {
    grid-template-columns: 1fr;
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
      <a href="sales_history.php" class="active">
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
      <a href="user_management.php">
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
      <h1>Sales History</h1>
      <div style="display: flex; align-items: center;">
        <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
          <i class="fas fa-<?php echo $currentTheme === 'light' ? 'sun' : 'moon'; ?>"></i>
        </button>
        <div id="clock" class="clock-card neu"></div>
      </div>
    </div>

    <!-- Top Section: Filter and Summary Side by Side -->
    <div class="top-section">
      <!-- Filter Card -->
      <div class="filter-card neu">
        <h2>Filter Sales Records</h2>
        <form method="get" class="filter-bar">
          <div class="filter-group">
            <label>From Date</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($start) ?>">
          </div>
          <div class="filter-group">
            <label>To Date</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($end) ?>">
          </div>
          <div class="filter-actions">
            <button type="submit">Apply Filter</button>
            <a href="sales_history.php" class="reset-btn">Reset</a>
          </div>
        </form>
      </div>

      <!-- Summary Card -->
      <div class="summary-card neu">
        <h3>₱<?= number_format($totalSales,2) ?></h3>
        <p>Total Sales in Selected Range</p>
      </div>
    </div>

    <!-- Scrollable Sales Table -->
    <div class="table-scroll-container neu">
      <div class="table-header">
        <h2>Sales Records</h2>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Total (₱)</th>
              <th>Paid (₱)</th>
              <th>Payment Method</th>
              <th>Date</th>
              <th>Operator</th>
            </tr>
          </thead>
          <tbody>
            <?php if($result && $result->num_rows > 0): ?>
              <?php while($row = $result->fetch_assoc()): ?>
                <tr class="sale-row" data-id="<?= $row['id'] ?>">
                  <td><?= $row['id'] ?></td>
                  <td><?= $row['customer_name'] ?></td>
                  <td><?= number_format($row['total_amount'],2) ?></td>
                  <td><?= number_format($row['paid_amount'],2) ?></td>
                  <td><?= $row['payment_method'] ?></td>
                  <td><?= $row['created_at'] ?></td>
                  <td><?= htmlspecialchars($row['operator_name'] ?? 'Unknown') ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="7">No sales records found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        <div class="table-info">
          <?php 
          $rowCount = $result ? $result->num_rows : 0;
          echo "Showing " . $rowCount . " records";
          ?>
        </div>
      </div>
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
      <div class="role"><?= htmlspecialchars($currentUser['role'] ?? 'Sales Associate') ?></div>
    </div>

    <div class="sale-details-card neu" id="saleDetailsCard">
      <h4><i class="fas fa-receipt"></i> Sale Details</h4>
      <div class="empty-state" id="emptyState">
        <i class="fas fa-mouse-pointer"></i>
        <p>Click on a sale record to view details</p>
      </div>
      <div id="saleDetailsContent" style="display: none;">
        <div class="receipt">
          <div class="receipt-header">
            <div class="receipt-title">SALE RECEIPT</div>
            <div class="receipt-id" id="receiptId">Sale #000</div>
          </div>
          
          <div class="receipt-body">
            <div class="receipt-row">
              <span class="receipt-label">Customer:</span>
              <span class="receipt-value" id="receiptCustomer"></span>
            </div>
            <div class="receipt-row">
              <span class="receipt-label">Payment:</span>
              <span class="receipt-value" id="receiptPayment"></span>
            </div>
            <div class="receipt-row">
              <span class="receipt-label">Date:</span>
              <span class="receipt-value" id="receiptDate"></span>
            </div>
            
            <div class="receipt-divider"></div>
            
            <div class="receipt-row">
              <span class="receipt-label">Items:</span>
              <span class="receipt-value" id="receiptItemCount">0</span>
            </div>
            
            <div class="receipt-total">
              <span class="receipt-total-label">TOTAL:</span>
              <span class="receipt-total-value" id="receiptTotal">₱0.00</span>
            </div>
          </div>
          
          <div class="receipt-footer">
            Thank you for your business!
          </div>
        </div>
        
        <div class="click-hint">
          <i class="fas fa-hand-pointer"></i>
          <span>Click for details</span>
        </div>
      </div>
    </div>
  </aside>
</div>

<!-- Sale Details Modal -->
<div id="saleDetailsModal" class="modal">
  <div class="modal-content">
    <span class="modal-close">&times;</span>
    <h2>Sale Details</h2>
    
    <div class="sale-info">
      <div class="info-row">
        <span class="info-label">Sale ID:</span>
        <span class="info-value" id="modalSaleId"></span>
      </div>
      <div class="info-row">
        <span class="info-label">Customer:</span>
        <span class="info-value" id="modalCustomerName"></span>
      </div>
      <div class="info-row">
        <span class="info-label">Date:</span>
        <span class="info-value" id="modalSaleDate"></span>
      </div>
      <div class="info-row">
        <span class="info-label">Payment Method:</span>
        <span class="info-value" id="modalPaymentMethod"></span>
      </div>
      <div class="info-row">
        <span class="info-label">Operator:</span>
        <span class="info-value" id="modalOperatorName"></span>
      </div>
      <div class="info-row">
        <span class="info-label">Total Amount:</span>
        <span class="info-value" id="modalTotalAmount"></span>
      </div>
    </div>
    
    <h3>Items Purchased</h3>
    <table class="items-table">
      <thead>
        <tr>
          <th>Product Name</th>
          <th>Quantity</th>
          <th>Price</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody id="modalItemsContainer">
        <!-- Items will be loaded here -->
      </tbody>
    </table>
    
    <div class="total-row">
      <span>Total:</span>
      <span class="total-amount" id="modalTotal"></span>
    </div>
  </div>
</div>

<script>
// Live clock
function updateClock(){
  document.getElementById('clock').textContent = new Date().toLocaleString('en-PH',{hour12:false});
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

// Store sale data globally for modal
let currentSaleData = null;

// Handle sale row clicks
document.querySelectorAll('.sale-row').forEach(row => {
  row.addEventListener('click', function() {
    // Remove selected class from all rows
    document.querySelectorAll('.sale-row').forEach(r => r.classList.remove('selected'));
    
    // Add selected class to clicked row
    this.classList.add('selected');
    
    // Get sale ID
    const saleId = this.getAttribute('data-id');
    
    // Show loading state
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('saleDetailsContent').style.display = 'none';
    
    // Create loading indicator
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'empty-state';
    loadingDiv.innerHTML = '<div class="loading"></div><p>Loading sale details...</p>';
    document.getElementById('saleDetailsCard').appendChild(loadingDiv);
    
    // Fetch sale details
    fetch(`get_sale_details.php?id=${saleId}`)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        // Remove loading indicator
        loadingDiv.remove();
        
        if (data.success) {
          // Store sale data globally
          currentSaleData = data;
          
          // Populate receipt details
          document.getElementById('receiptId').textContent = `Sale #${data.sale.id}`;
          document.getElementById('receiptCustomer').textContent = data.sale.customer_name;
          document.getElementById('receiptPayment').textContent = data.sale.payment_method;
          document.getElementById('receiptDate').textContent = new Date(data.sale.created_at).toLocaleDateString();
          document.getElementById('receiptItemCount').textContent = `${data.items.length} items`;
          document.getElementById('receiptTotal').textContent = `₱${parseFloat(data.sale.total_amount).toFixed(2)}`;
          
          // Show content
          document.getElementById('saleDetailsContent').style.display = 'flex';
        } else {
          // Show error message from server
          const errorDiv = document.createElement('div');
          errorDiv.className = 'empty-state';
          errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i><p>Error: ${data.message}</p>`;
          document.getElementById('saleDetailsCard').appendChild(errorDiv);
          
          // Remove error after 5 seconds
          setTimeout(() => {
            errorDiv.remove();
            document.getElementById('emptyState').style.display = 'flex';
          }, 5000);
        }
      })
      .catch(error => {
        // Remove loading indicator
        loadingDiv.remove();
        
        // Show error
        const errorDiv = document.createElement('div');
        errorDiv.className = 'empty-state';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i><p>Error: ${error.message}</p>`;
        document.getElementById('saleDetailsCard').appendChild(errorDiv);
        
        // Remove error after 5 seconds
        setTimeout(() => {
          errorDiv.remove();
          document.getElementById('emptyState').style.display = 'flex';
        }, 5000);
        
        console.error('Error:', error);
      });
  });
});

// Handle sale details card click to open modal
document.getElementById('saleDetailsCard').addEventListener('click', function() {
  if (currentSaleData) {
    // Populate modal with sale data
    document.getElementById('modalSaleId').textContent = currentSaleData.sale.id;
    document.getElementById('modalCustomerName').textContent = currentSaleData.sale.customer_name;
    document.getElementById('modalSaleDate').textContent = new Date(currentSaleData.sale.created_at).toLocaleString();
    document.getElementById('modalPaymentMethod').textContent = currentSaleData.sale.payment_method;
    document.getElementById('modalOperatorName').textContent = currentSaleData.sale.operator_name || 'Unknown';
    document.getElementById('modalTotalAmount').textContent = `₱${parseFloat(currentSaleData.sale.total_amount).toFixed(2)}`;
    
    // Populate items in modal
    const modalItemsContainer = document.getElementById('modalItemsContainer');
    modalItemsContainer.innerHTML = '';
    
    if (currentSaleData.items.length === 0) {
      modalItemsContainer.innerHTML = '<tr><td colspan="4">No items found for this sale</td></tr>';
    } else {
      currentSaleData.items.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${item.product_name}</td>
          <td>${parseFloat(item.quantity).toFixed(2)}</td>
          <td>₱${parseFloat(item.price).toFixed(2)}</td>
          <td class="item-price">₱${parseFloat(item.subtotal).toFixed(2)}</td>
        `;
        modalItemsContainer.appendChild(row);
      });
    }
    
    // Set total in modal
    document.getElementById('modalTotal').textContent = `₱${parseFloat(currentSaleData.sale.total_amount).toFixed(2)}`;
    
    // Show modal
    document.getElementById('saleDetailsModal').style.display = 'block';
  }
});

// Modal controls
document.querySelector('.modal-close').addEventListener('click', function() {
  document.getElementById('saleDetailsModal').style.display = 'none';
});

window.onclick = function(event) {
  const modal = document.getElementById('saleDetailsModal');
  if (event.target === modal) {
    modal.style.display = 'none';
  }
}
</script>

</body>
</html>