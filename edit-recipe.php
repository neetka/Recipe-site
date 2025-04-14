<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to edit recipes.";
    header("Location: login.php");
    exit();
}

// Get recipe ID from URL
$recipe_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$recipe_id) {
    $_SESSION['error'] = "Invalid recipe ID.";
    header("Location: my-recipes.php");
    exit();
}

// Get available cuisines
$cuisines = [
    'Italian' => ['Pizza', 'Pasta', 'Risotto', 'Mediterranean'],
    'Asian' => ['Chinese', 'Japanese', 'Thai', 'Korean', 'Vietnamese', 'Indian'],
    'American' => ['BBQ', 'Burgers', 'Soul Food', 'Tex-Mex'],
    'European' => ['French', 'German', 'Spanish', 'Greek'],
    'Latin American' => ['Mexican', 'Brazilian', 'Peruvian', 'Argentine'],
    'Middle Eastern' => ['Lebanese', 'Turkish', 'Persian', 'Arabian'],
    'African' => ['Moroccan', 'Ethiopian', 'Nigerian', 'South African'],
    'Other' => ['Fusion', 'Modern', 'International']
];

// Fetch existing recipe data
try {
    $stmt = $pdo->prepare("SELECT * FROM recipes WHERE id = ? AND user_id = ?");
    $stmt->execute([$recipe_id, $_SESSION['user_id']]);
    $recipe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recipe) {
        $_SESSION['error'] = "Recipe not found or you don't have permission to edit it.";
        header("Location: my-recipes.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: my-recipes.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_changes'])) {
    $errors = [];
    
    // Validate and sanitize inputs
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $ingredients = trim($_POST['ingredients']);
    $instructions = trim($_POST['instructions']);
    $prep_time = filter_var($_POST['prep_time'], FILTER_VALIDATE_INT);
    $cook_time = filter_var($_POST['cook_time'], FILTER_VALIDATE_INT);
    $servings = filter_var($_POST['servings'], FILTER_VALIDATE_INT);
    $difficulty = $_POST['difficulty'];
    $cuisine_type = $_POST['cuisine_type'];

    // Validation
    if (empty($title)) $errors[] = "Title is required";
    if (empty($ingredients)) $errors[] = "Ingredients are required";
    if (empty($instructions)) $errors[] = "Instructions are required";
    if ($prep_time === false || $prep_time <= 0) $errors[] = "Invalid prep time";
    if ($cook_time === false || $cook_time <= 0) $errors[] = "Invalid cook time";
    if ($servings === false || $servings <= 0) $errors[] = "Invalid servings number";
    
    // Handle image upload if provided
    $image_path = $recipe['image_path']; // Keep existing image by default
    if (!empty($_FILES['image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Invalid image type. Please upload JPEG, PNG, or GIF.";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Image size too large. Maximum size is 10MB.";
        } else {
            $upload_result = uploadImage($_FILES['image']);
            if ($upload_result['success']) {
                // Delete old image if exists
                if (!empty($recipe['image_path']) && file_exists($recipe['image_path'])) {
                    unlink($recipe['image_path']);
                }
                $image_path = $upload_result['path'];
            } else {
                $errors[] = "Error uploading image: " . $upload_result['error'];
            }
        }
    }

    // Update recipe if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE recipes 
                SET title = ?, description = ?, ingredients = ?, 
                    instructions = ?, prep_time = ?, cook_time = ?, 
                    servings = ?, difficulty = ?, cuisine_type = ?, 
                    image_path = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");

            $result = $stmt->execute([
                $title, $description, $ingredients, 
                $instructions, $prep_time, $cook_time, 
                $servings, $difficulty, $cuisine_type, 
                $image_path, $recipe_id, $_SESSION['user_id']
            ]);

            if ($result) {
                $_SESSION['success'] = "Recipe updated successfully!";
                header("Location: recipe.php?id=" . $recipe_id);
                exit();
            } else {
                $errors[] = "Failed to update recipe. Please try again.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
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
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION['success']; ?></span>
                    <?php unset($_SESSION['success']); ?>
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
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuisine Type*</label>
                        <select name="cuisine_type" class="form-select" required>
                            <?php foreach ($cuisines as $category => $subcuisines): ?>
                                <optgroup label="<?php echo htmlspecialchars($category); ?>">
                                    <?php foreach ($subcuisines as $cuisine): ?>
                                        <option value="<?php echo htmlspecialchars($cuisine); ?>"
                                                <?php echo $recipe['cuisine_type'] === $cuisine ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cuisine); ?> <?php echo getCuisineEmoji($cuisine); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
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
                    <a href="recipe.php?id=<?php echo $recipe_id; ?>" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                        Cancel
                    </a>
                    <button type="submit" name="save_changes" class="px-6 py-2 bg-[#ff6b00] text-white rounded-lg hover:bg-[#ff8533] transition-colors duration-200 flex items-center">
                        <i class="fas fa-save mr-2"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Enhance the existing form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.querySelector('input[name="title"]').value.trim();
            const ingredients = document.querySelector('textarea[name="ingredients"]').value.trim();
            const instructions = document.querySelector('textarea[name="instructions"]').value.trim();
            const prepTime = parseInt(document.querySelector('input[name="prep_time"]').value);
            const cookTime = parseInt(document.querySelector('input[name="cook_time"]').value);
            const servings = parseInt(document.querySelector('input[name="servings"]').value);
            
            let errors = [];
            
            if (!title) errors.push('Recipe title is required');
            if (!ingredients) errors.push('Ingredients are required');
            if (!instructions) errors.push('Instructions are required');
            if (isNaN(prepTime) || prepTime <= 0) errors.push('Prep time must be a positive number');
            if (isNaN(cookTime) || cookTime <= 0) errors.push('Cook time must be a positive number');
            if (isNaN(servings) || servings <= 0) errors.push('Servings must be a positive number');
            
            const imageInput = document.querySelector('input[type="file"]');
            if (imageInput.files.length > 0) {
                const file = imageInput.files[0];
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                if (file.size > maxSize) {
                    errors.push('Image size must be less than 10MB');
                }
                if (!['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
                    errors.push('Invalid image type. Please upload JPEG, PNG, or GIF');
                }
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                const errorDiv = document.createElement('div');
                errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4';
                errorDiv.innerHTML = errors.join('<br>');
                const form = document.querySelector('form');
                form.insertBefore(errorDiv, form.firstChild);
                errorDiv.scrollIntoView({ behavior: 'smooth' });
            }
        });

        // Add loading state to submit button
        document.querySelector('button[type="submit"]').addEventListener('click', function(e) {
            if (document.querySelector('form').checkValidity()) {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
            }
        });

        // Image preview enhancement
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type and size
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                const maxSize = 10 * 1024 * 1024; // 10MB
                
                if (!validTypes.includes(file.type)) {
                    alert('Please upload a valid image file (JPEG, PNG, or GIF)');
                    this.value = '';
                    return;
                }
                
                if (file.size > maxSize) {
                    alert('File size must be less than 10MB');
                    this.value = '';
                    return;
                }
                
                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('img') || document.createElement('img');
                    preview.src = e.target.result;
                    preview.className = 'w-32 h-32 object-cover rounded mb-2';
                    if (!document.querySelector('img')) {
                        document.querySelector('.mb-2').appendChild(preview);
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 