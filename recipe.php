<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$recipe_id = intval($_GET['id']);

// Get recipe details
try {
    $sql = "SELECT r.*, u.username, u.profile_picture 
            FROM recipes r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$recipe_id]);
    $recipe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipe) {
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching recipe: " . $e->getMessage());
    die("An error occurred while fetching the recipe.");
}

// Get reviews
try {
    $sql = "SELECT rev.*, u.username 
            FROM reviews rev 
            JOIN users u ON rev.user_id = u.id 
            WHERE rev.recipe_id = ? 
            ORDER BY rev.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$recipe_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching reviews: " . $e->getMessage());
    $reviews = [];
}

// Calculate average rating
$avg_rating = 0;
if (count($reviews) > 0) {
    $total = array_sum(array_column($reviews, 'rating'));
    $avg_rating = $total / count($reviews);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($recipe['title']); ?> | <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body class="bg-gradient-to-br from-orange-50 to-orange-100 min-h-screen">
    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-xl overflow-hidden max-w-6xl mx-auto">
            <!-- Recipe Header -->
            <div class="relative">
                <?php if (!empty($recipe['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($recipe['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($recipe['title']); ?>" 
                         class="w-full h-[500px] object-cover animate__animated animate__fadeIn"
                         onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-[500px] bg-orange-100 flex items-center justify-center\'><i class=\'fas fa-utensils text-8xl text-orange-400\'></i></div>';">
                <?php else: ?>
                    <div class="w-full h-[500px] bg-orange-100 flex items-center justify-center">
                        <i class="fas fa-utensils text-8xl text-orange-400"></i>
                    </div>
                <?php endif; ?>
                
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-8">
                    <h1 class="text-4xl font-bold text-white mb-2 animate__animated animate__fadeInUp">
                        <?php echo htmlspecialchars($recipe['title']); ?>
                    </h1>
                    <div class="flex items-center space-x-4 text-white animate__animated animate__fadeInUp animate__delay-1s">
                        <div class="flex items-center">
                            <div class="flex text-yellow-400">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?php echo ($i <= round($avg_rating)) ? '' : '-empty'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="ml-2">
                                <?php echo number_format($avg_rating, 1); ?> (<?php echo count($reviews); ?> reviews)
                            </span>
                        </div>
                        <span>•</span>
                        <span><?php echo getDifficultyEmoji($recipe['difficulty']); ?> <?php echo $recipe['difficulty']; ?></span>
                        <span>•</span>
                        <span><?php echo getCuisineEmoji($recipe['cuisine_type']); ?> <?php echo $recipe['cuisine_type']; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Recipe Meta -->
            <div class="p-6 border-b bg-orange-50">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white p-4 rounded-lg text-center transform hover:scale-105 transition">
                        <div class="text-orange-500 text-2xl mb-1"><i class="fas fa-clock"></i></div>
                        <div class="text-gray-500">Prep Time</div>
                        <div class="font-bold text-gray-800"><?php echo $recipe['prep_time']; ?> mins</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg text-center transform hover:scale-105 transition">
                        <div class="text-orange-500 text-2xl mb-1"><i class="fas fa-fire"></i></div>
                        <div class="text-gray-500">Cook Time</div>
                        <div class="font-bold text-gray-800"><?php echo $recipe['cook_time']; ?> mins</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg text-center transform hover:scale-105 transition">
                        <div class="text-orange-500 text-2xl mb-1"><i class="fas fa-chart-line"></i></div>
                        <div class="text-gray-500">Difficulty</div>
                        <div class="font-bold text-gray-800"><?php echo getDifficultyEmoji($recipe['difficulty']); ?> <?php echo $recipe['difficulty']; ?></div>
                    </div>
                    <div class="bg-white p-4 rounded-lg text-center transform hover:scale-105 transition">
                        <div class="text-orange-500 text-2xl mb-1"><i class="fas fa-users"></i></div>
                        <div class="text-gray-500">Servings</div>
                        <div class="font-bold text-gray-800"><?php echo $recipe['servings']; ?> people</div>
                    </div>
                </div>
            </div>
            
            <!-- Recipe Content -->
            <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column -->
                <div class="lg:col-span-2">
                    <div class="mb-8 animate__animated animate__fadeIn">
                        <h2 class="text-2xl font-bold mb-4 text-gray-800">
                            <i class="fas fa-info-circle text-orange-500 mr-2"></i> Description
                        </h2>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($recipe['description'])); ?></p>
                    </div>
                    
                    <div class="mb-8 animate__animated animate__fadeIn animate__delay-1s">
                        <h2 class="text-2xl font-bold mb-4 text-gray-800">
                            <i class="fas fa-list text-orange-500 mr-2"></i> Ingredients
                        </h2>
                        <div class="bg-orange-50 p-6 rounded-lg">
                            <ul class="space-y-3">
                                <?php 
                                $ingredients = explode("\n", $recipe['ingredients']);
                                foreach ($ingredients as $ingredient): 
                                    if (trim($ingredient)): 
                                ?>
                                    <li class="flex items-center">
                                        <i class="fas fa-check text-orange-500 mr-3"></i>
                                        <span><?php echo htmlspecialchars(trim($ingredient)); ?></span>
                                    </li>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mb-8 animate__animated animate__fadeIn animate__delay-2s">
                        <h2 class="text-2xl font-bold mb-4 text-gray-800">
                            <i class="fas fa-tasks text-orange-500 mr-2"></i> Instructions
                        </h2>
                        <div class="space-y-6">
                            <?php 
                            $instructions = explode("\n", $recipe['instructions']);
                            $step = 1;
                            foreach ($instructions as $instruction): 
                                if (trim($instruction)): 
                            ?>
                                <div class="flex items-start bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition">
                                    <div class="bg-orange-500 text-white rounded-full w-8 h-8 flex items-center justify-center flex-shrink-0 mr-4">
                                        <?php echo $step++; ?>
                                    </div>
                                    <p class="text-gray-700"><?php echo htmlspecialchars(trim($instruction)); ?></p>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <div class="bg-white p-6 rounded-xl shadow-lg sticky top-4 animate__animated animate__fadeInRight">
                        <div class="mb-6">
                            <h3 class="font-bold text-lg mb-4 text-gray-800">
                           Recipe By
                            </h3>
                            <div class="flex items-center bg-orange-50 p-4 rounded-lg">
                                <?php if (!empty($recipe['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($recipe['profile_picture']); ?>" 
                                         alt="<?php echo htmlspecialchars($recipe['username']); ?>"
                                         class="w-12 h-12 rounded-full object-cover border-2 border-orange-500 mr-4">
                                <?php else: ?>
                                    <div class="w-12 h-12 rounded-full bg-orange-500 flex items-center justify-center text-white font-bold text-xl mr-4">
                                        <?php echo strtoupper(substr($recipe['username'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($recipe['username']); ?></div>
                                    <div class="text-sm text-gray-500">Master Chef 👨‍🍳</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <h3 class="font-bold text-lg mb-2 text-gray-800">Fun Cooking Tip</h3>
                            <div class="bg-orange-50 p-4 rounded-lg text-gray-700" id="cooking-tip">
                                Loading tip... 🤔
                            </div>
                        </div>
                        
                        <a href="add-recipe.php" class="block w-full bg-orange-500 text-white text-center py-3 px-4 rounded-lg hover:bg-orange-600 transition transform hover:scale-105">
                            <i class="fas fa-plus-circle mr-2"></i> Share Your Recipe
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Reviews Section -->
            <div class="p-6 border-t bg-orange-50">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">
                    <i class="fas fa-comments text-orange-500 mr-2"></i> Reviews
                </h2>
                
                <?php if (empty($reviews)): ?>
                    <div class="text-center py-8">
                        <div class="text-6xl mb-4">🤔</div>
                        <p class="text-gray-500">No reviews yet. Be the first to share your thoughts!</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($reviews as $review): ?>
                            <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-orange-500 flex items-center justify-center text-white mr-3">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($review['username']); ?></span>
                                                <div class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex text-yellow-400">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo ($i <= $review['rating']) ? '' : '-empty'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Add Review Form -->
                <div class="mt-8">
                    <h3 class="text-xl font-bold mb-4 text-gray-800">
                        <i class="fas fa-star text-orange-500 mr-2"></i> Add Your Review
                    </h3>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form action="submit_review.php" method="post" class="bg-white p-6 rounded-lg shadow-sm">
                            <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Rating*</label>
                                <div class="flex space-x-4">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <label class="cursor-pointer transform hover:scale-110 transition">
                                            <input type="radio" name="rating" value="<?= $i ?>" class="hidden" required>
                                            <div class="text-3xl rating-star" data-rating="<?= $i ?>">⭐</div>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Your Review*</label>
                                <textarea name="comment" rows="4" required 
                                    class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                    placeholder="Share your cooking experience..."></textarea>
                            </div>
                            
                            <button type="submit" class="w-full bg-orange-500 text-white py-3 px-4 rounded-lg hover:bg-orange-600 transition transform hover:scale-105">
                                <i class="fas fa-paper-plane mr-2"></i> Submit Review
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-lg">
                            <p>Please <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI'])?>" 
                                class="text-orange-500 hover:underline font-medium">login</a> to submit a review.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Star rating interaction
        const ratingStars = document.querySelectorAll('.rating-star');
        ratingStars.forEach(star => {
            star.addEventListener('mouseover', () => {
                const rating = star.dataset.rating;
                ratingStars.forEach(s => {
                    if (s.dataset.rating <= rating) {
                        s.style.opacity = '1';
                    } else {
                        s.style.opacity = '0.3';
                    }
                });
            });
        });

        const ratingContainer = document.querySelector('.flex.space-x-4');
        if (ratingContainer) {
            ratingContainer.addEventListener('mouseout', () => {
                ratingStars.forEach(star => {
                    const input = star.parentElement.querySelector('input');
                    if (!input.checked) {
                        star.style.opacity = '0.3';
                    }
                });
            });
        }

        // Random cooking tips
        const cookingTips = [
            "Always read the entire recipe before starting! 📖",
            "Sharp knives are safer than dull ones! 🔪",
            "Mise en place: Prep all ingredients before cooking! 👩‍🍳",
            "Season as you go, taste as you cook! 🧂",
            "Let meat rest after cooking! 🥩",
            "Don't overcrowd the pan! 🍳",
            "Room temperature eggs blend better! 🥚",
            "Measure ingredients with love! ❤️"
        ];

        function updateCookingTip() {
            const tipElement = document.getElementById('cooking-tip');
            const randomTip = cookingTips[Math.floor(Math.random() * cookingTips.length)];
            tipElement.innerHTML = randomTip;
        }

        updateCookingTip();
        setInterval(updateCookingTip, 8000); // Change tip every 8 seconds
    </script>
</body>
</html>

<?php
// Keep only these functions
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getCuisineEmoji($cuisine) {
    $emojis = [
        'Italian' => '🍝',
        'Mexican' => '🌮',
        'Indian' => '🍛',
        'Chinese' => '🥢',
        'American' => '🍔',
        'Japanese' => '🍱',
        'Mediterranean' => '🫒',
        'Other' => '🍽️'
    ];
    return $emojis[$cuisine] ?? '🍽️';
}

function getDifficultyEmoji($difficulty) {
    $emojis = [
        'Easy' => '😊',
        'Medium' => '😅',
        'Hard' => '🤔'
    ];
    return $emojis[$difficulty] ?? '📝';
}
?>

// Modify the recipe query to include user information
$stmt = $conn->prepare("
    SELECT r.*, u.username, u.profile_picture 
    FROM recipes r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.id = ?
");
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$recipe = $stmt->get_result()->fetch_assoc();

<!-- Add this in the recipe details section -->
<div class="flex items-center gap-4 mb-6">
    <div class="flex items-center">
        <?php if (!empty($recipe['profile_picture'])): ?>
            <img src="<?php echo htmlspecialchars($recipe['profile_picture']); ?>" 
                 alt="<?php echo htmlspecialchars($recipe['username']); ?>"
                 class="w-12 h-12 rounded-full object-cover border-2 border-[#ff6b00]">
        <?php else: ?>
            <div class="w-12 h-12 rounded-full bg-[#ff6b00] flex items-center justify-center text-white font-bold text-xl">
                <?php echo strtoupper(substr($recipe['username'], 0, 1)); ?>
            </div>
        <?php endif; ?>
        <div class="ml-3">
            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($recipe['username']); ?></p>
            <p class="text-sm text-gray-500">Master Chef 👨‍🍳</p>
        </div>
    </div>
</div>