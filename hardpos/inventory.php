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

/* ---------- Filters & Queries ---------- */
 $whereClauses = [];
if (!empty($_GET['category'])) { 
    $category = $conn->real_escape_string($_GET['category']); 
    $whereClauses[] = "p.category = '$category'"; 
}
if (!empty($_GET['unit'])) { 
    $unit = $conn->real_escape_string($_GET['unit']); 
    $whereClauses[] = "p.unit = '$unit'"; 
}
if (!empty($_GET['search'])) { 
    $search = $conn->real_escape_string($_GET['search']); 
    $whereClauses[] = "p.product_name LIKE '%$search%'"; 
}
 $whereSQL = $whereClauses ? "WHERE ".implode(" AND ",$whereClauses) : "";

 $sql = "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category = c.id $whereSQL ORDER BY p.product_name ASC";
 $result = $conn->query($sql) or die("DB Error: ".$conn->error);

/* ---------- Low Stock ---------- */
 $lowStockThreshold = 5;
 $lowStockItems = [];
 $resLow = $conn->query("SELECT product_name, stock FROM products WHERE stock <= $lowStockThreshold ORDER BY stock ASC");
while ($resLow && $row = $resLow->fetch_assoc()) $lowStockItems[] = $row;

/* ---------- Get Categories for Edit Modal ---------- */
 $categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{
  /* Dark mode variables (default) */
  --bg: #1F1F23;       /* main background */
  --sidebar: #1B1E22;  /* sidebar background */
  --card: #26292E;     /* card background */
  --accent: #F25F3A;   /* orange accent */
  --text: #C7C8CE;
  --text-dark: #ffffff;
  --muted: #8C8D91;
  --success: #66BB6A;
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
  --text: #333333;
  --text-dark: #ffffff;
  --muted: #666666;
  --success: #4CAF50;
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
  font-family:"Segoe UI",sans-serif;
  background:var(--bg);
  color:var(--text);
  display:flex;
  height:100%;
  margin:0;
  overflow:hidden;
  transition: background-color 0.3s, color 0.3s;
}

.sidebar{
  width:80px;
  background:var(--sidebar);
  position:fixed;
  top:0;
  bottom:0;
  padding-top:20px;
  display:flex;
  flex-direction:column;
  align-items:center;
  z-index:20;
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
.sidebar ul{list-style:none;padding:0;margin:0;width:100%}
.sidebar li{width:100%}
.sidebar li a{
  display:flex;
  justify-content:center;
  align-items:center;
  padding:18px 0;
  color:var(--sidebar-icon);
  transition:.15s;
  position:relative;
  font-size:20px;
}
.sidebar li a:hover{color:var(--sidebar-hover)}
.sidebar li a.active{
  color:var(--accent);
  box-shadow:var(--neu-active-shadow);
}
.sidebar li a.active::after{
  content:"";
  position:absolute;
  left:100%;
  top:50%;
  transform:translateY(-50%);
  width:12px;
  height:48px;
  background:#F25F3A;
  border-radius:10px;
  box-shadow:var(--neu-active-shadow);
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

.wrapper{margin-left:80px;flex:1;display:flex;min-height:100vh}
.main{flex:1;padding:20px;display:flex;flex-direction:column;height:100%;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
.topbar h1{font-size:22px;margin:0;font-weight:600;color:var(--text-dark)}
.clock-card{padding:8px 18px;font-size:15px;color:var(--accent);font-weight:600;text-align:center;background:var(--card);border-radius:12px;box-shadow:var(--neu-shadow)}

/* Theme toggle button */
.theme-toggle {
  background: none;
  border: none;
  color: var(--text);
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

.filter-bar{
  background:var(--card);
  padding:16px;
  border-radius:16px;
  box-shadow:var(--neu-shadow);
  display:flex;
  justify-content:space-between;
  align-items:center;
  flex-wrap:wrap;
  gap:10px;
  margin-bottom:20px
}
.filter-bar select,.filter-bar input[type="text"]{
  background:var(--bg);
  border:none;
  border-radius:8px;
  color:var(--text);
  padding:6px 10px;
  font-size:14px;
  box-shadow:inset 3px 3px 6px rgba(0,0,0,0.2),inset -3px -3px 6px rgba(255,255,255,0.05);
  outline:none
}
.table-card{
  background:var(--card);
  border-radius:16px;
  padding:20px;
  box-shadow:var(--neu-shadow);
  overflow-y:auto;
  max-height:60vh
}
.table-card table{
  width:100%;
  border-collapse:collapse;
  color:var(--text);
  min-width:900px
}
.table-card th,.table-card td{
  padding:12px 10px;
  border-bottom:1px solid var(--border-light);
  text-align:left
}
.table-card tr:hover{background:var(--border-light)}
.btn{
  background:var(--bg);
  color:var(--text);
  border:none;
  padding:10px 18px;
  border-radius:30px;
  cursor:pointer;
  font-size:14px;
  box-shadow:var(--neu-shadow);
  transition:all 0.2s ease
}
.btn:hover{box-shadow:inset 6px 6px 12px rgba(0,0,0,0.2),inset -6px -6px 12px rgba(255,255,255,0.05)}
.btn-danger{background:#c0392b;color:white}
.btn-danger:hover{background:#e74c3c}
.btn-secondary{background:var(--muted);color:white}
.btn-secondary:hover{background:#777}
.right-panel{
  width:300px;
  padding:22px;
  border-left:1px solid var(--border-light);
  display:flex;
  flex-direction:column;
  gap:18px
}
.profile-card{
  background:var(--card);
  padding:20px;
  border-radius:16px;
  box-shadow:var(--neu-shadow);
  text-align:center
}
.profile-photo{
  width:56px;
  height:56px;
  border-radius:50%;
  object-fit:cover;
  margin:0 auto 8px;
  box-shadow:var(--neu-shadow);
  background:var(--bg);
  display:flex;
  align-items:center;
  justify-content:center
}
.profile-photo i{color:var(--muted);font-size:24px}
.profile-card h3{margin:6px 0 0;color:var(--text-dark);font-size:16px;}
.profile-card .role{color:var(--muted);font-size:13px;margin-top:6px}
.alert{
  background:var(--card);
  padding:16px;
  border-radius:16px;
  box-shadow:var(--neu-shadow)
}
.alert ul{margin:10px 0 0;padding-left:20px}
.alert h3{margin:0 0 10px;display:flex;align-items:center;gap:8px;color:var(--text-dark)}
.modal{
  display:none;
  position:fixed;
  inset:0;
  background:var(--modal-bg);
  justify-content:center;
  align-items:center;
  z-index:1000
}
.modal-content{
  background:var(--card);
  padding:20px;
  border-radius:16px;
  width:450px;
  color:var(--text);
  box-shadow:0 10px 30px rgba(0,0,0,0.5)
}
.modal-content input,.modal-content select{
  width:100%;
  background:var(--bg);
  border:none;
  padding:8px;
  margin-bottom:10px;
  border-radius:8px;
  color:var(--text);
  box-shadow:inset 3px 3px 6px rgba(0,0,0,0.2),inset -3px -3px 6px rgba(255,255,255,0.05);
  outline:none;
  font-size:13px
}
.modal-content input:focus,.modal-content select:focus{
  box-shadow:inset 2px 2px 4px rgba(0,0,0,0.4),inset -2px -2px 4px rgba(255,255,255,0.05),0 0 0 1px var(--accent)
}
.modal-content label{
  display:block;
  margin-bottom:3px;
  color:var(--text);
  font-weight:500;
  font-size:12px
}
.modal-content h3{
  margin:0 0 15px;
  color:var(--text-dark);
  font-size:18px
}
.close{
  cursor:pointer;
  float:right;
  font-size:18px;
  color:var(--muted);
  transition:color 0.2s
}
.close:hover{color:var(--text)}
.loading{
  display:inline-block;
  width:16px;
  height:16px;
  border:2px solid rgba(255,255,255,0.3);
  border-radius:50%;
  border-top-color:#fff;
  animation:spin 1s ease-in-out infinite
}
@keyframes spin{to{transform:rotate(360deg)}}
.current-image{margin-top:8px}
.current-image img{
  max-width:80px;
  max-height:80px;
  border-radius:6px;
  object-fit:cover
}
.current-image p{margin:3px 0;font-size:11px;color:var(--muted)}
.form-actions{display:flex;gap:8px;margin-top:15px;justify-content:flex-end}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px}
.form-grid.full-width{grid-template-columns:1fr}
.low-stock td{color:#e74c3c;font-weight:bold}
.image-section{grid-column:1 / -1}
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
      <a href="inventory.php"class="active">
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
    <h1>Inventory</h1>
    <div style="display: flex; align-items: center;">
      <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
        <i class="fas fa-<?php echo $currentTheme === 'light' ? 'sun' : 'moon'; ?>"></i>
      </button>
      <div id="clock" class="clock-card"></div>
    </div>
  </div>

  <div class="filter-bar">
    <a href="add_product.php" class="btn"><i class="fa fa-plus"></i> Add Product</a>
    <div>
      Category:
      <select id="filterCategory"><option value="">All</option>
        <?php $cats = $conn->query("SELECT DISTINCT category FROM products"); $currCat = $_GET['category'] ?? '';
        while ($c = $cats->fetch_assoc()) { $sel = $c['category']===$currCat ? 'selected' : ''; echo "<option $sel>".htmlspecialchars($c['category'])."</option>"; } ?>
      </select>
      Unit:
      <select id="filterUnit"><option value="">All</option></select>
      <input type="text" id="search" placeholder="Search…" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    </div>
  </div>

  <div class="table-card">
    <table>
      <thead><tr><th>Name</th><th>Category</th><th>Unit</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if ($result->num_rows): while ($r=$result->fetch_assoc()): ?>
        <tr class="<?= ($r['stock'] <= $lowStockThreshold) ? 'low-stock' : '' ?>">
          <td><?= htmlspecialchars($r['product_name']) ?></td>
          <td><?= htmlspecialchars($r['category_name'] ?? $r['category']) ?></td>
          <td><?= htmlspecialchars($r['unit']) ?></td>
          <td>₱<?= number_format($r['price'],2) ?></td>
          <td><?= $r['stock'] ?></td>
          <td>
            <button class="btn btn-edit"
              onclick="openEditModal('<?= $r['id'] ?>','<?= htmlspecialchars(addslashes($r['product_name'])) ?>','<?= htmlspecialchars(addslashes($r['category'])) ?>','<?= htmlspecialchars($r['unit']) ?>','<?= $r['price'] ?>','<?= $r['stock'] ?>')"><i class="fa fa-edit"></i></button>
            <a href="delete_product.php?id=<?= $r['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this product?');"><i class="fa fa-trash"></i></a>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="6">No products found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<aside class="right-panel">
  <div class="profile-card">
    <?php if ($currentUser && !empty($currentUser['photo']) && file_exists($currentUser['photo'])): ?>
      <img src="<?= htmlspecialchars($currentUser['photo']) ?>" alt="Profile Photo" class="profile-photo">
    <?php else: ?>
      <div class="profile-photo">
        <i class="fas fa-user"></i>
      </div>
    <?php endif; ?>
    <h3>Welcome, <?= htmlspecialchars($displayName) ?></h3>
    <div class="role"><?= htmlspecialchars($currentUser['role'] ?? 'Product Designer') ?></div>
  </div>
  <?php if ($lowStockItems): ?>
    <div class="alert">
      <h3><i class="fa fa-exclamation-triangle"></i> Low Stock</h3>
      <ul><?php foreach ($lowStockItems as $i): ?><li><?= htmlspecialchars($i['product_name']) ?> (<?= $i['stock'] ?> left)</li><?php endforeach; ?></ul>
    </div>
  <?php else: ?>
    <div class="alert"><h3>No Low Stock Items 🎉</h3></div>
  <?php endif; ?>
</aside>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3><i class="fa fa-edit"></i> Edit Product</h3>
    <form id="editForm" enctype="multipart/form-data">
      <input type="hidden" name="id" id="editId">
      
      <div class="form-grid">
        <div>
          <label for="editProductName">Product Name:</label>
          <input type="text" name="product_name" id="editProductName" required>
        </div>
        
        <div>
          <label for="editCategory">Category:</label>
          <select name="category" id="editCategory" required>
            <option value="">-- Select Category --</option>
            <?php 
            // Reset the categories result pointer
            $categories->data_seek(0);
            while($c = $categories->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars($c['category_name']) ?>">
                <?= htmlspecialchars($c['category_name']) ?> (<?= $c['points_per_item'] ?> pts)
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        
        <div>
          <label for="editUnit">Unit:</label>
          <select name="unit" id="editUnit" required>
            <option value="piece">Piece</option>
            <option value="pack">Pack</option>
            <option value="meter">Meter</option>
            <option value="feet">Feet</option>
            <option value="kilo">Kilo</option>
            <option value="sack">Sack</option>
          </select>
        </div>
        
        <div>
          <label for="editPrice">Price:</label>
          <input type="number" step="0.01" name="price" id="editPrice" required>
        </div>
      </div>
      
      <div class="form-grid full-width">
        <div>
          <label for="editStock">Stock:</label>
          <input type="number" step="0.01" name="stock" id="editStock" required>
        </div>
      </div>
      
      <div class="form-grid full-width image-section">
        <div>
          <label for="editImage">Product Image:</label>
          <input type="file" name="image" id="editImage" accept="image/*">
          <div id="currentImage" class="current-image"></div>
        </div>
      </div>
      
      <div class="form-actions">
        <button type="submit" class="btn" id="saveBtn">
          <i class="fa fa-save"></i> Save
        </button>
        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
          <i class="fa fa-times"></i> Cancel
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function updateClock(){document.getElementById('clock').textContent=new Date().toLocaleString('en-PH',{hour12:false});}
setInterval(updateClock,1000);updateClock();

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

function loadUnits(cat,sel=""){const u=document.getElementById('filterUnit');u.innerHTML='<option value="">All</option>';if(!cat)return;fetch('get_units.php?category='+encodeURIComponent(cat)).then(r=>r.json()).then(js=>{js.forEach(x=>{const o=document.createElement('option');o.value=o.textContent=x;if(x===sel)o.selected=true;u.appendChild(o);});});}
const cSel=document.getElementById('filterCategory');const uSel=document.getElementById('filterUnit');
loadUnits("<?= $_GET['category'] ?? '' ?>","<?= $_GET['unit'] ?? '' ?>");
[cSel,uSel,document.getElementById('search')].forEach(el=>{el.addEventListener('change',applyFilters);if(el.id==='search')el.addEventListener('keyup',()=>setTimeout(applyFilters,500));});
function applyFilters(){const cat=cSel.value,unit=uSel.value,s=document.getElementById('search').value;let url='inventory.php?';if(cat)url+='category='+encodeURIComponent(cat)+'&';if(unit)url+='unit='+encodeURIComponent(unit)+'&';if(s)url+='search='+encodeURIComponent(s);location.href=url;}

const modal=document.getElementById('editModal');

function openEditModal(id, n, c, u, p, s) {
  // Set form values
  document.getElementById('editId').value = id;
  document.getElementById('editProductName').value = n;
  document.getElementById('editCategory').value = c;
  document.getElementById('editUnit').value = u;
  document.getElementById('editPrice').value = p;
  document.getElementById('editStock').value = s;
  
  // Show current image if any
  fetch('get_product_image.php?id=' + id)
    .then(res => res.json())
    .then(data => {
      const currentImageDiv = document.getElementById('currentImage');
      if (data.image) {
        currentImageDiv.innerHTML = `
          <p>Current Image:</p>
          <img src="${data.image}?t=${new Date().getTime()}" style="max-width: 80px; max-height: 80px; border-radius: 6px; margin-top: 3px; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
        `;
      } else {
        currentImageDiv.innerHTML = '<p style="color: var(--muted); font-size: 11px;">No image available</p>';
      }
    })
    .catch(error => {
      console.error('Error fetching product image:', error);
      document.getElementById('currentImage').innerHTML = '<p style="color: #e74c3c; font-size: 11px;">Error loading image</p>';
    });
  
  modal.style.display = 'flex';
}

function closeEditModal() {
  modal.style.display = 'none';
  document.getElementById('editForm').reset();
  document.getElementById('currentImage').innerHTML = '';
}

// Close modal when clicking the X
document.querySelector('.close').onclick = closeEditModal;

// Close modal when clicking outside
window.onclick = function(event) {
  if (event.target === modal) {
    closeEditModal();
  }
}

// Form submission with proper error handling
document.getElementById('editForm').onsubmit = function(e) {
  e.preventDefault();
  
  const saveBtn = document.getElementById('saveBtn');
  const originalHTML = saveBtn.innerHTML;
  
  // Show loading state
  saveBtn.innerHTML = '<span class="loading"></span> Saving...';
  saveBtn.disabled = true;
  
  const formData = new FormData(this);
  
  fetch('edit_product.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    return response.json();
  })
  .then(data => {
    if (data.success) {
      // Show success message
      alert('✅ Product updated successfully!');
      // Reload the page to show updated data
      location.reload();
    } else {
      // Show error message
      alert('❌ Error: ' + (data.message || 'Failed to update product'));
      // Reset button
      saveBtn.innerHTML = originalHTML;
      saveBtn.disabled = false;
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('⚠️ An error occurred while updating the product. Please try again.');
    // Reset button
    saveBtn.innerHTML = originalHTML;
    saveBtn.disabled = false;
  });
};
</script>
</body>
</html>