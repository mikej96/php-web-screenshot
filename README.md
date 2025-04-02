# PHP Website Screenshot

A PHP library for capturing screenshots of websites using headless Chrome.

## Requirements

- PHP 7.2 or higher
- Chrome/Chromium installed on the server
- [chrome-php/chrome](https://github.com/chrome-php/chrome) package

## Installation

1. Clone this repository or copy the `WebsiteScreenshot.php` file to your project
2. Install dependencies:

```bash
composer require chrome-php/chrome
```

## Usage

### Basic Usage

```php
<?php
// Include the class
require_once 'WebsiteScreenshot.php';

// Create a new screenshot instance
$screenshot = new WebsiteScreenshot('https://example.com');

// Capture the screenshot
try {
    $screenshotPath = $screenshot->capture();
    echo "Screenshot saved to: $screenshotPath";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Advanced Usage

You can customize the screenshot with various options:

```php
<?php
require_once 'WebsiteScreenshot.php';

// Create with options in constructor
$screenshot = new WebsiteScreenshot('https://example.com', [
    'output' => 'my-screenshot',      // Output filename (without extension)
    'wait' => 5,                      // Wait 5 seconds after page load
    'window-width' => 1366,           // Custom window width
    'window-height' => 768,           // Custom window height
]);

// Or set options using fluent methods
$screenshot = new WebsiteScreenshot('https://example.com');
$screenshot->setOutput('my-screenshot')
           ->setWaitTime(5)
           ->setWindowSize(1366, 768)
           ->setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X)');

// Capture
$screenshotPath = $screenshot->capture();
```

### Using Cookies

You can authenticate with websites by providing a cookie file:

```php
<?php
require_once 'WebsiteScreenshot.php';

$screenshot = new WebsiteScreenshot('https://example.com');
$screenshot->setCookieFile('path/to/cookies.json');
$screenshotPath = $screenshot->capture();
```

The cookie file should be in JSON format as exported from a browser.

### Integration with External Applications

To integrate with your existing PHP application:

1. Copy the `WebsiteScreenshot.php` file to your project
2. Make sure dependencies are installed via Composer
3. Create an instance of the class and call methods as needed

Example integration with a framework controller:

```php
<?php
namespace App\Controllers;

class ScreenshotController
{
    public function takeScreenshot($url)
    {
        require_once 'path/to/WebsiteScreenshot.php';

        $screenshot = new \WebsiteScreenshot($url);
        $screenshot->setOutput('screenshots/' . uniqid());

        try {
            $path = $screenshot->capture();
            return ['status' => 'success', 'path' => $path];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
```

## License

MIT