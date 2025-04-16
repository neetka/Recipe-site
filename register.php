<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Initialize variables
$errors = [];
$form_data = [
    'username' => '',
    'email' => ''
];

// Validation constants
define('USERNAME_MIN_LENGTH', 3);
define('USERNAME_MAX_LENGTH', 20);
define('PASSWORD_MIN_LENGTH', 8);

// Validation patterns
define('USERNAME_PATTERN', '/^[a-zA-Z0-9_-]{3,20}$/');
define('PASSWORD_PATTERN', '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/');
define('EMAIL_PATTERN', '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Store form data for re-populating the form
    $form_data['username'] = $username;
    $form_data['email'] = $email;

    // Username validation
    if (empty($username)) {
        $errors['username'] = "Username is required";
    } elseif (!preg_match(USERNAME_PATTERN, $username)) {
        $errors['username'] = "Username must be 3-20 characters long and can only contain letters, numbers, underscores, and hyphens";
    }

    // Email validation
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!preg_match(EMAIL_PATTERN, $email)) {
        $errors['email'] = "Please enter a valid email address";
    }

    // Password validation
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (!preg_match(PASSWORD_PATTERN, $password)) {
        $errors['password'] = "Password must be at least 8 characters long and include: uppercase letter, lowercase letter, number, and special character";
    }

    // Confirm password validation
    if (empty($confirm_password)) {
        $errors['confirm_password'] = "Please confirm your password";
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match";
    }

    // If no validation errors, check for existing username/email
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_user) {
                if ($existing_user['username'] === $username) {
                    $errors['username'] = "This username is already taken";
                }
                if ($existing_user['email'] === $email) {
                    $errors['email'] = "This email is already registered";
                }
            } else {
                // Proceed with registration
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
                
                if ($stmt->execute([$username, $email, $hashed_password])) {
                    $_SESSION['success'] = "Registration successful! Please login.";
                    header("Location: login.php");
                    exit();
                } else {
                    $errors['general'] = "Registration failed. Please try again.";
                }
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors['general'] = "An error occurred during registration. Please try again.";
        }
    }
}

$page_title = "Register";
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
            <h2 class="text-5xl font-bold mb-4 leading-tight">Join Our<br>Culinary Community</h2>
            <p class="text-xl font-light opacity-90">Start sharing your amazing recipes today</p>
        </div>
    </div>

    <!-- Registration Form Section -->
    <div class="w-full md:w-1/2 flex items-center justify-center p-8">
        <div class="max-w-md w-full">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">Create Account</h2>
                    <p class="text-gray-600">Join our cooking community today!</p>
                </div>

                <?php if (isset($errors['general'])): ?>
                    <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6 animate-shake">
                        <p><?php echo $errors['general']; ?></p>
                    </div>
                <?php endif; ?>

                <form method="post" action="register.php" class="space-y-6">
                    <div class="space-y-4">
                        <div class="relative group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Username
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 transition-colors group-focus-within:text-[#ff6b00]">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" name="username" 
                                    value="<?php echo htmlspecialchars($form_data['username']); ?>"
                                    class="pl-10 w-full px-4 py-2 border <?php echo isset($errors['username']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-[#ff6b00] focus:border-transparent transition-all duration-300"
                                    placeholder="Choose a unique username">
                            </div>
                            <?php if (isset($errors['username'])): ?>
                                <p class="mt-1 text-sm text-red-500"><?php echo $errors['username']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="relative group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Email
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 transition-colors group-focus-within:text-[#ff6b00]">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" name="email" 
                                    value="<?php echo htmlspecialchars($form_data['email']); ?>"
                                    class="pl-10 w-full px-4 py-2 border <?php echo isset($errors['email']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-[#ff6b00] focus:border-transparent transition-all duration-300"
                                    placeholder="Enter your email address">
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <p class="mt-1 text-sm text-red-500"><?php echo $errors['email']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="relative group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Password
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 transition-colors group-focus-within:text-[#ff6b00]">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" name="password" 
                                    class="pl-10 w-full px-4 py-2 border <?php echo isset($errors['password']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-[#ff6b00] focus:border-transparent transition-all duration-300"
                                    placeholder="Create a strong password">
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <p class="mt-1 text-sm text-red-500"><?php echo $errors['password']; ?></p>
                            <?php endif; ?>
                            <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters with 1 uppercase, 1 lowercase, 1 number, and 1 special character</p>
                        </div>

                        <div class="relative group">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Confirm Password
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 transition-colors group-focus-within:text-[#ff6b00]">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" name="confirm_password" 
                                    class="pl-10 w-full px-4 py-2 border <?php echo isset($errors['confirm_password']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-[#ff6b00] focus:border-transparent transition-all duration-300"
                                    placeholder="Confirm your password">
                            </div>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <p class="mt-1 text-sm text-red-500"><?php echo $errors['confirm_password']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" 
                            class="w-full flex items-center justify-center gap-2 px-6 py-3.5 
                                   bg-gradient-to-r from-[#ff6b00] to-[#ff8533]
                                   text-white text-lg font-semibold rounded-xl
                                   transform transition-all duration-300
                                   hover:scale-[1.02] hover:shadow-lg hover:from-[#ff8533] hover:to-[#ff6b00]
                                   focus:outline-none focus:ring-2 focus:ring-[#ff6b00] focus:ring-offset-2
                                   active:scale-[0.98]">
                        <i class="fas fa-user-plus text-xl"></i>
                        <span>Create Account</span>
                    </button>

                    <div class="text-center text-sm text-gray-600 mt-6">
                        Already have an account? 
                        <a href="login.php" class="text-[#ff6b00] hover:text-[#ff8533] font-medium transition-colors duration-300">
                            Sign in
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
        'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', // Cooking preparation
        'https://images.unsplash.com/photo-1495521821757-a1efb6729352?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', // Ingredient spread
        'https://images.unsplash.com/photo-1516824711718-9c1e683412ac?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', // Baking
        'https://images.unsplash.com/photo-1551218808-94e220e084d2?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80', // Kitchen scene
        'https://images.unsplash.com/photo-1507048331197-7d4ac70811cf?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'  // Recipe planning
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
    const inputs = document.querySelectorAll('input');
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