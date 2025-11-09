<?php
require_once 'config/config.php';
requireLogin();

$db = getDB();

// Mark all as read if requested
if (isset($_GET['mark_read'])) {
    $db->query("UPDATE notifications SET is_read = 1 WHERE user_id = {$_SESSION['user_id']}");
    header('Location: notifications.php');
    exit();
}

// Get all notifications
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result();

$unread_count = getUnreadNotificationCount($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.html" class="navbar-brand"><i class="fas fa-search-location"></i><span><?php echo APP_NAME; ?></span></a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="notifications.php" class="active"><i class="fas fa-bell"></i> Notifications</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class="fas fa-bell"></i> Notifications</h1>
                <p>You have <?php echo $unread_count; ?> unread notification<?php echo $unread_count != 1 ? 's' : ''; ?></p>
            </div>
            <?php if ($unread_count > 0): ?>
                <a href="?mark_read=1" class="btn btn-primary">
                    <i class="fas fa-check-double"></i> Mark All as Read
                </a>
            <?php endif; ?>
        </div>

        <?php if ($notifications->num_rows > 0): ?>
            <?php while($notif = $notifications->fetch_assoc()): 
                $type_icons = [
                    'claim_update' => ['hand-paper', 'warning'],
                    'item_matched' => ['link', 'info'],
                    'item_approved' => ['check-circle', 'success'],
                    'item_rejected' => ['times-circle', 'danger'],
                    'new_claim' => ['bell', 'info'],
                    'system' => ['cog', 'secondary']
                ];
                [$icon, $color] = $type_icons[$notif['notification_type']] ?? ['bell', 'info'];
            ?>
                <div class="card" style="border-left: 4px solid var(--<?php echo $color; ?>-color); background-color: <?php echo $notif['is_read'] ? 'var(--white)' : '#F0F9FF'; ?>;">
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="flex-shrink: 0;">
                            <i class="fas fa-<?php echo $icon; ?>" style="font-size: 2rem; color: var(--<?php echo $color; ?>-color);"></i>
                        </div>
                        <div style="flex-grow: 1;">
                            <h3 style="margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($notif['title']); ?>
                                <?php if (!$notif['is_read']): ?>
                                    <span class="badge badge-info" style="margin-left: 0.5rem;">NEW</span>
                                <?php endif; ?>
                            </h3>
                            <p style="color: var(--gray-700); margin-bottom: 0.75rem;">
                                <?php echo htmlspecialchars($notif['message']); ?>
                            </p>
                            <div style="display: flex; gap: 1rem; align-items: center; font-size: 0.875rem; color: var(--gray-600);">
                                <span><i class="fas fa-clock"></i> <?php echo timeAgo($notif['created_at']); ?></span>
                                <?php if ($notif['related_item_id']): ?>
                                    <a href="item_details.php?id=<?php echo $notif['related_item_id']; ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i> View Item
                                    </a>
                                <?php endif; ?>
                                <?php if ($notif['related_claim_id']): ?>
                                    <a href="claim_status.php" class="btn btn-sm btn-outline">
                                        <i class="fas fa-list"></i> View Claim
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card text-center" style="padding: 3rem;">
                <i class="fas fa-bell-slash" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                <h3>No Notifications</h3>
                <p>You don't have any notifications yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <footer style="background-color: var(--dark-bg); color: var(--white); padding: 2rem; text-align: center; margin-top: 3rem;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?></p>
    </footer>
</body>
</html>
