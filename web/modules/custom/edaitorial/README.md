# edAItorial

**AI-powered editorial assistant** for intelligent content optimization and analysis.

## What is edAItorial?

**edAItorial** combines "editorial" with "AI" to create an intelligent content assistant for Drupal. It leverages AI models through the amazee.io provider to analyze content and provide smart recommendations for optimization.

## AI-Powered Features

### Intelligent Content Analysis
- **AI Content Optimization**: Real-time suggestions powered by Mistral models
- **Smart SEO Recommendations**: AI-driven title, meta description, and keyword suggestions
- **Content Quality Assessment**: AI analysis of readability, engagement, and structure
- **Automated Improvement Suggestions**: Context-aware recommendations for better content

### AI Dashboard Insights
- **Intelligent Health Scoring**: AI-calculated content quality metrics
- **Predictive Analytics**: AI-powered insights on content performance
- **Smart Recommendations**: Personalized suggestions based on content patterns
- **Automated Issue Detection**: AI identification of content problems

### AI Configuration
- **amazee.io Integration**: Seamless connection to AI provider
- **Model Selection**: Choose appropriate Mistral models for different tasks
- **AI Suggestion Tuning**: Configure AI recommendation sensitivity
- **Smart Analysis Triggers**: AI-powered content evaluation workflows

## AI Setup & Installation

### Prerequisites
1. **amazee.io AI Provider**: Configure at `/admin/config/ai/providers/amazeeio`
2. **AI Models**: Select Mistral models at `/admin/config/ai/ai_default_settings`

### Installation
1. Enable the module:
   ```bash
   drush en edaitorial -y
   ```

2. Configure AI integration:
   ```bash
   drush cr
   ```

### AI-Powered Usage

#### Smart Content Analysis
1. Create or edit content
2. AI automatically analyzes content quality
3. Review AI-generated suggestions
4. Apply recommended optimizations

#### AI Dashboard
Navigate to: `/admin/config/content/edaitorial`
- View AI-powered content insights
- Get intelligent recommendations
- Monitor AI analysis results

## AI Integration & Extension

### AI Service Integration

The module integrates with amazee.io AI provider:

```php
protected function getAiSuggestions(NodeInterface $node) {
  $ai_service = \Drupal::service('ai.provider.amazeeio');
  $content = $node->get('body')->value;
  
  $suggestions = $ai_service->chat([
    'messages' => [[
      'role' => 'user',
      'content' => "Analyze this content for SEO and readability: {$content}"
    ]],
    'model' => 'mistral-large-latest'
  ]);
  
  return $suggestions;
}
```

### Extending AI Capabilities

1. **Custom AI Analyzers**: Create specialized AI analysis services
2. **Model Fine-tuning**: Configure different Mistral models for specific tasks
3. **AI Prompt Engineering**: Optimize prompts for better content suggestions
4. **Multi-modal Analysis**: Extend to analyze images and media with AI

## Requirements

- Drupal 10 or 11
- PHP 8.1+
- **AI module**: Core AI functionality
- **amazee.io provider**: AI model access
- **Keys module**: Secure credential storage

## AI Dependencies

- **AI module**: Essential for AI provider integration
- **amazee.io provider**: Required for Mistral model access
- **Mistral models**: Large language models for content analysis

## AI Architecture

```
edaitorial/
├── src/
│   ├── Controller/
│   │   └── DashboardController.php    # AI-powered dashboard
│   ├── Service/
│   │   ├── ContentAnalyzer.php        # AI content analysis
│   │   ├── MetricsCollector.php       # AI metrics aggregation
│   │   └── SeoAnalyzer.php            # AI SEO optimization
│   └── Plugin/
│       └── EdaitorialChecker/         # AI-powered content checkers
├── templates/
│   └── edaitorial-*.html.twig         # AI insights display
└── edaitorial.module                  # AI integration hooks
```

## AI Roadmap

- [x] **amazee.io Integration**: Mistral model connectivity
- [x] **Smart Content Analysis**: AI-powered content evaluation
- [ ] **Real-time AI Suggestions**: Live content optimization
- [ ] **Multi-language AI**: Localized content analysis
- [ ] **Advanced AI Models**: GPT-4, Claude integration
- [ ] **AI Content Generation**: Automated content creation
- [ ] **Predictive Analytics**: AI-driven performance forecasting

## Support

For issues, feature requests, or contributions, please contact the development team or open an issue in the project repository.

## Credits

Developed for the DrupalAI Hackathon 2026.

## License

GPL-2.0+
