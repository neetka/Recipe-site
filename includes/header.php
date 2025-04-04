<header class="bg-white shadow">
    <div class="container mx-auto px-4 py-4 flex justify-between items-center">
        <a href="index.php" class="text-2xl font-bold text-blue-600"><?php echo APP_NAME; ?></a>
        
        <nav class="hidden md:flex space-x-6">
            <a href="index.php" class="text-gray-700 hover:text-blue-600">Home</a>
            <a href="add-recipe.php" class="text-gray-700 hover:text-blue-600">Add Recipe</a>
            <a href="#" class="text-gray-700 hover:text-blue-600">Categories</a>
            <a href="#" class="text-gray-700 hover:text-blue-600">About</a>
        </nav>
        
        <div class="flex items-center space-x-4">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="text-gray-700 hover:text-blue-600">Logout</a>
            <?php else: ?>
                <a href="login.php" class="text-gray-700 hover:text-blue-600">Login</a>
                <a href="register.php" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>