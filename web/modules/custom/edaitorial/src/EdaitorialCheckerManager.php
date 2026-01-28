<?php

namespace Drupal\edaitorial;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\node\NodeInterface;

/**
 * Provides the Edaitorial Checker plugin manager.
 */
class EdaitorialCheckerManager extends DefaultPluginManager {

  /**
   * Constructs a EdaitorialCheckerManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/EdaitorialChecker', $namespaces, $module_handler, 'Drupal\edaitorial\Plugin\EdaitorialCheckerInterface', 'Drupal\edaitorial\Annotation\EdaitorialChecker');
    $this->alterInfo('edaitorial_checker_info');
    $this->setCacheBackend($cache_backend, 'edaitorial_checker_plugins');
  }

  /**
   * Analyzes a node with all enabled checkers using BATCH AI call.
   * Makes only ONE AI call instead of multiple calls to reduce token consumption.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to analyze.
   *
   * @return array
   *   Array of issues found by all checkers.
   */
  public function analyzeNode(NodeInterface $node) {
    // Use batch AI analysis - single call for all checks
    return $this->batchAnalyzeNode($node);
  }

  /**
   * Batch analyze node with single AI call (optimized for token usage).
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to analyze.
   *
   * @return array
   *   Array of all issues from all checks.
   */
  protected function batchAnalyzeNode(NodeInterface $node) {
    $config = \Drupal::config('edaitorial.settings');
    
    // Check if AI is enabled
    if (!$config->get('use_ai')) {
      return $this->fallbackAnalyze($node);
    }
    
    // Get batch prompt template
    $batch_prompt = $config->get('batch_analysis_prompt');
    if (empty($batch_prompt)) {
      // Fallback to individual checkers if batch prompt not configured
      return $this->individualAnalyzeNode($node);
    }
    
    // Prepare node data
    $title = $node->getTitle();
    $body = $this->getNodeBody($node);
    $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id());
    $word_count = str_word_count(strip_tags($body));
    
    // Get available nodes for broken link checking
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $query = $node_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1);
    $nids = $query->execute();
    $available_nodes = implode(', ', array_map(function($nid) {
      return '/node/' . $nid;
    }, $nids));
    
    // Replace placeholders in batch prompt
    $prompt = str_replace(
      ['{title}', '{body}', '{url}', '{word_count}', '{available_nodes}'],
      [$title, strip_tags($body), $url, $word_count, $available_nodes],
      $batch_prompt
    );
    
    try {
      // Make SINGLE AI call for all checks
      $response = $this->callAiService($prompt);
      
      // Parse and return issues
      return $this->parseBatchResponse($response);
    }
    catch (\Exception $e) {
      \Drupal::logger('edaitorial')->error('Batch AI analysis error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return $this->fallbackAnalyze($node);
    }
  }

  /**
   * Individual analysis (OLD METHOD - higher token consumption).
   * Only used as fallback if batch analysis fails.
   */
  protected function individualAnalyzeNode(NodeInterface $node) {
    $all_issues = [];
    
    $definitions = $this->getDefinitions();
    
    // Sort by weight
    uasort($definitions, function ($a, $b) {
      $a_weight = $a['weight'] ?? 0;
      $b_weight = $b['weight'] ?? 0;
      return $a_weight <=> $b_weight;
    });
    
    foreach ($definitions as $plugin_id => $definition) {
      try {
        /** @var \Drupal\edaitorial\Plugin\EdaitorialCheckerInterface $checker */
        $checker = $this->createInstance($plugin_id);
        
        // Check if checker is enabled
        if ($checker->isEnabled()) {
          $issues = $checker->analyze($node);
          if (!empty($issues)) {
            $all_issues = array_merge($all_issues, $issues);
          }
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('edaitorial')->error('Error running checker @plugin: @message', [
          '@plugin' => $plugin_id,
          '@message' => $e->getMessage(),
        ]);
      }
    }
    
    return $all_issues;
  }

  /**
   * Get node body content.
   */
  protected function getNodeBody(NodeInterface $node) {
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      return $node->get('body')->value;
    }
    return '';
  }

  /**
   * Call AI service with prompt.
   */
  protected function callAiService($prompt) {
    $ai_service = \Drupal::service('ai.provider');
    $config = \Drupal::config('ai.settings');
    
    $provider_id = $config->get('default_provider');
    $model_id = $config->get('providers')[$provider_id]['configuration']['model'] ?? 'mistral-large-latest';
    
    $provider = $ai_service->getProvider($provider_id);
    
    $response = $provider->chat([
      'model' => $model_id,
      'messages' => [
        ['role' => 'user', 'content' => $prompt]
      ],
      'temperature' => 0.3,
    ]);
    
    return $response['choices'][0]['message']['content'] ?? '';
  }

  /**
   * Parse batch AI response.
   */
  protected function parseBatchResponse($response) {
    // Clean response
    $response = trim($response);
    $response = preg_replace('/^```json\s*/i', '', $response);
    $response = preg_replace('/\s*```$/i', '', $response);
    
    // Parse JSON
    $data = json_decode($response, TRUE);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
      \Drupal::logger('edaitorial')->warning('Failed to parse batch AI response: @error', [
        '@error' => json_last_error_msg(),
      ]);
      return [];
    }
    
    return $data;
  }

  /**
   * Fallback analysis without AI.
   */
  protected function fallbackAnalyze(NodeInterface $node) {
    $issues = [];
    $title = $node->getTitle();
    $body = $this->getNodeBody($node);
    
    // Basic checks
    $title_length = strlen($title);
    if ($title_length < 30 || $title_length > 60) {
      $issues[] = [
        'description' => "Title length ({$title_length} chars) not optimal (30-60 recommended)",
        'type' => 'SEO',
        'severity' => 'Medium',
        'impact' => 'Medium',
      ];
    }
    
    $word_count = str_word_count(strip_tags($body));
    if ($word_count < 300) {
      $issues[] = [
        'description' => "Content is short ({$word_count} words). Aim for 300+ words for better SEO.",
        'type' => 'SEO',
        'severity' => 'Low',
        'impact' => 'Medium',
      ];
    }
    
    return $issues;
  }

  /**
   * Gets all available checkers grouped by category.
   *
   * @return array
   *   Array of checkers grouped by category.
   */
  public function getCheckersByCategory() {
    $checkers = [];
    
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      $category = $definition['category'] ?? 'other';
      $checkers[$category][$plugin_id] = $definition;
    }
    
    return $checkers;
  }

}
