<?php require_once 'config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Lost & Found Management System</title>
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
            background: var(--white);
            color: var(--dark);
            line-height: 1.6;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.05);
            z-index: 1000;
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .navbar-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .navbar-menu a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .navbar-menu a:hover,
        .navbar-menu a.active {
            color: var(--primary);
        }

        .notification-badge {
            position: relative;
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            border-radius: 10px;
            font-weight: 600;
        }

        /* Hero Section */
        .hero-section {
            margin-top: 80px;
            padding: 5rem 2rem 4rem;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-content {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero-content h1 {
            font-size: 3rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 1.5rem;
        }

        .hero-content p {
            font-size: 1.25rem;
            color: var(--secondary);
            margin-bottom: 2.5rem;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .btn-secondary {
            background: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background: #475569;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: #eff6ff;
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .btn-block {
            width: 100%;
            justify-content: center;
        }

        /* Sections */
        .features-section {
            padding: 5rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .features-section h2 {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 3rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid var(--border);
        }

        .feature-card:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .feature-card i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.75rem;
        }

        .feature-card p {
            color: var(--secondary);
            line-height: 1.7;
        }

        /* Statistics */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .stat-card i {
            font-size: 3rem;
            color: var(--primary);
        }

        .stat-card.success i {
            color: var(--success);
        }

        .stat-card.warning i {
            color: var(--warning);
        }

        .stat-card.danger i {
            color: var(--danger);
        }

        .stat-info h3 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
        }

        .stat-info p {
            color: var(--secondary);
            font-size: 0.95rem;
        }

        /* Items Grid */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .item-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .item-card:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }

        .item-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: var(--light-bg);
        }

        .item-content {
            padding: 1.5rem;
        }

        .item-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .item-badge.lost {
            background: #fee2e2;
            color: var(--danger);
        }

        .item-badge.found {
            background: #d1fae5;
            color: var(--success);
        }

        .item-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .item-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            color: var(--secondary);
            font-size: 0.9rem;
        }

        .item-meta div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .item-actions {
            margin-top: 1rem;
        }

        /* Footer */
        footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 3rem 2rem;
            text-align: center;
        }

        footer p {
            margin-bottom: 0.5rem;
        }

        .text-center {
            text-align: center;
        }

        .mt-4 {
            margin-top: 2rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar-menu {
                display: none;
            }

            .hero-content h1 {
                font-size: 2rem;
            }

            .hero-content p {
                font-size: 1.1rem;
            }

            .features-section h2 {
                font-size: 2rem;
            }

            .features-grid,
            .items-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .feature-card,
        .stat-card,
        .item-card {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
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
                    <?php if (hasRole('admin')): ?>
                        <li><a href="admin/dashboard.php"><i class="fas fa-user-shield"></i> Admin</a></li>
                    <?php endif; ?>
                    <?php if (hasRole('faculty')): ?>
                        <li><a href="feedback_analytics.php"><i class="fas fa-chart-line"></i> Feedback</a></li>
                    <?php endif; ?>
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
                    <a href="login.php" class="btn btn-lg btn-outline">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </a>
                <?php endif; ?>
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
    
    <section class="features-section" style="background-color: var(--light-bg); padding: 4rem 2rem;">
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

    <!-- Features Section -->
    <section class="features-section">
        <h2><i class="fas fa-star"></i> Why Choose <?php echo APP_NAME; ?>?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-bolt"></i>
                <h3>Quick Reporting</h3>
                <p>Report lost or found items in seconds with our easy-to-use interface</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-search"></i>
                <h3>Smart Search</h3>
                <p>Find items quickly with advanced search and filtering options</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-bell"></i>
                <h3>Real-Time Notifications</h3>
                <p>Get instant alerts when your lost item is found or claims are updated</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Secure Claims</h3>
                <p>Verified claim process ensures items reach their rightful owners</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-users"></i>
                <h3>Community Driven</h3>
                <p>Join your campus community in helping others find their belongings</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-chart-line"></i>
                <h3>Analytics Dashboard</h3>
                <p>Track statistics and success rates of item recoveries</p>
            </div>
        </div>
    </section>

    <!-- Recent Items Section -->
    <section class="features-section" style="background-color: var(--light-bg);">
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
                        <div class="item-image" style="display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-image" style="font-size: 3rem; color: #cbd5e1;"></i>
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
    <section class="features-section">
        <h2><i class="fas fa-question-circle"></i> How It Works</h2>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-user-plus"></i>
                <h3>1. Register</h3>
                <p>Create your account with student/faculty credentials</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-edit"></i>
                <h3>2. Report</h3>
                <p>Report your lost item or found item with details and photos</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-search"></i>
                <h3>3. Search</h3>
                <p>Browse through reported items to find matches</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-handshake"></i>
                <h3>4. Claim</h3>
                <p>Submit a claim with proof of ownership</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-check-double"></i>
                <h3>5. Verify</h3>
                <p>Admin verifies and approves legitimate claims</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-smile"></i>
                <h3>6. Reunite</h3>
                <p>Get your item back and mark it as claimed!</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - Campus Lost & Found Management System</p>
        <p>Developed with <i class="fas fa-heart" style="color: var(--danger);"></i> for our campus community</p>
    </footer>

    <script>
        // Smooth fade-in animation
        document.addEventListener('DOMContentLoaded', function() {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s ease-in';
                document.body.style.opacity = '1';
            }, 100);
        });

        // Add scroll animation to cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '0';
                    entry.target.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        entry.target.style.transition = 'all 0.6s ease';
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, 100);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card, .stat-card, .item-card').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>
