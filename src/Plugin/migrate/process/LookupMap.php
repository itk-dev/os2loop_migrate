<?php

namespace Drupal\os2loop_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\StaticMap;
use Drupal\migrate\Row;

/**
 * Changes the source value based on a lookup map loaded from a database.
 *
 * Available configuration keys:
 * - source: The input value - either a scalar or an array.
 * - table: the table name
 * - from: the from column
 * - to: the to column
 * - connection:
 *     key: the database key, defaults to 'migrate'
 *     target: the database target, defaults to 'default'
 *
 * Examples:
 *
 * Map role id (rid) to role name.
 *
 * @code
 * process:
 *   bar:
 *     plugin: os2loop_lookup_map
 *     source: roles
 *     table: role
 *     from: rid
 *     to: name
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "os2loop_lookup_map"
 * )
 */
class LookupMap extends StaticMap {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!isset($this->configuration['map'])) {
      $this->configuration['map'] = $this->loadMap();
    }
    return parent::transform($value, $migrate_executable, $row, $destination_property);
  }

  /**
   * Load map from source database.
   *
   * @return array
   *   The lookup map.
   */
  private function loadMap() {
    return Database::getConnection(
      $this->configuration['connection']['target'] ?? 'default',
      $this->configuration['connection']['key'] ?? 'migrate'
    )
      ->select($this->configuration['table'], 't')
      ->fields('t', [$this->configuration['from'], $this->configuration['to']])
      ->execute()->fetchAllKeyed();
  }

}
