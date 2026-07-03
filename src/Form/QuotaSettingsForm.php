<?php

/**
 * @file
 * Contains \Drupal\space_quota\Form\QuotaSettingsForm.
 *
 * Changelog:
 *   0.4 - Warning threshold support. Fixed PHP 8.2 deprecations. Code restructuring.
 *   0.5 - Namespace conversion.
 *   0.6 - 2026-05-20 - Added configuration option to ignore orphaned files.
 */

namespace Drupal\space_quota\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for disk space quotas.
 */
class QuotaSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Let the parent create the instance, then inject our services.
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->fieldManager = $container->get('entity_field.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'space_quota_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['space_quota.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('space_quota.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General Quota Settings'),
      '#open' => TRUE,
    ];

    $form['general']['max_quota_mb'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum disk quota (MB)'),
      '#default_value' => $config->get('max_quota_mb') ?: 10,
      '#required' => TRUE,
      '#min' => 1,
    ];

    $form['general']['calculation_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Quota calculation mode'),
      '#default_value' => $config->get('calculation_mode') ?: 'global',
      '#options' => [
        'global' => $this->t('Global (sum of all files uploaded by all users)'),
        'user_specific' => $this->t('Per user (only files uploaded by the current user)'),
      ],
      '#required' => TRUE,
    ];

    $form['general']['warning_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Warning threshold (%)'),
      '#default_value' => $config->get('warning_threshold') ?: 75,
      '#description' => $this->t('Displays a warning message when the user reaches this percentage of their quota.'),
      '#min' => 1,
      '#max' => 100,
      '#field_suffix' => '%',
    ];

    $form['general']['ignore_orphaned_files'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore orphaned files (with 0 usages)'),
      '#default_value' => $config->get('ignore_orphaned_files') ?: FALSE,
      '#description' => $this->t('When enabled, files not referenced by any content will be excluded from the storage calculation without waiting for the next Cron run.'),
    ];

    $form['restrictions'] = [
      '#type' => 'details',
      '#title' => $this->t('Content and Field Restrictions'),
      '#open' => TRUE,
    ];

    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $type_options = [];
    foreach ($content_types as $type) {
      $type_options[$type->id()] = $type->label();
    }

    $form['restrictions']['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content Types to Restrict'),
      '#options' => $type_options,
      '#default_value' => $config->get('content_types') ?? ['article'],
    ];

    $form['restrictions']['file_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('File Fields to Monitor'),
      '#options' => $this->getFileFieldOptions(),
      '#default_value' => $config->get('file_fields') ?? ['field_image', 'field_attachments'],
      '#description' => $this->t('Include the "body" field if you want to disable the CKEditor when the quota is exceeded.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Builds a list of all file and image fields available on the site.
   *
   * @return array
   *   An associative array of field names to human-readable labels.
   */
  protected function getFileFieldOptions() {
    $options = [];
    $field_definitions = $this->fieldManager->getFieldMap();

    foreach ($field_definitions as $entity_type => $fields) {
      if (!in_array($entity_type, ['node', 'media', 'user'])) {
        continue;
      }

      foreach ($fields as $field_name => $field_info) {
        if (in_array($field_info['type'], ['image', 'file', 'text_with_summary'])) {
          $options[$field_name] = "[$entity_type] $field_name ({$field_info['type']})";
        }
        if ($field_name === 'body') {
          $options[$field_name] = "[node] body (text_with_summary)";
        }
      }
    }

    return array_unique($options);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_content_types = array_keys(array_filter($form_state->getValue('content_types')));
    $selected_file_fields = array_keys(array_filter($form_state->getValue('file_fields')));

    $this->config('space_quota.settings')
      ->set('max_quota_mb', $form_state->getValue('max_quota_mb'))
      ->set('calculation_mode', $form_state->getValue('calculation_mode'))
      ->set('content_types', $selected_content_types)
      ->set('file_fields', $selected_file_fields)
      ->set('warning_threshold', (int) $form_state->getValue('warning_threshold'))
      ->set('ignore_orphaned_files', (bool) $form_state->getValue('ignore_orphaned_files'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
