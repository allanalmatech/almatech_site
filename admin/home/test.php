<?php
// admin/home/test.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "DEBUG: Test file loaded<br>";

try {
    require_once __DIR__ . '/../../includes/helpers.php';
    echo "DEBUG: helpers.php loaded<br>";
    
    require_once __DIR__ . '/../../includes/db.php';
    echo "DEBUG: db.php loaded<br>";
    
    require_once __DIR__ . '/../includes/settings_lib.php';
    echo "DEBUG: settings_lib.php loaded<br>";
    
    $db = $GLOBALS['db'] ?? $mysqli ?? null;
    if ($db instanceof mysqli) {
        echo "DEBUG: DB connection OK<br>";
        
        $home = setting_get_json($db, 'home_settings', []);
        echo "DEBUG: Home settings loaded: ";
        var_dump($home);
    } else {
        echo "DEBUG: DB connection failed<br>";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}

echo "DEBUG: Test completed<br>";
?>
