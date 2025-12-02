<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Set theme from session or default to dark
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'dark';
}
 $currentTheme = $_SESSION['theme'];

 $displayName = $_SESSION['user']['username'] ?? $_SESSION['username'] ?? 'Guest';

// Check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Enhanced error handling function
function handleError($message, $conn) {
    $error = $conn->error;
    // Log to file
    file_put_contents('error_log.txt', date('Y-m-d H:i:s') . " - $message: $error\n", FILE_APPEND);
    // Display detailed error
    die("$message: $error");
}

// ✅ Handle Add New Customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $is_loyal = isset($_POST['is_loyal']) ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO customers (name, phone, email, address, is_loyal) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        handleError("Prepare failed for customer insert", $conn);
    }
    $stmt->bind_param("ssssi", $name, $phone, $email, $address, $is_loyal);
    
    if ($stmt->execute()) {
        $customer_id = $stmt->insert_id;
        
        // Log activity
        $status = $is_loyal ? "as loyal customer" : "as regular customer";
        $logText = "Added new customer: $name $status";
        $conn->query("INSERT INTO activity_logs (action) VALUES ('" . $conn->real_escape_string($logText) . "')");
        
        header("Location: customer_loyalty.php?success=1");
    } else {
        header("Location: customer_loyalty.php?error=1");
    }
    exit;
}

// ✅ Handle Mark Customer as Loyal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_loyalty'])) {
    $customer_id = intval($_POST['customer_id']);
    $is_loyal = isset($_POST['is_loyal']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE customers SET is_loyal = ? WHERE id = ?");
    if (!$stmt) {
        handleError("Prepare failed for loyalty update", $conn);
    }
    $stmt->bind_param("ii", $is_loyal, $customer_id);
    $stmt->execute();
    
    // Get customer name for logging
    $stmt = $conn->prepare("SELECT name FROM customers WHERE id = ?");
    if (!$stmt) {
        handleError("Prepare failed for customer name select", $conn);
    }
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $customer_name = $customer['name'];
    
    // Log activity
    $action = $is_loyal ? "marked as loyal" : "removed from loyalty program";
    $logText = "Customer $customer_name $action";
    $conn->query("INSERT INTO activity_logs (action) VALUES ('" . $conn->real_escape_string($logText) . "')");
    
    header("Location: customer_loyalty.php");
    exit;
}

// ✅ Handle Points Adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_points'])) {
    $customer_id = intval($_POST['customer_id']);
    $points = intval($_POST['points']);
    $reason = trim($_POST['reason']);
    
    if ($points != 0) {
        // Get current points balance
        $stmt = $conn->prepare("SELECT points_balance FROM customers WHERE id = ?");
        if (!$stmt) {
            handleError("Prepare failed for points balance select", $conn);
        }
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $current_balance = $customer['points_balance'];
        
        // Calculate new balance
        $new_balance = $current_balance + $points;
        
        // Ensure balance doesn't go negative
        if ($new_balance < 0) {
            $new_balance = 0;
            $points = -$current_balance; // Adjust points to what can actually be deducted
        }
        
        // Update customer points
        $stmt = $conn->prepare("UPDATE customers SET points_balance = ? WHERE id = ?");
        if (!$stmt) {
            handleError("Prepare failed for points balance update", $conn);
        }
        $stmt->bind_param("ii", $new_balance, $customer_id);
        $stmt->execute();
        
        // Record transaction
        if ($points > 0) {
            $stmt = $conn->prepare("INSERT INTO points_transactions (customer_id, points_earned, transaction_date) VALUES (?, ?, NOW())");
            if (!$stmt) {
                handleError("Prepare failed for points earned insert", $conn);
            }
            $stmt->bind_param("ii", $customer_id, $points);
        } else {
            $stmt = $conn->prepare("INSERT INTO points_transactions (customer_id, points_used, transaction_date) VALUES (?, ?, NOW())");
            if (!$stmt) {
                handleError("Prepare failed for points used insert", $conn);
            }
            $stmt->bind_param("ii", $customer_id, abs($points));
        }
        $stmt->execute();
        
        // Get customer name for logging
        $stmt = $conn->prepare("SELECT name FROM customers WHERE id = ?");
        if (!$stmt) {
            handleError("Prepare failed for customer name select after points", $conn);
        }
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $customer_name = $customer['name'];
        
        // Log activity
        $action = $points > 0 ? "added" : "deducted";
        $logText = "Adjusted points for customer $customer_name: $action " . abs($points) . " points. Reason: $reason";
        $conn->query("INSERT INTO activity_logs (action) VALUES ('" . $conn->real_escape_string($logText) . "')");
    }
    
    header("Location: customer_loyalty.php");
    exit;
}

// ✅ Handle Points Redemption (Updated to match altered table structure)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_points'])) {
    $customer_id = intval($_POST['customer_id']);
    $points_to_redeem = intval($_POST['points_to_redeem']);
    
    // Get current points balance
    $stmt = $conn->prepare("SELECT points_balance FROM customers WHERE id = ?");
    if (!$stmt) {
        handleError("Prepare failed for points balance select in redemption", $conn);
    }
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $current_balance = $customer['points_balance'];
    
    // Check if customer has enough points
    if ($current_balance >= $points_to_redeem) {
        // Deduct points from customer's balance
        $new_balance = $current_balance - $points_to_redeem;
        $stmt = $conn->prepare("UPDATE customers SET points_balance = ? WHERE id = ?");
        if (!$stmt) {
            handleError("Prepare failed for points balance update in redemption", $conn);
        }
        $stmt->bind_param("ii", $new_balance, $customer_id);
        $stmt->execute();
        
        // Record the redemption transaction
        $stmt = $conn->prepare("INSERT INTO points_transactions (customer_id, points_used, transaction_date) VALUES (?, ?, NOW())");
        if (!$stmt) {
            handleError("Prepare failed for redemption transaction insert", $conn);
        }
        $stmt->bind_param("ii", $customer_id, $points_to_redeem);
        $stmt->execute();
        
        // Get customer name for logging
        $stmt = $conn->prepare("SELECT name FROM customers WHERE id = ?");
        if (!$stmt) {
            handleError("Prepare failed for customer name select in redemption", $conn);
        }
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $customer_name = $customer['name'];
        
        // Log activity
        $logText = "Customer $customer_name redeemed $points_to_redeem points for commission";
        $conn->query("INSERT INTO activity_logs (action) VALUES ('" . $conn->real_escape_string($logText) . "')");
        
        // Record the payment with updated table structure
        $commission_amount = $points_to_redeem; // 1 point = 1 peso
        $payment_method = "Points Redemption";
        $sale_id = null; // Loyalty redemptions aren't tied to a specific sale
        
        $stmt = $conn->prepare("INSERT INTO payments (customer_id, sale_id, amount, payment_method, paid_at) VALUES (?, ?, ?, ?, NOW())");
        if (!$stmt) {
            handleError("Prepare failed for payment insert", $conn);
        }
        $stmt->bind_param("iids", $customer_id, $sale_id, $commission_amount, $payment_method);
        $stmt->execute();
    }
    
    header("Location: customer_loyalty.php");
    exit;
}

// ✅ Fetch Customers with loyalty status and points
 $sql = "
   SELECT c.id, c.name, c.phone, c.email, c.address, c.is_loyal, c.points_balance,
          COALESCE(SUM(CASE WHEN u.status='unpaid' THEN u.amount ELSE 0 END),0) as total_utang
   FROM customers c
   LEFT JOIN utang u ON c.id = u.customer_id
   GROUP BY c.id
   ORDER BY c.is_loyal DESC, c.name ASC
";
 $result = $conn->query($sql);
if (!$result) {
    handleError("Query failed for customers list", $conn);
}
 $customers = $result;

// ✅ Get current user data for profile card
 $currentUserId = $_SESSION['user']['id'] ?? 0;
 $currentUser = null;
if ($currentUserId) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    if (!$stmt) {
        handleError("Prepare failed for user select", $conn);
    }
    $stmt->bind_param("i", $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentUser = $result->fetch_assoc();
}

// ✅ Points transaction details
 $pointsDetails = [];
 $cust_id = null;
if (isset($_GET['view_points'])) {
    $cust_id = intval($_GET['view_points']);
    $q = "
       SELECT pt.*, 
              CASE 
                WHEN pt.points_earned > 0 THEN 'Earned'
                WHEN pt.points_used > 0 THEN 'Used'
              END as transaction_type
       FROM points_transactions pt
       WHERE pt.customer_id = ?
       ORDER BY pt.transaction_date DESC
   ";
    $stmt = $conn->prepare($q);
    if (!$stmt) {
        handleError("Prepare failed for points transactions", $conn);
    }
    $stmt->bind_param("i", $cust_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pointsDetails = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Customer Loyalty - POS</title>
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
      --success: #66BB6A;
      --warning: #FFA726;
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
    .customer-form .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }
    
    .loyalty-form .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }
    
    .points-form {
      display: flex;
      flex-direction: column;
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
    
    /* Checkbox styling */
    .checkbox-group {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 8px;
    }
    
    .checkbox-group input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--accent);
    }
    
    .checkbox-group label {
      margin: 0;
      font-size: 13px;
      cursor: pointer;
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
    
    .btn-success {
      background: var(--success);
    }
    
    .btn-success:hover {
      background: #43a047;
    }
    
    .btn-warning {
      background: var(--warning);
    }
    
    .btn-warning:hover {
      background: #fb8c00;
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
    
    .loyalty-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
    }
    
    .loyal {
      background: rgba(102, 187, 106, 0.2);
      color: var(--success);
    }
    
    .not-loyal {
      background: rgba(255, 255, 255, 0.1);
      color: var(--muted);
    }
    
    .points-balance {
      font-weight: 600;
      color: var(--accent);
    }
    
    /* Success/Error message */
    .message {
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .message-success {
      background: rgba(102, 187, 106, 0.2);
      color: var(--success);
      border-left: 4px solid var(--success);
    }
    
    .message-error {
      background: rgba(244, 67, 54, 0.2);
      color: #f44336;
      border-left: 4px solid #f44336;
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
      max-width: 1000px;
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
    
    /* Points form in modal */
    .points-form-container {
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
    
    .points-form-container input, .points-form-container select {
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
    
    .points-form-container input:focus, .points-form-container select:focus {
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
    
    .points-history {
      max-height: 60vh;
      overflow-y: auto;
      padding-right: 5px;
      margin-right: -5px;
    }
    
    /* Custom scrollbar for points history */
    .points-history::-webkit-scrollbar {
      width: 10px;
    }
    
    .points-history::-webkit-scrollbar-track {
      background: rgba(255,255,255,0.05);
      border-radius: 10px;
      margin: 5px 0;
    }
    
    .points-history::-webkit-scrollbar-thumb {
      background: var(--muted);
      border-radius: 10px;
      border: 2px solid var(--card);
      box-shadow: 
        inset 2px 2px 4px rgba(0,0,0,0.3),
        inset -2px -2px 4px rgba(255,255,255,0.05);
    }
    
    .points-history::-webkit-scrollbar-thumb:hover {
      background: var(--accent);
    }
    
    .points-summary {
      margin-top: 15px;
      padding: 12px;
      background: rgba(255,255,255,0.05);
      border-radius:8px;
      font-weight: 600;
    }
    
    .points-summary td {
      padding: 8px !important;
      font-size: 15px !important;
    }
    
    .transaction-earned {
      color: var(--success);
    }
    
    .transaction-used {
      color: var(--warning);
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
      
      .customer-form .form-grid {
        grid-template-columns: 1fr;
      }
      
      .loyalty-form .form-grid {
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
      
      .points-form-container {
        flex-direction: column;
        align-items: stretch;
      }
      
      .points-history {
        max-height: 40vh;
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
      <a href="customer_loyalty.php" class="active">
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
      <h1>Customer Loyalty Program</h1>
      <div style="display: flex; align-items: center;">
        <button id="theme-toggle" class="theme-toggle" title="Toggle theme">
          <i class="fas fa-<?php echo $currentTheme === 'light' ? 'sun' : 'moon'; ?>"></i>
        </button>
        <div class="clock-card neu">
          <span id="clock"></span>
        </div>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['success'])): ?>
      <div class="message message-success">
        <i class="fas fa-check-circle"></i>
        Customer registered successfully!
      </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
      <div class="message message-error">
        <i class="fas fa-exclamation-circle"></i>
        Error registering customer. Please try again.
      </div>
    <?php endif; ?>

    <div class="cards">
      <!-- Add Customer - Expandable Card -->
      <div class="expandable-card" id="customer-card">
        <div class="card-header" onclick="toggleCard('customer-card')">
          <h3><i class="fas fa-user-plus"></i> Register New Customer</h3>
          <i class="fas fa-chevron-down expand-icon"></i>
        </div>
        <div class="card-content">
          <form method="post" class="customer-form">
            <input type="hidden" name="add_customer" value="1">
            <div class="form-grid">
              <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" class="neu-input" required>
              </div>
              <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" class="neu-input">
              </div>
              <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="neu-input">
              </div>
              <div class="form-group" style="grid-column: span 2;">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" class="neu-input">
              </div>
            </div>
            <div class="checkbox-group">
              <input type="checkbox" id="is_loyal" name="is_loyal" value="1">
              <label for="is_loyal">Enroll in Loyalty Program</label>
            </div>
            <div style="text-align:right; margin-top:8px;">
              <button type="submit" class="btn btn-add">Register Customer</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Mark Customer as Loyal - Expandable Card -->
      <div class="expandable-card" id="loyalty-card">
        <div class="card-header" onclick="toggleCard('loyalty-card')">
          <h3><i class="fas fa-star"></i> Manage Loyalty Status</h3>
          <i class="fas fa-chevron-down expand-icon"></i>
        </div>
        <div class="card-content">
          <form method="post" class="loyalty-form">
            <input type="hidden" name="toggle_loyalty" value="1">
            <div class="form-grid">
              <div class="form-group">
                <label for="customer_id">Customer</label>
                <select id="customer_id" name="customer_id" class="neu-select" required>
                  <option value="">-- Select Customer --</option>
                  <?php
                  $custRes = $conn->query("SELECT id, name FROM customers ORDER BY name ASC");
                  if (!$custRes) {
                      handleError("Query failed for customer select", $conn);
                  }
                  while($c = $custRes->fetch_assoc()):
                  ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label for="is_loyal_status">Loyalty Status</label>
                <select id="is_loyal_status" name="is_loyal" class="neu-select" required>
                  <option value="1">Loyal Customer</option>
                  <option value="0">Regular Customer</option>
                </select>
              </div>
            </div>
            <div style="text-align:right; margin-top:8px;">
              <button type="submit" class="btn btn-add">Update Status</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Adjust Points - Expandable Card -->
      <div class="expandable-card" id="points-card">
        <div class="card-header" onclick="toggleCard('points-card')">
          <h3><i class="fas fa-coins"></i> Adjust Points</h3>
          <i class="fas fa-chevron-down expand-icon"></i>
        </div>
        <div class="card-content">
          <form method="post" class="points-form">
            <input type="hidden" name="adjust_points" value="1">
            <div class="form-grid">
              <div class="form-group">
                <label for="customer_id_points">Customer</label>
                <select id="customer_id_points" name="customer_id" class="neu-select" required>
                  <option value="">-- Select Customer --</option>
                  <?php
                  $custRes = $conn->query("SELECT id, name FROM customers WHERE is_loyal = 1 ORDER BY name ASC");
                  if (!$custRes) {
                      handleError("Query failed for loyal customer select", $conn);
                  }
                  while($c = $custRes->fetch_assoc()):
                  ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label for="points">Points (+ to add, - to deduct)</label>
                <input type="number" id="points" name="points" class="neu-input" required>
              </div>
              <div class="form-group" style="grid-column: span 2;">
                <label for="reason">Reason</label>
                <input type="text" id="reason" name="reason" class="neu-input" required>
              </div>
            </div>
            <div style="text-align:right; margin-top:8px;">
              <button type="submit" class="btn btn-warning">Adjust Points</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Customer List -->
    <div class="table-container">
      <h2 style="margin-top: 0; color: var(--text-light);">Customer Loyalty Status</h2>
      <table>
        <tr>
          <th>Name</th>
          <th>Phone</th>
          <th>Email</th>
          <th>Loyalty Status</th>
          <th>Points Balance</th>
          <th>Total Utang</th>
          <th>Action</th>
        </tr>
        <?php while($row=$customers->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['phone']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td>
              <span class="loyalty-badge <?= $row['is_loyal'] ? 'loyal' : 'not-loyal' ?>">
                <?= $row['is_loyal'] ? 'Loyal Customer' : 'Regular Customer' ?>
              </span>
            </td>
            <td><span class="points-balance"><?= $row['points_balance'] ?></span> pts</td>
            <td>₱<?= number_format($row['total_utang'],2) ?></td>
            <td>
              <a href="customer_loyalty.php?view_points=<?= $row['id'] ?>" class="btn btn-view">
                View Points
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
  </aside>
</div>

<!-- Modal for Points History -->
<?php if(!empty($pointsDetails)): ?>
<div class="modal" id="pointsModal" style="display:flex;">
  <div class="modal-content">
    <?php
    // Get customer info
    $custInfo = $conn->query("SELECT name, points_balance FROM customers WHERE id=$cust_id");
    if (!$custInfo) {
        handleError("Query failed for customer info", $conn);
    }
    $custInfo = $custInfo->fetch_assoc();
    $custName = $custInfo['name'];
    $pointsBalance = $custInfo['points_balance'];
    
    // Calculate totals
    $totalEarned = 0;
    $totalUsed = 0;
    foreach($pointsDetails as $pt) {
        $totalEarned += $pt['points_earned'];
        $totalUsed += $pt['points_used'];
    }
    ?>

    <!-- Modal Header -->
    <div class="modal-header">
      <h2>Points History - <?= htmlspecialchars($custName) ?></h2>
      <div class="balance">Current Balance: <?= $pointsBalance ?> pts</div>
    </div>

    <!-- Modal Body -->
    <div class="modal-body">
      <!-- LEFT SIDE: Points History -->
      <div class="modal-section">
        <h3><i class="fas fa-history"></i> Points Transactions</h3>
        
        <!-- Redemption form -->
        <form method="post" class="points-form-container">
          <input type="hidden" name="redeem_points" value="1">
          <input type="hidden" name="customer_id" value="<?= $cust_id ?>">
          <input type="number" name="points_to_redeem" min="1" max="<?= $pointsBalance ?>" placeholder="Points to Redeem" required>
          <button type="submit" class="btn btn-success">Redeem for Commission</button>
        </form>

        <!-- Points transactions table -->
        <div class="modal-table">
          <table>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Points</th>
              <th>Running Balance</th>
            </tr>
            <?php 
            $runningBalance = 0;
            foreach($pointsDetails as $pt): 
                $runningBalance += $pt['points_earned'] - $pt['points_used'];
            ?>
              <tr>
                <td><?= $pt['transaction_date'] ?></td>
                <td>
                  <?php if($pt['points_earned'] > 0): ?>
                    <span class="transaction-earned">Earned</span>
                  <?php else: ?>
                    <span class="transaction-used">Used</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if($pt['points_earned'] > 0): ?>
                    <span class="transaction-earned">+<?= $pt['points_earned'] ?></span>
                  <?php else: ?>
                    <span class="transaction-used">-<?= $pt['points_used'] ?></span>
                  <?php endif; ?>
                </td>
                <td><?= $runningBalance ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>

        <!-- Points summary at bottom -->
        <div class="points-summary">
          <table>
            <tr>
              <td>Total Points Earned:</td>
              <td class="transaction-earned">+<?= $totalEarned ?></td>
            </tr>
            <tr>
              <td>Total Points Used:</td>
              <td class="transaction-used">-<?= $totalUsed ?></td>
            </tr>
            <tr>
              <td>Current Balance:</td>
              <td><?= $pointsBalance ?></td>
            </tr>
          </table>
        </div>
      </div>

      <!-- RIGHT SIDE: Redemption Info -->
      <div class="modal-section">
        <h3><i class="fas fa-gift"></i> Points Redemption</h3>
        <div class="points-history">
          <div style="padding: 20px; text-align: center; background: rgba(0,0,0,0.2); border-radius: 12px; margin-bottom: 20px;">
            <i class="fas fa-coins" style="font-size: 48px; color: var(--accent); margin-bottom: 15px;"></i>
            <h3 style="margin: 0 0 10px; color: var(--text-light);">Redeem Points for Commission</h3>
            <p style="margin: 0; color: var(--muted);">1 Point = ₱1 Commission</p>
            <p style="margin: 15px 0 0; font-size: 24px; font-weight: 600; color: var(--accent);">
              Available: <?= $pointsBalance ?> pts (₱<?= $pointsBalance ?>)
            </p>
          </div>
          
          <div style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 20px;">
            <h4 style="margin-top: 0; color: var(--text-light);">How Redemption Works</h4>
            <ul style="padding-left: 20px; color: var(--muted);">
              <li>Enter the number of points you want to redeem</li>
              <li>Points will be converted to commission at 1:1 ratio</li>
              <li>Commission will be processed as a payment</li>
              <li>Transaction will be recorded in the system</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Close button -->
    <button class="close" onclick="document.getElementById('pointsModal').style.display='none'">
      <i class="fas fa-times"></i>
    </button>
  </div>
</div>
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
  const customerCard = document.getElementById('customer-card');
  const loyaltyCard = document.getElementById('loyalty-card');
  const pointsCard = document.getElementById('points-card');
  
  if (customerCard) customerCard.classList.remove('expanded');
  if (loyaltyCard) loyaltyCard.classList.remove('expanded');
  if (pointsCard) pointsCard.classList.remove('expanded');
});
</script>
</body>
</html>