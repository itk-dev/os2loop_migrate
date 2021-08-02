<?php

namespace Drupal\os2loop_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Gets mail notifications interval for a user.
 *
 * @MigrateProcessPlugin(
 *   id = "os2loop_mail_notifications_interval"
 * )
 */
class MailNotificationsInterval extends ProcessPluginBase {

  /**
   * Map from user id to notification interval.
   *
   * @var array
   */
  private $userNotificationIntervals;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (NULL === $this->userNotificationIntervals) {
      $this->userNotificationIntervals = $this->loadUserNotificationIntervals();
    }

    return $this->userNotificationIntervals[$value] ?? NULL;
  }

  /**
   * Load user notification intervals from database.
   *
   * @return array
   *   The user notification intervals.
   */
  private function loadUserNotificationIntervals() {
    $rows = Database::getConnection(
      $this->configuration['connection']['target'] ?? 'default',
      $this->configuration['connection']['key'] ?? 'migrate'
    )
      ->select('loop_notification_user_settings', 't')
      ->fields('t')
      ->execute()->fetchAll();

    $userNotificationIntervals = [];
    foreach ($rows as $row) {
      $userNotificationIntervals[$row->uid] = $row->enabled ? (int) $row->update_interval : 0;
    }

    return $userNotificationIntervals;
  }

}
