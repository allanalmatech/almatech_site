<?php
require_once 'includes/db.php';
$db = $GLOBALS['db'] ?? $mysqli ?? null;

if ($db) {
    // Default about settings
    $aboutSettings = [
        'hero_title' => 'About Us',
        'hero_subtitle' => 'We help businesses build, fix, and scale with technology.',
        'story' => 'We deliver modern websites, branding, ICT support, and digital growth strategies that are practical for real businesses in Uganda.',
        'mission' => 'To provide dependable ICT and digital services that help organizations operate efficiently, look professional online, and grow through technology.',
        'vision' => 'To be a leading technology partner for businesses across Uganda and the region—delivering systems, websites, and support that last.',
        'values' => "Integrity\nQuality\nSpeed\nSupport"
    ];
    
    $jsonValue = json_encode($aboutSettings, JSON_UNESCAPED_UNICODE);
    
    // Insert or update the settings using correct column names
    $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES ('about_settings', ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    $stmt->bind_param("s", $jsonValue);
    
    if ($stmt->execute()) {
        echo "✅ About settings have been set up successfully!\n";
        echo "You can now visit:\n";
        echo "- About page: /about.php\n";
        echo "- Admin settings: /admin/about/index.php\n";
        echo "\nDefault content added:\n";
        echo "- Hero Title: " . $aboutSettings['hero_title'] . "\n";
        echo "- Hero Subtitle: " . $aboutSettings['hero_subtitle'] . "\n";
        echo "- Mission: " . $aboutSettings['mission'] . "\n";
        echo "- Vision: " . $aboutSettings['vision'] . "\n";
        echo "- Values: " . str_replace("\n", ", ", $aboutSettings['values']) . "\n";
    } else {
        echo "❌ Error: " . $stmt->error . "\n";
    }
    
    $stmt->close();
} else {
    echo "❌ Database connection failed\n";
}
?>
