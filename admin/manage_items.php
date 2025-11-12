<?php
require_once '../config/config.php';
requireRole('admin');

$db = getDB();
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * 20;
$filter_type = sanitize($_GET['type'] ?? '');
$filter_status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');

// Build query
$where = [];
$params = [];
$types = '';

if ($filter_type) {
    $where[] = "i.item_type = ?";
    $params[] = $filter_type;
    $types .= 's';
}

if ($filter_status) {
    $where[] = "i.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($search) {
    $where[] = "(i.title LIKE ? OR i.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$where_clause = $where ? "WHERE " . implode(' AND ', $where) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM items i $where_clause";
$count_stmt = $db->prepare($count_query);
if ($params) {
    $refs = [];
    foreach ($params as &$param) {
        $refs[] = &$param;
    }
    call_user_func_array([$count_stmt, 'bind_param'], array_merge([$types], $refs));
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / 20);

// Get items
$query = "SELECT i.*, c.category_name, u.full_name as reporter_name 
          FROM items i 
          JOIN categories c ON i.category_id = c.category_id 
          JOIN users u ON i.reported_by = u.user_id 
          $where_clause 
          ORDER BY i.created_at DESC 
          LIMIT ? OFFSET ?";
$stmt = $db->prepare($query);
$limit = 20;
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$refs = [];
foreach ($params as &$param) {
    $refs[] = &$param;
}
call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $refs));

$stmt->execute();
$items = $stmt->get_result();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $item_id = intval($_POST['item_id'] ?? 0);
        $action = $_POST['action'];
        
        if ($action === 'approve') {
            $db->query("UPDATE items SET status = 'approved' WHERE item_id = $item_id");
            setSuccessMessage('Item approved');
        } elseif ($action === 'reject') {
            $db->query("UPDATE items SET status = 'rejected' WHERE item_id = $item_id");
            setSuccessMessage('Item rejected');
        } elseif ($action === 'delete') {
            $db->query("DELETE FROM items WHERE item_id = $item_id");
            setSuccessMessage('Item deleted');
        }
        
        header('Location: manage_items.php' . ($filter_type ? "?type=$filter_type" : '') . ($filter_status ? "&status=$filter_status" : ''));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Items - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.html" class="navbar-brand"><i class="fas fa-search-location"></i><span><?php echo APP_NAME; ?> - Admin</span></a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_claims.php"><i class="fas fa-tasks"></i> Manage Claims</a></li>
                <li><a href="manage_items.php" class="active"><i class="fas fa-box"></i> Manage Items</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="feedback_analytics.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-box"></i> Manage Items</h1>
            <p>Review, approve, and manage all reported items</p>
        </div>

        <?php if ($msg = getSuccessMessage()): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card">
            <form method="GET" action="">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search" placeholder="Search by title or description..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                    </div>
                    
                    <div>
                        <label><i class="fas fa-list"></i> Type</label>
                        <select name="type" class="form-control">
                            <option value="">All Types</option>
                            <option value="lost" <?php echo $filter_type === 'lost' ? 'selected' : ''; ?>>Lost</option>
                            <option value="found" <?php echo $filter_type === 'found' ? 'selected' : ''; ?>>Found</option>
                        </select>
                    </div>
                    
                    <div>
                        <label><i class="fas fa-check"></i> Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="matched" <?php echo $filter_status === 'matched' ? 'selected' : ''; ?>>Matched</option>
                            <option value="claimed" <?php echo $filter_status === 'claimed' ? 'selected' : ''; ?>>Claimed</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; align-items: flex-end; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="manage_items.php" class="btn btn-outline" style="flex: 1;">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Items Table -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Items (<?php echo number_format($total); ?> total)</h3>
            </div>

            <?php if ($items->num_rows > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Reporter</th>
                                <th>Status</th>
                                <th>Location</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $items->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge <?php echo $item['item_type'] === 'lost' ? 'badge-danger' : 'badge-success'; ?>">
                                            <?php echo strtoupper($item['item_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                        <br><small style="color: var(--gray-600);"><?php echo truncateText($item['description'], 50); ?></small>
                                    </td>
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
                                        <span class="badge <?php echo $status_class[$item['status']] ?? 'badge-secondary'; ?>">
                                            <?php echo strtoupper($item['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['location']); ?></td>
                                    <td><?php echo formatDate($item['created_at']); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                            <?php if ($item['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="../item_details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-sm btn-info" target="_blank" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this item?');">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1.5rem; flex-wrap: wrap;">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $filter_type ? "&type=$filter_type" : ''; ?><?php echo $filter_status ? "&status=$filter_status" : ''; ?><?php echo $search ? "&search=" . urlencode($search) : ''; ?>" 
                               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem;">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                    <p>No items found matching your criteria</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer style="background-color: var(--dark-bg); color: var(--white); padding: 2rem; text-align: center; margin-top: 3rem;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Admin Panel</p>
    </footer>
</body>
</html>
