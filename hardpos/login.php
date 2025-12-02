<?php
include "db.php";
session_start();

// Set theme from session or default to dark
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'dark';
}
 $currentTheme = $_SESSION['theme'];

 $error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);
        $_SESSION["user"] = [
            "id" => $user["id"],
            "username" => $user["username"],
            "role" => $user["role"]
        ];
        
        // Role-based redirection
        if ($user["role"] === "admin") {
            header("Location: dashboard.php");
        } else if ($user["role"] === "cashier") {
            header("Location: cashier_dashboard.php");
        } else {
            // Default fallback for any other roles
            header("Location: dashboard.php");
        }
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - POS System</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
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
      --bg: #f5f7fa;       /* main background - soft blueish white */
      --sidebar: #ffffff;  /* sidebar background */
      --card: #ffffff;     /* card background */
      --accent: #F25F3A;   /* orange accent - keeping the same for brand consistency */
      --muted: #64748b;    /* muted text - slate blue */
      --text-light: #334155; /* text color - dark slate */
      --text-dark: #ffffff;
      --border-light: rgba(0,0,0,0.08);
      --shadow-light: rgba(255,255,255,0.8);
      --shadow-dark: rgba(0,0,0,0.1);
      --neu-shadow: 6px 6px 12px rgba(0,0,0,0.1), -6px -6px 12px rgba(255,255,255,0.8);
      --neu-active-shadow: -4px -4px 6px rgba(255,255,255,0.8), 4px 4px 6px rgba(0,0,0,0.1);
      --sidebar-icon: #64748b;
      --sidebar-hover: #334155;
      --tooltip-bg: rgba(0, 0, 0, 0.8);
      --modal-bg: rgba(0,0,0,0.5);
    }
    
    *{
      margin:0;
      padding:0;
      box-sizing:border-box;
    }
    
    body{
      font-family: "Segoe UI", Roboto, sans-serif;
      background:var(--bg);
      color:var(--text-light);
      display:flex;
      align-items:center;
      justify-content:center;
      min-height:100vh;
      position:relative;
      overflow:hidden;
      transition: background-color 0.3s, color 0.3s;
    }
    
    body::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: 
        radial-gradient(circle at 20% 80%, rgba(242, 95, 58, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(41, 182, 246, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(155, 89, 182, 0.05) 0%, transparent 50%);
      z-index: 1;
    }
    
    .login-container {
      position: relative;
      z-index: 2;
      width: 100%;
      max-width: 400px;
      padding: 20px;
    }
    
    .login-card {
      background: var(--card);
      border-radius:20px;
      box-shadow: var(--neu-shadow);
      padding: 40px 30px;
      text-align: center;
      transition: all 0.3s ease;
    }
    
    .login-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--neu-active-shadow);
    }
    
    .login-logo {
      margin-bottom: 10px;
      display: flex;
      justify-content: center;
    }
    
    .login-logo img {
      width: 100px;
      height: auto;
      border-radius: 50%;
      object-fit: cover;
      box-shadow: var(--neu-shadow);
    }
    
    .login-title {
      font-size: 24px;
      font-weight: 600;
      color:var(--accent);
      margin-bottom: 30px;
    }
    
    .login-subtitle {
      color: var(--muted);
      font-size: 14px;
      margin-bottom: 30px;
    }
    
    .input-group {
      margin-bottom: 20px;
      text-align: left;
    }
    
    .input-group label {
      display: block;
      margin-bottom: 8px;
      font-size: 12px;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    
    .input-wrapper i {
      position: absolute;
      left: 15px;
      color: var(--muted);
      font-size: 16px;
      z-index: 2;
    }
    
    .neu-input {
      width: 100%;
      padding: 12px 15px 12px 45px;
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
    }
    
    .neu-input:focus {
      box-shadow: 
        inset 2px 2px 4px rgba(0,0,0,0.4),
        inset -2px -2px 4px rgba(255,255,255,0.05),
        0 0 0 1px var(--accent);
    }
    
    .error {
      background: rgba(242, 95, 58, 0.1);
      color: var(--accent);
      padding: 10px 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
      border-left: 3px solid var(--accent);
      text-align: left;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .error i {
      color: var(--accent);
    }
    
    .submit-btn {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 12px;
      background: var(--accent);
      color: #fff;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      box-shadow: var(--neu-shadow);
      transition: all 0.2s ease;
      margin-top: 10px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .submit-btn:hover {
      background: #e04a2a;
      transform: translateY(-1px);
      box-shadow: var(--neu-active-shadow);
    }
    
    .submit-btn:active {
      transform: translateY(0);
    }
    
    .login-footer {
      margin-top: 25px;
      font-size: 13px;
      color: var(--muted);
    }
    
    .login-footer a {
      color: var(--accent);
      text-decoration: none;
      transition: color 0.2s ease;
    }
    
    .login-footer a:hover {
      color: var(--text-light);
      text-decoration: underline;
    }
    
    .loading {
      display: none;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 1000;
    }
    
    .loading-spinner {
      width: 40px;
      height: 40px;
      border: 3px solid rgba(255,255,255,0.1);
      border-radius: 50%;
      border-top-color: var(--accent);
      animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    @media(max-width:480px){
      .login-container {
        padding: 10px;
        background: var(--card);
      }
      
      .login-card {
        padding: 30px 20px;
      }
      
      .login-logo img {
        width: 80px;
      }
      
      .login-title {
        font-size: 20px;
      }
    }
  </style>
</head>
<body class="<?php echo $currentTheme === 'light' ? 'light-mode' : ''; ?>">

  <div class="loading" id="loading">
    <div class="loading-spinner"></div>
  </div>

  <div class="login-container">
    <form class="login-card" method="POST" id="loginForm">
      <div class="login-logo">
        <img src="uploads/logo.png" alt="POS System Logo">
      </div>
      <h1 class="login-title">HARVIN HARDWARE</h1>
      <p class="login-subtitle">Sign in to your account</p>
      
      <?php if ($error): ?>
        <div class="error">
          <i class="fas fa-exclamation-circle"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      
      <div class="input-group">
        <label for="username">Username</label>
        <div class="input-wrapper">
          <i class="fas fa-user"></i>
          <input type="text" id="username" name="username" class="neu-input" placeholder="Enter your username" required>
        </div>
      </div>
      
      <div class="input-group">
        <label for="password">Password</label>
        <div class="input-wrapper">
          <i class="fas fa-lock"></i>
          <input type="password" id="password" name="password" class="neu-input" placeholder="Enter your password" required>
        </div>
      </div>
      
      <button type="submit" class="submit-btn">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
      
      <div class="login-footer">
        <a href="#">Forgot password?</a> | <a href="#">Contact Support</a>
      </div>
    </form>
  </div>

  <script>
    document.getElementById('loginForm').addEventListener('submit', function() {
      document.getElementById('loading').style.display = 'block';
    });
    
    document.getElementById('username').focus();
    
    document.getElementById('username').addEventListener('input', function() {
      const errorDiv = document.querySelector('.error');
      if (errorDiv) {
        errorDiv.style.display = 'none';
      }
    });
    
    document.getElementById('password').addEventListener('input', function() {
      const errorDiv = document.querySelector('.error');
      if (errorDiv) {
        errorDiv.style.display = 'none';
      }
    });
  </script>
</body>
</html>