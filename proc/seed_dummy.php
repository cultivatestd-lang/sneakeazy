<?php
// seed_dummy.php
// Script untuk generate 100 dummy users & interaksi random untuk Collaborative Filtering

require_once 'config/database.php';

try {
    $pdo = getDBConnection();

    // UI Header
    echo '<div style="font-family: sans-serif; padding: 20px; max-width: 800px; margin: 0 auto;">';
    echo "<h1>🤖 Dummy Data Generator (Auto-Interactions)</h1>";
    echo "<p>Generating datasets for K-NN Collaborative Filtering...</p>";
    echo "<hr>";

    // 1. Ambil semua Product ID
    $stmt = $pdo->query("SELECT id, product_name FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $productIds = array_column($products, 'id');

    if (empty($productIds)) {
        die("<h3 style='color:red'>Error: No products found. Please seed products first.</h3>");
    }

    echo "<p>✅ Found " . count($productIds) . " products in database.</p>";

    // 2. Generate 100 Dummy Users
    $userCount = 100;
    $usersCreated = 0;
    $interactionsCreated = 0;

    // Prepare Statements biar cepat
    $stmtUser = $pdo->prepare("INSERT IGNORE INTO users (id, name, email, password) VALUES (?, ?, ?, ?)");
    $stmtInteraction = $pdo->prepare("INSERT IGNORE INTO interactions (user_id, product_id, rating, view_count, view_score, timestamp) VALUES (?, ?, ?, ?, ?, ?)");

    // Begin Transaction biar ngebut
    $pdo->beginTransaction();

    for ($i = 1; $i <= $userCount; $i++) {
        // Data User
        $uniqInfo = uniqid();
        $uid = "dummy_" . $uniqInfo . "_" . $i;
        $name = "User Bot " . $i;
        $email = "bot" . $i . "_" . $uniqInfo . "@sneakeazy.test";
        $pass = password_hash("secret123", PASSWORD_DEFAULT);

        $stmtUser->execute([$uid, $name, $email, $pass]);
        $usersCreated++;

        // 3. Generate Random Interactions per User
        // Setiap bot akan berinteraksi dengan 5 sampai 25 produk acak
        $numInteractions = rand(5, 25);
        $randomKeys = array_rand($productIds, $numInteractions);
        if (!is_array($randomKeys))
            $randomKeys = [$randomKeys];

        foreach ($randomKeys as $k) {
            $pid = $productIds[$k];

            // Tentukan Tipe Interaksi (Rating vs View vs Add to Cart simulasi)
            $randType = rand(1, 100);

            $rating = null;
            $view_count = 1;
            $view_score = 1; // Score dasar

            if ($randType <= 60) {
                // 60% Kemungkinan Memberi Rating (1.0 - 5.0)
                // Bias ke rating bagus (3.5 - 5.0) agar rekomendasi tidak jelek semua
                $baseRating = rand(35, 50) / 10.0;
                // Tapi ada 20% kemungkinan rating jelek (1.0 - 3.0)
                if (rand(1, 5) == 1) {
                    $baseRating = rand(10, 30) / 10.0;
                }
                $rating = $baseRating;
                $view_score += 5; // Rating dianggap bobot tinggi
            } elseif ($randType <= 90) {
                // 30% Cuma View (No Rating)
                $view_count = rand(1, 10);
                $view_score += $view_count;
            } else {
                // 10% Simulasi "Add to Cart" / "Love" (Rating Tinggi + View Banyak)
                $rating = 5.0;
                $view_count = rand(5, 15);
                $view_score += 10;
            }

            // Timestamp acak dalam 60 hari terakhir
            $time = time() - rand(0, 60 * 24 * 60 * 60);

            $stmtInteraction->execute([
                $uid,
                $pid,
                $rating,
                $view_count,
                $view_score,
                $time
            ]);
            $interactionsCreated++;
        }
    }

    $pdo->commit();

    echo "<h2 style='color:green'>🎉 SUCCESS!</h2>";
    echo "<ul>";
    echo "<li><strong>$usersCreated</strong> Dummy Users Created.</li>";
    echo "<li><strong>$interactionsCreated</strong> Interactions Generated.</li>";
    echo "</ul>";
    echo "<p>Your Collaborative Filtering Algorithm now has rich data to process!</p>";
    echo "<br><a href='/admin' style='background:black; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Check Stats</a>";
    echo "</div>";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("<h1>Database Error</h1><p>" . $e->getMessage() . "</p>");
}
