<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Department Head Login Issue</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #0B2C62; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        button { background: #0B2C62; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        button:hover { background: #083a7e; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        input[type="text"] { padding: 10px; width: 300px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Department Head Login Issue</h1>
        
        <?php
        session_start();
        include("../StudentLogin/db_conn.php");
        
        $step = $_GET['step'] ?? 'start';
        
        if ($step === 'start') {
            ?>
            <div class="info">
                <strong>Problem:</strong> Department Head login redirects to Employee Portal instead of Department Head Dashboard.
            </div>
            
            <h2>Automatic Fix</h2>
            <p>This will:</p>
            <ol>
                <li>Update the database ENUM to include 'department_head' role</li>
                <li>Check if you have any existing Department Head accounts</li>
                <li>Fix the role if needed</li>
            </ol>
            
            <form method="GET">
                <input type="hidden" name="step" value="fix_database">
                <button type="submit">🚀 Start Automatic Fix</button>
            </form>
            
            <hr>
            
            <h2>Manual Fix</h2>
            <p>If you know the username of the Department Head account:</p>
            <form method="GET">
                <input type="hidden" name="step" value="fix_account">
                <input type="text" name="username" placeholder="Enter username" required>
                <button type="submit">Fix Specific Account</button>
            </form>
            <?php
        } elseif ($step === 'fix_database') {
            ?>
            <h2>Step 1: Update Database ENUM</h2>
            <?php
            try {
                // Check if table exists
                $table_check = $conn->query("SHOW TABLES LIKE 'employee_accounts'");
                
                if ($table_check->num_rows == 0) {
                    echo '<div class="warning">⚠️ employee_accounts table does not exist yet. It will be created when you add the first employee.</div>';
                } else {
                    // Check current ENUM
                    $roleColumn = $conn->query("SHOW COLUMNS FROM employee_accounts LIKE 'role'")->fetch_assoc();
                    
                    if (strpos($roleColumn['Type'], "'department_head'") !== false) {
                        echo '<div class="success">✅ Database ENUM already includes department_head!</div>';
                    } else {
                        // Update ENUM
                        $update_sql = "ALTER TABLE employee_accounts MODIFY role ENUM('registrar','cashier','guidance','attendance','hr','teacher','department_head') NOT NULL";
                        
                        if ($conn->query($update_sql)) {
                            echo '<div class="success">✅ Database ENUM updated successfully!</div>';
                        } else {
                            echo '<div class="error">❌ Failed to update database: ' . $conn->error . '</div>';
                        }
                    }
                    
                    // Show current ENUM
                    $roleColumn = $conn->query("SHOW COLUMNS FROM employee_accounts LIKE 'role'")->fetch_assoc();
                    echo '<p><strong>Current ENUM:</strong></p>';
                    echo '<pre>' . htmlspecialchars($roleColumn['Type']) . '</pre>';
                }
                
                // Check for accounts that might need fixing
                echo '<h2>Step 2: Check Existing Accounts</h2>';
                $accounts = $conn->query("SELECT username, role, employee_id FROM employee_accounts WHERE role != 'department_head'");
                
                if ($accounts && $accounts->num_rows > 0) {
                    echo '<p>Found ' . $accounts->num_rows . ' account(s) that are NOT department_head:</p>';
                    echo '<table border="1" cellpadding="10" style="width:100%; border-collapse: collapse;">';
                    echo '<tr><th>Username</th><th>Current Role</th><th>Action</th></tr>';
                    
                    while ($acc = $accounts->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($acc['username']) . '</td>';
                        echo '<td>' . htmlspecialchars($acc['role']) . '</td>';
                        echo '<td><a href="?step=fix_account&username=' . urlencode($acc['username']) . '"><button type="button">Fix This Account</button></a></td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<div class="info">No accounts found that need fixing.</div>';
                }
                
                // Check for department_head accounts
                $dept_heads = $conn->query("SELECT username, employee_id FROM employee_accounts WHERE role = 'department_head'");
                if ($dept_heads && $dept_heads->num_rows > 0) {
                    echo '<h3>Existing Department Head Accounts:</h3>';
                    echo '<div class="success">';
                    while ($dh = $dept_heads->fetch_assoc()) {
                        echo '✅ ' . htmlspecialchars($dh['username']) . ' (Employee ID: ' . htmlspecialchars($dh['employee_id']) . ')<br>';
                    }
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
            }
            ?>
            
            <hr>
            <h2>Next Steps</h2>
            <ol>
                <li>If you see a Department Head account above, try logging in</li>
                <li>If login still fails, use the "Fix Specific Account" option</li>
                <li>Clear browser cache before testing</li>
            </ol>
            
            <a href="../admin_login.php"><button>Go to Login Page</button></a>
            <a href="?step=start"><button class="btn-danger">Start Over</button></a>
            <?php
        } elseif ($step === 'fix_account') {
            $username = $_GET['username'] ?? '';
            
            if (empty($username)) {
                echo '<div class="error">❌ Username is required</div>';
                echo '<a href="?step=start"><button>Go Back</button></a>';
            } else {
                ?>
                <h2>Fix Account: <?= htmlspecialchars($username) ?></h2>
                <?php
                
                // Check if account exists
                $stmt = $conn->prepare("SELECT * FROM employee_accounts WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    echo '<div class="error">❌ Account not found: ' . htmlspecialchars($username) . '</div>';
                } else {
                    $account = $result->fetch_assoc();
                    
                    echo '<p><strong>Current Role:</strong> ' . htmlspecialchars($account['role']) . '</p>';
                    
                    if ($account['role'] === 'department_head') {
                        echo '<div class="success">✅ This account already has the correct role!</div>';
                    } else {
                        // Update the role
                        $update = $conn->prepare("UPDATE employee_accounts SET role = 'department_head' WHERE username = ?");
                        $update->bind_param("s", $username);
                        
                        if ($update->execute()) {
                            echo '<div class="success">✅ Role updated successfully!</div>';
                            echo '<p><strong>Old Role:</strong> ' . htmlspecialchars($account['role']) . '</p>';
                            echo '<p><strong>New Role:</strong> department_head</p>';
                        } else {
                            echo '<div class="error">❌ Failed to update role: ' . $conn->error . '</div>';
                        }
                    }
                }
                ?>
                
                <hr>
                <h2>Test Login</h2>
                <ol>
                    <li>Clear browser cache (Ctrl+Shift+Delete)</li>
                    <li>Go to login page</li>
                    <li>Login with username: <strong><?= htmlspecialchars($username) ?></strong></li>
                    <li>You should be redirected to Department Head Dashboard</li>
                </ol>
                
                <a href="../admin_login.php"><button>Go to Login Page</button></a>
                <a href="?step=start"><button class="btn-danger">Start Over</button></a>
                <?php
            }
        }
        ?>
    </div>
</body>
</html>
