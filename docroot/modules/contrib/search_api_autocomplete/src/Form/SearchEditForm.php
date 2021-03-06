<?php

namespace Drupal\search_api_autocomplete\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api_autocomplete\Suggester\SuggesterInterface;
use Drupal\search_api_autocomplete\Type\TypeManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an edit form for autocomplete search entities.
 */
class SearchEditForm extends EntityForm {

  /**
   * The entity.
   *
   * @var \Drupal\search_api_autocomplete\SearchInterface
   */
  protected $entity;

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
   * The logger to use.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Creates a new SearchEditForm instance.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $suggester_manager
   *   The suggester manager.
   * @param \Drupal\search_api_autocomplete\Type\TypeManager $autocomplete_type_manager
   *   The autocomplete type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(PluginManagerInterface $suggester_manager, TypeManager $autocomplete_type_manager, LoggerInterface $logger) {
    $this->suggesterManager = $suggester_manager;
    $this->typeManager = $autocomplete_type_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container->get('logger.channel.search_api_autocomplete');
    return new static(
      $container->get('plugin.manager.search_api_autocomplete.suggester'),
      $container->get('plugin.manager.search_api_autocomplete.type'),
      $logger
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $search = $this->entity;
    $form['#title'] = $this->t('Configure autocompletion for %search', ['%search' => $search->label()]);

    $form['#tree'] = TRUE;

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $search->status(),
    ];

    $form['suggesters'] = $this->buildSuggestersForm($form_state);

    if (!$search->hasValidType()) {
      drupal_set_message($this->t('No information about the type of this search could be found. Unless this is a temporary problem for some reason, you are advised to delete this search configuration.'), 'error');
    }
    else {
      $type = $search->getTypeInstance();
      if ($type instanceof PluginFormInterface) {
        $form['type_settings'] = [];
        $type_form_state = SubFormState::createForSubform($form['type_settings'], $form, $form_state);
        $form['type_settings'] = $type->buildConfigurationForm($form['type_settings'], $type_form_state);
      }
    }

    $form['options']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of suggestions'),
      '#description' => $this->t('The maximum number of autocomplete suggestions to display in total.'),
      '#required' => TRUE,
      '#min' => 1,
      '#step' => 1,
      '#default_value' => $search->getOption('limit', 10),
    ];
    $form['options']['min_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum length of keywords for autocompletion'),
      '#description' => $this->t('If the entered keywords are shorter than this, no autocomplete suggestions will be displayed.'),
      '#min' => 1,
      '#step' => 1,
      '#default_value' => $search->getOption('min_length', 1),
    ];
    $form['options']['show_count'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display result count estimates'),
      '#description' => $this->t('Display the estimated number of result for each suggestion. This option might not have an effect for some servers or types of suggestion.'),
      '#default_value' => (bool) $search->getOption('show_count', FALSE),
    ];

    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['advanced']['autosubmit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable auto-submit'),
      '#description' => $this->t('When enabled, the search form will automatically be submitted when a selection is made by pressing "Enter".'),
      '#default_value' => $search->getOption('autosubmit', TRUE),
      '#parents' => ['options', 'autosubmit'],
    ];
    $form['advanced']['submit_button_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search button selector'),
      '#description' => $this->t('<a href="@jquery_url">jQuery selector</a> identifying the button to use for submitting the search form. Use the ID attribute of the "Search" submit button to prevent issues when another button is present (e.g., "Reset"). The selector is evaluated relative to the form. The default value is "@default".', ['@jquery_url' => 'https://api.jquery.com/category/selectors/', '@default' => ':submit']),
      '#default_value' => $search->getOption('submit_button_selector', ':submit'),
      '#required' => TRUE,
      '#parents' => ['options', 'submit_button_selector'],
      '#states' => [
        'visible' => [
          ':input[name="options[autosubmit]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['advanced']['delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay (in ms)'),
      '#description' => $this->t('The delay in milliseconds between when a keystroke occurs and when a search is performed. Low values will result in a more responsive experience for users, but can also cause a higher load on the server. Defaults to 300 ms.'),
      '#min' => 0,
      '#step' => 1,
      '#default_value' => $search->getOption('delay'),
      '#parents' => ['options', 'delay'],
    ];

    return $form;
  }

  /**
   * Builds a form for the search's suggester plugins.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the (parent) form.
   *
   * @return array
   *   A form for selecting and configuring the search's suggesters.
   */
  protected function buildSuggestersForm(FormStateInterface $form_state) {
    $search = $this->entity;

    $available_suggesters = $this->getAvailableSuggesters();
    if (!$available_suggesters) {
      $args = array(
        '@feature' => 'search_api_autocomplete',
        ':backends_url' => 'https://www.drupal.org/docs/8/modules/search-api/getting-started/server-backends-and-features#backends',
      );
      drupal_set_message($this->t('There are currently no suggester plugins installed that support this index. To solve this problem, you can either:<ul><li>move the index to a server which supports the "@feature" feature (see the <a href=":backends_url">available backends</a>)</li><li>or install a module providing a new suggester plugin that supports this index</li></ul>', $args), 'error');
    }
    $suggester_ids = array_keys($available_suggesters);
    $suggester_weights = $search->getSuggesterWeights();
    $suggester_weights += array_fill_keys($suggester_ids, 0);
    $suggester_limits = $search->getSuggesterLimits();
    $suggester_limits += array_fill_keys($suggester_ids, NULL);

    $form = [
      '#type' => 'fieldset',
      '#title' => $this->t('Suggester plugins'),
      '#description' => $this->t('Suggester plugins represent methods for creating autocomplete suggestions from user input.'),
    ];
    $form['enabled'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled suggesters'),
      '#description' => $this->t('Choose the suggester plugins to use for creating autocomplete suggestions.'),
      '#options' => [],
      '#required' => TRUE,
      '#default_value' => $search->getSuggesterIds(),
    ];
    $form['weights'] = [
      '#type' => 'table',
    ];
    $form['weights']['#tabledrag'][] = [
      'action' => 'order',
      'relationship' => 'sibling',
      'group' => 'search-api-autocomplete-suggester-weight',
    ];

    // For the "limit" setting we use a sentence. This requires a bit of
    // preparation.
    $sentence = (string) $this->t('At most @num results');
    list($prefix, $suffix) = explode('@num', $sentence);
    $prefix = '<div class="container-inline">' . $prefix;
    $suffix .= '</div>';

    foreach ($available_suggesters as $suggester_id => $suggester) {
      $label = $suggester->label();

      // Option (with additional description) for the "Enabled" checkboxes.
      $form['enabled']['#options'][$suggester_id] = $label;
      $form['enabled'][$suggester_id] = [
        '#description' => $suggester->getDescription(),
      ];

      $states = [
        'visible' => [
          ":input[name=\"suggesters[enabled][$suggester_id]\"]" => [
            'checked' => TRUE,
          ],
        ],
      ];

      // Entry in the "Suggester order" table. (Hidden while the suggester is
      // disabled.)
      $form['weights'][$suggester_id] = [
        '#weight' => $suggester_weights[$suggester_id],
        'label' => [
          '#plain_text' => $label,
        ],
        'limit' => [
          '#type' => 'number',
          '#prefix' => $prefix,
          '#suffix' => $suffix,
          '#min' => 1,
          '#step' => 1,
          '#size' => 2,
          '#default_value' => $suggester_limits[$suggester_id],
        ],
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for suggester %label', ['%label' => $label]),
          '#title_display' => 'invisible',
          '#delta' => 50,
          '#default_value' => $suggester_weights[$suggester_id],
          '#attributes' => [
            'class' => ['search-api-autocomplete-suggester-weight'],
          ],
        ],
        '#attributes' => [
          'class' => ['draggable', 'js-form-wrapper'],
          'data-drupal-states' => Json::encode($states),
        ],
      ];

      // "Details" container with the suggester config form, if any. (Hidden
      // while the suggester is disabled.)
      if ($suggester instanceof PluginFormInterface) {
        $args = ['%label' => $suggester->label()];
        $form['settings'][$suggester_id] = [
          '#type' => 'details',
          '#title' => $this->t('Configure suggester %label', $args),
          '#states' => $states,
        ];
        $suggester_form_state = SubformState::createForSubform($form['settings'][$suggester_id], $form, $form_state);
        $form['settings'][$suggester_id] += $suggester->buildConfigurationForm($form['settings'][$suggester_id], $suggester_form_state);
      }
    }

    if (!empty($form['weights'])) {
      uasort($form['weights'], [SortArray::class, 'sortByWeightProperty']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = &$form_state->getValues();

    // Iterate over all suggesters that have a form and are enabled.
    $available_suggesters = $this->getAvailableSuggesters();
    $enabled_suggesters = array_keys(array_filter($values['suggesters']['enabled']));
    foreach ($enabled_suggesters as $suggester_id) {
      $suggester = $available_suggesters[$suggester_id];
      if ($suggester instanceof PluginFormInterface) {
        $suggester_form_state = SubformState::createForSubform($form['suggesters']['settings'][$suggester_id], $form, $form_state);
        $suggester->validateConfigurationForm($form['suggesters']['settings'][$suggester_id], $suggester_form_state);
      }
    }

    $type = $this->entity->getTypeInstance();
    if ($type instanceof PluginFormInterface) {
      $type_form_state = SubFormState::createForSubform($form['type_settings'], $form, $form_state);
      $type->validateConfigurationForm($form['type_settings'], $type_form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove button and internal Form API values from submitted values.
    $form_state->cleanValues();
    $this->entity = $this->buildEntity($form, $form_state);
    $search = $this->entity;
    $values = $form_state->getValues();

    // Iterate over all suggesters that are enabled.
    $available_suggesters = $this->getAvailableSuggesters();
    /** @var \Drupal\search_api_autocomplete\Suggester\SuggesterInterface[] $enabled_suggesters */
    $enabled_suggesters = array_intersect_key($available_suggesters, array_filter($values['suggesters']['enabled']));
    $suggester_weights = [];
    $suggester_limits = [];
    foreach ($enabled_suggesters as $suggester_id => $suggester) {
      // Submit the form, if there is one.
      if ($suggester instanceof PluginFormInterface) {
        $suggester_form_state = SubformState::createForSubform($form['suggesters']['settings'][$suggester_id], $form, $form_state);
        $suggester->submitConfigurationForm($form['suggesters']['settings'][$suggester_id], $suggester_form_state);
      }
      $suggester_weights[$suggester_id] = (int) $values['suggesters']['weights'][$suggester_id]['weight'];
      if (is_numeric($values['suggesters']['weights'][$suggester_id]['limit'])) {
        $suggester_limits[$suggester_id] = (int) $values['suggesters']['weights'][$suggester_id]['limit'];
      }
    }
    $search->setSuggesters($enabled_suggesters);
    $search->set('suggester_weights', $suggester_weights);
    $search->set('suggester_limits', $suggester_limits);

    $type = $this->entity->getTypeInstance();
    if ($type instanceof PluginFormInterface) {
      $type_form = empty($form['type_settings']) ? [] : $form['type_settings'];
      $type_form_state = SubFormState::createForSubform($form['type_settings'], $form, $form_state);
      $type->submitConfigurationForm($type_form, $type_form_state);
      $search->set('type_settings', $type->getConfiguration());
    }

    $form_state->setRedirect('search_api_autocomplete.admin_overview', ['search_api_index' => $search->getIndexId()]);

    drupal_set_message($this->t('The autocompletion settings for the search have been saved.'));
  }

  /**
   * Returns all suggesters available for this search.
   *
   * @return \Drupal\search_api_autocomplete\Suggester\SuggesterInterface[]
   *   The available suggesters, sorted by label.
   */
  protected function getAvailableSuggesters() {
    $suggesters = $this->entity->getSuggesters();
    $settings['#search'] = $this->entity;

    $definitions = $this->suggesterManager->getDefinitions();
    foreach ($definitions as $plugin_id => $definition) {
      if (isset($suggesters[$plugin_id])) {
        continue;
      }
      if (class_exists($definition['class'])) {
        $method = [$definition['class'], 'supportsSearch'];
        if (call_user_func($method, $this->entity)) {
          /** @var $suggester \Drupal\search_api_autocomplete\Suggester\SuggesterInterface */
          $suggester = $this->suggesterManager
            ->createInstance($plugin_id, $settings);
          $suggesters[$plugin_id] = $suggester;
        }
      }
      else {
        $this->logger->warning('Suggester %id specifies a non-existing class %class.', [
          '%id' => $plugin_id,
          '%class' => $definition['class'],
        ]);
      }
    }

    $compare = function (SuggesterInterface $a, SuggesterInterface $b) {
      return strnatcasecmp($a->label(), $b->label());
    };
    uasort($suggesters, $compare);

    return $suggesters;
  }

}
