<?php
require_once 'config/config.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

// Get user statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM items WHERE reported_by = ? AND item_type = 'lost') as my_lost_items,
    (SELECT COUNT(*) FROM items WHERE reported_by = ? AND item_type = 'found') as my_found_items,
    (SELECT COUNT(*) FROM claims WHERE claimed_by = ?) as my_claims,
    (SELECT COUNT(*) FROM claims c JOIN items i ON c.item_id = i.item_id 
     WHERE i.reported_by = ? AND c.claim_status = 'pending') as pending_claims_on_items";
$stmt = $db->prepare($stats_query);
$stmt->bind_param("iiii", $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get recent items by user
$my_items_query = "SELECT i.*, c.category_name 
                   FROM items i 
                   JOIN categories c ON i.category_id = c.category_id 
                   WHERE i.reported_by = ? 
                   ORDER BY i.created_at DESC 
                   LIMIT 5";
$stmt = $db->prepare($my_items_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$my_items = $stmt->get_result();

// Get recent notifications
$notif_query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($notif_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="report_lost.php"><i class="fas fa-exclamation-circle"></i> Report Lost</a></li>
                <li><a href="report_found.php"><i class="fas fa-check-circle"></i> Report Found</a></li>
                <li><a href="view_lost_items.php"><i class="fas fa-search"></i> Browse Items</a></li>
                <li><a href="notifications.php" class="notification-badge">
                    <i class="fas fa-bell"></i>
                    <?php 
                    $unread = getUnreadNotificationCount($_SESSION['user_id']);
                    if ($unread > 0): 
                    ?>
                        <span class="badge"><?php echo $unread; ?></span>
                    <?php endif; ?>
                </a></li>
                <?php if (hasRole('admin')): ?>
                    <li><a href="admin/dashboard.php"><i class="fas fa-user-shield"></i> Admin</a></li>
                <?php endif; ?>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt"></i> Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
            <p>Here's an overview of your activity on <?php echo APP_NAME; ?></p>
        </div>

        <?php if ($msg = getSuccessMessage()): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <?php if ($msg = getErrorMessage()): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-heart-broken"></i>
                <div class="stat-info">
                    <h3><?php echo $stats['my_lost_items']; ?></h3>
                    <p>Lost Items Reported</p>
                </div>
            </div>

            <div class="stat-card success">
                <i class="fas fa-hand-holding-heart"></i>
                <div class="stat-info">
                    <h3><?php echo $stats['my_found_items']; ?></h3>
                    <p>Found Items Reported</p>
                </div>
            </div>

            <div class="stat-card warning">
                <i class="fas fa-hand-paper"></i>
                <div class="stat-info">
                    <h3><?php echo $stats['my_claims']; ?></h3>
                    <p>Claims Submitted</p>
                </div>
            </div>

            <div class="stat-card danger">
                <i class="fas fa-clock"></i>
                <div class="stat-info">
                    <h3><?php echo $stats['pending_claims_on_items']; ?></h3>
                    <p>Pending Claims on My Items</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            </div>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="report_lost.php" class="btn btn-primary">
                    <i class="fas fa-exclamation-circle"></i> Report Lost Item
                </a>
                <a href="report_found.php" class="btn btn-secondary">
                    <i class="fas fa-check-circle"></i> Report Found Item
                </a>
                <a href="view_lost_items.php" class="btn btn-outline">
                    <i class="fas fa-search"></i> Browse Lost Items
                </a>
                <a href="view_found_items.php" class="btn btn-outline">
                    <i class="fas fa-search"></i> Browse Found Items
                </a>
                <a href="claim_status.php" class="btn btn-warning">
                    <i class="fas fa-list"></i> My Claims
                </a>
            </div>
        </div>

        <!-- My Recent Items -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-th-list"></i> My Recent Reports</h3>
            </div>
            
            <?php if ($my_items->num_rows > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $my_items->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge <?php echo $item['item_type'] === 'lost' ? 'badge-danger' : 'badge-success'; ?>">
                                            <?php echo strtoupper($item['item_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['location']); ?></td>
                                    <td><?php echo formatDate($item['date_lost_found']); ?></td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'pending' => 'badge-warning',
                                            'approved' => 'badge-success',
                                            'matched' => 'badge-info',
                                            'claimed' => 'badge-success',
                                            'rejected' => 'badge-danger'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $status_class[$item['status']]; ?>">
                                            <?php echo strtoupper($item['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="item_details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; padding: 2rem; color: var(--gray-500);">
                    <i class="fas fa-inbox"></i> You haven't reported any items yet.
                </p>
            <?php endif; ?>
        </div>

        <!-- Recent Notifications -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-bell"></i> Recent Notifications</h3>
            </div>
            
            <?php if ($notifications->num_rows > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php while ($notif = $notifications->fetch_assoc()): ?>
                        <div style="padding: 1rem; background-color: <?php echo $notif['is_read'] ? 'var(--gray-100)' : '#DBEAFE'; ?>; border-radius: var(--border-radius); border-left: 4px solid var(--primary-color);">
                            <h4 style="margin-bottom: 0.5rem; font-size: 1rem;">
                                <i class="fas fa-bell"></i> <?php echo htmlspecialchars($notif['title']); ?>
                            </h4>
                            <p style="margin-bottom: 0.5rem; color: var(--gray-700);">
                                <?php echo htmlspecialchars($notif['message']); ?>
                            </p>
                            <small style="color: var(--gray-500);">
                                <i class="fas fa-clock"></i> <?php echo timeAgo($notif['created_at']); ?>
                            </small>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="notifications.php" class="btn btn-outline">
                        <i class="fas fa-eye"></i> View All Notifications
                    </a>
                </div>
            <?php else: ?>
                <p style="text-align: center; padding: 2rem; color: var(--gray-500);">
                    <i class="fas fa-inbox"></i> No notifications yet.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer style="background-color: var(--dark-bg); color: var(--white); padding: 2rem; text-align: center; margin-top: 3rem;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Campus Lost & Found Management System</p>
    </footer>
</body>
</html>
