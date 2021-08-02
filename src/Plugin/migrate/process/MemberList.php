<?php

namespace Drupal\os2loop_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Gets info on member list visibility.
 *
 * @MigrateProcessPlugin(
 *   id = "os2loop_member_list"
 * )
 */
class MemberList extends ProcessPluginBase {

  /**
   * Map from user id to member list visibility.
   *
   * @var array
   */
  private $memberListVisibility;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (NULL === $this->memberListVisibility) {
      $this->memberListVisibility = $this->loadMemberListVisibility();
    }

    return $this->memberListVisibility[$value] ?? 0;
  }

  /**
   * Load user notification intervals from database.
   *
   * @return array
   *   The user notification intervals.
   */
  private function loadMemberListVisibility() {
    $table = 'field_data_field_show_in_contact_list';
    $field = 'field_show_in_contact_list_value';
    if ('external' === $this->configuration['type']) {
      $table = 'field_data_field_show_in_contact_list_publi';
      $field = 'field_show_in_contact_list_publi_value';
    }

    $connection = Database::getConnection(
      $this->configuration['connection']['target'] ?? 'default',
      $this->configuration['connection']['key'] ?? 'migrate'
    );
    if (!$connection->schema()->tableExists($table)) {
      return [];
    }

    return $connection
      ->select($table, 't')
      ->fields('t', ['entity_id', $field])
      ->condition('entity_type', 'user')
      ->execute()
      ->fetchAllKeyed();
  }

}
