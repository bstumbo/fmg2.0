<?php

namespace Drupal\bars;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Bar entity.
 *
 * @see \Drupal\bars\Entity\Bar.
 */
class BarAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\bars\Entity\BarInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished bar entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published bar entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit bar entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete bar entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add bar entities');
  }

}
