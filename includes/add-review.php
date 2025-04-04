<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

// Check if user is logged in (you'll need to implement authentication)
$logged_in = false; // Change this based on your auth system
$user_id = 1; // Replace with actual logged-in user ID

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
    $stmt->bind_param("iiis", $recipe_id, $user_id, $rating, $comment);
    
    if ($stmt->execute()) {
        header("Location: recipe.php?id=$recipe_id");
        exit();
    } else {
        die("Error saving review: " . $stmt->error);
    }
} else {
    header("Location: index.php");
    exit();
}
?>