<?php require_once 'config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Lost & Found Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.html" class="navbar-brand">
                <i class="fas fa-search-location"></i>
                <span><?php echo APP_NAME; ?></span>
            </a>
            <ul class="navbar-menu">
                <li><a href="home.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="view_lost_items.php"><i class="fas fa-heart-broken"></i> Lost Items</a></li>
                <li><a href="view_found_items.php"><i class="fas fa-hand-holding-heart"></i> Found Items</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="notifications.php" class="notification-badge">
                        <i class="fas fa-bell"></i> Notifications
                        <?php 
                        $unread = getUnreadNotificationCount($_SESSION['user_id']);
                        if ($unread > 0): 
                        ?>
                            <span class="badge"><?php echo $unread; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1><i class="fas fa-search-location"></i> Welcome to <?php echo APP_NAME; ?></h1>
            <p>Your Campus Lost & Found Solution - Helping reunite people with their belongings</p>
            <div class="hero-buttons">
                <?php if (isLoggedIn()): ?>
                    <a href="report_lost.php" class="btn btn-lg btn-primary">
                        <i class="fas fa-exclamation-circle"></i> Report Lost Item
                    </a>
                    <a href="report_found.php" class="btn btn-lg btn-outline">
                        <i class="fas fa-check-circle"></i> Report Found Item
                    </a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-lg btn-primary">
                        <i class="fas fa-user-plus"></i> Get Started
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <h2><i class="fas fa-star"></i> Why Choose <?php echo APP_NAME; ?>?</h2>
        <div class="features-grid">
            <div class="feature-card card">
                <i class="fas fa-bolt"></i>
                <h3>Quick Reporting</h3>
                <p>Report lost or found items in seconds with our easy-to-use interface</p>
            </div>
            
            <div class="feature-card card">
                <i class="fas fa-search"></i>
                <h3>Smart Search</h3>
                <p>Find items quickly with advanced search and filtering options</p>
            </div>
            
            <div class="feature-card card">
                <i class="fas fa-bell"></i>
                <h3>Real-Time Notifications</h3>
                <p>Get instant alerts when your lost item is found or claims are updated</p>
            </div>
            
            <div class="feature-card card">
                <i class="fas fa-shield-alt"></i>
                <h3>Secure Claims</h3>
                <p>Verified claim process ensures items reach their rightful owners</p>
            </div>
            
            <div class="feature-card card">
                <i class="fas fa-users"></i>
                <h3>Community Driven</h3>
                <p>Join your campus community in helping others find their belongings</p>
            </div>
            
            <div class="feature-card card">
                <i class="fas fa-chart-line"></i>
                <h3>Analytics Dashboard</h3>
                <p>Track statistics and success rates of item recoveries</p>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <?php
    $db = getDB();
    
    // Get statistics
    $stats_query = "SELECT 
        (SELECT COUNT(*) FROM items WHERE item_type = 'lost' AND status != 'rejected') as total_lost,
        (SELECT COUNT(*) FROM items WHERE item_type = 'found' AND status != 'rejected') as total_found,
        (SELECT COUNT(*) FROM items WHERE status = 'matched') as total_matched,
        (SELECT COUNT(*) FROM users) as total_users";
    $stats_result = $db->query($stats_query);
    $stats = $stats_result->fetch_assoc();
    ?>
    
    <section class="features-section" style="background-color: var(--white); padding: 3rem 2rem;">
        <h2><i class="fas fa-chart-bar"></i> Our Impact</h2>
        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-heart-broken"></i>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_lost']); ?></h3>
                    <p>Lost Items Reported</p>
                </div>
            </div>
            
            <div class="stat-card success">
                <i class="fas fa-hand-holding-heart"></i>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_found']); ?></h3>
                    <p>Found Items Reported</p>
                </div>
            </div>
            
            <div class="stat-card warning">
                <i class="fas fa-link"></i>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_matched']); ?></h3>
                    <p>Successful Matches</p>
                </div>
            </div>
            
            <div class="stat-card danger">
                <i class="fas fa-users"></i>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_users']); ?></h3>
                    <p>Registered Users</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Items Section -->
    <section class="features-section">
        <h2><i class="fas fa-clock"></i> Recently Reported Items</h2>
        
        <?php
        $recent_query = "SELECT i.*, c.category_name, u.full_name 
                        FROM items i 
                        JOIN categories c ON i.category_id = c.category_id 
                        JOIN users u ON i.reported_by = u.user_id 
                        WHERE i.status = 'approved' 
                        ORDER BY i.created_at DESC 
                        LIMIT 6";
        $recent_items = $db->query($recent_query);
        ?>
        
        <div class="items-grid">
            <?php while ($item = $recent_items->fetch_assoc()): ?>
                <div class="item-card">
                    <?php if ($item['item_image']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($item['item_image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>" class="item-image">
                    <?php else: ?>
                        <div class="item-image" style="display: flex; align-items: center; justify-content: center; background: var(--gray-200);">
                            <i class="fas fa-image" style="font-size: 3rem; color: var(--gray-400);"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="item-content">
                        <span class="item-badge <?php echo $item['item_type']; ?>">
                            <i class="fas fa-<?php echo $item['item_type'] === 'lost' ? 'heart-broken' : 'check-circle'; ?>"></i>
                            <?php echo strtoupper($item['item_type']); ?>
                        </span>
                        
                        <h3 class="item-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                        
                        <div class="item-meta">
                            <div><i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name']); ?></div>
                            <div><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location']); ?></div>
                            <div><i class="fas fa-calendar"></i> <?php echo formatDate($item['date_lost_found']); ?></div>
                        </div>
                        
                        <div class="item-actions">
                            <a href="item_details.php?id=<?php echo $item['item_id']; ?>" class="btn btn-primary btn-sm btn-block">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="view_lost_items.php" class="btn btn-primary">
                <i class="fas fa-th-large"></i> View All Lost Items
            </a>
            <a href="view_found_items.php" class="btn btn-secondary">
                <i class="fas fa-th-large"></i> View All Found Items
            </a>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="features-section" style="background-color: var(--white); padding: 3rem 2rem;">
        <h2><i class="fas fa-question-circle"></i> How It Works</h2>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-user-plus" style="color: var(--primary-color);"></i>
                <h3>1. Register</h3>
                <p>Create your account with student/faculty credentials</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-edit" style="color: var(--secondary-color);"></i>
                <h3>2. Report</h3>
                <p>Report your lost item or found item with details and photos</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-search" style="color: var(--warning-color);"></i>
                <h3>3. Search</h3>
                <p>Browse through reported items to find matches</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-handshake" style="color: var(--danger-color);"></i>
                <h3>4. Claim</h3>
                <p>Submit a claim with proof of ownership</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-check-double" style="color: var(--info-color);"></i>
                <h3>5. Verify</h3>
                <p>Admin verifies and approves legitimate claims</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-smile" style="color: var(--success-color);"></i>
                <h3>6. Reunite</h3>
                <p>Get your item back and mark it as claimed!</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background-color: var(--dark-bg); color: var(--white); padding: 2rem; text-align: center;">
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Campus Lost & Found Management System</p>
        <p>Developed with <i class="fas fa-heart" style="color: var(--danger-color);"></i> for our campus community</p>
    </footer>
</body>
</html>
