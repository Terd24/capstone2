<!DOCTYPE html>
<html>
<head>
    <title>Direct Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8 bg-gray-100">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-4">Direct API Test</h1>
        
        <div class="space-y-4">
            <div>
                <button onclick="test1()" class="bg-blue-600 text-white px-4 py-2 rounded w-full">
                    Test Update Config
                </button>
                <pre id="r1" class="mt-2 bg-gray-100 p-2 rounded text-xs"></pre>
            </div>
            
            <div>
                <button onclick="test2()" class="bg-green-600 text-white px-4 py-2 rounded w-full">
                    Test Backup
                </button>
                <pre id="r2" class="mt-2 bg-gray-100 p-2 rounded text-xs"></pre>
            </div>
            
            <div>
                <button onclick="test3()" class="bg-yellow-600 text-white px-4 py-2 rounded w-full">
                    Test Clear Logs
                </button>
                <pre id="r3" class="mt-2 bg-gray-100 p-2 rounded text-xs"></pre>
            </div>
            
            <div>
                <button onclick="test4()" class="bg-red-600 text-white px-4 py-2 rounded w-full">
                    Test Clear Attendance
                </button>
                <pre id="r4" class="mt-2 bg-gray-100 p-2 rounded text-xs"></pre>
            </div>
        </div>
    </div>

    <script>
        async function test1() {
            const r = document.getElementById('r1');
            r.textContent = 'Testing...';
            try {
                const res = await fetch('update_configuration.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({maintenance_mode: 'enabled'})
                });
                const text = await res.text();
                r.textContent = text;
                try {
                    const json = JSON.parse(text);
                    r.textContent = JSON.stringify(json, null, 2);
                } catch(e) {
                    r.textContent = 'NOT JSON:\n' + text;
                }
            } catch(e) {
                r.textContent = 'ERROR: ' + e.message;
            }
        }

        async function test2() {
            const r = document.getElementById('r2');
            r.textContent = 'Testing...';
            try {
                const res = await fetch('create_backup.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'}
                });
                const text = await res.text();
                try {
                    const json = JSON.parse(text);
                    r.textContent = JSON.stringify(json, null, 2);
                } catch(e) {
                    r.textContent = 'NOT JSON:\n' + text;
                }
            } catch(e) {
                r.textContent = 'ERROR: ' + e.message;
            }
        }

        async function test3() {
            const r = document.getElementById('r3');
            r.textContent = 'Testing...';
            try {
                const res = await fetch('clear_login_logs.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({start_date: '2024-01-01', end_date: '2024-12-31'})
                });
                const text = await res.text();
                try {
                    const json = JSON.parse(text);
                    r.textContent = JSON.stringify(json, null, 2);
                } catch(e) {
                    r.textContent = 'NOT JSON:\n' + text;
                }
            } catch(e) {
                r.textContent = 'ERROR: ' + e.message;
            }
        }

        async function test4() {
            const r = document.getElementById('r4');
            r.textContent = 'Testing...';
            try {
                const res = await fetch('clear_attendance_records.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({start_date: '2024-01-01', end_date: '2024-12-31'})
                });
                const text = await res.text();
                try {
                    const json = JSON.parse(text);
                    r.textContent = JSON.stringify(json, null, 2);
                } catch(e) {
                    r.textContent = 'NOT JSON:\n' + text;
                }
            } catch(e) {
                r.textContent = 'ERROR: ' + e.message;
            }
        }
    </script>
</body>
</html>
