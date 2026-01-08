<?php
// setup_reza.php

$usersFile = 'data/users.json';
$interactionsFile = 'data/interactions.json';
$productsFile = 'data/products.json';

// 1. Load Data
$users = json_decode(file_get_contents($usersFile), true);
$interactions = json_decode(file_get_contents($interactionsFile), true);
$products = json_decode(file_get_contents($productsFile), true);

// 2. Create/Update Reza
$rezaEmail = 'reza@sneakeazy.com';
$rezaId = 'user_reza_demo';
$rezaName = 'Reza';

// Remove existing Reza if any
$users = array_filter($users, function ($u) use ($rezaEmail) {
    return $u['email'] !== $rezaEmail;
});

// Add Reza
$users[] = [
    'id' => $rezaId,
    'name' => $rezaName,
    'email' => $rezaEmail,
    'password' => password_hash('password', PASSWORD_DEFAULT) // Password is 'password'
];

// 3. Create Interactions for Reza
// remove old reza interactions
$interactions = array_filter($interactions, function ($i) use ($rezaId) {
    return $i['user_id'] !== $rezaId;
});

// Reza loves 'NIKE' and 'Basketball' and 'Jordan'
// He gives 5 stars to these.
// He gives 1 star to 'VANS' (just to contrast).

$count = 0;
foreach ($products as $p) {
    $brand = strtoupper($p['brand']);
    $cat = strtoupper($p['category'] ?? '');
    $name = strtoupper($p['product_name']);

    // LIKE
    if ($brand === 'NIKE' || strpos($name, 'JORDAN') !== false || $cat === 'BASKETBALL') {
        $interactions[] = [
            'user_id' => $rezaId,
            'product_id' => $p['id'],
            'rating' => 5,
            'timestamp' => time()
        ];
        $count++;
    }

    // DISLIKE
    if ($brand === 'VANS' || strpos($name, 'SLIP-ON') !== false) {
        $interactions[] = [
            'user_id' => $rezaId,
            'product_id' => $p['id'],
            'rating' => 2,
            'timestamp' => time()
        ];
    }
}

// 4. Save
file_put_contents($usersFile, json_encode(array_values($users), JSON_PRETTY_PRINT));
file_put_contents($interactionsFile, json_encode(array_values($interactions), JSON_PRETTY_PRINT));

echo "Created User: Reza (reza@sneakeazy.com / password)\n";
echo "Generated $count positive interactions for Nike/Jordan/Basketball items.\n";
?>