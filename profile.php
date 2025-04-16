<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Get user data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found");
    }

    // Get user stats
    $stmt = $conn->prepare("SELECT COUNT(*) as recipe_count FROM recipes WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $recipe_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT COUNT(*) as review_count FROM reviews WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $review_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    $errors[] = "An error occurred while fetching your profile data.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $bio = sanitizeInput($_POST['bio']);

    // Validate inputs
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Check if username or email already exists for other users
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Username or email already exists";
        }
    } catch (PDOException $e) {
        error_log("Error checking username/email: " . $e->getMessage());
        $errors[] = "An error occurred while validating your information";
    }

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profile_pictures/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                error_log("Failed to create directory: " . $upload_dir);
                $errors[] = "Failed to create upload directory";
            }
        }
        
        // Validate file type and size
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $file_type = $_FILES['profile_picture']['type'];
        $file_size = $_FILES['profile_picture']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Only JPG, PNG and GIF files are allowed";
        } elseif ($file_size > $max_size) {
            $errors[] = "File size must be less than 5MB";
        } else {
            try {
                $file_info = getimagesize($_FILES['profile_picture']['tmp_name']);
                if ($file_info === false) {
                    throw new Exception("Invalid image file");
                }

                $extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '_' . time() . '.' . $extension;
                $target_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_path)) {
                    $profile_picture_path = $target_path;
                } else {
                    throw new Exception("Failed to move uploaded file");
                }
            } catch (Exception $e) {
                error_log("Error handling file upload: " . $e->getMessage());
                $errors[] = "Failed to upload profile picture: " . $e->getMessage();
            }
        }
    }

    // If no errors, update the database
    if (empty($errors)) {
        try {
            $sql = "UPDATE users SET username = ?, email = ?, bio = ?";
            $params = [$username, $email, $bio];

            if (isset($profile_picture_path)) {
                $sql .= ", profile_picture = ?";
                $params[] = $profile_picture_path;
            }

            $sql .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $conn->prepare($sql);
            if ($stmt->execute($params)) {
                $success_message = "Profile updated successfully!";
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                throw new Exception("Failed to update profile");
            }
        } catch (Exception $e) {
            error_log("Error updating profile: " . $e->getMessage());
            $errors[] = "An error occurred while updating your profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Profile Header -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-6">
                <div class="relative h-48 bg-gradient-to-r from-[#ff6b00] to-[#ff8533]">
                    <div class="absolute -bottom-16 left-8">
                        <div class="relative">
                            <div class="w-32 h-32 rounded-full border-4 border-white overflow-hidden bg-white">
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                         alt="Profile Picture"
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-user text-4xl text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="pt-20 px-8 pb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </h1>
                    <p class="text-gray-600 mb-6">
                        Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
                    </p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div class="bg-[#fff0e6] rounded-xl p-6 text-center transform hover:scale-105 transition-transform">
                            <div class="text-3xl font-bold text-[#ff6b00] mb-2">
                                <?php echo $recipe_stats['recipe_count']; ?>
                            </div>
                            <div class="text-gray-600">Recipes</div>
                        </div>
                        <div class="bg-[#fff0e6] rounded-xl p-6 text-center transform hover:scale-105 transition-transform">
                            <div class="text-3xl font-bold text-[#ff6b00] mb-2">
                                <?php echo $review_stats['review_count']; ?>
                            </div>
                            <div class="text-gray-600">Reviews</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Profile</h2>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 text-red-500 p-4 rounded-xl mb-6">
                        <ul class="list-disc pl-5">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="bg-green-50 text-green-500 p-4 rounded-xl mb-6">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <form action="profile.php" method="post" enctype="multipart/form-data" class="space-y-6">
                    <!-- Profile Picture Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Profile Picture</label>
                        <div class="flex items-center space-x-6">
                            <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-100">
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                         alt="Current Profile Picture"
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas fa-user text-2xl text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <input type="file" name="profile_picture" accept="image/*" 
                                       class="block w-full text-sm text-gray-500
                                              file:mr-4 file:py-2 file:px-4
                                              file:rounded-full file:border-0
                                              file:text-sm file:font-semibold
                                              file:bg-[#fff0e6] file:text-[#ff6b00]
                                              hover:file:bg-[#ffe4d1]">
                                <p class="mt-1 text-sm text-gray-500">
                                    Recommended: Square image, at least 200x200 pixels
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" name="username" 
                               value="<?php echo htmlspecialchars($user['username']); ?>"
                               class="form-input">
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>"
                               class="form-input">
                    </div>

                    <!-- Bio -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                        <textarea name="bio" rows="4" cols="96" 
                                  class="form-input"
                                  placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-3 border border-transparent 
                                       text-base font-medium rounded-full shadow-sm text-white 
                                       bg-[#ff6b00] hover:bg-[#ff8533] 
                                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#ff6b00]
                                       transform transition-all duration-200 hover:scale-105
                                       space-x-2">
                            <i class="fas fa-save"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Preview profile picture before upload
        const fileInput = document.querySelector('input[type="file"]');
        const preview = document.querySelector('.w-20.h-20 img, .w-20.h-20 div');
        
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (preview.tagName === 'IMG') {
                        preview.src = e.target.result;
                    } else {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('w-full', 'h-full', 'object-cover');
                        preview.parentNode.replaceChild(img, preview);
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 