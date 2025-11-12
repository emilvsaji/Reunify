<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: home.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $db = getDB();
        $query = "SELECT * FROM users WHERE email = ? AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['user_role'];
                $_SESSION['email'] = $user['email'];
                
                // Log activity
                logActivity($user['user_id'], 'login', 'User logged in successfully');
                
                // Redirect all users to home page
                header('Location: home.php');
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #1e293b;
            --light-bg: #f8fafc;
            --white: #ffffff;
            --border: #e2e8f0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            z-index: 0;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            z-index: 0;
        }

        .auth-container {
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
        }

        .auth-box {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            backdrop-filter: blur(10px);
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-logo i {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            display: block;
        }

        .auth-logo h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .auth-logo p {
            color: var(--secondary);
            font-size: 0.95rem;
        }

        h2 {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
        }

        .alert i {
            font-size: 1.2rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #b91c1c;
            border-left: 4px solid var(--danger);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #047857;
            border-left: 4px solid var(--success);
        }

        .auth-form {
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .form-group input::placeholder {
            color: #94a3b8;
        }

        .btn {
            padding: 0.875rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-block {
            width: 100%;
        }

        .auth-footer {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .auth-footer p {
            margin-bottom: 0.75rem;
            color: var(--secondary);
            font-size: 0.95rem;
        }

        .auth-footer p:last-child {
            margin-bottom: 0;
        }

        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .auth-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            body {
                padding: 1rem;
            }

            .auth-box {
                padding: 2rem 1.5rem;
            }

            .auth-logo h1 {
                font-size: 1.5rem;
            }

            .auth-logo i {
                font-size: 3rem;
            }

            h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-logo">
                <i class="fas fa-search-location"></i>
                <h1><?php echo APP_NAME; ?></h1>
                <p>Lost & Found Management System</p>
            </div>
            
            <h2>Welcome Back!</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Enter your email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="home.php"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>
