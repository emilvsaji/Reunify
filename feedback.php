<?php
require_once 'config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Only students can submit feedback
if ($_SESSION['user_role'] !== 'student') {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';
$db = getDB();

// Initialize form variables
$category = '';
$rating = '';
$subject = '';
$feedback_text = '';
$is_anonymous = 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = sanitize($_POST['category'] ?? '');
    $rating = sanitize($_POST['rating'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $feedback_text = sanitize($_POST['feedback_text'] ?? '');
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    
    // Validation
    if (empty($category) || empty($rating) || empty($subject) || empty($feedback_text)) {
        $error = 'Please fill in all required fields';
    } elseif (!in_array($category, ['application', 'features', 'performance', 'user_experience', 'documentation', 'other'])) {
        $error = 'Invalid category selected';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Rating must be between 1 and 5';
    } elseif (strlen($subject) < 5) {
        $error = 'Subject must be at least 5 characters long';
    } elseif (strlen($feedback_text) < 10) {
        $error = 'Feedback must be at least 10 characters long';
    } else {
        // Insert feedback
        $query = "INSERT INTO feedback (student_id, category, rating, subject, feedback_text, is_anonymous, status) 
                  VALUES (?, ?, ?, ?, ?, ?, 'new')";
        $stmt = $db->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("isisii", $_SESSION['user_id'], $category, $rating, $subject, $feedback_text, $is_anonymous);
            
            if ($stmt->execute()) {
                $success = 'Feedback submitted successfully! Thank you for your input.';
                // Clear form
                $category = '';
                $rating = '';
                $subject = '';
                $feedback_text = '';
                $is_anonymous = 0;
            } else {
                $error = 'Error submitting feedback. Please try again.';
            }
            $stmt->close();
        } else {
            $error = 'Database error. Please try again.';
        }
    }
}

// Get user's previous feedback count
$query = "SELECT COUNT(*) as total FROM feedback WHERE student_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user_feedback = $result->fetch_assoc();
$stmt->close();

// Helper function to check if logged in
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback - <?php echo APP_NAME; ?></title>
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
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1e293b;
            --light-bg: #f8fafc;
            --white: #ffffff;
            --border: #e2e8f0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--light-bg);
            color: var(--dark);
            line-height: 1.6;
        }

        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            z-index: 1000;
            padding: 1rem 0;
        }

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
        }

        .navbar-menu {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .navbar-menu a {
            text-decoration: none;
            color: var(--dark);
            transition: color 0.3s;
        }

        .navbar-menu a:hover {
            color: var(--primary);
        }

        .btn-logout {
            background: var(--danger);
            color: var(--white);
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-logout:hover {
            background: #dc2626;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 6rem 2rem 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .page-header p {
            color: var(--secondary);
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 0.75rem;
            border-left: 4px solid var(--primary);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .stat-card-label {
            color: var(--secondary);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .stat-card-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dark);
        }

        .form-card {
            background: var(--white);
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .form-label .required {
            color: var(--danger);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .rating-group {
            display: flex;
            gap: 0.5rem;
        }

        .rating-btn {
            width: 2.5rem;
            height: 2.5rem;
            border: 2px solid var(--border);
            background: var(--white);
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s;
            font-weight: bold;
        }

        .rating-btn:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }

        .rating-btn.active {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .form-check input[type="checkbox"] {
            width: 1.2rem;
            height: 1.2rem;
            cursor: pointer;
        }

        .form-check label {
            cursor: pointer;
            margin: 0;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: var(--secondary);
            color: var(--white);
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #b91c1c;
            border-left: 4px solid var(--danger);
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: #1d4ed8;
            border-left: 4px solid var(--info);
        }

        .help-text {
            font-size: 0.9rem;
            color: var(--secondary);
            margin-top: 0.25rem;
        }

        .breadcrumb {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            color: var(--secondary);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .navbar-container {
                flex-direction: column;
                gap: 1rem;
            }

            .navbar-menu {
                flex-direction: column;
                width: 100%;
                gap: 1rem;
            }

            .container {
                padding: 7rem 1rem 2rem;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-comments"></i> Feedback
            </div>
            <div class="navbar-menu">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="view_lost_items.php"><i class="fas fa-search"></i> Lost Items</a>
                <a href="view_found_items.php"><i class="fas fa-check"></i> Found Items</a>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="dashboard.php">Dashboard</a>
            <span>/</span>
            <span>Submit Feedback</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-comment-dots"></i> Share Your Feedback</h1>
            <p>Help us improve by sharing your thoughts and suggestions about our Lost & Found system.</p>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-label">Your Total Feedback</div>
                <div class="stat-card-value"><?php echo $user_feedback['total']; ?></div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Feedback Form -->
        <div class="form-card">
            <form method="POST" action="" novalidate>
                <!-- Category -->
                <div class="form-group">
                    <label class="form-label">Feedback Category <span class="required">*</span></label>
                    <select name="category" class="form-control" required>
                        <option value="">Select a category...</option>
                        <option value="application" <?php echo ($category === 'application' ? 'selected' : ''); ?>>Application Issues</option>
                        <option value="features" <?php echo ($category === 'features' ? 'selected' : ''); ?>>Feature Suggestions</option>
                        <option value="performance" <?php echo ($category === 'performance' ? 'selected' : ''); ?>>Performance</option>
                        <option value="user_experience" <?php echo ($category === 'user_experience' ? 'selected' : ''); ?>>User Experience</option>
                        <option value="documentation" <?php echo ($category === 'documentation' ? 'selected' : ''); ?>>Documentation</option>
                        <option value="other" <?php echo ($category === 'other' ? 'selected' : ''); ?>>Other</option>
                    </select>
                    <div class="help-text">Select the category that best describes your feedback</div>
                </div>

                <!-- Rating -->
                <div class="form-group">
                    <label class="form-label">Overall Rating <span class="required">*</span></label>
                    <div class="rating-group">
                        <input type="hidden" name="rating" id="rating" value="<?php echo htmlspecialchars($rating); ?>" required>
                        <button type="button" class="rating-btn" data-rating="1" title="Poor">1</button>
                        <button type="button" class="rating-btn" data-rating="2" title="Fair">2</button>
                        <button type="button" class="rating-btn" data-rating="3" title="Good">3</button>
                        <button type="button" class="rating-btn" data-rating="4" title="Very Good">4</button>
                        <button type="button" class="rating-btn" data-rating="5" title="Excellent">5</button>
                    </div>
                    <div class="help-text">Rate your overall experience from 1 (Poor) to 5 (Excellent)</div>
                </div>

                <!-- Subject -->
                <div class="form-group">
                    <label class="form-label">Subject <span class="required">*</span></label>
                    <input type="text" name="subject" class="form-control" placeholder="Brief subject of your feedback" 
                           value="<?php echo htmlspecialchars($subject); ?>" required minlength="5" maxlength="200">
                    <div class="help-text">Provide a brief subject (5-200 characters)</div>
                </div>

                <!-- Feedback Text -->
                <div class="form-group">
                    <label class="form-label">Feedback <span class="required">*</span></label>
                    <textarea name="feedback_text" class="form-control" placeholder="Please share your detailed feedback..." 
                              required minlength="10"><?php echo htmlspecialchars($feedback_text); ?></textarea>
                    <div class="help-text">Provide detailed feedback (minimum 10 characters)</div>
                </div>

                <!-- Anonymous Checkbox -->
                <div class="form-check">
                    <input type="checkbox" id="is_anonymous" name="is_anonymous" value="1" 
                           <?php echo ($is_anonymous ? 'checked' : ''); ?>>
                    <label for="is_anonymous">Submit this feedback anonymously</label>
                </div>

                <!-- Alert Info -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Your feedback helps us improve. All feedback will be reviewed by the admin and faculty.</span>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Feedback
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Rating button functionality
        document.querySelectorAll('.rating-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const rating = this.dataset.rating;
                document.getElementById('rating').value = rating;
                
                // Update button states
                document.querySelectorAll('.rating-btn').forEach(b => {
                    if (parseInt(b.dataset.rating) <= parseInt(rating)) {
                        b.classList.add('active');
                    } else {
                        b.classList.remove('active');
                    }
                });
            });
        });

        // Highlight active rating on page load
        const savedRating = document.getElementById('rating').value;
        if (savedRating) {
            document.querySelectorAll('.rating-btn').forEach(btn => {
                if (parseInt(btn.dataset.rating) <= parseInt(savedRating)) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const rating = document.getElementById('rating').value;
            if (!rating) {
                e.preventDefault();
                alert('Please select a rating');
                return false;
            }
        });
    </script>
</body>
</html>
