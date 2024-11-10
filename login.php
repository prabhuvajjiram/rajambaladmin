<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'login_errors.log');

require_once "db_config.php";

// Function to log debug information
function logDebug($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, 'login_debug.log');
}

// Check if already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: admin.php");
    exit;
}

$login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    logDebug('Processing login attempt');
    
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT id, username, password FROM admins WHERE username = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "s", $param_username);
        $param_username = $username;
        
        logDebug('Executing login query');
        
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) == 1){
                mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                if(mysqli_stmt_fetch($stmt)){
                    if(password_verify($password, $hashed_password)){
                        logDebug('Login successful for user: ' . $username);
                        logDebug('Login successful for user: ' . $hashed_password);
                        
                        // Store session data
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["username"] = $username;
                        
                        header("location: admin.php");
                        exit;
                    } else {
                        logDebug('Password verification failed for user: ' . $username);
                        logDebug('Password verification failed for user: ' . $hashed_password);
                        $login_err = "Invalid username or password.";
                    }
                }
            } else {
                logDebug('No user found with username: ' . $username);
                $login_err = "Invalid username or password.";
            }
        } else {
            logDebug('Query execution failed: ' . mysqli_error($conn));
            $login_err = "Oops! Something went wrong. Please try again later.";
        }
        
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rajambal Cottons - Admin Login</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .login-container h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .login-container form {
            display: flex;
            flex-direction: column;
        }
        .login-container input {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .login-container button {
            align-self: center;
            margin-top: 10px;
        }
        #loginMessage {
            margin-top: 15px;
            font-weight: bold;
            text-align: center;
            color: red;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="images/rc.svg" alt="Rajambal Cottons Logo" class="logo-icon">
                <span class="logo-text">Rajambal Cottons - Admin Login</span>
            </div>
        </div>
    </header>

    <main>
        <section class="login-container">
            <h2>Admin Login</h2>
            <form id="loginForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="text" id="username" name="username" placeholder="Username" required>
                <input type="password" id="password" name="password" placeholder="Password" required>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            <div id="loginMessage">
                <?php 
                if(!empty($login_err)){
                    echo $login_err;
                }        
                ?>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024 Rajambal Cottons. All rights reserved. | Designed by Prabu Vajjiram</p>
            </div>
        </div>
    </footer>
</body>
</html>