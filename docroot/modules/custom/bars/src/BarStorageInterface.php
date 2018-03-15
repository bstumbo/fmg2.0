<?php

namespace Drupal\bars;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface BarStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Bar revision IDs for a specific Bar.
   *
   * @param \Drupal\bars\Entity\BarInterface $entity
   *   The Bar entity.
   *
   * @return int[]
   *   Bar revision IDs (in ascending order).
   */
  public function revisionIds(BarInterface $entity);

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
   * @param \Drupal\bars\Entity\BarInterface $entity
   *   The Bar entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(BarInterface $entity);

  /**
   * Unsets the language for all Bar with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
