<?php

namespace Drupal\workbench_annotation\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\workbench_annotation\WorkbenchAnnotationInterface;

/**
 * Defines the workbench_annotation entity.
 *
 * @ContentEntityType(
 *   id = "workbench_annotation",
 *   label = @Translation("Workbench annotation"),
 *   handlers = {
 *     "access" = "Drupal\workbench_annotation\AccessControlHandler"
 *   },
 *   base_table = "workbench_annotation",
 *   admin_permission = "administer workbench_annotation entity",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   },
 * )
 *
 */
class WorkbenchAnnotation extends ContentEntityBase implements WorkbenchAnnotationInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'author' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the entity.'))
      ->setReadOnly(TRUE);

    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent'))
      ->setDescription(t('The parent workbench_annotation ID if this is a reply.'))
      ->setSetting('target_type', 'workbench_annotation');

    $fields['author'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user who created this entity.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity'))
      ->setDescription(t('The entity type this annotation is related to.'))
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH)
      ->setRequired(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity'))
      ->setDescription(t('The entity id this annotation is related to.'))
      ->setRequired(TRUE);

    $fields['quote'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Selected text'))
      ->setDescription(t('The text that was selected, if applicable.'))
      ->setSetting('max_length', 255)
      ->setRequired(TRUE);

    $fields['text'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Text'))
      ->setDescription(t('The text of the annotation.'))
      ->setSetting('max_length', FieldStorageConfig::NAME_MAX_LENGTH)
      ->setRequired(TRUE);

    $fields['ranges'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Ranges'))
      ->setDescription(t('The ranges of selection.'))
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setRequired(TRUE);

    $fields['severity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Severity'))
      ->setSetting('target_type', 'workbench_annotation_severity')
      ->setDescription(t('The severity for this annotation.'))
      ->setRequired(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function hasParent() {
    return (bool) $this->get('parent')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    if ($this->hasParent()) {
      return $this->get('parent')->entity;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthor() {
    if ($this->get('author')->target_id) {
      return $this->get('author')->entity;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorName() {
    if ($author = $this->getAuthor()) {
      return $author->label();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorImageUrl() {
    /** @var \Drupal\user\Entity\User $author */
    if ($author = $this->getAuthor()) {
      /** @var \Drupal\Core\File\FileSystem $file_system */
      if ($author->hasField('user_picture')) {
        /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $user_picture */
        $user_picture = $author->get('user_picture')->get(0);
        if ($user_picture) {
          $image_path = $user_picture->entity->getFileUri();
        }
      }
    }

    if (!isset($image_path)) {
      $image_path = drupal_get_path('module', 'workbench_annotation') . '/images/account.svg';
    }

    return file_create_url($image_path);
  }

  /**
   * {@inheritdoc}
   */
  public function getAnnotatedEntity() {
    $entity_type = $this->getAnnotatedEntityTypeId();
    $entity_id = $this->getAnnotatedEntityId();
    $storage = $this->entityTypeManager()->getStorage($entity_type);
    return $storage->load($entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setAnnotatedEntity($entity) {
    if (!$entity->isNew()) {
      $this->set('entity_type', $entity->getEntityTypeId());
      $this->set('entity_id', $entity->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAnnotatedEntityTypeId() {
    return $this->get('entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnnotatedEntityId() {
    return $this->get('entity_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAnnotatedEntityById($entity_type_id, $entity_id) {
    if ($storage = $this->entityTypeManager()->getStorage($entity_type_id)) {
      if ($storage->load($entity_id)) {
        $this->set('entity_type', $entity_type_id);
        $this->set('entity_id', $entity_id);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuote() {
    return $this->get('quote')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function setQuote($quote) {
    if (is_string($quote)) {
      $this->set('quote', $quote);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getText() {
    return $this->get('text')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function setText($text) {
    if (is_string($text)) {
      $this->set('text', $text);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRanges() {
    return $this->get('ranges')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setRanges($ranges) {
    if (is_array($ranges)) {
      $this->set('ranges', $ranges);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSeverity() {
    return WorkbenchAnnotationSeverity::load($this->getSeverityId());
  }

  /**
   * {@inheritdoc}
   */
  public function getSeverityId() {
    return $this->get('severity')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function setSeverityId($id) {
    if ($id == 'default') {
      $this->set('severity', NULL);
    }
    else if (WorkbenchAnnotationSeverity::load($id)) {
      $this->set('severity', $id);
    }
  }

}
