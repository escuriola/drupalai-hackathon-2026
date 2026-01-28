# edAItorial Quality Gate

**Workflow quality gate that requires 80% content score before publishing.**

## Overview

This module adds a **Quality Gate** to your content moderation workflow. Before content can be published, it must pass an AI-powered quality analysis with a minimum score of 80% (configurable).

### Key Features

- 🚫 **Blocks publishing** if content score < 80%
- 📊 **Visual sidebar** showing quality breakdown (LEFT side)
- 📈 **Category percentages**: SEO, Accessibility, Typos, Links, Content
- ⚠️ **Issue tracking** by severity (Critical, High, Medium, Low)
- ✅ **Real-time validation** when attempting to publish
- 🎯 **Configurable threshold** (default: 80%)

## How It Works

### 1. Editor Creates/Edits Content

Editor works on an article normally in Drupal.

### 2. Quality Gate Panel (LEFT Sidebar)

A panel appears on the **LEFT side** showing:

```
╔══════════════════════════════╗
║     QUALITY GATE             ║
║  Overall Score: 75%    ✗     ║
║  ━━━━━━━━━━━━━━━━━━━━━━━    ║
║  [███████████████░░░░░░░]    ║
║        ↑ 80% minimum         ║
║                              ║
║  Category Breakdown:         ║
║  ━━━━━━━━━━━━━━━━━━━━━━━    ║
║  SEO           72%  ▂▂▂▂▂▂▂  ║
║  Accessibility 85%  ▂▂▂▂▂▂▂▂ ║
║  Typos         90%  ▂▂▂▂▂▂▂▂▂║
║  Links         80%  ▂▂▂▂▂▂▂▂ ║
║  Content       68%  ▂▂▂▂▂▂   ║
║                              ║
║  Issues (8):                 ║
║  🔴 2 High  🟡 3 Medium      ║
║  🔵 3 Low                    ║
║                              ║
║  [ Analyze Now ]             ║
║                              ║
║  ⚠️ Fix issues to reach 80% ║
║     before publishing        ║
╚══════════════════════════════╝
```

### 3. Attempt to Publish

When editor tries to change moderation state to "Published":

**If score >= 80%**: ✅ Content is published

**If score < 80%**: ❌ Error message shown:
```
Content quality score is 75%. Minimum 80% required to publish.
Please improve the content or save as draft.
```

### 4. Improve and Retry

Editor:
1. Reviews issues in the Quality Gate panel
2. Fixes problems (SEO, accessibility, typos, etc.)
3. Clicks "Analyze Now" to re-check
4. Score increases to 80%+
5. Successfully publishes ✅

## Installation

```bash
# Enable the module
drush en edaitorial_real_time_checker -y
drush cr
```

## Configuration

Visit: `/admin/config/content/edaitorial/real-time-checker`

### General Settings

- **Enable quality gate**: Master on/off switch
- **Minimum score to publish (%)**: Default 80%, range 0-100
- **Block publishing below threshold**: Enforce the quality gate

### Content Types

Select which content types require quality gate checking:
- ☑ Article
- ☑ Page
- ☐ Landing Page (optional)

### Enabled Checks

Choose which analyses to perform:
- ☑ SEO Analysis
- ☑ Accessibility (WCAG)
- ☑ Spelling & Typos
- ☑ Broken Links
- ☑ Content Suggestions

### Display Settings

- **Show category breakdown**: Display percentage per category

## Score Calculation

### Overall Score (0-100%)

Based on issues found, with deductions:
- **Critical**: -25 points
- **High**: -15 points
- **Medium**: -10 points
- **Low**: -5 points

Starting from 100%, deductions are applied per issue.

### Category Scores

Each category (SEO, Accessibility, etc.) is scored independently:

- **SEO**: Title length, meta descriptions, keywords, headings
- **Accessibility**: Alt text, ARIA labels, color contrast, semantic HTML
- **Typos**: Spelling errors, repeated words, grammar
- **Links**: Broken links, empty hrefs, poor anchor text
- **Content**: Structure, readability, length, formatting

### Color Coding

- **Green** (75-100%): Good quality
- **Orange** (50-74%): Fair quality, needs improvement
- **Red** (0-49%): Poor quality, requires attention

## Use Cases

### Publishing Workflow

```
Draft → Review → [Quality Gate] → Published
                      ↓
                  < 80% = BLOCKED
                  ≥ 80% = ALLOWED
```

### Content Editor Experience

1. **Create article**: `/node/add/article`
2. **Write content**: Title, body, etc.
3. **Check quality**: Click "Analyze Now" in sidebar
4. **Review results**: See score and issues
5. **Improve content**: Fix flagged problems
6. **Re-analyze**: Score increases
7. **Attempt publish**: Change to "Published" state
8. **Success**: Score ≥ 80%, content published ✅

### Content Manager Experience

1. **Review pending content**: Check items in moderation queue
2. **Quality metrics**: See which content is ready (80%+)
3. **Filter by score**: Prioritize high-quality content
4. **Monitor standards**: Ensure consistent quality across site

## Technical Details

### Validation Hook

The module uses `hook_form_node_form_alter()` to add validation:

```php
function edaitorial_real_time_checker_node_form_validate(&$form, FormStateInterface $form_state) {
  // Check if trying to publish
  $moderation_state = $form_state->getValue('moderation_state');
  
  if ($moderation_state[0]['value'] === 'published') {
    // Analyze content
    $analysis = $analyzer->analyzeContent($title, $body, $type);
    
    // Block if score < minimum
    if ($analysis['score'] < $min_score) {
      $form_state->setErrorByName('moderation_state', t('Score too low'));
    }
  }
}
```

### AJAX Endpoint

Manual analysis triggered by "Analyze Now" button:

```
POST /edaitorial/real-time/analyze
```

Response:
```json
{
  "score": 75,
  "score_class": "fair",
  "category_scores": {
    "seo": 72,
    "accessibility": 85,
    "typos": 90,
    "links": 80,
    "content": 68
  },
  "issues": [...],
  "suggestions": [...]
}
```

### Integration with edaitorial

Uses the parent module's:
- `EdaitorialCheckerManager`: Batch AI analysis
- Checker plugins: SEO, Typos, Broken Links, etc.
- Scoring system: Consistent across modules

## Customization

### Change Minimum Score

Via settings UI or in `settings.php`:

```php
$config['edaitorial_real_time_checker.settings']['min_score'] = 90;
```

### Custom Styling

Override CSS in your theme:

```css
/* Move panel to right side */
.edaitorial-quality-gate {
  left: auto;
  right: 0;
}

/* Change colors */
.gate-header {
  background: linear-gradient(135deg, #000000 0%, #333333 100%);
}
```

### Disable for Specific Roles

In your module:

```php
function mymodule_form_node_form_alter(&$form, FormStateInterface $form_state) {
  $user = \Drupal::currentUser();
  
  if ($user->hasPermission('bypass quality gate')) {
    // Remove quality gate for admins
    unset($form['edaitorial_quality_gate']);
    unset($form['#validate']['edaitorial_real_time_checker_node_form_validate']);
  }
}
```

## Troubleshooting

### Panel not appearing

```bash
drush cr
drush config:get edaitorial_real_time_checker.settings
```

Verify:
- Module is enabled
- Content type is enabled in settings
- User has permission to view edaitorial

### "Analyze Now" not working

Check:
1. AI provider is configured in edaitorial module
2. API key is valid
3. Browser console for JavaScript errors
4. Drupal logs: `drush watchdog:show --filter=edaitorial`

### Publishing still blocked at 80%+

This can happen if:
1. Content changed since last analysis
2. Validation uses fresh analysis (not cached)
3. Score dropped below threshold

**Solution**: Click "Analyze Now" before attempting to publish.

### Categories showing 0%

This means no issues were found in that category (good!).
Or the category isn't being analyzed (check enabled checks).

## Performance

### Analysis Speed

- **Single node**: ~2-5 seconds
- **Batch**: Depends on AI provider
- **Caching**: Not implemented (always fresh)

### Recommendations

1. **Optimize prompts**: Shorter prompts = faster response
2. **Selective checks**: Only enable needed checks
3. **Content type targeting**: Don't enable for all types
4. **AI provider**: Choose fast provider (e.g., Mistral)

## Dependencies

- Drupal 10 or 11
- `drupal:workflows`
- `drupal:content_moderation`
- `edaitorial:edaitorial` (parent module)
- `drupal:ai` (^1.2)
- Configured AI provider

## Support

For issues and questions:
- GitHub: [https://github.com/Maxfire/edAItorial](https://github.com/Maxfire/edAItorial)
- Drupal.org: edaitorial project page

## License

GPL-2.0-or-later

---

**Part of the edAItorial suite for AI-powered content management.**
