<?php

namespace Drupal\edaitorial\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Edaitorial Checker plugins.
 */
abstract class EdaitorialCheckerBase extends PluginBase implements EdaitorialCheckerInterface, ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The AI provider plugin manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected $aiProvider;

  /**
   * Constructs an EdaitorialCheckerBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\ai\AiProviderPluginManager $ai_provider
   *   The AI provider plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, AiProviderPluginManager $ai_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->aiProvider = $ai_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
  public function getCategory() {
    return $this->pluginDefinition['category'] ?? 'content';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'] ?? $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    $config = $this->configFactory->get('edaitorial.settings');
    $enabled_checkers = $config->get('enabled_checkers') ?? [];
    return empty($enabled_checkers) || in_array($this->getPluginId(), $enabled_checkers);
  }

  /**
   * Helper method to get text field content from a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to get text from.
   *
   * @return string|null
   *   The text content or NULL if not found.
   */
  protected function getTextContent($node) {
    $text_field_names = ['body', 'field_content', 'field_text', 'field_text1', 'field_description', 'field_body'];
    
    foreach ($text_field_names as $field_name) {
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        $field_value = $node->get($field_name)->value;
        if (!empty($field_value)) {
          return $field_value;
        }
      }
    }
    
    return NULL;
  }

  /**
   * Call AI with a prompt and get response.
   *
   * @param string $prompt
   *   The prompt to send to AI.
   *
   * @return string
   *   The AI response.
   */
  protected function callAi(string $prompt): string {
    $config = $this->configFactory->get('edaitorial.settings');
    
    // Check if AI is enabled in edaitorial
    if (!$config->get('use_ai')) {
      return '[]';
    }
    
    try {
      // Get default chat provider from Drupal AI configuration
      $ai_config = $this->configFactory->get('ai.settings');
      $default_providers = $ai_config->get('default_providers') ?? [];
      
      // Try to use chat_with_complex_json first (has model configured)
      // Fall back to chat if not available
      $provider_id = NULL;
      $model_id = NULL;
      
      if (isset($default_providers['chat_with_complex_json']) && is_array($default_providers['chat_with_complex_json'])) {
        $provider_id = $default_providers['chat_with_complex_json']['provider_id'];
        $model_id = $default_providers['chat_with_complex_json']['model_id'];
      }
      elseif (isset($default_providers['chat'])) {
        $provider_id = is_array($default_providers['chat']) ? 
          $default_providers['chat']['provider_id'] : 
          $default_providers['chat'];
        
        // Try to get model from provider config
        if (!$model_id) {
          $model_id = $this->getDefaultModelForProvider($provider_id);
        }
      }
      
      if (!$provider_id) {
        \Drupal::logger('edaitorial')->warning('No default chat provider configured in Drupal AI module.');
        return '[]';
      }
      
      if (!$model_id) {
        \Drupal::logger('edaitorial')->warning('No model available for provider @provider', [
          '@provider' => $provider_id,
        ]);
        return '[]';
      }
      
      // Get the AI provider instance
      $provider = $this->aiProvider->createInstance($provider_id);
      
      // Create chat message
      $message = new ChatMessage('user', $prompt);
      
      // Create chat input
      $input = new ChatInput([$message]);
      
      // Call the AI
      $output = $provider->chat($input, $model_id);
      
      // Get the text response from ChatMessage
      $message = $output->getNormalized();
      $response = $message->getText();
      
      return $response;
    }
    catch (\Exception $e) {
      \Drupal::logger('edaitorial')->error('AI call failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return '[]';
    }
  }

  /**
   * Get default model for a provider.
   *
   * @param string $provider_id
   *   The provider ID.
   *
   * @return string|null
   *   The model ID or NULL if none found.
   */
  protected function getDefaultModelForProvider(string $provider_id): ?string {
    try {
      // First, check if there's a model configured in ai.settings for any chat operation
      $ai_config = $this->configFactory->get('ai.settings');
      $default_providers = $ai_config->get('default_providers') ?? [];
      
      // Check all chat-related operations for a configured model
      $chat_operations = ['chat_with_complex_json', 'chat_with_structured_response', 'chat_with_tools', 'chat'];
      
      foreach ($chat_operations as $op) {
        if (isset($default_providers[$op]) && is_array($default_providers[$op])) {
          if (isset($default_providers[$op]['provider_id']) && 
              $default_providers[$op]['provider_id'] === $provider_id &&
              isset($default_providers[$op]['model_id'])) {
            return $default_providers[$op]['model_id'];
          }
        }
      }
      
      // If not found in ai.settings, try provider config
      $provider_config_name = 'ai_provider_' . $provider_id . '.settings';
      $provider_config = $this->configFactory->get($provider_config_name);
      
      // Try to get default model
      if ($default_model = $provider_config->get('default_model')) {
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
    
    return NULL;
  }

  /**
   * Parse AI JSON response into issues array.
   *
   * @param string $response
   *   The AI response string.
   *
   * @return array
   *   Array of issues.
   */
  protected function parseAiResponse(string $response): array {
    // Try to extract JSON from response (in case AI adds explanation)
    if (preg_match('/\[[\s\S]*\]/i', $response, $matches)) {
      $json_string = $matches[0];
    }
    else {
      $json_string = $response;
    }
    
    try {
      $decoded = json_decode($json_string, TRUE, 512, JSON_THROW_ON_ERROR);
      
      if (is_array($decoded)) {
        return $decoded;
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('edaitorial')->error('Failed to parse AI response: @message. Response: @response', [
        '@message' => $e->getMessage(),
        '@response' => $response,
      ]);
    }
    
    return [];
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
