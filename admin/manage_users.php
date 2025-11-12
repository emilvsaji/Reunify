<?php
require_once '../config/config.php';
requireRole('admin');

$db = getDB();
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * 20;
$filter_role = sanitize($_GET['role'] ?? '');
$search = sanitize($_GET['search'] ?? '');

// Build query
$where = [];
$params = [];
$types = '';

if ($filter_role) {
    $where[] = "u.user_role = ?";
    $params[] = $filter_role;
    $types .= 's';
}

if ($search) {
    $where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.user_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$where_clause = $where ? "WHERE " . implode(' AND ', $where) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users u $where_clause";
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

// Get users with stats
$query = "SELECT u.*,
          (SELECT COUNT(*) FROM items WHERE reported_by = u.user_id) as items_reported,
          (SELECT COUNT(*) FROM claims WHERE claimed_by = u.user_id) as claims_made
          FROM users u 
          $where_clause 
          ORDER BY u.created_at DESC 
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
$users = $stmt->get_result();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $user_id = intval($_POST['user_id'] ?? 0);
        $action = $_POST['action'];
        
        if ($action === 'change_role') {
            $new_role = sanitize($_POST['new_role'] ?? '');
            if (in_array($new_role, ['student', 'faculty', 'admin'])) {
                $db->query("UPDATE users SET user_role = '$new_role' WHERE user_id = $user_id");
                setSuccessMessage('User role updated');
            }
        } elseif ($action === 'delete') {
            // Check if user can be deleted
            $item_count = $db->query("SELECT COUNT(*) as count FROM items WHERE reported_by = $user_id")->fetch_assoc()['count'];
            $claim_count = $db->query("SELECT COUNT(*) as count FROM claims WHERE claimed_by = $user_id")->fetch_assoc()['count'];
            
            if ($item_count == 0 && $claim_count == 0) {
                $db->query("DELETE FROM users WHERE user_id = $user_id");
                setSuccessMessage('User deleted');
            } else {
                setErrorMessage('Cannot delete user with existing items or claims');
            }
        }
        
        header('Location: manage_users.php' . ($filter_role ? "?role=$filter_role" : ''));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - <?php echo APP_NAME; ?></title>
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
                <li><a href="manage_items.php"><i class="fas fa-box"></i> Manage Items</a></li>
                <li><a href="manage_users.php" class="active"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="feedback_analytics.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-users"></i> Manage Users</h1>
            <p>View, manage, and control user accounts</p>
        </div>

        <?php if ($msg = getSuccessMessage()): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <?php if ($msg = getErrorMessage()): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card">
            <form method="GET" action="">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label><i class="fas fa-search"></i> Search</label>
                        <input type="text" name="search" placeholder="Search by name, email, or ID..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                    </div>
                    
                    <div>
                        <label><i class="fas fa-user-tag"></i> Role</label>
                        <select name="role" class="form-control">
                            <option value="">All Roles</option>
                            <option value="student" <?php echo $filter_role === 'student' ? 'selected' : ''; ?>>Student</option>
                            <option value="faculty" <?php echo $filter_role === 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                            <option value="admin" <?php echo $filter_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; align-items: flex-end; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="manage_users.php" class="btn btn-outline" style="flex: 1;">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Users (<?php echo number_format($total); ?> total)</h3>
            </div>

            <?php if ($users->num_rows > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Items Reported</th>
                                <th>Claims Made</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($user['user_id']); ?></code></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php
                                        $role_class = [
                                            'student' => 'badge-info',
                                            'faculty' => 'badge-warning',
                                            'admin' => 'badge-danger'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $role_class[$user['user_role']] ?? 'badge-secondary'; ?>">
                                            <?php echo strtoupper($user['user_role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($user['items_reported']); ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($user['claims_made']); ?></strong>
                                    </td>
                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <!-- Change Role Modal Button -->
                                            <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#roleModal<?php echo $user['user_id']; ?>" title="Change Role">
                                                <i class="fas fa-user-cog"></i>
                                            </button>
                                            
                                            <!-- Delete Button -->
                                            <?php if ($user['items_reported'] == 0 && $user['claims_made'] == 0): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this user?');">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline" disabled title="Cannot delete user with items/claims">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Role Change Modal -->
                                        <div class="modal" id="roleModal<?php echo $user['user_id']; ?>">
                                            <div class="modal-content" style="width: 90%; max-width: 400px;">
                                                <div class="modal-header">
                                                    <h3>Change Role: <?php echo htmlspecialchars($user['full_name']); ?></h3>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                        
                                                        <div style="margin-bottom: 1rem;">
                                                            <label><i class="fas fa-user-tag"></i> New Role</label>
                                                            <select name="new_role" class="form-control" required>
                                                                <option value="">Select a role...</option>
                                                                <option value="student" <?php echo $user['user_role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                                                <option value="faculty" <?php echo $user['user_role'] === 'faculty' ? 'selected' : ''; ?>>Faculty</option>
                                                                <option value="admin" <?php echo $user['user_role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline" data-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="action" value="change_role" class="btn btn-primary">Change Role</button>
                                                    </div>
                                                </form>
                                            </div>
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
                            <a href="?page=<?php echo $i; ?><?php echo $filter_role ? "&role=$filter_role" : ''; ?><?php echo $search ? "&search=" . urlencode($search) : ''; ?>" 
                               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem;">
                    <i class="fas fa-users" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                    <p>No users found matching your criteria</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Styling & Scripts -->
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--white);
            padding: 0;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
        }

        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-600);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
    </style>

    <script>
        // Simple modal functionality
        document.querySelectorAll('[data-toggle="modal"]').forEach(btn => {
            btn.addEventListener('click', function() {
                const target = this.getAttribute('data-target');
                document.querySelector(target).classList.add('show');
            });
        });

        document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.modal').classList.remove('show');
            });
        });

        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                }
            });
        });
    </script>

    <footer style="background-color: var(--dark-bg); color: var(--white); padding: 2rem; text-align: center; margin-top: 3rem;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Admin Panel</p>
    </footer>
</body>
</html>
