<?php

namespace Drupal\workbench_annotation;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the workbench_annotation entity type.
 *
 * @see \Drupal\workbench_annotation\Entity\WorkbenchAnnotation
 */
class AccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIf($account->hasPermission('access workbench annotations'))->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

      case 'update':
        return AccessResult::allowedIf($account->id() && $account->id() == $entity->get('author')->target_id)->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

      default:
        // No opinion.
        return AccessResult::neutral()->cachePerPermissions();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create workbench annotations');
  }

}
