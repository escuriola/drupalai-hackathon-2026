<?php

namespace Drupal\edaitorial\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\edaitorial\Service\MetricsCollector;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for edAItorial Dashboard.
 */
class DashboardController extends ControllerBase {

  /**
   * The metrics collector service.
   *
   * @var \Drupal\edaitorial\Service\MetricsCollector
   */
  protected $metricsCollector;

  /**
   * Constructs a DashboardController object.
   *
   * @param \Drupal\edaitorial\Service\MetricsCollector $metrics_collector
   *   The metrics collector service.
   */
  public function __construct(MetricsCollector $metrics_collector) {
    $this->metricsCollector = $metrics_collector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('edaitorial.metrics_collector')
    );
  }

  /**
   * Main dashboard page.
   *
   * @return array
   *   Render array for the dashboard.
   */
  public function dashboard() {
    $metrics = $this->metricsCollector->collectAllMetrics();

    return [
      '#theme' => 'edaitorial_dashboard',
      '#metrics' => $metrics,
      '#attached' => [
        'library' => [
          'edaitorial/dashboard',
        ],
      ],
    ];
  }

  /**
   * SEO Overview page.
   *
   * @return array
   *   Render array for SEO overview.
   */
  public function seoOverview() {
    $metrics = $this->metricsCollector->collectSeoMetrics();

    return [
      '#theme' => 'edaitorial_seo_overview',
      '#metrics' => $metrics,
      '#attached' => [
        'library' => [
          'edaitorial/dashboard',
        ],
      ],
    ];
  }

  /**
   * Accessibility page.
   *
   * @return array
   *   Render array for accessibility.
   */
  public function accessibility() {
    $metrics = $this->metricsCollector->collectAccessibilityMetrics();

    return [
      '#theme' => 'edaitorial_accessibility',
      '#metrics' => $metrics,
      '#attached' => [
        'library' => [
          'edaitorial/dashboard',
        ],
      ],
    ];
  }

  /**
   * Content Audit page - fast listing without AI analysis.
   *
   * @return array
   *   Render array for content audit.
   */
  public function contentAudit() {
    // Use fast list method - no AI analysis here
    $audit_results = $this->metricsCollector->auditContentList();
    
    // Don't calculate scores here - will be done on detail view
    // Just mark as "pending" for display
    foreach ($audit_results as &$item) {
      $item['score'] = NULL; // Not calculated yet
      $item['score_class'] = 'pending';
      $item['issues'] = [];
      $item['issue_count'] = 0;
    }

    return [
      '#theme' => 'edaitorial_content_audit',
      '#audit_results' => $audit_results,
      '#attached' => [
        'library' => [
          'edaitorial/dashboard',
        ],
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
  public function detailTitle($node) {
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $node_entity = $node_storage->load($node);
    
    if ($node_entity) {
      return $this->t('Content Analysis: @title', ['@title' => $node_entity->getTitle()]);
    }
    
    return $this->t('Content Analysis');
  }

  /**
   * Content Audit detail page for a specific node.
   * This is where the full AI analysis happens.
   *
   * @param int $node
   *   The node ID.
   *
   * @return array
   *   Render array for content audit detail.
   */
  public function contentAuditDetail($node) {
    // Load the node
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $node_entity = $node_storage->load($node);
    
    if (!$node_entity) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    
    // Run full AI analysis for THIS specific node only
    $node_data = $this->metricsCollector->analyzeSpecificNode($node);
    
    if (!$node_data) {
      $node_data = [
        'id' => $node,
        'title' => $node_entity->getTitle(),
        'type' => $node_entity->bundle(),
        'status' => $node_entity->isPublished(),
        'status_label' => $node_entity->isPublished() ? 'Published' : 'Draft',
        'issues' => [],
        'issue_count' => 0,
      ];
    }
    
    // Calculate score
    $node_data['score'] = $this->calculateScore($node_data['issues']);
    $node_data['score_class'] = $this->getScoreClass($node_data['score']);
    
    // Group issues by type and severity
    $node_data['issues_by_type'] = $this->groupIssuesByType($node_data['issues']);
    $node_data['issues_by_severity'] = $this->groupIssuesBySeverity($node_data['issues']);
    
    return [
      '#theme' => 'edaitorial_content_audit_detail',
      '#node' => $node_entity,
      '#audit_data' => $node_data,
      '#attached' => [
        'library' => [
          'edaitorial/dashboard',
        ],
      ],
    ];
  }

  /**
   * Calculate score based on issues.
   *
   * @param array $issues
   *   Array of issues.
   *
   * @return int
   *   Score from 0 to 100.
   */
  protected function calculateScore(array $issues) {
    $score = 100;
    
    foreach ($issues as $issue) {
      $severity = $issue['severity'] ?? 'Low';
      
      switch ($severity) {
        case 'Critical':
          $score -= 10;
          break;
        case 'High':
          $score -= 5;
          break;
        case 'Medium':
          $score -= 2;
          break;
        case 'Low':
          $score -= 1;
          break;
      }
    }
    
    return max(0, $score);
  }

  /**
   * Get CSS class based on score.
   *
   * @param int $score
   *   The score.
   *
   * @return string
   *   CSS class.
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
   * Group issues by type.
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
   * Group issues by severity.
   *
   * @param array $issues
   *   Array of issues.
   *
   * @return array
   *   Issues grouped by severity.
   */
  protected function groupIssuesBySeverity(array $issues) {
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
    
    return array_filter($grouped);
  }

}
