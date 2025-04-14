<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['review_data'] = [
        'recipe_id' => $_POST['recipe_id'],
        'rating' => $_POST['rating'],
        'comment' => $_POST['comment']
    ];
    header("Location: login.php?redirect=" . urlencode($_SERVER['HTTP_REFERER']));
    exit();
}

// Process review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid request");
    }

    // Validate inputs
    $recipe_id = validateInput($_POST['recipe_id'], 'int', 1);
    $user_id = (int)$_SESSION['user_id'];
    $rating = validateInput($_POST['rating'], 'int', 1, 5);
    $comment = validateInput($_POST['comment'], 'string', 1, 1000);

    if (!$recipe_id || !$rating || !$comment) {
        die("Invalid input data");
    }

    // Check if recipe exists
    $stmt = $conn->prepare("SELECT id FROM recipes WHERE id = ?");
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    if (!$stmt->get_result()->num_rows) {
        die("Recipe not found");
    }

    // Check for existing review
    $stmt = $conn->prepare("SELECT id FROM reviews WHERE recipe_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $recipe_id, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows) {
        die("You have already reviewed this recipe");
    }

    // Save to database
    $sql = "INSERT INTO reviews (recipe_id, user_id, rating, comment) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $recipe_id, $user_id, $rating, $comment);
    
    if ($stmt->execute()) {
        $_SESSION['review_success'] = true;
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        error_log("Error saving review: " . $conn->error);
        die("An error occurred while saving your review. Please try again later.");
    }
}
?>