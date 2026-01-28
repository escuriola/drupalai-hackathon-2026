<?php

namespace Drupal\edaitorial\Plugin\EdaitorialChecker;

use Drupal\edaitorial\Plugin\EdaitorialCheckerBase;
use Drupal\node\NodeInterface;

/**
 * Performs comprehensive SEO checks using AI.
 *
 * @EdaitorialChecker(
 *   id = "seo",
 *   label = @Translation("SEO Checker"),
 *   description = @Translation("Performs comprehensive SEO analysis using AI"),
 *   category = "seo",
 *   weight = 5
 * )
 */
class SeoChecker extends EdaitorialCheckerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(NodeInterface $node) {
    if ($this->shouldUseAi()) {
      return $this->analyzeWithAi($node);
    }
    
    // Fallback to basic checks if AI is disabled
    return $this->analyzeWithoutAi($node);
  }

  /**
   * Analyze using AI.
   */
  protected function analyzeWithAi(NodeInterface $node) {
    $config = $this->configFactory->get('edaitorial.settings');
    $prompt_template = $config->get('seo_prompt');
    
    if (empty($prompt_template)) {
      return [];
    }
    
    $title = $node->getTitle();
    $body = $this->getTextContent($node) ?? '';
    $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id());
    
    // Replace placeholders
    $prompt = str_replace(
      ['{title}', '{body}', '{url}'],
      [$title, strip_tags($body), $url],
      $prompt_template
    );
    
    // Call AI
    $response = $this->callAi($prompt);
    
    // Parse response
    return $this->parseAiResponse($response);
  }

  /**
   * Analyze without AI (basic fallback).
   */
  protected function analyzeWithoutAi(NodeInterface $node) {
    $issues = [];
    $title = $node->getTitle();
    
    // Basic title length check
    $title_length = strlen($title);
    if ($title_length < 30 || $title_length > 60) {
      $issues[] = [
        'description' => "Title length ({$title_length} chars) not optimal (30-60 recommended)",
        'type' => 'SEO',
        'severity' => 'Medium',
        'impact' => 'Medium',
      ];
    }
    
    // Basic content length check
    $body = $this->getTextContent($node);
    if ($body) {
      $word_count = str_word_count(strip_tags($body));
      if ($word_count < 300) {
        $issues[] = [
          'description' => "Content too short: {$word_count} words (min 300 recommended)",
          'type' => 'SEO',
          'severity' => 'Medium',
          'impact' => 'Medium',
        ];
      }
    }
    
    return $issues;
  }

}
