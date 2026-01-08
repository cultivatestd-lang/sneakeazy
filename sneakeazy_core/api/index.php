<?php
session_start();
// Enable GZIP compression for faster content delivery
if (extension_loaded('zlib')) {
    ob_start('ob_gzhandler');
}

// --- KONFIGURASI & PEMUATAN DATABASE ---
// Database MySQL menggunakan MAMP
require_once 'config/database.php';
$pdo = getDBConnection();

// Load data from database
try {
    $productsStmt = $pdo->query("SELECT * FROM products ORDER BY id");
    $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

    $interactionsStmt = $pdo->query("SELECT * FROM interactions");
    $interactions = $interactionsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error loading data: " . $e->getMessage());
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$error = '';
$success = '';

// --- TRACK INTERACTION AJAX (HOVER) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'track_interaction') {
    if (!isset($_SESSION['user_id'])) {
        exit; // Ignore anonymous users
    }

    $productId = $_POST['product_id'];
    $userId = $_SESSION['user_id'];
    $type = $_POST['type'] ?? 'hover'; // 'hover' or 'click'

    // Score increments
    $increment = ($type === 'hover') ? 0.5 : 1.0;

    try {
        $checkStmt = $pdo->prepare("SELECT * FROM interactions WHERE user_id = ? AND product_id = ?");
        $checkStmt->execute([$userId, $productId]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            // Update score (max 5)
            $newScore = min(floatval($existing['view_score']) + $increment, 5);
            // Update view_count just for tracking records
            $newViewCount = $existing['view_count'] + 1;

            $updateStmt = $pdo->prepare("UPDATE interactions SET view_count = ?, view_score = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            $updateStmt->execute([$newViewCount, $newScore, $userId, $productId]);
        } else {
            // New interaction
            $initialScore = $increment;
            $insertStmt = $pdo->prepare("INSERT INTO interactions (user_id, product_id, view_count, view_score, timestamp) VALUES (?, ?, 1, ?, ?)");
            $insertStmt->execute([$userId, $productId, $initialScore, time()]);
        }
        echo json_encode(['status' => 'success', 'score' => $newScore ?? $initialScore]);
    } catch (PDOException $e) {
        // Silent fail
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// --- TRACK PRODUCT VIEW/CLICK (Legacy Page Load & Validation) ---
// Track when user views/clicks a product (for collaborative filtering)
if (isset($_GET['page']) && $_GET['page'] === 'detail' && isset($_GET['product_id']) && isset($_SESSION['user_id'])) {
    $productId = $_GET['product_id'];
    $userId = $_SESSION['user_id'];

    try {
        // Check if interaction exists
        $checkStmt = $pdo->prepare("SELECT * FROM interactions WHERE user_id = ? AND product_id = ?");
        $checkStmt->execute([$userId, $productId]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            // Click implies strong interest, ensure at least +1 or max
            $newViewCount = $existing['view_count'] + 1;
            $viewScore = min(floatval($existing['view_score']) + 1.0, 5);

            $updateStmt = $pdo->prepare("UPDATE interactions SET view_count = ?, view_score = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            $updateStmt->execute([$newViewCount, $viewScore, $userId, $productId]);
        } else {
            // Create new interaction record with view
            $insertStmt = $pdo->prepare("INSERT INTO interactions (user_id, product_id, view_count, view_score, timestamp) VALUES (?, ?, 1, 1, ?)");
            $insertStmt->execute([$userId, $productId, time()]);
        }
    } catch (PDOException $e) {
        // Silently fail - don't interrupt user experience
        error_log("Error tracking view: " . $e->getMessage());
    }
}

// --- AUTHENTICATION LOGIC ---

// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } catch (PDOException $e) {
        $error = "Database error. Please try again.";
    }
}

// Signup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Check if email exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);

        if ($checkStmt->fetch()) {
            $error = "Email already registered.";
        } else {
            $userId = uniqid();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $insertStmt = $pdo->prepare("INSERT INTO users (id, name, email, password) VALUES (?, ?, ?, ?)");
            $insertStmt->execute([$userId, htmlspecialchars($name), $email, $hashedPassword]);

            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = htmlspecialchars($name);
            header("Location: index.php");
            exit;
        }
    } catch (PDOException $e) {
        $error = "Database error. Please try again.";
    }
}

// Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

// --- RATING HANDLING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?page=login");
        exit;
    }

    $pId = $_POST['product_id'];
    $rating = floatval($_POST['rating']);
    $userId = $_SESSION['user_id'];

    try {
        // 1. Save or update interaction (rating)
        $checkStmt = $pdo->prepare("SELECT * FROM interactions WHERE user_id = ? AND product_id = ?");
        $checkStmt->execute([$userId, $pId]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            // Update existing interaction (preserve view_count and view_score)
            $updateStmt = $pdo->prepare("UPDATE interactions SET rating = ?, timestamp = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            $updateStmt->execute([$rating, time(), $userId, $pId]);
        } else {
            // Create new interaction with rating (view_count and view_score default to 0)
            $insertStmt = $pdo->prepare("INSERT INTO interactions (user_id, product_id, rating, view_count, view_score, timestamp) VALUES (?, ?, ?, 0, 0, ?)");
            $insertStmt->execute([$userId, $pId, $rating, time()]);
        }

        // 2. Update Global Product Rating (Recalculate from all ratings)
        $ratingStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count FROM interactions WHERE product_id = ? AND rating IS NOT NULL");
        $ratingStmt->execute([$pId]);
        $ratingData = $ratingStmt->fetch();

        $avgRating = round(floatval($ratingData['avg_rating'] ?? 0), 1);
        $ratingCount = intval($ratingData['rating_count'] ?? 0);

        $updateProductStmt = $pdo->prepare("UPDATE products SET rating = ?, rating_count = ?, updated_at = NOW() WHERE id = ?");
        $updateProductStmt->execute([$avgRating, $ratingCount, $pId]);

        // Reload products to reflect changes
        $productsStmt = $pdo->query("SELECT * FROM products ORDER BY id");
        $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

        header("Location: index.php?page=detail&product_id=" . $pId . "&rated=true");
        exit;
    } catch (PDOException $e) {
        $error = "Error saving rating. Please try again.";
    }
}

// --- MESIN REKOMENDASI (Recommendation Engine) ---

/**
 * Mendapatkan Preferensi User (User Profiling)
 * Fungsi ini membaca history rating dan view score user untuk mengetahui selera mereka.
 */
function getUserPreferences($userId, $pdo)
{
    if (!$userId)
        return null;

    try {
        $stmt = $pdo->prepare("
            SELECT i.product_id, i.rating, i.view_score, p.category, p.brand 
            FROM interactions i
            INNER JOIN products p ON i.product_id = p.id
            WHERE i.user_id = ? AND (i.rating >= 4 OR i.view_score >= 2.0)
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows))
            return null;

        $cats = [];
        $brands = [];

        foreach ($rows as $r) {
            $cat = $r['category'] ?? 'Sneakers';
            // Weight: Rating counts more than views
            $w = $r['rating'] ? ($r['rating'] * 1.5) : ($r['view_score'] * 1.0);

            $cats[$cat] = ($cats[$cat] ?? 0) + $w;
            $brands[$r['brand']] = ($brands[$r['brand']] ?? 0) + $w;
        }

        arsort($cats);
        arsort($brands);

        return ['categories' => array_keys($cats), 'brands' => array_keys($brands)];
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Collaborative Filtering: Item-to-Item
 * "Users who liked THIS also liked THAT"
 * Menggunakan KNN-like approach berdasarkan intersection user interactions.
 */
function getCollaborativeScores($targetProductId, $pdo)
{
    if (!$targetProductId || !$pdo)
        return [];

    // Cari produk lain yang dilihat/dirating oleh user yang JUGA melihat targetProduct
    $sql = "
        SELECT 
            i2.product_id, 
            SUM(i2.view_score + (IFNULL(i2.rating, 0) * 2)) as interaction_weight
        FROM interactions i1
        JOIN interactions i2 ON i1.user_id = i2.user_id
        WHERE i1.product_id = ? 
          AND i2.product_id != ?
          AND (i2.view_score > 1 OR i2.rating >= 3)
        GROUP BY i2.product_id
        ORDER BY interaction_weight DESC
        LIMIT 20
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$targetProductId, $targetProductId]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [product_id => weight]
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Cold Start / Global Trending Scores
 * Untuk user yang belum login, gunakan data kolektif ("Wisdom of the Crowd").
 */
function getGlobalActivityScores($pdo)
{
    // Total skor = view_score + (rating * 2) dari semua user
    $sql = "
        SELECT product_id, SUM(view_score + (IFNULL(rating,0)*2)) as popularity
        FROM interactions
        GROUP BY product_id
    ";
    try {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        return [];
    }
}

function calculateUserScore($product, $userPrefs)
{
    if (!$userPrefs)
        return 0;
    $score = 0;

    // Preference Matching
    if (isset($product['category']) && in_array($product['category'], $userPrefs['categories'])) {
        $rank = array_search($product['category'], $userPrefs['categories']);
        $score += max(10, 40 - ($rank * 10));
    }
    if (in_array($product['brand'], $userPrefs['brands'])) {
        $rank = array_search($product['brand'], $userPrefs['brands']);
        $score += max(5, 25 - ($rank * 5));
    }
    return $score;
}

/**
 * HYBRID RECOMMENDATION ENGINE
 * 1. Content-Based (Attributes)
 * 2. Personalized (User History)
 * 3. Collaborative (User-driven Item similarity)
 * 4. Global Popularity (Cold start fallback)
 */
function getRecommendations($currentProduct, $allProducts, $userPrefs, $pdo)
{
    $recommendations = [];
    $scores = [];

    // 1. Get Collaborative Data (Who else liked this?)
    $collabScores = getCollaborativeScores($currentProduct['id'], $pdo);

    foreach ($allProducts as $p) {
        if ($p['id'] == $currentProduct['id'])
            continue;

        $score = 0;

        // A. Content Relevance
        if (isset($p['category']) && $p['category'] === ($currentProduct['category'] ?? ''))
            $score += 20;
        if ($p['brand'] === $currentProduct['brand'])
            $score += 10;

        // B. Personalization (if logged in)
        if ($userPrefs) {
            $score += calculateUserScore($p, $userPrefs);
        }

        // C. Collaborative Filtering Impact (Community Patterns)
        if (isset($collabScores[$p['id']])) {
            // Boost significantly if found in collaborative neighbor list
            $cScore = $collabScores[$p['id']];
            // Logarithmic boost to prevent older, massive products from dominating purely by count
            $score += min(50, $cScore * 2);
        }

        // D. Social Proof
        $rating = isset($p['rating']) ? floatval($p['rating']) : 0;
        $score += ($rating * 3);

        $score += rand(0, 3); // Serendipity
        $scores[$p['id']] = $score;
    }

    arsort($scores);
    $topIds = array_keys(array_slice($scores, 0, 4, true));

    foreach ($topIds as $id) {
        foreach ($allProducts as $p) {
            if ($p['id'] == $id) {
                $recommendations[] = $p;
                break;
            }
        }
    }
    return $recommendations;
}

/**
 * Render HTML Kartu Produk untuk efisiensi & AJAX
 */
function renderProductCard($product)
{
    ob_start();
    ?>
    <div x-data="{ hoverTimer: null }"
        @mouseenter="hoverTimer = setTimeout(() => { trackInteraction(<?= $product['id'] ?>) }, 2000)"
        @mouseleave="clearTimeout(hoverTimer)"
        class="group relative bg-white rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300 border border-gray-100 animate-fade-in-up">
        <a href="?page=detail&product_id=<?= $product['id'] ?>" class="block">
            <!-- Image -->
            <div class="relative aspect-square w-full bg-gray-100">
                <img src="<?= $product['image_url'] ?>" alt="<?= htmlspecialchars($product['product_name']) ?>"
                    loading="lazy"
                    class="h-full w-full object-cover object-center group-hover:scale-105 transition-transform duration-500">
                <?php if ($product['sale_price']): ?>
                    <span
                        class="absolute top-2 right-2 bg-red-600 text-white text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider">Sale</span>
                <?php endif; ?>

                <?php
                // Badges for Recommendation Logic Visualization
                if (isset($product['badge_type'])) {
                    if ($product['badge_type'] === 'new') {
                        // NEW Badge: Black text, Light Italic, No Rounded (Square), Visible background
                        echo '<span class="absolute top-0 left-0 bg-white/90 text-black text-[12px] font-light italic px-3 py-1 shadow-sm">New</span>';
                    }
                }
                ?>
            </div>
            <!-- Content -->
            <div class="p-4">
                <div class="flex justify-between items-center mb-1">
                    <h4 class="text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                        <?= htmlspecialchars($product['brand']) ?>
                    </h4>
                </div>
                <h3 class="text-sm font-bold text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-1 mb-2">
                    <?= htmlspecialchars($product['product_name']) ?>
                </h3>
                <div class="flex items-center gap-2">
                    <?php if ($product['sale_price']): ?>
                        <span class="text-sm font-bold text-red-600"><?= $product['sale_price'] ?></span>
                    <?php else: ?>
                        <span class="text-sm font-bold text-gray-900"><?= $product['original_price'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
    </div>
    <?php
    return ob_get_clean();
}

// --- LOGIKA PENCARIAN (Search Logic) ---
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($searchQuery) {
    try {
        $searchStmt = $pdo->prepare("
            SELECT * FROM products 
            WHERE product_name LIKE ? OR brand LIKE ? OR category LIKE ?
            ORDER BY id
        ");
        $searchTerm = "%{$searchQuery}%";
        $searchStmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $products = $searchStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalProducts = count($products);
        $displayProducts = array_slice($products, 0, 50);
    } catch (PDOException $e) {
        $error = "Search error. Please try again.";
        $products = [];
        $totalProducts = 0;
        $displayProducts = [];
    }
}

// --- LOGIKA TAMPILAN (View Logic) ---
$currentUser = isset($_SESSION['user_id']) ? true : false;
// Jika user login, hitung preferensinya dari database
$userPrefs = $currentUser ? getUserPreferences($_SESSION['user_id'], $pdo) : null;

// --- LOGIKA HOME PAGE FEED UTAMA
// Memproses Filter: Sale, New, Top Picks, atau All (Default)
if ($page === 'home' && !$searchQuery) {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

    try {
        $scoredProducts = [];

        if ($filter === 'sale') {
            // SALE: Produk diskon, acak (Rekomendasi ringan)
            // Mengambil produk yang memiliki sale_price
            $stmt = $pdo->query("SELECT * FROM products WHERE sale_price IS NOT NULL ORDER BY RAND() LIMIT 50");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $p) {
                // Beri score acak agar tetap ada variasi visual badge
                $p['recommendation_score'] = rand(50, 90);
                $scoredProducts[] = ['product' => $p, 'score' => $p['recommendation_score']];
            }

        } elseif ($filter === 'new') {
            // NEW RELEASES: Top 60 produk terbaru fixed.
            // Badge: "New" (Minimalist elegant low transparency)
            $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 60");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $p) {
                // Semua item di filter New Release mendapatkan badge NEW
                $p['badge_type'] = 'new';
                $scoredProducts[] = ['product' => $p, 'score' => 0]; // Score irrelevant for fixed list
            }

        } elseif ($filter === 'top-picks') {
            // TOP PICKS: Hanya untuk User Login (KNN / Collaborative / Personalized)
            // No Badges explicitly requested ("hapus logo bintang ... tulisan for you ...")
            if (!$currentUser) {
                header("Location: ?page=login");
                exit;
            }

            $allProductsStmt = $pdo->query("SELECT * FROM products LIMIT 300");
            $allProducts = $allProductsStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($allProducts as $p) {
                $score = calculateUserScore($p, $userPrefs);
                $score += (isset($p['rating']) ? floatval($p['rating']) * 2 : 0);
                $score += rand(0, 5);

                $p['recommendation_score'] = $score;
                // No badge_type set
                $scoredProducts[] = ['product' => $p, 'score' => $score];
            }
            usort($scoredProducts, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

        } else {
            // DEFAULT (SHOP ALL / HOME):
            // Load almost ALL products (Limit 1000 for safety, works with load more) for candidates.
            // Logic: "hapus semua tanda trending, sisakan 8 teratas saja secara roulette"
            $allProductsStmt = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 1000");
            $allProducts = $allProductsStmt->fetchAll(PDO::FETCH_ASSOC);

            // 1. Shuffle ALL candidates first to create "Roulette" base
            shuffle($allProducts);

            foreach ($allProducts as $index => $p) {
                // Top 8 from the shuffled list get "Trending" status (sorted to top), but NO badge displayed
                if ($index < 8) {
                    $score = 1000; // Force top sort
                } else {
                    $score = 0;
                }

                $scoredProducts[] = ['product' => $p, 'score' => $score];
            }

            // Sort: Trending (1000) first, then the rest.
            usort($scoredProducts, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });
        }

        $products = array_column($scoredProducts, 'product');
        $totalProducts = count($products); // Total in current filtered view
        $displayProducts = array_slice($products, 0, 50);

        // If total products in DB is needed for load more logic override
        if ($filter === 'all') {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM products");
            $totalProducts = $countStmt->fetchColumn();
        }

    } catch (PDOException $e) {
        $displayProducts = []; // Fallback empty
    }
}


// --- AJAX HANDLER: LOAD MORE ---
if (isset($_GET['action']) && $_GET['action'] === 'load_more') {
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit = 50;

    try {
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        if ($searchTerm) {
            $searchTermLike = "%{$searchTerm}%";
            $stmt = $pdo->prepare("
                SELECT * FROM products 
                WHERE product_name LIKE ? OR brand LIKE ? OR category LIKE ?
                ORDER BY id LIMIT " . intval($limit) . " OFFSET " . intval($offset) . "
            ");
            $stmt->execute([$searchTermLike, $searchTermLike, $searchTermLike]);
        } else {
            // NOTE: Ideally we should use the same sorted list for load more, but for simplicity we keep it standard unless full sophisticated session-based ID storage is used.
            // For now, standard ID order for load more on anonymous or non-personalized searches is acceptable fallback.
            $stmt = $pdo->query("SELECT * FROM products ORDER BY id LIMIT " . intval($limit) . " OFFSET " . intval($offset));
        }
        $pagedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pagedProducts as $p) {
            echo renderProductCard($p);
        }
    } catch (PDOException $e) {
        echo "<!-- Error loading products -->";
    }
    exit; // Stop execution here for AJAX
}

// Siapkan data untuk Tampilan Awal (Default / Fallback)
if (!isset($displayProducts)) {
    $totalProductsStmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $totalProducts = intval($totalProductsStmt->fetch()['total']); // Total in DB

    try {
        $displayStmt = $pdo->query("SELECT * FROM products ORDER BY id LIMIT 50");
        $displayProducts = $displayStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $displayProducts = [];
    }
}

$current_product = null;
$recommendations = [];

if ($page === 'detail' && isset($_GET['product_id'])) {
    try {
        $productStmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $productStmt->execute([$_GET['product_id']]);
        $current_product = $productStmt->fetch();

        if ($current_product) {
            // Reload all products for recommendations
            // OPTIMIZATION: Limit to random 100 items for variety without performance cost
            $allProductsStmt = $pdo->query("SELECT * FROM products ORDER BY RAND() LIMIT 100");
            $allProducts = $allProductsStmt->fetchAll(PDO::FETCH_ASSOC);
            $recommendations = getRecommendations($current_product, $allProducts, $userPrefs, $pdo);
        }
    } catch (PDOException $e) {
        $error = "Error loading product details.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNEAKEAZY</title>
    <!-- Resource Hints for Speed -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">

    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        function trackInteraction(productId) {
            const formData = new FormData();
            formData.append('action', 'track_interaction');
            formData.append('product_id', productId);
            formData.append('type', 'hover');

            fetch('index.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        console.log('Interaction detected:', productId, 'Score:', data.score);
                    }
                })
                .catch(err => console.error('Tracking error:', err));
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
            color: #ddd;
            transition: color 0.2s;
        }

        .star-rating input:checked~label,
        .star-rating label:hover,
        .star-rating label:hover~label {
            color: #fbbf24;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-50 text-slate-900 min-h-screen flex flex-col">

    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100 transition-all duration-300"
        x-data="{ searchOpen: false, mobileMenuOpen: false }">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 h-16 sm:h-20 flex items-center justify-between">

            <!-- Left Group: Burger & Logo -->
            <div class="flex items-center gap-3 sm:gap-4">
                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="lg:hidden p-2 -ml-2 text-gray-800 focus:outline-none hover:bg-gray-50 rounded-full transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>

                <!-- Logo -->
                <a href="index.php"
                    class="text-xl sm:text-2xl font-black tracking-tighter text-black lowercase flex-shrink-0"
                    style="font-family: 'Poppins', sans-serif;">
                    sneak<span class="text-blue-600">eazy</span>
                </a>
            </div>

            <!-- Desktop Nav -->
            <nav class="hidden lg:flex items-center gap-8 text-[13px] font-bold tracking-wider uppercase text-gray-800">
                <a href="index.php?filter=all" class="hover:text-blue-600 transition-colors">Shop By</a>
                <a href="index.php?filter=new" class="hover:text-blue-600 transition-colors">Releases</a>
                <a href="index.php?filter=brands" class="hover:text-blue-600 transition-colors">Brands</a>
                <a href="index.php?filter=sale" class="text-red-500 hover:text-red-600 transition-colors">Sale</a>
                <?php if ($currentUser): ?>
                    <a href="index.php?filter=foryou" class="text-blue-600">For You</a>
                <?php endif; ?>
            </nav>

            <!-- Right: Actions -->
            <div class="flex items-center gap-3 sm:gap-6">
                <!-- Search Trigger (Visible on Mobile now too) -->
                <div class="relative">
                    <button
                        onclick="document.getElementById('search-container').classList.toggle('hidden'); document.getElementById('search-input').focus();"
                        class="text-[13px] font-medium text-gray-400 uppercase tracking-wider hover:text-blue-500/80 transition-colors flex items-center gap-1">
                        <!-- Search Icon only on mobile, Text on desktop -->
                        <span class="hidden sm:inline">Search</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:hidden" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>
                    <!-- Search Dropdown -->
                    <div id="search-container"
                        class="hidden absolute right-0 top-full mt-4 w-80 bg-white shadow-xl border border-gray-100 p-3 rounded-xl z-50">
                        <form method="GET" action="index.php" class="relative">
                            <input id="search-input" type="text" name="search"
                                value="<?= htmlspecialchars($searchQuery ?? '') ?>" placeholder="Search..."
                                class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg pl-4 pr-10 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            <button type="submit"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Icons (Cart & User) -->
                <div class="flex items-center gap-4 sm:gap-5 text-gray-400 hover:text-blue-500/80">
                    <!-- Cart -->
                    <a href="#" class="hover:text-blue-600 transition-colors relative">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        <span
                            class="absolute -top-1.5 -right-1.5 bg-blue-500/80 text-white text-[9px] font-bold h-4 w-4 flex items-center justify-center rounded-full shadow-sm">0</span>
                    </a>

                    <!-- User Dropdown (Visible on Mobile) -->
                    <div class="relative" x-data="{ userOpen: false }">
                        <button @click="userOpen = !userOpen" @click.outside="userOpen = false"
                            class="hover:text-blue-600 transition-colors block focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="userOpen" style="display: none;"
                            class="absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50 text-left">
                            <?php if ($currentUser): ?>
                                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/50">
                                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Signed in as</p>
                                    <p class="text-sm font-bold text-gray-900 truncate">
                                        <?= htmlspecialchars($_SESSION['user_name']) ?>
                                    </p>
                                </div>
                                <a href="?action=logout"
                                    class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors font-medium">Log
                                    Out</a>
                            <?php else: ?>
                                <a href="?page=login" class="block px-4 py-2 text-sm text-gray-700 hover:text-blue-600">Log
                                    In</a>
                                <a href="?page=signup" class="block px-4 py-2 text-sm font-bold text-blue-600">Register</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Menu (Drawer) -->
        <!-- Mobile Menu (Drawer) -->
        <div x-show="mobileMenuOpen"
            class="lg:hidden absolute top-16 left-0 w-full bg-white border-b border-gray-100 shadow-xl p-6 flex flex-col gap-4 z-40"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">

            <!-- Simplified Menu Links (No Search) -->
            <nav class="flex flex-col gap-4 font-bold text-gray-800 text-lg">
                <a href="index.php?filter=all" class="flex items-center group py-2 border-b border-gray-50">
                    Shop All
                </a>
                <a href="index.php?filter=new" class="flex items-center group py-2 border-b border-gray-50">
                    New Releases
                </a>
                <a href="index.php?filter=sale"
                    class="flex items-center group py-2 border-b border-gray-50 text-red-500">
                    Sale
                </a>

                <?php if ($currentUser): ?>
                    <a href="index.php?filter=top-picks" class="flex items-center group py-2 text-blue-600">
                        Top Picks
                    </a>
                    <a href="?action=logout" class="text-sm font-normal text-gray-400 mt-2">Log Out
                        (<?= htmlspecialchars($_SESSION['user_name']) ?>)</a>
                <?php else: ?>
                    <div class="grid grid-cols-2 gap-3 mt-4">
                        <a href="?page=login"
                            class="text-center py-3 border border-gray-200 rounded-xl text-sm hover:border-gray-900 transition">Log
                            In</a>
                        <a href="?page=signup"
                            class="text-center py-3 bg-black text-white rounded-xl text-sm shadow-md hover:bg-gray-800 transition">Sign
                            Up</a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 mb-16">

        <!-- LOGIN PAGE -->
        <?php if ($page === 'login'): ?>
            <div class="max-w-md mx-auto bg-white p-8 rounded-2xl shadow-sm border border-gray-100 mt-10">
                <h2 class="text-2xl font-bold mb-6 text-center">Welcome Back</h2>
                <?php if ($error)
                    echo "<div class='bg-red-50 text-red-600 p-3 rounded mb-4 text-sm'>$error</div>"; ?>
                <form method="POST" class="flex flex-col gap-4">
                    <input type="hidden" name="action" value="login">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required
                            class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required
                            class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit"
                        class="w-full bg-black text-white font-bold py-3 rounded-lg hover:bg-gray-800 transition">Login</button>
                    <p class="text-center text-sm text-gray-500 mt-4">
                        Don't have an account? <a href="?page=signup" class="text-blue-600 font-bold">Sign up</a>
                    </p>
                </form>
            </div>

            <!-- SIGNUP PAGE -->
        <?php elseif ($page === 'signup'): ?>
            <div class="max-w-md mx-auto bg-white p-8 rounded-2xl shadow-sm border border-gray-100 mt-10">
                <h2 class="text-2xl font-bold mb-6 text-center">Join SNEAKEAZY</h2>
                <?php if ($error)
                    echo "<div class='bg-red-50 text-red-600 p-3 rounded mb-4 text-sm'>$error</div>"; ?>
                <form method="POST" class="flex flex-col gap-4">
                    <input type="hidden" name="action" value="signup">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" name="name" required
                            class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required
                            class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required
                            class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit"
                        class="w-full bg-black text-white font-bold py-3 rounded-lg hover:bg-gray-800 transition">Create
                        Account</button>
                    <p class="text-center text-sm text-gray-500 mt-4">
                        Already have an account? <a href="?page=login" class="text-blue-600 font-bold">Login</a>
                    </p>
                </form>
            </div>

            <!-- HOME PAGE -->
        <?php elseif ($page === 'home'): ?>
            <div class="mb-12 text-center md:text-left">
                <h2 class="text-4xl font-black mb-4 uppercase">Latest Drops</h2>
                <p class="text-gray-500 max-w-xl">
                    Discover the freshest kicks from the world's top brands.
                    <?php if (!$currentUser): ?>
                        <a href="?page=login" class="text-blue-600 underline">Login</a> to get personalized recommendations
                        based on your style.
                    <?php else: ?>
                        Curated just for you based on your taste.
                    <?php endif; ?>
                </p>
                <!-- Your Vibe section hidden as requested -->
            </div>

            <!-- Infinite Grid with Alpine.js -->
            <div x-data="{ 
                count: 50, 
                total: <?= $totalProducts ?>, 
                loading: false, 
                async load() {
                    if (this.count >= this.total) return;
                    this.loading = true;
                    // Simpan scope 'this' ke variabel agar aman di dalam fetch
                    let self = this;
                    const url = `?action=load_more&offset=${this.count}<?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>`;
                    
                    try {
                        const res = await fetch(url);
                        const html = await res.text();
                        if(html.trim()) {
                            // Gunakan $refs untuk append
                            $refs.productGrid.insertAdjacentHTML('beforeend', html);
                            self.count = Math.min(self.count + 50, self.total);
                        } else {
                             self.count = self.total; 
                        }
                    } catch(e) { console.error(e); }
                    self.loading = false;
                }
            }">

                <!-- GRID -->
                <div x-ref="productGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-6 mb-12">
                    <?php foreach ($displayProducts as $product): ?>
                        <?= renderProductCard($product); ?>
                    <?php endforeach; ?>
                </div>

                <!-- LOAD MORE UI -->
                <div class="text-center max-w-xs mx-auto mb-20" x-show="count < total">
                    <p class="text-sm font-bold text-gray-900 mb-2">
                        <span x-text="count"></span> out of <span x-text="total"></span> products
                    </p>

                    <!-- Progress Bar -->
                    <div class="w-full bg-gray-200 rounded-full h-1 mb-8 overflow-hidden">
                        <div class="bg-blue-600 h-1 rounded-full transition-all duration-500"
                            :style="`width: ${(count / total) * 100}%`"></div>
                    </div>

                    <button @click="load()" :disabled="loading"
                        class="px-10 py-3 border border-gray-900 text-gray-900 font-bold text-xs tracking-widest hover:bg-black hover:text-white transition-colors uppercase disabled:opacity-50 disabled:cursor-not-allowed w-full">
                        <span x-show="!loading">Load More</span>
                        <span x-show="loading">Loading...</span>
                    </button>
                </div>
            </div>

            <!-- DETAIL PAGE -->
        <?php elseif ($page === 'detail' && $current_product): ?>
            <?php
            // --- RECENTLY VIEWED TRACKING ---
            // Simpan history view ke Session
            if (!isset($_SESSION['recently_viewed'])) {
                $_SESSION['recently_viewed'] = [];
            }
            // Hapus jika sudah ada (agar nanti dipush ke paling depan/terbaru)
            if (($key = array_search($current_product['id'], $_SESSION['recently_viewed'])) !== false) {
                unset($_SESSION['recently_viewed'][$key]);
            }
            // Tambahkan ke depan
            array_unshift($_SESSION['recently_viewed'], $current_product['id']);
            // Limit simpan 10 item terakhir
            if (count($_SESSION['recently_viewed']) > 10) {
                array_pop($_SESSION['recently_viewed']);
            }
            ?>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex flex-col md:flex-row">
                    <!-- Image Side -->
                    <div class="w-full md:w-1/2 p-8 bg-gray-50 flex items-center justify-center relative">
                        <button onclick="history.back()"
                            class="absolute top-6 left-6 text-gray-400 hover:text-black transition">← Back</button>

                        <img src="<?= $current_product['image_url'] ?>"
                            alt="<?= htmlspecialchars($current_product['product_name']) ?>"
                            class="w-full max-w-sm object-contain drop-shadow-2xl hover:rotate-3 transition-transform duration-500">
                    </div>

                    <!-- Info Side -->
                    <div class="w-full md:w-1/2 p-8 md:p-12 flex flex-col justify-center">
                        <div class="flex items-center justify-between mb-4">
                            <span
                                class="text-blue-600 font-bold bg-blue-50 px-3 py-1 rounded-full text-xs uppercase tracking-wider">
                                <?= $current_product['brand'] ?>
                            </span>

                            <!-- Rating HIDDEN -->
                            <div class="flex items-center gap-1 text-sm font-medium hidden">
                                <span class="text-yellow-500 text-lg">★</span>
                                <span><?= $current_product['rating'] ?></span>
                                <span class="text-gray-400">(<?= $current_product['rating_count'] ?> reviews)</span>
                            </div>
                        </div>

                        <h1 class="text-4xl font-black text-gray-900 mb-6 leading-tight">
                            <?= htmlspecialchars($current_product['product_name']) ?>
                        </h1>

                        <p class="text-gray-500 mb-8 leading-relaxed">
                            Discover the perfect blend of style and performance.
                            Ranked highly in <span
                                class="font-bold text-gray-700"><?= isset($current_product['category']) ? $current_product['category'] : 'Sneakers' ?></span>.
                        </p>

                        <div class="flex items-end gap-4 mb-10">
                            <?php if ($current_product['sale_price']): ?>
                                <span class="text-3xl font-bold text-gray-900"><?= $current_product['sale_price'] ?></span>
                                <span
                                    class="text-lg text-gray-400 line-through mb-1"><?= $current_product['original_price'] ?></span>
                            <?php else: ?>
                                <span class="text-3xl font-bold text-gray-900"><?= $current_product['original_price'] ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col gap-6">
                            <button
                                class="w-full bg-black text-white font-bold py-4 rounded-xl hover:bg-gray-800 transition shadow-lg shadow-gray-200"
                                onclick="alert('Added to Cart!')">
                                Add to Cart
                            </button>

                            <!-- RATING FORM (Authorized Only) -->
                            <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
                                <h4 class="font-bold text-gray-900 mb-1">Rate this Pair</h4>
                                <?php if ($currentUser): ?>
                                    <p class="text-xs text-gray-500 mb-4">Help us improve your recommendations.</p>
                                    <form method="POST" action="index.php" class="flex items-center justify-between">
                                        <input type="hidden" name="action" value="rate">
                                        <input type="hidden" name="product_id" value="<?= $current_product['id'] ?>">

                                        <div class="star-rating flex flex-row-reverse justify-end text-2xl gap-1">
                                            <input type="radio" id="star5" name="rating" value="5" /><label
                                                for="star5">★</label>
                                            <input type="radio" id="star4" name="rating" value="4" /><label
                                                for="star4">★</label>
                                            <input type="radio" id="star3" name="rating" value="3" /><label
                                                for="star3">★</label>
                                            <input type="radio" id="star2" name="rating" value="2" /><label
                                                for="star2">★</label>
                                            <input type="radio" id="star1" name="rating" value="1" /><label
                                                for="star1">★</label>
                                        </div>

                                        <button type="submit"
                                            class="text-xs font-bold bg-white border border-gray-200 hover:border-black px-4 py-2 rounded-lg transition">
                                            Submit
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <p class="text-sm text-gray-500"><a href="?page=login"
                                            class="text-blue-600 font-bold underline">Login</a> to rate this product.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- RECOMMENDATIONS: Horizontal Interactive Swipe -->
            <div class="mt-16">
                <div class="flex items-end justify-between mb-8">
                    <div>
                        <h3 class="text-2xl font-black text-gray-900">You Might Also Like</h3>
                        <?php if ($currentUser && $userPrefs): ?>
                            <p class="text-sm text-gray-500 mt-1">Based on this item + your personal history.</p>
                        <?php else: ?>
                            <p class="text-sm text-gray-500 mt-1">Curated selection based on category and popularity.</p>
                        <?php endif; ?>
                    </div>
                    <!-- Swipe indicators hint (desktop) -->
                    <div class="hidden md:flex gap-2 text-gray-300">
                        <span class="text-xs">Scroll for more →</span>
                    </div>
                </div>

                <!-- Container Carousel -->
                <div
                    class="flex overflow-x-auto gap-4 pb-8 -mx-4 px-4 sm:mx-0 sm:px-0 scrollbar-hide snap-x snap-mandatory">
                    <?php foreach ($recommendations as $rec): ?>
                        <div
                            class="bg-white rounded-xl overflow-hidden border border-gray-100 hover:shadow-md transition flex-shrink-0 w-44 sm:w-56 snap-start">
                            <a href="?page=detail&product_id=<?= $rec['id'] ?>" class="block h-full">
                                <div class="relative aspect-square w-full bg-gray-50 p-4">
                                    <img src="<?= $rec['image_url'] ?>" alt="<?= htmlspecialchars($rec['product_name']) ?>"
                                        class="h-full w-full object-contain mix-blend-multiply hover:scale-110 transition-transform duration-300"
                                        loading="lazy">
                                    <?php if ($rec['sale_price']): ?>
                                        <span
                                            class="absolute top-2 right-2 bg-red-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded">SALE</span>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3">
                                    <div class="text-[10px] font-bold text-gray-400 uppercase mb-1"><?= $rec['brand'] ?></div>
                                    <div class="text-xs font-bold text-gray-900 truncate mb-1">
                                        <?= htmlspecialchars($rec['product_name']) ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- RECENTLY VIEWED SECTION -->
            <?php
            // Logic ambil data Recently Viewed dari Session
            // Kecualikan produk yang sedang dilihat sekarang agar tidak duplikat visual
            $recentIds = isset($_SESSION['recently_viewed']) ? $_SESSION['recently_viewed'] : [];
            $recentIdsToShow = array_filter($recentIds, function ($id) use ($current_product) {
                return $id != $current_product['id'];
            });
            $recentIdsToShow = array_slice($recentIdsToShow, 0, 3); // Ambil 3 teratas
        
            if (!empty($recentIdsToShow)) {
                $placeholders = implode(',', array_fill(0, count($recentIdsToShow), '?'));
                $rvStmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
                $rvStmt->execute($recentIdsToShow);
                $rvProductsRaw = $rvStmt->fetchAll(PDO::FETCH_ASSOC);

                // Urutkan kembali sesuai urutan di session (karena IN query tidak menjamin urutan)
                $rvProducts = [];
                foreach ($recentIdsToShow as $id) {
                    foreach ($rvProductsRaw as $p) {
                        if ($p['id'] == $id) {
                            $rvProducts[] = $p;
                            break;
                        }
                    }
                }
                ?>
                <!-- Reduced Spacing & Horizontal Swipe -->
                <div class="mt-4 border-t border-gray-100 pt-8">
                    <h3 class="text-2xl font-black text-gray-900 mb-4">Recently Viewed</h3>
                    <div
                        class="flex overflow-x-auto gap-4 pb-4 -mx-4 px-4 sm:mx-0 sm:px-0 scrollbar-hide snap-x snap-mandatory">
                        <?php foreach ($rvProducts as $rv): ?>
                            <div
                                class="bg-white rounded-xl overflow-hidden border border-gray-100 hover:shadow-md transition flex-shrink-0 w-32 sm:w-44 snap-start">
                                <a href="?page=detail&product_id=<?= $rv['id'] ?>" class="block h-full">
                                    <div class="relative aspect-square w-full bg-gray-50 p-3">
                                        <img src="<?= $rv['image_url'] ?>" alt="<?= htmlspecialchars($rv['product_name']) ?>"
                                            class="h-full w-full object-contain mix-blend-multiply hover:scale-110 transition-transform duration-300">
                                    </div>
                                    <div class="p-2">
                                        <div class="text-[9px] font-bold text-gray-400 uppercase mb-0.5"><?= $rv['brand'] ?></div>
                                        <div class="text-[10px] sm:text-xs font-bold text-gray-900 truncate">
                                            <?= htmlspecialchars($rv['product_name']) ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php } ?>

        <?php endif; ?>

    </main>

    <footer class="bg-white border-t border-gray-200 py-12 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <a href="index.php"
                class="inline-block text-2xl font-black tracking-tighter text-gray-300 lowercase mb-4 hover:text-gray-900 transition-colors"
                style="font-family: 'Poppins', sans-serif;">
                sneak<span class="text-blue-400">eazy</span>
            </a>
            <p class="text-gray-400 text-sm">© 2025 sneakeazy Inc. All rights reserved.</p>
        </div>
    </footer>

</body>

</html>