<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

echo "<h2>Full System Test - PostgreSQL</h2>";

// Test 1: Database connection
echo "<h3>1. Database Connection:</h3>";
try {
    $test = $db->query("SELECT 'PostgreSQL' as db, version() as version")->fetch();
    echo "✅ Connected to: " . $test['db'] . "<br>";
    echo "✅ Version: " . substr($test['version'], 0, 50) . "...<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

// Test 2: Check data
echo "<h3>2. Data Check:</h3>";
$tables = ['users', 'products', 'orders', 'payments', 'financial_records'];
foreach ($tables as $table) {
    $sql = "SELECT COUNT(*) as count FROM $table";
    $result = $db->query($sql)->fetch();
    echo "• $table: " . $result['count'] . " records<br>";
}

// Test 3: Test product functions
echo "<h3>3. Product Functions:</h3>";
$products = getFeaturedProducts(3);
echo "✅ Featured products: " . count($products) . "<br>";

// Test 4: Format price
echo "<h3>4. Utility Functions:</h3>";
echo "✅ Price format: " . formatPrice(29990000) . "<br>";

// Test 5: Test cart
echo "<h3>5. Cart System:</h3>";
$_SESSION['cart'] = [1 => 2]; // Add product ID 1, quantity 2
$cartItems = getCartItems();
echo "✅ Cart items: " . count($cartItems) . "<br>";

// Test 6: Test financial
echo "<h3>6. Financial System:</h3>";
$financial = getFinancialSummary();
echo "✅ Revenue: " . formatPrice($financial['total_revenue'] ?? 0) . "<br>";
echo "✅ Expense: " . formatPrice($financial['total_expense'] ?? 0) . "<br>";

echo "<hr>";
echo "<h3>✅ System Ready!</h3>";
echo "Open: <a href='http://localhost/laptopstore/'>http://localhost/laptopstore/</a>";
?>