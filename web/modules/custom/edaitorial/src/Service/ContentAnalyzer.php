<?php

namespace Drupal\edaitorial\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Service for AI-powered content analysis.
 */
class ContentAnalyzer {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a ContentAnalyzer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Analyze content before publishing.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to analyze.
   *
   * @return array
   *   Analysis results.
   */
  public function analyzeBeforePublish(NodeInterface $node) {
    $results = [
      'seo' => $this->analyzeSeoElements($node),
      'accessibility' => $this->analyzeAccessibility($node),
      'readability' => $this->analyzeReadability($node),
      'ai_suggestions' => $this->getAiSuggestions($node),
    ];

    $results['overall_score'] = $this->calculateOverallScore($results);

    return $results;
  }

  /**
   * Analyze SEO elements of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to analyze.
   *
   * @return array
   *   SEO analysis results.
   */
  protected function analyzeSeoElements(NodeInterface $node) {
    $issues = [];

    // Check title length.
    $title = $node->getTitle();
    if (strlen($title) < 30 || strlen($title) > 60) {
      $issues[] = 'Title should be between 30-60 characters';
    }

    // Check for meta description.
    if ($node->hasField('field_meta_description') && $node->get('field_meta_description')->isEmpty()) {
      $issues[] = 'Missing meta description';
    }

    // Check for images with alt text.
    if ($node->hasField('field_image')) {
      $images = $node->get('field_image')->getValue();
      foreach ($images as $image) {
        if (empty($image['alt'])) {
          $issues[] = 'Image missing alt text';
          break;
        }
      }
    }

    return [
      'issues' => $issues,
      'score' => count($issues) === 0 ? 100 : max(0, 100 - (count($issues) * 20)),
    ];
  }

  /**
   * Analyze accessibility of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to analyze.
   *
   * @return array
   *   Accessibility analysis results.
   */
  protected function analyzeAccessibility(NodeInterface $node) {
    $issues = [];

    // Check for proper heading structure.
    if ($node->hasField('body')) {
      $body = $node->get('body')->value;
      if (!empty($body)) {
        if (!preg_match('/<h[1-6]/', $body)) {
          $issues[] = 'Content should include proper heading structure';
        }
      }
    }

    // Check color contrast (placeholder - would need actual implementation).
    // $issues[] = 'Some text may have insufficient color contrast';

    return [
      'issues' => $issues,
      'score' => count($issues) === 0 ? 100 : max(0, 100 - (count($issues) * 20)),
    ];
  }

  /**
   * Analyze readability of content.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to analyze.
   *
   * @return array
   *   Readability analysis results.
   */
  protected function analyzeReadability(NodeInterface $node) {
    $issues = [];

    if ($node->hasField('body')) {
      $body = strip_tags($node->get('body')->value);
      $word_count = str_word_count($body);

      if ($word_count < 300) {
        $issues[] = 'Content may be too short for good SEO (under 300 words)';
      }

      // Simple sentence length check.
      $sentences = preg_split('/[.!?]+/', $body, -1, PREG_SPLIT_NO_EMPTY);
      $avg_sentence_length = $word_count / max(count($sentences), 1);

      if ($avg_sentence_length > 25) {
        $issues[] = 'Average sentence length is too long for readability';
      }
    }

    return [
      'issues' => $issues,
      'score' => count($issues) === 0 ? 100 : max(0, 100 - (count($issues) * 15)),
    ];
  }

  /**
   * Get AI-powered suggestions for content improvement.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to analyze.
   *
   * @return array
   *   AI suggestions.
   */
  protected function getAiSuggestions(NodeInterface $node) {
    // Placeholder for AI integration.
    // In a real implementation, this would call the amazee.io AI service.
    return [
      'Consider adding more internal links to related content',
      'The introduction could be more engaging',
      'Add more specific keywords related to your topic',
    ];
  }

  /**
   * Calculate overall content score.
   *
   * @param array $results
   *   Analysis results.
   *
   * @return int
   *   Overall score (0-100).
   */
  protected function calculateOverallScore(array $results) {
    $scores = [
      $results['seo']['score'],
      $results['accessibility']['score'],
      $results['readability']['score'],
    ];

    return round(array_sum($scores) / count($scores));
  }

}
