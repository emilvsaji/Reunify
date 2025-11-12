<?php
require_once 'config/config.php';
$db = getDB();
$item_id = intval($_GET['id'] ?? 0);

if (!$item_id) {
    header('Location: index.html');
    exit();
}

// Get item details
$query = "SELECT i.*, c.category_name, c.category_icon, u.full_name as reporter_name, u.phone as reporter_phone, u.email as reporter_email, d.department_name
          FROM items i 
          JOIN categories c ON i.category_id = c.category_id 
          JOIN users u ON i.reported_by = u.user_id 
          LEFT JOIN departments d ON i.department_id = d.department_id 
          WHERE i.item_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    header('Location: index.html');
    exit();
}

// Update views count
$db->query("UPDATE items SET views_count = views_count + 1 WHERE item_id = $item_id");

// Get existing claims for this item
$claims_query = "SELECT c.*, u.full_name as user_full_name, u.phone as user_phone, u.email as user_email FROM claims c JOIN users u ON c.claimed_by = u.user_id WHERE c.item_id = ? ORDER BY c.claimed_at DESC";
$stmt = $db->prepare($claims_query);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$claims = $stmt->get_result();

$is_owner = isLoggedIn() && $_SESSION['user_id'] == $item['reported_by'];
$has_claimed = false;
if (isLoggedIn()) {
    $check = $db->query("SELECT 1 FROM claims WHERE item_id = $item_id AND claimed_by = {$_SESSION['user_id']}");
    $has_claimed = $check->num_rows > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['title']); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.html" class="navbar-brand"><i class="fas fa-search-location"></i><span><?php echo APP_NAME; ?></span></a>
            <ul class="navbar-menu">
                <li><a href="home.php"><i class="fas fa-home"></i> Home</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <a href="<?php echo $item['item_type'] === 'lost' ? 'view_lost_items.php' : 'view_found_items.php'; ?>" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 400px; gap: 2rem;">
            <!-- Main Content -->
            <div>
                <div class="card">
                    <span class="item-badge <?php echo $item['item_type']; ?>" style="font-size: 1rem; padding: 0.5rem 1rem;">
                        <i class="fas fa-<?php echo $item['item_type'] === 'lost' ? 'heart-broken' : 'check-circle'; ?>"></i>
                        <?php echo strtoupper($item['item_type']); ?> ITEM
                    </span>
                    
                    <?php
                    $status_badges = [
                        'pending' => ['badge-warning', 'clock'],
                        'approved' => ['badge-success', 'check'],
                        'matched' => ['badge-info', 'link'],
                        'claimed' => ['badge-success', 'handshake'],
                        'rejected' => ['badge-danger', 'times']
                    ];
                    [$badge_class, $icon] = $status_badges[$item['status']];
                    ?>
                    <span class="badge <?php echo $badge_class; ?>" style="font-size: 1rem; padding: 0.5rem 1rem; margin-left: 0.5rem;">
                        <i class="fas fa-<?php echo $icon; ?>"></i> <?php echo strtoupper($item['status']); ?>
                    </span>
                    
                    <h1 style="margin-top: 1.5rem;"><?php echo htmlspecialchars($item['title']); ?></h1>
                    
                    <?php if ($item['item_image']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($item['item_image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>" 
                             style="width: 100%; max-height: 500px; object-fit: contain; border-radius: var(--border-radius); margin: 1.5rem 0;">
                    <?php endif; ?>
                    
                    <h3><i class="fas fa-align-left"></i> Description</h3>
                    <p style="color: var(--gray-700); line-height: 1.8; white-space: pre-wrap;"><?php echo htmlspecialchars($item['description']); ?></p>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 2rem; padding: 1.5rem; background: var(--gray-100); border-radius: var(--border-radius);">
                        <div>
                            <strong><i class="fas fa-tag"></i> Category:</strong><br>
                            <?php echo htmlspecialchars($item['category_name']); ?>
                        </div>
                        <div>
                            <strong><i class="fas fa-map-marker-alt"></i> Location:</strong><br>
                            <?php echo htmlspecialchars($item['location']); ?>
                        </div>
                        <div>
                            <strong><i class="fas fa-calendar"></i> Date:</strong><br>
                            <?php echo formatDate($item['date_lost_found'], 'd M Y'); ?>
                        </div>
                        <div>
                            <strong><i class="fas fa-clock"></i> Time:</strong><br>
                            <?php echo $item['time_lost_found'] ?: 'Not specified'; ?>
                        </div>
                        <?php if ($item['department_name']): ?>
                            <div>
                                <strong><i class="fas fa-building"></i> Department:</strong><br>
                                <?php echo htmlspecialchars($item['department_name']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($item['is_equipment']): ?>
                            <div>
                                <strong><i class="fas fa-flask"></i> Type:</strong><br>
                                Lab/Department Equipment
                            </div>
                        <?php endif; ?>
                        <div>
                            <strong><i class="fas fa-eye"></i> Views:</strong><br>
                            <?php echo number_format($item['views_count']); ?>
                        </div>
                        <div>
                            <strong><i class="fas fa-clock"></i> Reported:</strong><br>
                            <?php echo timeAgo($item['created_at']); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Claims Section -->
                <?php if ($is_owner && $claims->num_rows > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-hand-paper"></i> Claims on This Item (<?php echo $claims->num_rows; ?>)</h3>
                        </div>
                        <?php while ($claim = $claims->fetch_assoc()): ?>
                            <div style="padding: 1.5rem; background: var(--gray-100); border-radius: var(--border-radius); margin-bottom: 1.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                    <div>
                                        <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($claim['claimer_name'] ?? $claim['user_full_name']); ?></h4>
                                        <?php
                                        $claim_badges = [
                                            'pending' => 'badge-warning',
                                            'under_review' => 'badge-info',
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-danger',
                                            'completed' => 'badge-success'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $claim_badges[$claim['claim_status']]; ?>">
                                            <?php echo strtoupper($claim['claim_status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Claimer Details -->
                                <div style="background: white; padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1rem;">
                                    <h5 style="margin: 0 0 0.75rem 0; color: var(--primary-color);"><i class="fas fa-user-circle"></i> Claimer Details</h5>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 0.75rem;">
                                        <div>
                                            <strong><i class="fas fa-user"></i> Full Name:</strong><br>
                                            <span style="color: var(--gray-700);"><?php echo htmlspecialchars($claim['claimer_name'] ?? '-'); ?></span>
                                        </div>
                                        <div>
                                            <strong><i class="fas fa-phone"></i> Phone:</strong><br>
                                            <span style="color: var(--gray-700);"><?php echo htmlspecialchars($claim['claimer_phone'] ?? '-'); ?></span>
                                        </div>
                                        <div>
                                            <strong><i class="fas fa-envelope"></i> Email:</strong><br>
                                            <span style="color: var(--gray-700);"><?php echo htmlspecialchars($claim['claimer_email'] ?? '-'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Claim Description -->
                                <div>
                                    <h5 style="margin: 0 0 0.5rem 0; color: var(--primary-color);"><i class="fas fa-comment"></i> Why is this item yours?</h5>
                                    <p style="margin: 0.5rem 0; color: var(--gray-700); line-height: 1.6; white-space: pre-wrap;"><?php echo htmlspecialchars($claim['claim_description']); ?></p>
                                </div>
                                
                                <!-- Proof Image if available -->
                                <?php if ($claim['proof_image']): ?>
                                    <div style="margin-top: 1rem;">
                                        <h5 style="margin: 0 0 0.5rem 0; color: var(--primary-color);"><i class="fas fa-image"></i> Proof Image</h5>
                                        <img src="uploads/<?php echo htmlspecialchars($claim['proof_image']); ?>" 
                                             alt="Proof" 
                                             style="max-width: 300px; max-height: 300px; border-radius: var(--border-radius); border: 1px solid var(--gray-300);">
                                    </div>
                                <?php endif; ?>
                                
                                <small style="color: var(--gray-500); display: block; margin-top: 1rem;">Claimed <?php echo timeAgo($claim['claimed_at']); ?></small>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div>
                <!-- Reporter Info -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user"></i> Reported By</h3>
                    </div>
                    <p><strong><?php echo htmlspecialchars($item['reporter_name']); ?></strong></p>
                    <?php if ($item['contact_info']): ?>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($item['contact_info']); ?></p>
                    <?php endif; ?>
                    <?php if (isLoggedIn() && !$is_owner): ?>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($item['reporter_email']); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Actions -->
                <?php if (isLoggedIn()): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-bolt"></i> Actions</h3>
                        </div>
                        <?php if (!$is_owner && $item['status'] !== 'claimed' && !$has_claimed): ?>
                            <a href="claim_item.php?id=<?php echo $item_id; ?>" class="btn btn-warning btn-block">
                                <i class="fas fa-hand-paper"></i> Claim This Item
                            </a>
                        <?php elseif ($has_claimed): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-check"></i> You have already claimed this item
                            </div>
                            <a href="claim_status.php" class="btn btn-outline btn-block">
                                <i class="fas fa-list"></i> View My Claims
                            </a>
                        <?php elseif ($item['status'] === 'claimed'): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-double"></i> This item has been claimed
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Please login to claim this item
                        </div>
                        <a href="login.php" class="btn btn-primary btn-block"><i class="fas fa-sign-in-alt"></i> Login</a>
                        <a href="register.php" class="btn btn-outline btn-block"><i class="fas fa-user-plus"></i> Register</a>
                    </div>
                <?php endif; ?>
                
                <!-- Share -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-share-alt"></i> Share</h3>
                    </div>
                    <p style="font-size: 0.875rem; color: var(--gray-600);">Help spread the word!</p>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="#" class="btn btn-sm" style="background: #1877f2; color: white;" onclick="alert('Share functionality can be implemented'); return false;">
                            <i class="fab fa-facebook"></i> Facebook
                        </a>
                        <a href="#" class="btn btn-sm" style="background: #1da1f2; color: white;" onclick="alert('Share functionality can be implemented'); return false;">
                            <i class="fab fa-twitter"></i> Twitter
                        </a>
                        <a href="#" class="btn btn-sm" style="background: #25d366; color: white;" onclick="alert('Share functionality can be implemented'); return false;">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer style="background-color: var(--dark-bg); color: var(--white); padding: 2rem; text-align: center; margin-top: 3rem;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?></p>
    </footer>
</body>
</html>
