<?php

namespace Drupal\edaitorial\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for edAItorial settings.
 *
 * Provides configuration options for AI integration, analysis settings,
 * SEO parameters, and accessibility compliance levels.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'edaitorial_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['edaitorial.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('edaitorial.settings');

    // AI Configuration Section
    $form['ai'] = [
      '#type' => 'details',
      '#title' => $this->t('AI Configuration'),
      '#description' => $this->t('Configure AI-powered analysis features. AI settings are managed by the <a href="@url">Drupal AI module</a>.', [
        '@url' => '/admin/config/ai',
      ]),
      '#open' => TRUE,
    ];

    $ai_info = $this->getAiProviderInfo();

    $form['ai']['use_ai'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable AI-powered content analysis'),
      '#description' => $this->t('Use AI for advanced content analysis and suggestions. Requires a configured AI provider.'),
      '#default_value' => $config->get('use_ai') ?? TRUE,
    ];

    // Current AI Configuration Display
    $form['ai']['ai_info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Current AI Configuration'),
      '#states' => [
        'visible' => [':input[name="use_ai"]' => ['checked' => TRUE]],
      ],
    ];

    $form['ai']['ai_info']['provider_display'] = [
      '#type' => 'item',
      '#title' => $this->t('Default Chat Provider'),
      '#markup' => $ai_info['provider']
        ? '<strong>' . $ai_info['provider_label'] . '</strong> (' . $ai_info['provider'] . ')'
        : '<em>' . $this->t('No provider configured. Please configure AI providers first.') . '</em>',
    ];

    $form['ai']['ai_info']['model_display'] = [
      '#type' => 'item',
      '#title' => $this->t('Default Model'),
      '#markup' => $ai_info['model']
        ? '<strong>' . $ai_info['model'] . '</strong>'
        : '<em>' . $this->t('Will use provider\'s default model') . '</em>',
    ];

    if (!$ai_info['provider']) {
      $form['ai']['ai_info']['warning'] = [
        '#type' => 'item',
        '#markup' => '<div class="messages messages--warning">' .
          $this->t('No AI provider is configured. Please <a href="@url">configure an AI provider</a> first to enable AI-powered analysis.', [
            '@url' => '/admin/config/ai',
          ]) . '</div>',
      ];
    }

    // Analysis Settings Section
    $form['analysis'] = [
      '#type' => 'details',
      '#title' => $this->t('Analysis Settings'),
      '#description' => $this->t('Configure when and how content analysis is performed.'),
      '#open' => FALSE,
    ];

    $form['analysis']['enable_pre_publish_check'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable pre-publish content analysis'),
      '#description' => $this->t('Add content analysis tools to node edit forms for real-time feedback.'),
      '#default_value' => $config->get('enable_pre_publish_check') ?? TRUE,
    ];

    $form['analysis']['auto_suggestions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable AI-powered suggestions'),
      '#description' => $this->t('Provide automatic suggestions for content improvement using AI analysis.'),
      '#default_value' => $config->get('auto_suggestions') ?? TRUE,
      '#states' => [
        'visible' => [':input[name="use_ai"]' => ['checked' => TRUE]],
      ],
    ];

    // SEO Settings Section
    $form['seo'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('SEO Settings'),
      '#description' => $this->t('Configure SEO analysis parameters and thresholds.'),
    ];

    $form['seo']['min_title_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum title length (characters)'),
      '#description' => $this->t('Titles shorter than this will be flagged as SEO issues.'),
      '#default_value' => $config->get('min_title_length') ?? 30,
      '#min' => 10,
      '#max' => 100,
      '#step' => 1,
    ];

    $form['seo']['max_title_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum title length (characters)'),
      '#description' => $this->t('Titles longer than this will be flagged as SEO issues.'),
      '#default_value' => $config->get('max_title_length') ?? 60,
      '#min' => 10,
      '#max' => 100,
      '#step' => 1,
    ];

    // Accessibility Settings Section
    $form['accessibility'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Accessibility Settings'),
      '#description' => $this->t('Configure WCAG compliance targets and accessibility analysis.'),
    ];

    $form['accessibility']['wcag_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Target WCAG compliance level'),
      '#description' => $this->t('The WCAG compliance level to target for accessibility analysis.'),
      '#options' => [
        'A' => $this->t('Level A - Basic accessibility'),
        'AA' => $this->t('Level AA - Standard compliance (Recommended)'),
        'AAA' => $this->t('Level AAA - Enhanced accessibility'),
      ],
      '#default_value' => $config->get('wcag_level') ?? 'AA',
    ];

    // AI Prompts Configuration Section
    $form['prompts'] = [
      '#type' => 'details',
      '#title' => $this->t('AI Prompts Configuration'),
      '#description' => $this->t('Customize the prompts used by AI checkers. Use placeholders: {title}, {body}, {url}, {word_count}, {available_nodes}'),
      '#open' => FALSE,
      '#states' => [
        'visible' => [':input[name="use_ai"]' => ['checked' => TRUE]],
      ],
    ];

    $this->buildPromptFields($form, $config);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Build AI prompt configuration fields.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The configuration object.
   */
  protected function buildPromptFields(array &$form, $config): void {
    $prompts = [
      'seo_prompt' => [
        'title' => $this->t('SEO Checker Prompt'),
        'default' => $this->getDefaultSeoPrompt(),
      ],
      'broken_links_prompt' => [
        'title' => $this->t('Broken Links Checker Prompt'),
        'default' => $this->getDefaultBrokenLinksPrompt(),
      ],
      'typos_prompt' => [
        'title' => $this->t('Typos Checker Prompt'),
        'default' => $this->getDefaultTyposPrompt(),
      ],
      'suggestions_prompt' => [
        'title' => $this->t('Suggestions Checker Prompt'),
        'default' => $this->getDefaultSuggestionsPrompt(),
      ],
    ];

    foreach ($prompts as $key => $prompt_info) {
      $form['prompts'][$key] = [
        '#type' => 'textarea',
        '#title' => $prompt_info['title'],
        '#default_value' => $config->get($key) ?? $prompt_info['default'],
        '#rows' => 8,
        '#attributes' => [
          'style' => 'font-family: monospace; font-size: 12px;',
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate title length settings
    $min_length = $form_state->getValue('min_title_length');
    $max_length = $form_state->getValue('max_title_length');

    if ($min_length >= $max_length) {
      $form_state->setErrorByName('max_title_length',
        $this->t('Maximum title length must be greater than minimum title length.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('edaitorial.settings');

    // Save all form values
    $values_to_save = [
      'use_ai',
      'enable_pre_publish_check',
      'auto_suggestions',
      'min_title_length',
      'max_title_length',
      'wcag_level',
      'seo_prompt',
      'broken_links_prompt',
      'typos_prompt',
      'suggestions_prompt',
    ];

    foreach ($values_to_save as $key) {
      $config->set($key, $form_state->getValue($key));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get AI provider information from Drupal AI module.
   *
   * @return array
   *   Array with provider, provider_label, and model information.
   */
  protected function getAiProviderInfo(): array {
    $info = [
      'provider' => NULL,
      'provider_label' => NULL,
      'model' => NULL,
    ];

    try {
      $ai_config = \Drupal::config('ai.settings');
      $default_providers = $ai_config->get('default_providers') ?? [];

      if (isset($default_providers['chat'])) {
        $provider_config = $default_providers['chat'];

        if (is_array($provider_config)) {
          $provider_id = $provider_config['provider_id'] ?? NULL;
          $model_id = $provider_config['model_id'] ?? NULL;
        } else {
          $provider_id = $provider_config;
          $model_id = NULL;
        }

        if ($provider_id) {
          $info['provider'] = $provider_id;

          // Get provider label
          $ai_provider_manager = \Drupal::service('ai.provider');
          $definitions = $ai_provider_manager->getDefinitions();

          if (isset($definitions[$provider_id])) {
            $info['provider_label'] = (string) $definitions[$provider_id]['label'];
          }

          $info['model'] = $model_id;
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('edaitorial')->error('Failed to get AI provider info: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $info;
  }

  /**
   * Get default SEO checker prompt.
   *
   * @return string
   *   The default SEO prompt template.
   */
  protected function getDefaultSeoPrompt(): string {
    return <<<'EOT'
Analyze the following content for SEO issues and return a JSON array of issues found.

Title: {title}
URL: {url}
Content: {body}

Check for:
1. Title length (optimal: 30-60 characters)
2. Duplicate titles across the site
3. Meta description presence and length
4. Content length (minimum 300 words)
5. Multiple H1 tags (should have only one)
6. Text-to-HTML ratio
7. Keywords from title present in content
8. URL structure

Return ONLY a JSON array with this format:
[
  {
    "description": "Issue description",
    "type": "SEO",
    "severity": "High|Medium|Low",
    "impact": "High|Medium|Low"
  }
]

If no issues, return empty array: []
EOT;
  }

  /**
   * Get default broken links checker prompt.
   *
   * @return string
   *   The default broken links prompt template.
   */
  protected function getDefaultBrokenLinksPrompt(): string {
    return <<<'EOT'
Analyze the following HTML content for broken or problematic links and return a JSON array of issues.

Content: {body}
Available internal nodes: {available_nodes}

Check for:
1. Empty links (href="" or href="#")
2. Broken internal links (links to /node/X that don't exist)
3. Poor anchor text ("click here", "read more", "here", etc.)
4. External links without rel="noopener" or rel="noreferrer"
5. Links with empty or missing text

Return ONLY a JSON array with this format:
[
  {
    "description": "Issue description with count",
    "type": "SEO|Accessibility|Security",
    "severity": "High|Medium|Low",
    "impact": "High|Medium|Low"
  }
]

If no issues, return empty array: []
EOT;
  }

  /**
   * Get default typos checker prompt.
   *
   * @return string
   *   The default typos prompt template.
   */
  protected function getDefaultTyposPrompt(): string {
    return <<<'EOT'
Analyze the following content for spelling errors, typos, and repeated words.

Title: {title}
Content: {body}

Check for:
1. Common typos (teh→the, recieve→receive, definately→definitely, etc.)
2. Repeated words (e.g., "the the", "and and")
3. Spelling errors in title
4. Spelling errors in body content

Return ONLY a JSON array with this format:
[
  {
    "description": "Typo description (e.g., 'Possible typos in title: Teh → the' or '5 possible typos detected: recieve → receive, ...')",
    "type": "Content",
    "severity": "High|Medium|Low",
    "impact": "Medium|Low"
  }
]

Severity guide:
- High: > 10 typos
- Medium: 5-10 typos or typos in title
- Low: 1-4 typos

If no issues, return empty array: []
EOT;
  }

  /**
   * Get default suggestions checker prompt.
   *
   * @return string
   *   The default suggestions prompt template.
   */
  protected function getDefaultSuggestionsPrompt(): string {
    return <<<'EOT'
Analyze the following content and provide improvement suggestions.

Title: {title}
Content: {body}
Word count: {word_count}

Provide suggestions for:
1. Content structure (headings for long content > 300 words)
2. Use of lists for better organization
3. Adding images or visual content
4. Sentence length and readability
5. Paragraph structure
6. Active vs passive voice
7. Call-to-action presence
8. External links for credibility
9. Power words in title (How, Guide, Best, Top, Ultimate)
10. Numbers in title for engagement

Return ONLY a JSON array with this format:
[
  {
    "description": "Suggestion: [specific actionable advice]",
    "type": "Content|SEO",
    "severity": "Low",
    "impact": "Low"
  }
]

Focus on actionable, specific suggestions. If content is good, return empty array: []
EOT;
  }

}
