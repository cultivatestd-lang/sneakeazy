<?php
// stats.php - Monitoring & Export Data Dashboard
// Updated for Cloud Deployment
session_start();
require_once 'config/database.php';

$pdo = getDBConnection();

// --- LOGIC: HANDLE CSV EXPORT ---
if (isset($_GET['export'])) {
    $type = $_GET['export'];
    $filename = $type . "_" . date('Y-m-d_H-i-s') . ".csv";

    // Set header agar browser download file
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    if ($type == 'users') {
        // Header Kolom CSV
        fputcsv($output, ['ID', 'Name', 'Email', 'Created At']);
        
        // Ambil Data
        $stmt = $pdo->query("SELECT id, name, email, created_at FROM users ORDER BY created_at DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
    } 
    elseif ($type == 'interactions') {
        fputcsv($output, ['ID', 'User Email', 'Product Name', 'Action Type', 'Rating', 'Timestamp']);
        
        // Join table agar data lebih bermakna (ada nama produk & email user)
        $sql = "SELECT i.id, u.email, p.product_name, 
                CASE 
                    WHEN i.rating IS NOT NULL THEN 'Rating' 
                    ELSE 'View' 
                END as action_type,
                i.rating, i.created_at
                FROM interactions i
                LEFT JOIN users u ON i.user_id = u.id
                LEFT JOIN products p ON i.product_id = p.id
                ORDER BY i.created_at DESC";
        
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
    }

    fclose($output);
    exit(); // Stop script agar HTML tidak ikut ter-download
}

// --- LOGIC: FETCH DASHBOARD DATA ---
// 1. Total Stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalInteractions = $pdo->query("SELECT COUNT(*) FROM interactions")->fetchColumn();

// 2. Recent Users (Limit 5)
$recentUsers = $pdo->query("SELECT name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// 3. Recent Interactions (Limit 10)
$recentInteractionsSql = "
    SELECT u.name as user_name, p.product_name, i.rating, i.created_at
    FROM interactions i
    LEFT JOIN users u ON i.user_id = u.id
    LEFT JOIN products p ON i.product_id = p.id
    ORDER BY i.created_at DESC LIMIT 10
";
$recentInteractions = $pdo->query($recentInteractionsSql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Stats - SneakEasy</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800">

    <!-- Navbar Sederhana -->
    <nav class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center shadow-sm">
        <div class="font-bold text-xl tracking-tight">SneakEasy <span class="text-blue-600">Analytics</span></div>
        <a href="index.php" class="text-sm font-medium text-gray-500 hover:text-black">Back to App &rarr;</a>
    </nav>

    <div class="max-w-6xl mx-auto px-6 py-10">
        
        <!-- Header -->
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Dashboard Monitor</h1>
                <p class="text-gray-500 mt-1">Real-time data from Google Cloud SQL</p>
            </div>
            <div class="flex gap-3">
                <a href="?export=users" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 hover:text-black transition flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Export Users CSV
                </a>
                <a href="?export=interactions" class="bg-black text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-800 transition flex items-center gap-2 shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Export Interactions CSV
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <!-- Card 1 -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <div class="text-gray-500 text-sm font-medium mb-1">Total Users</div>
                <div class="text-4xl font-bold text-gray-900"><?php echo number_format($totalUsers); ?></div>
            </div>
            <!-- Card 2 -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <div class="text-gray-500 text-sm font-medium mb-1">Total Products</div>
                <div class="text-4xl font-bold text-gray-900"><?php echo number_format($totalProducts); ?></div>
            </div>
            <!-- Card 3 -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <div class="text-gray-500 text-sm font-medium mb-1">Interactions (Views/Ratings)</div>
                <div class="text-4xl font-bold text-blue-600"><?php echo number_format($totalInteractions); ?></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Table: Recent Users -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-900">Newest Users</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 font-medium">Name</th>
                                <th class="px-6 py-3 font-medium">Email</th>
                                <th class="px-6 py-3 font-medium">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($recentUsers) > 0): ?>
                                <?php foreach ($recentUsers as $u): ?>
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-6 py-3 font-medium text-gray-900"><?php echo htmlspecialchars($u['name']); ?></td>
                                    <td class="px-6 py-3 text-gray-500"><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td class="px-6 py-3 text-gray-400 text-xs"><?php echo date('d M Y, H:i', strtotime($u['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="px-6 py-4 text-center text-gray-400">No users yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Table: Recent Activity -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-900">Recent Activity</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 font-medium">User</th>
                                <th class="px-6 py-3 font-medium">Action</th>
                                <th class="px-6 py-3 font-medium">Product</th>
                                <th class="px-6 py-3 font-medium text-right">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($recentInteractions) > 0): ?>
                                <?php foreach ($recentInteractions as $i): ?>
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-6 py-3 font-medium text-gray-900">
                                        <?php echo htmlspecialchars($i['user_name'] ?: 'Guest'); ?>
                                    </td>
                                    <td class="px-6 py-3">
                                        <?php if ($i['rating']): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                ★ <?php echo $i['rating']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">
                                                Viewed
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-3 text-gray-600 truncate max-w-[150px]" title="<?php echo htmlspecialchars($i['product_name']); ?>">
                                        <?php echo htmlspecialchars($i['product_name']); ?>
                                    </td>
                                    <td class="px-6 py-3 text-right text-gray-400 text-xs">
                                        <?php echo date('H:i', strtotime($i['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="px-6 py-4 text-center text-gray-400">No recent activity.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div class="mt-10 text-center text-xs text-gray-400">
            <p>&copy; <?php echo date('Y'); ?> SneakEasy Admin Panel. All data is served from Google Cloud SQL.</p>
        </div>

    </div>

</body>
</html>
