<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get recipe ID from URL
$recipe_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get available cuisines
$cuisines = [
    'Italian' => '🍝',
    'Mexican' => '🌮',
    'Indian' => '🍛',
    'Chinese' => '🥢',
    'American' => '🍔',
    'Japanese' => '🍱',
    'Mediterranean' => '🫒',
    'Other' => '🍽️'
];

// Fetch existing recipe data
try {
    $stmt = $conn->prepare("SELECT * FROM recipes WHERE id = ? AND user_id = ?");
    $stmt->execute([$recipe_id, $_SESSION['user_id']]);
    $recipe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipe) {
        $_SESSION['error_message'] = "Recipe not found or you don't have permission to edit it.";
        header("Location: my-recipes.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching recipe: " . $e->getMessage());
    die("An error occurred while fetching the recipe.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $ingredients = sanitizeInput($_POST['ingredients']);
    $instructions = sanitizeInput($_POST['instructions']);
    $prep_time = intval($_POST['prep_time']);
    $cook_time = intval($_POST['cook_time']);
    $servings = intval($_POST['servings']);
    $difficulty = sanitizeInput($_POST['difficulty']);
    $cuisine_type = sanitizeInput($_POST['cuisine_type']);
    
    $errors = [];
    
    // Validate required fields
    if (empty($title)) $errors[] = "Title is required";
    if (empty($ingredients)) $errors[] = "Ingredients are required";
    if (empty($instructions)) $errors[] = "Instructions are required";
    if ($prep_time <= 0) $errors[] = "Prep time must be positive";
    if ($cook_time <= 0) $errors[] = "Cook time must be positive";
    if ($servings <= 0) $errors[] = "Servings must be positive";
    
    // Handle image upload if a new image was provided
    $image_path = $recipe['image_path']; // Default to existing image path
    if (!empty($_FILES['image']['name'])) {
        // Delete the old image if it exists
        if (!empty($recipe['image_path']) && file_exists($recipe['image_path'])) {
            unlink($recipe['image_path']);
        }
        
        // Upload the new image
        $upload_result = uploadImage($_FILES['image']);
        if ($upload_result['success']) {
            $image_path = $upload_result['path'];
        } else {
            $errors[] = "Error uploading image: " . $upload_result['message'];
        }
    }
    
    // If no errors, update the recipe
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                UPDATE recipes 
                SET title = ?,
                    description = ?,
                    ingredients = ?,
                    instructions = ?,
                    prep_time = ?,
                    cook_time = ?,
                    servings = ?,
                    difficulty = ?,
                    cuisine_type = ?,
                    image_path = ?
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([
                $title,
                $description,
                $ingredients,
                $instructions,
                $prep_time,
                $cook_time,
                $servings,
                $difficulty,
                $cuisine_type,
                $image_path,
                $recipe_id,
                $_SESSION['user_id']
            ]);
            
            $_SESSION['success_message'] = "Recipe updated successfully!";
            header("Location: recipe.php?id=" . $recipe_id);
            exit();
            
        } catch (PDOException $e) {
            error_log("Recipe update error: " . $e->getMessage());
            $errors[] = "An error occurred while updating the recipe.";
        }
    }
}

// Function to check if a cuisine is selected
function isCuisineSelected($cuisine) {
    global $recipe;
    return $recipe['cuisine_type'] === $cuisine ? 'selected' : '';
}

// Function to get cuisine emoji
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Recipe - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="p-6 border-b">
                <h1 class="text-2xl font-bold text-[#ff6b00]">Edit Recipe</h1>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert-error mx-6 mt-4">
                    <ul class="list-disc pl-5">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="edit-recipe.php?id=<?php echo $recipe_id; ?>" method="post" enctype="multipart/form-data" class="p-6 space-y-6">
                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Recipe Title*</label>
                        <input type="text" name="title" required class="form-input"
                               value="<?php echo htmlspecialchars($recipe['title']); ?>">
                    </div>
                    
                    <!-- Cuisine Type -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cuisine Type</label>
                        <div class="relative">
                            <select name="cuisine_type" class="form-select w-full py-3 px-4 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-[#ff6b00] focus:border-transparent appearance-none cursor-pointer">
                                <?php foreach ($cuisines as $cuisine => $emoji): ?>
                                    <option value="<?php echo $cuisine; ?>" 
                                            <?php echo $recipe['cuisine_type'] === $cuisine ? 'selected' : ''; ?>>
                                        <?php echo $emoji . ' ' . $cuisine; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3" class="form-input"><?php echo htmlspecialchars($recipe['description']); ?></textarea>
                </div>
                
                <!-- Image Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recipe Image</label>
                    <?php if (!empty($recipe['image_path'])): ?>
                        <div class="mb-2">
                            <img src="<?php echo htmlspecialchars($recipe['image_path']); ?>" 
                                 alt="Current recipe image" class="w-32 h-32 object-cover rounded">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*" class="form-input">
                    <p class="mt-1 text-sm text-gray-500">Leave empty to keep current image. Max size: 10MB (JPEG, PNG, GIF)</p>
                </div>
                
                <!-- Times and Servings -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prep Time (mins)*</label>
                        <input type="number" name="prep_time" min="1" required class="form-input"
                               value="<?php echo $recipe['prep_time']; ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cook Time (mins)*</label>
                        <input type="number" name="cook_time" min="1" required class="form-input"
                               value="<?php echo $recipe['cook_time']; ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Servings*</label>
                        <input type="number" name="servings" min="1" required class="form-input"
                               value="<?php echo $recipe['servings']; ?>">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Difficulty*</label>
                    <select name="difficulty" required class="form-select">
                        <option value="Easy" <?php echo $recipe['difficulty'] === 'Easy' ? 'selected' : ''; ?>>Easy</option>
                        <option value="Medium" <?php echo $recipe['difficulty'] === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="Hard" <?php echo $recipe['difficulty'] === 'Hard' ? 'selected' : ''; ?>>Hard</option>
                    </select>
                </div>
                
                <!-- Ingredients -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ingredients*</label>
                    <p class="text-sm text-gray-500 mb-2">Enter one ingredient per line</p>
                    <textarea name="ingredients" rows="6" required class="form-input font-mono"><?php echo htmlspecialchars($recipe['ingredients']); ?></textarea>
                </div>
                
                <!-- Instructions -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Instructions*</label>
                    <p class="text-sm text-gray-500 mb-2">Enter one step per line</p>
                    <textarea name="instructions" rows="8" required class="form-input"><?php echo htmlspecialchars($recipe['instructions']); ?></textarea>
                </div>
                
                <!-- Submit Buttons -->
                <div class="flex justify-end space-x-4 mt-6">
                    <a href="recipe.php?id=<?php echo $recipe_id; ?>" 
                       class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition transform hover:scale-105">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                    <button type="submit" name="save_changes" 
                            class="px-6 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition transform hover:scale-105 flex items-center">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Preview image before upload
        const imageInput = document.querySelector('input[type="file"]');
        imageInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('img');
                    if (preview) {
                        preview.src = e.target.result;
                    } else {
                        const newPreview = document.createElement('img');
                        newPreview.src = e.target.result;
                        newPreview.classList.add('w-32', 'h-32', 'object-cover', 'rounded', 'mb-2');
                        imageInput.parentNode.insertBefore(newPreview, imageInput);
                    }
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.querySelector('input[name="title"]').value.trim();
            const ingredients = document.querySelector('textarea[name="ingredients"]').value.trim();
            const instructions = document.querySelector('textarea[name="instructions"]').value.trim();
            const cuisine_type = document.querySelector('select[name="cuisine_type"]');
            
            let errors = [];
            
            if (!title) errors.push('Recipe title is required');
            if (!ingredients) errors.push('Ingredients are required');
            if (!instructions) errors.push('Instructions are required');
            if (!cuisine_type.value) errors.push('Please select a cuisine type');
            
            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join('\n'));
            }
        });

        // Remove the cuisine selection event listeners since we're using a select element now
        document.querySelector('select[name="cuisine_type"]').addEventListener('change', function() {
            this.style.borderColor = '#ff6b00';
        });
    </script>

    <style>
        /* Custom styles for the select dropdown */
        .form-select {
            background-image: none;
        }
        
        .form-select option {
            padding: 8px;
            font-size: 1rem;
        }
        
        /* Hover effect for options */
        .form-select option:hover {
            background-color: #fff0e6;
            color: #ff6b00;
        }
        
        /* Selected option style */
        .form-select option:checked {
            background-color: #fff0e6;
            color: #ff6b00;
        }
    </style>
</body>
</html>