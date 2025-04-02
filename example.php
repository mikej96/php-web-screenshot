<?php

// Include the WebsiteScreenshot class
require_once __DIR__ . '/WebsiteScreenshot.php';

// Basic usage
echo "Taking basic screenshot...\n";
$basic = new WebsiteScreenshot('https://example.com');
try {
    $path = $basic->capture();
    echo "Screenshot saved to: $path\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Advanced usage with options
echo "\nTaking custom screenshot...\n";
$advanced = new WebsiteScreenshot('https://github.com');
$advanced->setOutput('github-screenshot')
         ->setWaitTime(5)
         ->setWindowSize(1366, 768);

try {
    $path = $advanced->capture();
    echo "Screenshot saved to: $path\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Usage with cookie file (if you have one)
// $authenticated = new WebsiteScreenshot('https://example.com/dashboard');
// $authenticated->setCookieFile('cookies.json');
// $path = $authenticated->capture();
// echo "Authenticated screenshot saved to: $path\n";