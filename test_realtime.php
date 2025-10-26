<?php
// test_realtime.php - Test page to demonstrate real-time notifications
session_start();

// Only allow registrar access for testing
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'registrar') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Real-time Notifications - Registrar</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">Test Real-time Notifications</h1>

        <div class="space-y-6">
            <div>
                <label for="studentId" class="block text-sm font-medium text-gray-700 mb-2">
                    Student ID Number:
                </label>
                <input type="text" id="studentId" placeholder="Enter student ID (e.g., 2024001)"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                    Notification Message:
                </label>
                <textarea id="message" rows="4" placeholder="Enter notification message..."
                         class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
            </div>

            <button onclick="sendTestNotification()"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                Send Test Notification
            </button>
        </div>

        <div class="mt-8 p-4 bg-blue-50 rounded-lg">
            <h3 class="font-semibold text-blue-800 mb-2">How to Test:</h3>
            <ol class="text-sm text-blue-700 space-y-1">
                <li>1. Enter a student ID (use a real student ID from your database)</li>
                <li>2. Write a test notification message</li>
                <li>3. Click "Send Test Notification"</li>
                <li>4. Open the student dashboard in another tab/window</li>
                <li>5. The notification badge should update within 10 seconds! 🎉</li>
            </ol>
        </div>

        <div id="result" class="mt-6 text-center hidden">
            <div id="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded hidden">
                ✅ Notification sent successfully! Check student dashboard in 10 seconds.
            </div>
            <div id="errorMessage" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded hidden">
                ❌ Error sending notification. Check console for details.
            </div>
        </div>
    </div>

    <script>
        function sendTestNotification() {
            const studentId = document.getElementById('studentId').value.trim();
            const message = document.getElementById('message').value.trim();
            const resultDiv = document.getElementById('result');
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');

            // Hide previous results
            successMessage.classList.add('hidden');
            errorMessage.classList.add('hidden');

            if (!studentId || !message) {
                errorMessage.textContent = '❌ Please fill in both student ID and message.';
                errorMessage.classList.remove('hidden');
                resultDiv.classList.remove('hidden');
                return;
            }

            // Show loading state
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Sending...';
            button.disabled = true;

            // Send notification to database (registrar already does this, but for testing)
            fetch('../StudentLogin/db_conn.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'test_notification': '1',
                    'student_id': studentId,
                    'message': message
                })
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes('Notification inserted successfully')) {
                    successMessage.classList.remove('hidden');
                    document.getElementById('studentId').value = '';
                    document.getElementById('message').value = '';
                } else {
                    errorMessage.textContent = '❌ ' + data;
                    errorMessage.classList.remove('hidden');
                }
                resultDiv.classList.remove('hidden');
            })
            .catch(error => {
                console.error('Error:', error);
                errorMessage.textContent = '❌ Network error. Check console for details.';
                errorMessage.classList.remove('hidden');
                resultDiv.classList.remove('hidden');
            })
            .finally(() => {
                button.textContent = originalText;
                button.disabled = false;
            });
        }

        // Allow Enter key to submit
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('studentId').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendTestNotification();
                }
            });
            document.getElementById('message').addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendTestNotification();
                }
            });
        });
    </script>
</body>
</html>
