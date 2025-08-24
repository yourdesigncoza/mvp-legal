<?php
// Database Fix Script
// Run this once to add missing columns

require_once __DIR__ . '/../app/db.php';

try {
    echo "Checking database structure...\n<br>";
    
    // Add stored_filename column if missing
    $sql1 = "ALTER TABLE cases ADD COLUMN stored_filename VARCHAR(255) NULL DEFAULT NULL AFTER original_filename";
    try {
        db_execute($sql1);
        echo "✅ Added stored_filename column\n<br>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✓ stored_filename column already exists\n<br>";
        } else {
            echo "❌ Error adding stored_filename: " . $e->getMessage() . "\n<br>";
        }
    }
    
    // Add analyzed_at column if missing
    $sql2 = "ALTER TABLE cases ADD COLUMN analyzed_at TIMESTAMP NULL DEFAULT NULL";
    try {
        db_execute($sql2);
        echo "✅ Added analyzed_at column\n<br>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✓ analyzed_at column already exists\n<br>";
        } else {
            echo "❌ Error adding analyzed_at: " . $e->getMessage() . "\n<br>";
        }
    }
    
    // Show current table structure
    echo "\n<br><strong>Current cases table structure:</strong>\n<br>";
    $result = db_query("DESCRIBE cases");
    if ($result) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "\n<br><strong>Database fix complete!</strong> You can now delete this file.\n<br>";
    echo "<a href='upload.php'>← Back to Upload</a>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>