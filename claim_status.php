<?php
require_once 'config/config.php';
requireLogin();

$db = getDB();

// Get all claims by user
$query = "SELECT c.*, i.title as item_title, i.item_type, i.item_image, cat.category_name, u.full_name as reporter_name
          FROM claims c 
          JOIN items i ON c.item_id = i.item_id 
          JOIN categories cat ON i.category_id = cat.category_id
          JOIN users u ON i.reported_by = u.user_id
          WHERE c.claimed_by = ? 
          ORDER BY c.claimed_at DESC";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$claims = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Claims - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand"><i class="fas fa-search-location"></i><span><?php echo APP_NAME; ?></span></a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="claim_status.php" class="active"><i class="fas fa-list"></i> My Claims</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-list"></i> My Claims</h1>
            <p>Track the status of your item claims</p>
        </div>

        <?php if ($msg = getSuccessMessage()): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <?php if ($claims->num_rows > 0): ?>
            <?php while($claim = $claims->fetch_assoc()): 
                $status_info = [
                    'pending' => ['badge-warning', 'clock', 'Your claim is waiting for review'],
                    'under_review' => ['badge-info', 'search', 'Your claim is being reviewed'],
                    'approved' => ['badge-success', 'check-circle', 'Your claim has been approved!'],
                    'rejected' => ['badge-danger', 'times-circle', 'Your claim was rejected'],
                    'completed' => ['badge-success', 'check-double', 'Item claimed successfully!']
                ];
                [$badge, $icon, $desc] = $status_info[$claim['claim_status']];
            ?>
                <div class="card">
                    <div style="display: grid; grid-template-columns: 150px 1fr auto; gap: 1.5rem; align-items: center;">
                        <?php if ($claim['item_image']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($claim['item_image']); ?>" style="width: 150px; height: 150px; object-fit: cover; border-radius: var(--border-radius);">
                        <?php else: ?>
                            <div style="width: 150px; height: 150px; background: var(--gray-200); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-image" style="font-size: 3rem; color: var(--gray-400);"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <div style="margin-bottom: 0.5rem;">
                                <span class="badge <?php echo $claim['item_type'] === 'lost' ? 'badge-danger' : 'badge-success'; ?>">
                                    <?php echo strtoupper($claim['item_type']); ?>
                                </span>
                                <span class="badge <?php echo $badge; ?>">
                                    <i class="fas fa-<?php echo $icon; ?>"></i> <?php echo strtoupper($claim['claim_status']); ?>
                                </span>
                            </div>
                            
                            <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($claim['item_title']); ?></h3>
                            <p style="color: var(--gray-600); font-size: 0.875rem; margin-bottom: 0.5rem;"><?php echo $desc; ?></p>
                            <p style="font-size: 0.875rem;"><strong>Your claim:</strong> <?php echo htmlspecialchars(truncateText($claim['claim_description'], 100)); ?></p>
                            
                            <?php if ($claim['review_notes']): ?>
                                <div style="margin-top: 0.75rem; padding: 0.75rem; background: var(--gray-100); border-radius: var(--border-radius);">
                                    <strong><i class="fas fa-comment"></i> Admin Notes:</strong>
                                    <p style="margin: 0.5rem 0 0 0;"><?php echo htmlspecialchars($claim['review_notes']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div style="margin-top: 0.75rem; display: flex; gap: 1rem; font-size: 0.875rem; color: var(--gray-600);">
                                <span><i class="fas fa-user"></i> Reporter: <?php echo htmlspecialchars($claim['reporter_name']); ?></span>
                                <span><i class="fas fa-clock"></i> Claimed: <?php echo timeAgo($claim['claimed_at']); ?></span>
                                <?php if ($claim['reviewed_at']): ?>
                                    <span><i class="fas fa-calendar-check"></i> Reviewed: <?php echo timeAgo($claim['reviewed_at']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <a href="item_details.php?id=<?php echo $claim['item_id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> View Item
                            </a>
                            <?php if ($claim['proof_image']): ?>
                                <a href="uploads/<?php echo htmlspecialchars($claim['proof_image']); ?>" target="_blank" class="btn btn-outline btn-sm">
                                    <i class="fas fa-image"></i> View Proof
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card text-center" style="padding: 3rem;">
                <i class="fas fa-hand-paper" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                <h3>No Claims Yet</h3>
                <p>You haven't claimed any items yet. Browse lost or found items to claim yours.</p>
                <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
                    <a href="view_lost_items.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Browse Lost Items
                    </a>
                    <a href="view_found_items.php" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Browse Found Items
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer style="background-color: var(--dark-bg); color: var(--white); padding: 2rem; text-align: center; margin-top: 3rem;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?></p>
    </footer>
</body>
</html>
