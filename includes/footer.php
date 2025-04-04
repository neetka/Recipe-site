<footer class="bg-gray-800 text-white py-8 mt-12">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-xl font-bold mb-4"><?php echo APP_NAME; ?></h3>
                <p class="text-gray-400">Share your favorite recipes with the world and discover new culinary delights.</p>
            </div>
            
            <div>
                <h4 class="font-bold mb-4">Quick Links</h4>
                <ul class="space-y-2">
                    <li><a href="index.php" class="text-gray-400 hover:text-white">Home</a></li>
                    <li><a href="add-recipe.php" class="text-gray-400 hover:text-white">Add Recipe</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Popular Recipes</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Categories</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-bold mb-4">Help</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white">FAQ</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Contact Us</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-bold mb-4">Connect With Us</h4>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
        </div>
        
        <div class="border-t border-gray-700 mt-8 pt-6 text-center text-gray-400">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>