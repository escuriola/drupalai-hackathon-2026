<?php

namespace Drupal\edaitorial\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\edaitorial\Service\MetricsCollector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for edAItorial Dashboard.
 *
 * Provides dashboard pages for SEO and accessibility metrics,
 * content audit listings, and detailed analysis views.
 */
class DashboardController extends ControllerBase {

  /**
   * Constructs a DashboardController object.
   */
  public function __construct(
    protected readonly MetricsCollector $metricsCollector,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('edaitorial.metrics_collector')
    );
  }

  /**
   * Main dashboard page.
   *
   * Displays overall site health metrics, SEO and accessibility scores,
   * and recent activity.
   *
   * @return array
   *   Render array for the dashboard.
   */
  public function dashboard(): array {
    $metrics = $this->metricsCollector->collectAllMetrics();

    return [
      '#theme' => 'edaitorial_dashboard',
      '#metrics' => $metrics,
      '#attached' => [
        'library' => ['edaitorial/dashboard'],
      ],
      '#cache' => [
        'max-age' => 300, // Cache for 5 minutes
        'tags' => ['edaitorial:dashboard'],
      ],
    ];
  }

  /**
   * SEO Overview page.
   *
   * Displays detailed SEO metrics, checks, and recommendations.
   *
   * @return array
   *   Render array for SEO overview.
   */
  public function seoOverview(): array {
    $metrics = $this->metricsCollector->collectSeoMetrics();

    return [
      '#theme' => 'edaitorial_seo_overview',
      '#metrics' => $metrics,
      '#attached' => [
        'library' => ['edaitorial/dashboard'],
      ],
      '#cache' => [
        'max-age' => 600, // Cache for 10 minutes
        'tags' => ['edaitorial:seo'],
      ],
    ];
  }

  /**
   * Accessibility page.
   *
   * Displays WCAG compliance metrics and accessibility analysis.
   *
   * @return array
   *   Render array for accessibility overview.
   */
  public function accessibility(): array {
    $metrics = $this->metricsCollector->collectAccessibilityMetrics();

    return [
      '#theme' => 'edaitorial_accessibility',
      '#metrics' => $metrics,
      '#attached' => [
        'library' => ['edaitorial/dashboard'],
      ],
      '#cache' => [
        'max-age' => 600, // Cache for 10 minutes
        'tags' => ['edaitorial:accessibility'],
      ],
    ];
  }

  /**
   * Content Audit page - fast listing without AI analysis.
   *
   * Displays a list of all content with basic information.
   * Full analysis is performed only when viewing individual items.
   *
   * @return array
   *   Render array for content audit listing.
   */
  public function contentAudit(): array {
    // Use fast list method - no AI analysis here for performance
    $audit_results = $this->metricsCollector->auditContentList();
    
    // Mark all items as pending analysis
    foreach ($audit_results as &$item) {
      $item['score'] = NULL;
      $item['score_class'] = 'pending';
      $item['issues'] = [];
      $item['issue_count'] = 0;
    }

    return [
      '#theme' => 'edaitorial_content_audit',
      '#audit_results' => $audit_results,
      '#attached' => [
        'library' => ['edaitorial/dashboard'],
      ],
      '#cache' => [
        'max-age' => 180, // Cache for 3 minutes
        'tags' => ['node_list', 'edaitorial:content_audit'],
      ],
    ];
  }

  /**
   * Title callback for content audit detail.
   *
   * @param int $node
   *   The node ID.
   *
   * @return string
   *   The page title.
   */
  public function detailTitle(int $node): string {
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $node_entity = $node_storage->load($node);
    
    if ($node_entity) {
      return $this->t('Content Analysis: @title', [
        '@title' => $node_entity->getTitle(),
      ]);
    }
    
    return $this->t('Content Analysis');
  }

  /**
   * Content Audit detail page for a specific node.
   *
   * This is where the full AI analysis happens for individual content items.
   *
   * @param int $node
   *   The node ID.
   *
   * @return array
   *   Render array for content audit detail.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the node is not found.
   */
  public function contentAuditDetail(int $node): array {
    // Load the node
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $node_entity = $node_storage->load($node);
    
    if (!$node_entity) {
      throw new NotFoundHttpException();
    }
    
    // Run full AI analysis for this specific node only
    $node_data = $this->metricsCollector->analyzeSpecificNode($node);
    
    if (!$node_data) {
      $node_data = $this->createEmptyNodeData($node_entity);
    }
    
    // Calculate score and organize issues
    $node_data['score'] = $this->calculateScore($node_data['issues']);
    $node_data['score_class'] = $this->getScoreClass($node_data['score']);
    $node_data['issues_by_type'] = $this->groupIssuesByType($node_data['issues']);
    $node_data['issues_by_severity'] = $this->groupIssuesBySeverity($node_data['issues']);
    
    return [
      '#theme' => 'edaitorial_content_audit_detail',
      '#node' => $node_entity,
      '#audit_data' => $node_data,
      '#attached' => [
        'library' => ['edaitorial/dashboard'],
      ],
      '#cache' => [
        'max-age' => 900, // Cache for 15 minutes
        'tags' => ['node:' . $node, 'edaitorial:analysis'],
      ],
    ];
  }

  /**
   * Create empty node data structure.
   *
   * @param \Drupal\node\NodeInterface $node_entity
   *   The node entity.
   *
   * @return array
   *   Empty node data structure.
   */
  protected function createEmptyNodeData($node_entity): array {
    return [
      'id' => $node_entity->id(),
      'title' => $node_entity->getTitle(),
      'type' => $node_entity->bundle(),
      'status' => $node_entity->isPublished(),
      'status_label' => $node_entity->isPublished() ? 'Published' : 'Draft',
      'issues' => [],
      'issue_count' => 0,
      'changed' => $node_entity->getChangedTime(),
    ];
  }

  /**
   * Calculate score based on issues.
   *
   * @param array $issues
   *   Array of issues with severity levels.
   *
   * @return int
   *   Score from 0 to 100.
   */
  protected function calculateScore(array $issues): int {
    $score = 100;
    
    foreach ($issues as $issue) {
      $severity = $issue['severity'] ?? 'Low';
      
      $score -= match ($severity) {
        'Critical' => 15,
        'High' => 10,
        'Medium' => 5,
        'Low' => 2,
        default => 1,
      };
    }
    
    return max(0, $score);
  }

  /**
   * Get CSS class based on score.
   *
   * @param int $score
   *   The score value.
   *
   * @return string
   *   CSS class name.
   */
  protected function getScoreClass(int $score): string {
    return match (true) {
      $score >= 90 => 'excellent',
      $score >= 75 => 'good',
      $score >= 50 => 'fair',
      $score >= 25 => 'poor',
      default => 'critical',
    };
  }

  /**
   * Group issues by type.
   */
  protected function groupIssuesByType(array $issues): array {
    return array_reduce($issues, function($grouped, $issue) {
      $type = $issue['type'] ?? 'Other';
      $grouped[$type][] = $issue;
      return $grouped;
    }, []);
  }

  /**
   * Group issues by severity.
   *
   * @param array $issues
   *   Array of issues.
   *
   * @return array
   *   Issues grouped by severity, filtered to remove empty groups.
   */
  protected function groupIssuesBySeverity(array $issues): array {
    $grouped = [
      'Critical' => [],
      'High' => [],
      'Medium' => [],
      'Low' => [],
    ];
    
    foreach ($issues as $issue) {
      $severity = $issue['severity'] ?? 'Low';
      if (isset($grouped[$severity])) {
        $grouped[$severity][] = $issue;
      }
    }
    
    // Remove empty groups
    return array_filter($grouped);
  }

}
