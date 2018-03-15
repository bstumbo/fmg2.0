<?php

/**
 * @file
 * Contains bar.page.inc.
 *
 * Page callback for Bar entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Bar templates.
 *
 * Default template: bar.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_bar(array &$variables) {
  // Fetch Bar Entity Object.
  $bar = $variables['elements']['#bar'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
