<!DOCTYPE html>
<html>
<head>
    <title>Debug Restore Functionality</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .test-btn { background: #2F8D46; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 5px; }
        .result { margin: 10px 0; padding: 10px; border: 1px solid #ccc; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
    </style>
</head>
<body>
    <h1>Restore Functionality Debug</h1>
    
    <h2>Test Restore Student</h2>
    <input type="text" id="studentId" placeholder="Enter Student ID" value="02000000002">
    <button class="test-btn" onclick="testRestoreStudent()">Test Restore Student</button>
    
    <h2>Test Restore Employee</h2>
    <input type="text" id="employeeId" placeholder="Enter Employee ID" value="710921243478">
    <button class="test-btn" onclick="testRestoreEmployee()">Test Restore Employee</button>
    
    <h2>Results:</h2>
    <div id="results"></div>
    
    <script>
        function addResult(message, isSuccess) {
            const div = document.createElement('div');
            div.className = 'result ' + (isSuccess ? 'success' : 'error');
            div.textContent = new Date().toLocaleTimeString() + ': ' + message;
            document.getElementById('results').prepend(div);
        }
        
        function testRestoreStudent() {
            const studentId = document.getElementById('studentId').value;
            if (!studentId) {
                alert('Please enter a student ID');
                return;
            }
            
            addResult('Testing restore for student: ' + studentId, true);
            
            fetch('restore_record.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'restore',
                    record_type: 'student',
                    record_id: studentId
                })
            })
            .then(response => {
                addResult('Response status: ' + response.status, true);
                return response.json();
            })
            .then(data => {
                addResult('Response: ' + JSON.stringify(data), data.success);
                if (data.success) {
                    alert('SUCCESS: ' + data.message);
                } else {
                    alert('FAILED: ' + data.message);
                }
            })
            .catch(error => {
                addResult('Error: ' + error.message, false);
                alert('ERROR: ' + error.message);
            });
        }
        
        function testRestoreEmployee() {
            const employeeId = document.getElementById('employeeId').value;
            if (!employeeId) {
                alert('Please enter an employee ID');
                return;
            }
            
            addResult('Testing restore for employee: ' + employeeId, true);
            
            fetch('restore_record.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'restore',
                    record_type: 'employee',
                    record_id: employeeId
                })
            })
            .then(response => {
                addResult('Response status: ' + response.status, true);
                return response.json();
            })
            .then(data => {
                addResult('Response: ' + JSON.stringify(data), data.success);
                if (data.success) {
                    alert('SUCCESS: ' + data.message);
                } else {
                    alert('FAILED: ' + data.message);
                }
            })
            .catch(error => {
                addResult('Error: ' + error.message, false);
                alert('ERROR: ' + error.message);
            });
        }
    </script>
</body>
</html>
