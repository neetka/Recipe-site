<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

// Check if user is logged in (you'll need to implement authentication)
$logged_in = false; 
$user_id = 1; 

if (!$logged_in) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipe_id = intval($_POST['recipe_id']);
    $rating = intval($_POST['rating']);
    $comment = sanitizeInput($_POST['comment']);
    
    // Validate inputs
    if ($rating < 1 || $rating > 5) {
        die("Invalid rating");
    }
    
    // Insert review
    $sql = "INSERT INTO reviews (recipe_id, user_id, rating, comment) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$recipe_id, $user_id, $rating, $comment]);
    
    if ($stmt->rowCount() > 0) {
        header("Location: recipe.php?id=$recipe_id");
        exit();
    } else {
        die("Error saving review");
    }
} else {
    header("Location: index.php");
    exit();
}
?>