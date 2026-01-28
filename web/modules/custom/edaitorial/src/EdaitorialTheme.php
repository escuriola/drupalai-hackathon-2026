<?php

namespace Drupal\edaitorial;

/**
 * Theme definitions for edAItorial module.
 */
class EdaitorialTheme {

  /**
   * Get theme definitions.
   */
  public static function getThemeDefinitions(): array {
    return [
      'edaitorial_dashboard' => [
        'variables' => ['metrics' => []],
        'template' => 'edaitorial-dashboard',
      ],
      'edaitorial_seo_overview' => [
        'variables' => ['metrics' => []],
        'template' => 'edaitorial-seo-overview',
      ],
      'edaitorial_accessibility' => [
        'variables' => ['metrics' => []],
        'template' => 'edaitorial-accessibility',
      ],
      'edaitorial_content_audit' => [
        'variables' => ['audit_results' => []],
        'template' => 'edaitorial-content-audit',
      ],
      'edaitorial_content_audit_detail' => [
        'variables' => [
          'node' => NULL,
          'audit_data' => [],
        ],
        'template' => 'edaitorial-content-audit-detail',
      ],
    ];
  }

}