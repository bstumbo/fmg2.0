<?php

namespace Drupal\bars\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BarTypeForm.
 */
class BarTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $bar_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $bar_type->label(),
      '#description' => $this->t("Label for the Bar type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $bar_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\bars\Entity\BarType::load',
      ],
      '#disabled' => !$bar_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $bar_type = $this->entity;
    $status = $bar_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Bar type.', [
          '%label' => $bar_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Bar type.', [
          '%label' => $bar_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($bar_type->toUrl('collection'));
  }

}
