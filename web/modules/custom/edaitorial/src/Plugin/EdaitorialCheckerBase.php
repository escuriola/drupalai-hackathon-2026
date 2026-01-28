<?php

namespace Drupal\edaitorial\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Edaitorial Checker plugins.
 *
 * Provides common functionality for AI-powered content analysis plugins
 * including AI service integration and content extraction methods.
 */
abstract class EdaitorialCheckerBase extends PluginBase implements EdaitorialCheckerInterface, ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The AI provider plugin manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected AiProviderPluginManager $aiProvider;

  /**
   * Constructs an EdaitorialCheckerBase object.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    ConfigFactoryInterface $config_factory,
    AiProviderPluginManager $ai_provider
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->aiProvider = $ai_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('ai.provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCategory(): string {
    return $this->pluginDefinition['category'] ?? 'content';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) ($this->pluginDefinition['label'] ?? $this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    $config = $this->configFactory->get('edaitorial.settings');
    $enabled_checkers = $config->get('enabled_checkers') ?? [];
    
    // If no specific checkers are configured, all are enabled by default
    return empty($enabled_checkers) || in_array($this->getPluginId(), $enabled_checkers, true);
  }

  /**
   * Get text field content from a node.
   *
   * Searches common text field names and returns the first non-empty content.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to get text from.
   *
   * @return string|null
   *   The text content or NULL if not found.
   */
  protected function getTextContent(NodeInterface $node): ?string {
    $text_field_names = [
      'body', 
      'field_content', 
      'field_text', 
      'field_text1', 
      'field_description', 
      'field_body'
    ];
    
    foreach ($text_field_names as $field_name) {
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        $field_value = $node->get($field_name)->value;
        if (!empty(trim($field_value))) {
          return $field_value;
        }
      }
    }
    
    return null;
  }

  /**
   * Call AI with a prompt and get response.
   *
   * Handles AI service integration with proper error handling and fallbacks.
   *
   * @param string $prompt
   *   The prompt to send to AI.
   *
   * @return string
   *   The AI response or empty JSON array on failure.
   */
  protected function callAi(string $prompt): string {
    if (!$this->shouldUseAi()) {
      return '[]';
    }
    
    try {
      $provider_config = $this->getAiProviderConfig();
      
      if (!$provider_config['provider_id'] || !$provider_config['model_id']) {
        \Drupal::logger('edaitorial')->warning('AI provider or model not configured properly.');
        return '[]';
      }
      
      // Get the AI provider instance
      $provider = $this->aiProvider->createInstance($provider_config['provider_id']);
      
      // Create chat message and input
      $message = new ChatMessage('user', $prompt);
      $input = new ChatInput([$message]);
      
      // Call the AI service
      $output = $provider->chat($input, $provider_config['model_id']);
      
      // Extract response text
      $response_message = $output->getNormalized();
      return $response_message->getText();
    }
    catch (\Exception $e) {
      \Drupal::logger('edaitorial')->error('AI call failed in @plugin: @message', [
        '@plugin' => $this->getPluginId(),
        '@message' => $e->getMessage(),
      ]);
      return '[]';
    }
  }

  /**
   * Get AI provider configuration.
   *
   * @return array
   *   Array with provider_id and model_id.
   */
  protected function getAiProviderConfig(): array {
    $ai_config = $this->configFactory->get('ai.settings');
    $default_providers = $ai_config->get('default_providers') ?? [];
    
    $provider_id = null;
    $model_id = null;
    
    // Try different chat operations in order of preference
    $chat_operations = [
      'chat_with_complex_json',
      'chat_with_structured_response', 
      'chat_with_tools',
      'chat'
    ];
    
    foreach ($chat_operations as $operation) {
      if (isset($default_providers[$operation])) {
        $config = $default_providers[$operation];
        
        if (is_array($config)) {
          $provider_id = $config['provider_id'] ?? null;
          $model_id = $config['model_id'] ?? null;
        } else {
          $provider_id = $config;
          $model_id = $this->getDefaultModelForProvider($provider_id);
        }
        
        if ($provider_id && $model_id) {
          break;
        }
      }
    }
    
    return [
      'provider_id' => $provider_id,
      'model_id' => $model_id,
    ];
  }

  /**
   * Get default model for a provider.
   *
   * @param string|null $provider_id
   *   The provider ID.
   *
   * @return string|null
   *   The model ID or NULL if none found.
   */
  protected function getDefaultModelForProvider(?string $provider_id): ?string {
    if (!$provider_id) {
      return null;
    }
    
    try {
      $provider_config_name = "ai_provider_{$provider_id}.settings";
      $provider_config = $this->configFactory->get($provider_config_name);
      
      // Try to get default model
      $default_model = $provider_config->get('default_model');
      if ($default_model) {
        return $default_model;
      }
      
      // If no default, try to get first available model
      $models = $provider_config->get('models');
      if ($models && is_array($models) && !empty($models)) {
        return array_key_first($models);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('edaitorial')->error('Failed to get model for provider @provider: @message', [
        '@provider' => $provider_id,
        '@message' => $e->getMessage(),
      ]);
    }
    
    return null;
  }

  /**
   * Parse AI JSON response into issues array.
   *
   * Handles various response formats and extracts valid JSON.
   *
   * @param string $response
   *   The AI response string.
   *
   * @return array
   *   Array of parsed issues or empty array on failure.
   */
  protected function parseAiResponse(string $response): array {
    if (empty(trim($response))) {
      return [];
    }
    
    // Clean response - remove markdown code blocks if present
    $response = trim($response);
    $response = preg_replace('/^```json\s*/i', '', $response);
    $response = preg_replace('/\s*```$/i', '', $response);
    
    // Try to extract JSON array from response
    if (preg_match('/\[[\s\S]*\]/i', $response, $matches)) {
      $json_string = $matches[0];
    } else {
      $json_string = $response;
    }
    
    try {
      $decoded = json_decode($json_string, true, 512, JSON_THROW_ON_ERROR);
      
      if (is_array($decoded)) {
        return $this->validateIssuesArray($decoded);
      }
    }
    catch (\JsonException $e) {
      \Drupal::logger('edaitorial')->warning('Failed to parse AI response in @plugin: @error. Response: @response', [
        '@plugin' => $this->getPluginId(),
        '@error' => $e->getMessage(),
        '@response' => substr($response, 0, 200) . (strlen($response) > 200 ? '...' : ''),
      ]);
    }
    
    return [];
  }

  /**
   * Validate and sanitize issues array from AI response.
   *
   * @param array $issues
   *   Raw issues array from AI.
   *
   * @return array
   *   Validated issues array.
   */
  protected function validateIssuesArray(array $issues): array {
    $validated = [];
    
    foreach ($issues as $issue) {
      if (!is_array($issue)) {
        continue;
      }
      
      // Ensure required fields exist with defaults
      $validated_issue = [
        'description' => $issue['description'] ?? 'Unknown issue',
        'type' => $issue['type'] ?? 'Content',
        'severity' => $this->validateSeverity($issue['severity'] ?? 'Low'),
        'impact' => $this->validateImpact($issue['impact'] ?? 'Low'),
      ];
      
      $validated[] = $validated_issue;
    }
    
    return $validated;
  }

  /**
   * Validate severity level.
   *
   * @param string $severity
   *   The severity to validate.
   *
   * @return string
   *   Valid severity level.
   */
  protected function validateSeverity(string $severity): string {
    $valid_severities = ['Critical', 'High', 'Medium', 'Low'];
    return in_array($severity, $valid_severities, true) ? $severity : 'Low';
  }

  /**
   * Validate impact level.
   *
   * @param string $impact
   *   The impact to validate.
   *
   * @return string
   *   Valid impact level.
   */
  protected function validateImpact(string $impact): string {
    $valid_impacts = ['High', 'Medium', 'Low'];
    return in_array($impact, $valid_impacts, true) ? $impact : 'Low';
  }

  /**
   * Check if AI should be used for this checker.
   *
   * @return bool
   *   TRUE if AI should be used.
   */
  protected function shouldUseAi(): bool {
    $config = $this->configFactory->get('edaitorial.settings');
    return (bool) $config->get('use_ai');
  }

}
