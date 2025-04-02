#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/WebsiteScreenshot.php';

// Parse command line arguments
if ($argc < 2) {
    echo "Usage: php website-to-png.php <url> [options]\n";
    echo "Options:\n";
    echo "  --output=<filename>        Output filename (default: domain)\n";
    echo "  --cookie-file=<file.json>  JSON file containing cookies exported from browser\n";
    echo "  --user-agent=<string>      Custom user agent string\n";
    echo "  --wait=<seconds>           Wait time in seconds after page load (default: 2)\n";
    echo "  --window-width=<pixels>    Browser window width in pixels (default: 1920)\n";
    echo "  --window-height=<pixels>   Browser window height in pixels (default: 1080)\n";
    echo "\nExamples:\n";
    echo "  php website-to-png.php https://example.com --output=example\n";
    echo "  php website-to-png.php https://example.com --cookie-file=cookies.json\n";
    echo "  php website-to-png.php https://example.com --wait=5\n";
    echo "  php website-to-png.php https://example.com --window-width=1366 --window-height=768\n";
    exit(1);
}

$url = $argv[1];
$options = [];

// Parse options
for ($i = 2; $i < $argc; $i++) {
    if (strpos($argv[$i], '--output=') === 0) {
        $options['output'] = substr($argv[$i], 9);
    } elseif (strpos($argv[$i], '--cookie-file=') === 0) {
        $options['cookie-file'] = substr($argv[$i], 14);
    } elseif (strpos($argv[$i], '--user-agent=') === 0) {
        $options['user-agent'] = substr($argv[$i], 13);
    } elseif (strpos($argv[$i], '--wait=') === 0) {
        $options['wait'] = (int)substr($argv[$i], 7);
    } elseif (strpos($argv[$i], '--window-width=') === 0) {
        $options['window-width'] = (int)substr($argv[$i], 15);
    } elseif (strpos($argv[$i], '--window-height=') === 0) {
        $options['window-height'] = (int)substr($argv[$i], 16);
    } elseif (strpos($argv[$i], '--') !== 0) {
        // Backward compatibility for just providing output filename
        $options['output'] = $argv[$i];
    }
}

try {
    echo "Preparing to fetch content from $url...\n";

    // Create WebsiteScreenshot instance with URL and options
    $screenshot = new WebsiteScreenshot($url, $options);

    // Show configuration details
    if (isset($options['cookie-file'])) {
        echo "Loading cookies from {$options['cookie-file']}...\n";
    }

    echo "Navigating to $url...\n";

    $waitTime = $options['wait'] ?? 2;
    echo "Waiting {$waitTime} seconds for JavaScript execution...\n";

    // Capture the screenshot
    $outputFile = $screenshot->capture();

    echo "Done! Screenshot saved to: $outputFile\n";
    exit(0);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}