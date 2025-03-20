# CLI Website Screenshot Utility

A simple PHP command-line tool that screenshots websites with full JavaScript support.

## Requirements

- PHP 7.4 or higher
- Composer
- Chrome/Chromium (required for headless browser functionality)

## Installation

1. Clone this repository
2. Run `composer install` to install dependencies
3. Make sure Chrome or Chromium is installed

## Usage

```bash
php website-to-png.php <url> [options]
```

### Options

```
--output=<filename.png>    Output filename (default: domain.png)
--cookie-file=<file.json>  JSON file containing cookies exported from browser
--user-agent=<string>      Custom user agent string
--wait=<seconds>           Wait time in seconds after page load (default: 2)
```

### Setting Wait Time for JavaScript

You can specify how long to wait for JavaScript execution with the `--wait` option:

```bash
php website-to-png.php https://example.com --wait=5
```

### Authentication with Browser Cookies

Since this tool uses a headless Chrome browser, the best way to handle authentication is through cookies:

1. Install a cookie export extension in your browser (like "EditThisCookie" for Chrome)
2. Log in to the website in your browser
3. Export the cookies to a JSON file
4. Use the `--cookie-file` option:

```bash
php website-to-png.php https://example.com --cookie-file=cookies.json
```

### Examples

Convert a website (default 2-second wait for JavaScript):
```bash
php website-to-png.php https://example.com
```

Convert a website and wait longer for JavaScript execution:
```bash
php website-to-png.php https://example.com --wait=10 --output=example-site.png
```

## How it works

This script uses Chrome headless browser to:
1. Navigate to the specified URL
2. Wait for the page to fully render with JavaScript
3. Capture a full page screenshot. 

## Limitations

- Some websites actively block headless browsers
- Very complex websites may still not render perfectly
- Login sessions expire based on the same rules as in your normal browser
- You must log in with your normal browser and export the cookies to a json file. 