<?php

/**
 * Integration checks against an upgraded, populated local site (no content writes).
 * Run: ddev drush php:script scripts/verify-drupal11.php
 */

function verify_upgrade(bool $condition, string $message): void {
  if (!$condition) {
    throw new \RuntimeException($message);
  }
  echo "PASS: $message\n";
}

verify_upgrade(str_starts_with(\Drupal::VERSION, '11.'), 'Drupal 11 is running');
$modules = \Drupal::moduleHandler();
verify_upgrade($modules->moduleExists('book'), 'Book remains enabled');
verify_upgrade($modules->getModule('book')->getPath() === 'modules/contrib/book', 'Book is provided by contrib');
verify_upgrade(!$modules->moduleExists('tour'), 'Tour is uninstalled');

$database = \Drupal::database();
$orphans = $database->query('SELECT COUNT(*) FROM {book} b LEFT JOIN {node} n ON n.nid = b.nid WHERE n.nid IS NULL')->fetchField();
verify_upgrade((int) $orphans === 0, 'Every book outline entry has a node');
$nids = \Drupal::entityQuery('node')->condition('type', 'book')->condition('status', 1)->accessCheck(FALSE)->execute();
verify_upgrade(count($nids) > 0, 'Published Book content exists');
$outline_ids = $database->select('book', 'b')->fields('b', ['nid'])->execute()->fetchCol();
$audio_ids = $database->select('node__field_mp3_player', 'a')->fields('a', ['entity_id'])->execute()->fetchCol();
$origin = rtrim(\Drupal::request()->getSchemeAndHttpHost(), '/');
$client = \Drupal::httpClient();
// This site's existing /book alias shadows the generic listing route.
foreach (array_merge(['/'], array_map(static fn ($nid) => '/node/' . $nid, array_values($nids))) as $path) {
  // The local DDEV certificate may not be trusted inside the container.
  $verify_tls = !str_ends_with(\Drupal::request()->getHost(), '.ddev.site');
  $response = $client->get($origin . $path, ['verify' => $verify_tls, 'http_errors' => FALSE]);
  $html = (string) $response->getBody();
  verify_upgrade($response->getStatusCode() === 200, "$path returns HTTP 200 anonymously");
  verify_upgrade(!str_contains($html, 'The website encountered an unexpected error'), "$path renders successfully");
  $nid = str_starts_with($path, '/node/') ? substr($path, 6) : NULL;
  if ($nid !== NULL && in_array($nid, $outline_ids)) {
    verify_upgrade(str_contains($html, 'class="book-navigation"'), "$path renders the custom book navigation");
  }
  if ($nid !== NULL && in_array($nid, $audio_ids)) {
    verify_upgrade(str_contains($html, 'sm2-bar-ui') && str_contains($html, '.mp3'), "$path renders its SoundManager player and MP3 link");
  }
}

$controller = \Drupal::classResolver()->getInstanceFromDefinition(\Drupal\book\Controller\BookController::class);
$listing = $controller->bookRender();
verify_upgrade(!empty($listing['#items']), 'Book listing controller finds the existing book');

// Build editor forms without submitting or saving them.
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(\Drupal\user\Entity\User::load(1));
try {
  $form = \Drupal::formBuilder()->getForm(\Drupal\scn\Form\SCNSettingsForm::class);
  $roles = $form['fieldset']['scn_roles']['#options'];
  verify_upgrade(!isset($roles['anonymous']) && isset($roles['authenticated']), 'Comment notification roles build correctly');
  $node = \Drupal\node\Entity\Node::load(reset($nids));
  $form = \Drupal::service('entity.form_builder')->getForm($node, 'edit');
  verify_upgrade(isset($form['book']), 'Existing Book page edit form includes the book outline');
}
finally {
  $switcher->switchBack();
}

echo "Upgrade integration checks completed.\n";
