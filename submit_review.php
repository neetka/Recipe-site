<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Invalid request. Please try again.";
    header("Location: recipe.php?id=" . $_POST['recipe_id']);
    exit();
}

// Validate POST request and data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_POST['recipe_id']) && 
    isset($_POST['rating']) && 
    isset($_POST['comment'])) {
    
    $recipe_id = filter_var($_POST['recipe_id'], FILTER_VALIDATE_INT);
    $rating = filter_var($_POST['rating'], FILTER_VALIDATE_INT);
    $comment = trim($_POST['comment']);
    
    // Validate data
    if (!$recipe_id || $rating < 1 || $rating > 5 || empty($comment)) {
        $_SESSION['error_message'] = "Please provide valid rating and comment.";
        header("Location: recipe.php?id=" . $_POST['recipe_id']);
        exit();
    }

    try {
        // Check if user has already reviewed this recipe
        $stmt = $conn->prepare("SELECT id FROM reviews WHERE recipe_id = ? AND user_id = ?");
        $stmt->execute([$recipe_id, $_SESSION['user_id']]);
        $existing_review = $stmt->fetch();

        if ($existing_review) {
            // Update existing review
            $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ?, updated_at = NOW() WHERE recipe_id = ? AND user_id = ?");
            $stmt->execute([$rating, $comment, $recipe_id, $_SESSION['user_id']]);
        } else {
            // Insert new review
            $stmt = $conn->prepare("INSERT INTO reviews (recipe_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$recipe_id, $_SESSION['user_id'], $rating, $comment]);
        }

        $_SESSION['success_message'] = "Your review has been submitted successfully!";
    } catch (PDOException $e) {
        error_log("Review submission error: " . $e->getMessage());
        $_SESSION['error_message'] = "An error occurred while submitting your review.";
    }
} else {
    $_SESSION['error_message'] = "Invalid request. Please fill all required fields.";
}

// Redirect back to recipe page
header("Location: recipe.php?id=" . $_POST['recipe_id']);
exit();