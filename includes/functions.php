<?php
require_once 'db.php';

function sanitizeInput($data) {
    global $conn;
    return htmlspecialchars(strip_tags(trim($conn->real_escape_string($data))));
}

function getRecipes($sort = 'newest', $filter = []) {
    global $conn;
    
    $sql = "SELECT r.*, u.username, 
           (SELECT AVG(rating) FROM reviews WHERE recipe_id = r.id) as avg_rating
           FROM recipes r 
           JOIN users u ON r.user_id = u.id";
    
    $where = [];
    $params = [];
    $types = '';
    
    // Apply filters
    if (!empty($filter['cuisine'])) {
        $where[] = "r.cuisine_type = ?";
        $params[] = $filter['cuisine'];
        $types .= 's';
    }
    
    if (!empty($filter['difficulty'])) {
        $where[] = "r.difficulty = ?";
        $params[] = $filter['difficulty'];
        $types .= 's';
    }
    
    if (!empty($filter['ingredient'])) {
        $where[] = "r.ingredients LIKE ?";
        $params[] = '%' . $filter['ingredient'] . '%';
        $types .= 's';
    }
    
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    
    // Apply sorting
    switch ($sort) {
        case 'rating':
            $sql .= " ORDER BY avg_rating DESC";
            break;
        case 'prep_time':
            $sql .= " ORDER BY r.prep_time ASC";
            break;
        default:
            $sql .= " ORDER BY r.created_at DESC";
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

function uploadImage($file) {
    $target_dir = UPLOAD_DIR;
    $target_file = $target_dir . basename($file['name']);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is a actual image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['success' => false, 'message' => 'File is not an image.'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File is too large.'];
    }
    
    // Allow certain file formats
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($imageFileType, $allowed)) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '.' . $imageFileType;
    $target_path = $target_dir . $filename;
    
    // Try to upload file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'path' => $target_path];
    } else {
        return ['success' => false, 'message' => 'Error uploading file.'];
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit();
    }
}
?>