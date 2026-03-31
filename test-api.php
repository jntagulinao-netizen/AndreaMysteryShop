<?php
echo "=== Testing Products API ===\n";
$json = file_get_contents('http://localhost/AndreaMysteryShop/api/get-products.php');
$data = json_decode($json, true);
echo "Products Count: " . count($data) . "\n";
if (count($data) > 0) {
    echo "First Product: " . json_encode($data[0], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "NO PRODUCTS FOUND!\n";
    echo "Raw Response: " . $json . "\n";
}
?>
