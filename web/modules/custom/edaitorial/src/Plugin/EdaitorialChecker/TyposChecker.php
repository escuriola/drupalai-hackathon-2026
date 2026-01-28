<?php

namespace Drupal\edaitorial\Plugin\EdaitorialChecker;

use Drupal\edaitorial\Plugin\EdaitorialCheckerBase;
use Drupal\node\NodeInterface;

/**
 * Checks for typos and spelling errors using AI.
 *
 * @EdaitorialChecker(
 *   id = "typos",
 *   label = @Translation("Typos Checker"),
 *   description = @Translation("Detects typos and spelling errors using AI"),
 *   category = "content",
 *   weight = 20
 * )
 */
class TyposChecker extends EdaitorialCheckerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(NodeInterface $node) {
    if ($this->shouldUseAi()) {
      return $this->analyzeWithAi($node);
    }
    
    // Fallback to basic dictionary checks
    return $this->analyzeWithoutAi($node);
  }

  /**
   * Analyze using AI.
   */
  protected function analyzeWithAi(NodeInterface $node) {
    $config = $this->configFactory->get('edaitorial.settings');
    $prompt_template = $config->get('typos_prompt');
    
    if (empty($prompt_template)) {
      return [];
    }
    
    $title = $node->getTitle();
    $body = $this->getTextContent($node) ?? '';
    $body_text = strip_tags($body);
    
    // Replace placeholders
    $prompt = str_replace(
      ['{title}', '{body}'],
      [$title, $body_text],
      $prompt_template
    );
    
    // Call AI
    $response = $this->callAi($prompt);
    
    // Parse response
    return $this->parseAiResponse($response);
  }

  /**
   * Analyze without AI (basic dictionary fallback).
   */
  protected function analyzeWithoutAi(NodeInterface $node) {
    $issues = [];
    
    // Basic dictionary of common typos
    $common_typos = [
      'teh' => 'the',
      'recieve' => 'receive',
      'definately' => 'definitely',
      'goverment' => 'government',
      'alot' => 'a lot',
    ];
    
    $title = $node->getTitle();
    $body = $this->getTextContent($node);
    
    $typos_found = [];
    
    // Check title
    foreach ($common_typos as $typo => $correct) {
      if (stripos($title, $typo) !== FALSE) {
        $typos_found[] = "$typo → $correct";
      }
    }
    
    if (!empty($typos_found)) {
      $issues[] = [
        'description' => 'Possible typos in title: ' . implode(', ', $typos_found),
        'type' => 'Content',
        'severity' => 'Medium',
        'impact' => 'Medium',
      ];
    }
    
    // Check body
    if ($body) {
      $body_typos = [];
      $body_text = strip_tags($body);
      
      foreach ($common_typos as $typo => $correct) {
        if (stripos($body_text, $typo) !== FALSE) {
          $body_typos[] = "$typo → $correct";
        }
      }
      
      if (!empty($body_typos)) {
        $issues[] = [
          'description' => count($body_typos) . ' possible typos detected: ' . implode(', ', array_slice($body_typos, 0, 5)),
          'type' => 'Content',
          'severity' => count($body_typos) > 5 ? 'High' : 'Medium',
          'impact' => 'Medium',
        ];
      }
    }
    
    return $issues;
  }

}
