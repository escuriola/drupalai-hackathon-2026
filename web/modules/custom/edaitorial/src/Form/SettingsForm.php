<?php

namespace Drupal\edaitorial\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for edAItorial settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edaitorial_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['edaitorial.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('edaitorial.settings');

    // AI Configuration
    $form['ai'] = [
      '#type' => 'details',
      '#title' => $this->t('AI Configuration'),
      '#description' => $this->t('AI settings are managed by the Drupal AI module. Configure providers and models at <a href="@url">AI Settings</a>.', [
        '@url' => '/admin/config/ai',
      ]),
      '#open' => TRUE,
    ];

    // Get AI provider info
    $ai_info = $this->getAiProviderInfo();

    $form['ai']['use_ai'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use AI for content analysis'),
      '#description' => $this->t('Enable AI-powered analysis using the configured AI provider.'),
      '#default_value' => $config->get('use_ai') ?? TRUE,
    ];

    // Show current AI configuration (read-only)
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
      '#markup' => $ai_info['provider'] ? 
        '<strong>' . $ai_info['provider_label'] . '</strong> (' . $ai_info['provider'] . ')' : 
        '<em>' . $this->t('No provider configured. Please configure AI providers first.') . '</em>',
    ];

    $form['ai']['ai_info']['model_display'] = [
      '#type' => 'item',
      '#title' => $this->t('Default Model'),
      '#markup' => $ai_info['model'] ? 
        '<strong>' . $ai_info['model'] . '</strong>' : 
        '<em>' . $this->t('Will use provider\'s default model') . '</em>',
    ];

    if (!$ai_info['provider']) {
      $form['ai']['ai_info']['warning'] = [
        '#type' => 'item',
        '#markup' => '<div class="messages messages--warning">' . 
          $this->t('No AI provider is configured. Please <a href="@url">configure an AI provider</a> first.', [
            '@url' => '/admin/config/ai',
          ]) . '</div>',
      ];
    }

    // Prompts Configuration
    $form['prompts'] = [
      '#type' => 'details',
      '#title' => $this->t('AI Prompts Configuration'),
      '#description' => $this->t('Configure the prompts used by each checker. Use {title}, {body}, and {url} as placeholders.'),
      '#open' => FALSE,
    ];

    $form['prompts']['seo_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('SEO Checker Prompt'),
      '#default_value' => $config->get('seo_prompt') ?? $this->getDefaultSeoPrompt(),
      '#rows' => 8,
    ];

    $form['prompts']['broken_links_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Broken Links Checker Prompt'),
      '#default_value' => $config->get('broken_links_prompt') ?? $this->getDefaultBrokenLinksPrompt(),
      '#rows' => 8,
    ];

    $form['prompts']['typos_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Typos Checker Prompt'),
      '#default_value' => $config->get('typos_prompt') ?? $this->getDefaultTyposPrompt(),
      '#rows' => 8,
    ];

    $form['prompts']['suggestions_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Suggestions Checker Prompt'),
      '#default_value' => $config->get('suggestions_prompt') ?? $this->getDefaultSuggestionsPrompt(),
      '#rows' => 8,
    ];

    $form['analysis'] = [
      '#type' => 'details',
      '#title' => $this->t('Analysis Settings'),
      '#open' => FALSE,
    ];

    $form['analysis']['enable_pre_publish_check'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable pre-publish content check'),
      '#description' => $this->t('Analyze content for SEO and accessibility before publishing.'),
      '#default_value' => $config->get('enable_pre_publish_check') ?? TRUE,
    ];

    $form['analysis']['auto_suggestions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable AI-powered suggestions'),
      '#description' => $this->t('Get automatic suggestions for content improvement.'),
      '#default_value' => $config->get('auto_suggestions') ?? TRUE,
    ];

    $form['seo'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('SEO Settings'),
    ];

    $form['seo']['min_title_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum title length'),
      '#default_value' => $config->get('min_title_length') ?? 30,
      '#min' => 10,
      '#max' => 100,
    ];

    $form['seo']['max_title_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum title length'),
      '#default_value' => $config->get('max_title_length') ?? 60,
      '#min' => 10,
      '#max' => 100,
    ];

    $form['accessibility'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Accessibility Settings'),
    ];

    $form['accessibility']['wcag_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Target WCAG compliance level'),
      '#options' => [
        'A' => $this->t('Level A'),
        'AA' => $this->t('Level AA (Recommended)'),
        'AAA' => $this->t('Level AAA'),
      ],
      '#default_value' => $config->get('wcag_level') ?? 'AA',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('edaitorial.settings')
      ->set('use_ai', $form_state->getValue('use_ai'))
      ->set('seo_prompt', $form_state->getValue('seo_prompt'))
      ->set('broken_links_prompt', $form_state->getValue('broken_links_prompt'))
      ->set('typos_prompt', $form_state->getValue('typos_prompt'))
      ->set('suggestions_prompt', $form_state->getValue('suggestions_prompt'))
      ->set('enable_pre_publish_check', $form_state->getValue('enable_pre_publish_check'))
      ->set('auto_suggestions', $form_state->getValue('auto_suggestions'))
      ->set('min_title_length', $form_state->getValue('min_title_length'))
      ->set('max_title_length', $form_state->getValue('max_title_length'))
      ->set('wcag_level', $form_state->getValue('wcag_level'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get AI provider information from Drupal AI module.
   *
   * @return array
   *   Array with provider, provider_label, and model information.
   */
  protected function getAiProviderInfo() {
    $info = [
      'provider' => NULL,
      'provider_label' => NULL,
      'model' => NULL,
    ];

    try {
      // Get default provider for 'chat' operation
      $ai_config = \Drupal::config('ai.settings');
      $default_providers = $ai_config->get('default_providers') ?? [];
      
      if (isset($default_providers['chat'])) {
        $provider_id = $default_providers['chat'];
        $info['provider'] = $provider_id;
        
        // Get provider label
        $ai_provider_manager = \Drupal::service('ai.provider');
        $definitions = $ai_provider_manager->getDefinitions();
        
        if (isset($definitions[$provider_id])) {
          $info['provider_label'] = (string) $definitions[$provider_id]['label'];
        }
        
        // Try to get default model from provider config
        $provider_config_name = 'ai_provider_' . $provider_id . '.settings';
        $provider_config = \Drupal::config($provider_config_name);
        
        // Some providers might have a default_model setting
        if ($default_model = $provider_config->get('default_model')) {
          $info['model'] = $default_model;
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
   */
  protected function getDefaultSeoPrompt() {
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
   */
  protected function getDefaultBrokenLinksPrompt() {
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
   */
  protected function getDefaultTyposPrompt() {
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
   */
  protected function getDefaultSuggestionsPrompt() {
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
