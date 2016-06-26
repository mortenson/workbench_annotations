<?php

namespace Drupal\workbench_annotation;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
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
  protected function checkAccess(WorkbenchAnnotationInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access workbench annotations')
          ->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

      case 'update':
      case 'delete':
        return AccessResult::allowedIf($account->id() && $account->id() === $entity->getAuthor()->id())
          ->orIf(AccessResult::allowedIfHasPermission($account, 'administer workbench annotations'))
          ->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

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
