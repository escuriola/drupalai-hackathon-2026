<?php

namespace Drupal\edaitorial\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Event subscriber for node operations.
 *
 * Note: This is a placeholder. Cache invalidation is handled via hooks.
 */
class NodeEventSubscriber {

  /**
   * Constructs a NodeEventSubscriber object.
   */
  public function __construct(
    protected readonly CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {}

  /**
   * Invalidates edAItorial cache tags.
   *
   * This method is called from hooks in edaitorial.module.
   *
   * @param array $tags
   *   Cache tags to invalidate.
   */
  public function invalidateCacheTags(array $tags = []): void {
    $default_tags = [
      'edaitorial:dashboard',
      'edaitorial:seo',
      'edaitorial:accessibility',
      'edaitorial:content_audit',
      'edaitorial:analysis',
    ];
    
    $this->cacheTagsInvalidator->invalidateTags(array_merge($default_tags, $tags));
  }

}