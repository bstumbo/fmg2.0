<?php

namespace Drupal\search_api_autocomplete\Type;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api_autocomplete\Plugin\SearchPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for type plugins.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. These definition arrays may be altered through
 * hook_search_api_autocomplete_type_alter(). The definition includes the
 * following keys:
 * - id: The unique, system-wide identifier of the type plugin.
 * - label: The human-readable name of the type plugin, translated.
 * - description: A human-readable description for the type plugin,
 *   translated.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @SearchApiAutocompleteType(
 *   id = "my_type",
 *   label = @Translation("Custom Search"),
 *   description = @Translation("Custom-defined site-specific search."),
 *   index = "my_index",
 * )
 * @endcode
 *
 * @see \Drupal\search_api_autocomplete\Annotation\SearchApiAutocompleteType
 * @see \Drupal\search_api_autocomplete\Type\TypeInterface
 * @see \Drupal\search_api_autocomplete\Type\TypeManager
 * @see plugin_api
 */
abstract class TypePluginBase extends SearchPluginBase implements TypeInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $type */
    $type = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $type->setEntityTypeManager($container->get('entity_type.manager'));
    $type->setStringTranslation($container->get('string_translation'));

    return $type;
  }

  /**
   * Retrieves the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * Sets the entity manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel() {
    $plugin_definition = $this->getPluginDefinition();
    if (isset($plugin_definition['group_label'])) {
      return $plugin_definition['group_label'];
    }
    return $this->t('Other searches');
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupDescription() {
    $plugin_definition = $this->getPluginDefinition();
    if (isset($plugin_definition['group_description'])) {
      return $plugin_definition['group_description'];
    }
    return $this->t('Searches not belonging to any specific group');
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexId() {
    return $this->getPluginDefinition()['index'];
  }

  /**
   * {@inheritdoc}
   */
  public function getIndex() {
    return $this->getEntityTypeManager()
      ->getStorage('search_api_index')
      ->load($this->getIndexId());
  }

}
