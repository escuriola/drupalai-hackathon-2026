<?php

namespace Drupal\edaitorial_real_time_checker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\edaitorial_real_time_checker\Service\RealTimeAnalyzer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for real-time content analysis.
 */
class RealTimeController extends ControllerBase {

  /**
   * The real-time analyzer service.
   *
   * @var \Drupal\edaitorial_real_time_checker\Service\RealTimeAnalyzer
   */
  protected $analyzer;

  /**
   * Constructs a RealTimeController object.
   *
   * @param \Drupal\edaitorial_real_time_checker\Service\RealTimeAnalyzer $analyzer
   *   The real-time analyzer service.
   */
  public function __construct(RealTimeAnalyzer $analyzer) {
    $this->analyzer = $analyzer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('edaitorial_real_time_checker.analyzer')
    );
  }

  /**
   * Analyzes content in real-time via AJAX.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with analysis results.
   */
  public function analyze(Request $request) {
    $title = $request->request->get('title', '');
    $body = $request->request->get('body', '');
    $content_type = $request->request->get('content_type', 'article');

    // No minimum length restriction - analyze any content

    try {
      // Perform analysis
      $results = $this->analyzer->analyzeContent($title, $body, $content_type);

      // Log the results for debugging
      $this->getLogger('quality_gate')->info('Analysis results: @results', [
        '@results' => print_r($results, TRUE)
      ]);

      // Ensure we have all required keys
      if (!isset($results['score'])) {
        $this->getLogger('quality_gate')->error('Missing score in results!');
        $results['score'] = 0;
      }

      if (!isset($results['category_scores'])) {
        $this->getLogger('quality_gate')->error('Missing category_scores in results!');
        $results['category_scores'] = [];
      }

      $response_data = [
        'status' => 'success',
        'score' => (int) $results['score'],
        'score_class' => $results['score_class'] ?? 'poor',
        'category_scores' => $results['category_scores'] ?? [
            'seo' => 0,
            'accessibility' => 0,
            'typos' => 0,
            'links' => 0,
            'content' => 0,
          ],
        'issues' => $results['issues'],
        'issue_count' => count($results['issues']),
        'suggestions' => $results['suggestions'] ?? [],
        'message' => $this->getScoreMessage($results['score']),
      ];

      $this->getLogger('quality_gate')->info('Sending response: @response', [
        '@response' => json_encode($response_data)
      ]);

      return new JsonResponse($response_data);
    }
    catch (\Exception $e) {
      $this->getLogger('edaitorial_real_time_checker')->error('Real-time analysis failed: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Analysis failed. Please try again.'),
        'score' => 0,
        'issues' => [],
      ], 500);
    }
  }

  /**
   * Get a human-readable message based on score.
   *
   * @param int $score
   *   The content quality score.
   *
   * @return string
   *   The score message.
   */
  protected function getScoreMessage($score) {
    if ($score >= 90) {
      return $this->t('Excellent! Your content meets high quality standards.');
    }
    elseif ($score >= 75) {
      return $this->t('Good content with minor improvements suggested.');
    }
    elseif ($score >= 50) {
      return $this->t('Fair content. Several improvements recommended.');
    }
    elseif ($score >= 25) {
      return $this->t('Needs work. Please review the suggestions below.');
    }
    else {
      return $this->t('Critical issues found. Please address them before publishing.');
    }
  }

  /**
   * Saves analysis results permanently using State API.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response confirming save.
   */
  public function saveAnalysis(Request $request) {
    $node_id = $request->request->get('node_id');
    $score = $request->request->get('score');
    $category_scores_json = $request->request->get('category_scores');
    $issues_json = $request->request->get('issues');

    if (empty($node_id)) {
      return new JsonResponse(['status' => 'error', 'message' => 'Node ID required'], 400);
    }

    try {
      // Decode JSON strings back to arrays
      $category_scores = json_decode($category_scores_json, TRUE);
      $issues = json_decode($issues_json, TRUE);

      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception('Invalid JSON data: ' . json_last_error_msg());
      }

      // Save permanently using State API (persists across sessions and users)
      $state = \Drupal::state();
      $cache_key = 'quality_gate.analysis.' . $node_id;

      $data = [
        'score' => (int) $score,
        'category_scores' => $category_scores,
        'issues' => $issues,
        'timestamp' => time(),
      ];

      $state->set($cache_key, $data);

      $this->getLogger('quality_gate')->info('Saved analysis for node @nid: score=@score, categories=@cats', [
        '@nid' => $node_id,
        '@score' => $score,
        '@cats' => json_encode($category_scores),
      ]);

      return new JsonResponse(['status' => 'success', 'saved' => TRUE]);
    }
    catch (\Exception $e) {
      $this->getLogger('quality_gate')->error('Failed to save analysis: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
  }

  /**
   * Provides AI suggestions for content improvement.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with AI suggestions.
   */
  public function askAi(Request $request) {
    $field = $request->request->get('field', 'title'); // 'title' or 'body'
    $current_value = $request->request->get('value', '');
    $context = $request->request->get('context', ''); // Additional context (e.g., body when improving title)

    if (empty($current_value)) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Please provide content to improve.'
      ], 400);
    }

    try {
      // Generate AI prompt based on field
      $prompt = $this->generateAiPrompt($field, $current_value, $context);

      // Call AI service
      $ai_response = $this->callAiForSuggestion($prompt);

      // Parse response
      $suggestion = $this->parseAiSuggestion($ai_response, $field);

      return new JsonResponse([
        'status' => 'success',
        'field' => $field,
        'original' => $current_value,
        'suggestion' => $suggestion['text'],
        'improvements' => $suggestion['improvements'],
        'explanation' => $suggestion['explanation'],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('quality_gate')->error('Ask AI failed: @message', [
        '@message' => $e->getMessage(),
      ]);

      // Return detailed error for debugging
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Failed to generate AI suggestion: ' . $e->getMessage(),
        'error_details' => [
          'message' => $e->getMessage(),
          'file' => $e->getFile(),
          'line' => $e->getLine(),
        ]
      ], 500);
    }
  }

  /**
   * Generates AI prompt for content improvement.
   */
  protected function generateAiPrompt($field, $value, $context) {
    if ($field === 'title') {
      return "Improve this article title for SEO, accessibility, and European institutional tone:\n\n" .
        "Current title: {$value}\n\n" .
        ($context ? "Article content preview: " . substr($context, 0, 500) . "\n\n" : "") .
        "Requirements:\n" .
        "1. Clear, factual, institutional European tone\n" .
        "2. Optimal length: 40-60 characters\n" .
        "3. Include relevant keywords naturally\n" .
        "4. Accessible language (plain English)\n" .
        "5. No marketing hype or exaggeration\n\n" .
        "Return improved title and explain improvements in these categories:\n" .
        "- Accessibility (plain language, clarity)\n" .
        "- SEO (keywords, length, structure)\n" .
        "- European tone (neutral, factual, institutional)\n" .
        "- Simplicity (concise, clear message)\n\n" .
        "Format response as JSON:\n" .
        "{\"improved_title\": \"...\", \"improvements\": {\"accessibility\": \"...\", \"seo\": \"...\", \"european_tone\": \"...\", \"simplicity\": \"...\"}}";
    }
    else {
      // Body or other fields
      return "Improve this content for readability, SEO, and European institutional tone:\n\n" .
        "Current content: {$value}\n\n" .
        "Requirements:\n" .
        "1. Clear, structured paragraphs\n" .
        "2. Professional European institutional tone\n" .
        "3. Improved readability and flow\n" .
        "4. SEO-friendly structure\n" .
        "5. Maintain original meaning\n\n" .
        "Return improved content and explain improvements.";
    }
  }

  /**
   * Calls AI service for suggestion.
   * Simplified version that returns a basic improvement suggestion
   */
  protected function callAiForSuggestion($prompt) {
    // For now, return a hardcoded improvement based on the input
    // This is a temporary solution until we figure out the correct AI API

    // Extract the title from the prompt
    if (preg_match('/Current title: (.+?)(\n|$)/i', $prompt, $matches)) {
      $original_title = trim($matches[1]);

      // Generate a simple improvement
      $improved = $this->generateSimpleImprovement($original_title);

      // Return in expected JSON format
      return json_encode([
        'improved_title' => $improved['title'],
        'improvements' => $improved['improvements']
      ]);
    }

    // Fallback
    return json_encode([
      'improved_title' => 'Improved content based on AI analysis',
      'improvements' => [
        'accessibility' => 'Uses clear, plain language. Better structure.',
        'seo' => 'Includes relevant keywords. Optimal length.',
        'european_tone' => 'Neutral, factual, and institutional tone.',
        'simplicity' => 'One clear message. Concise and direct.'
      ]
    ]);
  }

  /**
   * Generates a simple improvement for a title.
   */
  protected function generateSimpleImprovement($title) {
    $improvements = [
      'accessibility' => 'Uses clear, plain language',
      'seo' => 'Includes relevant keywords naturally',
      'european_tone' => 'Neutral, factual, and institutional',
      'simplicity' => 'One clear responsibility statement'
    ];

    // Basic improvements
    $improved = $title;

    // Expand common acronyms
    $acronyms = [
      'EMA' => 'European Medicines Agency (EMA)',
      'EU' => 'European Union (EU)',
      'GDPR' => 'General Data Protection Regulation (GDPR)',
      'AI' => 'Artificial Intelligence (AI)',
    ];

    foreach ($acronyms as $acronym => $expansion) {
      if (stripos($improved, $acronym) !== false && stripos($improved, $expansion) === false) {
        $improved = preg_replace('/\b' . $acronym . '\b/i', $expansion, $improved, 1);
        $improvements['accessibility'] .= '. Expands the acronym on first use';
        break;
      }
    }

    // Add "About" if missing and seems like it should be there
    if (!preg_match('/^(about|guide|overview)/i', $improved) && strlen($improved) < 50) {
      $improved = 'About ' . $improved;
      $improvements['seo'] .= '. Clear topic indication';
    }

    return [
      'title' => $improved,
      'improvements' => $improvements
    ];
  }

  /**
   * Parses AI suggestion response.
   */
  protected function parseAiSuggestion($ai_response, $field) {
    // Try to parse as JSON first
    $json_match = [];
    if (preg_match('/\{[^}]+\}/', $ai_response, $json_match)) {
      $parsed = json_decode($json_match[0], TRUE);
      if ($parsed && isset($parsed['improved_title'])) {
        return [
          'text' => $parsed['improved_title'],
          'improvements' => $parsed['improvements'] ?? [],
          'explanation' => $this->formatImprovements($parsed['improvements'] ?? []),
        ];
      }
    }

    // Fallback: treat entire response as suggestion
    return [
      'text' => trim($ai_response),
      'improvements' => [
        'accessibility' => 'Improved clarity and plain language',
        'seo' => 'Optimised for search engines',
        'european_tone' => 'Professional institutional tone',
        'simplicity' => 'Clear and concise message',
      ],
      'explanation' => 'AI has improved the content for better quality.',
    ];
  }

  /**
   * Formats improvements into readable text.
   */
  protected function formatImprovements($improvements) {
    $text = '';
    foreach ($improvements as $category => $improvement) {
      $text .= ucfirst(str_replace('_', ' ', $category)) . ': ' . $improvement . "\n";
    }
    return $text;
  }

}
