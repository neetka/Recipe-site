<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Get search parameters
$search_query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
$cuisine_type = isset($_GET['cuisine']) ? sanitizeInput($_GET['cuisine']) : 'All Cuisines';
$difficulty = isset($_GET['difficulty']) ? sanitizeInput($_GET['difficulty']) : 'Any Level';

// Base query
$sql = "SELECT * FROM recipes WHERE 1=1";
$params = [];

// Add search conditions
if (!empty($search_query)) {
    $sql .= " AND (title LIKE ? OR description LIKE ? OR ingredients LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($cuisine_type !== 'All Cuisines') {
    $sql .= " AND cuisine_type = ?";
    $params[] = $cuisine_type;
}

if ($difficulty !== 'Any Level') {
    $sql .= " AND difficulty = ?";
    $params[] = $difficulty;
}

try {
    // Prepare and execute query
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error searching recipes: " . $e->getMessage());
    $results = [];
}

// Start HTML output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Search Results</h1>
        
        <?php if (empty($results)): ?>
            <div class="text-center py-8">
                <p class="text-gray-600">No recipes found matching your search criteria.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($results as $recipe): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <a href="recipe.php?id=<?php echo $recipe['id']; ?>">
                            <?php if (!empty($recipe['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($recipe['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($recipe['title']); ?>"
                                     class="w-full h-48 object-cover">
                            <?php else: ?>
                                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                    <span class="text-gray-400">No image available</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-4">
                                <h2 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($recipe['title']); ?></h2>
                                <p class="text-gray-600 mb-2"><?php echo substr(htmlspecialchars($recipe['description']), 0, 100) . '...'; ?></p>
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <span><?php echo $recipe['cuisine_type']; ?></span>
                                    <span><?php echo $recipe['difficulty']; ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>