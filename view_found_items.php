<?php
// Copy of view_lost_items.php but for found items
require_once 'config/config.php';
$db = getDB();
$search = sanitize($_GET['search'] ?? '');
$category = intval($_GET['category'] ?? 0);
$date_from = sanitize($_GET['date_from'] ?? '');
$date_to = sanitize($_GET['date_to'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * ITEMS_PER_PAGE;

$where = ["item_type = 'found'", "status IN ('approved', 'matched')"];
$params = [];
$types = '';

if ($search) {
    $where[] = "(title LIKE ? OR description LIKE ? OR location LIKE ?)";
    $search_param = "%$search%";
    $params[] = &$search_param;
    $params[] = &$search_param;
    $params[] = &$search_param;
    $types .= 'sss';
}
if ($category) { $where[] = "category_id = ?"; $params[] = &$category; $types .= 'i'; }
if ($date_from) { $where[] = "date_lost_found >= ?"; $params[] = &$date_from; $types .= 's'; }
if ($date_to) { $where[] = "date_lost_found <= ?"; $params[] = &$date_to; $types .= 's'; }

$where_clause = implode(' AND ', $where);
$count_query = "SELECT COUNT(*) as total FROM items WHERE $where_clause";
$count_stmt = $db->prepare($count_query);
if ($params) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_items = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_items / ITEMS_PER_PAGE);

$query = "SELECT i.*, c.category_name, u.full_name as reporter_name FROM items i JOIN categories c ON i.category_id = c.category_id JOIN users u ON i.reported_by = u.user_id WHERE $where_clause ORDER BY i.created_at DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($query);
$limit = ITEMS_PER_PAGE;
$new_params = $params;
$new_params[] = &$limit;
$new_params[] = &$offset;
$new_types = $types . 'ii';
if ($new_params) $stmt->bind_param($new_types, ...$new_params);
$stmt->execute();
$items = $stmt->get_result();
$categories = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Found Items - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.html" class="navbar-brand"><i class="fas fa-search-location"></i><span><?php echo APP_NAME; ?></span></a>
            <ul class="navbar-menu">
                <li><a href="home.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="view_lost_items.php"><i class="fas fa-heart-broken"></i> Lost Items</a></li>
                <li><a href="view_found_items.php" class="active"><i class="fas fa-hand-holding-heart"></i> Found Items</a></li>
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
            <h1><i class="fas fa-hand-holding-heart"></i> Found Items</h1>
            <p>Browse through found items. Is one of them yours? Claim it!</p>
        </div>
        <div class="search-filter-section">
            <form method="GET" action="">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search by title, description, or location..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-grid">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Category</label>
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php while($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Date From</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Date To</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                        <a href="view_found_items.php" class="btn btn-outline"><i class="fas fa-redo"></i> Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Results (<?php echo number_format($total_items); ?> items found)</h3>
            </div>
        </div>
        <?php if ($items->num_rows > 0): ?>
            <div class="items-grid">
                <?php while($item = $items->fetch_assoc()): ?>
                    <div class="item-card">
                        <?php if ($item['item_image']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($item['item_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="item-image">
                        <?php else: ?>
                            <div class="item-image" style="display: flex; align-items: center; justify-content: center; background: var(--gray-200);">
                                <i class="fas fa-image" style="font-size: 3rem; color: var(--gray-400);"></i>
                            </div>
                        <?php endif; ?>
                        <div class="item-content">
                            <span class="item-badge found"><i class="fas fa-check-circle"></i> FOUND</span>
                            <?php if ($item['status'] === 'matched'): ?>
                                <span class="badge badge-info"><i class="fas fa-link"></i> MATCHED</span>
                            <?php endif; ?>
                            <h3 class="item-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p style="color: var(--gray-600); font-size: 0.875rem; margin-bottom: 1rem;">
                                <?php echo truncateText($item['description'], 100); ?>
                            </p>
                            <div class="item-meta">
                                <div><i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name']); ?></div>
                                <div><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location']); ?></div>
                                <div><i class="fas fa-calendar"></i> <?php echo formatDate($item['date_lost_found']); ?></div>
                                <div><i class="fas fa-clock"></i> <?php echo timeAgo($item['created_at']); ?></div>
                            </div>
                            <div class="item-actions">
                                <a href="item_details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-secondary btn-sm btn-block">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <?php if ($total_pages > 1): ?>
                <div class="card" style="margin-top: 2rem;">
                    <div style="display: flex; justify-content: center; gap: 0.5rem;">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
                               class="btn <?php echo $i === $page ? 'btn-secondary' : 'btn-outline'; ?> btn-sm"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card text-center" style="padding: 3rem;">
                <i class="fas fa-search" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                <h3>No found items match your criteria</h3>
                <p>Try adjusting your search filters</p>
            </div>
        <?php endif; ?>
    </div>
    <footer style="background-color: var(--dark-bg); color: var(--white); padding: 2rem; text-align: center; margin-top: 3rem;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Campus Lost & Found Management System</p>
    </footer>
</body>
</html>
