<?php

/**
 * WebsiteScreenshot - A wrapper class for capturing website screenshots
 */
class WebsiteScreenshot
{
    private $url;
    private $options = [
        'output' => null,
        'cookie-file' => null,
        'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'wait' => 2,
        'window-width' => 1920,
        'window-height' => 1080
    ];

    /**
     * Create a new WebsiteScreenshot instance
     *
     * @param string $url URL to capture
     * @param array $options Optional configuration options
     */
    public function __construct(string $url, array $options = [])
    {
        $this->url = $url;

        // Merge user options with defaults
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $this->options)) {
                $this->options[$key] = $value;
            }
        }

        // Set default output filename based on URL if not provided
        if ($this->options['output'] === null) {
            $this->options['output'] = parse_url($url, PHP_URL_HOST);
        }
    }

    /**
     * Set the output filename
     *
     * @param string $filename Output filename
     * @return $this
     */
    public function setOutput(string $filename)
    {
        $this->options['output'] = $filename;
        return $this;
    }

    /**
     * Set the cookie file path
     *
     * @param string $cookieFile Path to JSON cookie file
     * @return $this
     */
    public function setCookieFile(string $cookieFile)
    {
        $this->options['cookie-file'] = $cookieFile;
        return $this;
    }

    /**
     * Set the user agent string
     *
     * @param string $userAgent Custom user agent string
     * @return $this
     */
    public function setUserAgent(string $userAgent)
    {
        $this->options['user-agent'] = $userAgent;
        return $this;
    }

    /**
     * Set wait time after page load
     *
     * @param int $seconds Seconds to wait
     * @return $this
     */
    public function setWaitTime(int $seconds)
    {
        $this->options['wait'] = $seconds;
        return $this;
    }

    /**
     * Set browser window dimensions
     *
     * @param int $width Window width in pixels
     * @param int $height Window height in pixels
     * @return $this
     */
    public function setWindowSize(int $width, int $height)
    {
        $this->options['window-width'] = $width;
        $this->options['window-height'] = $height;
        return $this;
    }

    /**
     * Capture the screenshot
     *
     * @param bool $returnPath Whether to return the path to the saved screenshot
     * @return string|bool Path to screenshot file if $returnPath is true, otherwise true on success
     * @throws Exception If capturing fails
     */
    public function capture(bool $returnPath = true)
    {
        require_once __DIR__ . '/vendor/autoload.php';

        try {
            // Create browser factory
            $browserFactory = new \HeadlessChromium\BrowserFactory();

            // Create a browser instance
            $browser = $browserFactory->createBrowser([
                'windowSize' => [$this->options['window-width'], $this->options['window-height']],
                'enableImages' => true,
                'ignoreCertificateErrors' => true,
                'headers' => [
                    'User-Agent' => $this->options['user-agent'],
                ]
            ]);

            // Create a page
            $page = $browser->createPage();

            // Handle cookies if provided
            if ($this->options['cookie-file']) {
                if (!file_exists($this->options['cookie-file'])) {
                    throw new Exception("Cookie file not found: {$this->options['cookie-file']}");
                }

                // Load cookies from file
                $cookieData = json_decode(file_get_contents($this->options['cookie-file']), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON in cookie file");
                }

                // Get target domain from URL
                $urlParts = parse_url($this->url);
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

                    $cookies[] = \HeadlessChromium\Cookies\Cookie::create(
                        $cookie['name'] ?? '',
                        $cookie['value'] ?? '',
                        $cookieOptions
                    );
                }

                // Set cookies
                if (!empty($cookies)) {
                    $page->setCookies($cookies)->await();
                }
            }

            // Navigate to the URL
            $navigation = $page->navigate($this->url);
            $navigation->waitForNavigation();

            // Wait additional time for JavaScript execution
            sleep($this->options['wait']);

            // Bitbucket cloud specific handling
			//See if 'bitbucket' is in the url
			if (strpos($this->url, 'bitbucket') !== false) {
				try {
					$page->mouse()->find('button[aria-label=\"Current project sidebar\"]')->click();
					usleep(500);
					$page->mouse()->find('div[data-testid=\"emptyChecksCountHeader\"]')->click();
				} catch (Exception $e) {
					// Ignore errors for non-Bitbucket sites
				}
			}//end if bitbucket.
            sleep(1);

            // Save screenshot as a full page screenshot
            $outputFile = $this->options['output'];
            $screenshot = $page->screenshot([
                'captureBeyondViewport' => true,
                'clip' => $page->getFullPageClip(),
                'format' => 'png',
            ]);
            $screenshot->saveToFile($outputFile . '.png');

            // Close browser
            $browser->close();

            return $returnPath ? $outputFile . '.png' : true;

        } catch (Exception $e) {
            if (isset($browser)) {
                $browser->close();
            }
            throw $e;
        }
    }
}