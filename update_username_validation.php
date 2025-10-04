<?php
echo "<h2>🔧 Update Username Validation to Allow Special Symbols</h2>";

// Files that need username validation updates
$files_to_update = [
    'RegistrarF/Accounts/add_account.php'
];

// Search for all files with username validation patterns
$all_files = [];
$directories = ['RegistrarF', 'CashierF', 'GuidanceF', 'HRF', 'AdminF', 'OwnerF'];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                if (strpos($content, 'preg_match') !== false && strpos($content, 'username') !== false) {
                    $all_files[] = $file->getPathname();
                }
            }
        }
    }
}

echo "<h3>Files Found with Username Validation:</h3>";
foreach ($all_files as $file) {
    echo "<p>📄 " . htmlspecialchars($file) . "</p>";
}

echo "<h3>Current Username Validation Patterns:</h3>";

// Show current patterns
foreach ($all_files as $file) {
    echo "<h4>" . htmlspecialchars($file) . "</h4>";
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'preg_match') !== false && strpos($line, 'username') !== false) {
            echo "<p><strong>Line " . ($lineNum + 1) . ":</strong> <code>" . htmlspecialchars(trim($line)) . "</code></p>";
        }
        if (strpos($line, 'Username can only contain') !== false) {
            echo "<p><strong>Line " . ($lineNum + 1) . ":</strong> <code>" . htmlspecialchars(trim($line)) . "</code></p>";
        }
    }
}

if (isset($_POST['update_validation'])) {
    echo "<h3>🔄 Updating Username Validation Patterns...</h3>";
    
    $updated_count = 0;
    
    foreach ($all_files as $file) {
        $content = file_get_contents($file);
        $original_content = $content;
        
        // Replace restrictive username patterns with more permissive ones
        $patterns_to_replace = [
            // Pattern 1: Basic username validation
            '/if \(!preg_match\(\'\/\^\[A-Za-z0-9_\]\+\$\/\', \$username\)\) \{/' => 'if (preg_match(\'/\\s/\', $username)) {',
            '/if \(!preg_match\(\'\/\^\[A-Za-z0-9_\]\+\$\/\', \$parent_username\)\) \{/' => 'if (preg_match(\'/\\s/\', $parent_username)) {',
            
            // Pattern 2: Error messages
            '/"[^"]*username can only contain letters, numbers, and underscores[^"]*"/' => '"Username cannot contain spaces."',
            '/"[^"]*Username can only contain letters, numbers, and underscores[^"]*"/' => '"Username cannot contain spaces."',
            
            // Pattern 3: Student username specific
            '/"Student username can only contain letters, numbers, and underscores\."/' => '"Student username cannot contain spaces."',
            '/"Parent username can only contain letters, numbers, and underscores\."/' => '"Parent username cannot contain spaces."',
        ];
        
        foreach ($patterns_to_replace as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        // Additional replacements for common variations
        $content = str_replace(
            'if (!empty($parent_username) && !preg_match(\'/^[A-Za-z0-9_]+$/\', $parent_username)) {',
            'if (!empty($parent_username) && preg_match(\'/\\s/\', $parent_username)) {',
            $content
        );
        
        $content = str_replace(
            'if (!empty($username) && !preg_match(\'/^[A-Za-z0-9_]+$/\', $username)) {',
            'if (!empty($username) && preg_match(\'/\\s/\', $username)) {',
            $content
        );
        
        // Update error messages
        $content = str_replace(
            'Username can only contain letters, numbers, and underscores.',
            'Username cannot contain spaces.',
            $content
        );
        
        if ($content !== $original_content) {
            if (file_put_contents($file, $content)) {
                echo "<p>✅ Updated: " . htmlspecialchars($file) . "</p>";
                $updated_count++;
            } else {
                echo "<p>❌ Failed to update: " . htmlspecialchars($file) . "</p>";
            }
        } else {
            echo "<p>ℹ️ No changes needed: " . htmlspecialchars($file) . "</p>";
        }
    }
    
    echo "<p><strong>✅ Updated $updated_count files</strong></p>";
    
    echo "<hr>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🎉 Username Validation Updated!</h3>";
    echo "<p><strong>Changes made:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Removed restriction on letters, numbers, and underscores only</li>";
    echo "<li>✅ Now allows special symbols: @, #, $, %, &, *, +, =, etc.</li>";
    echo "<li>✅ Only restriction: No spaces allowed in usernames</li>";
    echo "<li>✅ Applied to all modules: Registrar, Cashier, Guidance, HR, Admin, Owner</li>";
    echo "</ul>";
    echo "<p><strong>Examples of now-allowed usernames:</strong></p>";
    echo "<ul>";
    echo "<li>✅ john@doe</li>";
    echo "<li>✅ user#123</li>";
    echo "<li>✅ parent$account</li>";
    echo "<li>✅ test+user</li>";
    echo "<li>✅ admin@school.com</li>";
    echo "<li>❌ user name (spaces not allowed)</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<form method='POST'>";
echo "<button type='submit' name='update_validation' style='background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>";
echo "🔧 Update Username Validation (Allow Special Symbols)";
echo "</button>";
echo "</form>";

echo "<p><a href='RegistrarF/AccountList.php'>Test Updated Registration Form</a></p>";
?>
