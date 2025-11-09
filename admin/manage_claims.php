<?php
require_once '../config/config.php';
requireRole('admin');
$db = getDB();

// Get filter
$status_filter = sanitize($_GET['status'] ?? 'pending');

// Handle claim action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $claim_id = intval($_POST['claim_id'] ?? 0);
    $action = $_POST['action'];
    $notes = sanitize($_POST['review_notes'] ?? '');
    
    if ($action === 'approve') {
        $db->query("CALL approve_claim($claim_id, {$_SESSION['user_id']}, '$notes')");
        setSuccessMessage('Claim approved successfully!');
    } elseif ($action === 'reject') {
        $db->query("UPDATE claims SET claim_status = 'rejected', reviewed_by = {$_SESSION['user_id']}, review_notes = '$notes', reviewed_at = NOW() WHERE claim_id = $claim_id");
        setSuccessMessage('Claim rejected');
    } elseif ($action === 'review') {
        $db->query("UPDATE claims SET claim_status = 'under_review' WHERE claim_id = $claim_id");
        setSuccessMessage('Claim marked under review');
    }
    header('Location: manage_claims.php?status=' . $status_filter);
    exit();
}

// Get claims
$claims_query = "SELECT c.*, i.title as item_title, i.item_type, i.item_image, u1.full_name as claimer_name, u1.email as claimer_email, u2.full_name as reporter_name
                FROM claims c 
                JOIN items i ON c.item_id = i.item_id 
                JOIN users u1 ON c.claimed_by = u1.user_id 
                JOIN users u2 ON i.reported_by = u2.user_id";

if ($status_filter !== 'all') {
    $claims_query .= " WHERE c.claim_status = '$status_filter'";
}
$claims_query .= " ORDER BY c.claimed_at DESC";
$claims = $db->query($claims_query);

$count_query = "SELECT claim_status, COUNT(*) as count FROM claims GROUP BY claim_status";
$counts = $db->query($count_query);
$status_counts = [];
while ($row = $counts->fetch_assoc()) {
    $status_counts[$row['claim_status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Claims - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="navbar-brand"><i class="fas fa-search-location"></i><span><?php echo APP_NAME; ?> - Admin</span></a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_claims.php" class="active"><i class="fas fa-hand-paper"></i> Claims</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-hand-paper"></i> Manage Claims</h1>
            <p>Review and process item claims</p>
        </div>

        <?php if ($msg = getSuccessMessage()): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div class="card">
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="?status=all" class="btn <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                    All Claims (<?php echo array_sum($status_counts); ?>)
                </a>
                <a href="?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-warning' : 'btn-outline'; ?> btn-sm">
                    Pending (<?php echo $status_counts['pending'] ?? 0; ?>)
                </a>
                <a href="?status=under_review" class="btn <?php echo $status_filter === 'under_review' ? 'btn-info' : 'btn-outline'; ?> btn-sm">
                    Under Review (<?php echo $status_counts['under_review'] ?? 0; ?>)
                </a>
                <a href="?status=approved" class="btn <?php echo $status_filter === 'approved' ? 'btn-secondary' : 'btn-outline'; ?> btn-sm">
                    Approved (<?php echo $status_counts['approved'] ?? 0; ?>)
                </a>
                <a href="?status=rejected" class="btn <?php echo $status_filter === 'rejected' ? 'btn-danger' : 'btn-outline'; ?> btn-sm">
                    Rejected (<?php echo $status_counts['rejected'] ?? 0; ?>)
                </a>
            </div>
        </div>

        <!-- Claims List -->
        <?php if ($claims->num_rows > 0): ?>
            <?php while($claim = $claims->fetch_assoc()): ?>
                <div class="card">
                    <div style="display: grid; grid-template-columns: 120px 1fr; gap: 1.5rem;">
                        <?php if ($claim['item_image']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($claim['item_image']); ?>" style="width: 120px; height: 120px; object-fit: cover; border-radius: var(--border-radius);">
                        <?php else: ?>
                            <div style="width: 120px; height: 120px; background: var(--gray-200); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-image" style="font-size: 2rem; color: var(--gray-400);"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                <div>
                                    <span class="badge <?php echo $claim['item_type'] === 'lost' ? 'badge-danger' : 'badge-success'; ?>">
                                        <?php echo strtoupper($claim['item_type']); ?>
                                    </span>
                                    <?php
                                    $status_badges = ['pending' => 'badge-warning', 'under_review' => 'badge-info', 'approved' => 'badge-success', 'rejected' => 'badge-danger'];
                                    ?>
                                    <span class="badge <?php echo $status_badges[$claim['claim_status']]; ?>">
                                        <?php echo strtoupper($claim['claim_status']); ?>
                                    </span>
                                </div>
                                <div style="font-size: 0.875rem; color: var(--gray-600);">
                                    Claimed <?php echo timeAgo($claim['claimed_at']); ?>
                                </div>
                            </div>
                            
                            <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($claim['item_title']); ?></h3>
                            
                            <div style="margin-bottom: 1rem;">
                                <strong>Claimed by:</strong> <?php echo htmlspecialchars($claim['claimer_name']); ?> (<?php echo htmlspecialchars($claim['claimer_email']); ?>)<br>
                                <strong>Item reported by:</strong> <?php echo htmlspecialchars($claim['reporter_name']); ?>
                            </div>
                            
                            <div style="background: var(--gray-100); padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1rem;">
                                <strong>Claim Description:</strong>
                                <p style="margin-top: 0.5rem;"><?php echo htmlspecialchars($claim['claim_description']); ?></p>
                            </div>
                            
                            <?php if ($claim['proof_image']): ?>
                                <div style="margin-bottom: 1rem;">
                                    <a href="../uploads/<?php echo htmlspecialchars($claim['proof_image']); ?>" target="_blank" class="btn btn-sm btn-outline">
                                        <i class="fas fa-image"></i> View Proof Image
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($claim['review_notes']): ?>
                                <div style="background: #FEF3C7; padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1rem;">
                                    <strong>Review Notes:</strong>
                                    <p style="margin-top: 0.5rem;"><?php echo htmlspecialchars($claim['review_notes']); ?></p>
                                    <small>Reviewed <?php echo timeAgo($claim['reviewed_at']); ?></small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($claim['claim_status'] === 'pending' || $claim['claim_status'] === 'under_review'): ?>
                                <form method="POST" style="display: flex; gap: 1rem; align-items: flex-end;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="claim_id" value="<?php echo $claim['claim_id']; ?>">
                                    
                                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                        <label>Review Notes</label>
                                        <input type="text" name="review_notes" placeholder="Add notes for the claimer...">
                                    </div>
                                    
                                    <?php if ($claim['claim_status'] === 'pending'): ?>
                                        <button type="submit" name="action" value="review" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i> Mark Under Review
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button type="submit" name="action" value="approve" class="btn btn-secondary btn-sm" 
                                            onclick="return confirm('Approve this claim? The item will be marked as claimed.');">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    
                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Reject this claim?');">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                    
                                    <a href="../item_details.php?id=<?php echo $claim['item_id']; ?>" target="_blank" class="btn btn-outline btn-sm">
                                        <i class="fas fa-external-link-alt"></i> View Item
                                    </a>
                                </form>
                            <?php else: ?>
                                <a href="../item_details.php?id=<?php echo $claim['item_id']; ?>" target="_blank" class="btn btn-outline btn-sm">
                                    <i class="fas fa-external-link-alt"></i> View Item Details
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card text-center" style="padding: 3rem;">
                <i class="fas fa-inbox" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                <h3>No claims found</h3>
                <p>No claims with status: <?php echo htmlspecialchars($status_filter); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <footer style="background-color: var(--dark-bg); color: var(--white); padding: 2rem; text-align: center; margin-top: 3rem;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Admin Panel</p>
    </footer>
</body>
</html>
