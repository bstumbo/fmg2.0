<?php

/**
 * @file
 * Contains bar_entity.page.inc.
 *
 * Page callback for Bar entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Bar templates.
 *
 * Default template: bar_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_bar_entity(array &$variables) {
  // Fetch BarEntity Entity Object.
  $bar_entity = $variables['elements']['#bar_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
