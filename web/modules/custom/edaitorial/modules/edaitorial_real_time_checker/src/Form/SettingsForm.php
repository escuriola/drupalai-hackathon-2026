<?php

namespace Drupal\edaitorial_real_time_checker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Configure Real-Time Checker settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edaitorial_real_time_checker_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['edaitorial_real_time_checker.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('edaitorial_real_time_checker.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General Settings'),
      '#open' => TRUE,
    ];

    $form['general']['enable_quality_gate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable quality gate'),
      '#description' => $this->t('Enable AI-powered quality gate that blocks publishing below threshold.'),
      '#default_value' => $config->get('enable_quality_gate') ?? TRUE,
    ];

    $form['general']['min_score'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum score to publish (%)'),
      '#description' => $this->t('Content must reach this score before it can be published. Recommended: 80%.'),
      '#default_value' => $config->get('min_score') ?: 80,
      '#min' => 0,
      '#max' => 100,
      '#step' => 1,
      '#required' => TRUE,
    ];

    $form['general']['block_publishing_below_threshold'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Block publishing below threshold'),
      '#description' => $this->t('If enabled, prevents content from being published if score is below minimum. If disabled, shows a recommendation message instead of blocking.'),
      '#default_value' => $config->get('block_publishing_below_threshold') ?? TRUE,
    ];

    // Content types
    $form['content_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Content Types'),
      '#open' => TRUE,
    ];

    $node_types = NodeType::loadMultiple();
    $type_options = [];
    foreach ($node_types as $type) {
      $type_options[$type->id()] = $type->label();
    }

    $form['content_types']['enabled_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable for content types'),
      '#description' => $this->t('Select which content types should have real-time checking.'),
      '#options' => $type_options,
      '#default_value' => $config->get('enabled_content_types') ?: ['article', 'page'],
    ];

    // Checks
    $form['checks'] = [
      '#type' => 'details',
      '#title' => $this->t('Analysis Checks'),
      '#open' => TRUE,
    ];

    $form['checks']['enabled_checks'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled checks'),
      '#description' => $this->t('Select which checks to perform in quality gate analysis.'),
      '#options' => [
        'seo' => $this->t('SEO Analysis'),
        'accessibility' => $this->t('Accessibility (WCAG)'),
        'typos' => $this->t('Spelling & Typos'),
        'broken_links' => $this->t('Broken Links'),
        'suggestions' => $this->t('Content Suggestions'),
      ],
      '#default_value' => $config->get('enabled_checks') ?: ['seo', 'accessibility', 'typos', 'broken_links'],
    ];

    // Display
    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Settings'),
      '#open' => FALSE,
    ];

    $form['display']['show_category_breakdown'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show category breakdown'),
      '#description' => $this->t('Display percentage scores for each category (SEO, Accessibility, etc.).'),
      '#default_value' => $config->get('show_category_breakdown') ?? TRUE,
    ];

    // Performance & Consistency
    $form['performance'] = [
      '#type' => 'details',
      '#title' => $this->t('Performance & Consistency'),
      '#description' => $this->t('Settings to improve analysis consistency and reduce AI costs.'),
      '#open' => TRUE,
    ];

    $form['performance']['cache_analysis_results'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cache analysis results'),
      '#description' => $this->t('Cache results to ensure consistent scores for identical content. Highly recommended for consistency.'),
      '#default_value' => $config->get('cache_analysis_results') ?? TRUE,
    ];

    $form['performance']['cache_ttl'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache lifetime (seconds)'),
      '#description' => $this->t('How long to cache results. Default: 3600 (1 hour). Set to 0 to disable caching.'),
      '#default_value' => $config->get('cache_ttl') ?? 3600,
      '#min' => 0,
      '#max' => 86400,
      '#step' => 60,
      '#states' => [
        'visible' => [
          ':input[name="cache_analysis_results"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['performance']['cache_info'] = [
      '#type' => 'item',
      '#markup' => '<div class="messages messages--warning">' .
        '<strong>' . $this->t('Why caching matters:') . '</strong><br>' .
        $this->t('AI models can produce slightly different results even with identical input (temperature > 0). Caching ensures that analyzing the same content multiple times will always return the exact same score and issues, improving user experience and trust.') .
        '<br><br><strong>' . $this->t('Technical details:') . '</strong><br>' .
        $this->t('- AI temperature is set to 0 (deterministic mode) for maximum consistency<br>- Cache key is based on MD5 hash of title + body<br>- If you modify content, the hash changes and a new analysis is performed<br>- You can clear all cached analyses with: <code>drush cache-clear</code>') .
        '</div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('edaitorial_real_time_checker.settings')
      ->set('enable_quality_gate', $form_state->getValue('enable_quality_gate'))
      ->set('min_score', $form_state->getValue('min_score'))
      ->set('block_publishing_below_threshold', $form_state->getValue('block_publishing_below_threshold'))
      ->set('enabled_content_types', array_filter($form_state->getValue('enabled_content_types')))
      ->set('enabled_checks', array_filter($form_state->getValue('enabled_checks')))
      ->set('show_category_breakdown', $form_state->getValue('show_category_breakdown'))
      ->set('cache_analysis_results', $form_state->getValue('cache_analysis_results'))
      ->set('cache_ttl', $form_state->getValue('cache_ttl'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
