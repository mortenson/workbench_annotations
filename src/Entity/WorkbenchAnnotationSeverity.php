<?php

namespace Drupal\workbench_annotation\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Moderation state entity.
 *
 * @ConfigEntityType(
 *   id = "workbench_annotation_severity",
 *   label = @Translation("Workbench annotation severity"),
 *   config_prefix = "workbench_annotation_severity",
 *   admin_permission = "administer workbench annotation severities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 *
 */
class WorkbenchAnnotationSeverity extends ConfigEntityBase {

  /**
   * The severity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The severity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The severity color, in hex.
   *
   * @var string
   */
  protected $color;

  /**
   * The blocked moderation states.
   *
   * @var array
   */
  protected $blocked_moderation_states = [];

}
