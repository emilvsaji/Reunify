<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reunify - System Check</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        h1 { color: #4F46E5; margin-bottom: 1rem; }
        .check-item { padding: 1rem; margin-bottom: 1rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .success { background: #D1FAE5; color: #065F46; border-left: 4px solid #10B981; }
        .error { background: #FEE2E2; color: #991B1B; border-left: 4px solid #EF4444; }
        .warning { background: #FEF3C7; color: #92400E; border-left: 4px solid #F59E0B; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-weight: 600; font-size: 0.875rem; }
        .badge-success { background: #10B981; color: white; }
        .badge-error { background: #EF4444; color: white; }
        .badge-warning { background: #F59E0B; color: white; }
        .info { background: #F3F4F6; padding: 1rem; border-radius: 8px; margin-top: 2rem; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #4F46E5; color: white; text-decoration: none; border-radius: 8px; margin-top: 1rem; }
        .btn:hover { background: #4338CA; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Reunify System Check</h1>
        <p style="margin-bottom: 2rem; color: #6B7280;">Verifying your installation...</p>

        <?php
        $checks = [];
        $errors = 0;
        $warnings = 0;

        // Check PHP version
        $php_version = phpversion();
        $php_ok = version_compare($php_version, '7.4.0', '>=');
        $checks[] = [
            'name' => 'PHP Version',
            'status' => $php_ok ? 'success' : 'error',
            'message' => "PHP $php_version" . ($php_ok ? ' ✓' : ' (Requires 7.4+)')
        ];
        if (!$php_ok) $errors++;

        // Check database connection
        $db_exists = file_exists(__DIR__ . '/config/config.php');
        if ($db_exists) {
            require_once 'config/config.php';
            try {
                $db = getDB();
                $checks[] = [
                    'name' => 'Database Connection',
                    'status' => 'success',
                    'message' => 'Connected to ' . DB_NAME . ' ✓'
                ];
            } catch (Exception $e) {
                $checks[] = [
                    'name' => 'Database Connection',
                    'status' => 'error',
                    'message' => 'Failed: ' . $e->getMessage()
                ];
                $errors++;
            }
        } else {
            $checks[] = [
                'name' => 'Configuration File',
                'status' => 'error',
                'message' => 'config/config.php not found'
            ];
            $errors++;
        }

        // Check upload directory
        $upload_dir = __DIR__ . '/uploads';
        $uploads_ok = is_dir($upload_dir) && is_writable($upload_dir);
        $checks[] = [
            'name' => 'Uploads Directory',
            'status' => $uploads_ok ? 'success' : 'error',
            'message' => $uploads_ok ? 'Exists and writable ✓' : 'Not found or not writable'
        ];
        if (!$uploads_ok) $errors++;

        // Check items directory
        $items_dir = __DIR__ . '/uploads/items';
        $items_ok = is_dir($items_dir) && is_writable($items_dir);
        $checks[] = [
            'name' => 'Items Upload Directory',
            'status' => $items_ok ? 'success' : 'warning',
            'message' => $items_ok ? 'Exists and writable ✓' : 'Create: uploads/items/'
        ];
        if (!$items_ok) $warnings++;

        // Check claims directory
        $claims_dir = __DIR__ . '/uploads/claims';
        $claims_ok = is_dir($claims_dir) && is_writable($claims_dir);
        $checks[] = [
            'name' => 'Claims Upload Directory',
            'status' => $claims_ok ? 'success' : 'warning',
            'message' => $claims_ok ? 'Exists and writable ✓' : 'Create: uploads/claims/'
        ];
        if (!$claims_ok) $warnings++;

        // Check GD library
        $gd_ok = extension_loaded('gd');
        $checks[] = [
            'name' => 'GD Library',
            'status' => $gd_ok ? 'success' : 'warning',
            'message' => $gd_ok ? 'Installed ✓' : 'Not installed (optional for image processing)'
        ];
        if (!$gd_ok) $warnings++;

        // Check mysqli
        $mysqli_ok = extension_loaded('mysqli');
        $checks[] = [
            'name' => 'MySQLi Extension',
            'status' => $mysqli_ok ? 'success' : 'error',
            'message' => $mysqli_ok ? 'Installed ✓' : 'Not installed (required)'
        ];
        if (!$mysqli_ok) $errors++;

        // Check file permissions
        $config_readable = is_readable(__DIR__ . '/config/config.php');
        $checks[] = [
            'name' => 'File Permissions',
            'status' => $config_readable ? 'success' : 'error',
            'message' => $config_readable ? 'Config file readable ✓' : 'Cannot read config file'
        ];
        if (!$config_readable) $errors++;

        // Check .htaccess
        $htaccess_ok = file_exists(__DIR__ . '/.htaccess');
        $checks[] = [
            'name' => '.htaccess File',
            'status' => $htaccess_ok ? 'success' : 'warning',
            'message' => $htaccess_ok ? 'Exists ✓' : 'Not found (optional but recommended)'
        ];
        if (!$htaccess_ok) $warnings++;

        // Display checks
        foreach ($checks as $check) {
            $class = $check['status'];
            $badge_class = 'badge-' . $check['status'];
            echo "<div class='check-item $class'>";
            echo "<div><strong>{$check['name']}</strong><br><small>{$check['message']}</small></div>";
            echo "<span class='badge $badge_class'>" . strtoupper($check['status']) . "</span>";
            echo "</div>";
        }

        // Summary
        $total = count($checks);
        $success = $total - $errors - $warnings;
        ?>

        <div class="info">
            <h3>Summary</h3>
            <p><strong>Total Checks:</strong> <?php echo $total; ?></p>
            <p><strong>✓ Passed:</strong> <?php echo $success; ?></p>
            <?php if ($warnings > 0): ?>
                <p><strong>⚠ Warnings:</strong> <?php echo $warnings; ?></p>
            <?php endif; ?>
            <?php if ($errors > 0): ?>
                <p><strong>✗ Errors:</strong> <?php echo $errors; ?></p>
            <?php endif; ?>
            
            <?php if ($errors === 0): ?>
                <p style="color: #10B981; font-weight: bold; margin-top: 1rem;">
                    🎉 Your system is ready! All critical checks passed.
                </p>
                <a href="index.html" class="btn">Go to Application →</a>
            <?php else: ?>
                <p style="color: #EF4444; font-weight: bold; margin-top: 1rem;">
                    ⚠ Please fix the errors above before using the application.
                </p>
                <a href="INSTALLATION.md" class="btn">View Installation Guide →</a>
            <?php endif; ?>
        </div>

        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #E5E7EB; text-align: center; color: #6B7280;">
            <p>Reunify - Lost & Found Management System</p>
            <p style="font-size: 0.875rem;">For help, check INSTALLATION.md or README.md</p>
        </div>
    </div>
</body>
</html>
