<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Under Maintenance - Cornerstone College Inc.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12 max-w-2xl w-full text-center">
        <!-- Logo -->
        <div class="flex justify-center mb-6">
            <div class="w-32 h-32 bg-blue-100 rounded-full flex items-center justify-center">
                <img src="images/LogoCCI.png" alt="Cornerstone College Inc." class="w-24 h-24 rounded-full">
            </div>
        </div>

        <!-- Warning Icon -->
        <div class="flex justify-center mb-6">
            <div class="w-24 h-24 bg-yellow-100 rounded-full flex items-center justify-center">
                <svg class="w-16 h-16 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>

        <!-- Title -->
        <h1 class="text-4xl font-bold text-gray-900 mb-4">System Under Maintenance</h1>
        
        <!-- Message -->
        <p class="text-lg text-gray-600 mb-8">
            We're currently performing system maintenance to improve your experience.
        </p>

        <!-- Info Box -->
        <div class="bg-blue-50 rounded-2xl p-6 mb-8">
            <h2 class="text-xl font-semibold text-blue-900 mb-2">Cornerstone College Inc.</h2>
            <p class="text-blue-700">
                The system will be back online shortly. Please try again later.
            </p>
        </div>

        <!-- Contact Info -->
        <p class="text-gray-500 text-sm">
            If you need immediate assistance, please contact the IT department.
        </p>

        <!-- Admin Login Link -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <a href="admin_login.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Admin Login →
            </a>
        </div>
    </div>
</body>
</html>
