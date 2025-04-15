<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get recipe ID
$recipe_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($recipe_id > 0) {
    try {
        // First, check if the recipe exists and belongs to the user
        $stmt = $conn->prepare("SELECT image_path FROM recipes WHERE id = ? AND user_id = ?");
        $stmt->execute([$recipe_id, $_SESSION['user_id']]);
        $recipe = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($recipe) {
            // Start transaction
            $conn->beginTransaction();
            
            try {
                // Delete reviews
                $stmt = $conn->prepare("DELETE FROM reviews WHERE recipe_id = ?");
                $stmt->execute([$recipe_id]);
                
                // Delete likes
                $stmt = $conn->prepare("DELETE FROM recipe_likes WHERE recipe_id = ?");
                $stmt->execute([$recipe_id]);
                
                // Delete recipe
                $stmt = $conn->prepare("DELETE FROM recipes WHERE id = ? AND user_id = ?");
                $stmt->execute([$recipe_id, $_SESSION['user_id']]);
                
                // Delete the image file if it exists
                if (!empty($recipe['image_path']) && file_exists($recipe['image_path'])) {
                    unlink($recipe['image_path']);
                }
                
                $conn->commit();
                $_SESSION['success_message'] = "Recipe deleted successfully!";
                header("Location: my-recipes.php");
                exit();
            } catch (PDOException $e) {
                $conn->rollBack();
                throw $e;
            }
        } else {
            $_SESSION['error_message'] = "Recipe not found or you don't have permission to delete it.";
            header("Location: my-recipes.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error deleting recipe: " . $e->getMessage());
        $_SESSION['error_message'] = "An error occurred while deleting the recipe.";
        header("Location: recipe.php?id=" . $recipe_id);
        exit();
    }
} else {
    $_SESSION['error_message'] = "Invalid recipe ID.";
    header("Location: my-recipes.php");
    exit();
}
?> 