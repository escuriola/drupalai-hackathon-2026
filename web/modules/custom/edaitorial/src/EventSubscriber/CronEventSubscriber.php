<?php

namespace Drupal\edaitorial\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\edaitorial\Service\AccessibilityAnalyzer;
use Drupal\edaitorial\Service\SeoAnalyzer;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Cron\CronEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for cron operations.
 */
class CronEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a CronEventSubscriber object.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly SeoAnalyzer $seoAnalyzer,
    protected readonly AccessibilityAnalyzer $accessibilityAnalyzer,
    protected readonly StateInterface $state,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::CRON => 'onCron',
    ];
  }

  /**
   * Responds to cron events.
   */
  public function onCron(CronEvent $event): void {
    try {
      $pages_count = $this->entityTypeManager
        ->getStorage('node')
        ->getQuery()
        ->condition('status', 1)
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      $this->state->set('edaitorial.previous_metrics', [
        'pages_crawled' => $pages_count,
        'seo_issues' => $this->seoAnalyzer->countSeoIssues(),
        'a11y_issues' => $this->accessibilityAnalyzer->countAccessibilityIssues(),
        'timestamp' => time(),
      ]);

      $this->logger->info('Metrics updated: @pages pages analyzed', [
        '@pages' => $pages_count,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Cron metrics update failed: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

}