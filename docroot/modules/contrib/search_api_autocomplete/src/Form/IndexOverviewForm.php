<?php

namespace Drupal\search_api_autocomplete\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_autocomplete\Entity\Search;
use Drupal\search_api_autocomplete\Plugin\search_api_autocomplete\type\Page;
use Drupal\search_api_autocomplete\Type\TypeInterface;
use Drupal\search_api_autocomplete\Type\TypeManager;
use Drupal\search_api_autocomplete\Utility\PluginHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the overview of all search autocompletion configurations.
 */
class IndexOverviewForm extends FormBase {

  /**
   * The autocomplete suggester manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $suggesterManager;

  /**
   * The autocomplete type manager.
   *
   * @var \Drupal\search_api_autocomplete\Type\TypeManager
   */
  protected $typeManager;

  /**
   * The plugin helper.
   *
   * @var \Drupal\search_api_autocomplete\Utility\PluginHelperInterface
   */
  protected $pluginHelper;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Creates a new AutocompleteSearchAdminOverview instance.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $suggester_manager
   *   The suggester manager.
   * @param \Drupal\search_api_autocomplete\Type\TypeManager $autocomplete_type_manager
   *   The autocomplete type manager.
   * @param \Drupal\search_api_autocomplete\Utility\PluginHelperInterface $plugin_helper
   *   The plugin helper.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination.
   */
  public function __construct(PluginManagerInterface $suggester_manager, TypeManager $autocomplete_type_manager, PluginHelperInterface $plugin_helper, EntityTypeManagerInterface $entity_type_manager, RedirectDestinationInterface $redirect_destination) {
    $this->suggesterManager = $suggester_manager;
    $this->typeManager = $autocomplete_type_manager;
    $this->pluginHelper = $plugin_helper;
    $this->entityTypeManager = $entity_type_manager;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.search_api_autocomplete.suggester'),
      $container->get('plugin.manager.search_api_autocomplete.type'),
      $container->get('search_api_autocomplete.plugin_helper'),
      $container->get('entity_type.manager'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_autocomplete_admin_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, IndexInterface $search_api_index = NULL) {
    $form_state->set('index', $search_api_index);
    $index_id = $search_api_index->id();

    $form['#tree'] = TRUE;

    /** @var \Drupal\search_api_autocomplete\SearchInterface[] $searches_by_type */
    $searches_by_type = [];
    foreach ($this->loadAutocompleteSearchByIndex($index_id) as $search) {
      $searches_by_type[$search->getTypeId()] = $search;
    }
    $any_suggester = FALSE;
    $used_ids = [];

    $types = $this->pluginHelper->createTypePluginsForIndex($index_id);
    foreach ($types as $type_id => $type) {
      $group_label = (string) $type->getGroupLabel();
      if (empty($form[$group_label])) {
        $form[$group_label] = [
          '#type' => 'fieldset',
          '#title' => $type->getGroupLabel(),
        ];
        if ($description = $type->getGroupDescription()) {
          $form[$group_label]['#description'] = '<p>' . $description . '</p>';
        }
        $form[$group_label]['searches']['#type'] = 'tableselect';
        $form[$group_label]['searches']['#header'] = [
          'label' => $this->t('label'),
          'operations' => $this->t('Operations'),
        ];
        $form[$group_label]['searches']['#empty'] = '';
        $form[$group_label]['searches']['#js_select'] = TRUE;
      }

      $suggesters_available = TRUE;
      if (isset($searches_by_type[$type_id])) {
        $search = $searches_by_type[$type_id];
      }
      else {
        $search = $this->createSearchForType($type, $used_ids);
        $used_ids[$search->id()] = TRUE;
        // Determine whether there were any suggesters available for that
        // search.
        $suggesters_available = (bool) $search->getSuggesterIds();
      }
      // Update whether we encountered any search at all which had a suggester
      // available.
      $any_suggester |= $suggesters_available;

      $id = $search->id();
      $form_state->set(['searches', $id], $search);
      $form[$group_label]['searches'][$id] = [
        '#type' => 'checkbox',
        '#default_value' => $search->status(),
        '#parents' => ['searches', $id],
      ];
      if (!$search->status() && !$suggesters_available) {
        $form[$group_label]['searches'][$id]['#disabled'] = TRUE;
        $form[$group_label]['searches'][$id]['#attributes'] = [
          'title' => $this->t('Cannot be enabled because no suggester plugins are available that support this search.'),
        ];
      }

      $options = &$form[$group_label]['searches']['#options'][$id];
      $options['label'] = $search->label();
      $items = [];
      if ($search->status()) {
        $items[] = [
          'title' => $this->t('Edit'),
          'url' => $search->toUrl('edit-form'),
        ];
      }
      if (!$search->isNew()) {
        $items[] = [
          'title' => $this->t('Delete'),
          'url' => $search->toUrl('delete-form'),
        ];
      }

      if ($items) {
        $options['operations'] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $items,
          ],
        ];
      }
      else {
        $options['operations'] = '';
      }
      unset($options);
    }

    if (!Element::children($form)) {
      $form['message']['#markup'] = '<p>' . $this->t('There are currently no searches known for this index.') . '</p>';
    }
    else {
      if (!$any_suggester) {
        $args = [
          '@feature' => 'search_api_autocomplete',
          ':backends_url' => 'https://www.drupal.org/docs/8/modules/search-api/getting-started/server-backends-and-features#backends',
        ];
        drupal_set_message($this->t('There are currently no suggester plugins installed that support this index. To solve this problem, you can either:<ul><li>move the index to a server which supports the "@feature" feature (see the <a href=":backends_url">available backends</a>);</li><li>or install a module providing a new suggester plugin that supports this index.</li></ul>', $args), 'error');
      }

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ];
    }

    return $form;
  }

  /**
   * Creates a new search entity for the given type.
   *
   * @param \Drupal\search_api_autocomplete\Type\TypeInterface $type
   *   The type plugin for which to create a search.
   * @param true[] $used_ids
   *   The IDs of the searches created so far for this form (to avoid
   *   duplicates). IDs are used as array keys, mapping to TRUE.
   *
   * @return \Drupal\search_api_autocomplete\SearchInterface
   *   The new search entity.
   */
  protected function createSearchForType(TypeInterface $type, array $used_ids) {
    $type_id = $type->getPluginId();
    $index_id = $type->getIndexId();

    $id = $base_id = $type->getDerivativeId() ?: $type_id;
    $search_storage = $this->entityTypeManager
      ->getStorage('search_api_autocomplete_search');
    $i = 0;
    while (!empty($used_ids[$id]) || $search_storage->load($id)) {
      $id = $base_id . '_' . ++$i;
    }

    $search = Search::create([
      'id' => $id,
      'status' => FALSE,
      'label' => $type->label(),
      'index_id' => $index_id,
      'type_settings' => [
        $type_id => [],
      ],
      'options' => [],
    ]);

    // Find a supported suggester and set it as the default for the search.
    $suggesters = array_map(function ($suggester_info) {
      return $suggester_info['class'];
    }, $this->suggesterManager->getDefinitions());
    $filter_suggesters = function ($suggester_class) use ($search) {
      return $suggester_class::supportsSearch($search);
    };
    $available_suggesters = array_filter($suggesters, $filter_suggesters);
    if ($available_suggesters) {
      reset($available_suggesters);
      $suggester_id = key($available_suggesters);
      $suggester = $this->pluginHelper
        ->createSuggesterPlugin($search, $suggester_id);
      $search->setSuggesters([
        $suggester_id => $suggester,
      ]);
    }

    return $search;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messages = $this->t('The settings have been saved.');
    foreach ($form_state->getValue('searches') as $id => $enabled) {
      /** @var \Drupal\search_api_autocomplete\SearchInterface $search */
      $search = $form_state->get(['searches', $id]);
      if ($search && $search->status() != $enabled) {
        $change = TRUE;
        if ($search->isNew()) {
          $options['query'] = $this->redirectDestination->getAsArray();
          $options['fragment'] = 'module-search_api_autocomplete';
          $url = Url::fromRoute('user.admin_permissions', [], $options);
          if ($url->access()) {
            $vars[':perm_url'] = $url->toString();
            $messages = $this->t('The settings have been saved. Please remember to set the <a href=":perm_url">permissions</a> for the newly enabled searches.', $vars);
          }
        }
        $search->setStatus($enabled);
        $search->save();
      }
    }
    drupal_set_message(empty($change) ? $this->t('No values were changed.') : $messages);
  }

  /**
   * Load the autocomplete plugins for an index.
   *
   * @param string $index_id
   *   The index ID.
   *
   * @return \Drupal\search_api_autocomplete\SearchInterface[]
   *   An array of autocomplete plugins.
   */
  protected function loadAutocompleteSearchByIndex($index_id) {
    /** @var \Drupal\search_api_autocomplete\SearchInterface[] $searches */
    $searches = $this->entityTypeManager
      ->getStorage('search_api_autocomplete_search')
      ->loadByProperties([
        'index_id' => $index_id,
      ]);
    return $searches;
  }

}
