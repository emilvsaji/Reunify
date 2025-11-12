<?php
require_once 'config/config.php';

$db = getDB();

// Check admin user
$query = "SELECT user_id, username, email, password_hash, user_role, is_active FROM users WHERE username = 'admin' OR email = 'admin@reunify.com'";
$result = $db->query($query);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Admin Debug</title>
    <link rel='stylesheet' href='assets/css/style.css'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body>
    <div class='dashboard-container' style='margin-top: 3rem;'>
        <div class='card'>
            <h2><i class='fas fa-user-shield'></i> Admin User Debug</h2>
            <hr>";

if ($result && $result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "<h3>Found Admin User:</h3>";
    echo "<ul style='list-style: none; padding: 0;'>";
    echo "<li><strong>User ID:</strong> " . htmlspecialchars($admin['user_id']) . "</li>";
    echo "<li><strong>Username:</strong> " . htmlspecialchars($admin['username']) . "</li>";
    echo "<li><strong>Email:</strong> " . htmlspecialchars($admin['email']) . "</li>";
    echo "<li><strong>Role:</strong> " . htmlspecialchars($admin['user_role']) . "</li>";
    echo "<li><strong>Active:</strong> " . ($admin['is_active'] ? 'Yes' : 'No') . "</li>";
    echo "<li><strong>Password Hash:</strong> " . htmlspecialchars(substr($admin['password_hash'], 0, 20)) . "...</li>";
    echo "</ul>";
    
    echo "<h3 style='margin-top: 1.5rem;'>Password Verification Test:</h3>";
    $test_password = 'admin123';
    $is_valid = password_verify($test_password, $admin['password_hash']);
    echo "<p>Testing password 'admin123': <strong style='color: " . ($is_valid ? 'green' : 'red') . ";'>" . ($is_valid ? 'VALID ✓' : 'INVALID ✗') . "</strong></p>";
    
    if (!$is_valid) {
        echo "<div class='alert alert-warning' style='margin-top: 1rem;'>";
        echo "<h4><i class='fas fa-warning'></i> Password Mismatch</h4>";
        echo "<p>The password hash in the database doesn't match 'admin123'. Let me reset it...</p>";
        echo "</div>";
        
        // Reset password
        $new_password = 'admin123';
        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $update_query = "UPDATE users SET password_hash = ? WHERE email = 'admin@reunify.com'";
        $stmt = $db->prepare($update_query);
        $stmt->bind_param("s", $new_hash);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>";
            echo "<i class='fas fa-check-circle'></i> <strong>Password Reset Successfully!</strong>";
            echo "<p>Admin credentials:</p>";
            echo "<ul>";
            echo "<li><strong>Email:</strong> admin@reunify.com</li>";
            echo "<li><strong>Password:</strong> admin123</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div class='alert alert-error'>";
            echo "<i class='fas fa-exclamation-circle'></i> Error updating password: " . $db->error;
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check-circle'></i> Password is correct!";
        echo "</div>";
    }
} else {
    echo "<div class='alert alert-error'>";
    echo "<i class='fas fa-exclamation-circle'></i> Admin user not found in database!";
    echo "</div>";
    
    echo "<h3>Creating Admin User...</h3>";
    $username = 'admin';
    $email = 'admin@reunify.com';
    $password = 'admin123';
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $full_name = 'System Administrator';
    $phone = '1234567890';
    $user_role = 'admin';
    
    $insert_query = "INSERT INTO users (username, email, password_hash, full_name, phone, user_role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)";
    $stmt = $db->prepare($insert_query);
    $stmt->bind_param("ssssss", $username, $email, $password_hash, $full_name, $phone, $user_role);
    
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check-circle'></i> <strong>Admin User Created Successfully!</strong>";
        echo "<p>Credentials:</p>";
        echo "<ul>";
        echo "<li><strong>Email:</strong> admin@reunify.com</li>";
        echo "<li><strong>Password:</strong> admin123</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-error'>";
        echo "<i class='fas fa-exclamation-circle'></i> Error creating admin: " . $db->error;
        echo "</div>";
    }
}

echo "
        </div>
        <div class='card' style='margin-top: 1rem;'>
            <h3><i class='fas fa-arrow-right'></i> Next Steps</h3>
            <p>After the admin credentials are fixed:</p>
            <ol>
                <li>Go to <a href='login.php' target='_blank'>Login Page</a></li>
                <li>Enter Email: <strong>admin@reunify.com</strong></li>
                <li>Enter Password: <strong>admin123</strong></li>
                <li>Click Login</li>
            </ol>
            <p>You can delete this file (debug_admin.php) after verifying the login works.</p>
        </div>
    </div>
</body>
</html>";
?>
