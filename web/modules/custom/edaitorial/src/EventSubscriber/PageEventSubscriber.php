<?php

namespace Drupal\edaitorial\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Page\PageAttachmentsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for page operations.
 */
class PageEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a PageEventSubscriber object.
   */
  public function __construct(
    protected readonly RouteMatchInterface $routeMatch,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::PAGE_ATTACHMENTS => 'onPageAttachments',
    ];
  }

  /**
   * Responds to page attachments events.
   */
  public function onPageAttachments(PageAttachmentsEvent $event): void {
    $route_name = $this->routeMatch->getRouteName();

    if (!$route_name || !str_starts_with($route_name, 'edaitorial.')) {
      return;
    }

    $attachments = &$event->getAttachments();
    $attachments['#attached']['html_head'][] = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'viewport',
          'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no',
        ],
      ],
      'edaitorial_viewport',
    ];
  }

}