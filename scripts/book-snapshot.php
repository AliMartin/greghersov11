<?php

/**
 * Read-only content fingerprint. Run with: ddev drush php:script scripts/book-snapshot.php
 * Save output before and after an upgrade and compare with diff.
 */

$database = \Drupal::database();
$tables = ['book', 'path_alias', 'path_alias_revision'];
foreach ($database->schema()->findTables('node%') as $table) {
  $tables[] = $table;
}
sort($tables);
$snapshot = [];
foreach (array_unique($tables) as $table) {
  if (!$database->schema()->tableExists($table)) {
    throw new \RuntimeException("Missing content table: $table");
  }
  $rows = $database->select($table, 't')->fields('t')->execute()->fetchAll(\PDO::FETCH_ASSOC);
  $encoded = array_map(static function (array $row): string {
    ksort($row);
    return json_encode($row, JSON_THROW_ON_ERROR);
  }, $rows);
  sort($encoded, SORT_STRING);
  $snapshot[$table] = ['rows' => count($rows), 'sha256' => hash('sha256', implode("\n", $encoded))];
}
echo json_encode($snapshot, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
