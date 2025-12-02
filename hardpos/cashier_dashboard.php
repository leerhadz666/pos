<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Set theme from session or default to dark
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'dark';
}
 $currentTheme = $_SESSION['theme'];

// Check if user is logged in and is a cashier
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'cashier') {
    header("Location: login.php");
    exit;
}

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

/* ---- Metrics ---- */
 $salesToday = $conn->query("SELECT SUM(total_amount) AS total FROM sales WHERE DATE(created_at)=CURDATE()")->fetch_assoc();
 $totalSalesToday = $salesToday['total'] ?? 0;

 $transactions = $conn->query("SELECT COUNT(*) AS cnt FROM sales WHERE DATE(created_at)=CURDATE()")->fetch_assoc();
 $totalTransactions = $transactions['cnt'] ?? 0;

 $lowStock = $conn->query("SELECT COUNT(*) AS low FROM products WHERE stock < 5")->fetch_assoc();
 $lowStockItems = $lowStock['low'] ?? 0;

 $customers = $conn->query("SELECT COUNT(*) AS cnt FROM customers")->fetch_assoc();
 $totalCustomers = $customers['cnt'] ?? 0;

/* 7-day sales */
 $salesData = $conn->query("SELECT DATE(created_at) as date, SUM(total_amount) as total
                           FROM sales
                           WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                           GROUP BY DATE(created_at)");
 $salesLabels = $salesValues = [];
while($row = $salesData->fetch_assoc()){
    $salesLabels[] = $row['date'];
    $salesValues[] = $row['total'];
}

/* top products */
 $topProducts = $conn->query("SELECT p.product_name, SUM(si.quantity) as qty
                             FROM sales_items si
                             JOIN products p ON si.product_id = p.id
                             GROUP BY p.product_name
                             ORDER BY qty DESC LIMIT 5");
 $productLabels = $productValues = [];
while($row = $topProducts->fetch_assoc()){
    $productLabels[] = $row['product_name'];
    $productValues[] = $row['qty'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Cashier Dashboard - POS</title>
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
  position:relative;
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
  position: relative;
}

.sidebar li a .tooltip {
  position: absolute;
  left: 100%;
  top: 50%;
  transform: translateY(-50%);
  margin-left: 15px;
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
  pointer-events: none;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
}

.sidebar li a:hover .tooltip {
  opacity: 1;
  visibility: visible;
}

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

/* Single summary card (all 4 stats inside) */
.summary-card{
  display:grid;
  grid-template-columns: repeat(4,1fr);
  gap:20px;
  padding:22px;
  margin-bottom:22px;
  align-items:center;
}
.summary-card .stat{
  text-align:left;
}
.summary-card .stat h4{
  margin:0;font-size:12px;color:var(--muted);font-weight:600;
}
.summary-card .stat h3{
  margin:8px 0 0;font-size:22px;color:var(--text-light);
}

/* Charts */
.charts{
  display:grid;
  grid-template-columns: 2fr 1fr;
  gap:20px;
  margin-bottom:22px;
}
.chart-box{ padding:20px; height:360px; }
.chart-box canvas{ width:100%!important; height:100%!important; }

/* right panel (profile + recent mini list) */
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
  cursor: pointer; 
}
.events-card li:hover { color: var(--accent); }
.events-card li small {
  color: var(--muted);
  font-size: 12px;
}

/* modal */
.modal{
  display:none;
  position:fixed;
  z-index:999;
  left:0;
  top:0;
  width:100%;
  height:100%;
  background:var(--modal-bg);
}
.modal-content{
  margin:5% auto;
  padding:20px;
  max-width:800px;
  overflow:auto;
  max-height:80vh;
  background: var(--card);
  border-radius:16px;
  box-shadow: var(--neu-shadow);
}
.modal-close{
  float:right;
  font-size:20px;
  cursor:pointer;
  color:#f15a29;
}
table{width:100%;border-collapse:collapse;font-size:14px;}
th,td{padding:10px;border-bottom:1px solid var(--border-light);text-align:left;}
th{color:var(--muted);}
/* responsive */
@media(max-width:1000px){
  .right-panel{ display:none; }
  .charts{ grid-template-columns: 1fr; }
  .summary-card{ grid-template-columns: repeat(2,1fr); }
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
      <a href="cashier_dashboard.php" class="active">
        <i class="fa fa-home"></i>
        <span class="tooltip">Dashboard</span>
      </a>
    </li>
    <li>
      <a href="cashier_sales.php">
        <i class="fa fa-credit-card"></i>
        <span class="tooltip">Sales</span>
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
      <h1>Cashier Dashboard</h1>
      <div style="display: flex; align-items: center;">
        <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
          <i class="fas fa-<?php echo $currentTheme === 'light' ? 'sun' : 'moon'; ?>"></i>
        </button>
        <div id="clock" class="clock-card neu"></div>
      </div>
    </div>

    <!-- single neumorphic summary card containing all 4 stats -->
    <div class="summary-card neu">
      <div class="stat">
        <h4>Daily Sales</h4>
        <h3>₱<?=number_format($totalSalesToday,2)?></h3>
      </div>
      <div class="stat">
        <h4>Transactions</h4>
        <h3><?=$totalTransactions?></h3>
      </div>
      <div class="stat">
        <h4>Low Stock Items</h4>
        <h3><?=$lowStockItems?></h3>
      </div>
      <div class="stat">
        <h4>Total Customers</h4>
        <h3><?=$totalCustomers?></h3>
      </div>
    </div>

    <div class="charts">
      <div class="chart-box neu"><canvas id="salesChart"></canvas></div>
      <div class="chart-box neu"><canvas id="topProductsChart"></canvas></div>
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
      <div class="role"><?= htmlspecialchars($currentUser['role'] ?? 'Cashier') ?></div>
    </div>

    <div class="events-card neu" id="recentCard">
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

<!-- Modal -->
<div id="recentModal" class="modal">
  <div class="modal-content neu">
    <span class="modal-close">&times;</span>
    <h2>All Recent Transactions</h2>
    <table>
      <thead><tr><th>ID</th><th>Amount</th><th>Payment</th><th>Date</th></tr></thead>
      <tbody>
      <?php
      $recent=$conn->query("SELECT * FROM sales ORDER BY created_at DESC LIMIT 50");
      while($row=$recent->fetch_assoc()): ?>
        <tr>
          <td><?=$row['id']?></td>
          <td>₱<?=number_format($row['total_amount'],2)?></td>
          <td><?=$row['payment_method']?></td>
          <td><?=$row['created_at']?></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
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
  
  // Update sales chart
  const salesChart = Chart.getChart('salesChart');
  if (salesChart) {
    salesChart.options.scales.x.grid.color = gridColor;
    salesChart.options.scales.y.grid.color = gridColor;
    salesChart.options.scales.x.ticks.color = textColor;
    salesChart.options.scales.y.ticks.color = textColor;
    salesChart.update();
  }
  
  // Update top products chart
  const topProductsChart = Chart.getChart('topProductsChart');
  if (topProductsChart) {
    topProductsChart.options.scales.x.grid.color = gridColor;
    topProductsChart.options.scales.y.grid.color = gridColor;
    topProductsChart.options.scales.x.ticks.color = textColor;
    topProductsChart.options.scales.y.ticks.color = textColor;
    topProductsChart.update();
  }
}

/* Charts */
new Chart(document.getElementById('salesChart'),{
  type:'line',
  data:{
    labels: <?=json_encode($salesLabels)?>,
    datasets:[{
      label:'Sales (₱)',
      data: <?=json_encode($salesValues)?>,
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
    plugins:{ legend:{ display:false } },
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

new Chart(document.getElementById('topProductsChart'),{
  type:'bar',
  data:{
    labels: <?=json_encode($productLabels)?>,
    datasets:[{
      label:'Top Products',
      data: <?=json_encode($productValues)?>,
      backgroundColor:'#29b6f6'
    }]
  },
  options:{
    responsive:true,
    maintainAspectRatio:false,
    plugins:{ legend:{ display:false } },
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

/* Modal logic */
const modal = document.getElementById('recentModal');
document.getElementById('recentCard').onclick = () => modal.style.display = 'block';
document.querySelector('.modal-close').onclick = () => modal.style.display = 'none';
window.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };

// Initialize charts with the correct theme
updateChartsTheme(<?php echo $currentTheme === 'light' ? 'true' : 'false'; ?>);
</script>
</body>
</html>