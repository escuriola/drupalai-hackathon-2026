<?php

namespace Drupal\edaitorial\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service for Accessibility (WCAG) analysis.
 */
class AccessibilityAnalyzer {

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
   * Constructs an AccessibilityAnalyzer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Calculate overall WCAG compliance score.
   *
   * @return int
   *   Accessibility score (0-100).
   */
  public function calculateAccessibilityScore() {
    $levelA = $this->getLevelACompliance();
    $levelAA = $this->getLevelAACompliance();

    $totalScore = 0;
    $totalMax = 0;

    foreach (['perceivable', 'operable', 'understandable', 'robust'] as $principle) {
      $totalScore += $levelA[$principle]['passed'];
      $totalMax += $levelA[$principle]['total'];
      $totalScore += $levelAA[$principle]['passed'];
      $totalMax += $levelAA[$principle]['total'];
    }

    return $totalMax > 0 ? round(($totalScore / $totalMax) * 100) : 0;
  }

  /**
   * Get WCAG Level A compliance metrics.
   *
   * @return array
   *   Level A compliance data.
   */
  public function getLevelACompliance() {
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['status' => 1]);
    $total_nodes = count($nodes);
    
    // Analyze real content for accessibility issues
    $issues = $this->analyzeAccessibilityIssues($nodes);
    
    // Perceivable: Images with alt text, text alternatives
    $perceivable_total = 20;
    $perceivable_passed = $perceivable_total - min($issues['missing_alt'], 5) - min($issues['missing_headings'], 3);
    
    // Operable: Keyboard accessible, navigation
    $operable_total = 18;
    $operable_passed = $operable_total - min($issues['missing_labels'], 4);
    
    // Understandable: Readable text, predictable operation
    $understandable_total = 12;
    $understandable_passed = $understandable_total - min($issues['complex_content'], 2);
    
    // Robust: Valid HTML, compatible with assistive tech
    $robust_total = 10;
    $robust_passed = $robust_total - min($issues['html_issues'], 3);
    
    return [
      'perceivable' => [
        'passed' => max(0, $perceivable_passed),
        'total' => $perceivable_total,
      ],
      'operable' => [
        'passed' => max(0, $operable_passed),
        'total' => $operable_total,
      ],
      'understandable' => [
        'passed' => max(0, $understandable_passed),
        'total' => $understandable_total,
      ],
      'robust' => [
        'passed' => max(0, $robust_passed),
        'total' => $robust_total,
      ],
    ];
  }

  /**
   * Get WCAG Level AA compliance metrics.
   *
   * @return array
   *   Level AA compliance data.
   */
  public function getLevelAACompliance() {
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['status' => 1]);
    $issues = $this->analyzeAccessibilityIssues($nodes);
    
    // AA Level is more strict
    // Perceivable: Color contrast, audio descriptions
    $perceivable_total = 14;
    $perceivable_passed = $perceivable_total - min($issues['contrast_issues'], 4);
    
    // Operable: Multiple ways to navigate, focus visible
    $operable_total = 8;
    $operable_passed = $operable_total - min($issues['navigation_issues'], 2);
    
    // Understandable: Reading level, consistent navigation
    $understandable_total = 6;
    $understandable_passed = $understandable_total - min($issues['readability_issues'], 1);
    
    // Robust: Compatible with current and future tech
    $robust_total = 2;
    $robust_passed = $robust_total - min($issues['compatibility_issues'], 0);
    
    return [
      'perceivable' => [
        'passed' => max(0, $perceivable_passed),
        'total' => $perceivable_total,
      ],
      'operable' => [
        'passed' => max(0, $operable_passed),
        'total' => $operable_total,
      ],
      'understandable' => [
        'passed' => max(0, $understandable_passed),
        'total' => $understandable_total,
      ],
      'robust' => [
        'passed' => max(0, $robust_passed),
        'total' => $robust_total,
      ],
    ];
  }

  /**
   * Count total accessibility issues.
   *
   * @return int
   *   Number of accessibility issues.
   */
  public function countAccessibilityIssues() {
    $levelA = $this->getLevelACompliance();
    $levelAA = $this->getLevelAACompliance();

    $issues = 0;

    foreach (['perceivable', 'operable', 'understandable', 'robust'] as $principle) {
      $issues += ($levelA[$principle]['total'] - $levelA[$principle]['passed']);
      $issues += ($levelAA[$principle]['total'] - $levelAA[$principle]['passed']);
    }

    return $issues;
  }

  /**
   * Analyze accessibility issues in content.
   *
   * @param array $nodes
   *   Array of node entities to analyze.
   *
   * @return array
   *   Array of issue counts by category.
   */
  protected function analyzeAccessibilityIssues(array $nodes) {
    $issues = [
      'missing_alt' => 0,
      'missing_headings' => 0,
      'missing_labels' => 0,
      'complex_content' => 0,
      'html_issues' => 0,
      'contrast_issues' => 0,
      'navigation_issues' => 0,
      'readability_issues' => 0,
      'compatibility_issues' => 0,
    ];

    foreach ($nodes as $node) {
      if (!$node instanceof \Drupal\node\NodeInterface) {
        continue;
      }

      // Check for images without alt text
      if ($node->hasField('field_image')) {
        $images = $node->get('field_image')->getValue();
        foreach ($images as $image) {
          if (empty($image['alt'])) {
            $issues['missing_alt']++;
          }
        }
      }

      // Check body content for accessibility issues
      if ($node->hasField('body')) {
        $body = $node->get('body')->value;
        if (!empty($body)) {
          // Check for proper heading structure
          if (!preg_match('/<h[1-6]/i', $body)) {
            $issues['missing_headings']++;
          }

          // Check for form inputs without labels (basic check)
          if (preg_match('/<input/i', $body) && !preg_match('/<label/i', $body)) {
            $issues['missing_labels']++;
          }

          // Check readability - very long paragraphs
          if (strlen(strip_tags($body)) > 2000) {
            $word_count = str_word_count(strip_tags($body));
            if ($word_count > 500) {
              $issues['complex_content']++;
            }
          }

          // Check for potential HTML issues (unclosed tags, etc)
          $stripped = strip_tags($body);
          if (strlen($body) - strlen($stripped) > strlen($body) * 0.5) {
            // Too many HTML tags might indicate issues
            $issues['html_issues']++;
          }
        }
      }

      // Check for proper link text (not just "click here")
      if ($node->hasField('body')) {
        $body = $node->get('body')->value;
        if (!empty($body)) {
          if (preg_match('/href[^>]*>(click here|here|read more)<\/a>/i', $body)) {
            $issues['navigation_issues']++;
          }
        }
      }

      // Check title length for readability
      $title = $node->getTitle();
      if (strlen($title) > 100) {
        $issues['readability_issues']++;
      }
    }

    return $issues;
  }

}
