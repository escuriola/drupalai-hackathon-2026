<?php

namespace Drupal\edaitorial\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Service for Accessibility (WCAG) analysis.
 *
 * Provides WCAG compliance analysis across four principles:
 * Perceivable, Operable, Understandable, and Robust.
 */
class AccessibilityAnalyzer {

  /**
   * WCAG compliance levels and their criteria counts.
   */
  private const WCAG_LEVELS = [
    'A' => [
      'perceivable' => 20,
      'operable' => 18,
      'understandable' => 12,
      'robust' => 10,
    ],
    'AA' => [
      'perceivable' => 14,
      'operable' => 8,
      'understandable' => 6,
      'robust' => 2,
    ],
  ];

  /**
   * Constructs an AccessibilityAnalyzer object.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Calculate overall WCAG compliance score.
   *
   * Combines Level A and AA compliance scores across all four principles.
   *
   * @return int
   *   Accessibility score (0-100).
   */
  public function calculateAccessibilityScore(): int {
    $levelA = $this->getLevelACompliance();
    $levelAA = $this->getLevelAACompliance();

    $total_score = 0;
    $total_max = 0;

    foreach (['perceivable', 'operable', 'understandable', 'robust'] as $principle) {
      $total_score += $levelA[$principle]['passed'] + $levelAA[$principle]['passed'];
      $total_max += $levelA[$principle]['total'] + $levelAA[$principle]['total'];
    }

    return $total_max > 0 ? (int) round(($total_score / $total_max) * 100) : 0;
  }

  /**
   * Get WCAG Level A compliance metrics.
   *
   * Analyzes content for basic accessibility compliance.
   *
   * @return array
   *   Level A compliance data by principle.
   */
  public function getLevelACompliance(): array {
    $nodes = $this->getPublishedNodes();
    $issues = $this->analyzeAccessibilityIssues($nodes);
    
    return [
      'perceivable' => $this->calculatePrincipleScore(
        'perceivable', 
        'A', 
        $issues['missing_alt'] + $issues['missing_headings']
      ),
      'operable' => $this->calculatePrincipleScore(
        'operable', 
        'A', 
        $issues['missing_labels']
      ),
      'understandable' => $this->calculatePrincipleScore(
        'understandable', 
        'A', 
        $issues['complex_content']
      ),
      'robust' => $this->calculatePrincipleScore(
        'robust', 
        'A', 
        $issues['html_issues']
      ),
    ];
  }

  /**
   * Get WCAG Level AA compliance metrics.
   *
   * Analyzes content for enhanced accessibility compliance.
   *
   * @return array
   *   Level AA compliance data by principle.
   */
  public function getLevelAACompliance(): array {
    $nodes = $this->getPublishedNodes();
    $issues = $this->analyzeAccessibilityIssues($nodes);
    
    return [
      'perceivable' => $this->calculatePrincipleScore(
        'perceivable', 
        'AA', 
        $issues['contrast_issues']
      ),
      'operable' => $this->calculatePrincipleScore(
        'operable', 
        'AA', 
        $issues['navigation_issues']
      ),
      'understandable' => $this->calculatePrincipleScore(
        'understandable', 
        'AA', 
        $issues['readability_issues']
      ),
      'robust' => $this->calculatePrincipleScore(
        'robust', 
        'AA', 
        $issues['compatibility_issues']
      ),
    ];
  }

  /**
   * Count total accessibility issues.
   *
   * @return int
   *   Total number of accessibility issues found.
   */
  public function countAccessibilityIssues(): int {
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
   * Performs comprehensive accessibility analysis on all provided nodes.
   *
   * @param \Drupal\node\NodeInterface[] $nodes
   *   Array of node entities to analyze.
   *
   * @return array
   *   Array of issue counts by category.
   */
  protected function analyzeAccessibilityIssues(array $nodes): array {
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
      $this->analyzeNodeAccessibility($node, $issues);
    }

    return $issues;
  }

  /**
   * Analyze accessibility issues for a single node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to analyze.
   * @param array &$issues
   *   Issues array to update.
   */
  protected function analyzeNodeAccessibility(NodeInterface $node, array &$issues): void {
    // Check images for alt text
    $this->checkImageAltText($node, $issues);
    
    // Check content structure
    $body = $this->getNodeBody($node);
    if ($body) {
      $this->checkContentStructure($body, $issues);
      $this->checkLinkAccessibility($body, $issues);
      $this->checkContentComplexity($body, $issues);
    }
    
    // Check title accessibility
    $this->checkTitleAccessibility($node, $issues);
  }

  /**
   * Check images for alt text accessibility.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   * @param array &$issues
   *   Issues array to update.
   */
  protected function checkImageAltText(NodeInterface $node, array &$issues): void {
    $image_fields = ['field_image', 'field_images', 'field_media'];
    
    foreach ($image_fields as $field_name) {
      if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
        continue;
      }

      foreach ($node->get($field_name)->getValue() as $image) {
        if (empty($image['alt'])) {
          $issues['missing_alt']++;
        }
      }
    }
  }

  /**
   * Check content structure for accessibility.
   *
   * @param string $body
   *   The body content.
   * @param array &$issues
   *   Issues array to update.
   */
  protected function checkContentStructure(string $body, array &$issues): void {
    // Check for proper heading structure
    if (!preg_match('/<h[1-6]/i', $body)) {
      $issues['missing_headings']++;
    }

    // Check for form inputs without labels
    if (preg_match('/<input/i', $body) && !preg_match('/<label/i', $body)) {
      $issues['missing_labels']++;
    }

    // Check for tables without proper structure
    if (preg_match('/<table/i', $body) && !preg_match('/<th/i', $body)) {
      $issues['html_issues']++;
    }
  }

  /**
   * Check link accessibility.
   *
   * @param string $body
   *   The body content.
   * @param array &$issues
   *   Issues array to update.
   */
  protected function checkLinkAccessibility(string $body, array &$issues): void {
    // Check for poor link text
    $poor_link_patterns = [
      '/href[^>]*>\s*(click here|here|read more|more|link)\s*<\/a>/i',
      '/href[^>]*>\s*<\/a>/i', // Empty links
    ];
    
    foreach ($poor_link_patterns as $pattern) {
      if (preg_match($pattern, $body)) {
        $issues['navigation_issues']++;
      }
    }
  }

  /**
   * Check content complexity for readability.
   *
   * @param string $body
   *   The body content.
   * @param array &$issues
   *   Issues array to update.
   */
  protected function checkContentComplexity(string $body, array &$issues): void {
    $text_content = strip_tags($body);
    $word_count = str_word_count($text_content);
    
    // Check for overly complex content
    if ($word_count > 2000) {
      $issues['complex_content']++;
    }
    
    // Basic readability check - very long paragraphs
    $paragraphs = explode('</p>', $body);
    foreach ($paragraphs as $paragraph) {
      $paragraph_words = str_word_count(strip_tags($paragraph));
      if ($paragraph_words > 150) {
        $issues['readability_issues']++;
        break;
      }
    }
  }

  /**
   * Check title accessibility.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   * @param array &$issues
   *   Issues array to update.
   */
  protected function checkTitleAccessibility(NodeInterface $node, array &$issues): void {
    $title = $node->getTitle();
    
    // Check for overly long titles that may be hard to read
    if (strlen($title) > 100) {
      $issues['readability_issues']++;
    }
  }

  /**
   * Calculate principle score based on issues found.
   *
   * @param string $principle
   *   The WCAG principle name.
   * @param string $level
   *   The WCAG level (A or AA).
   * @param int $issues_found
   *   Number of issues found.
   *
   * @return array
   *   Array with 'passed' and 'total' scores.
   */
  protected function calculatePrincipleScore(string $principle, string $level, int $issues_found): array {
    $total = self::WCAG_LEVELS[$level][$principle];
    $penalty = min($issues_found, $total); // Cap penalty at total possible
    $passed = max(0, $total - $penalty);
    
    return [
      'passed' => $passed,
      'total' => $total,
    ];
  }

  /**
   * Get all published nodes efficiently.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Array of published node entities.
   */
  protected function getPublishedNodes(): array {
    return $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties(['status' => 1]);
  }

  /**
   * Get node body content from various possible fields.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to get content from.
   *
   * @return string|null
   *   The body content or NULL if not found.
   */
  protected function getNodeBody(NodeInterface $node): ?string {
    $text_fields = ['body', 'field_content', 'field_text', 'field_description'];
    
    foreach ($text_fields as $field_name) {
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        $value = $node->get($field_name)->value;
        if (!empty($value)) {
          return $value;
        }
      }
    }
    
    return null;
  }

}
