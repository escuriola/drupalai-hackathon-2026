<?php

namespace Drupal\edaitorial\Plugin;

use Drupal\node\NodeInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Edaitorial Checker plugins.
 */
interface EdaitorialCheckerInterface extends PluginInspectionInterface {

  /**
   * Analyzes a node and returns issues found.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to analyze.
   *
   * @return array
   *   Array of issues found. Each issue should have:
   *   - description: string
   *   - type: string (SEO, Accessibility, Content, Security)
   *   - severity: string (Critical, High, Medium, Low)
   *   - impact: string (High, Medium, Low)
   */
  public function analyze(NodeInterface $node);

  /**
   * Returns the category of this checker.
   *
   * @return string
   *   The category (seo, accessibility, content, security).
   */
  public function getCategory();

  /**
   * Returns the label of this checker.
   *
   * @return string
   *   The human-readable label.
   */
  public function getLabel();

  /**
   * Returns whether this checker is enabled.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public function isEnabled();

}
