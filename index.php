<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$sort = $_GET['sort'] ?? 'newest';
$filter = [
    'cuisine' => $_GET['cuisine'] ?? '',
    'difficulty' => $_GET['difficulty'] ?? '',
    'ingredient' => $_GET['ingredient'] ?? ''
];

$recipes = getRecipes($sort, $filter);
$cuisines = ['Italian', 'Mexican', 'Indian', 'Chinese', 'American', 'Japanese', 'Mediterranean', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Share Your Recipes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Sidebar Filters -->
            <aside class="w-full md:w-1/4 bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">Filter Recipes</h2>
                
                <form method="get" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuisine Type</label>
                        <select name="cuisine" class="w-full p-2 border rounded">
                            <option value="">All Cuisines</option>
                            <?php foreach ($cuisines as $cuisine): ?>
                                <option value="<?php echo $cuisine; ?>" <?php echo ($filter['cuisine'] === $cuisine) ? 'selected' : ''; ?>>
                                    <?php echo $cuisine; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Difficulty</label>
                        <select name="difficulty" class="w-full p-2 border rounded">
                            <option value="">All Levels</option>
                            <option value="Easy" <?php echo ($filter['difficulty'] === 'Easy') ? 'selected' : ''; ?>>Easy</option>
                            <option value="Medium" <?php echo ($filter['difficulty'] === 'Medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="Hard" <?php echo ($filter['difficulty'] === 'Hard') ? 'selected' : ''; ?>>Hard</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ingredient</label>
                        <input type="text" name="ingredient" placeholder="Search by ingredient" 
                               class="w-full p-2 border rounded" value="<?php echo htmlspecialchars($filter['ingredient']); ?>">
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                        Apply Filters
                    </button>
                </form>
            </aside>
            
            <!-- Main Content -->
            <div class="w-full md:w-3/4">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">Latest Recipes</h1>
                    
                    <div class="flex space-x-2">
                        <span class="text-sm text-gray-600">Sort by:</span>
                        <select id="sort-select" class="text-sm border rounded p-1">
                            <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Newest</option>
                            <option value="rating" <?php echo ($sort === 'rating') ? 'selected' : ''; ?>>Top Rated</option>
                            <option value="prep_time" <?php echo ($sort === 'prep_time') ? 'selected' : ''; ?>>Quickest</option>
                        </select>
                    </div>
                </div>
                
                <!-- Recipe Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($recipes as $recipe): ?>
                        <div class="bg-white rounded-lg overflow-hidden shadow hover:shadow-lg transition">
                            <a href="recipe.php?id=<?php echo $recipe['id']; ?>">
                                <?php if ($recipe['image_path']): ?>
                                    <img src="<?php echo $recipe['image_path']; ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>" class="w-full h-48 object-cover">
                                <?php else: ?>
                                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-utensils text-4xl text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="p-4">
                                    <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($recipe['title']); ?></h3>
                                    <div class="flex items-center mb-2">
                                        <div class="flex text-yellow-400">
                                            <?php 
                                            $rating = round($recipe['avg_rating'] ?? 0);
                                            for ($i = 1; $i <= 5; $i++): 
                                            ?>
                                                <i class="fas fa-star<?php echo ($i <= $rating) ? '' : '-empty'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-sm text-gray-600 ml-2">
                                            <?php echo $recipe['avg_rating'] ? number_format($recipe['avg_rating'], 1) : 'No ratings'; ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                                        <span><i class="fas fa-clock"></i> <?php echo $recipe['prep_time'] + $recipe['cook_time']; ?> mins</span>
                                        <span><?php echo $recipe['difficulty']; ?></span>
                                    </div>
                                    <p class="text-gray-700 text-sm"><?php echo substr(htmlspecialchars($recipe['description']), 0, 100); ?>...</p>
                                    <div class="mt-3 text-sm text-gray-500">
                                        <span>By <?php echo htmlspecialchars($recipe['username']); ?></span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($recipes)): ?>
                        <div class="col-span-3 text-center py-10">
                            <p class="text-gray-500">No recipes found. Try adjusting your filters.</p>
                            <a href="add-recipe.php" class="mt-4 inline-block bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                                Add Your First Recipe
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Handle sort selection change
        document.getElementById('sort-select').addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', this.value);
            window.location.href = url.toString();
        });
    </script>
</body>
</html>