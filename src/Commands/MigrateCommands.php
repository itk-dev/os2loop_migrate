<?php

namespace Drupal\os2loop_migrate\Commands;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\Entity\Media;
use Drush\Commands\DrushCommands;

/**
 * A drush command file.
 *
 * @package Drupal\os2loop_migrate\Commands
 */
class MigrateCommands extends DrushCommands {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Migrate files to media.
   *
   * @command os2loop:migrate:files-to-media
   * @usage os2loop:migrate:files-to-media
   */
  public function migrateFilesToMedia() {
    $this->output()->writeln('Deleting all media');
    $storage = $this->entityTypeManager->getStorage('media');
    foreach ($storage->loadMultiple() as $media) {
      $media->delete();
    }
    $this->output()->writeln('');

    $migrations = [

      'User images' => [
        'sql' => "SELECT * FROM field_data_field_user_image WHERE entity_type = 'user'",
        // Map from result row bundle.
        'user' => [
          'media' => [
            'bundle' => 'os2loop_media_image',
            'field_name' => 'field_media_image',
            'fields' => [
              'target_id' => 'field_user_image_fid',
              'alt' => 'field_user_image_alt',
            ],
          ],
          'entity' => [
            'bundle' => 'user',
            'media_field_name' => 'os2loop_user_image',
          ],
        ],
      ],

      'Node files' => [
        'sql' => "SELECT * FROM field_data_field_file_upload WHERE entity_type = 'node'",
        'post' => [
          'media' => [
            'bundle' => 'os2loop_media_file',
            'field_name' => 'field_media_file',
            'fields' => [
              'target_id' => 'field_file_upload_fid',
            ],
          ],
          'entity' => [
            'bundle' => 'node',
            'media_field_name' => 'os2loop_question_file',
          ],
        ],

        'page' => [
          'media' => [
            'bundle' => 'os2loop_media_file',
            'field_name' => 'field_media_file',
            'fields' => [
              'target_id' => 'field_file_upload_fid',
            ],
          ],
          'entity' => [
            'bundle' => 'node',
            'media_field_name' => 'os2loop_page_image',
          ],
        ],
      ],

      'Comment files' => [
        'sql' => "SELECT * FROM field_data_field_file_upload_comment WHERE entity_type = 'comment'",
        'comment_node_post' => [
          'media' => [
            'bundle' => 'os2loop_media_file',
            'field_name' => 'field_media_file',
            'fields' => [
              'target_id' => 'field_file_upload_fid',
            ],
          ],
          'entity' => [
            'bundle' => 'comment',
            'media_field_name' => 'os2loop_question_answer_media',
          ],
        ],
      ],

    ];

    foreach ($migrations as $name => $migration) {
      $this->output()->writeln($name);
      $items = Database::getConnection('default', 'migrate')->query(
        $migration['sql'],
        [],
        ['return' => Database::RETURN_STATEMENT]
      )->fetchAll();
      foreach ($items as $item) {
        if (!isset($migration[$item->bundle])) {
          $this->output()->writeln(sprintf('Unhandled bundle: %s', $item->bundle));
          continue;
        }
        $data = [
          'bundle' => $migration[$item->bundle]['media']['bundle'],
        ];
        foreach ($migration[$item->bundle]['media']['fields'] as $field => $property) {
          $data[$migration[$item->bundle]['media']['field_name']][$field] = $item->{$property};
        }
        $media = Media::create($data);
        $media->save();

        /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
        $entity = $this->entityTypeManager->getStorage($migration[$item->bundle]['entity']['bundle'])->load($item->entity_id);
        $mediaFieldName = $migration[$item->bundle]['entity']['media_field_name'];
        $entity->set($mediaFieldName, ['target_id' => $media->id()]);
        $entity->save();

        $this->output()->writeln(sprintf('%s:%s.%s -> %s:%s', $entity->bundle(), $entity->id(), $mediaFieldName, $media->bundle(), $media->id()));
        $this->output()->writeln(sprintf('%s -> %s', $entity->toUrl('canonical', ['absolute' => TRUE])->toString(), $media->toUrl('canonical', ['absolute' => TRUE])->toString()));
      }
      $this->output()->writeln('');
    }
  }

}
