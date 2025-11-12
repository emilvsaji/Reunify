<?php
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
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
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box register-box">
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
                <p><a href="index.html">← Back to Home</a></p>
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
                roleFields.style.display = 'flex';
                if (role === 'student') {
                    studentField.style.display = 'block';
                    facultyField.style.display = 'none';
                } else if (role === 'faculty') {
                    studentField.style.display = 'none';
                    facultyField.style.display = 'block';
                }
            } else {
                roleFields.style.display = 'none';
            }
        }
    </script>
</body>
</html>
