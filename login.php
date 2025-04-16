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

<div class="min-h-screen flex bg-[#FFF8F3]">
    <!-- Image Slideshow Section -->
    <div class="hidden md:block w-1/2 relative overflow-hidden rounded-r-[3rem] shadow-2xl">
        <div class="absolute inset-0 bg-gradient-to-b from-black/10 via-black/20 to-black/60 z-10"></div>
        <div id="imageSlideshow" class="absolute inset-0 transition-all duration-1000">
            <!-- Images will be added here -->
        </div>
        <div class="absolute bottom-0 left-0 right-0 p-12 text-white z-20 transform translate-y-3 opacity-0 transition-all duration-700" id="slideCaption">
            <h2 class="text-5xl font-bold mb-4 leading-tight">Welcome to<br>RecipeShare</h2>
            <p class="text-xl font-light opacity-90">Share your culinary masterpieces with the world</p>
        </div>
    </div>

    <!-- Login Form Section -->
    <div class="w-full md:w-1/2 flex items-center justify-center p-8">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-3xl shadow-xl p-10 transform hover:scale-[1.01] transition-all duration-300">
                <div class="text-center mb-10">
                    <h2 class="text-4xl font-bold text-gray-800 mb-3">Welcome Back!</h2>
                    <p class="text-gray-600">Sign in to continue your culinary journey</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 text-red-500 p-4 rounded-xl mb-6 animate-shake">
                        <ul class="list-disc pl-5">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="login.php" class="space-y-6">
                    <div class="space-y-4">
                        <div class="relative group">
                            <label class="block text-sm font-medium text-gray-700 mb-2 transition-colors group-focus-within:text-[#ff6b00]">
                                Email or Username
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 transition-colors group-focus-within:text-[#ff6b00]">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" name="username" required 
                                    class="pl-12 w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-[#ff6b00] focus:border-transparent transition-all duration-300 outline-none">
                            </div>
                        </div>

                        <div class="relative group">
                            <label class="block text-sm font-medium text-gray-700 mb-2 transition-colors group-focus-within:text-[#ff6b00]">
                                Password
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 transition-colors group-focus-within:text-[#ff6b00]">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" name="password" required 
                                    class="pl-12 w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-[#ff6b00] focus:border-transparent transition-all duration-300 outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mt-6">
                        <div class="flex items-center">
                            <input type="checkbox" name="remember" id="remember" 
                                class="h-4 w-4 text-[#ff6b00] focus:ring-[#ff6b00] border-gray-300 rounded transition-all duration-300">
                            <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="text-sm font-medium text-[#ff6b00] hover:text-[#ff8533] transition-colors duration-300">
                            Forgot password?
                        </a>
                    </div>

                    <div class="mt-4">
                        <a href="forgot-password.php" class="btn-link text-sm">
                            Forgot password?
                        </a>
                    </div>

                    <button type="submit" 
                            class="w-full flex items-center justify-center gap-2 px-6 py-3.5 
                                   bg-gradient-to-r from-[#ff6b00] to-[#ff8533]
                                   text-white text-lg font-semibold rounded-xl
                                   transform transition-all duration-300
                                   hover:scale-[1.02] hover:shadow-lg hover:from-[#ff8533] hover:to-[#ff6b00]
                                   focus:outline-none focus:ring-2 focus:ring-[#ff6b00] focus:ring-offset-2
                                   active:scale-[0.98]">
                        <i class="fas fa-sign-in-alt text-xl"></i>
                        <span>Sign In</span>
                    </button>

                    <div class="text-center text-sm text-gray-600 mt-6">
                        Don't have an account? 
                        <a href="register.php" class="btn-link">
                            Create one
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .animate-shake {
        animation: shake 0.5s ease-in-out;
    }

    @keyframes kenBurns {
        0% {
            transform: scale(1) translate(0, 0);
        }
        50% {
            transform: scale(1.1) translate(-1%, -1%);
        }
        100% {
            transform: scale(1) translate(0, 0);
        }
    }

    #imageSlideshow {
        animation: kenBurns 30s ease-in-out infinite;
        background-size: cover;
        background-position: center;
    }

    .group:focus-within label {
        color: #ff6b00;
    }

    input:focus::placeholder {
        color: #ff6b00;
        opacity: 0.7;
    }

    /* Elegant scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: #ff6b00;
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: #ff8533;
    }
</style>

<script>
    const images = [
        'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', // Colorful healthy breakfast
        'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', // Vegetable salad
        'https://images.unsplash.com/photo-1504674900247-0877df9cc836?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', // Grilled steak
        'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', // Pasta dish
        'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', // Homemade burger
        'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', // Fresh smoothie bowl
        'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', // Perfect pizza
        'https://images.unsplash.com/photo-1563805042-7684c019e1cb?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', // Sushi platter
        'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', // Fresh pancakes
        'https://images.unsplash.com/photo-1551782450-a2132b4ba21d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'  // Gourmet burger
    ];

    let currentImageIndex = 0;
    const slideshow = document.getElementById('imageSlideshow');
    const caption = document.getElementById('slideCaption');
    const fadeTime = 1000;

    function showCaption() {
        caption.style.transform = 'translateY(0)';
        caption.style.opacity = '1';
    }

    function hideCaption() {
        caption.style.transform = 'translateY(3rem)';
        caption.style.opacity = '0';
    }

    function fadeOut(element, callback) {
        element.style.opacity = 1;
        let opacity = 1;
        hideCaption();
        
        const timer = setInterval(() => {
            opacity -= 0.1;
            element.style.opacity = opacity;
            if (opacity <= 0) {
                clearInterval(timer);
                callback();
            }
        }, fadeTime / 10);
    }

    function fadeIn(element) {
        element.style.opacity = 0;
        let opacity = 0;
        
        const timer = setInterval(() => {
            opacity += 0.1;
            element.style.opacity = opacity;
            if (opacity >= 1) {
                clearInterval(timer);
                showCaption();
            }
        }, fadeTime / 10);
    }

    function changeImage() {
        fadeOut(slideshow, () => {
            currentImageIndex = (currentImageIndex + 1) % images.length;
            slideshow.style.backgroundImage = `url('${images[currentImageIndex]}')`;
            fadeIn(slideshow);
        });
    }

    // Set initial image and show caption
    slideshow.style.backgroundImage = `url('${images[0]}')`;
    slideshow.style.opacity = 1;
    setTimeout(showCaption, 500);

    // Change image every 7 seconds
    setInterval(changeImage, 7000);

    // Add hover effect to form inputs
    const inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.parentElement.classList.add('focused');
        });
        input.addEventListener('blur', () => {
            input.parentElement.parentElement.classList.remove('focused');
        });
    });
</script>

<?php include 'includes/footer.php'; ?>