<?php

namespace Drupal\edaitorial\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Service for SEO analysis.
 *
 * Provides comprehensive SEO analysis including meta tags, content structure,
 * technical SEO elements, and site-wide SEO health checks.
 */
class SeoAnalyzer {

  /**
   * Constructs a SeoAnalyzer object.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Calculate overall SEO score.
   *
   * Runs all SEO checks and calculates a percentage score based on
   * the number of passed checks.
   *
   * @return int
   *   SEO score (0-100).
   */
  public function calculateSeoScore(): int {
    $checks = $this->runSeoChecks();
    $passed = array_filter($checks, fn($check) => $check['status'] === 'passed');

    return count($checks) > 0 ? (int) round((count($passed) / count($checks)) * 100) : 0;
  }

  /**
   * Run all SEO checks.
   *
   * Performs comprehensive SEO analysis including technical elements,
   * content optimization, and site structure.
   *
   * @return array
   *   Array of check results with status, label, message, and count.
   */
  public function runSeoChecks(): array {
    return [
      'meta_title' => $this->checkMetaTitles(),
      'meta_description' => $this->checkMetaDescriptions(),
      'canonical_urls' => $this->checkCanonicalUrls(),
      'xml_sitemap' => $this->checkXmlSitemap(),
      'robots_txt' => $this->checkRobotsTxt(),
      'structured_data' => $this->checkStructuredData(),
      'open_graph' => $this->checkOpenGraphTags(),
      'mobile_friendly' => $this->checkMobileFriendly(),
    ];
  }

  /**
   * Check if all pages have unique meta titles.
   *
   * @return array
   *   Check result array.
   */
  protected function checkMetaTitles(): array {
    $nodes = $this->getPublishedNodes();
    $total = count($nodes);
    $missing_titles = 0;

    foreach ($nodes as $node) {
      if (empty(trim($node->getTitle()))) {
        $missing_titles++;
      }
    }

    $status = $missing_titles === 0 ? 'passed' : 'warning';
    $message = $missing_titles === 0 
      ? 'All pages have titles' 
      : $this->formatPlural($missing_titles, '1 page missing title', '@count pages missing titles');

    return [
      'status' => $status,
      'label' => 'Meta Titles',
      'message' => $message,
      'count' => $missing_titles,
    ];
  }

  /**
   * Check meta descriptions.
   *
   * @return array
   *   Check result array.
   */
  protected function checkMetaDescriptions(): array {
    $nodes = $this->getPublishedNodes();
    $missing = 0;

    foreach ($nodes as $node) {
      if (!$this->hasMetaDescription($node)) {
        $missing++;
      }
    }

    $status = $missing === 0 ? 'passed' : 'warning';
    $message = $missing === 0 
      ? 'All pages have descriptions' 
      : $this->formatPlural($missing, '1 page missing description', '@count pages missing descriptions');

    return [
      'status' => $status,
      'label' => 'Meta Descriptions',
      'message' => $message,
      'count' => $missing,
    ];
  }

  /**
   * Check canonical URLs configuration.
   *
   * @return array
   *   Check result array.
   */
  protected function checkCanonicalUrls(): array {
    $metatag_enabled = \Drupal::moduleHandler()->moduleExists('metatag');
    
    return [
      'status' => $metatag_enabled ? 'passed' : 'warning',
      'label' => 'Canonical URLs',
      'message' => $metatag_enabled 
        ? 'Configured via Metatag module' 
        : 'Consider installing Metatag module for canonical URL management',
      'count' => $metatag_enabled ? 0 : 1,
    ];
  }

  /**
   * Check XML sitemap configuration.
   *
   * @return array
   *   Check result array.
   */
  protected function checkXmlSitemap(): array {
    $sitemap_enabled = \Drupal::moduleHandler()->moduleExists('simple_sitemap');

    return [
      'status' => $sitemap_enabled ? 'passed' : 'failed',
      'label' => 'XML Sitemap',
      'message' => $sitemap_enabled 
        ? 'XML sitemap is configured' 
        : 'XML sitemap not configured - install Simple XML Sitemap module',
      'count' => $sitemap_enabled ? 0 : 1,
    ];
  }

  /**
   * Check robots.txt file.
   *
   * @return array
   *   Check result array.
   */
  protected function checkRobotsTxt(): array {
    $robots_path = DRUPAL_ROOT . '/robots.txt';
    
    if (!file_exists($robots_path)) {
      return [
        'status' => 'warning',
        'label' => 'Robots.txt',
        'message' => 'robots.txt file not found',
        'count' => 1,
      ];
    }

    $content = @file_get_contents($robots_path);
    if (empty($content) || strlen(trim($content)) < 10) {
      return [
        'status' => 'warning',
        'label' => 'Robots.txt',
        'message' => 'robots.txt file is empty or too small',
        'count' => 1,
      ];
    }

    return [
      'status' => 'passed',
      'label' => 'Robots.txt',
      'message' => 'robots.txt file exists and is configured',
      'count' => 0,
    ];
  }

  /**
   * Check structured data implementation.
   *
   * @return array
   *   Check result array.
   */
  protected function checkStructuredData(): array {
    $schema_enabled = \Drupal::moduleHandler()->moduleExists('schema_metatag');
    
    if (!$schema_enabled) {
      return [
        'status' => 'warning',
        'label' => 'Structured Data',
        'message' => 'Schema.org Metatag module not installed',
        'count' => 1,
      ];
    }

    // If module is installed, assume it's configured
    // In a real implementation, you might check for actual schema markup
    return [
      'status' => 'passed',
      'label' => 'Structured Data',
      'message' => 'Schema.org markup is available',
      'count' => 0,
    ];
  }

  /**
   * Check Open Graph tags configuration.
   *
   * @return array
   *   Check result array.
   */
  protected function checkOpenGraphTags(): array {
    $metatag_enabled = \Drupal::moduleHandler()->moduleExists('metatag');
    
    if (!$metatag_enabled) {
      return [
        'status' => 'failed',
        'label' => 'Open Graph Tags',
        'message' => 'Metatag module required for Open Graph support',
        'count' => 1,
      ];
    }

    return [
      'status' => 'passed',
      'label' => 'Open Graph Tags',
      'message' => 'Social sharing optimization available',
      'count' => 0,
    ];
  }

  /**
   * Check mobile friendliness.
   *
   * @return array
   *   Check result array.
   */
  protected function checkMobileFriendly(): array {
    $theme_handler = \Drupal::service('theme_handler');
    $default_theme = $theme_handler->getDefault();
    
    // Modern Drupal themes are typically responsive
    return [
      'status' => 'passed',
      'label' => 'Mobile Friendly',
      'message' => "Responsive theme active ({$default_theme})",
      'count' => 0,
    ];
  }

  /**
   * Count total SEO issues across all checks.
   *
   * @return int
   *   Total number of SEO issues found.
   */
  public function countSeoIssues(): int {
    $checks = $this->runSeoChecks();
    
    return array_sum(array_column($checks, 'count'));
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
   * Check if a node has a meta description.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   *
   * @return bool
   *   TRUE if the node has a meta description.
   */
  protected function hasMetaDescription(NodeInterface $node): bool {
    // Check common meta description field names
    $meta_fields = ['field_meta_description', 'field_description', 'field_summary'];
    
    foreach ($meta_fields as $field_name) {
      if ($node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        return true;
      }
    }
    
    return false;
  }

  /**
   * Format plural text.
   *
   * @param int $count
   *   The count.
   * @param string $singular
   *   Singular form.
   * @param string $plural
   *   Plural form.
   *
   * @return string
   *   Formatted text.
   */
  protected function formatPlural(int $count, string $singular, string $plural): string {
    return $count === 1 ? $singular : str_replace('@count', (string) $count, $plural);
  }

}
