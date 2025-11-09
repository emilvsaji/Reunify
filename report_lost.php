<?php
require_once 'config/config.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Get categories
$categories_query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name";
$categories = $db->query($categories_query);

// Get departments (for faculty)
$departments_query = "SELECT * FROM departments ORDER BY department_name";
$departments = $db->query($departments_query);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $location = sanitize($_POST['location'] ?? '');
    $date_lost = sanitize($_POST['date_lost'] ?? '');
    $time_lost = sanitize($_POST['time_lost'] ?? '');
    $contact_info = sanitize($_POST['contact_info'] ?? '');
    $is_equipment = isset($_POST['is_equipment']) ? 1 : 0;
    $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
    
    // Validation
    if (empty($title) || empty($description) || empty($category_id) || empty($location) || empty($date_lost)) {
        $error = 'Please fill in all required fields';
    } else {
        // Handle image upload
        $item_image = '';
        if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadFile($_FILES['item_image'], 'items');
            if ($upload_result['success']) {
                $item_image = $upload_result['filename'];
            } else {
                $error = $upload_result['message'];
            }
        }
        
        if (empty($error)) {
            // Insert item
            $insert_query = "INSERT INTO items (item_type, title, description, category_id, location, 
                           date_lost_found, time_lost_found, reported_by, contact_info, item_image, 
                           is_equipment, department_id, status) 
                           VALUES ('lost', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')";
            $stmt = $db->prepare($insert_query);
            $stmt->bind_param("ssisssiisii", $title, $description, $category_id, $location, 
                            $date_lost, $time_lost, $_SESSION['user_id'], $contact_info, 
                            $item_image, $is_equipment, $department_id);
            
            if ($stmt->execute()) {
                $item_id = $stmt->insert_id;
                
                // Log activity
                logActivity($_SESSION['user_id'], 'report_lost', "Reported lost item: $title");
                
                // Create notification
                createNotification($_SESSION['user_id'], 'item_approved', 'Lost Item Reported', 
                                 "Your lost item report '$title' has been submitted successfully", $item_id);
                
                setSuccessMessage('Lost item reported successfully!');
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Failed to report item. Please try again.';
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
    <title>Report Lost Item - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.html" class="navbar-brand">
                <i class="fas fa-search-location"></i>
                <span><?php echo APP_NAME; ?></span>
            </a>
            <ul class="navbar-menu">
                <li><a href="home.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="report_lost.php" class="active"><i class="fas fa-exclamation-circle"></i> Report Lost</a></li>
                <li><a href="report_found.php"><i class="fas fa-check-circle"></i> Report Found</a></li>
                <li><a href="view_lost_items.php"><i class="fas fa-search"></i> Browse Items</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Report Form -->
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-exclamation-circle"></i> Report Lost Item</h1>
            <p>Fill in the details about your lost item. Be as specific as possible to help others identify it.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">
                            <i class="fas fa-heading"></i> Item Title *
                        </label>
                        <input type="text" id="title" name="title" required 
                               placeholder="e.g., Black iPhone 13 Pro" 
                               value="<?php echo htmlspecialchars($title ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="category_id">
                            <i class="fas fa-tag"></i> Category *
                        </label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php 
                            $categories->data_seek(0);
                            while($cat = $categories->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $cat['category_id']; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">
                        <i class="fas fa-align-left"></i> Detailed Description *
                    </label>
                    <textarea id="description" name="description" required 
                              placeholder="Describe your lost item in detail (color, brand, unique features, etc.)"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location">
                            <i class="fas fa-map-marker-alt"></i> Last Seen Location *
                        </label>
                        <input type="text" id="location" name="location" required 
                               placeholder="e.g., Library 3rd Floor, Cafeteria" 
                               value="<?php echo htmlspecialchars($location ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="date_lost">
                            <i class="fas fa-calendar"></i> Date Lost *
                        </label>
                        <input type="date" id="date_lost" name="date_lost" required 
                               max="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo htmlspecialchars($date_lost ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="time_lost">
                            <i class="fas fa-clock"></i> Approximate Time Lost
                        </label>
                        <input type="time" id="time_lost" name="time_lost" 
                               value="<?php echo htmlspecialchars($time_lost ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="contact_info">
                            <i class="fas fa-phone"></i> Contact Information
                        </label>
                        <input type="text" id="contact_info" name="contact_info" 
                               placeholder="Phone number or email" 
                               value="<?php echo htmlspecialchars($contact_info ?? $user['phone']); ?>">
                    </div>
                </div>

                <?php if ($user['user_role'] === 'faculty'): ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="department_id">
                                <i class="fas fa-building"></i> Department
                            </label>
                            <select id="department_id" name="department_id">
                                <option value="">Select Department (if applicable)</option>
                                <?php 
                                $departments->data_seek(0);
                                while($dept = $departments->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $dept['department_id']; ?>">
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group" style="display: flex; align-items: center; padding-top: 2rem;">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="checkbox" id="is_equipment" name="is_equipment" 
                                       style="width: auto; margin-right: 0.5rem;">
                                <i class="fas fa-flask"></i> This is lab/department equipment
                            </label>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="item_image">
                        <i class="fas fa-image"></i> Upload Item Image (Optional)
                    </label>
                    <input type="file" id="item_image" name="item_image" 
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <small style="color: var(--gray-500); display: block; margin-top: 0.5rem;">
                        Supported formats: JPG, PNG, GIF, WEBP. Max size: 5MB
                    </small>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Tips for better results:</strong>
                        <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                            <li>Provide as much detail as possible</li>
                            <li>Include unique identifying features</li>
                            <li>Upload a clear image if you have one</li>
                            <li>Check the found items regularly</li>
                        </ul>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Lost Item Report
                    </button>
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer style="background-color: var(--dark-bg); color: var(--white); padding: 2rem; text-align: center; margin-top: 3rem;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Campus Lost & Found Management System</p>
    </footer>
</body>
</html>
