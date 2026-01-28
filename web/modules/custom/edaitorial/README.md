# edAItorial

**AI-powered editorial assistant** with SEO and Accessibility insights for content analysis and optimization.

## 🎯 What is edAItorial?

**edAItorial** combines "editorial" with "AI" to create an intelligent content assistant for Drupal. It provides real-time analysis of your content's SEO performance, accessibility compliance (WCAG), and offers AI-powered suggestions to help editors create better content.

## Features

### 🎯 Dashboard Overview
- **Overall Health Score**: Visual circular gauge showing site health (SEO + Accessibility combined)
- **Key Metrics**: Pages crawled, SEO issues, accessibility issues, and average load time
- **Real-time Statistics**: Track improvements with percentage changes
- **SEO Health Checklist**: Monitor technical SEO elements
  - Meta titles and descriptions
  - Canonical URLs
  - XML Sitemap
  - Robots.txt
  - Structured data (Schema.org)
  - Open Graph tags
  - Mobile friendliness

### ♿ WCAG Compliance Tracking
- **Level A Compliance**: Minimum accessibility standards
- **Level AA Compliance**: Enhanced accessibility (recommended)
- **Four Principles**: Perceivable, Operable, Understandable, Robust
- Visual progress bars for each principle
- Detailed issue counts and recommendations

### 📝 Content Analysis (Pre-Publish)
- Analyze content before publishing
- SEO optimization suggestions
- Accessibility checks
- Readability analysis
- AI-powered improvement recommendations

### 🔧 Configuration
- Enable/disable pre-publish content checks
- Configure title length requirements
- Set target WCAG compliance level
- Toggle AI-powered suggestions

## Installation

1. Enable the module:
   ```bash
   drush en edaitorial -y
   ```

2. Clear cache:
   ```bash
   drush cr
   ```

3. Configure permissions at `/admin/people/permissions`:
   - **View edAItorial Dashboard**: For content editors and above
   - **Administer edAItorial**: For site administrators

## Usage

### Accessing the Dashboard

Navigate to: **Configuration → Content authoring → edAItorial**  
Path: `/admin/config/content/edaitorial`

### Running an Audit

Click the "Run Audit" button in the dashboard header to analyze all published content.

### Analyzing Content Before Publishing

1. Create or edit any content
2. Expand the "edAItorial" section in the sidebar
3. Click "Analyze Content" to get instant feedback
4. Review SEO, accessibility, and readability suggestions
5. Make improvements before publishing

### Configuration

Navigate to: **Configuration → Content authoring → edAItorial → Settings**  
Path: `/admin/config/content/edaitorial/settings`

Configure:
- Pre-publish content checks
- Title length requirements
- Target WCAG level
- AI suggestions

## Extending the Module

This module is designed to be extensible. Here are some ways to extend it:

### Adding New Analyzers

Create a new service in `src/Service/` implementing your custom analysis logic:

```php
namespace Drupal\edaitorial\Service;

class CustomAnalyzer {
  // Your analyzer logic
}
```

Register it in `edaitorial.services.yml`:

```yaml
services:
  edaitorial.custom_analyzer:
    class: Drupal\edaitorial\Service\CustomAnalyzer
    arguments: ['@entity_type.manager']
```

### Adding New Dashboard Sections

1. Create a new route in `edaitorial.routing.yml`
2. Add a controller method in `src/Controller/DashboardController.php`
3. Create a Twig template in `templates/`
4. Add styles in `css/dashboard.css`

### Integrating with AI Services

The module is ready for AI integration. To connect with amazee.io or other AI providers:

1. Add AI module dependencies to `edaitorial.info.yml`
2. Inject AI services in your analyzer classes
3. Use AI predictions in the `ContentAnalyzer::getAiSuggestions()` method

Example:
```php
protected function getAiSuggestions(NodeInterface $node) {
  $ai_service = \Drupal::service('ai.provider');
  $content = $node->get('body')->value;
  
  $suggestions = $ai_service->analyze($content, [
    'task' => 'content_optimization',
    'focus' => ['seo', 'readability', 'engagement']
  ]);
  
  return $suggestions;
}
```

## Requirements

- Drupal 10 or 11
- PHP 8.1+
- Node module (core)
- User module (core)
- System module (core)

## Recommended Modules

- **Simple XML Sitemap**: For XML sitemap generation and tracking
- **Metatag**: For enhanced meta tag management
- **Schema.org Metatag**: For structured data implementation
- **AI**: For enhanced AI-powered suggestions (amazee.io integration)

## Architecture

```
edaitorial/
├── src/
│   ├── Controller/
│   │   └── DashboardController.php    # Main dashboard controller
│   ├── Form/
│   │   └── SettingsForm.php           # Configuration form
│   └── Service/
│       ├── AccessibilityAnalyzer.php  # WCAG compliance analysis
│       ├── ContentAnalyzer.php        # Pre-publish content analysis
│       ├── MetricsCollector.php       # Dashboard metrics collection
│       └── SeoAnalyzer.php            # SEO checks and scoring
├── templates/
│   ├── edaitorial-dashboard.html.twig
│   ├── edaitorial-seo-overview.html.twig
│   ├── edaitorial-accessibility.html.twig
│   └── edaitorial-content-audit.html.twig
├── css/
│   └── dashboard.css                  # Dashboard styles
├── js/
│   └── dashboard.js                   # Dashboard interactions
└── edaitorial.module                  # Hooks and theme implementations
```

## Future Enhancements

- [ ] Real-time AI-powered content suggestions
- [ ] Integration with Google Search Console
- [ ] Performance monitoring with Core Web Vitals
- [ ] Automated SEO reports via email
- [ ] Content optimization score trends over time
- [ ] Competitive analysis features
- [ ] Multi-language SEO support
- [ ] Advanced accessibility testing with automated tools
- [ ] Integration with external SEO tools (Ahrefs, SEMrush, etc.)

## Support

For issues, feature requests, or contributions, please contact the development team or open an issue in the project repository.

## Credits

Developed for the DrupalAI Hackathon 2026.

## License

GPL-2.0+
