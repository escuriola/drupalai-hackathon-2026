<?php

namespace Drupal\edaitorial\EventSubscriber;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\edaitorial\Service\ContentAnalyzer;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Form\FormAlterEvent;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for form alterations.
 */
class FormEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Constructs a FormEventSubscriber object.
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly ContentAnalyzer $contentAnalyzer,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::FORM_ALTER => 'onFormAlter',
    ];
  }

  /**
   * Responds to form alter events.
   */
  public function onFormAlter(FormAlterEvent $event): void {
    $form = &$event->getForm();
    $form_state = $event->getFormState();
    $form_id = $event->getFormId();

    if (!str_starts_with($form_id, 'node_') || !str_ends_with($form_id, '_form')) {
      return;
    }

    $config = $this->configFactory->get('edaitorial.settings');
    if (!$config->get('enable_pre_publish_check')) {
      return;
    }

    $form['edaitorial'] = [
      '#type' => 'details',
      '#title' => $this->t('edAItorial Analysis'),
      '#description' => $this->t('Analyze this content for SEO and accessibility issues.'),
      '#group' => 'advanced',
      '#weight' => 100,
    ];

    $form['edaitorial']['analyze_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Analyze Content'),
      '#ajax' => [
        'callback' => [$this, 'analyzeContent'],
        'wrapper' => 'edaitorial-results',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Analyzing content...'),
        ],
      ],
    ];

    $form['edaitorial']['results'] = [
      '#type' => 'markup',
      '#markup' => '<div id="edaitorial-results"></div>',
    ];
  }

  /**
   * AJAX callback to analyze content.
   */
  public function analyzeContent(array &$form, FormStateInterface $form_state): AjaxResponse {
    $node = $form_state->getFormObject()->getEntity();
    $response = new AjaxResponse();

    if (!($node instanceof NodeInterface)) {
      return $response;
    }

    try {
      $results = $this->contentAnalyzer->analyzeBeforePublish($node);
      $output = $this->formatAnalysisResults($results);
      $response->addCommand(new HtmlCommand('#edaitorial-results', $output));
    }
    catch (\Exception $e) {
      $this->logger->error('Analysis failed: @message', ['@message' => $e->getMessage()]);
      $error_output = '<div class="messages messages--error">' .
        $this->t('Analysis failed. Please try again or check the logs.') .
        '</div>';
      $response->addCommand(new HtmlCommand('#edaitorial-results', $error_output));
    }

    return $response;
  }

  /**
   * Format analysis results.
   */
  protected function formatAnalysisResults(array $results): string {
    $score_class = $this->getScoreClass($results['overall_score']);

    $output = '<div class="edaitorial-results">';
    $output .= '<h3>' . $this->t('Analysis Results') . '</h3>';
    $output .= '<div class="score-display score-' . $score_class . '">';
    $output .= $this->t('Overall Score: <strong>@score/100</strong>', ['@score' => $results['overall_score']]);
    $output .= '</div>';

    // SEO Issues
    if (!empty($results['seo']['issues'])) {
      $output .= '<div class="issue-section">';
      $output .= '<h4>' . $this->t('SEO Issues (@count)', ['@count' => count($results['seo']['issues'])]) . '</h4>';
      $output .= '<ul class="issue-list">';
      foreach ($results['seo']['issues'] as $issue) {
        $output .= '<li class="issue-item">' . $issue . '</li>';
      }
      $output .= '</ul></div>';
    }

    // Accessibility Issues
    if (!empty($results['accessibility']['issues'])) {
      $output .= '<div class="issue-section">';
      $output .= '<h4>' . $this->t('Accessibility Issues (@count)', ['@count' => count($results['accessibility']['issues'])]) . '</h4>';
      $output .= '<ul class="issue-list">';
      foreach ($results['accessibility']['issues'] as $issue) {
        $output .= '<li class="issue-item">' . $issue . '</li>';
      }
      $output .= '</ul></div>';
    }

    // AI Suggestions
    if (!empty($results['ai_suggestions'])) {
      $output .= '<div class="suggestions-section">';
      $output .= '<h4>' . $this->t('AI Suggestions') . '</h4>';
      $output .= '<ul class="suggestions-list">';
      foreach ($results['ai_suggestions'] as $suggestion) {
        $output .= '<li class="suggestion-item">' . $suggestion . '</li>';
      }
      $output .= '</ul></div>';
    }

    // No issues found
    if (empty($results['seo']['issues']) && empty($results['accessibility']['issues'])) {
      $output .= '<div class="no-issues">';
      $output .= '<p>' . $this->t('✓ No critical issues found. Your content looks good!') . '</p>';
      $output .= '</div>';
    }

    $output .= '</div>';
    return $output;
  }

  /**
   * Get CSS class based on score.
   */
  protected function getScoreClass(int $score): string {
    return match (true) {
      $score >= 90 => 'excellent',
      $score >= 75 => 'good',
      $score >= 50 => 'fair',
      $score >= 25 => 'poor',
      default => 'critical',
    };
  }

}