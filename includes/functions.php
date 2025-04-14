<?php
require_once 'db.php';

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validateInput($data, $type = 'string', $min = null, $max = null) {
    switch ($type) {
        case 'int':
            $data = filter_var($data, FILTER_VALIDATE_INT);
            if ($data === false) return false;
            if ($min !== null && $data < $min) return false;
            if ($max !== null && $data > $max) return false;
            return $data;
        case 'string':
            if ($min !== null && strlen($data) < $min) return false;
            if ($max !== null && strlen($data) > $max) return false;
            return $data;
        default:
            return false;
    }
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
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
    }
    
    // Check if upload directory exists and is writable
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            error_log("Failed to create uploads directory at: " . $target_dir);
            return ['success' => false, 'message' => 'Failed to create uploads directory. Please check server permissions.'];
        }
    }
    
    // Check directory permissions
    if (!is_writable($target_dir)) {
        // Try to fix permissions
        if (!chmod($target_dir, 0777)) {
            error_log("Uploads directory is not writable at: " . $target_dir);
            error_log("Current permissions: " . substr(sprintf('%o', fileperms($target_dir)), -4));
            return ['success' => false, 'message' => 'Uploads directory is not writable. Please check server permissions.'];
        }
    }

    // Validate file type and get image information
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG and GIF are allowed'];
    }

    // Get image information
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return ['success' => false, 'message' => 'Invalid image file'];
    }

    // Generate a unique filename with proper extension
    $extension = image_type_to_extension($image_info[2], false);
    $filename = uniqid() . '_' . pathinfo($file['name'], PATHINFO_FILENAME) . '.' . $extension;
    $target_path = $target_dir . $filename;
    $web_path = 'uploads/' . $filename;

    // Process and save the image
    $source_image = null;
    switch ($mime_type) {
        case 'image/jpeg':
            $source_image = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/gif':
            $source_image = imagecreatefromgif($file['tmp_name']);
            break;
    }

    if (!$source_image) {
        return ['success' => false, 'message' => 'Failed to process image'];
    }

    // Preserve transparency for PNG and GIF
    if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
        imagealphablending($source_image, true);
        imagesavealpha($source_image, true);
    }

    // Save the image with high quality
    $success = false;
    switch ($mime_type) {
        case 'image/jpeg':
            $success = imagejpeg($source_image, $target_path, 100); // 100 is maximum quality
            break;
        case 'image/png':
            $success = imagepng($source_image, $target_path, 0); // 0 is no compression
            break;
        case 'image/gif':
            $success = imagegif($source_image, $target_path);
            break;
    }

    // Free up memory
    imagedestroy($source_image);

    if ($success) {
        chmod($target_path, 0666);
        return ['success' => true, 'path' => $web_path];
    } else {
        error_log("Failed to save processed image. Target path: " . $target_path);
        return ['success' => false, 'message' => 'Failed to save image. Please try again.'];
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