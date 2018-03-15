<?php

namespace Drupal\bars;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\bars\Entity\BarInterface;

/**
 * Defines the storage handler class for Bar entities.
 *
 * This extends the base storage class, adding required special handling for
 * Bar entities.
 *
 * @ingroup bars
 */
class BarStorage extends SqlContentEntityStorage implements BarStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(BarInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {bar_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {bar_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(BarInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {bar_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('bar_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
