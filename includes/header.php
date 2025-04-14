<?php
// Get current page name
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-50">
    <header class="bg-white shadow-sm">
        <nav class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <a href="index.php" class="flex items-center space-x-2">
                    <span class="text-2xl font-bold text-[#ff6b00]"><?php echo APP_NAME; ?></span>
                </a>
                
                <div class="flex items-center space-x-6">
                    <a href="index.php" class="nav-link <?php echo $current_page === 'index' ? 'active' : ''; ?>">
                        <i class="fas fa-home mr-1"></i> Home
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="add-recipe.php" class="nav-link <?php echo $current_page === 'add-recipe' ? 'active' : ''; ?>">
                            <i class="fas fa-plus mr-1"></i> Add Recipe
                        </a>
                        <a href="my-recipes.php" class="nav-link <?php echo $current_page === 'my-recipes' ? 'active' : ''; ?>">
                            <i class="fas fa-book mr-1"></i> My Recipes
                        </a>
                        <div class="relative group">
                            <button class="nav-link flex items-center">
                                <i class="fas fa-user mr-1"></i> Account
                                <i class="fas fa-chevron-down ml-1 text-sm"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden group-hover:block">
                                <a href="profile.php" class="block px-4 py-2 hover:bg-[#fff0e6] text-gray-700 hover:text-[#ff6b00]">
                                    <i class="fas fa-user-circle mr-1"></i> Profile
                                </a>
                                <a href="logout.php" class="block px-4 py-2 hover:bg-[#fff0e6] text-gray-700 hover:text-[#ff6b00]">
                                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn-secondary">Login</a>
                        <a href="register.php" class="btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <style>
        .nav-link {
            position: relative;
            color: #4B5563;
            padding: 0.5rem 0;
            transition: color 0.3s;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background: #ff6b00;
            transition: width 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: #ff6b00;
        }

        .nav-link:hover::after, .nav-link.active::after {
            width: 100%;
        }
    </style>
</body>
</html>