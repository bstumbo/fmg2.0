<?php

namespace Drupal\search_api_autocomplete\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element\Textfield;
use Drupal\search_api_autocomplete\Controller\AutocompleteController;
use Drupal\search_api_autocomplete\Entity\Search;

/**
 * Provides a Search API Autocomplete form element.
 *
 * @FormElement("search_api_autocomplete")
 */
class SearchApiAutocomplete extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);

    // Apply default form element properties.
    $info['#search_id'] = NULL;
    $info['#data'] = [];

    array_unshift($info['#process'], [$class, 'processSearchApiAutocomplete']);

    return $info;
  }

  /**
   * Adds Search API Autocomplete functionality to a form element.
   *
   * @param array $element
   *   The form element to process. Properties used:
   *   - #search_id: The entity ID of the Search config entity.
   *   - #additional_data: (optional) Additional data to pass to the
   *     autocomplete callback as GET parameters.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed form element.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the #search_id property is missing or invalid.
   */
  public static function processSearchApiAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Require a search ID argument. If the #search_id key is not set on the
    // element, we can't load the Search entity so we should throw an exception.
    if (empty($element['#search_id'])) {
      throw new \InvalidArgumentException('Missing required "#search_id" parameter.');
    }
    if (!($search = Search::load($element['#search_id']))) {
      $search_id = $element['#search_id'];
      throw new \InvalidArgumentException("Search entity with ID \"$search_id\" not found.");
    }

    $controller = new AutocompleteController(\Drupal::service('renderer'));
    $access = $controller->access($search, \Drupal::currentUser());

    $metadata = BubbleableMetadata::createFromRenderArray($element);
    $metadata->merge(BubbleableMetadata::createFromObject($access))
      ->applyTo($element);

    if (!$access->isAllowed()) {
      // Don't process if access isn't allowed.
      return $element;
    }

    // Add option defaults (in case of updates from earlier versions).
    $options = $search->getOptions() + [
      'autosubmit' => TRUE,
      'delay' => 0,
      'min_length' => 1,
      'submit_button_selector' => ':submit',
    ];
    $js_settings = [];
    if ($options['submit_button_selector'] != ':submit') {
      $js_settings['selector'] = $options['submit_button_selector'];
    }
    if ($delay = $search->getOption('delay') !== NULL) {
      $js_settings['delay'] = $delay;
    }
    if ($options['autosubmit']) {
      $js_settings['auto_submit'] = TRUE;
    }
    if ($options['min_length'] > 1) {
      $js_settings['min_length'] = $options['min_length'];
    }

    $element['#attached']['library'][] = 'search_api_autocomplete/search_api_autocomplete';
    if ($js_settings) {
      $element['#attached']['drupalSettings'] = [
        'search_api_autocomplete' => [
          $search->id() => $js_settings,
        ],
      ];
    }

    $element['#autocomplete_route_name'] = 'search_api_autocomplete.autocomplete';
    $element['#autocomplete_route_parameters'] = [
      'search_api_autocomplete_search' => $search->id(),
    ];
    if (!empty($element['#additional_data'])) {
      $element['#autocomplete_route_parameters'] += $element['#additional_data'];
    }
    $element['#attributes']['data-search-api-autocomplete-search'] = $search->id();

    return $element;
  }

}
