<?php

namespace Drupal\os2loop_migrate\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\os2loop_documents\Helper\CollectionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Connects documents to collections.
 *
 * @MigrateProcessPlugin(
 *   id = "os2loop_collection_document"
 * )
 */
class CollectionDocument extends ProcessPluginBase implements ContainerFactoryPluginInterface {
  /**
   * The collection helper.
   *
   * @var \Drupal\os2loop_documents\Helper\CollectionHelper
   */
  private $collectionHelper;

  /**
   * Map from menu link ids to documents.
   *
   * Used to resolve parents on document collection items.
   *
   * @var array
   */
  private static $documents = [];

  /**
   * Contructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CollectionHelper $collectionHelper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->collectionHelper = $collectionHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('os2loop_documents.collection_helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    switch ($destination_property) {
      case 'collection_id':
        return $this->getCollectionId($row);

      case 'document_id':
        return $this->getDocumentId($row);

      case 'parent_document_id':
        return $this->getParentDocumentId($row);

      case 'weight':
        return $row->get('weight');
    }

    return '';
  }

  /**
   * Get collection id.
   *
   * @param \Drupal\migrate\Row $row
   *   The row.
   */
  private function getCollectionId(Row $row) {
    $menuName = $row->get('menu_name');
    if (preg_match('/(?<collectionId>\d+)$/', $menuName, $matches)) {
      $id = (int) $matches['collectionId'];
      $collection = $this->collectionHelper->loadCollection($id);
      if (NULL !== $collection) {
        return $collection->id();
      }
    }

    return '';
  }

  /**
   * Get document id.
   *
   * @param \Drupal\migrate\Row $row
   *   The row.
   */
  private function getDocumentId(Row $row) {
    $linkPath = $row->get('link_path');
    if (preg_match('/(?<documentId>\d+)$/', $linkPath, $matches)) {
      $id = (int) $matches['documentId'];
      $document = $this->collectionHelper->loadDocument($id);
      if (NULL !== $document) {
        // Store the document so we can get it in getParentDocumentId.
        static::$documents[$row->get('mlid')] = $document;

        return $document->id();
      }
    }

    return '';
  }

  /**
   * Get parent document id.
   *
   * @param \Drupal\migrate\Row $row
   *   The row.
   */
  private function getParentDocumentId(Row $row) {
    $parentId = $row->get('plid');
    $parentDocument = static::$documents[$parentId] ?? NULL;

    return $parentDocument ? $parentDocument->id() : 0;
  }

}
