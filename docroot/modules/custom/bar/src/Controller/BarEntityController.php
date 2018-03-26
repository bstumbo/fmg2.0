<?php

namespace Drupal\bar\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\bar\Entity\BarEntityInterface;

/**
 * Class BarEntityController.
 *
 *  Returns responses for Bar routes.
 */
class BarEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Bar  revision.
   *
   * @param int $bar_entity_revision
   *   The Bar  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($bar_entity_revision) {
    $bar_entity = $this->entityManager()->getStorage('bar_entity')->loadRevision($bar_entity_revision);
    $view_builder = $this->entityManager()->getViewBuilder('bar_entity');

    return $view_builder->view($bar_entity);
  }

  /**
   * Page title callback for a Bar  revision.
   *
   * @param int $bar_entity_revision
   *   The Bar  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($bar_entity_revision) {
    $bar_entity = $this->entityManager()->getStorage('bar_entity')->loadRevision($bar_entity_revision);
    return $this->t('Revision of %title from %date', ['%title' => $bar_entity->label(), '%date' => format_date($bar_entity->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Bar .
   *
   * @param \Drupal\bar\Entity\BarEntityInterface $bar_entity
   *   A Bar  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(BarEntityInterface $bar_entity) {
    $account = $this->currentUser();
    $langcode = $bar_entity->language()->getId();
    $langname = $bar_entity->language()->getName();
    $languages = $bar_entity->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $bar_entity_storage = $this->entityManager()->getStorage('bar_entity');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $bar_entity->label()]) : $this->t('Revisions for %title', ['%title' => $bar_entity->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all bar revisions") || $account->hasPermission('administer bar entities')));
    $delete_permission = (($account->hasPermission("delete all bar revisions") || $account->hasPermission('administer bar entities')));

    $rows = [];

    $vids = $bar_entity_storage->revisionIds($bar_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\bar\BarEntityInterface $revision */
      $revision = $bar_entity_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $bar_entity->getRevisionId()) {
          $link = $this->l($date, new Url('entity.bar_entity.revision', ['bar_entity' => $bar_entity->id(), 'bar_entity_revision' => $vid]));
        }
        else {
          $link = $bar_entity->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.bar_entity.revision_revert', ['bar_entity' => $bar_entity->id(), 'bar_entity_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.bar_entity.revision_delete', ['bar_entity' => $bar_entity->id(), 'bar_entity_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['bar_entity_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
