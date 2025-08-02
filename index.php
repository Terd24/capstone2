<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">
  <form action="login.php" method="POST" class="bg-white p-8 rounded shadow-md w-96">
    <h2 class="text-2xl font-bold mb-6 text-center">Student Login</h2>
    
    <input type="text" name="id_number" placeholder="ID Number"
      class="w-full mb-4 p-2 border border-gray-300 rounded" required />
    
    <input type="password" name="password" placeholder="Password"
      class="w-full mb-4 p-2 border border-gray-300 rounded" required />
    
    <button type="submit"
      class="w-full bg-black text-white p-2 rounded hover:bg-gray-800">Login</button>
  </form>
</body>
</html>
