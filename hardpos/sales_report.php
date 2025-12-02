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

// ----- Date range -----
 $start = $_GET['start_date'] ?? date('Y-m-01');   // default: first of this month
 $end   = $_GET['end_date']   ?? date('Y-m-d');    // default: today
 $s = $conn->real_escape_string($start);
 $e = $conn->real_escape_string($end);

// ----- Totals / averages -----
 $totals = $conn->query("
  SELECT 
    COUNT(*) AS transactions,
    SUM(total_amount) AS total,
    AVG(total_amount) AS average
  FROM sales
  WHERE DATE(created_at) BETWEEN '$s' AND '$e'
")->fetch_assoc();

// ----- Daily totals for line chart -----
 $dailyLabels = $dailyTotals = [];
 $res = $conn->query("
  SELECT DATE(created_at) AS day, SUM(total_amount) AS total
  FROM sales
  WHERE DATE(created_at) BETWEEN '$s' AND '$e'
  GROUP BY day ORDER BY day
");
while ($r = $res->fetch_assoc()) {
    $dailyLabels[] = $r['day'];
    $dailyTotals[] = $r['total'];
}

// ----- Payment method totals -----
 $payLabels = $payTotals = [];
 $pay = $conn->query("
  SELECT payment_method, SUM(total_amount) AS total
  FROM sales
  WHERE DATE(created_at) BETWEEN '$s' AND '$e'
  GROUP BY payment_method
");
while ($p = $pay->fetch_assoc()) {
    $payLabels[] = $p['payment_method'];
    $payTotals[] = $p['total'];
}

// ----- Top selling products -----
 $prodLabels = $prodTotals = [];
 $prod = $conn->query("
  SELECT p.product_name AS product_name,
         SUM(si.quantity) AS qty
  FROM sales_items si
  JOIN products p ON si.product_id = p.id
  JOIN sales s ON si.sale_id = s.id
  WHERE DATE(s.created_at) BETWEEN '$s' AND '$e'
  GROUP BY p.product_name
  ORDER BY qty DESC
  LIMIT 10
");
if (!$prod) {
    die('Top products query failed: ' . $conn->error);
}
while ($p = $prod->fetch_assoc()) {
    $prodLabels[] = $p['product_name'];
    $prodTotals[] = $p['qty'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Sales Report</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

/* Filter bar */
.filter-bar {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 16px 22px;
  margin-bottom:22px;
  background: var(--card);
  border-radius:16px;
  box-shadow: var(--neu-shadow);
}
.filter-bar label {
  color: var(--muted);
  font-size: 14px;
}
.filter-bar input[type="date"] {
  background: var(--input-bg);
  border: none;
  border-radius: 8px;
  padding: 8px 12px;
  color: var(--text-light);
  font-size: 14px;
  box-shadow: var(--input-shadow);
}
.filter-bar button, .filter-bar .btn-export {
  background: var(--accent);
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 14px;
}
.filter-bar button:hover, .filter-bar .btn-export:hover {
  background: #e04a2a;
}

/* Summary cards */
.summary{
  display:grid;
  grid-template-columns: repeat(3,1fr);
  gap:20px;
  margin-bottom:22px;
}
.summary .card{
  padding:22px;
  text-align:center;
  background: var(--card);
  border-radius:16px;
  box-shadow: var(--neu-shadow);
}
.summary .card h2 {
  margin:0 0 8px;
  font-size:28px;
  color:var(--text-dark);
}
.summary .card p {
  margin:0;
  color:var(--muted);
  font-size:14px;
}

/* Charts - ALL IN ONE ROW */
.charts{
  display:grid;
  grid-template-columns: repeat(3, 1fr); /* Three equal columns */
  gap:20px;
  margin-bottom:22px;
}
.chart-box{ 
  padding:15px; /* Reduced padding for more space */
  height:280px; /* Reduced height to fit three in a row */
  background: var(--card);
  border-radius:16px;
  box-shadow: var(--neu-shadow);
}
.chart-box canvas{ 
  width:100%!important; 
  height:100%!important; 
}

/* right panel */
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
.events-card{ 
  padding:12px; 
  font-size:14px;
  background: var(--card);
  border-radius:16px;
  box-shadow: var(--neu-shadow);
}
.events-card h4{ 
  margin:0 0 10px; 
  color:var(--text-dark); 
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

/* responsive */
@media(max-width:1200px){
  .charts{ 
    grid-template-columns: repeat(2, 1fr); /* Two per row on medium screens */
  }
}
@media(max-width:1000px){
  .right-panel{ display:none; }
  .charts{ 
    grid-template-columns: 1fr; /* One per row on smaller screens */
  }
  .summary{ grid-template-columns: repeat(2,1fr); }
}
@media(max-width:600px){
  .summary{ grid-template-columns: 1fr; }
  .filter-bar {
    flex-direction: column;
    align-items: stretch;
  }
  .filter-bar button, .filter-bar .btn-export {
    width: 100%;
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
      <a href="sales_report.php" class="active">
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
      <h1>Sales Report</h1>
      <div style="display: flex; align-items: center;">
        <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
          <i class="fas fa-<?php echo $currentTheme === 'light' ? 'sun' : 'moon'; ?>"></i>
        </button>
        <div id="clock" class="clock-card neu"></div>
      </div>
    </div>

    <!-- Filter / Export -->
    <form method="get" class="filter-bar">
      <label>From: <input type="date" name="start_date" value="<?= htmlspecialchars($start) ?>"></label>
      <label>To: <input type="date" name="end_date" value="<?= htmlspecialchars($end) ?>"></label>
      <button type="submit">Filter</button>
      <button type="button" onclick="window.print()">Print</button>
      <a class="btn-export" href="export_sales_csv.php?start_date=<?=urlencode($start)?>&end_date=<?=urlencode($end)?>">CSV</a>
    </form>

    <!-- Summary -->
    <div class="summary">
      <div class="card neu">
        <h2>₱<?= number_format($totals['total'] ?? 0, 2) ?></h2>
        <p>Total Sales</p>
      </div>
      <div class="card neu">
        <h2><?= $totals['transactions'] ?? 0 ?></h2>
        <p>Transactions</p>
      </div>
      <div class="card neu">
        <h2>₱<?= number_format($totals['average'] ?? 0, 2) ?></h2>
        <p>Average per Sale</p>
      </div>
    </div>

    <!-- Charts - ALL IN ONE ROW -->
    <div class="charts">
      <div class="chart-box neu">
        <canvas id="lineChart"></canvas>
      </div>
      <div class="chart-box neu">
        <canvas id="payChart"></canvas>
      </div>
      <div class="chart-box neu">
        <canvas id="prodChart"></canvas>
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
      <div class="role"><?= htmlspecialchars($currentUser['role'] ?? 'Sales Manager') ?></div>
    </div>

    <div class="events-card neu">
      <h4><i class="fas fa-history"></i> Recent Transactions</h4>
      <ul>
        <?php
        $rightRecent = $conn->query("SELECT id, total_amount, created_at
                                     FROM sales
                                     ORDER BY created_at DESC LIMIT 5");
        while ($row = $rightRecent->fetch_assoc()): ?>
          <li>#<?=$row['id']?> — ₱<?=number_format($row['total_amount'],2)?>
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
      
      // Update charts for the new theme
      updateChartsTheme(newTheme === 'light');
    }
  })
  .catch(error => console.error('Error setting theme:', error));
});

// Function to update chart colors based on theme
function updateChartsTheme(isLight) {
  const gridColor = isLight ? 'rgba(0,0,0,0.1)' : 'rgba(255,255,255,0.03)';
  const textColor = isLight ? '#333333' : '#C7C8CE';
  
  // Update line chart
  const lineChart = Chart.getChart('lineChart');
  if (lineChart) {
    lineChart.options.scales.x.grid.color = gridColor;
    lineChart.options.scales.y.grid.color = gridColor;
    lineChart.options.scales.x.ticks.color = textColor;
    lineChart.options.scales.y.ticks.color = textColor;
    lineChart.options.plugins.title.color = textColor;
    lineChart.update();
  }
  
  // Update payment chart
  const payChart = Chart.getChart('payChart');
  if (payChart) {
    payChart.options.plugins.legend.labels.color = textColor;
    payChart.options.plugins.title.color = textColor;
    payChart.update();
  }
  
  // Update product chart
  const prodChart = Chart.getChart('prodChart');
  if (prodChart) {
    prodChart.options.scales.x.grid.color = gridColor;
    prodChart.options.scales.y.grid.color = gridColor;
    prodChart.options.scales.x.ticks.color = textColor;
    prodChart.options.scales.y.ticks.color = textColor;
    prodChart.options.plugins.title.color = textColor;
    prodChart.update();
  }
}

/* Charts */
new Chart(document.getElementById('lineChart'), {
  type:'line',
  data:{
    labels: <?= json_encode($dailyLabels) ?>,
    datasets:[{
      label:'Daily Sales (₱)',
      data: <?= json_encode($dailyTotals) ?>,
      borderColor: 'rgba(242,95,58,1)',
      backgroundColor:'rgba(242,95,58,0.15)',
      fill:true,
      tension:0.35,
      pointRadius:3
    }]
  },
  options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{ 
      legend:{ display:false },
      title: {
        display: true,
        text: 'Daily Sales Trend',
        color: '#C7C8CE',
        font: { size: 14 }
      }
    },
    scales:{ 
      x:{ 
        grid:{ color:'rgba(255,255,255,0.03)' },
        ticks: { color: '#C7C8CE' }
      }, 
      y:{ 
        grid:{ color:'rgba(255,255,255,0.03)' },
        ticks: { color: '#C7C8CE' }
      } 
    }
  }
});

new Chart(document.getElementById('payChart'), {
  type:'doughnut',
  data:{
    labels: <?= json_encode($payLabels) ?>,
    datasets:[{
      label:'Sales by Payment',
      data: <?= json_encode($payTotals) ?>,
      backgroundColor:['#3498db','#e74c3c','#2ecc71','#f1c40f','#9b59b6']
    }]
  },
  options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{ 
      legend:{ 
        position:'right',
        labels: { color: '#C7C8CE' }
      },
      title: {
        display: true,
        text: 'Payment Methods',
        color: '#C7C8CE',
        font: { size: 14 }
      }
    }
  }
});

new Chart(document.getElementById('prodChart'), {
  type:'bar',
  data:{
    labels: <?= json_encode($prodLabels) ?>,
    datasets:[{
      label:'Top Products (Qty)',
      data: <?= json_encode($prodTotals) ?>,
      backgroundColor:'#29b6f6'
    }]
  },
  options:{
    responsive:true,
    maintainAspectRatio:false,
    indexAxis: 'y', /* Horizontal bar chart for better space usage */
    plugins:{ 
      legend:{ display:false },
      title: {
        display: true,
        text: 'Top Products',
        color: '#C7C8CE',
        font: { size: 14 }
      }
    },
    scales:{ 
      x:{ 
        grid:{ color:'rgba(255,255,255,0.03)' },
        ticks: { color: '#C7C8CE' }
      }, 
      y:{ 
        grid:{ color:'rgba(255,255,255,0.03)' },
        ticks: { color: '#C7C8CE' }
      } 
    }
  }
});

// Initialize charts with the correct theme
updateChartsTheme(<?php echo $currentTheme === 'light' ? 'true' : 'false'; ?>);
</script>
</body>
</html>