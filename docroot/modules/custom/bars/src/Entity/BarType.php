<?php

namespace Drupal\bars\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Bar type entity.
 *
 * @ConfigEntityType(
 *   id = "bar_type",
 *   label = @Translation("Bar type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\bars\BarTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\bars\Form\BarTypeForm",
 *       "edit" = "Drupal\bars\Form\BarTypeForm",
 *       "delete" = "Drupal\bars\Form\BarTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\bars\BarTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "bar_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "bar",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/bar_type/{bar_type}",
 *     "add-form" = "/admin/structure/bar_type/add",
 *     "edit-form" = "/admin/structure/bar_type/{bar_type}/edit",
 *     "delete-form" = "/admin/structure/bar_type/{bar_type}/delete",
 *     "collection" = "/admin/structure/bar_type"
 *   }
 * )
 */
class BarType extends ConfigEntityBundleBase implements BarTypeInterface {

  /**
   * The Bar type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Bar type label.
   *
   * @var string
   */
  protected $label;

}
