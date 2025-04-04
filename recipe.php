<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$recipe_id = intval($_GET['id']);

// Get recipe details
$sql = "SELECT r.*, u.username 
        FROM recipes r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$result = $stmt->get_result();
$recipe = $result->fetch_assoc();

if (!$recipe) {
    header("Location: index.php");
    exit();
}

// Get reviews
$sql = "SELECT rev.*, u.username 
        FROM reviews rev 
        JOIN users u ON rev.user_id = u.id 
        WHERE rev.recipe_id = ? 
        ORDER BY rev.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Recipe Header -->
            <div class="relative">
                <?php if ($recipe['image_path']): ?>
                    <img src="<?php echo $recipe['image_path']; ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>" class="w-full h-96 object-cover">
                <?php else: ?>
                    <div class="w-full h-96 bg-gray-200 flex items-center justify-center">
                        <i class="fas fa-utensils text-8xl text-gray-400"></i>
                    </div>
                <?php endif; ?>
                
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-6">
                    <h1 class="text-3xl font-bold text-white"><?php echo htmlspecialchars($recipe['title']); ?></h1>
                    <div class="flex items-center mt-2">
                        <div class="flex text-yellow-400">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo ($i <= round($avg_rating)) ? '' : '-empty'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-white ml-2">
                            <?php echo number_format($avg_rating, 1); ?> (<?php echo count($reviews); ?> reviews)
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Recipe Meta -->
            <div class="p-6 border-b">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-center">
                    <div>
                        <div class="text-gray-500">Prep Time</div>
                        <div class="font-bold"><?php echo $recipe['prep_time']; ?> mins</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Cook Time</div>
                        <div class="font-bold"><?php echo $recipe['cook_time']; ?> mins</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Difficulty</div>
                        <div class="font-bold"><?php echo $recipe['difficulty']; ?></div>
                    </div>
                    <div>
                        <div class="text-gray-500">Servings</div>
                        <div class="font-bold"><?php echo $recipe['servings']; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Recipe Content -->
            <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column -->
                <div class="lg:col-span-2">
                    <div class="mb-8">
                        <h2 class="text-xl font-bold mb-4">Description</h2>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($recipe['description'])); ?></p>
                    </div>
                    
                    <div class="mb-8">
                        <h2 class="text-xl font-bold mb-4">Ingredients</h2>
                        <ul class="list-disc pl-5 space-y-2">
                            <?php 
                            $ingredients = explode("\n", $recipe['ingredients']);
                            foreach ($ingredients as $ingredient): 
                                if (trim($ingredient)): 
                            ?>
                                <li><?php echo htmlspecialchars(trim($ingredient)); ?></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ul>
                    </div>
                    
                    <div class="mb-8">
                        <h2 class="text-xl font-bold mb-4">Instructions</h2>
                        <ol class="list-decimal pl-5 space-y-4">
                            <?php 
                            $instructions = explode("\n", $recipe['instructions']);
                            foreach ($instructions as $step): 
                                if (trim($step)): 
                            ?>
                                <li><?php echo htmlspecialchars(trim($step)); ?></li>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </ol>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <div class="bg-gray-50 p-4 rounded-lg sticky top-4">
                        <div class="mb-6">
                            <h3 class="font-bold mb-2">Recipe By</h3>
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center mr-3">
                                    <i class="fas fa-user text-gray-600"></i>
                                </div>
                                <span><?php echo htmlspecialchars($recipe['username']); ?></span>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <h3 class="font-bold mb-2">Cuisine</h3>
                            <p><?php echo htmlspecialchars($recipe['cuisine_type']); ?></p>
                        </div>
                        
                        <div class="mb-6">
                            <h3 class="font-bold mb-2">Added On</h3>
                            <p><?php echo date('F j, Y', strtotime($recipe['created_at'])); ?></p>
                        </div>
                        
                        <a href="add-recipe.php" class="block w-full bg-blue-600 text-white text-center py-2 px-4 rounded hover:bg-blue-700 transition mb-4">
                            Add Your Own Recipe
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Reviews Section -->
            <div class="p-6 border-t">
                <h2 class="text-2xl font-bold mb-6">Reviews</h2>
                
                <?php if (empty($reviews)): ?>
                    <p class="text-gray-500">No reviews yet. Be the first to review!</p>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($reviews as $review): ?>
                            <div class="border-b pb-4">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <span class="font-bold"><?php echo htmlspecialchars($review['username']); ?></span>
                                        <span class="text-gray-500 text-sm ml-2"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
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
                    <h3 class="text-xl font-bold mb-4">Add Your Review</h3>
                    <form action="includes/add-review.php" method="post" class="space-y-4">
                        <input type="hidden" name="recipe_id" value="<?php echo $recipe_id; ?>">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
                            <select name="rating" class="w-full p-2 border rounded" required>
                                <option value="">Select rating</option>
                                <option value="1">1 - Poor</option>
                                <option value="2">2 - Fair</option>
                                <option value="3">3 - Good</option>
                                <option value="4">4 - Very Good</option>
                                <option value="5">5 - Excellent</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Comment</label>
                            <textarea name="comment" rows="4" class="w-full p-2 border rounded" required></textarea>
                        </div>
                        
                        <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                            Submit Review
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>