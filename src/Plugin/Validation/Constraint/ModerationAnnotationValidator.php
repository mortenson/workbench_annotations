<?php

namespace Drupal\workbench_annotation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\workbench_annotation\Entity\WorkbenchAnnotation;
use Drupal\workbench_annotation\Entity\WorkbenchAnnotationSeverity;
use Drupal\workbench_moderation\ModerationInformationInterface;
use Drupal\workbench_moderation\StateTransitionValidation;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ModerationAnnotationValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The state transition validation.
   *
   * @var \Drupal\workbench_moderation\StateTransitionValidation
   */
  protected $validation;

  /**
   * The moderation info.
   *
   * @var \Drupal\workbench_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * The Entity Query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $user;

  /**
   * Creates a new WorkbenchAnnotationValidator instance.
   *
   * @param \Drupal\workbench_moderation\StateTransitionValidation $validation
   *   The state transition validation.
   * @param \Drupal\workbench_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The Entity Query factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The current user.
   */
  public function __construct(StateTransitionValidation $validation, ModerationInformationInterface $moderation_information, QueryFactory $entity_query, AccountProxyInterface $user) {
    $this->validation = $validation;
    $this->moderationInformation = $moderation_information;
    $this->entityQuery = $entity_query;
    $this->user = $user;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workbench_moderation.state_transition_validation'),
      $container->get('workbench_moderation.moderation_information'),
      $container->get('entity.query'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $value->getEntity();

    // Ignore entities that are not subject to moderation anyway.
    if (!$this->moderationInformation->isModeratableEntity($entity)) {
      return;
    }

    // Ignore entities that are being created for the first time.
    if ($entity->isNew()) {
      return;
    }

    // Ignore entities that are being moderated for the first time, such as
    // when they existed before moderation was enabled for this entity type.
    if ($this->isFirstTimeModeration($entity)) {
      return;
    }

    $original_entity = $this->moderationInformation->getLatestRevision($entity->getEntityTypeId(), $entity->id());
    if (!$entity->isDefaultTranslation() && $original_entity->hasTranslation($entity->language()->getId())) {
      $original_entity = $original_entity->getTranslation($entity->language()->getId());
    }
    $next_moderation_state_id = $entity->moderation_state->target_id;
    $original_moderation_state_id = $original_entity->moderation_state->target_id;

    $transitions = $this->validation->getValidTransitions($original_entity, $this->user);

    // Determine the current transition object based on the from and to states.
    $current_transition = FALSE;
    foreach ($transitions as $transition) {
      if ($transition->getToState() == $next_moderation_state_id && $transition->getFromState() == $original_moderation_state_id) {
        $current_transition = $transition;
      }
    }

    // Something went wrong, return early before running into errors.
    if (!$current_transition) {
      return;
    }

    // Load all annotations associated with this Entity.
    $result = $this->entityQuery->get('workbench_annotation', 'AND')
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->execute();
    /** @var \Drupal\workbench_annotation\Entity\WorkbenchAnnotation[] $annotations */
    $annotations = WorkbenchAnnotation::loadMultiple($result);

    // Check if any annotation blocks the current transition.
    foreach ($annotations as $annotation) {
      $severity_id = $annotation->getSeverityId();
      /** @var \Drupal\workbench_annotation\Entity\WorkbenchAnnotationSeverity $severity */
      $severity = WorkbenchAnnotationSeverity::load($severity_id);
      if ($severity) {
        foreach ($severity->get('blocked_moderation_transitions') as $transition) {
          if ($current_transition->id() == $transition) {
            $this->context->addViolation($constraint->message, ['@transition' => $current_transition->label()]);
          }
        }
      }
    }
  }

  /**
   * Determines if this entity is being moderated for the first time.
   *
   * If the previous version of the entity has no moderation state, we assume
   * that means it predates the presence of moderation states.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return bool
   *   TRUE if this is the entity's first time being moderated, FALSE otherwise.
   */
  protected function isFirstTimeModeration(EntityInterface $entity) {
    $original_entity = $this->moderationInformation->getLatestRevision($entity->getEntityTypeId(), $entity->id());

    $original_id = $original_entity->moderation_state->target_id;

    return !($entity->moderation_state->target_id && $original_entity && $original_id);
  }

}
