<?php

namespace Drupal\bar;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\bar\Entity\BarEntityInterface;

/**
 * Defines the storage handler class for Bar entities.
 *
 * This extends the base storage class, adding required special handling for
 * Bar entities.
 *
 * @ingroup bar
 */
interface BarEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Bar revision IDs for a specific Bar.
   *
   * @param \Drupal\bar\Entity\BarEntityInterface $entity
   *   The Bar entity.
   *
   * @return int[]
   *   Bar revision IDs (in ascending order).
   */
  public function revisionIds(BarEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Bar author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Bar revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\bar\Entity\BarEntityInterface $entity
   *   The Bar entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(BarEntityInterface $entity);

  /**
   * Unsets the language for all Bar with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
