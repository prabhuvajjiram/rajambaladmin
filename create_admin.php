<?php
require_once "db_config.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO admins (username, password) VALUES (?, ?)";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "ss", $param_username, $param_password);
        
        $param_username = $username;
        $param_password = $hashed_password;
        
        if(mysqli_stmt_execute($stmt)){
            echo "Admin user created successfully.";
        } else{
            echo "Oops! Something went wrong. Please try again later.";
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
    <title>Create Admin User</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .create-admin-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .create-admin-container h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .create-admin-container form {
            display: flex;
            flex-direction: column;
        }
        .create-admin-container input {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .create-admin-container button {
            align-self: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="create-admin-container">
        <h2>Create Admin User</h2>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn btn-primary">Create Admin</button>
        </form>
    </div>
</body>
</html>