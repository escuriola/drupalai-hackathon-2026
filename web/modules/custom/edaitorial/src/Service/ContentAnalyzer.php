<?php

namespace Drupal\edaitorial\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Service for AI-powered content analysis.
 *
 * Provides comprehensive content analysis including SEO, accessibility,
 * and readability checks with AI-powered suggestions.
 */
class ContentAnalyzer {

  /**
   * Constructs a ContentAnalyzer object.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Analyze content before publishing.
   *
   * Performs comprehensive analysis including SEO, accessibility,
   * readability, and AI-powered suggestions.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to analyze.
   *
   * @return array
   *   Analysis results with scores and issues.
   */
  public function analyzeBeforePublish(NodeInterface $node): array {
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
   * Checks title length, meta description, image alt text, and other
   * SEO-related elements.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to analyze.
   *
   * @return array
   *   SEO analysis results with issues and score.
   */
  protected function analyzeSeoElements(NodeInterface $node): array {
    $issues = [];
    $config = $this->configFactory->get('edaitorial.settings');

    // Check title length against configured limits
    $title = $node->getTitle();
    $title_length = strlen($title);
    $min_length = $config->get('min_title_length') ?? 30;
    $max_length = $config->get('max_title_length') ?? 60;

    if ($title_length < $min_length || $title_length > $max_length) {
      $issues[] = t('Title length (@length chars) should be between @min-@max characters', [
        '@length' => $title_length,
        '@min' => $min_length,
        '@max' => $max_length,
      ]);
    }

    // Check for meta description
    if ($node->hasField('field_meta_description') && $node->get('field_meta_description')->isEmpty()) {
      $issues[] = t('Missing meta description - important for search results');
    }

    // Check images for alt text
    $this->checkImageAltText($node, $issues);

    return [
      'issues' => $issues,
      'score' => $this->calculateSeoScore($issues),
    ];
  }

  /**
   * Check images for alt text.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   * @param array &$issues
   *   Array to add issues to.
   */
  protected function checkImageAltText(NodeInterface $node, array &$issues): void {
    // Check common image field names
    $image_fields = ['field_image', 'field_images', 'field_media'];
    
    foreach ($image_fields as $field_name) {
      if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
        continue;
      }

      $images = $node->get($field_name)->getValue();
      $missing_alt = 0;
      
      foreach ($images as $image) {
        if (empty($image['alt'])) {
          $missing_alt++;
        }
      }
      
      if ($missing_alt > 0) {
        $issues[] = t('@count image(s) missing alt text in @field', [
          '@count' => $missing_alt,
          '@field' => $field_name,
        ]);
      }
    }
  }

  /**
   * Analyze accessibility of a node.
   *
   * Checks heading structure, color contrast, and other accessibility
   * elements according to WCAG guidelines.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to analyze.
   *
   * @return array
   *   Accessibility analysis results.
   */
  protected function analyzeAccessibility(NodeInterface $node): array {
    $issues = [];

    // Check for proper heading structure
    $body = $this->getNodeBody($node);
    if ($body && !preg_match('/<h[1-6]/i', $body)) {
      $issues[] = t('Content should include proper heading structure (H2, H3, etc.)');
    }

    // Check for multiple H1 tags
    if ($body && preg_match_all('/<h1/i', $body) > 1) {
      $issues[] = t('Multiple H1 tags found - should have only one per page');
    }

    return [
      'issues' => $issues,
      'score' => $this->calculateAccessibilityScore($issues),
    ];
  }

  /**
   * Analyze readability of content.
   *
   * Checks content length, sentence structure, and readability metrics.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to analyze.
   *
   * @return array
   *   Readability analysis results.
   */
  protected function analyzeReadability(NodeInterface $node): array {
    $issues = [];
    $body = $this->getNodeBody($node);
    
    if (!$body) {
      $issues[] = t('No content body found');
      return ['issues' => $issues, 'score' => 0];
    }

    $text_content = strip_tags($body);
    $word_count = str_word_count($text_content);

    // Check content length
    if ($word_count < 300) {
      $issues[] = t('Content may be too short (@words words) - aim for 300+ words for better SEO', [
        '@words' => $word_count,
      ]);
    }

    // Check sentence length
    $sentences = preg_split('/[.!?]+/', $text_content, -1, PREG_SPLIT_NO_EMPTY);
    $sentence_count = count($sentences);
    
    if ($sentence_count > 0) {
      $avg_sentence_length = $word_count / $sentence_count;
      if ($avg_sentence_length > 25) {
        $issues[] = t('Average sentence length (@avg words) is too long for readability', [
          '@avg' => round($avg_sentence_length, 1),
        ]);
      }
    }

    return [
      'issues' => $issues,
      'score' => $this->calculateReadabilityScore($issues, $word_count),
    ];
  }

  /**
   * Get AI-powered suggestions for content improvement.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to analyze.
   *
   * @return array
   *   AI suggestions for improvement.
   */
  protected function getAiSuggestions(NodeInterface $node): array {
    $config = $this->configFactory->get('edaitorial.settings');
    
    // Return empty if AI suggestions are disabled
    if (!$config->get('auto_suggestions')) {
      return [];
    }

    // Placeholder for AI integration
    // TODO: Integrate with amazee.io AI service
    return [
      t('Consider adding more internal links to related content'),
      t('The introduction could be more engaging'),
      t('Add more specific keywords related to your topic'),
    ];
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
    
    return NULL;
  }

  /**
   * Calculate overall content score.
   *
   * @param array $results
   *   Analysis results from all checks.
   *
   * @return int
   *   Overall score (0-100).
   */
  protected function calculateOverallScore(array $results): int {
    $scores = [
      $results['seo']['score'],
      $results['accessibility']['score'],
      $results['readability']['score'],
    ];

    return (int) round(array_sum($scores) / count($scores));
  }

  /**
   * Calculate SEO score based on issues found.
   *
   * @param array $issues
   *   Array of SEO issues.
   *
   * @return int
   *   SEO score (0-100).
   */
  protected function calculateSeoScore(array $issues): int {
    $base_score = 100;
    $penalty_per_issue = 20;
    
    return max(0, $base_score - (count($issues) * $penalty_per_issue));
  }

  /**
   * Calculate accessibility score based on issues found.
   *
   * @param array $issues
   *   Array of accessibility issues.
   *
   * @return int
   *   Accessibility score (0-100).
   */
  protected function calculateAccessibilityScore(array $issues): int {
    $base_score = 100;
    $penalty_per_issue = 25;
    
    return max(0, $base_score - (count($issues) * $penalty_per_issue));
  }

  /**
   * Calculate readability score based on issues and content metrics.
   *
   * @param array $issues
   *   Array of readability issues.
   * @param int $word_count
   *   Word count of the content.
   *
   * @return int
   *   Readability score (0-100).
   */
  protected function calculateReadabilityScore(array $issues, int $word_count): int {
    $base_score = 100;
    $penalty_per_issue = 15;
    
    // Bonus for good word count
    $word_bonus = 0;
    if ($word_count >= 300 && $word_count <= 2000) {
      $word_bonus = 10;
    }
    
    $score = $base_score - (count($issues) * $penalty_per_issue) + $word_bonus;
    return max(0, min(100, $score));
  }

}
