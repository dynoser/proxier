## dynoser\webtools\Proxier

### Introduction

The `Proxier` class is designed to work as a proxy for web requests.

Optional features:

- replace any headers from original request to specified
- cache responses with code 200 for the specified time (default 3600 seconds)

Requirements

- PHP 5.6 or higher.
- cURL extension enabled.

### Installation

All code is in one src/Proxier.php file, you may include only this file.

or, you may use `composer require dynoser/proxier`

or, include the package in your `composer.json` file and run `composer update`.

### Features

- **URL and Header Manipulation**: Allows encoding and decoding of URLs and request headers using Base64url.
- **Caching Mechanism**: (optional) Caches successful responses (code 200) for a specified duration.

### Usage

To use this class, you need to create a file accessible via the web with code approximately like this:

```php
// (optional) specify directory for cache. if the cacheBaseDir is not specified, it will work without caching
$cacheBaseDir = "[YOUR ROOT PATH]/cache/proxier";

// load Proxier class without autoload, or use 'vendor/autoload.php' instead
$chkFile = "[YOUR ROOT PATH]/vendor/dynoser/proxier/src/Proxier.php";
require  $chkFile;

// required: create object and setup parameters: url, rep, cachesec.
// Only the URL is required, other parameters are optional.
$p = new \dynoser\webtools\Proxier($_REQUEST['url'] ?? '', $_REQUEST['rep'] ?? '', $_REQUEST['cachesec'] ?? '');

// (optional)
if (!empty($cacheBaseDir)) {
    $p->setCacheBaseDir($cacheBaseDir, true);
}

// run required
$p->run();

// remove old-cache-data periodicaly
//   (or you may call this fuction by cron instead)
$p->removeCachePeriodically();

```

#### Remote call:

In order to use a proxy request, you can insert something like the following code when creating a web page:

```php
        $urlB64 = Proxier::makeUrlPar($url);

        echo '<img src="[URL WHERE PLACED YOUR SCRIPT]/proxier.php?' . $urlB64 . '" />';

```

### Contribution
Feel free to contribute or suggest improvements via GitHub.

### License
This class is open-sourced software licensed under the MIT license.

### Support
For issues and features requests, please file an issue on the GitHub repository.
