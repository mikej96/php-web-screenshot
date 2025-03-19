#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Cookies\Cookie;
use Mpdf\Mpdf;

// Parse command line arguments
if ($argc < 2) {
    echo "Usage: php website-to-pdf.php <url> [options]\n";
    echo "Options:\n";
    echo "  --output=<filename.pdf>    Output filename (default: domain.pdf)\n";
    echo "  --cookie-file=<file.json>  JSON file containing cookies exported from browser\n";
    echo "  --user-agent=<string>      Custom user agent string\n";
    echo "  --wait=<seconds>           Wait time in seconds after page load (default: 2)\n";
    echo "\nExamples:\n";
    echo "  php website-to-pdf.php https://example.com --output=example.pdf\n";
    echo "  php website-to-pdf.php https://example.com --cookie-file=cookies.json\n";
    echo "  php website-to-pdf.php https://example.com --wait=5\n";
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
    } elseif (strpos($argv[$i], '--') !== 0) {
        // Backward compatibility for just providing output filename
        $options['output'] = $argv[$i];
    }
}

// Set default output filename based on the URL if not provided
$outputFile = isset($options['output']) ? $options['output'] : parse_url($url, PHP_URL_HOST) . '.pdf';

try {
    echo "Preparing to fetch content from $url...\n";

    // Create browser factory
    $browserFactory = new BrowserFactory();

    // Create a browser instance
    $browser = $browserFactory->createBrowser([
        'windowSize' => [1280, 1024],
        'enableImages' => true,
        'ignoreCertificateErrors' => true,
        'headers' => [
            'User-Agent' => $options['user-agent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ]
    ]);

    try {
        // Create a page
        $page = $browser->createPage();

        // Handle cookies if provided
        if (isset($options['cookie-file'])) {
            if (!file_exists($options['cookie-file'])) {
                echo "Error: Cookie file not found: {$options['cookie-file']}\n";
                exit(1);
            }

            echo "Loading cookies from {$options['cookie-file']}...\n";

            // Load cookies from file
            $cookieData = json_decode(file_get_contents($options['cookie-file']), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "Error: Invalid JSON in cookie file\n";
                exit(1);
            }

            // Get target domain from URL
            $urlParts = parse_url($url);
            $targetDomain = $urlParts['host'];

            // Prepare cookies array
            $cookies = [];
            foreach ($cookieData as $cookie) {
                // Skip cookies that don't match our target domain
                if (!isset($cookie['domain']) || strpos($targetDomain, ltrim($cookie['domain'], '.')) === false) {
                    continue;
                }

                // Create cookie object
                $expires = isset($cookie['expirationDate']) ? (int)$cookie['expirationDate'] : null;
                $cookieOptions = [
                    'domain' => $cookie['domain'] ?? '',
                    'path' => $cookie['path'] ?? '/',
                ];

                if ($expires) {
                    $cookieOptions['expires'] = $expires;
                }

                if (isset($cookie['secure'])) {
                    $cookieOptions['secure'] = $cookie['secure'];
                }

                if (isset($cookie['httpOnly'])) {
                    $cookieOptions['httpOnly'] = $cookie['httpOnly'];
                }

                $cookies[] = Cookie::create(
                    $cookie['name'] ?? '',
                    $cookie['value'] ?? '',
                    $cookieOptions
                );
            }

            // Set cookies
            if (!empty($cookies)) {
                echo "Setting " . count($cookies) . " cookies...\n";
                $page->setCookies($cookies)->await();
            }
        }

        // Now navigate to the URL with cookies already set
        echo "Navigating to $url...\n";
        $navigation = $page->navigate($url);
        $navigation->waitForNavigation();

        // Wait additional time for JavaScript execution if specified
        $waitTime = isset($options['wait']) ? $options['wait'] : 2;
        echo "Waiting {$waitTime} seconds for JavaScript execution...\n";
        sleep($waitTime);

        // Get page content
        $html = $page->getHtml();

        // Close browser
        $browser->close();

    } catch (Exception $e) {
        if (isset($browser)) {
            $browser->close();
        }
        throw $e;
    }

    // For debugging: Uncomment to see the HTML
    // echo $html;
    // exit;

    echo "Converting to PDF...\n";

    // Create mPDF instance
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 10,
        'margin_bottom' => 10,
    ]);

    // Set document properties
    $mpdf->SetTitle("Web page: $url");
    $mpdf->SetAuthor("Website to PDF Converter");

    // Write HTML to PDF
    $mpdf->WriteHTML($html);

    // Save the PDF
    $mpdf->Output($outputFile, 'F');

    echo "PDF saved successfully as $outputFile\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}