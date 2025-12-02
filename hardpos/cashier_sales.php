<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Set theme from session or default to dark
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'dark';
}
 $currentTheme = $_SESSION['theme'];

 $displayName = $_SESSION['user']['username'] ?? $_SESSION['username'] ?? 'Guest';

// Load categories with images
 $category_error = null;
 $categories_data = [];

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if categories table exists
 $table_check = "SHOW TABLES LIKE 'categories'";
 $table_result = $conn->query($table_check);

if ($table_result->num_rows == 0) {
    $category_error = "Error: Categories table does not exist";
} else {
    $cats_query = "SELECT id, category_name, image FROM categories";
    $cats_result = $conn->query($cats_query);
    
    if (!$cats_result) {
        $category_error = "Database error: " . $conn->error;
    } else {
        while($row = $cats_result->fetch_assoc()) {
            $categories_data[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Sales - Hardware POS</title>
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
  --success: #66BB6A;
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
  --success: #4CAF50;
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

/* Sales Page Styles */
.product-search {
  position: relative;
  margin-bottom: 20px;
}

.product-search i {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
}

.product-search input {
  width: 100%;
  padding: 12px 15px 12px 45px;
  border-radius: 10px;
  border: none;
  background: var(--card);
  color: var(--text-light);
  font-size: 15px;
  box-shadow: inset 2px 2px 5px rgba(0,0,0,0.3), inset -2px -2px 5px rgba(255,255,255,0.05);
  outline: none;
}

/* Categories Container */
.categories-wrapper {
  position: relative;
  margin-bottom: 20px;
}

.categories {
  display: flex;
  overflow-x: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
  padding: 15px;
  background: var(--card);
  border-radius: 16px;
  box-shadow: var(--neu-shadow);
  margin: 0 40px; /* Space for scroll buttons */
}

.categories::-webkit-scrollbar {
  display: none;
}

/* Category Items */
.cat-item {
  flex: 0 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin: 0 12px;
  cursor: pointer;
  padding: 15px;
  border-radius: 16px;
  transition: all 0.2s ease;
  background: var(--card);
  box-shadow: 
    4px 4px 8px rgba(0,0,0,0.4),
   -4px -4px 8px rgba(255,255,255,0.05);
  min-width: 90px;
}

.cat-item:hover {
  transform: translateY(-3px);
  box-shadow: 
    6px 6px 12px rgba(0,0,0,0.6),
   -6px -6px 12px rgba(255,255,255,0.08);
}

.cat-item.active {
  background: var(--bg);
  box-shadow: 
    inset 4px 4px 8px rgba(0,0,0,0.4),
    inset -4px -4px 8px rgba(255,255,255,0.05);
  color: var(--accent);
}

.cat-item img {
  width: 50px;
  height: 50px;
  object-fit: contain;
  margin-bottom: 8px;
  filter: drop-shadow(0 2px 3px rgba(0,0,0,0.3));
}

.cat-item span {
  font-size: 12px;
  text-align: center;
  font-weight: 500;
}

/* Scroll Buttons */
.scroll-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: var(--card);
  border: none;
  color: var(--text-light);
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 5;
  box-shadow: var(--neu-shadow);
  transition: all 0.2s ease;
}

.scroll-btn:hover {
  box-shadow: var(--neu-active-shadow);
  color: var(--accent);
}

.scroll-btn:active {
  box-shadow: 
    inset 2px 2px 5px rgba(0,0,0,0.3),
    inset -2px -2px 5px rgba(255,255,255,0.05);
}

.scroll-btn.left {
  left: 5px;
}

.scroll-btn.right {
  right: 5px;
}

.product-list-container {
  background: var(--card);
  border-radius: 15px;
  padding: 15px;
  box-shadow: var(--neu-shadow);
  margin-bottom: 20px;
  height: 320px; /* Fixed height for two rows */
  overflow-y: auto; /* Enable vertical scrolling */
  overflow-x: hidden; /* Prevent horizontal scrolling */
}

/* Add scrollbar styling */
.product-list-container::-webkit-scrollbar {
  width: 8px;
}

.product-list-container::-webkit-scrollbar-track {
  background: var(--bg);
  border-radius: 4px;
}

.product-list-container::-webkit-scrollbar-thumb {
  background: var(--muted);
  border-radius: 4px;
}

.product-list-container::-webkit-scrollbar-thumb:hover {
  background: var(--accent);
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); /* Smaller min-width for more columns */
  gap: 12px; /* Smaller gap between items */
}

.product-card {
  background: var(--bg);
  border-radius: 10px;
  padding: 8px; /* Reduced padding */
  text-align: center;
  transition: all 0.2s ease;
  cursor: pointer;
  height: 100%; /* Make cards consistent height */
  display: flex;
  flex-direction: column;
}

.product-card:hover {
  transform: translateY(-3px); /* Reduced movement */
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.product-card h4 {
  margin: 8px 0 4px; /* Reduced margins */
  font-size: 13px; /* Smaller font */
  line-height: 1.2; /* Better text wrapping */
  height: 32px; /* Fixed height for consistent layout */
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.product-card p {
  margin: 0;
  color: var(--accent);
  font-weight: bold;
  font-size: 14px;
}

.cart {
  background: var(--card);
  border-radius: 15px;
  padding: 20px;
  box-shadow: var(--neu-shadow);
  height: fit-content;
  width: 300px; /* Fixed width for cart */
  flex-shrink: 0; /* Prevent cart from shrinking */
}

.cart h2 {
  margin-top: 0;
  margin-bottom: 15px;
  font-size: 18px;
  border-bottom: 1px solid var(--border-light);
  padding-bottom: 10px;
}

.cart-items {
  max-height: 300px;
  overflow-y: auto;
  margin-bottom: 15px;
}

.cart-item {
  display: flex;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid var(--border-light);
}

.cart-item:last-child {
  border-bottom: none;
}

.remove-btn {
  background: none;
  border: none;
  color: var(--muted);
  cursor: pointer;
  margin-right: 10px;
}

.remove-btn:hover {
  color: #ff4d4d;
}

.item-name {
  flex: 1;
  font-size: 14px;
}

.qty-control {
  display: flex;
  align-items: center;
  margin: 0 10px;
}

.qty-btn {
  background: var(--bg);
  border: none;
  color: var(--text-light);
  width: 25px;
  height: 25px;
  border-radius: 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.qty-input {
  width: 50px;
  text-align: center;
  background: var(--bg);
  border: none;
  color: var(--text-light);
  margin: 0 5px;
  padding: 5px;
  border-radius: 5px;
}

.price {
  font-weight: bold;
  color: var(--accent);
}

.cart-summary {
  margin-top: 15px;
}

.cart-summary > div {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.cart-summary .total {
  font-size: 16px;
  font-weight: bold;
  color: var(--accent);
  border-top: 1px solid var(--border-light);
  padding-top: 10px;
}

.cart-actions {
  margin-top: 15px;
}

.btn-pay {
  width: 100%;
  padding: 12px;
  background: var(--accent);
  border: none;
  color: white;
  border-radius: 8px;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-pay:hover {
  background: #e04525;
}

.btn-pay:disabled {
  background: var(--muted);
  cursor: not-allowed;
}

.stock-badge {
  font-size: 12px;
  padding: 3px 8px;
  border-radius: 10px;
  margin-top: 5px;
  display: inline-block;
}

.in-stock {
  background: rgba(40, 167, 69, 0.2);
  color: #28a745;
}

.out-stock {
  background: rgba(220, 53, 69, 0.2);
  color: #dc3545;
}

.btn-add {
  width: 100%;
  padding: 8px;
  background: var(--accent);
  border: none;
  color: white;
  border-radius: 5px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-top: auto; /* Push button to bottom of card */
}

.btn-add:hover {
  background: #e04525;
}

.product-img {
  height: 60px; /* Reduced height */
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px; /* Reduced margin */
}

.product-img img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.placeholder {
  width: 100%;
  height: 100%;
  background: rgba(255,255,255,0.05);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--muted);
  font-size: 12px;
}

/* Minimal Modal Styles */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0);
  backdrop-filter: blur(0px);
  opacity: 0;
  transition: all 0.3s ease;
}

.modal.show {
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--modal-bg);
  backdrop-filter: blur(5px);
  opacity: 1;
}

.modal-content {
  background-color: var(--card);
  padding: 20px;
  border-radius: 15px;
  width: 380px;
  max-width: 90%;
  box-shadow: 0 10px 30px rgba(0,0,0,0.4);
  transform: scale(0.9) translateY(20px);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.modal.show .modal-content {
  transform: scale(1) translateY(0);
  opacity: 1;
}

.modal h3 {
  margin-top: 0;
  margin-bottom: 15px;
  color: var(--text-light);
  text-align: center;
  font-size: 18px;
  font-weight: 600;
}

.modal label {
  display: block;
  margin-bottom: 5px;
  color: var(--text-light);
  font-weight: 500;
  font-size: 13px;
}

.modal input, .modal select {
  width: 100%;
  padding: 10px 12px;
  margin-bottom: 12px;
  border: none;
  border-radius: 8px;
  background: var(--bg);
  color: var(--text-light);
  box-sizing: border-box;
  font-size: 14px;
  transition: all 0.2s ease;
  box-shadow: inset 2px 2px 5px rgba(0,0,0,0.3), inset -2px -2px 5px rgba(255,255,255,0.05);
}

.modal input:focus, .modal select:focus {
  outline: none;
  box-shadow: inset 2px 2px 5px rgba(0,0,0,0.3), inset -2px -2px 5px rgba(255,255,255,0.05), 0 0 0 2px rgba(242, 95, 58, 0.3);
}

.modal-actions {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin-top: 15px;
}

.modal button {
  padding: 10px 15px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
  font-size: 14px;
  flex: 1;
  transition: all 0.2s ease;
  box-shadow: var(--neu-shadow);
}

.btn-confirm {
  background-color: var(--accent);
  color: white;
}

.btn-confirm:hover {
  background-color: #e04525;
  transform: translateY(-1px);
  box-shadow: var(--neu-active-shadow);
}

.btn-cancel {
  background-color: var(--muted);
  color: white;
}

.btn-cancel:hover {
  background-color: #777;
  transform: translateY(-1px);
  box-shadow: var(--neu-active-shadow);
}

#change-display {
  text-align: right;
  margin-top: 10px;
  font-weight: bold;
  color: var(--success);
  font-size: 14px;
}

/* Points Info Styling */
#points-info {
  margin-top: 10px;
  padding: 10px;
  background: rgba(242, 95, 58, 0.1);
  border-radius: 8px;
  border-left: 3px solid var(--accent);
  transition: all 0.3s ease;
}

#points-earned {
  font-weight: bold;
  color: var(--accent);
  font-size: 16px;
}

/* Loading and Error States */
.loading, .error, .no-products {
  text-align: center;
  padding: 20px;
  border-radius: 10px;
  margin: 10px 0;
}

.loading {
  color: var(--accent);
  font-style: italic;
}

.error {
  background: rgba(220, 53, 69, 0.2);
  color: #dc3545;
}

.no-products {
  background: rgba(255, 193, 7, 0.2);
  color: #ffc107;
}

.error-message {
  color: #dc3545;
  padding: 10px;
  background: rgba(220, 53, 69, 0.1);
  border-radius: 5px;
  margin: 10px;
  text-align: center;
}

/* Customer Search Styles */
.customer-search-container {
  position: relative;
  margin-bottom: 10px;
}

.customer-search-input {
  width: 100%;
  padding: 10px 35px 10px 12px;
  border-radius: 8px;
  border: none;
  background: var(--bg);
  color: var(--text-light);
  box-sizing: border-box;
  outline: none;
  font-size: 13px;
}

.customer-search-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  font-size: 14px;
}

.customer-search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  max-height: 150px;
  overflow-y: auto;
  background: var(--card);
  border-radius: 8px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
  z-index: 100;
  display: none;
  margin-top: 5px;
}

.customer-search-result-item {
  padding: 8px 12px;
  cursor: pointer;
  border-bottom: 1px solid var(--border-light);
  font-size: 13px;
}

.customer-search-result-item:hover {
  background: rgba(242, 95, 58, 0.2);
}

.customer-search-result-item:last-child {
  border-bottom: none;
}

.customer-name {
  font-weight: bold;
}

.customer-details {
  font-size: 11px;
  color: var(--muted);
}

.loyal-badge {
  display: inline-block;
  background: var(--accent);
  color: white;
  font-size: 9px;
  padding: 1px 5px;
  border-radius: 8px;
  margin-left: 6px;
}

.selected-customer {
  margin-top: 8px;
  padding: 8px;
  background: rgba(242, 95, 58, 0.1);
  border-radius: 8px;
  border-left: 3px solid var(--accent);
  display: none;
  font-size: 13px;
}

.selected-customer-name {
  font-weight: bold;
  margin-bottom: 3px;
}

.selected-customer-details {
  font-size: 11px;
  color: var(--muted);
}

.change-customer-btn {
  background: none;
  border: none;
  color: var(--accent);
  cursor: pointer;
  font-size: 11px;
  margin-top: 5px;
  padding: 0;
}

/* Recent Customers */
.recent-customers {
  margin-top: 10px;
}

.recent-customer-item {
  padding: 8px;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.2s;
}

.recent-customer-item:hover {
  background: rgba(242, 95, 58, 0.1);
}

.recent-customer-name {
  font-weight: bold;
  font-size: 13px;
}

.recent-customer-date {
  font-size: 11px;
  color: var(--muted);
}

/* Responsive adjustments */
@media (max-width: 1200px) {
  .product-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  }
  
  .product-card h4 {
    font-size: 12px;
    height: 28px;
  }
  
  .product-card p {
    font-size: 13px;
  }
  
  .product-img {
    height: 50px;
  }
}

@media (max-width: 768px) {
  .wrapper {
    flex-direction: column;
  }
  
  .cart {
    width: 100%;
    margin-top: 20px;
  }
  
  .product-grid {
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  }
  
  .modal-content {
    width: 90%;
    padding: 15px;
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
      <a href="cashier_dashboard.php" >
        <i class="fa fa-home"></i>
        <span class="tooltip">Dashboard</span>
      </a>
    </li>
    
    <li>
      <a href="cashier_sales.php" class="active">
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
      <h1>Sales</h1>
      <div style="display: flex; align-items: center;">
        <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
          <i class="fas fa-<?php echo $currentTheme === 'light' ? 'sun' : 'moon'; ?>"></i>
        </button>
        <div id="clock" class="clock-card neu"></div>
      </div>
    </div>

    <div style="display:flex; gap:20px; flex-wrap: wrap;">
      <!-- Left Column: Products -->
      <div style="flex:1; min-width: 300px;">
        <!-- Product Search -->
        <div class="product-search neu">
          <i class="fa fa-search"></i>
          <input type="text" id="search-input" placeholder="Search products...">
        </div>

        <!-- Categories -->
<div class="categories-wrapper">
  <button class="scroll-btn left" onclick="scrollCategories(-200)">&#10094;</button>
  <div class="categories" id="categories">
    <div class="cat-item" onclick="loadProducts('all')">
      <img src="icons/all.png" alt="All">
      <span>All</span>
    </div>
    
    <?php if ($category_error): ?>
      <div class="error-message"><?php echo htmlspecialchars($category_error); ?></div>
    <?php else: ?>
      <?php foreach ($categories_data as $row): ?>
        <div class="cat-item" onclick="loadProducts('<?= htmlspecialchars($row['category_name']) ?>')">
          <img src="<?= !empty($row['image']) ? $row['image'] : 'icons/default.png' ?>" 
               alt="<?= htmlspecialchars($row['category_name']) ?>">
          <span><?= htmlspecialchars($row['category_name']) ?></span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <button class="scroll-btn right" onclick="scrollCategories(200)">&#10095;</button>
</div>

        <!-- Product Grid -->
        <div class="product-list-container">
          <div id="product-grid" class="product-grid">
            <!-- Products will be loaded here -->
          </div>
        </div>
      </div>

      <!-- Right Column: Cart -->
      <div class="cart">
        <h2>Order Details</h2>
        <div class="cart-items" id="cart-items"></div>
        <div class="cart-summary">
          <div>
            <span>Discount (%)</span>
            <input type="number" id="discount" value="0" min="0" max="100" style="width:60px;text-align:right;">
          </div>
          <div>
            <span>Sub Total</span>
            <span id="subtotal">₱0.00</span>
          </div>
          <div class="total">
            <span>Total</span>
            <span id="total">₱0.00</span>
          </div>
        </div>
        <div class="cart-actions">
          <button id="btn-pay" class="btn-pay" onclick="checkout()" disabled>
            Pay (₱0.00)
          </button>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="modal">
  <div class="modal-content">
    <h3>Confirm Payment</h3>
    <p id="payment-amount">Total: ₱0.00</p>
    
    <label for="payment-method">Payment Method:</label>
    <select id="payment-method">
      <option value="Cash">Cash</option>
      <option value="GCash">GCash</option>
      <option value="Utang">Utang</option>
    </select>

    <div id="cash-section">
      <label for="amount-received">Amount Received:</label>
      <input type="number" id="amount-received" placeholder="₱0.00" min="0">
      <p id="change-display">Change: ₱0.00</p>
    </div>

    <div id="customer-section">
      <label>Customer (Optional for Cash/GCash):</label>
      
      <!-- Customer Search -->
      <div class="customer-search-container">
        <input type="text" id="customer-search" class="customer-search-input" placeholder="Search by name or phone number...">
        <i class="fa fa-search customer-search-icon"></i>
        <div id="customer-search-results" class="customer-search-results"></div>
      </div>
      
      <!-- Selected Customer Display -->
      <div id="selected-customer" class="selected-customer">
        <div class="selected-customer-name" id="selected-customer-name"></div>
        <div class="selected-customer-details" id="selected-customer-details"></div>
        <button id="change-customer-btn" class="change-customer-btn">Change Customer</button>
      </div>
      
      <!-- Recent Customers -->
      <div class="recent-customers">
        <div style="margin-bottom: 5px; font-weight: bold;">Recent Customers:</div>
        <div id="recent-customers-list">
          <!-- Will be populated by JavaScript -->
        </div>
      </div>
      
      <!-- Hidden customer ID field -->
      <input type="hidden" id="customer-id" value="">
      
      <div id="points-info" style="display:none;">
        <label>Points to be earned:</label>
        <div id="points-earned">0</div>
      </div>
    </div>

    <div class="modal-actions">
      <button id="confirmPayBtn" class="btn-confirm">Confirm</button>
      <button id="cancelPayBtn" class="btn-cancel">Cancel</button>
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

// Cart functionality
let cart = [];
let selectedCustomer = null;

// Load products with error handling
function loadProducts(category = 'all') {
  console.log('Loading products for category:', category);
  
  // Show loading state
  let grid = document.getElementById("product-grid");
  grid.innerHTML = '<div class="loading">Loading products...</div>';
  
  fetch("get_products.php?category=" + category)
    .then(res => {
      if (!res.ok) {
        throw new Error('Network response was not ok');
      }
      return res.json();
    })
    .then(data => {
      console.log('Products data:', data);
      grid.innerHTML = ""; // Clear loading state
      
      // Check if error was returned
      if (data.error) {
        grid.innerHTML = `<div class="error">Error: ${data.error}</div>`;
        return;
      }
      
      // Check if no products found
      if (data.length === 0) {
        grid.innerHTML = `<div class="no-products">No products found</div>`;
        return;
      }
      
      // Display products
      data.forEach(p => {
        grid.innerHTML += `
          <div class="product-card">
            <div class="product-img">
              ${p.image 
                ? `<img src="${p.image}" alt="${p.product_name}">`
                : `<div class="placeholder">No Image</div>`}
            </div>
            <div class="product-info">
              <h4 class="product-name">${p.product_name}</h4>
              <p class="product-price">₱${parseFloat(p.price).toFixed(2)} / ${p.unit}</p>
              <span class="stock-badge ${p.stock > 0 ? 'in-stock' : 'out-stock'}">
                ${p.stock > 0 ? 'In Stock: ' + p.stock + ' ' + p.unit : 'Out of Stock'}
              </span>
            </div>
            <button class="btn-add"
              ${p.stock > 0 
                ? `onclick="addToCart({id:${p.id}, product_name:'${p.product_name.replace(/'/g,"\\'")}', price:${p.price}, unit:'${p.unit}', stock:${p.stock}})"`
                : "disabled style='background:#777; cursor:not-allowed;'"} >
              ${p.stock > 0 ? "Add" : "Out of Stock"}
            </button>
          </div>`;
      });
    })
    .catch(error => {
      console.error('Error loading products:', error);
      grid.innerHTML = `<div class="error">Failed to load products. Please try again.</div>`;
    });
}

// Initial load
loadProducts();

// Add to cart
function addToCart(product) {
  let existing = cart.find(item => item.id === product.id);
  let step = (['kilo','meter','liter'].includes(product.unit)) ? 0.25 : 1;

  if (existing) {
    if (existing.qty + step <= product.stock) {
      existing.qty = parseFloat((existing.qty + step).toFixed(2));
    } else {
      alert("Not enough stock for " + product.product_name);
    }
  } else {
    cart.push({
      id: product.id,
      name: product.product_name,
      price: parseFloat(product.price),
      unit: product.unit,
      stock: product.stock,
      qty: step
    });
  }
  renderCart();
}

// Render cart
function renderCart() {
  let itemsEl = document.getElementById("cart-items");
  itemsEl.innerHTML = "";
  let subtotal = 0;

  cart.forEach((item, index) => {
    let line = item.qty * item.price;
    subtotal += line;

    itemsEl.innerHTML += `
      <div class="cart-item" data-index="${index}">
        <button class="remove-btn"><i class="fa fa-trash"></i></button>
        <span class="item-name">${item.name} (${item.unit})</span>
        <div class="qty-control">
          <button class="qty-btn decrease">-</button>
          <input 
            type="number" 
            class="qty-input" 
            step="0.01" 
            min="0.01" 
            max="${item.stock}" 
            value="${item.qty.toFixed(2)}">
          <button class="qty-btn increase">+</button>
        </div>
        <span class="price">₱${line.toFixed(2)}</span>
      </div>
    `;
  });

  // Apply discount
  let discountInput = document.getElementById("discount");
  let discountRate = discountInput ? parseFloat(discountInput.value) || 0 : 0;
  let discountAmount = subtotal * (discountRate / 100);
  let total = subtotal - discountAmount;

  document.getElementById("subtotal").innerText = "₱" + subtotal.toFixed(2);
  document.getElementById("total").innerText = "₱" + total.toFixed(2);

  // Update Pay Button
  let payBtn = document.getElementById("btn-pay");
  if (cart.length > 0) {
    payBtn.innerText = `Pay (₱${total.toFixed(2)})`;
    payBtn.disabled = false;
  } else {
    payBtn.innerText = "Pay (₱0.00)";
    payBtn.disabled = true;
  }

  // Attach input handlers
  document.querySelectorAll(".qty-input").forEach((input, idx) => {
    input.addEventListener("change", e => {
      let val = parseFloat(e.target.value);
      if (isNaN(val) || val < 0.01) val = 0.01;
      if (val > cart[idx].stock) {
        alert("Not enough stock for " + cart[idx].name);
        val = cart[idx].stock;
      }
      cart[idx].qty = parseFloat(val.toFixed(2));
      renderCart();
    });
  });
}

// Event listeners
document.addEventListener("input", function(e) {
  if (e.target.id === "discount") {
    renderCart();
  }
});

document.getElementById("cart-items").addEventListener("click", function(e) {
  let itemEl = e.target.closest(".cart-item");
  if (!itemEl) return;
  let index = itemEl.dataset.index;

  if (e.target.classList.contains("increase")) {
    if (cart[index].qty < cart[index].stock) {
      cart[index].qty++;
    } else {
      alert("Not enough stock for " + cart[index].name);
    }
    renderCart();
  }

  if (e.target.classList.contains("decrease")) {
    if (cart[index].qty > 1) {
      cart[index].qty--;
    } else {
      cart.splice(index, 1);
    }
    renderCart();
  }

  if (e.target.closest(".remove-btn")) {
    cart.splice(index, 1);
    renderCart();
  }
});

// Checkout
let currentTotal = 0;

function checkout() {
  if (cart.length === 0) return alert("Cart is empty!");

  let subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
  let discountRate = parseFloat(document.getElementById("discount").value) || 0;
  let discountAmount = subtotal * (discountRate / 100);
  let total = subtotal - discountAmount;

  currentTotal = total;
  document.getElementById("payment-amount").textContent = "Total: ₱" + total.toFixed(2);

  // Reset modal
  document.getElementById("payment-method").value = "Cash";
  document.getElementById("amount-received").value = "";
  document.getElementById("change-display").textContent = "Change: ₱0.00";
  document.getElementById("cash-section").style.display = "block";
  document.getElementById("customer-section").style.display = "block";
  document.getElementById("customer-search").value = "";
  document.getElementById("customer-search-results").style.display = "none";
  document.getElementById("selected-customer").style.display = "none";
  document.getElementById("customer-id").value = "";
  document.getElementById("points-info").style.display = "none";
  document.getElementById("points-earned").textContent = "0";
  
  // Load recent customers
  loadRecentCustomers();

  // Show modal with new approach
  const modal = document.getElementById("paymentModal");
  modal.classList.add('show');
}

// Payment method change
document.getElementById("payment-method").addEventListener("change", function() {
  // Always show customer section
  document.getElementById("customer-section").style.display = "block";
  
  if (this.value === "Cash") {
    document.getElementById("cash-section").style.display = "block";
  } else {
    document.getElementById("cash-section").style.display = "none";
  }
});

// Customer search functionality
document.getElementById("customer-search").addEventListener("input", function() {
  const query = this.value.trim();
  
  if (query.length < 2) {
    document.getElementById("customer-search-results").style.display = "none";
    return;
  }
  
  fetch('search_customers.php?q=' + encodeURIComponent(query))
    .then(res => res.json())
    .then(data => {
      const resultsContainer = document.getElementById("customer-search-results");
      resultsContainer.innerHTML = "";
      
      if (data.length === 0) {
        resultsContainer.innerHTML = '<div class="customer-search-result-item">No customers found</div>';
      } else {
        data.forEach(customer => {
          const loyalBadge = customer.is_loyal ? '<span class="loyal-badge">Loyal</span>' : '';
          resultsContainer.innerHTML += `
            <div class="customer-search-result-item" data-id="${customer.id}" data-is-loyal="${customer.is_loyal}">
              <div class="customer-name">${customer.name} ${loyalBadge}</div>
              <div class="customer-details">${customer.phone || 'No phone number'}</div>
            </div>
          `;
        });
      }
      
      resultsContainer.style.display = "block";
      
      // Add click event to results
      document.querySelectorAll(".customer-search-result-item").forEach(item => {
        item.addEventListener("click", function() {
          selectCustomer(
            this.dataset.id,
            this.querySelector(".customer-name").textContent.replace('Loyal', '').trim(),
            this.querySelector(".customer-details").textContent,
            this.dataset.is_loyal === "1"
          );
        });
      });
    });
});

// Select customer function
function selectCustomer(id, name, details, isLoyal) {
  selectedCustomer = { id, name, details, isLoyal };
  
  // Update UI
  document.getElementById("selected-customer-name").textContent = name;
  document.getElementById("selected-customer-details").textContent = details;
  document.getElementById("customer-id").value = id;
  document.getElementById("customer-search").value = "";
  document.getElementById("customer-search-results").style.display = "none";
  document.getElementById("selected-customer").style.display = "block";
  
  // Show points if loyal customer
  if (isLoyal) {
    calculatePoints();
    document.getElementById("points-info").style.display = "block";
  } else {
    document.getElementById("points-info").style.display = "none";
    document.getElementById("points-earned").textContent = "0";
  }
}

// Change customer button
document.getElementById("change-customer-btn").addEventListener("click", function() {
  document.getElementById("selected-customer").style.display = "none";
  document.getElementById("customer-id").value = "";
  document.getElementById("customer-search").focus();
  document.getElementById("points-info").style.display = "none";
  selectedCustomer = null;
});

// Load recent customers
function loadRecentCustomers() {
  fetch('get_recent_customers.php')
    .then(res => res.json())
    .then(data => {
      const recentList = document.getElementById("recent-customers-list");
      recentList.innerHTML = "";
      
      if (data.length === 0) {
        recentList.innerHTML = '<div style="color: var(--muted);">No recent customers</div>';
      } else {
        data.forEach(customer => {
          const date = new Date(customer.last_purchase);
          const formattedDate = date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric',
            year: date.getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined
          });
          
          recentList.innerHTML += `
            <div class="recent-customer-item" data-id="${customer.id}" data-is-loyal="${customer.is_loyal}">
              <div class="recent-customer-name">${customer.name}</div>
              <div class="recent-customer-date">${formattedDate}</div>
            </div>
          `;
        });
        
        // Add click event to recent customers
        document.querySelectorAll(".recent-customer-item").forEach(item => {
          item.addEventListener("click", function() {
            selectCustomer(
              this.dataset.id,
              this.querySelector(".recent-customer-name").textContent,
              `Last purchase: ${this.querySelector(".recent-customer-date").textContent}`,
              this.dataset.is_loyal === "1"
            );
          });
        });
      }
    });
}

// Calculate points function
function calculatePoints() {
  if (!selectedCustomer || !selectedCustomer.isLoyal) return;
  
  fetch('get_points.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({cart: cart})
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      document.getElementById("points-earned").textContent = data.points;
    }
  });
}

// Calculate change
document.getElementById("amount-received").addEventListener("input", function() {
  let received = parseFloat(this.value) || 0;
  let change = received - currentTotal;
  document.getElementById("change-display").textContent = 
    "Change: ₱" + (change >= 0 ? change.toFixed(2) : "0.00");
});

// Modal controls
document.getElementById("cancelPayBtn").addEventListener("click", function() {
  const modal = document.getElementById("paymentModal");
  modal.classList.remove('show');
});

document.getElementById("confirmPayBtn").addEventListener("click", function() {
  const payment = document.getElementById("payment-method").value;
  
  if (payment === 'Cash') {
    const received = parseFloat(document.getElementById("amount-received").value) || 0;
    if (received < currentTotal) {
      alert('Amount received is not enough!');
      return;
    }
  }
  
  const customer_id = document.getElementById("customer-id").value;
  
  // Only require customer for Utang (credit)
  if (payment === 'Utang' && !customer_id) {
    alert("Please select a customer for Utang!");
    return;
  }
  
  // Get points if customer is loyal and selected
  let pointsEarned = 0;
  if (customer_id && selectedCustomer && selectedCustomer.isLoyal) {
    pointsEarned = parseInt(document.getElementById("points-earned").textContent) || 0;
  }
  
  fetch('process_sale.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ 
      cart, 
      payment, 
      customer_id, 
      points_earned: pointsEarned,
      user_id: <?= json_encode($_SESSION['user']['id'] ?? $_SESSION['id'] ?? null) ?> 
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      let message = 'Sale recorded! Sale ID: ' + data.sale_id;
      if (pointsEarned > 0) {
        message += '\nPoints earned: ' + pointsEarned;
      }
      alert(message);
      cart = [];
      renderCart();
      loadProducts();
    } else {
      alert('Error: ' + data.message);
    }
    const modal = document.getElementById("paymentModal");
    modal.classList.remove('show');
  });
});

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById("paymentModal");
  if (event.target === modal) {
    modal.classList.remove('show');
  }
}

// Close search results when clicking outside
document.addEventListener("click", function(event) {
  const searchContainer = document.querySelector(".customer-search-container");
  if (!searchContainer.contains(event.target)) {
    document.getElementById("customer-search-results").style.display = "none";
  }
});

// Category scrolling
function scrollCategories(amount) {
  document.getElementById("categories").scrollBy({
    left: amount,
    behavior: 'smooth'
  });
}

// Search functionality
document.getElementById("search-input").addEventListener("input", function(e) {
  let query = e.target.value.trim().toLowerCase();

  if (query === "") {
    loadProducts("all");
    return;
  }

  let matchedCategory = null;
  document.querySelectorAll(".cat-item span").forEach(el => {
    if (el.textContent.toLowerCase().includes(query)) {
      matchedCategory = el.textContent;
    }
  });

  let url = "get_products.php?";
  if (matchedCategory) {
    url += "category=" + encodeURIComponent(matchedCategory) + "&";
  }
  url += "search=" + encodeURIComponent(query);

  fetch(url)
    .then(res => res.json())
    .then(data => {
      let grid = document.getElementById("product-grid");
      grid.innerHTML = "";
      
      // Check for error in response
      if (data.error) {
        grid.innerHTML = `<div class="error">Error: ${data.error}</div>`;
        return;
      }
      
      // Check if no products found
      if (data.length === 0) {
        grid.innerHTML = `<div class="no-products">No products found</div>`;
        return;
      }
      
      data.forEach(p => {
        grid.innerHTML += `
          <div class="product-card">
            <div class="product-img">
              ${p.image 
                ? `<img src="${p.image}" alt="${p.product_name}">`
                : `<div class="placeholder">No Image</div>`}
            </div>
            <div class="product-info">
              <h4 class="product-name">${p.product_name}</h4>
              <p class="product-price">₱${parseFloat(p.price).toFixed(2)} / ${p.unit}</p>
              <span class="stock-badge ${p.stock > 0 ? 'in-stock' : 'out-stock'}">
                ${p.stock > 0 ? 'In Stock: ' + p.stock + ' ' + p.unit : 'Out of Stock'}
              </span>
            </div>
            <button class="btn-add"
              ${p.stock > 0 
               ? `onclick="addToCart({id:${p.id}, product_name:'${p.product_name.replace(/'/g,"\\'")}', price:${p.price}, unit:'${p.unit}', stock:${p.stock}})"`
               : "disabled style='background:#777; cursor:not-allowed;'"} >
              ${p.stock > 0 ? "Add" : "Out of Stock"}
            </button>
          </div>`;
      });
    });
});

// Category active state management
document.addEventListener('DOMContentLoaded', function() {
  // Set first category as active by default
  const firstCategory = document.querySelector('.cat-item');
  if (firstCategory) {
    firstCategory.classList.add('active');
  }
  
  // Add click event to categories
  document.querySelectorAll('.cat-item').forEach(item => {
    item.addEventListener('click', function() {
      // Remove active class from all categories
      document.querySelectorAll('.cat-item').forEach(cat => {
        cat.classList.remove('active');
      });
      
      // Add active class to clicked category
      this.classList.add('active');
    });
  });
});
</script>

</body>
</html>