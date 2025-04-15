<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $errors[] = "Please enter both username and password";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                if ($remember) {
                    // Set remember me cookie for 30 days
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + (30 * 24 * 60 * 60);
                    
                    try {
                        $stmt = $conn->prepare("UPDATE users SET remember_token = ?, remember_token_expiry = ? WHERE id = ?");
                        $stmt->execute([$token, date('Y-m-d H:i:s', $expiry), $user['id']]);
                        
                        setcookie('remember_token', $token, $expiry, '/', '', true, true);
                    } catch (PDOException $e) {
                        error_log("Error setting remember token: " . $e->getMessage());
                    }
                }
                
                // Redirect to the page they were trying to access or home page
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                header("Location: " . $redirect);
                exit();
            } else {
                $errors[] = "Invalid username/email or password";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = "An error occurred during login. Please try again.";
        }
    }
}

$page_title = "Login";
include 'includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-orange-50 to-orange-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Welcome Back!</h2>
            <p class="text-gray-600">Sign in to continue cooking</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6">
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email or Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" name="username" required 
                           class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" required 
                           class="pl-10 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" 
                           class="h-4 w-4 text-[#ff6b00] focus:ring-orange-500 border-gray-300 rounded">
                    <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
                </div>
                <a href="forgot-password.php" class="text-sm font-medium text-[#ff6b00] hover:text-[#ff8533]">
                    Forgot password?
                </a>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-[#ff6b00] to-[#ff8533] text-white py-2 px-4 rounded-lg hover:opacity-90 transition duration-200 flex items-center justify-center gap-2">
                <i class="fas fa-sign-in-alt"></i>
                <span>Sign In</span>
            </button>

            <div class="text-center text-sm text-gray-600">
                Don't have an account? 
                <a href="register.php" class="font-medium text-[#ff6b00] hover:text-[#ff8533]">Create one</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>