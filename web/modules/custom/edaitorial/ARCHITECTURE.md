# 🏗️ edAItorial - Module Architecture

## 📋 Table of Contents

1. [Overview](#overview)
2. [Folder Structure](#folder-structure)
3. [Main Components](#main-components)
4. [Data Flow](#data-flow)
5. [Plugin System](#plugin-system)
6. [Drupal AI Integration](#drupal-ai-integration)
7. [Routing and Controllers](#routing-and-controllers)
8. [Services](#services)
9. [Templates and Theming](#templates-and-theming)
10. [Configuration](#configuration)

---

## 📐 Overview

**edAItorial** is a Drupal module that provides AI-powered content analysis for SEO, accessibility, content quality, and broken links.

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     EDAITORIAL MODULE                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │  Dashboard   │  │Content Audit │  │   Settings   │    │
│  │  (Fast View) │  │(AI Analysis) │  │(Configuration)│    │
│  └──────────────┘  └──────────────┘  └──────────────┘    │
│         │                  │                  │           │
│         └──────────────────┼──────────────────┘           │
│                            │                              │
│              ┌─────────────▼─────────────┐               │
│              │   Metrics Collector       │               │
│              │   (Orchestrator)          │               │
│              └─────────────┬─────────────┘               │
│                            │                              │
│              ┌─────────────▼─────────────┐               │
│              │ Plugin Manager (Checkers) │               │
│              └─────────────┬─────────────┘               │
│                            │                              │
│         ┌──────────────────┼──────────────────┐          │
│         │                  │                  │          │
│    ┌────▼────┐      ┌─────▼─────┐      ┌────▼────┐     │
│    │   SEO   │      │   Typos   │      │ Broken  │     │
│    │ Checker │      │  Checker  │      │  Links  │     │
│    └────┬────┘      └─────┬─────┘      └────┬────┘     │
│         │                  │                  │          │
│         └──────────────────┼──────────────────┘          │
│                            │                              │
│              ┌─────────────▼─────────────┐               │
│              │      Drupal AI Module      │               │
│              │   (LLM Provider Adapter)   │               │
│              └─────────────┬─────────────┘               │
│                            │                              │
│              ┌─────────────▼─────────────┐               │
│              │   AI Providers (Mistral,  │               │
│              │   OpenAI, Claude, etc.)   │               │
│              └───────────────────────────┘               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Design Principles

1. **Lazy Loading**: AI only executes when requested
2. **Plugin Architecture**: Extensible checkers via plugins
3. **Dependency Injection**: Services injected, not instantiated
4. **Separation of Concerns**: Dashboard (fast) vs Content Audit (AI)
5. **Drupal Standards**: Follows Drupal 10 conventions

---

## 📁 Folder Structure

```
web/modules/custom/edaitorial/
│
├── config/                         # Module configuration
│   ├── install/                    # Initial config
│   │   └── edaitorial.settings.yml
│   └── schema/                     # Configuration schemas
│       └── edaitorial.schema.yml
│
├── css/                            # Styles
│   └── dashboard.css               # Main styles
│
├── js/                             # JavaScript
│   └── dashboard.js                # Dashboard scripts
│
├── src/                            # PHP code
│   ├── Controller/                 # Controllers
│   │   └── DashboardController.php # Main controller
│   │
│   ├── Form/                       # Forms
│   │   └── SettingsForm.php        # Configuration form
│   │
│   ├── Plugin/                     # Plugin system
│   │   └── EdaitorialChecker/      # Checker plugins
│   │       ├── SeoChecker.php
│   │       ├── TyposChecker.php
│   │       ├── SuggestionsChecker.php
│   │       └── BrokenLinksChecker.php
│   │
│   ├── Service/                    # Services
│   │   ├── MetricsCollector.php    # Main orchestrator
│   │   ├── SeoAnalyzer.php         # SEO analysis
│   │   └── AccessibilityAnalyzer.php # A11y analysis
│   │
│   ├── EdaitorialCheckerInterface.php # Interface for checkers
│   └── EdaitorialCheckerManager.php   # Plugin manager
│
├── templates/                      # Twig templates
│   ├── edaitorial-dashboard.html.twig
│   ├── edaitorial-content-audit.html.twig
│   └── edaitorial-content-audit-detail.html.twig
│
├── edaitorial.info.yml             # Module metadata
├── edaitorial.module               # Drupal hooks
├── edaitorial.routing.yml          # Route definitions
├── edaitorial.services.yml         # Service definitions
├── edaitorial.libraries.yml        # CSS/JS libraries
├── edaitorial.permissions.yml      # Permissions
├── edaitorial.links.menu.yml       # Menu links
└── edaitorial.links.task.yml       # Navigation tabs
```

---

## 🔧 Main Components

### 1. DashboardController

**Location**: `src/Controller/DashboardController.php`

**Responsibility**: Manages module views

```php
class DashboardController extends ControllerBase {
  
  // Main view (Fast Mode - <1s)
  public function dashboard()
  
  // Detailed SEO view
  public function seoOverview()
  
  // Accessibility view
  public function accessibility()
  
  // Content list (Fast - <1s)
  public function contentAudit()
  
  // Detailed analysis with AI (3-5s)
  public function contentAuditDetail($node)
  
  // Scoring functions
  protected function calculateScore(array $issues)
  protected function getScoreClass($score)
  protected function groupIssuesByType(array $issues)
  protected function groupIssuesBySeverity(array $issues)
}
```

**Flow Diagram**:

```
User → Route → DashboardController → MetricsCollector → Template
                                            │
                                            ├─> Fast Mode (without AI)
                                            └─> AI Mode (with checkers)
```

### 2. MetricsCollector Service

**Location**: `src/Service/MetricsCollector.php`

**Responsibility**: Main orchestrator for metrics and analysis

```php
class MetricsCollector {
  
  // FAST: Dashboard without AI (<1s)
  public function collectAllMetrics()
  
  // SLOW: Dashboard with AI (30-60s) - LEGACY
  public function collectAllMetricsWithAI()
  
  // Fast node list without AI (<1s)
  public function auditContentList()
  
  // Specific analysis with AI (3-5s)
  public function analyzeSpecificNode($node_id)
  
  // Node analysis (executes checkers)
  protected function analyzeNodeIssues($node)
  
  // Helpers
  protected function getFastSeoChecks()
  protected function getFastWcagCompliance($level)
}
```

**Dependency Diagram**:

```
MetricsCollector
    │
    ├─> SeoAnalyzer (legacy)
    ├─> AccessibilityAnalyzer (legacy)
    └─> EdaitorialCheckerManager (plugin manager)
            │
            ├─> SeoChecker (plugin)
            ├─> TyposChecker (plugin)
            ├─> SuggestionsChecker (plugin)
            └─> BrokenLinksChecker (plugin)
```

### 3. Plugin Manager

**Location**: `src/EdaitorialCheckerManager.php`

**Responsibility**: Manages checker plugins

```php
class EdaitorialCheckerManager extends DefaultPluginManager {
  
  // Constructor with plugin discovery
  public function __construct(...)
  
  // Analyzes a node with all checkers
  public function analyzeNode(NodeInterface $node)
  
  // Gets all available checkers
  public function getCheckers()
}
```

**Discovery Process**:

```
1. Scans src/Plugin/EdaitorialChecker/
2. Reads @EdaitorialChecker annotations
3. Instantiates plugins with DI
4. Caches definitions
```

### 4. Checker Plugins

**Interface**: `src/EdaitorialCheckerInterface.php`

```php
interface EdaitorialCheckerInterface {
  public function analyze(NodeInterface $node);
}
```

**Implemented Plugins**:

```
┌─────────────────────────────────────────────────────────┐
│                    CHECKER PLUGINS                       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────────┐  ┌──────────────┐                   │
│  │ SeoChecker   │  │TyposChecker  │                   │
│  │              │  │              │                   │
│  │ - Meta desc  │  │ - Spelling   │                   │
│  │ - Titles     │  │ - Grammar    │                   │
│  │ - Keywords   │  │ - Style      │                   │
│  │ - Headings   │  │ - Tone       │                   │
│  └──────────────┘  └──────────────┘                   │
│                                                         │
│  ┌──────────────┐  ┌──────────────┐                   │
│  │Suggestions   │  │ BrokenLinks  │                   │
│  │Checker       │  │ Checker      │                   │
│  │              │  │              │                   │
│  │ - Improve    │  │ - Internal   │                   │
│  │ - Expand     │  │ - External   │                   │
│  │ - Optimize   │  │ - Validation │                   │
│  └──────────────┘  └──────────────┘                   │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## 🔄 Data Flow

### Flow 1: Dashboard (Fast Mode)

```
1. User → /admin/config/content/edaitorial
                │
2. DashboardController::dashboard()
                │
3. MetricsCollector::collectAllMetrics()
                │
                ├─> getPagesCount() → DB Query (COUNT)
                ├─> getFastSeoChecks() → Static data
                ├─> getFastWcagCompliance() → Static data
                └─> getRecentActivity() → DB Query
                │
4. Template: edaitorial-dashboard.html.twig
                │
5. User sees dashboard (<1 second)
```

**Features**:
- No AI calls
- Only basic DB queries
- Time: <1 second
- Pre-calculated scores

### Flow 2: Content Audit List (Fast Mode)

```
1. User → /admin/config/content/edaitorial/content-audit
                │
2. DashboardController::contentAudit()
                │
3. MetricsCollector::auditContentList()
                │
                └─> DB Query: Load all nodes (metadata only)
                │
4. Template: edaitorial-content-audit.html.twig
                │
5. User sees table with filters (<1 second)
```

**Features**:
- No AI analysis
- Node metadata only
- JavaScript filters (client-side)
- JavaScript sorting

### Flow 3: Content Audit Detail (AI Mode)

```
1. User → Clicks on node in table
                │
2. /admin/config/content/edaitorial/content-audit/25
                │
3. DashboardController::contentAuditDetail(25)
                │
4. MetricsCollector::analyzeSpecificNode(25)
                │
5. EdaitorialCheckerManager::analyzeNode($node)
                │
                ├─> SeoChecker::analyze($node)
                │       │
                │       └─> Drupal AI → Mistral API
                │               │
                │               └─> Returns issues
                │
                ├─> TyposChecker::analyze($node)
                │       │
                │       └─> Drupal AI → Mistral API
                │               │
                │               └─> Returns issues
                │
                ├─> SuggestionsChecker::analyze($node)
                │       │
                │       └─> Drupal AI → Mistral API
                │               │
                │               └─> Returns issues
                │
                └─> BrokenLinksChecker::analyze($node)
                        │
                        └─> Drupal AI → Mistral API
                                │
                                └─> Returns issues
                │
6. Combined issues + calculated score
                │
7. Template: edaitorial-content-audit-detail.html.twig
                │
8. User sees complete analysis (3-5 seconds)
```

**Features**:
- 4 checkers executed
- 4 AI calls (Mistral)
- Issues grouped by type and severity
- Calculated score (0-100)
- Time: 3-5 seconds

---

## 🔌 Plugin System

### Plugin Architecture

```
┌──────────────────────────────────────────────────────────┐
│                   PLUGIN SYSTEM                          │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  Annotation-based Discovery                              │
│  ┌────────────────────────────────────────────────┐     │
│  │ @EdaitorialChecker(                            │     │
│  │   id = "seo_checker",                          │     │
│  │   label = @Translation("SEO Checker"),         │     │
│  │   description = @Translation("...")            │     │
│  │ )                                              │     │
│  └────────────────────────────────────────────────┘     │
│                                                          │
│  Interface Contract                                      │
│  ┌────────────────────────────────────────────────┐     │
│  │ EdaitorialCheckerInterface {                   │     │
│  │   public function analyze(NodeInterface $node);│     │
│  │ }                                              │     │
│  └────────────────────────────────────────────────┘     │
│                                                          │
│  Base Class (optional)                                   │
│  ┌────────────────────────────────────────────────┐     │
│  │ EdaitorialCheckerBase extends PluginBase       │     │
│  │   implements EdaitorialCheckerInterface {      │     │
│  │                                                │     │
│  │   protected $aiClient;                         │     │
│  │   protected $config;                           │     │
│  │                                                │     │
│  │   public function analyze(NodeInterface $node) │     │
│  │ }                                              │     │
│  └────────────────────────────────────────────────┘     │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

### Creating a New Checker

**Step 1**: Create class in `src/Plugin/EdaitorialChecker/`

```php
<?php

namespace Drupal\edaitorial\Plugin\EdaitorialChecker;

use Drupal\edaitorial\EdaitorialCheckerInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Custom checker plugin.
 *
 * @EdaitorialChecker(
 *   id = "my_custom_checker",
 *   label = @Translation("My Custom Checker"),
 *   description = @Translation("Checks custom aspects of content.")
 * )
 */
class MyCustomChecker extends PluginBase implements EdaitorialCheckerInterface {
  
  public function analyze(NodeInterface $node) {
    $issues = [];
    
    // Your analysis logic here
    // Can use Drupal AI if needed
    
    return $issues;
  }
}
```

**Step 2**: Clear cache

```bash
drush cr
```

**Step 3**: Plugin automatically available

The plugin will be discovered and executed by the `EdaitorialCheckerManager`.

---

## 🤖 Drupal AI Integration

### Integration Architecture

```
┌─────────────────────────────────────────────────────────┐
│              DRUPAL AI INTEGRATION                       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  edAItorial Module                                      │
│       │                                                 │
│       │ uses                                            │
│       ▼                                                 │
│  drupal/ai Module                                       │
│       │                                                 │
│       │ provides                                        │
│       ▼                                                 │
│  AI Providers (amazeeio, openai, etc.)                 │
│       │                                                 │
│       │ calls                                           │
│       ▼                                                 │
│  LLM APIs (Mistral, OpenAI, Claude)                    │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Configuration in Settings

```yaml
# config/install/edaitorial.settings.yml

ai_provider: 'amazeeio'          # From drupal/ai
ai_model: 'mistral-large-latest' # From drupal/ai

prompts:
  seo: |
    Analyze this content for SEO optimization...
  
  typos: |
    Check for spelling and grammar errors...
  
  suggestions: |
    Provide suggestions to improve...
  
  broken_links: |
    Identify potential broken links...
```

### Usage in Checkers

```php
// In SeoChecker.php

public function analyze(NodeInterface $node) {
  // 1. Get AI configuration
  $config = \Drupal::config('edaitorial.settings');
  $provider = $config->get('ai_provider');
  $model = $config->get('ai_model');
  $prompt = $config->get('prompts.seo');
  
  // 2. Get Drupal AI client
  $aiClient = \Drupal::service('ai.provider')->getClient($provider);
  
  // 3. Prepare content
  $content = $node->body->value;
  $title = $node->getTitle();
  
  // 4. Call AI
  $response = $aiClient->chat([
    'model' => $model,
    'messages' => [
      ['role' => 'user', 'content' => $prompt . "\n\nTitle: $title\n\nContent: $content"]
    ]
  ]);
  
  // 5. Parse response and return issues
  return $this->parseAiResponse($response);
}
```

### Dependency

```yaml
# edaitorial.info.yml

dependencies:
  - drupal:ai (^1.0)
```

---

## 🛣️ Routing and Controllers

### Route Definitions

```yaml
# edaitorial.routing.yml

edaitorial.dashboard:
  path: '/admin/config/content/edaitorial'
  defaults:
    _controller: '\Drupal\edaitorial\Controller\DashboardController::dashboard'
    _title: 'edAItorial Dashboard'
  requirements:
    _permission: 'view edaitorial'

edaitorial.content_audit:
  path: '/admin/config/content/edaitorial/content-audit'
  defaults:
    _controller: '\Drupal\edaitorial\Controller\DashboardController::contentAudit'
    _title: 'Content Audit'
  requirements:
    _permission: 'view edaitorial'

edaitorial.content_audit_detail:
  path: '/admin/config/content/edaitorial/content-audit/{node}'
  defaults:
    _controller: '\Drupal\edaitorial\Controller\DashboardController::contentAuditDetail'
    _title_callback: '\Drupal\edaitorial\Controller\DashboardController::detailTitle'
  requirements:
    _permission: 'view edaitorial'
    node: \d+

edaitorial.settings:
  path: '/admin/config/content/edaitorial/settings'
  defaults:
    _form: '\Drupal\edaitorial\Form\SettingsForm'
    _title: 'edAItorial Settings'
  requirements:
    _permission: 'administer edaitorial'
```

### URL Map

```
/admin/config/content/edaitorial
    │
    ├─ / (Dashboard - Fast)
    ├─ /seo (SEO Overview)
    ├─ /accessibility (Accessibility)
    ├─ /content-audit (List - Fast)
    │   └─ /{node} (Detail - AI)
    └─ /settings (Configuration)
```

---

## 🔨 Services

### Service Definitions

```yaml
# edaitorial.services.yml

services:
  # Plugin Manager
  plugin.manager.edaitorial_checker:
    class: Drupal\edaitorial\EdaitorialCheckerManager
    parent: default_plugin_manager
  
  # Metrics Collector (Orchestrator)
  edaitorial.metrics_collector:
    class: Drupal\edaitorial\Service\MetricsCollector
    arguments:
      - '@edaitorial.seo_analyzer'
      - '@edaitorial.accessibility_analyzer'
      - '@plugin.manager.edaitorial_checker'
  
  # SEO Analyzer (Legacy)
  edaitorial.seo_analyzer:
    class: Drupal\edaitorial\Service\SeoAnalyzer
    arguments: ['@entity_type.manager']
  
  # Accessibility Analyzer (Legacy)
  edaitorial.accessibility_analyzer:
    class: Drupal\edaitorial\Service\AccessibilityAnalyzer
    arguments: ['@entity_type.manager']
```

### Dependency Injection

```php
// In DashboardController.php

class DashboardController extends ControllerBase {
  
  protected $metricsCollector;
  
  public function __construct(MetricsCollector $metrics_collector) {
    $this->metricsCollector = $metrics_collector;
  }
  
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('edaitorial.metrics_collector')
    );
  }
}
```

---

## 🎨 Templates and Theming

### Template System

```
┌────────────────────────────────────────────────┐
│            THEMING ARCHITECTURE                 │
├────────────────────────────────────────────────┤
│                                                │
│  Hook: edaitorial_theme()                     │
│       │                                        │
│       ├─ edaitorial_dashboard                 │
│       │    └─> edaitorial-dashboard.html.twig │
│       │                                        │
│       ├─ edaitorial_content_audit             │
│       │    └─> edaitorial-content-audit...    │
│       │                                        │
│       └─ edaitorial_content_audit_detail      │
│            └─> edaitorial-content-audit-...   │
│                                                │
│  Library: edaitorial/dashboard                 │
│       │                                        │
│       ├─ css/dashboard.css                    │
│       └─ js/dashboard.js                      │
│                                                │
└────────────────────────────────────────────────┘
```

### Template Registration

```php
// edaitorial.module

function edaitorial_theme($existing, $type, $theme, $path) {
  return [
    'edaitorial_dashboard' => [
      'variables' => ['metrics' => []],
      'template' => 'edaitorial-dashboard',
    ],
    'edaitorial_content_audit' => [
      'variables' => ['audit_results' => []],
      'template' => 'edaitorial-content-audit',
    ],
    'edaitorial_content_audit_detail' => [
      'variables' => ['node' => NULL, 'audit_data' => []],
      'template' => 'edaitorial-content-audit-detail',
    ],
  ];
}
```

### Libraries

```yaml
# edaitorial.libraries.yml

dashboard:
  version: 1.0
  css:
    theme:
      css/dashboard.css: {}
  js:
    js/dashboard.js: {}
  dependencies:
    - core/drupal
    - core/jquery
```

---

## ⚙️ Configuration

### Configuration Schema

```yaml
# config/schema/edaitorial.schema.yml

edaitorial.settings:
  type: config_object
  label: 'edAItorial settings'
  mapping:
    ai_provider:
      type: string
      label: 'AI Provider'
    ai_model:
      type: string
      label: 'AI Model'
    enable_pre_publish_check:
      type: boolean
      label: 'Enable pre-publish check'
    prompts:
      type: mapping
      label: 'AI Prompts'
      mapping:
        seo:
          type: text
          label: 'SEO Prompt'
        typos:
          type: text
          label: 'Typos Prompt'
        suggestions:
          type: text
          label: 'Suggestions Prompt'
        broken_links:
          type: text
          label: 'Broken Links Prompt'
```

### Default Configuration

```yaml
# config/install/edaitorial.settings.yml

ai_provider: 'amazeeio'
ai_model: 'mistral-large-latest'
enable_pre_publish_check: false

prompts:
  seo: |
    Analyze the following content for SEO optimization...
  
  typos: |
    Check the following content for spelling and grammar...
  
  suggestions: |
    Provide suggestions to improve the following content...
  
  broken_links: |
    Identify potential broken or problematic links...
```

---

## 📊 Sequence Diagrams

### Sequence 1: AI Analysis

```
User          Controller      MetricsCollector    PluginManager    Checker    Drupal AI    Mistral
  │               │                   │                 │             │           │           │
  │─ Click Node ─>│                   │                 │             │           │           │
  │               │                   │                 │             │           │           │
  │               │─ analyzeNode() ──>│                 │             │           │           │
  │               │                   │                 │             │           │           │
  │               │                   │─ analyzeNode()─>│             │           │           │
  │               │                   │                 │             │           │           │
  │               │                   │                 │─ analyze()─>│           │           │
  │               │                   │                 │             │           │           │
  │               │                   │                 │             │─ chat()─>│           │
  │               │                   │                 │             │           │           │
  │               │                   │                 │             │           │─ API ───>│
  │               │                   │                 │             │           │           │
  │               │                   │                 │             │           │<─ JSON ──│
  │               │                   │                 │             │           │           │
  │               │                   │                 │             │<─ issues─│           │
  │               │                   │                 │             │           │           │
  │               │                   │                 │<─ issues ──│           │           │
  │               │                   │                 │             │           │           │
  │               │                   │<─ all issues ──│             │           │           │
  │               │                   │                 │             │           │           │
  │               │<─ audit data ────│                 │             │           │           │
  │               │                   │                 │             │           │           │
  │<─ HTML ──────│                   │                 │             │           │           │
  │               │                   │                 │             │           │           │
```

### Sequence 2: Fast Mode (Dashboard)

```
User          Controller      MetricsCollector    Database
  │               │                   │                │
  │─ Visit URL ──>│                   │                │
  │               │                   │                │
  │               │─ collectMetrics()>│                │
  │               │                   │                │
  │               │                   │─ COUNT query ─>│
  │               │                   │                │
  │               │                   │<─ count ──────│
  │               │                   │                │
  │               │<─ fast metrics ──│                │
  │               │                   │                │
  │<─ HTML ──────│                   │                │
  │               │                   │                │
```

---

## 🔐 Permissions and Security

### Permission Definitions

```yaml
# edaitorial.permissions.yml

view edaitorial:
  title: 'View edAItorial dashboards'
  description: 'Access to view SEO and content dashboards'
  restrict access: false

administer edaitorial:
  title: 'Administer edAItorial'
  description: 'Configure edAItorial settings and prompts'
  restrict access: true
```

### Usage in Routes

```yaml
# Requirements in routing.yml
requirements:
  _permission: 'view edaitorial'
  # or
  _permission: 'administer edaitorial'
```

---

## 🚀 Performance

### Implemented Optimizations

1. **AI Lazy Loading**
   - Dashboard: Without AI (<1s)
   - Content Audit List: Without AI (<1s)
   - Detail View: On-demand AI (3-5s)

2. **Plugin Definition Cache**
   - Plugins discovered once
   - Definitions cached in DB

3. **Optimized Queries**
   - COUNT instead of LOAD
   - Metadata only in listings
   - Full load only in detail

4. **Client-Side Processing**
   - JavaScript filters
   - Client-side sorting
   - No server round-trips

### Metrics

```
Dashboard:          <1 second
Content Audit List: <1 second
Detail Analysis:    3-5 seconds
Settings Form:      <1 second
```

---

## 📦 Dependencies

### Drupal Core

```yaml
core_version_requirement: ^10
```

### Contrib Modules

```yaml
dependencies:
  - drupal:ai (^1.0)
```

### PHP

```
PHP >= 8.1
```

---

## 🧪 Testing

### Test Structure (Suggested)

```
tests/
  ├── src/
  │   ├── Unit/
  │   │   ├── MetricsCollectorTest.php
  │   │   ├── SeoCheckerTest.php
  │   │   └── TyposCheckerTest.php
  │   │
  │   ├── Kernel/
  │   │   ├── PluginManagerTest.php
  │   │   └── ServiceIntegrationTest.php
  │   │
  │   └── Functional/
  │       ├── DashboardTest.php
  │       ├── ContentAuditTest.php
  │       └── SettingsFormTest.php
```

---

## 📚 Extensibility

### Extension Points

1. **New Checkers**
   - Implement `EdaitorialCheckerInterface`
   - Add `@EdaitorialChecker` annotation
   - Clear cache

2. **New Templates**
   - Override in theme
   - Use `hook_theme_suggestions_HOOK`

3. **New Routes**
   - Add in `edaitorial.routing.yml`
   - Create method in controller

4. **Alterations**
   - `hook_edaitorial_checkers_alter()`
   - `hook_edaitorial_issues_alter()`

---

## 🎯 Summary

### Key Components

| Component | Location | Responsibility |
|-----------|----------|----------------|
| **DashboardController** | `src/Controller/` | Views and routes |
| **MetricsCollector** | `src/Service/` | Orchestration |
| **PluginManager** | `src/` | Checker management |
| **Checkers** | `src/Plugin/` | AI analysis |
| **Templates** | `templates/` | Rendering |
| **SettingsForm** | `src/Form/` | Configuration |

### Main Flows

1. **Fast Dashboard**: DB → Template (<1s)
2. **Fast List**: DB → Template (<1s)
3. **AI Analysis**: DB → Plugins → AI → Template (3-5s)

### Technologies

- **Backend**: Drupal 10, PHP 8.1+
- **Frontend**: Twig, CSS, JavaScript
- **AI**: Drupal AI Module, Mistral API
- **Architecture**: Plugin System, Dependency Injection

---

**Document Version**: 1.0  
**Date**: 2026-01-27  
**Module**: edAItorial 1.0.0
