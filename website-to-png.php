#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Cookies\Cookie;

// Parse command line arguments
if ($argc < 2) {
    echo "Usage: php website-to-pdf.php <url> [options]\n";
    echo "Options:\n";
    echo "  --output=<filename.png>    Output filename (default: domain.png)\n";
    echo "  --cookie-file=<file.json>  JSON file containing cookies exported from browser\n";
    echo "  --user-agent=<string>      Custom user agent string\n";
    echo "  --wait=<seconds>           Wait time in seconds after page load (default: 2)\n";
    echo "  --window-width=<pixels>    Browser window width in pixels (default: 1920)\n";
    echo "  --window-height=<pixels>   Browser window height in pixels (default: 1080)\n";
    echo "\nExamples:\n";
    echo "  php website-to-pdf.php https://example.com --output=example.pdf\n";
    echo "  php website-to-pdf.php https://example.com --cookie-file=cookies.json\n";
    echo "  php website-to-pdf.php https://example.com --wait=5\n";
    echo "  php website-to-pdf.php https://example.com --window-width=1366 --window-height=768\n";
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

// Set default output filename based on the URL if not provided
$outputFile = isset($options['output']) ? $options['output'] : parse_url($url, PHP_URL_HOST);

try {
    echo "Preparing to fetch content from $url...\n";

    // Create browser factory
    $browserFactory = new BrowserFactory();

    // Get window dimensions from options or use defaults
    $windowWidth = isset($options['window-width']) ? $options['window-width'] : 1920;
    $windowHeight = isset($options['window-height']) ? $options['window-height'] : 1080;

    // Create a browser instance
    $browser = $browserFactory->createBrowser([
        'windowSize' => [$windowWidth, $windowHeight],
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

		//bitbucket cloud specific
        //special code to collapse bitbucket sidebar and build menu.
		try{
			$page->mouse()->find('button[aria-label=\"Current project sidebar\"]')->click();
            usleep(500);
            $page->mouse()->find('div[data-testid=\"emptyChecksCountHeader\"]')->click();
		} catch (Exception $e) {
			echo "Error: " . $e->getMessage() . "\n";
		}
		sleep(1);

        // Get page content
        //$html = $page->getHtml();

        //Can generate PDF if we want.
		// $pdf_options = [
		// 	'landscape'           => true,             // default to false
		// 	'printBackground'     => true,             // default to false
		// 	//'displayHeaderFooter' => true,             // default to false
		// 	//'preferCSSPageSize'   => true,             // default to false (reads parameters directly from @page)
		// 	//'marginTop'           => 0.0,              // defaults to ~0.4 (must be a float, value in inches)
		// 	//'marginBottom'        => 1.4,              // defaults to ~0.4 (must be a float, value in inches)
		// 	//'marginLeft'          => 5.0,              // defaults to ~0.4 (must be a float, value in inches)
		// 	//'marginRight'         => 1.0,              // defaults to ~0.4 (must be a float, value in inches)
		// 	//'paperWidth'          => 6.0,              // defaults to 8.5 (must be a float, value in inches)
		// 	//'paperHeight'         => 6.0,              // defaults to 11.0 (must be a float, value in inches)
		// 	//'headerTemplate'      => '<div>foo</div>', // see details above
		// 	//'footerTemplate'      => '<div>foo</div>', // see details above
		// 	//'scale'               => 1.2,              // defaults to 1.0 (must be a float)
		// ];
        // $pdf = $page->pdf($pdf_options);
		// echo "saving to file $outputFile\n";
		// $pdf->saveToFile($outputFile);

		//save screenshot as a full page screenshot
		$screenshot = $page->screenshot([
            'captureBeyondViewport' => true,
            'clip' => $page->getFullPageClip(),
            'format' => 'png', // default to 'png' - possible values: 'png', 'jpeg', 'webp'
        ]);
		$screenshot->saveToFile($outputFile . '.png');


		echo "Done!\n";
        // Close browser
        $browser->close();
        exit(0);

    } catch (Exception $e) {
        if (isset($browser)) {
            $browser->close();
        }
        throw $e;
        exit(1);
    }


} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}