<?php

namespace Drupal\workbench_annotation\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Defines the workbench_annotation entity.
 *
 * @ContentEntityType(
 *   id = "workbench_annotation",
 *   label = @Translation("Workbench annotation"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\workbench_annotation\AccessControlHandler",
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
class WorkbenchAnnotation extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'author' => \Drupal::currentUser()->id(),
      'created' => REQUEST_TIME,
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

    $fields['entity_type'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity'))
      ->setDescription(t('The entity type this annotation is related to.'))
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

    $fields['resolved'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Resolved'))
      ->setDescription(t('Whether or not the annotation is resolved.'));

    $fields['severity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Severity'))
      ->setSetting('target_type', 'workbench_annotation_severity')
      ->setDescription(t('The severity for this annotation.'))
      ->setRequired(TRUE);

    return $fields;
  }

}
