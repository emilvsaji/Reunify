<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: home.php');
    exit();
}

$error = '';
$success = '';

// Get departments for dropdown
$db = getDB();
$dept_query = "SELECT * FROM departments ORDER BY department_name";
$departments = $db->query($dept_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitize($_POST['full_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $user_role = sanitize($_POST['user_role'] ?? '');
    $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
    $student_id = sanitize($_POST['student_id'] ?? '');
    $employee_id = sanitize($_POST['employee_id'] ?? '');
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($user_role)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if username or email already exists
        $check_query = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($check_query);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username or email already exists';
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $insert_query = "INSERT INTO users (username, email, password_hash, full_name, phone, user_role, department_id, student_id, employee_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($insert_query);
            $stmt->bind_param("ssssssiss", $username, $email, $password_hash, $full_name, $phone, $user_role, $department_id, $student_id, $employee_id);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now login.';
                // Clear form
                $username = $email = $full_name = $phone = $student_id = $employee_id = '';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo APP_NAME; ?></title>
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
            padding: 2rem 2rem 3rem;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
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
            position: fixed;
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
            max-width: 900px;
            position: relative;
            z-index: 1;
            margin: auto;
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

        .alert-success a {
            color: #047857;
            font-weight: 600;
            text-decoration: underline;
            margin-left: 0.5rem;
        }

        .auth-form {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .form-group input::placeholder {
            color: #94a3b8;
        }

        .form-group select {
            cursor: pointer;
        }

        .role-specific-fields {
            display: none;
        }

        .student-field,
        .faculty-field {
            display: none;
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
            margin-top: 0.5rem;
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

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .auth-box {
                padding: 2rem 1.5rem;
            }

            body {
                padding: 1.5rem 1rem 2rem;
            }
        }

        @media (max-width: 640px) {
            body {
                padding: 1rem 1rem 2rem;
            }

            .auth-box {
                padding: 1.5rem 1rem;
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
            
            <h2>Create New Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <a href="login.php">Click here to login</a>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">
                            <i class="fas fa-user"></i> Full Name *
                        </label>
                        <input type="text" id="full_name" name="full_name" required 
                               placeholder="Enter your full name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user-circle"></i> Username *
                        </label>
                        <input type="text" id="username" name="username" required 
                               placeholder="Choose a username" value="<?php echo htmlspecialchars($username ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address *
                        </label>
                        <input type="email" id="email" name="email" required 
                               placeholder="Enter your email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">
                            <i class="fas fa-phone"></i> Phone Number
                        </label>
                        <input type="tel" id="phone" name="phone" 
                               placeholder="Enter your phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Password *
                        </label>
                        <input type="password" id="password" name="password" required 
                               placeholder="Minimum 6 characters">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i> Confirm Password *
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="Re-enter password">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="user_role">
                            <i class="fas fa-user-tag"></i> I am a *
                        </label>
                        <select id="user_role" name="user_role" required onchange="toggleRoleFields()">
                            <option value="">Select Role</option>
                            <option value="student">Student</option>
                            <option value="faculty">Faculty/Staff</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="department_id">
                            <i class="fas fa-building"></i> Department
                        </label>
                        <select id="department_id" name="department_id">
                            <option value="">Select Department</option>
                            <?php while($dept = $departments->fetch_assoc()): ?>
                                <option value="<?php echo $dept['department_id']; ?>">
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row role-specific-fields" style="display: none;">
                    <div class="form-group student-field">
                        <label for="student_id">
                            <i class="fas fa-id-card"></i> Student ID
                        </label>
                        <input type="text" id="student_id" name="student_id" 
                               placeholder="Enter your student ID" value="<?php echo htmlspecialchars($student_id ?? ''); ?>">
                    </div>
                    
                    <div class="form-group faculty-field">
                        <label for="employee_id">
                            <i class="fas fa-id-badge"></i> Employee ID
                        </label>
                        <input type="text" id="employee_id" name="employee_id" 
                               placeholder="Enter your employee ID" value="<?php echo htmlspecialchars($employee_id ?? ''); ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p><a href="home.php"><i class="fas fa-arrow-left"></i> Back to Home</a></p>
            </div>
        </div>
    </div>
    
    <script>
        function toggleRoleFields() {
            const role = document.getElementById('user_role').value;
            const roleFields = document.querySelector('.role-specific-fields');
            const studentField = document.querySelector('.student-field');
            const facultyField = document.querySelector('.faculty-field');
            
            if (role) {
                roleFields.style.display = 'grid';
                if (role === 'student') {
                    studentField.style.display = 'flex';
                    facultyField.style.display = 'none';
                } else if (role === 'faculty') {
                    studentField.style.display = 'none';
                    facultyField.style.display = 'flex';
                }
            } else {
                roleFields.style.display = 'none';
            }
        }
    </script>
</body>
</html>
