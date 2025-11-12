<?php
require_once '../config/config.php';
requireRole('admin');

$db = getDB();

// Get overall statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM items WHERE item_type = 'lost') as total_lost,
    (SELECT COUNT(*) FROM items WHERE item_type = 'found') as total_found,
    (SELECT COUNT(*) FROM items WHERE status = 'pending') as pending_items,
    (SELECT COUNT(*) FROM items WHERE status = 'matched') as matched_items,
    (SELECT COUNT(*) FROM items WHERE status = 'claimed') as claimed_items,
    (SELECT COUNT(*) FROM claims WHERE claim_status = 'pending') as pending_claims,
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as new_users_week";
$stats = $db->query($stats_query)->fetch_assoc();

// Get recent items
$recent_items_query = "SELECT i.*, c.category_name, u.full_name as reporter_name 
                      FROM items i 
                      JOIN categories c ON i.category_id = c.category_id 
                      JOIN users u ON i.reported_by = u.user_id 
                      ORDER BY i.created_at DESC 
                      LIMIT 10";
$recent_items = $db->query($recent_items_query);

// Get pending claims
$pending_claims_query = "SELECT c.*, i.title as item_title, u.full_name as claimer_name 
                        FROM claims c 
                        JOIN items i ON c.item_id = i.item_id 
                        JOIN users u ON c.claimed_by = u.user_id 
                        WHERE c.claim_status = 'pending' 
                        ORDER BY c.claimed_at DESC 
                        LIMIT 10";
$pending_claims = $db->query($pending_claims_query);

// Handle claim actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $claim_id = intval($_POST['claim_id'] ?? 0);
        $action = $_POST['action'];
        $notes = sanitize($_POST['notes'] ?? '');
        
        if ($action === 'approve') {
            $db->query("CALL approve_claim($claim_id, {$_SESSION['user_id']}, '$notes')");
            setSuccessMessage('Claim approved successfully');
        } elseif ($action === 'reject') {
            $db->query("UPDATE claims SET claim_status = 'rejected', reviewed_by = {$_SESSION['user_id']}, review_notes = '$notes', reviewed_at = NOW() WHERE claim_id = $claim_id");
            setSuccessMessage('Claim rejected');
        }
        header('Location: dashboard.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.html" class="navbar-brand"><i class="fas fa-search-location"></i><span><?php echo APP_NAME; ?> - Admin</span></a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_claims.php"><i class="fas fa-tasks"></i> Manage Claims</a></li>
                <li><a href="feedback_analytics.php"><i class="fas fa-comments"></i> Feedback Analytics</a></li>
                <li><a href="../index.html"><i class="fas fa-home"></i> Main Site</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-user-shield"></i> Admin Dashboard</h1>
            <p>Manage the Reunify Lost & Found System</p>
        </div>

        <?php if ($msg = getSuccessMessage()): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-heart-broken"></i>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_lost']); ?></h3>
                    <p>Lost Items</p>
                </div>
            </div>

            <div class="stat-card success">
                <i class="fas fa-hand-holding-heart"></i>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_found']); ?></h3>
                    <p>Found Items</p>
                </div>
            </div>

            <div class="stat-card warning">
                <i class="fas fa-clock"></i>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['pending_claims']); ?></h3>
                    <p>Pending Claims</p>
                </div>
            </div>

            <div class="stat-card danger">
                <i class="fas fa-link"></i>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['matched_items']); ?></h3>
                    <p>Matched Items</p>
                </div>
            </div>
        </div>

        <!-- More Stats -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="card text-center">
                <i class="fas fa-check-double" style="font-size: 2rem; color: var(--success-color); margin-bottom: 0.5rem;"></i>
                <h3><?php echo number_format($stats['claimed_items']); ?></h3>
                <p style="color: var(--gray-600);">Claimed Items</p>
            </div>
            <div class="card text-center">
                <i class="fas fa-users" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 0.5rem;"></i>
                <h3><?php echo number_format($stats['total_users']); ?></h3>
                <p style="color: var(--gray-600);">Total Users</p>
            </div>
            <div class="card text-center">
                <i class="fas fa-user-plus" style="font-size: 2rem; color: var(--info-color); margin-bottom: 0.5rem;"></i>
                <h3><?php echo number_format($stats['new_users_week']); ?></h3>
                <p style="color: var(--gray-600);">New This Week</p>
            </div>
            <div class="card text-center">
                <i class="fas fa-hourglass-half" style="font-size: 2rem; color: var(--warning-color); margin-bottom: 0.5rem;"></i>
                <h3><?php echo number_format($stats['pending_items']); ?></h3>
                <p style="color: var(--gray-600);">Pending Review</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="manage_claims.php" class="btn btn-warning">
                    <i class="fas fa-hand-paper"></i> Manage Claims (<?php echo $stats['pending_claims']; ?>)
                </a>
                <a href="manage_items.php" class="btn btn-primary">
                    <i class="fas fa-box"></i> Manage Items
                </a>
                <a href="manage_users.php" class="btn btn-secondary">
                    <i class="fas fa-users"></i> Manage Users
                </a>
            </div>
        </div>

        <!-- Pending Claims -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-hand-paper"></i> Pending Claims Requiring Action</h3>
            </div>
            
            <?php if ($pending_claims->num_rows > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Claimer</th>
                                <th>Item</th>
                                <th>Description</th>
                                <th>Claimed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($claim = $pending_claims->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($claim['claimer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($claim['item_title']); ?></td>
                                    <td><?php echo truncateText($claim['claim_description'], 100); ?></td>
                                    <td><?php echo timeAgo($claim['claimed_at']); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline-flex; gap: 0.5rem;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="claim_id" value="<?php echo $claim['claim_id']; ?>">
                                            <input type="hidden" name="notes" value="Approved by admin">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-secondary" 
                                                    onclick="return confirm('Approve this claim?');">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Reject this claim?');">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                        <a href="../item_details.php?id=<?php echo $claim['item_id']; ?>" class="btn btn-sm btn-outline" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="manage_claims.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View All Claims
                    </a>
                </div>
            <?php else: ?>
                <p style="text-align: center; padding: 2rem; color: var(--gray-500);">
                    <i class="fas fa-check-circle"></i> No pending claims
                </p>
            <?php endif; ?>
        </div>

        <!-- Recent Items -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-clock"></i> Recently Reported Items</h3>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Reporter</th>
                            <th>Status</th>
                            <th>Reported</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $recent_items->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="badge <?php echo $item['item_type'] === 'lost' ? 'badge-danger' : 'badge-success'; ?>">
                                        <?php echo strtoupper($item['item_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['reporter_name']); ?></td>
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
                                <td><?php echo timeAgo($item['created_at']); ?></td>
                                <td>
                                    <a href="../item_details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer style="background-color: var(--dark-bg); color: var(--white); padding: 2rem; text-align: center; margin-top: 3rem;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Admin Panel</p>
    </footer>
</body>
</html>
