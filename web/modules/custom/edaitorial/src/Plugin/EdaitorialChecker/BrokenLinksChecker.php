<?php

namespace Drupal\edaitorial\Plugin\EdaitorialChecker;

use Drupal\edaitorial\Plugin\EdaitorialCheckerBase;
use Drupal\node\NodeInterface;

/**
 * Checks for broken links using AI.
 *
 * @EdaitorialChecker(
 *   id = "broken_links",
 *   label = @Translation("Broken Links Checker"),
 *   description = @Translation("Detects broken links using AI analysis"),
 *   category = "seo",
 *   weight = 10
 * )
 */
class BrokenLinksChecker extends EdaitorialCheckerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(NodeInterface $node) {
    if ($this->shouldUseAi()) {
      return $this->analyzeWithAi($node);
    }
    
    // Fallback to basic checks
    return $this->analyzeWithoutAi($node);
  }

  /**
   * Analyze using AI.
   */
  protected function analyzeWithAi(NodeInterface $node) {
    $config = $this->configFactory->get('edaitorial.settings');
    $prompt_template = $config->get('broken_links_prompt');
    
    if (empty($prompt_template)) {
      return [];
    }
    
    $body = $this->getTextContent($node) ?? '';
    
    // Get list of available nodes for AI to check against
    $available_nodes = $this->getAvailableNodes();
    
    // Replace placeholders
    $prompt = str_replace(
      ['{body}', '{available_nodes}'],
      [$body, $available_nodes],
      $prompt_template
    );
    
    // Call AI
    $response = $this->callAi($prompt);
    
    // Parse response
    $issues = $this->parseAiResponse($response);
    
    // Also check link fields with AI
    $link_field_issues = $this->checkLinkFieldsWithAi($node);
    
    return array_merge($issues, $link_field_issues);
  }

  /**
   * Check link fields using AI.
   */
  protected function checkLinkFieldsWithAi(NodeInterface $node) {
    $link_fields_data = [];
    
    foreach ($node->getFieldDefinitions() as $field_name => $field_def) {
      if ($field_def->getType() === 'link' && $node->hasField($field_name) && !$node->get($field_name)->isEmpty()) {
        foreach ($node->get($field_name) as $link_item) {
          $link_fields_data[] = [
            'field' => $field_name,
            'uri' => $link_item->uri ?? '',
            'title' => $link_item->title ?? '',
          ];
        }
      }
    }
    
    if (empty($link_fields_data)) {
      return [];
    }
    
    $available_nodes = $this->getAvailableNodes();
    
    $prompt = <<<EOT
Analyze the following link fields for broken or problematic links.

Link Fields Data:
{$this->formatLinkFieldsData($link_fields_data)}

Available internal nodes: {$available_nodes}

Check for:
1. Empty or broken URIs
2. Broken entity:node/X or internal:/node/X references
3. Empty link titles
4. Poor link titles (click here, read more, etc.)

Return ONLY a JSON array with issues found:
[
  {
    "description": "Issue description",
    "type": "SEO|Accessibility",
    "severity": "High|Medium|Low",
    "impact": "High|Medium|Low"
  }
]

If no issues, return empty array: []
EOT;
    
    $response = $this->callAi($prompt);
    return $this->parseAiResponse($response);
  }

  /**
   * Format link fields data for AI.
   */
  protected function formatLinkFieldsData(array $link_fields_data): string {
    $formatted = '';
    foreach ($link_fields_data as $index => $data) {
      $formatted .= sprintf(
        "\nLink %d:\n  Field: %s\n  URI: %s\n  Title: %s\n",
        $index + 1,
        $data['field'],
        $data['uri'],
        $data['title']
      );
    }
    return $formatted;
  }

  /**
   * Get list of available node IDs.
   */
  protected function getAvailableNodes(): string {
    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->execute();
    
    return implode(', ', array_slice($nids, 0, 100));
  }

  /**
   * Analyze without AI (basic fallback).
   */
  protected function analyzeWithoutAi(NodeInterface $node) {
    $issues = [];
    $body = $this->getTextContent($node);
    
    if (!$body) {
      return $issues;
    }
    
    // Basic check for empty links
    if (preg_match_all('/<a[^>]*href=["\']([^"\']*)["\']/', $body, $matches)) {
      $empty_links = 0;
      foreach ($matches[1] as $href) {
        if (empty($href) || $href === '#') {
          $empty_links++;
        }
      }
      
      if ($empty_links > 0) {
        $issues[] = [
          'description' => "{$empty_links} empty or hash-only link(s) detected",
          'type' => 'SEO',
          'severity' => 'Medium',
          'impact' => 'Medium',
        ];
      }
    }
    
    return $issues;
  }

}
