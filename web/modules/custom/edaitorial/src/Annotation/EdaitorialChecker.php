<?php

namespace Drupal\edaitorial\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Edaitorial Checker annotation object.
 *
 * @Annotation
 */
class EdaitorialChecker extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the checker.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the checker.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The category of the checker (seo, accessibility, content, security).
   *
   * @var string
   */
  public $category;

  /**
   * The weight for ordering checkers.
   *
   * @var int
   */
  public $weight = 0;

}
