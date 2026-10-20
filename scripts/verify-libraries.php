<?php

/**
 * Check Composer installs and module-declared browser assets without a database.
 * Run: ddev exec php scripts/verify-libraries.php
 */

use Composer\InstalledVersions;
use Symfony\Component\Yaml\Yaml;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$composer = json_decode(file_get_contents($root . '/composer.json'), TRUE, 512, JSON_THROW_ON_ERROR);
$directories = [];
foreach ($composer['repositories'] as $repository) {
  if ($repository['type'] !== 'package') {
    continue;
  }
  foreach ($repository['package'] as $package) {
    if ($package['type'] !== 'drupal-library') {
      continue;
    }
    $directory = 'libraries/' . $package['extra']['installer-name'];
    if (!InstalledVersions::isInstalled($package['name']) ||
        realpath(InstalledVersions::getInstallPath($package['name'])) !== realpath($root . '/' . $directory)) {
      throw new RuntimeException("Missing or incorrectly installed package: {$package['name']}");
    }
    $directories[$directory] = TRUE;
  }
}
if (!$directories) {
  throw new RuntimeException('No managed libraries found');
}

$assets = [];
$used_directories = [];
foreach (['audiofield', 'colorbox', 'fitvids', 'responsive_menu'] as $module) {
  $definitions = Yaml::parseFile("$root/modules/contrib/$module/$module.libraries.yml");
  foreach ($definitions as $definition) {
    $paths = array_keys($definition['js'] ?? []);
    foreach ($definition['css'] ?? [] as $group) {
      $paths = array_merge($paths, array_keys($group));
    }
    foreach ($paths as $path) {
      $path = ltrim($path, '/');
      $directory = implode('/', array_slice(explode('/', $path), 0, 2));
      if (isset($directories[$directory])) {
        $assets[$path] = TRUE;
        $used_directories[$directory] = TRUE;
      }
    }
  }
}

foreach (array_keys($directories) as $directory) {
  if (!isset($used_directories[$directory])) {
    throw new RuntimeException("No module asset declarations found for $directory");
  }
}
foreach (array_keys($assets) as $path) {
  if (!is_file($root . '/' . $path)) {
    throw new RuntimeException("Missing module asset: $path");
  }
  echo "PASS: $path\n";
}

// Optional HTTP checks when a local site origin is provided.
$origin = getenv('LIBRARY_CHECK_ORIGIN');
if ($origin) {
  $client = new GuzzleHttp\Client(['base_uri' => rtrim($origin, '/') . '/']);
  $verify_tls = !str_ends_with(parse_url($origin, PHP_URL_HOST) ?? '', '.ddev.site');
  foreach (array_keys($assets) as $path) {
    $response = $client->get($path, ['verify' => $verify_tls, 'http_errors' => FALSE]);
    $type = $response->getHeaderLine('Content-Type');
    $contents = (string) $response->getBody();
    if ($response->getStatusCode() !== 200 || str_contains($type, 'text/html') ||
        !hash_equals(hash_file('sha256', "$root/$path"), hash('sha256', $contents))) {
      throw new RuntimeException("Asset is not served intact: $path");
    }
  }
  echo "PASS: All declared assets served intact over HTTP\n";
}
echo 'Verified ' . count($directories) . ' libraries and ' . count($assets) . " module asset paths.\n";
