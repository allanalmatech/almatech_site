<?php
// Simple database seeder for about settings
require_once 'includes/db.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;

if (!$db) {
    die("❌ Database connection failed\n");
}

echo "🌱 Seeding about settings...\n";

// Default about settings
$aboutSettings = [
    'hero_title' => 'About Alma Tech Consults',
    'hero_subtitle' => 'We help businesses build, fix, and scale with technology.',
    'story' => 'We deliver modern websites, branding, ICT support, and digital growth strategies that are practical for real businesses in Uganda. Our team combines technical expertise with business understanding to deliver solutions that actually work.',
    'mission' => 'To provide dependable ICT and digital services that help organizations operate efficiently, look professional online, and grow through technology.',
    'vision' => 'To be a leading technology partner for businesses across Uganda and the region—delivering systems, websites, and support that last.',
    'values' => "Integrity\nQuality\nSpeed\nSupport"
];

$jsonValue = json_encode($aboutSettings, JSON_UNESCAPED_UNICODE);

try {
    // Insert or update using the working pattern
    $sql = "INSERT INTO settings (`key`, `value`) VALUES ('about_settings', ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)";
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        throw new RuntimeException("Prepare failed: " . $db->error);
    }
    
    $stmt->bind_param("s", $jsonValue);
    
    if ($stmt->execute()) {
        echo "✅ About settings seeded successfully!\n\n";
        echo "📝 Content added:\n";
        echo "   Hero Title: " . $aboutSettings['hero_title'] . "\n";
        echo "   Hero Subtitle: " . $aboutSettings['hero_subtitle'] . "\n";
        echo "   Story: " . substr($aboutSettings['story'], 0, 50) . "...\n";
        echo "   Mission: " . $aboutSettings['mission'] . "\n";
        echo "   Vision: " . $aboutSettings['vision'] . "\n";
        echo "   Values: " . str_replace("\n", ", ", $aboutSettings['values']) . "\n\n";
        echo "🔗 You can now:\n";
        echo "   • View public page: /about.php\n";
        echo "   • Edit in admin: /admin/about/index.php\n";
        echo "   • Manage in settings: /admin/settings/index.php (About Page tab)\n";
    } else {
        throw new RuntimeException("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Seeding complete!\n";
?>
