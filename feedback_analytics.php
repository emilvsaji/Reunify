<?php
require_once 'config/config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$db = getDB();
$error = '';
$success = '';

// Handle response submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_response') {
        $feedback_id = intval($_POST['feedback_id']);
        $response_text = sanitize($_POST['response_text'] ?? '');
        $status = sanitize($_POST['status'] ?? '');
        
        if (empty($response_text) || empty($status)) {
            $error = 'Please provide both response text and status';
        } elseif (!in_array($status, ['new', 'reviewed', 'in_progress', 'resolved'])) {
            $error = 'Invalid status';
        } else {
            // Update feedback with faculty response
            $query = "UPDATE feedback SET faculty_response = ?, status = ?, reviewed_by = ? WHERE feedback_id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt) {
                $stmt->bind_param("ssii", $response_text, $status, $_SESSION['user_id'], $feedback_id);
                if ($stmt->execute()) {
                    $success = 'Response added successfully!';
                } else {
                    $error = 'Error updating feedback';
                }
                $stmt->close();
            }
        }
    }
}

// Get filter parameters
$category_filter = sanitize($_GET['category'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');
$rating_filter = sanitize($_GET['rating'] ?? '');
$sort_by = sanitize($_GET['sort'] ?? 'created_at');
$sort_order = (sanitize($_GET['order'] ?? 'DESC') === 'ASC') ? 'ASC' : 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where_clauses = [];
$params = [];
$param_types = '';

if (!empty($category_filter)) {
    $where_clauses[] = 'category = ?';
    $params[] = $category_filter;
    $param_types .= 's';
}

if (!empty($status_filter)) {
    $where_clauses[] = 'status = ?';
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($rating_filter)) {
    $where_clauses[] = 'rating = ?';
    $params[] = intval($rating_filter);
    $param_types .= 'i';
}

$where = '';
if (!empty($where_clauses)) {
    $where = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Valid sort columns
$valid_sorts = ['created_at', 'rating', 'status', 'category'];
if (!in_array($sort_by, $valid_sorts)) {
    $sort_by = 'created_at';
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM feedback $where";
$stmt = $db->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$count_result = $stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);
$stmt->close();

// Get feedback records
$query = "SELECT f.*, u.full_name, u.email, u.student_id 
          FROM feedback f
          JOIN users u ON f.student_id = u.user_id
          $where
          ORDER BY $sort_by $sort_order
          LIMIT ? OFFSET ?";

$stmt = $db->prepare($query);
$params[] = $limit;
$params[] = $offset;
$param_types .= 'ii';

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$feedbacks = [];
while ($row = $result->fetch_assoc()) {
    $feedbacks[] = $row;
}
$stmt->close();

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_feedback,
    AVG(rating) as avg_rating,
    COUNT(CASE WHEN status = 'new' THEN 1 END) as new_count,
    COUNT(CASE WHEN status = 'reviewed' THEN 1 END) as reviewed_count,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_count,
    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_count
FROM feedback";

$stats_result = $db->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get category statistics
$category_query = "SELECT category, COUNT(*) as count, AVG(rating) as avg_rating
                   FROM feedback
                   GROUP BY category
                   ORDER BY count DESC";
$category_result = $db->query($category_query);
$category_stats = [];
while ($row = $category_result->fetch_assoc()) {
    $category_stats[] = $row;
}

function getCategoryBadgeColor($category) {
    $colors = [
        'application' => '#3b82f6',
        'features' => '#8b5cf6',
        'performance' => '#06b6d4',
        'user_experience' => '#ec4899',
        'documentation' => '#14b8a6',
        'other' => '#6b7280'
    ];
    return $colors[$category] ?? '#6b7280';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Analytics - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1e293b;
            --light-bg: #f8fafc;
            --white: #ffffff;
            --border: #e2e8f0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--light-bg);
            color: var(--dark);
        }

        .navbar {
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--primary);
        }

        .navbar-menu {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .navbar-menu a {
            text-decoration: none;
            color: var(--dark);
            transition: color 0.3s;
        }

        .navbar-menu a:hover {
            color: var(--primary);
        }

        .btn-logout {
            background: var(--danger);
            color: var(--white);
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: #dc2626;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: var(--secondary);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            min-height: 160px;
        }

        .stat-card-label {
            color: var(--secondary);
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .stat-card-value {
            font-size: 2.8rem;
            font-weight: bold;
            color: var(--dark);
            margin-bottom: 0.75rem;
            line-height: 1;
        }

        .stat-card-sublabel {
            font-size: 0.9rem;
            color: var(--secondary);
            margin-top: auto;
            font-style: italic;
        }

        .stats-grid .stat-card:nth-child(2) {
            border-left-color: var(--success);
        }

        .stats-grid .stat-card:nth-child(3) {
            border-left-color: var(--info);
        }

        .stats-grid .stat-card:nth-child(4) {
            border-left-color: var(--warning);
        }

        .stats-grid .stat-card:nth-child(5) {
            border-left-color: var(--danger);
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .chart-card h3 {
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .category-stat {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .category-stat:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .category-name {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
            color: var(--white);
        }

        .category-bar {
            background: var(--border);
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }

        .category-bar-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 10px;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #b91c1c;
            border-left: 4px solid var(--danger);
        }

        .filters {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark);
            font-size: 0.9rem;
        }

        .form-control {
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: var(--secondary);
            color: var(--white);
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }

        .table-container {
            background: var(--white);
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--light-bg);
            border-bottom: 2px solid var(--border);
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            cursor: pointer;
        }

        th a {
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        th a:hover {
            color: var(--primary);
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }

        tbody tr:hover {
            background: var(--light-bg);
        }

        .badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: #92400e;
        }

        .badge-info {
            background: rgba(59, 130, 246, 0.1);
            color: #1e40af;
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
        }

        .badge-secondary {
            background: rgba(100, 116, 139, 0.1);
            color: #334155;
        }

        .rating-stars {
            color: #f59e0b;
            font-size: 0.9rem;
        }

        .feedback-subject {
            font-weight: 500;
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .student-name {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .student-id {
            font-size: 0.85rem;
            color: var(--secondary);
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            text-decoration: none;
            color: var(--primary);
        }

        .pagination a:hover {
            background: var(--light-bg);
        }

        .pagination .active {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            animation: fadeIn 0.3s;
        }

        .modal-content {
            background-color: var(--white);
            margin: 5% auto;
            padding: 2rem;
            border-radius: 0.75rem;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.3s;
            max-height: 85vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            margin: 0;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--secondary);
        }

        .close-btn:hover {
            color: var(--dark);
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: var(--secondary);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
                min-height: 150px;
            }

            .stat-card-label {
                font-size: 0.85rem;
                margin-bottom: 0.5rem;
            }

            .stat-card-value {
                font-size: 2.2rem;
                margin-bottom: 0.5rem;
            }

            .stat-card-sublabel {
                font-size: 0.8rem;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 0.9rem;
            }

            th, td {
                padding: 0.75rem;
            }

            .navbar-menu {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .page-header h1 {
                font-size: 1.2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .stat-card {
                padding: 1.25rem;
                min-height: 130px;
            }

            .stat-card-label {
                font-size: 0.8rem;
                margin-bottom: 0.4rem;
            }

            .stat-card-value {
                font-size: 1.8rem;
                margin-bottom: 0.4rem;
            }

            .stat-card-sublabel {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-chart-bar"></i> Feedback Analytics
            </div>
            <div class="navbar-menu">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="feedback_analytics.php" style="color: var(--primary); font-weight: 600;"><i class="fas fa-comments"></i> Feedback</a>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-chart-bar"></i> Feedback Analytics Dashboard</h1>
            <p>Review and analyze feedback from students to help improve the Lost & Found system.</p>
        </div>

        <!-- Alerts -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-label">Total Feedback</div>
                <div class="stat-card-value"><?php echo $stats['total_feedback']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Average Rating</div>
                <div class="stat-card-value"><?php echo round($stats['avg_rating'], 1); ?>/5</div>
                <div class="stat-card-sublabel">Out of 5 Stars</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">New Feedback</div>
                <div class="stat-card-value"><?php echo $stats['new_count']; ?></div>
                <div class="stat-card-sublabel">Awaiting Review</div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">In Progress</div>
                <div class="stat-card-value"><?php echo $stats['in_progress_count']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Resolved</div>
                <div class="stat-card-value"><?php echo $stats['resolved_count']; ?></div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3><i class="fas fa-list"></i> Feedback by Category</h3>
                <?php foreach ($category_stats as $cat): ?>
                    <div class="category-stat">
                        <div class="category-name">
                            <span class="category-badge" style="background-color: <?php echo getCategoryBadgeColor($cat['category']); ?>;">
                                <?php echo ucfirst(str_replace('_', ' ', $cat['category'])); ?>
                            </span>
                            <span>(<?php echo $cat['count']; ?>)</span>
                            <span class="rating-stars" title="Average: <?php echo round($cat['avg_rating'], 1); ?>">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star" style="color: <?php echo $i <= round($cat['avg_rating']) ? '#f59e0b' : '#e2e8f0'; ?>; font-size: 0.8rem;"></i>
                                <?php endfor; ?>
                            </span>
                        </div>
                        <div class="category-bar">
                            <div class="category-bar-fill" style="width: <?php echo ($cat['count'] / $stats['total_feedback'] * 100); ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="chart-card">
                <h3><i class="fas fa-tasks"></i> Feedback Status</h3>
                <div class="category-stat">
                    <div class="category-name">
                        <span class="category-badge" style="background-color: #ef4444;">New</span>
                        <span>(<?php echo $stats['new_count']; ?>)</span>
                    </div>
                    <div class="category-bar">
                        <div class="category-bar-fill" style="background: #ef4444; width: <?php echo ($stats['new_count'] / $stats['total_feedback'] * 100); ?>%"></div>
                    </div>
                </div>
                <div class="category-stat">
                    <div class="category-name">
                        <span class="category-badge" style="background-color: #f59e0b;">Reviewed</span>
                        <span>(<?php echo $stats['reviewed_count']; ?>)</span>
                    </div>
                    <div class="category-bar">
                        <div class="category-bar-fill" style="background: #f59e0b; width: <?php echo ($stats['reviewed_count'] / $stats['total_feedback'] * 100); ?>%"></div>
                    </div>
                </div>
                <div class="category-stat">
                    <div class="category-name">
                        <span class="category-badge" style="background-color: #3b82f6;">In Progress</span>
                        <span>(<?php echo $stats['in_progress_count']; ?>)</span>
                    </div>
                    <div class="category-bar">
                        <div class="category-bar-fill" style="background: #3b82f6; width: <?php echo ($stats['in_progress_count'] / $stats['total_feedback'] * 100); ?>%"></div>
                    </div>
                </div>
                <div class="category-stat">
                    <div class="category-name">
                        <span class="category-badge" style="background-color: #10b981;">Resolved</span>
                        <span>(<?php echo $stats['resolved_count']; ?>)</span>
                    </div>
                    <div class="category-bar">
                        <div class="category-bar-fill" style="background: #10b981; width: <?php echo ($stats['resolved_count'] / $stats['total_feedback'] * 100); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <option value="application" <?php echo ($category_filter === 'application' ? 'selected' : ''); ?>>Application Issues</option>
                            <option value="features" <?php echo ($category_filter === 'features' ? 'selected' : ''); ?>>Feature Suggestions</option>
                            <option value="performance" <?php echo ($category_filter === 'performance' ? 'selected' : ''); ?>>Performance</option>
                            <option value="user_experience" <?php echo ($category_filter === 'user_experience' ? 'selected' : ''); ?>>User Experience</option>
                            <option value="documentation" <?php echo ($category_filter === 'documentation' ? 'selected' : ''); ?>>Documentation</option>
                            <option value="other" <?php echo ($category_filter === 'other' ? 'selected' : ''); ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="new" <?php echo ($status_filter === 'new' ? 'selected' : ''); ?>>New</option>
                            <option value="reviewed" <?php echo ($status_filter === 'reviewed' ? 'selected' : ''); ?>>Reviewed</option>
                            <option value="in_progress" <?php echo ($status_filter === 'in_progress' ? 'selected' : ''); ?>>In Progress</option>
                            <option value="resolved" <?php echo ($status_filter === 'resolved' ? 'selected' : ''); ?>>Resolved</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rating</label>
                        <select name="rating" class="form-control">
                            <option value="">All Ratings</option>
                            <option value="5" <?php echo ($rating_filter === '5' ? 'selected' : ''); ?>>5 Stars</option>
                            <option value="4" <?php echo ($rating_filter === '4' ? 'selected' : ''); ?>>4 Stars</option>
                            <option value="3" <?php echo ($rating_filter === '3' ? 'selected' : ''); ?>>3 Stars</option>
                            <option value="2" <?php echo ($rating_filter === '2' ? 'selected' : ''); ?>>2 Stars</option>
                            <option value="1" <?php echo ($rating_filter === '1' ? 'selected' : ''); ?>>1 Star</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex-direction: row; align-items: flex-end; gap: 0.5rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="feedback_analytics.php" class="btn btn-secondary" style="flex: 1;">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Feedback Table -->
        <div class="table-container">
            <?php if (!empty($feedbacks)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>
                                <a href="?category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&sort=subject&order=<?php echo ($sort_by === 'subject' && $sort_order === 'ASC' ? 'DESC' : 'ASC'); ?>">
                                    Subject <i class="fas fa-sort-down"></i>
                                </a>
                            </th>
                            <th>Category</th>
                            <th>
                                <a href="?category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&sort=rating&order=<?php echo ($sort_by === 'rating' && $sort_order === 'ASC' ? 'DESC' : 'ASC'); ?>">
                                    Rating <i class="fas fa-sort-down"></i>
                                </a>
                            </th>
                            <th>
                                <a href="?category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&sort=status&order=<?php echo ($sort_by === 'status' && $sort_order === 'ASC' ? 'DESC' : 'ASC'); ?>">
                                    Status <i class="fas fa-sort-down"></i>
                                </a>
                            </th>
                            <th>
                                <a href="?category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&sort=created_at&order=<?php echo ($sort_by === 'created_at' && $sort_order === 'ASC' ? 'DESC' : 'ASC'); ?>">
                                    Date <i class="fas fa-sort-down"></i>
                                </a>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedbacks as $feedback): ?>
                            <tr>
                                <td>
                                    <div class="student-name">
                                        <strong><?php echo htmlspecialchars($feedback['is_anonymous'] ? 'Anonymous' : $feedback['full_name']); ?></strong>
                                        <span class="student-id"><?php echo htmlspecialchars($feedback['is_anonymous'] ? 'N/A' : ($feedback['student_id'] ?? 'N/A')); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="feedback-subject" title="<?php echo htmlspecialchars($feedback['subject']); ?>">
                                        <?php echo htmlspecialchars($feedback['subject']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: <?php echo getCategoryBadgeColor($feedback['category']); ?>20; color: <?php echo getCategoryBadgeColor($feedback['category']); ?>;">
                                        <?php echo ucfirst(str_replace('_', ' ', $feedback['category'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star" style="color: <?php echo $i <= $feedback['rating'] ? '#f59e0b' : '#e2e8f0'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $status_badges = [
                                        'new' => 'badge-danger',
                                        'reviewed' => 'badge-info',
                                        'in_progress' => 'badge-warning',
                                        'resolved' => 'badge-success'
                                    ];
                                    $badge_class = $status_badges[$feedback['status']] ?? 'badge-secondary';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $feedback['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($feedback['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="openModal(<?php echo $feedback['feedback_id']; ?>, '<?php echo htmlspecialchars($feedback['subject']); ?>')">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination" style="padding: 1rem 0 0; margin: 0; border-top: 1px solid var(--border); display: flex; justify-content: center; gap: 0.5rem; padding-top: 1rem; margin-top: 1rem;">
                        <?php if ($page > 1): ?>
                            <a href="?page=1&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>">First</a>
                            <a href="?page=<?php echo $page - 1; ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>">Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>">Next</a>
                            <a href="?page=<?php echo $total_pages; ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&rating=<?php echo $rating_filter; ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>">Last</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                    <p>No feedback found matching the selected criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Feedback Detail Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-comments"></i> Feedback Details</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div id="feedbackDetails"></div>
        </div>
    </div>

    <script>
        function openModal(feedbackId, subject) {
            const modal = document.getElementById('feedbackModal');
            const detailsDiv = document.getElementById('feedbackDetails');

            // Make AJAX request to get feedback details
            fetch('get_feedback_details.php?id=' + feedbackId)
                .then(response => response.text())
                .then(data => {
                    detailsDiv.innerHTML = data;
                    modal.style.display = 'block';
                })
                .catch(error => {
                    alert('Error loading feedback details');
                    console.error(error);
                });
        }

        function closeModal() {
            document.getElementById('feedbackModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('feedbackModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
