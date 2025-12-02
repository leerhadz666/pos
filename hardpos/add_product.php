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

// Fetch categories with points
 $cats = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");

// Stats
 $totalProducts = $conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];
 $totalCategories = $conn->query("SELECT COUNT(*) AS c FROM categories")->fetch_assoc()['c'];

// Recent Activity (last 5 logs)
 $recentLogs = $conn->query("SELECT action, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Product & Category</title>
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
  --input-shadow: inset 3px 3px 6px #0e0e0e, inset -3px -3px 6px #262626;
  --input-focus-shadow: inset 2px 2px 4px rgba(0,0,0,0.4), inset -2px -2px 4px rgba(255,255,255,0.05), 0 0 0 1px var(--accent);
  --scrollbar-thumb: #444;
  --placeholder-bg: #444;
}

/* Light mode variables */
body.light-mode {
  --bg: #f5f5f5;       /* main background */
  --sidebar: #ffffff;  /* sidebar background */
  --card: #ffffff;     /* card background */
  --accent: #F25F3A;   /* orange accent */
  --text: #333333;
  --text-dark: #333333;
  --muted: #666666;
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
  --input-shadow: inset 3px 3px 6px rgba(0,0,0,0.1), inset -3px -3px 6px rgba(255,255,255,0.8);
  --input-focus-shadow: inset 2px 2px 4px rgba(0,0,0,0.1), inset -2px -2px 4px rgba(255,255,255,0.8), 0 0 0 1px var(--accent);
  --scrollbar-thumb: #cccccc;
  --placeholder-bg: #e0e0e0;
}

*{box-sizing:border-box}
body{
  margin:0;
  font-family:"Segoe UI",sans-serif;
  background:var(--bg);
  color:var(--text);
  display:flex;
  height:100%;
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
  font-size:20px
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

.wrapper{margin-left:80px;flex:1;display:flex;min-height:100vh}
.main{flex:1;padding:20px;display:flex;flex-direction:column;height:100%}
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

.form-container{display:grid;grid-template-columns:1fr 1fr;gap:20px;flex:1;min-height:0}
.form-card{
  background:var(--card);
  padding:20px;
  border-radius:16px;
  box-shadow:var(--neu-shadow);
  display:flex;
  flex-direction:column
}
.form-card h2{margin-top:0;color:var(--text-dark);font-size:18px}
.form-card label{display:block;margin:12px 0 6px;font-weight:500;color:var(--text)}
.form-card input,.form-card select{
  width:100%;
  padding:10px;
  background:var(--input-bg);
  border:none;
  border-radius:8px;
  color:var(--text);
  box-shadow:var(--input-shadow);
  outline:none
}
.form-card input:focus,.form-card select:focus{
  box-shadow:var(--input-focus-shadow)
}
.form-card input[type=file]{padding:6px}
.btn{
  background:var(--bg);
  color:var(--text);
  border:none;
  padding:10px 18px;
  border-radius:30px;
  cursor:pointer;
  font-size:14px;
  box-shadow:var(--neu-shadow)
}
.btn:hover{box-shadow:inset 6px 6px 12px rgba(0,0,0,0.2),inset -6px -6px 12px rgba(255,255,255,0.05)}
.btn-secondary{background:var(--muted);color:white}
.actions{display:flex;gap:12px;justify-content:flex-end;position:sticky;bottom:0;background:var(--card);padding-top:10px;margin-top:20px}
.stats-card{margin-top:20px;display:flex;gap:20px;flex-wrap:wrap}
.stat-box{
  flex:1;
  background:var(--card);
  padding:20px;
  border-radius:16px;
  text-align:center;
  cursor:pointer;
  transition:0.2s;
  box-shadow:var(--neu-shadow)
}
.stat-box:hover{background:var(--border-light)}
.stat-box h3{margin:0;font-size:16px;color:var(--text-dark)}
.stat-box p{margin:8px 0 0;font-size:20px;font-weight:bold;color:var(--accent)}
.scrollable-form{flex:1;overflow-y:auto;min-height:0;max-height:calc(100vh - 180px);padding-right:5px}
.scrollable-form::-webkit-scrollbar{width:6px}
.scrollable-form::-webkit-scrollbar-thumb{background:var(--scrollbar-thumb);border-radius:6px}
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
  text-align:center;
  box-shadow:var(--neu-shadow);
}
.profile-card h3{margin:6px 0 0;color:var(--text-dark);font-size:16px;}
.profile-card .role{color:var(--muted);font-size:13px;margin-top:6px}
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

.modal{
  display:none;
  position:fixed;
  top:0;
  left:0;
  width:100%;
  height:100%;
  background:var(--modal-bg);
  justify-content:center;
  align-items:center;
  z-index:200
}
.modal-content{
  background:var(--card);
  padding:20px;
  border-radius:16px;
  width:500px;
  max-height:80vh;
  overflow-y:auto;
  box-shadow:0 10px 30px rgba(0,0,0,0.5)
}
.modal-content h2{margin-top:0;color:var(--text-dark)}
.category-item{
  display:flex;
  align-items:center;
  gap:12px;
  padding:10px;
  border-bottom:1px solid var(--border-light)
}
.category-item img{
  width:40px;
  height:40px;
  object-fit:cover;
  border-radius:8px;
  background:var(--placeholder-bg)
}
.category-item .cat-actions{margin-left:auto;display:flex;gap:6px}
.cat-image-container{position:relative;display:flex;align-items:center;gap:6px}
.cat-img{
  width:40px;
  height:40px;
  object-fit:cover;
  border-radius:8px;
  background:var(--placeholder-bg)
}
.change-img-btn{
  position:absolute;
  bottom:0;
  right:-5px;
  padding:2px 6px;
  font-size:12px;
  border-radius:4px
}
.cat-points{font-size:12px;color:var(--muted);margin-top:2px}
.modal-close{
  background:var(--accent);
  color:#fff;
  border:none;
  padding:6px 12px;
  border-radius:6px;
  cursor:pointer;
  margin-top:10px
}
.activity-card{
  background:var(--card);
  padding:16px;
  border-radius:16px;
  box-shadow:var(--neu-shadow);
}
.activity-card h3{
  margin:0 0 12px;
  font-size:16px;
  color:var(--text-dark);
  display:flex;
  align-items:center;
  gap:8px
}
.activity-card ul{list-style:none;margin:0;padding:0}
.activity-card li{
  font-size:13px;
  margin-bottom:10px;
  color:var(--text);
  display:flex;
  flex-direction:column
}
.activity-card li small{font-size:11px;color:var(--muted)}
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
      <a href="add_product.php" class="active">
        <i class="fa fa-plus"></i>
        <span class="tooltip">Add Product</span>
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
    <h1>Add Product & Category</h1>
    <div style="display: flex; align-items: center;">
      <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
        <i class="fas fa-<?php echo $currentTheme === 'light' ? 'sun' : 'moon'; ?>"></i>
      </button>
      <div id="clock" class="clock-card"></div>
    </div>
  </div>

  <div class="form-container">
    <!-- Add Product -->
    <div class="form-card">
      <h2>Add Product</h2>
      <form action="save_product.php" method="POST" enctype="multipart/form-data" class="scrollable-form">
        <label for="name">Product Name</label>
        <input type="text" name="product_name" id="name" required>

        <label for="category">Category</label>
        <select name="category" id="category" required>
          <option value="">-- Select Category --</option>
          <?php 
          // Re-query categories to reset fetch pointer
          $categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
          while($c = $categories->fetch_assoc()): ?>
            <!-- FIXED: Changed value from ID to category name -->
            <option value="<?= htmlspecialchars($c['category_name']) ?>">
              <?= htmlspecialchars($c['category_name']) ?> (<?= $c['points_per_item'] ?> pts)
            </option>
          <?php endwhile; ?>
        </select>

        <label for="price">Unit Price</label>
        <input type="number" step="0.01" name="price" id="price" required>

        <label for="unit">Unit Type</label>
        <select name="unit" id="unit" required>
          <option value="piece">Piece</option>
          <option value="pack">Pack</option>
          <option value="meter">Meter</option>
          <option value="feet">Feet</option>
          <option value="kilo">Kilo</option>
          <option value="sack">Sack</option>
        </select>

        <label for="stock">Stock Quantity</label>
        <input type="number" step="0.01" name="stock" id="stock" required>

        <label for="image">Product Image</label>
        <input type="file" name="image" id="image" accept="image/*">

        <div class="actions">
          <button type="submit" class="btn">Save</button>
          <a href="inventory.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>

    <!-- Right Column -->
    <div>
      <!-- Add Category -->
      <div class="form-card">
        <h2>Add Category</h2>
        <form id="categoryForm" enctype="multipart/form-data">
          <label for="cat_name">Category Name</label>
          <input type="text" name="name" id="cat_name" required>

          <label for="cat_points">Points per Item</label>
          <input type="number" name="points" id="cat_points" min="0" value="0" required>

          <label for="cat_img">Category Image</label>
          <input type="file" name="category_image" id="cat_img" accept="image/*">

          <div class="actions">
            <button type="submit" class="btn">Add Category</button>
          </div>
        </form>
      </div>

      <!-- Stats + Category List Card -->
      <div class="stats-card">
        <div class="stat-box">
          <h3>Total Categories</h3>
          <p><?= $totalCategories ?></p>
        </div>
        <div class="stat-box">
          <h3>Total Products</h3>
          <p><?= $totalProducts ?></p>
        </div>
        <div class="stat-box" id="categoryListBtn">
          <h3>Category List</h3>
          <p><i class="fa fa-list"></i></p>
        </div>
      </div>
    </div>
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
    <div class="role"><?= htmlspecialchars($currentUser['role'] ?? 'Product Manager') ?></div>
  </div>

  <!-- Recent Activity -->
  <div class="activity-card">
    <h3><i class="fas fa-history"></i> Recent Activity</h3>
    <ul>
      <?php if ($recentLogs && $recentLogs->num_rows > 0): ?>
        <?php while($log = $recentLogs->fetch_assoc()): ?>
          <li>
            <span><?= htmlspecialchars($log['action']) ?></span>
            <small><?= date("M d, H:i", strtotime($log['created_at'])) ?></small>
          </li>
        <?php endwhile; ?>
      <?php else: ?>
        <li><em>No recent activity</em></li>
      <?php endif; ?>
    </ul>
  </div>
</aside>
</div>

<!-- Modal -->
<div class="modal" id="categoryModal">
  <div class="modal-content">
    <h2>All Categories</h2>
    <div id="categoryItems">
      <?php
      $allCats = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
      if ($allCats->num_rows > 0):
          while($cat = $allCats->fetch_assoc()): ?>
            <div class="category-item" data-id="<?= $cat['id'] ?>">
              <div class="cat-image-container">
                <?php if ($cat['image']): ?>
                  <img src="<?= htmlspecialchars($cat['image']) ?>" alt="" class="cat-img">
                <?php else: ?>
                  <img src="https://via.placeholder.com/40" alt="" class="cat-img">
                <?php endif; ?>
                <button class="change-img-btn btn btn-secondary" title="Change Image">📷</button>
                <input type="file" class="cat-img-input" style="display:none" accept="image/*">
              </div>
              <div>
                <span class="cat-name"><?= htmlspecialchars($cat['category_name']) ?></span>
                <div class="cat-points">Points: <?= $cat['points_per_item'] ?></div>
              </div>
              <div class="cat-actions">
                <button class="edit-btn btn">Edit</button>
                <button class="delete-btn btn btn-secondary">Delete</button>
              </div>
            </div>
          <?php endwhile;
      else: ?>
        <p>No categories available.</p>
      <?php endif; ?>
    </div>
    <button class="modal-close" id="closeModal">Close</button>
  </div>
</div>

<script>
function updateClock(){
  document.getElementById('clock').textContent=new Date().toLocaleString('en-PH',{hour12:false});
}
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

// Add Category AJAX
document.getElementById('categoryForm').addEventListener('submit', function(e){
  e.preventDefault();
  const formData = new FormData(this);
  
  // Show loading state
  const submitBtn = this.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;
  submitBtn.textContent = "Saving...";
  submitBtn.disabled = true;

  fetch('save_category.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if(data.success){
      alert("✅ Category added successfully!");
      location.reload();
    } else {
      alert("❌ Error: " + data.error);
    }
  })
  .catch(err => alert("⚠️ Server error while saving category."))
  .finally(() => {
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
  });
});

// Modal Toggle
const modal=document.getElementById('categoryModal');
document.getElementById('categoryListBtn').addEventListener('click',()=>modal.style.display='flex');
document.getElementById('closeModal').addEventListener('click',()=>modal.style.display='none');
window.addEventListener('click',e=>{if(e.target===modal){modal.style.display='none'}});

// Edit buttons
document.querySelectorAll('.edit-btn').forEach(btn=>{
  btn.addEventListener('click', e=>{
    const parent = e.target.closest('.category-item');
    const catId = parent.dataset.id;
    const oldName = parent.querySelector('.cat-name').textContent;
    const oldPointsText = parent.querySelector('.cat-points').textContent;
    const oldPoints = oldPointsText.replace('Points: ', '');
    
    const newName = prompt("Enter new category name:", oldName);
    if (newName === null) return; // cancelled
    
    const newPoints = prompt("Enter points per item:", oldPoints);
    if (newPoints === null) return; // cancelled
    
    // Validate points is a non-negative integer
    if (isNaN(newPoints) || newPoints < 0 || parseInt(newPoints) != newPoints) {
        alert("Points must be a non-negative integer.");
        return;
    }
    
    if(newName !== oldName || newPoints !== oldPoints){
      fetch('edit_category.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({id:catId, name:newName, points: parseInt(newPoints)})
      })
      .then(res=>res.json())
      .then(data=>{
        if(data.success){
          parent.querySelector('.cat-name').textContent = newName;
          parent.querySelector('.cat-points').textContent = 'Points: ' + newPoints;
        } else alert("❌ "+data.error);
      });
    }
  });
});

// Delete buttons
document.querySelectorAll('.delete-btn').forEach(btn=>{
  btn.addEventListener('click', e=>{
    const parent = e.target.closest('.category-item');
    const catId = parent.dataset.id;
    if(confirm("Are you sure you want to delete this category?")){
      fetch('delete_category.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({id:catId})
      })
      .then(res=>res.json())
      .then(data=>{
        if(data.success){
          parent.remove();
        } else alert("❌ "+data.error);
      });
    }
  });
});

// Image update
document.querySelectorAll('.change-img-btn').forEach(btn=>{
  btn.addEventListener('click', e=>{
    const container = e.target.closest('.cat-image-container');
    const fileInput = container.querySelector('.cat-img-input');
    fileInput.click();
    
    fileInput.addEventListener('change', ()=>{
      const catId = e.target.closest('.category-item').dataset.id;
      const formData = new FormData();
      formData.append('id', catId);
      formData.append('image', fileInput.files[0]);
      
      fetch('update_category_image.php',{
        method:'POST',
        body: formData
      })
      .then(res=>res.json())
      .then(data=>{
        if(data.success){
          container.querySelector('.cat-img').src = data.path + '?t=' + new Date().getTime(); // refresh image
        } else alert("❌ "+data.error);
      });
    }, {once:true});
  });
});

</script>
</body>
</html>