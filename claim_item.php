<?php
require_once 'config/config.php';
requireLogin();

$item_id = intval($_GET['id'] ?? 0);
$db = getDB();

if (!$item_id) {
    header('Location: index.php');
    exit();
}

// Get item details
$query = "SELECT i.*, c.category_name, u.full_name as reporter_name FROM items i JOIN categories c ON i.category_id = c.category_id JOIN users u ON i.reported_by = u.user_id WHERE i.item_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item || $item['status'] === 'claimed' || $item['reported_by'] == $_SESSION['user_id']) {
    setErrorMessage('Cannot claim this item');
    header('Location: item_details.php?id=' . $item_id);
    exit();
}

// Check if already claimed
$check = $db->query("SELECT 1 FROM claims WHERE item_id = $item_id AND claimed_by = {$_SESSION['user_id']}");
if ($check->num_rows > 0) {
    setErrorMessage('You have already claimed this item');
    header('Location: item_details.php?id=' . $item_id);
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $claim_description = sanitize($_POST['claim_description'] ?? '');
    
    if (empty($claim_description)) {
        $error = 'Please provide details about why this item is yours';
    } else {
        // Handle proof image upload
        $proof_image = '';
        if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadFile($_FILES['proof_image'], 'claims');
            if ($upload_result['success']) {
                $proof_image = $upload_result['filename'];
            }
        }
        
        // Insert claim
        $insert_query = "INSERT INTO claims (item_id, claimed_by, claim_description, proof_image, claim_status) VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $db->prepare($insert_query);
        $stmt->bind_param("iiss", $item_id, $_SESSION['user_id'], $claim_description, $proof_image);
        
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'claim_item', "Claimed item: {$item['title']}");
            setSuccessMessage('Claim submitted successfully! The item owner will review your claim.');
            header('Location: claim_status.php');
            exit();
        } else {
            $error = 'Failed to submit claim. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Item - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.html" class="navbar-brand"><i class="fas fa-search-location"></i><span><?php echo APP_NAME; ?></span></a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-hand-paper"></i> Claim Item</h1>
            <p>Provide details to prove ownership of this item</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Item Preview -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-eye"></i> Item You're Claiming</h3>
            </div>
            <div style="display: flex; gap: 1.5rem; align-items: center;">
                <?php if ($item['item_image']): ?>
                    <img src="uploads/<?php echo htmlspecialchars($item['item_image']); ?>" style="width: 150px; height: 150px; object-fit: cover; border-radius: var(--border-radius);">
                <?php endif; ?>
                <div>
                    <h2><?php echo htmlspecialchars($item['title']); ?></h2>
                    <p style="color: var(--gray-600);"><?php echo truncateText($item['description'], 150); ?></p>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($item['category_name']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                </div>
            </div>
        </div>

        <!-- Claim Form -->
        <div class="card">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="claim_description">
                        <i class="fas fa-comment"></i> Why is this item yours? *
                    </label>
                    <textarea id="claim_description" name="claim_description" required rows="6"
                              placeholder="Provide specific details: unique features, where/when you lost it, what's inside it, serial numbers, etc."></textarea>
                    <small style="color: var(--gray-500);">Be specific! This helps verify your ownership.</small>
                </div>

                <div class="form-group">
                    <label for="proof_image">
                        <i class="fas fa-camera"></i> Upload Proof (Optional but recommended)
                    </label>
                    <input type="file" id="proof_image" name="proof_image" accept="image/*">
                    <small style="color: var(--gray-500);">
                        Upload receipt, purchase proof, photo of similar item you own, or any other proof of ownership
                    </small>
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Important:</strong>
                        <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                            <li>Provide accurate and truthful information</li>
                            <li>False claims will result in account suspension</li>
                            <li>The item reporter will review your claim</li>
                            <li>You'll be notified once the claim is processed</li>
                        </ul>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-paper-plane"></i> Submit Claim
                    </button>
                    <a href="item_details.php?id=<?php echo $item_id; ?>" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    <footer style="background-color: var(--dark-bg); color: var(--white); padding: 2rem; text-align: center; margin-top: 3rem;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?></p>
    </footer>
</body>
</html>
