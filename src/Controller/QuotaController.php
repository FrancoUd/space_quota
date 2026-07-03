<?php

namespace Drupal\space_quota\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Renders the quota status page.
 */
class QuotaController extends ControllerBase {

  /**
   * Returns the render array for the quota status page.
   *
   * @return array
   *   A render array representing the quota status page content.
   */
  public function statusPage() {
    $config = $this->config('space_quota.settings');
    $max_quota_mb = $config->get('max_quota_mb') ?: 10;
    $calculation_mode = $config->get('calculation_mode') ?: 'global';

    $output = [];

    // Initialise cache contexts array.
    $cache_contexts = [];

    // If calculation is user-specific, the page must vary per logged-in user.
    if ($calculation_mode === 'user_specific') {
      $cache_contexts[] = 'user';
    }

    // 1. General Settings section.
    $output[] = [
      '#type' => 'container',
      '#markup' => '<h2>' . $this->t('General Settings') . '</h2>',
    ];

    $output[] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Configured Maximum Quota: <b>@maxquota MB</b>', ['@maxquota' => $max_quota_mb]),
        $this->t('Calculation Mode: <b>@mode</b>', [
          '@mode' => ($calculation_mode === 'user_specific' ? $this->t('User Specific') : $this->t('Global for all users')),
        ]),
      ],
    ];

    // 2. Current usage section.
    $uid = $this->currentUser()->id();

    $used_bytes = $calculation_mode === 'user_specific'
      ? \space_quota_calculate_user_storage_size($uid)
      : \space_quota_calculate_global_storage_size();

    $used_formatted = \space_quota_format_file_size($used_bytes);

    $usage_percentage = $max_quota_mb > 0
      ? round(($used_bytes / ($max_quota_mb * 1024 * 1024)) * 100, 2)
      : 0;

    $output[] = [
      '#type' => 'container',
      '#markup' => '<h2>' . $this->t('Current Status') . '</h2>',
    ];

    $output[] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Used storage space: <b>@used</b> out of <b>@maxquota MB</b>.', [
        '@used' => $used_formatted,
        '@maxquota' => $max_quota_mb,
      ]),
    ];

    $output[] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Usage percentage: <b>@percent %</b>', ['@percent' => $usage_percentage]),
      '#attributes' => [
        'class' => ['quota-status-' . ($usage_percentage > 90 ? 'critical' : ($usage_percentage > 70 ? 'warning' : 'ok'))],
      ],
    ];

    // Add a notice when the quota is shared across all users.
    if ($calculation_mode !== 'user_specific') {
      $output[] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => '⚠️ ' . $this->t('The displayed quota is global. The disk space limit is shared across all users.'),
      ];
    }

    // Disable page cache so values are recalculated on every request.
    $output['#cache'] = [
      'max-age' => 0,
      'contexts' => $cache_contexts,
    ];

    return $output;
  }

}
