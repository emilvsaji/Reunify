<?php
require_once 'config/config.php';

$db = getDB();

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Check</title>
    <link rel='stylesheet' href='assets/css/style.css'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body>
    <div class='dashboard-container' style='margin-top: 3rem;'>
        <div class='card'>
            <h2><i class='fas fa-database'></i> Claims Table Structure</h2>
            <hr>";

$result = $db->query("DESCRIBE claims");

if ($result) {
    echo "<table style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background: var(--gray-100); border-bottom: 2px solid var(--gray-300);'>";
    echo "<th style='padding: 0.75rem; text-align: left;'>Field</th>";
    echo "<th style='padding: 0.75rem; text-align: left;'>Type</th>";
    echo "<th style='padding: 0.75rem; text-align: left;'>Null</th>";
    echo "<th style='padding: 0.75rem; text-align: left;'>Key</th>";
    echo "<th style='padding: 0.75rem; text-align: left;'>Default</th>";
    echo "</tr>";
    
    $found_columns = [];
    while ($row = $result->fetch_assoc()) {
        echo "<tr style='border-bottom: 1px solid var(--gray-200);'>";
        echo "<td style='padding: 0.75rem;'><strong>" . htmlspecialchars($row['Field']) . "</strong></td>";
        echo "<td style='padding: 0.75rem;'>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td style='padding: 0.75rem;'>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td style='padding: 0.75rem;'>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td style='padding: 0.75rem;'>" . htmlspecialchars($row['Default'] ?? '-') . "</td>";
        echo "</tr>";
        
        $found_columns[] = $row['Field'];
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3 style='margin-top: 1.5rem;'>Required Columns Status:</h3>";
    echo "<ul style='list-style: none; padding: 0;'>";
    
    $required = ['claimer_name', 'claimer_phone', 'claimer_email'];
    foreach ($required as $col) {
        if (in_array($col, $found_columns)) {
            echo "<li style='padding: 0.5rem; color: green;'><i class='fas fa-check-circle'></i> ✓ $col - Present</li>";
        } else {
            echo "<li style='padding: 0.5rem; color: red;'><i class='fas fa-times-circle'></i> ✗ $col - Missing</li>";
        }
    }
    echo "</ul>";
    
} else {
    echo "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> Error: " . $db->error . "</div>";
}

echo "
        </div>
    </div>
</body>
</html>";
?>
