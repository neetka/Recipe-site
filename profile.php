<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
try {
    $stmt = $conn->prepare("
        SELECT u.*, 
               COUNT(DISTINCT r.id) as total_recipes,
               COUNT(DISTINCT rev.recipe_id) as total_reviews,
               (SELECT COUNT(*) FROM recipe_likes WHERE user_id = u.id) as total_likes_given,
               (SELECT COUNT(*) FROM recipe_likes rl 
                JOIN recipes r2 ON rl.recipe_id = r2.id 
                WHERE r2.user_id = u.id) as total_likes_received
        FROM users u
        LEFT JOIN recipes r ON u.id = r.user_id
        LEFT JOIN reviews rev ON u.id = rev.user_id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    die("An error occurred while fetching your profile data.");
}

// Get user's recent activity
try {
    $stmt = $conn->prepare("
        (SELECT 'recipe' as type, r.id, r.title, r.created_at, NULL as rating, NULL as comment
         FROM recipes r
         WHERE r.user_id = ?
         ORDER BY r.created_at DESC
         LIMIT 5)
        UNION ALL
        (SELECT 'review' as type, r.id, r.title, rev.created_at, rev.rating, rev.comment
         FROM reviews rev
         JOIN recipes r ON rev.recipe_id = r.id
         WHERE rev.user_id = ?
         ORDER BY rev.created_at DESC
         LIMIT 5)
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching user activity: " . $e->getMessage());
    $activities = [];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $bio = sanitizeInput($_POST['bio']);
    $errors = [];
    
    // Validate inputs
    if (empty($username)) $errors[] = "Username is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    
    // Check if username or email already exists
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $_SESSION['user_id']]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Username or email already exists";
            }
        } catch (PDOException $e) {
            error_log("Error checking username/email: " . $e->getMessage());
            $errors[] = "An error occurred while checking username/email availability";
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, bio = ? WHERE id = ?");
            if ($stmt->execute([$username, $email, $bio, $_SESSION['user_id']])) {
                $_SESSION['success_message'] = "Profile updated successfully!";
                header("Location: profile.php");
                exit();
            }
        } catch (PDOException $e) {
            error_log("Error updating profile: " . $e->getMessage());
            $errors[] = "An error occurred while updating your profile";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-50">
    <?php include 'includes/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Profile Header -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="p-6 sm:p-8">
                    <div class="flex items-center space-x-6">
                        <div class="w-24 h-24 bg-[#ff6b00] rounded-full flex items-center justify-center text-white text-4xl font-bold">
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($user['username']); ?></h1>
                            <p class="text-gray-600">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                            <?php if (!empty($user['bio'])): ?>
                                <p class="mt-2 text-gray-700"><?php echo htmlspecialchars($user['bio']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                        <div class="bg-[#fff0e6] p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-[#ff6b00]"><?php echo $user['total_recipes']; ?></div>
                            <div class="text-sm text-gray-600">Recipes</div>
                        </div>
                        <div class="bg-[#fff0e6] p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-[#ff6b00]"><?php echo $user['total_reviews']; ?></div>
                            <div class="text-sm text-gray-600">Reviews</div>
                        </div>
                        <div class="bg-[#fff0e6] p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-[#ff6b00]"><?php echo $user['total_likes_received']; ?></div>
                            <div class="text-sm text-gray-600">Likes Received</div>
                        </div>
                        <div class="bg-[#fff0e6] p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-[#ff6b00]"><?php echo $user['total_likes_given']; ?></div>
                            <div class="text-sm text-gray-600">Likes Given</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Edit Profile Form -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold text-[#ff6b00]">Edit Profile</h2>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert-error mx-6 mt-4">
                        <ul class="list-disc pl-5">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert-success mx-6 mt-4">
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <form action="profile.php" method="post" class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username*</label>
                        <input type="text" name="username" required class="form-input"
                               value="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email*</label>
                        <input type="email" name="email" required class="form-input"
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                        <textarea name="bio" rows="4" class="form-input"
                                  placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save mr-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold text-[#ff6b00]">Recent Activity</h2>
                </div>
                
                <div class="p-6">
                    <?php if (empty($activities)): ?>
                        <p class="text-gray-600">No recent activity</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($activities as $activity): ?>
                                <div class="flex items-start space-x-4">
                                    <div class="w-8 h-8 rounded-full bg-[#fff0e6] flex items-center justify-center">
                                        <i class="fas <?php echo $activity['type'] === 'recipe' ? 'fa-utensils' : 'fa-comment'; ?> text-[#ff6b00]"></i>
                                    </div>
                                    <div>
                                        <p class="text-gray-700">
                                            <?php if ($activity['type'] === 'recipe'): ?>
                                                Added a new recipe: 
                                                <a href="recipe.php?id=<?php echo $activity['id']; ?>" 
                                                   class="text-[#ff6b00] hover:text-[#ff8533] font-medium">
                                                    <?php echo htmlspecialchars($activity['title']); ?>
                                                </a>
                                            <?php else: ?>
                                                Reviewed 
                                                <a href="recipe.php?id=<?php echo $activity['id']; ?>" 
                                                   class="text-[#ff6b00] hover:text-[#ff8533] font-medium">
                                                    <?php echo htmlspecialchars($activity['title']); ?>
                                                </a>
                                                <span class="text-yellow-500">
                                                    <?php echo str_repeat('★', $activity['rating']) . str_repeat('☆', 5 - $activity['rating']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </p>
                                        <?php if ($activity['type'] === 'review' && !empty($activity['comment'])): ?>
                                            <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($activity['comment']); ?></p>
                                        <?php endif; ?>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <?php echo date('F j, Y \a\t g:i a', strtotime($activity['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html> 