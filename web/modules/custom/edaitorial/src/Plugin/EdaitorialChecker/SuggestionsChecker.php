<?php

namespace Drupal\edaitorial\Plugin\EdaitorialChecker;

use Drupal\edaitorial\Plugin\EdaitorialCheckerBase;
use Drupal\node\NodeInterface;

/**
 * Provides content improvement suggestions using AI.
 *
 * @EdaitorialChecker(
 *   id = "suggestions",
 *   label = @Translation("Content Suggestions"),
 *   description = @Translation("Provides AI-powered suggestions to improve content quality"),
 *   category = "content",
 *   weight = 30
 * )
 */
class SuggestionsChecker extends EdaitorialCheckerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(NodeInterface $node) {
    if ($this->shouldUseAi()) {
      return $this->analyzeWithAi($node);
    }
    
    // Fallback to basic rule-based suggestions
    return $this->analyzeWithoutAi($node);
  }

  /**
   * Analyze using AI.
   */
  protected function analyzeWithAi(NodeInterface $node) {
    $config = $this->configFactory->get('edaitorial.settings');
    $prompt_template = $config->get('suggestions_prompt');
    
    if (empty($prompt_template)) {
      return [];
    }
    
    $title = $node->getTitle();
    $body = $this->getTextContent($node) ?? '';
    $body_text = strip_tags($body);
    $word_count = str_word_count($body_text);
    
    // Replace placeholders
    $prompt = str_replace(
      ['{title}', '{body}', '{word_count}'],
      [$title, $body_text, $word_count],
      $prompt_template
    );
    
    // Call AI
    $response = $this->callAi($prompt);
    
    // Parse response
    return $this->parseAiResponse($response);
  }

  /**
   * Analyze without AI (basic rule-based fallback).
   */
  protected function analyzeWithoutAi(NodeInterface $node) {
    $issues = [];
    $body = $this->getTextContent($node);
    
    if (!$body) {
      return $issues;
    }
    
    $text_content = strip_tags($body);
    $word_count = str_word_count($text_content);
    
    // Suggest headings for long content
    $has_headings = preg_match('/<h[2-6]/i', $body);
    if ($word_count > 300 && !$has_headings) {
      $issues[] = [
        'description' => 'Suggestion: Break up long content with headings (H2, H3) for better readability',
        'type' => 'Content',
        'severity' => 'Low',
        'impact' => 'Low',
      ];
    }
    
    // Suggest lists
    $has_lists = preg_match('/<[uo]l/i', $body);
    if ($word_count > 500 && !$has_lists) {
      $issues[] = [
        'description' => 'Suggestion: Consider using bullet points or numbered lists to organize information',
        'type' => 'Content',
        'severity' => 'Low',
        'impact' => 'Low',
      ];
    }
    
    // Suggest images
    $img_count = preg_match_all('/<img/i', $body);
    if ($word_count > 500 && $img_count === 0) {
      $issues[] = [
        'description' => 'Suggestion: Add images or visual content to make the page more engaging',
        'type' => 'Content',
        'severity' => 'Low',
        'impact' => 'Low',
      ];
    }
    
    return $issues;
  }

}
