<?php

namespace Drupal\bars\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Bar entities.
 *
 * @ingroup bars
 */
interface BarInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Bar name.
   *
   * @return string
   *   Name of the Bar.
   */
  public function getName();

  /**
   * Sets the Bar name.
   *
   * @param string $name
   *   The Bar name.
   *
   * @return \Drupal\bars\Entity\BarInterface
   *   The called Bar entity.
   */
  public function setName($name);

  /**
   * Gets the Bar creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Bar.
   */
  public function getCreatedTime();

  /**
   * Sets the Bar creation timestamp.
   *
   * @param int $timestamp
   *   The Bar creation timestamp.
   *
   * @return \Drupal\bars\Entity\BarInterface
   *   The called Bar entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Bar published status indicator.
   *
   * Unpublished Bar are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Bar is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Bar.
   *
   * @param bool $published
   *   TRUE to set this Bar to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\bars\Entity\BarInterface
   *   The called Bar entity.
   */
  public function setPublished($published);

  /**
   * Gets the Bar revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Bar revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\bars\Entity\BarInterface
   *   The called Bar entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Bar revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Bar revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\bars\Entity\BarInterface
   *   The called Bar entity.
   */
  public function setRevisionUserId($uid);

}
