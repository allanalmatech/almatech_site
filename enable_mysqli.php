<?php
$phpIniPath = 'C:\xampp\php8\php.ini';
$content = file_get_contents($phpIniPath);

// Uncomment mysqli extension
$content = preg_replace('/;extension=mysqli/', 'extension=mysqli', $content);

// Save the modified php.ini
file_put_contents($phpIniPath, $content);

echo "MySQLi extension has been enabled in php.ini\n";
echo "Please restart Apache for changes to take effect.\n";
?>
