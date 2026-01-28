<?php

namespace Drupal\edaitorial_real_time_checker\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\edaitorial\EdaitorialCheckerManager;

/**
 * Service for real-time content analysis.
 */
class RealTimeAnalyzer {

  /**
   * The edAItorial checker manager.
   *
   * @var \Drupal\edaitorial\EdaitorialCheckerManager
   */
  protected $checkerManager;

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
   * Constructs a RealTimeAnalyzer object.
   *
   * @param \Drupal\edaitorial\EdaitorialCheckerManager $checker_manager
   *   The edAItorial checker manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    EdaitorialCheckerManager $checker_manager,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->checkerManager = $checker_manager;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Analyzes content and returns issues with score.
   *
   * @param string $title
   *   The content title.
   * @param string $body
   *   The content body.
   * @param string $content_type
   *   The content type.
   *
   * @return array
   *   Array containing score, issues, category scores, and suggestions.
   */
  public function analyzeContent($title, $body, $content_type = 'article') {
    $config = $this->configFactory->get('edaitorial_real_time_checker.settings');
    
    try {
      // Create a temporary stub node for analysis
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $node = $node_storage->create([
        'type' => $content_type,
        'title' => $title,
        'body' => [
          'value' => $body,
          'format' => 'full_html',
        ],
        'status' => 0, // Unpublished
      ]);
      
      // Use the edaitorial checker manager for batch analysis
      $issues = $this->checkerManager->analyzeNode($node);
      
      // Calculate category scores FIRST
      $category_scores = $this->calculateCategoryScores($issues);
      
      // Calculate overall score as AVERAGE of category scores
      $score = $this->calculateOverallScore($category_scores);
      
      // Group issues by type
      $grouped_issues = $this->groupIssuesByType($issues);
      
      // Generate suggestions
      $suggestions = $this->generateSuggestions($issues, $score);
      
      $result = [
        'score' => $score,
        'score_class' => $this->getScoreClass($score),
        'issues' => $issues,
        'grouped_issues' => $grouped_issues,
        'category_scores' => $category_scores,
        'suggestions' => $suggestions,
      ];
      
      // Log the complete result
      $this->loggerFactory->get('quality_gate')->info('RealTimeAnalyzer result: score=@score, categories=@cats, issues=@count', [
        '@score' => $score,
        '@cats' => json_encode($category_scores),
        '@count' => count($issues),
      ]);
      
      return $result;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('edaitorial_real_time_checker')->error(
        'Analysis failed: @message',
        ['@message' => $e->getMessage()]
      );
      
      return [
        'score' => 0,
        'score_class' => 'critical',
        'issues' => [],
        'grouped_issues' => [],
        'category_scores' => [
          'seo' => 0,
          'accessibility' => 0,
          'typos' => 0,
          'links' => 0,
          'content' => 0,
        ],
        'suggestions' => [],
      ];
    }
  }

  /**
   * Calculates overall score as average of category scores.
   *
   * @param array $category_scores
   *   Array of category scores.
   *
   * @return int
   *   Overall score from 0-100.
   */
  protected function calculateOverallScore(array $category_scores) {
    if (empty($category_scores)) {
      return 100;
    }
    
    // Calculate average of all category scores
    $total = 0;
    $count = 0;
    
    foreach ($category_scores as $category => $score) {
      $total += $score;
      $count++;
    }
    
    if ($count === 0) {
      return 100;
    }
    
    $average = round($total / $count);
    return max(0, min(100, $average));
  }

  /**
   * Calculates score for each category (SEO, Accessibility, etc.).
   *
   * @param array $issues
   *   Array of issues found.
   *
   * @return array
   *   Array of scores by category (percentage).
   */
  protected function calculateCategoryScores(array $issues) {
    $categories = [
      'seo' => 100,
      'accessibility' => 100,
      'typos' => 100,
      'links' => 100,
      'content' => 100,
    ];
    
    $deductions = [
      'seo' => 0,
      'accessibility' => 0,
      'typos' => 0,
      'links' => 0,
      'content' => 0,
    ];
    
    foreach ($issues as $issue) {
      $type = strtolower($issue['type'] ?? 'content');
      $severity = $issue['severity'] ?? 'Low';
      
      // Map issue types to categories (case-insensitive)
      $category = 'content'; // default
      if ($type === 'seo' || strpos($type, 'seo') !== FALSE) {
        $category = 'seo';
      }
      elseif ($type === 'accessibility' || strpos($type, 'accessibility') !== FALSE || strpos($type, 'wcag') !== FALSE) {
        $category = 'accessibility';
      }
      elseif ($type === 'typo' || $type === 'typos' || strpos($type, 'typo') !== FALSE || strpos($type, 'spelling') !== FALSE) {
        $category = 'typos';
      }
      elseif ($type === 'link' || $type === 'links' || strpos($type, 'link') !== FALSE || strpos($type, 'broken') !== FALSE) {
        $category = 'links';
      }
      elseif ($type === 'title' || $type === 'content') {
        $category = 'content';
      }
      
      // Deduct points based on severity
      switch ($severity) {
        case 'Critical':
          $deductions[$category] += 25;
          break;
        case 'High':
          $deductions[$category] += 15;
          break;
        case 'Medium':
          $deductions[$category] += 10;
          break;
        case 'Low':
          $deductions[$category] += 5;
          break;
      }
    }
    
    // Calculate final scores
    $scores = [];
    foreach ($categories as $category => $base_score) {
      $score = $base_score - $deductions[$category];
      $scores[$category] = max(0, min(100, $score));
    }
    
    return $scores;
  }

  /**
   * Gets the score class for styling.
   *
   * @param int $score
   *   The score.
   *
   * @return string
   *   The score class.
   */
  protected function getScoreClass($score) {
    if ($score >= 90) {
      return 'excellent';
    }
    elseif ($score >= 75) {
      return 'good';
    }
    elseif ($score >= 50) {
      return 'fair';
    }
    elseif ($score >= 25) {
      return 'poor';
    }
    else {
      return 'critical';
    }
  }

  /**
   * Groups issues by type.
   *
   * @param array $issues
   *   Array of issues.
   *
   * @return array
   *   Issues grouped by type.
   */
  protected function groupIssuesByType(array $issues) {
    $grouped = [];
    foreach ($issues as $issue) {
      $type = $issue['type'] ?? 'Other';
      if (!isset($grouped[$type])) {
        $grouped[$type] = [];
      }
      $grouped[$type][] = $issue;
    }
    return $grouped;
  }

  /**
   * Generates actionable suggestions based on issues.
   *
   * @param array $issues
   *   Array of issues.
   * @param int $score
   *   The content score.
   *
   * @return array
   *   Array of suggestions.
   */
  protected function generateSuggestions(array $issues, $score) {
    $suggestions = [];
    
    if ($score >= 90) {
      $suggestions[] = [
        'text' => 'Your content is excellent! Consider adding images or video to enhance engagement.',
        'priority' => 'low',
      ];
    }
    elseif ($score >= 75) {
      $suggestions[] = [
        'text' => 'Good start! Review the issues below to reach excellent quality.',
        'priority' => 'medium',
      ];
    }
    else {
      $suggestions[] = [
        'text' => 'Focus on addressing high-priority issues first for maximum impact.',
        'priority' => 'high',
      ];
    }
    
    // Add type-specific suggestions
    $issue_types = array_unique(array_column($issues, 'type'));
    
    if (in_array('SEO', $issue_types)) {
      $suggestions[] = [
        'text' => 'Optimize your title and content for search engines.',
        'priority' => 'high',
      ];
    }
    
    if (in_array('Typos', $issue_types)) {
      $suggestions[] = [
        'text' => 'Fix spelling errors to improve professionalism.',
        'priority' => 'high',
      ];
    }
    
    return $suggestions;
  }

}
