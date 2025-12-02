<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Set theme from session or default to dark
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'dark';
}
 $currentTheme = $_SESSION['theme'];

 $displayName = $_SESSION['user']['username'] ?? $_SESSION['username'] ?? 'Guest';

// ✅ Handle Partial Payment with Auto-Deduction + Logging to payments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $customer_id = intval($_POST['customer_id']);
    $amount = floatval($_POST['amount']);

    // Get all unpaid utang records for this customer (oldest first)
    $q = "SELECT * FROM utang WHERE customer_id = ? AND status = 'unpaid' ORDER BY created_at ASC";
    $stmt = $conn->prepare($q);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $utangList = $stmt->get_result();

    $remaining = $amount;

    while ($row = $utangList->fetch_assoc()) {
        if ($remaining <= 0) break;

        $utang_id = $row['id'];
        $sale_id = $row['sale_id'];
        $balance = $row['amount'];

        if ($remaining >= $balance) {
            // Full payment for this utang
            $remaining -= $balance;

            // Mark utang as paid
            $conn->query("UPDATE utang SET amount = 0, status = 'paid' WHERE id = $utang_id");

            // Update sales paid_amount
            $conn->query("UPDATE sales SET paid_amount = paid_amount + $balance WHERE id = $sale_id");

            // Log payment
            $log = $conn->prepare("INSERT INTO payments (sale_id, amount, paid_at) VALUES (?,?,NOW())");
            $log->bind_param("id", $sale_id, $balance);
            $log->execute();

        } else {
            // Partial payment
            $new_balance = $balance - $remaining;

            $conn->query("UPDATE utang SET amount = $new_balance WHERE id = $utang_id");
            $conn->query("UPDATE sales SET paid_amount = paid_amount + $remaining WHERE id = $sale_id");

            // Log payment
            $log = $conn->prepare("INSERT INTO payments (sale_id, amount, paid_at) VALUES (?,?,NOW())");
            $log->bind_param("id", $sale_id, $remaining);
            $log->execute();

            $remaining = 0;
        }
    }

    header("Location: customers.php?view_utang=" . $customer_id);
    exit;
}

// ✅ Add Reminder (general or customer-specific)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_reminder'])) {
    $note = $_POST['note'];
    $cid  = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : NULL;
    $due  = !empty($_POST['due_date']) ? $_POST['due_date'] : NULL;

    $stmt = $conn->prepare("INSERT INTO customer_reminders (note, customer_id, due_date) VALUES (?,?,?)");
    $stmt->bind_param("sis", $note, $cid, $due);
    $stmt->execute();
    header("Location: customers.php");
    exit;
}

// ✅ Delete Reminder
if (isset($_GET['delete_reminder'])) {
    $rid = intval($_GET['delete_reminder']);
    $conn->query("DELETE FROM customer_reminders WHERE id=$rid");
    header("Location: customers.php");
    exit;
}

// ✅ Fetch Reminders
 $reminders = $conn->query("
    SELECT r.*, c.name AS customer_name 
    FROM customer_reminders r
    LEFT JOIN customers c ON r.customer_id = c.id
    ORDER BY r.created_at DESC
");

// ✅ Customers list
 $sql = "
    SELECT c.id, c.name, c.phone, c.email, c.address,
           COALESCE(SUM(CASE WHEN u.status='unpaid' THEN u.amount ELSE 0 END),0) as total_utang
    FROM customers c
    LEFT JOIN utang u ON c.id = u.customer_id
    GROUP BY c.id
";
 $customers = $conn->query($sql);

// ✅ Get current user data for profile card
 $currentUserId = $_SESSION['user']['id'] ?? 0;
 $currentUser = null;
if ($currentUserId) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentUser = $result->fetch_assoc();
}

// ✅ Utang details
 $utangDetails = [];
 $cust_id = null;
if (isset($_GET['view_utang'])) {
    $cust_id = intval($_GET['view_utang']);
    $q = "
        SELECT u.id as utang_id, u.amount as balance, u.status, u.created_at,
               s.id as sale_id, s.total_amount, s.paid_amount,
               si.quantity, si.price, si.subtotal, p.product_name
        FROM utang u
        JOIN sales s ON u.sale_id = s.id
        JOIN sales_items si ON s.id = si.sale_id
        JOIN products p ON si.product_id = p.id
        WHERE u.customer_id = ?
        ORDER BY u.created_at ASC
    ";
    $stmt = $conn->prepare($q);
    $stmt->bind_param("i", $cust_id);
    $stmt->execute();
    $utangDetails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Customer Credit Management - POS</title>
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

    /* Expandable Card Styles - Side by Side Layout */
    .cards {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      margin-bottom: 22px;
    }
    
    .expandable-card {
      background: var(--card);
      border-radius:16px;
      box-shadow: var(--neu-shadow);
      overflow: hidden;
      flex: 1;
      min-width: 280px;
      max-width: calc(50% - 8px);
    }
    
    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 16px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .card-header:hover {
      background: rgba(255,255,255,0.05);
    }
    
    .card-header h3 {
      margin: 0;
      color: var(--text-light);
      font-size: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .card-header h3 i {
      color: var(--accent);
      font-size: 14px;
    }
    
    .expand-icon {
      color: var(--muted);
      transition: transform 0.3s ease;
      font-size: 12px;
    }
    
    .card-content {
      max-height: 0;
      overflow: hidden;
      padding: 0 16px;
      transition: max-height 0.4s ease, padding 0.4s ease;
    }
    
    .expandable-card.expanded .card-content {
      max-height: 1000px;
      padding: 0 16px 16px;
    }
    
    .expandable-card.expanded .expand-icon {
      transform: rotate(180deg);
    }
    
    /* Form styling - Minimalist */
    .reminder-form {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    
    .reminder-options {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }
    
    .form-group {
      display: flex;
      flex-direction: column;
      margin-bottom: 6px;
    }
    
    .form-group label {
      font-size: 11px;
      margin-bottom: 2px;
      color: var(--muted);
    }
    
    /* Enhanced Neumorphic Inputs - Smaller */
    .neu-input, .neu-select, .neu-textarea {
      padding: 8px 10px;
      border: none;
      border-radius: 8px;
      font-size: 12px;
      background: var(--bg);
      color: var(--text-light);
      box-shadow: 
        inset 3px 3px 6px rgba(0,0,0,0.4),
        inset -3px -3px 6px rgba(255,255,255,0.05);
      transition: all 0.2s ease;
      outline: none;
      width: 100%;
      box-sizing: border-box;
    }
    
    .neu-input:focus, .neu-select:focus, .neu-textarea:focus {
      box-shadow: 
        inset 2px 2px 4px rgba(0,0,0,0.4),
        inset -2px -2px 4px rgba(255,255,255,0.05),
        0 0 0 1px var(--accent);
    }
    
    /* Special styling for textarea */
    .neu-textarea {
      resize: vertical;
      min-height: 50px;
      font-family: inherit;
    }
    
    /* Special styling for select */
    .neu-select {
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 12 12'%3E%3Cpath fill='%23C7C8CE' d='M10.293 3.293L6 7.586 1.707 3.293A1 1 0 00.293 4.707l5 5a1 1 0 001.414 0l5-5a1 1 0 10-1.414-1.414z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 8px center;
      padding-right: 25px;
    }
    
    /* Neumorphic buttons - Smaller */
    .btn {
      padding: 8px 14px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 12px;
      transition: all 0.2s;
      box-shadow: 
        4px 4px 8px rgba(0,0,0,0.3),
       -4px -4px 8px rgba(255,255,255,0.02);
      color: #fff;
    }
    
    .btn:hover {
      box-shadow: 
        inset 2px 2px 4px rgba(0,0,0,0.3),
        inset -2px -2px 4px rgba(255,255,255,0.02);
    }
    
    .btn-add {
      background: var(--accent);
    }
    
    .btn-add:hover {
      background: #e04a2a;
    }
    
    .btn-view {
      background: #29b6f6;
    }
    
    .btn-view:hover {
      background: #0288d1;
    }
    
    .btn-pay {
      background: #66bb6a;
    }
    
    .btn-pay:hover {
      background: #43a047;
    }

    /* Table styling */
    .table-container {
      background: var(--card);
      border-radius:16px;
      box-shadow: var(--neu-shadow);
      padding: 20px;
      overflow: hidden;
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
    .reminders-card{ 
      padding:12px; 
      font-size:14px;
      background: var(--card);
      border-radius:16px;
      box-shadow: var(--neu-shadow);
    }
    .reminders-card h4{ 
      margin:0 0 10px; 
      color:var(--text-light); 
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .reminders-list {
      max-height: 300px;
      overflow-y: auto;
      border-radius: 8px;
      background: rgba(0,0,0,0.2);
      padding: 8px;
      margin-right: -8px;
      padding-right: 16px;
    }
    
    /* Custom scrollbar for reminders list */
    .reminders-list::-webkit-scrollbar {
      width: 8px;
    }
    
    .reminders-list::-webkit-scrollbar-track {
      background: rgba(255,255,255,0.03);
      border-radius: 6px;
      margin: 4px 0;
    }
    
    .reminders-list::-webkit-scrollbar-thumb {
      background: var(--muted);
      border-radius: 6px;
      border: 2px solid rgba(0,0,0,0.2);
      box-shadow: 
        inset 1px 1px 3px rgba(0,0,0,0.3),
        inset -1px -1px 3px rgba(255,255,255,0.05);
    }
    
    .reminders-list::-webkit-scrollbar-thumb:hover {
      background: var(--accent);
    }
    
    .reminders-list ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .reminders-list li {
      padding: 10px;
      margin-bottom: 8px;
      border-radius: 8px;
      background: rgba(255,255,255,0.05);
      border-left: 4px solid var(--muted);
    }
    .reminders-list li.due {
      background: rgba(242,95,58,0.1);
      border-left: 4px solid var(--accent);
    }
    .reminders-list li small {
      color: var(--muted);
      font-size: 12px;
    }
    .reminders-list li a {
      color: #ff5252;
      float: right;
      text-decoration: none;
    }
    .reminders-list li a:hover {
      color: #ff1744;
    }
    
    /* Neumorphic Modal Styling */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: var(--modal-bg);
      z-index: 1000;
      justify-content: center;
      align-items: center;
      backdrop-filter: blur(5px);
    }
    
    .modal-content {
      background: var(--card);
      border-radius:20px;
      box-shadow: var(--neu-shadow);
      padding: 30px;
      width: 90%;
      max-width: 1200px;
      max-height: 90vh;
      overflow: auto;
      position: relative;
      border: 1px solid var(--border-light);
    }
    
    /* Custom scrollbar for the entire modal */
    .modal-content::-webkit-scrollbar {
      width: 14px;
    }
    
    .modal-content::-webkit-scrollbar-track {
      background: rgba(255,255,255,0.05);
      border-radius: 10px;
      margin: 10px;
      box-shadow: 
        inset 2px 2px 4px rgba(0,0,0,0.2),
        inset -2px -2px 4px rgba(255,255,255,0.02);
    }
    
    .modal-content::-webkit-scrollbar-thumb {
      background: var(--muted);
      border-radius: 10px;
      border: 3px solid var(--card);
      box-shadow: 
        inset 2px 2px 4px rgba(0,0,0,0.3),
        inset -2px -2px 4px rgba(255,255,255,0.05);
    }
    
    .modal-content::-webkit-scrollbar-thumb:hover {
      background: var(--accent);
    }
    
    .modal-content::-webkit-scrollbar-corner {
      background: transparent;
    }
    
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid var(--border-light);
      padding-right: 50px; /* Space for close button */
    }
    
    .modal-header h2 {
      margin: 0;
      color: var(--text-light);
      font-size: 24px;
      font-weight: 600;
    }
    
    .modal-header .balance {
      color: var(--accent);
      font-size: 20px;
      font-weight: 600;
    }
    
    .close {
      position: absolute;
      top: 20px;
      right: 20px;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: var(--bg);
      color: var(--muted);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 18px;
      box-shadow: var(--neu-shadow);
      transition: all 0.2s ease;
      border: none;
      z-index: 10;
    }
    
    .close:hover {
      color: #fff;
      box-shadow: var(--neu-active-shadow);
      transform: rotate(90deg);
    }
    
    .modal-body {
      display: flex;
      gap: 30px;
    }
    
    .modal-section {
      flex: 1;
    }
    
    .modal-section h3 {
      color: var(--text-light);
      margin-bottom: 15px;
      font-size: 18px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .modal-section h3 i {
      color: var(--accent);
    }
    
    /* Payment form in modal */
    .payment-form {
      display: flex;
      gap: 12px;
      align-items: center;
      margin-bottom: 25px;
      padding: 16px;
      background: rgba(0,0,0,0.3);
      border-radius:12px;
      box-shadow: 
        inset 4px 4px 8px rgba(0,0,0,0.4),
        inset -4px -4px 8px rgba(255,255,255,0.05);
    }
    
    .payment-form input {
      flex: 1;
      padding: 12px 16px;
      border: none;
      border-radius:10px;
      background: var(--bg);
      color: var(--text-light);
      font-size: 14px;
      box-shadow: 
        inset 3px 3px 6px rgba(0,0,0,0.4),
        inset -3px -3px 6px rgba(255,255,255,0.05);
    }
    
    .payment-form input:focus {
      outline: none;
      box-shadow: 
        inset 2px 2px 4px rgba(0,0,0,0.4),
        inset -2px -2px 4px rgba(255,255,255,0.05),
        0 0 0 1px var(--accent);
    }
    
    /* Tables in modal */
    .modal-table {
      background: rgba(0,0,0,0.2);
      border-radius:12px;
      overflow: hidden;
      margin-bottom: 15px;
      box-shadow: 
        inset 2px 2px 4px rgba(0,0,0,0.4),
        inset -2px -2px 4px rgba(255,255,255,0.05);
    }
    
    .modal-table table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .modal-table th {
      background: rgba(255,255,255,0.05);
      padding: 12px;
      text-align: left;
      font-weight: 600;
      color: var(--muted);
      font-size: 13px;
    }
    
    .modal-table td {
      padding: 12px;
      border-bottom: 1px solid var(--border-light);
      font-size: 14px;
    }
    
    .modal-table tr:last-child td {
      border-bottom: none;
    }
    
    .modal-table tr:hover td {
      background: rgba(255,255,255,0.03);
    }
    
    .items-table {
      margin-left: 20px;
      width: calc(100% - 20px);
      background: rgba(0,0,0,0.15);
      border-radius:8px;
      margin-bottom: 15px;
    }
    
    .items-table th {
      background: rgba(255,255,255,0.03);
      padding: 8px;
      font-size: 12px;
    }
    
    .items-table td {
      padding: 8px;
      font-size: 13px;
    }
    
    .total-balance {
      text-align: right;
      margin-top: 20px;
      padding: 15px;
      background: rgba(242,95,58,0.1);
      border-radius:10px;
      box-shadow: 
        inset 2px 2px 4px rgba(0,0,0,0.3),
        inset -2px -2px 4px rgba(255,255,255,0.05);
    }
    
    .total-balance h3 {
      margin: 0;
      color: var(--accent);
      font-size: 20px;
    }
    
    .payment-history {
      max-height: 60vh;
      overflow-y: auto;
      padding-right: 5px;
      margin-right: -5px;
    }
    
    /* Custom scrollbar for payment history */
    .payment-history::-webkit-scrollbar {
      width: 10px;
    }
    
    .payment-history::-webkit-scrollbar-track {
      background: rgba(255,255,255,0.05);
      border-radius: 10px;
      margin: 5px 0;
    }
    
    .payment-history::-webkit-scrollbar-thumb {
      background: var(--muted);
      border-radius: 10px;
      border: 2px solid var(--card);
      box-shadow: 
        inset 2px 2px 4px rgba(0,0,0,0.3),
        inset -2px -2px 4px rgba(255,255,255,0.05);
    }
    
    .payment-history::-webkit-scrollbar-thumb:hover {
      background: var(--accent);
    }
    
    .payment-summary {
      margin-top: 15px;
      padding: 12px;
      background: rgba(255,255,255,0.05);
      border-radius:8px;
      font-weight: 600;
    }
    
    .payment-summary td {
      padding: 8px !important;
      font-size: 15px !important;
    }

    /* Reminder popup */
    .reminder-popup {
      position: fixed;
      top: 20px;
      right: 20px;
      background: var(--card);
      color: var(--text-light);
      border: 1px solid var(--accent);
      border-radius: 12px;
      padding: 16px;
      box-shadow: var(--neu-shadow);
      font-size: 14px;
      z-index: 9999;
      max-width: 300px;
      animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
    
    .reminder-popup strong {
      color: var(--accent);
    }

    /* Responsive */
    @media(max-width:1000px){
      .right-panel{ display:none; }
      .expandable-card {
        max-width: 100%;
      }
      .modal-body {
        flex-direction: column;
      }
    }
    
    @media(max-width:600px){
      .cards {
        flex-direction: column;
      }
      
      .reminder-options {
        grid-template-columns: 1fr;
      }
      
      .modal-content {
        width: 95%;
        padding: 20px;
      }
      
      /* Adjust scrollbar for mobile */
      .modal-content::-webkit-scrollbar {
        width: 10px;
      }
      
      .modal-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        padding-right: 20px; /* Reduce space for close button on mobile */
      }
      
      .payment-form {
        flex-direction: column;
        align-items: stretch;
      }
      
      .items-table {
        margin-left: 0;
        width: 100%;
      }
      
      .payment-history {
        max-height: 40vh;
      }
      
      .reminders-list {
        padding-right: 12px;
        margin-right: -4px;
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
      <a href="customers.php" class="active">
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
      <h1>Customer Credit Management</h1>
      <div style="display: flex; align-items: center;">
        <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
          <i class="fas fa-<?php echo $currentTheme === 'light' ? 'sun' : 'moon'; ?>"></i>
        </button>
        <div class="clock-card neu">
          <span id="clock"></span>
        </div>
      </div>
    </div>

    <div class="cards">
      <!-- Add Reminder - Expandable Card -->
      <div class="expandable-card" id="reminder-card">
        <div class="card-header" onclick="toggleCard('reminder-card')">
          <h3><i class="fas fa-bell"></i> Add Reminder</h3>
          <i class="fas fa-chevron-down expand-icon"></i>
        </div>
        <div class="card-content">
          <form method="post" class="reminder-form">
            <input type="hidden" name="add_reminder" value="1">
            
            <div class="form-group">
              <label for="note">Reminder Note</label>
              <textarea id="note" name="note" class="neu-textarea" required></textarea>
            </div>
            
            <div class="reminder-options">
              <div class="form-group">
                <label for="customer_id">For Customer</label>
                <select id="customer_id" name="customer_id" class="neu-select">
                  <option value="">-- General --</option>
                  <?php
                  $custRes = $conn->query("SELECT id, name FROM customers ORDER BY name ASC");
                  while($c = $custRes->fetch_assoc()):
                  ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              
              <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" class="neu-input">
              </div>
            </div>
            
            <div style="text-align:right; margin-top:6px;">
              <button type="submit" class="btn btn-add">Save Reminder</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Customer List -->
    <div class="table-container">
      <h2 style="margin-top: 0; color: var(--text-light);">Customer Credit List</h2>
      <table>
        <tr>
          <th>Name</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Address</th>
          <th>Total Credit</th>
          <th>Action</th>
        </tr>
        <?php while($row=$customers->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['phone']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['address']) ?></td>
            <td>₱<?= number_format($row['total_utang'],2) ?></td>
            <td>
              <a href="customers.php?view_utang=<?= $row['id'] ?>" class="btn btn-view">
                View Credit
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
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

    <div class="reminders-card neu">
      <h4><i class="fas fa-list"></i> Saved Reminders</h4>
      <div class="reminders-list">
        <ul>
          <?php 
          $today = date("Y-m-d"); 
          $dueToday = [];
          if($reminders->num_rows > 0): 
            while($r = $reminders->fetch_assoc()): 
              $isDue = $r['due_date'] && $r['due_date'] <= $today;
              if($isDue) { $dueToday[] = ($r['note']) . " (Due: ".$r['due_date'].")"; }
          ?>
            <li class="<?= $isDue ? 'due' : '' ?>">
              <?= htmlspecialchars($r['note']) ?>
              <?php if($r['customer_name']): ?>
                <br><small>For: <?= htmlspecialchars($r['customer_name']) ?></small>
              <?php endif; ?>
              <?php if($r['due_date']): ?>
                <br><small <?= $isDue ? 'style="color: var(--accent); font-weight:bold;"' : '' ?>>
                  Due: <?= $r['due_date'] ?> <?= $isDue ? '⚠️' : '' ?>
                </small>
              <?php endif; ?>
              <br><small><?= $r['created_at'] ?></small>
              <a href="customers.php?delete_reminder=<?= $r['id'] ?>" 
                 onclick="return confirm('Delete this reminder?')">
                <i class="fas fa-trash"></i>
              </a>
            </li>
          <?php endwhile; else: ?>
            <li>No reminders yet</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </aside>
</div>

<!-- Modal for Utang -->
<?php if(!empty($utangDetails)): ?>
<div class="modal" id="utangModal" style="display:flex;">
  <div class="modal-content">
    <?php
    // ✅ Group sales properly & compute grand_total here
    $grouped=[];
    $grand_total=0;
    foreach($utangDetails as $row){
      $grouped[$row['sale_id']]['info']=[
        'created_at'=>$row['created_at'],
        'sale_id'=>$row['sale_id'],
        'total'=>$row['total_amount'],
        'paid'=>$row['paid_amount'],
        'balance'=>$row['balance'],
        'status'=>$row['status']
      ];
      $grouped[$row['sale_id']]['items'][]=$row;
    }
    foreach($grouped as $sale){ $grand_total += $sale['info']['balance']; }
    $custName = $conn->query("SELECT name FROM customers WHERE id=$cust_id")->fetch_assoc()['name'];
    ?>

    <!-- Modal Header -->
    <div class="modal-header">
      <h2>Credit Details - <?= htmlspecialchars($custName) ?></h2>
      <div class="balance">Overall Balance: ₱<?= number_format($grand_total,2) ?></div>
    </div>

    <!-- Modal Body -->
    <div class="modal-body">
      <!-- LEFT SIDE: Utang by Sale -->
      <div class="modal-section">
        <h3><i class="fas fa-money-bill-wave"></i> Outstanding Balances</h3>
        
        <!-- Payment form -->
        <form method="post" onsubmit="return validatePayment(this)" class="payment-form">
          <input type="hidden" name="add_payment" value="1">
          <input type="hidden" name="customer_id" value="<?= $cust_id ?>">
          <input type="number" name="amount" step="0.01" placeholder="Enter Amount (₱)" required>
          <button type="submit" class="btn btn-pay">Apply Payment</button>
        </form>

        <!-- Sales with balances -->
        <?php foreach($grouped as $sale): ?>
          <div class="modal-table">
            <table>
              <tr>
                <th>Date</th>
                <th>Sale ID</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Balance</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
              <tr>
                <td><?= $sale['info']['created_at'] ?></td>
                <td>#<?= $sale['info']['sale_id'] ?></td>
                <td>₱<?= number_format($sale['info']['total'],2) ?></td>
                <td>₱<?= number_format($sale['info']['paid'],2) ?></td>
                <td>₱<?= number_format($sale['info']['balance'],2) ?></td>
                <td><?= $sale['info']['status']=="paid"?"✅ Paid":"Unpaid" ?></td>
                <td><button class="btn btn-view" onclick="toggleItems(<?= $sale['info']['sale_id'] ?>)">View Items</button></td>
              </tr>
            </table>
          </div>
          
          <div id="items-<?= $sale['info']['sale_id'] ?>" class="items-table" style="display:none;">
            <table>
              <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Subtotal</th>
              </tr>
              <?php foreach($sale['items'] as $it): ?>
                <tr>
                  <td><?= $it['product_name'] ?></td>
                  <td><?= $it['quantity'] ?></td>
                  <td>₱<?= number_format($it['price'],2) ?></td>
                  <td>₱<?= number_format($it['subtotal'],2) ?></td>
                </tr>
              <?php endforeach; ?>
            </table>
          </div>
        <?php endforeach; ?>

        <!-- Overall balance at bottom -->
        <div class="total-balance">
          <h3>Overall Balance: ₱<?= number_format($grand_total,2) ?></h3>
        </div>
      </div>

      <!-- RIGHT SIDE: Payment History -->
      <div class="modal-section">
        <h3><i class="fas fa-history"></i> Payment History</h3>
        <div class="payment-history">
          <?php
          $payRes = $conn->query("
            SELECT p.paid_at, p.amount, s.id as sale_id
            FROM payments p
            JOIN sales s ON p.sale_id = s.id
            JOIN utang u ON u.sale_id = s.id
            WHERE u.customer_id = $cust_id
            ORDER BY p.paid_at ASC
          ");
          if ($payRes && $payRes->num_rows > 0): ?>
            <div class="modal-table">
              <table>
                <tr>
                  <th>Date</th>
                  <th>Sale</th>
                  <th>Amount</th>
                </tr>
                <?php $totalPayments=0; while($pay = $payRes->fetch_assoc()): $totalPayments+=$pay['amount']; ?>
                  <tr>
                    <td><?= $pay['paid_at'] ?></td>
                    <td>#<?= $pay['sale_id'] ?></td>
                    <td>₱<?= number_format($pay['amount'],2) ?></td>
                  </tr>
                <?php endwhile; ?>
                <tr class="payment-summary">
                  <td colspan="2" style="text-align:right;">Total Paid:</td>
                  <td>₱<?= number_format($totalPayments,2) ?></td>
                </tr>
              </table>
            </div>
          <?php else: ?>
            <div style="text-align:center; padding: 30px; color: var(--muted);">
              <i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
              <p>No payments yet</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <!-- Close button -->
    <button class="close" onclick="document.getElementById('utangModal').style.display='none'">
      <i class="fas fa-times"></i>
    </button>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($dueToday)): ?>
<div id="reminderPopup" class="reminder-popup">
  <strong><i class="fas fa-bell"></i> Reminders Due Today:</strong><br>
  <?= implode("<br>", array_map('htmlspecialchars', $dueToday)) ?>
</div>
<script>
  setTimeout(()=>{ 
    document.getElementById('reminderPopup').style.display='none';
  }, 8000); // auto-hide after 8 seconds
</script>
<?php endif; ?>

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

function validatePayment(form){
  let amount=parseFloat(form.amount.value);
  if(amount<=0){alert("❌ Invalid payment amount");return false;}
  return true;
}

function toggleItems(id){
  let tbl=document.getElementById("items-"+id);
  tbl.style.display=(tbl.style.display==="none")?"table":"none";
}

/* Add this new function for toggling cards */
function toggleCard(cardId) {
  const card = document.getElementById(cardId);
  if (card) {
    card.classList.toggle('expanded');
  } else {
    console.error('Card not found: ' + cardId);
  }
}

// Initialize cards to be collapsed on page load
document.addEventListener('DOMContentLoaded', function() {
  const reminderCard = document.getElementById('reminder-card');
  
  if (reminderCard) reminderCard.classList.remove('expanded');
});
</script>
</body>
</html>