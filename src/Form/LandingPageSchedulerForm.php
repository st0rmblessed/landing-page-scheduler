<?php

namespace Drupal\landing_page_scheduler\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * LandingPageSchedulerForm form.
 */
class LandingPageSchedulerForm extends FormBase {

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Provides an interface for an entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Provides an interface for entity type managers.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ReportWorkerBase constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service the instance should use.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Provides an interface for an entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Provides an interface for entity type managers.
   */
  public function __construct(StateInterface $state, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->state = $state;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('state'),
          $container->get('entity_field.manager'),
          $container->get('entity_type.manager')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'landing_page_scheduler_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $data = $this->state->get('landing_page_scheduler_config');

    // Load previously saved config.
    if (!is_null($data)) {
      $landing_page_scheduler_configs = $data;
      $default_node = \Drupal::entityTypeManager()->getStorage('node')->load($data['node']);
    }

    // Build form.
    $form['field_set_1'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Scheduler'),
    ];

    $form['field_set_1']['start_redirect'] = [
      '#type' => 'datetime',
      '#description' => $this->t('The date and time at which the redirect will be in effect.'),
      '#default_value' => isset($landing_page_scheduler_configs['start']) ? $landing_page_scheduler_configs['start'] : '',
      '#title' => $this->t('Activate redirect on'),
    ];

    $form['field_set_1']['stop_redirect'] = [
      '#type' => 'datetime',
      '#description' => $this->t('The date and time at which the redirect will stop.'),
      '#default_value' => isset($landing_page_scheduler_configs['end']) ? $landing_page_scheduler_configs['end'] : '',
      '#title' => $this->t('Stop redirect on'),
    ];

    $form['field_set_1']['landing_page'] = [
      '#type' => 'entity_autocomplete',
      '#description' => $this->t('The node to be selected as landing page.'),
      '#default_value' => isset($default_node) ? $default_node : '',
      '#title' => 'Landing Page',
      '#target_type' => 'node',
    ];

    $form['field_set_1']['first_access'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('When this option is selected the landing page will only be displayed on the first time the user logs in.'),
      '#title' => $this->t('Only on first access?'),
      '#default_value' => isset($landing_page_scheduler_configs['first_access']) ? $landing_page_scheduler_configs['first_access'] : '',
      '#return_Value' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('start_redirect') === NULL) {

      $form_state->setErrorByName('start_redirect', $this->t('Please select a date for the beginning of the redirect.'));
    }
    if ($form_state->getValue('stop_redirect') === NULL) {
      $form_state->setErrorByName('stop_redirect', $this->t('Please select a date for the end of the redirect.'));
    }
    if ($form_state->getValue('landing_page') === NULL) {
      $form_state->setErrorByName('landing_page', $this->t('Please select a node to redirect on login.'));
    }

    if ($form_state->getValue('start_redirect') !== NULL && $form_state->getValue('stop_redirect') !== NULL) {
      if ($form_state->getValue('start_redirect')->getTimestamp() >= $form_state->getValue('stop_redirect')->getTimestamp()) {
        $form_state->setErrorByName('stop_redirect', $this->t('The date for stopping the redirect should be after the start of the redirect.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Set values in form state.
    $landing_page_scheduler_configs['start'] = $form_state->getValue('start_redirect');
    $landing_page_scheduler_configs['end'] = $form_state->getValue('stop_redirect');
    $landing_page_scheduler_configs['node'] = $form_state->getValue('landing_page');
    $landing_page_scheduler_configs['first_access'] = $form_state->getValue('first_access');

    // Save form state.
    if (isset($landing_page_scheduler_configs)) {
      $this->state->set('landing_page_scheduler_config', $landing_page_scheduler_configs);
    }

    // Save field value in database.
    \Drupal::database()->update('user__field_redirect_to_landing_page')->fields([
      'field_redirect_to_landing_page_value' => TRUE,
    ])->execute();

    // Display success message.
    $this->messenger()->addStatus($this->t('Configurations successfully saved.'));
  }

}
