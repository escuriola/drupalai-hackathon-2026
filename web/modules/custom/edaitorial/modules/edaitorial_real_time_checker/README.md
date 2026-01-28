# edAItorial Quality Gate

**AI-powered workflow quality gate that requires 80% content score before publishing.**

## Overview

This module adds an **AI Quality Gate** to your content moderation workflow. Before content can be published, it must pass an AI-powered quality analysis with a minimum score of 80% (configurable).

### Key Features

- **Blocks publishing** if AI content score < 80%
- **Visual sidebar** showing AI quality breakdown
- **Category percentages**: SEO, Accessibility, Typos, Links, Content
- **Issue tracking** by AI-determined severity
- **Real-time AI validation** when attempting to publish
- **Configurable AI threshold** (default: 80%)

## How It Works

### 1. Editor Creates/Edits Content

Editor works on an article normally in Drupal.

### 2. AI Quality Gate Panel

A panel appears showing AI-powered analysis:

```
╔══════════════════════════════╗
║     AI QUALITY GATE          ║
║  Overall Score: 75%    ✗     ║
║  ━━━━━━━━━━━━━━━━━━━━━━━    ║
║  [███████████████░░░░░░░]    ║
║        ↑ 80% minimum         ║
║                              ║
║  AI Category Breakdown:      ║
║  ━━━━━━━━━━━━━━━━━━━━━━━    ║
║  SEO           72%  ▂▂▂▂▂▂▂  ║
║  Accessibility 85%  ▂▂▂▂▂▂▂▂ ║
║  Typos         90%  ▂▂▂▂▂▂▂▂▂║
║  Links         80%  ▂▂▂▂▂▂▂▂ ║
║  Content       68%  ▂▂▂▂▂▂   ║
║                              ║
║  AI Issues (8):              ║
║  🔴 2 High  🟡 3 Medium      ║
║  🔵 3 Low                    ║
║                              ║
║  [ AI Analyze Now ]          ║
║                              ║
║  ⚠️ Fix AI suggestions to    ║
║     reach 80% before publish ║
╚══════════════════════════════╝
```

### 3. Attempt to Publish

When editor tries to change moderation state to "Published":

**If AI score >= 80%**: Content is published

**If AI score < 80%**: Error message shown:
```
AI content quality score is 75%. Minimum 80% required to publish.
Please improve the content based on AI suggestions or save as draft.
```

### 4. Improve with AI Guidance

Editor:
1. Reviews AI-generated issues in the Quality Gate panel
2. Fixes problems based on AI recommendations
3. Clicks "AI Analyze Now" to re-check with AI
4. AI score increases to 80%+
5. Successfully publishes

## AI Setup & Installation

```bash
# Enable the module
drush en edaitorial_real_time_checker -y
drush cr
```

## AI Configuration

Visit: `/admin/config/content/edaitorial/real-time-checker`

### AI Analysis Settings

- **Enable AI quality gate**: Master on/off switch
- **Minimum AI score to publish (%)**: Default 80%, range 0-100
- **Block publishing below AI threshold**: Enforce the AI quality gate

### Content Types

Select which content types require AI quality gate checking:
- Article
- Page
- Landing Page (optional)

### AI-Enabled Checks

Choose which AI analyses to perform:
- AI SEO Analysis
- AI Accessibility (WCAG)
- AI Spelling & Typos
- AI Broken Links Detection
- AI Content Suggestions

## AI Score Calculation

### Overall AI Score (0-100%)

Based on AI-detected issues, with deductions:
- **Critical**: -25 points
- **High**: -15 points
- **Medium**: -10 points
- **Low**: -5 points

Starting from 100%, AI deductions are applied per issue.

### AI Category Scores

Each category is scored by AI independently:

- **SEO**: AI analyzes title length, meta descriptions, keywords, headings
- **Accessibility**: AI checks alt text, ARIA labels, color contrast, semantic HTML
- **Typos**: AI detects spelling errors, repeated words, grammar
- **Links**: AI identifies broken links, empty hrefs, poor anchor text
- **Content**: AI evaluates structure, readability, length, formatting

### Color Coding

- **Green** (75-100%): Good quality
- **Orange** (50-74%): Fair quality, needs improvement
- **Red** (0-49%): Poor quality, requires attention

## AI Publishing Workflow

```
Draft → Review → [AI Quality Gate] → Published
                      ↓
                  < 80% = AI BLOCKED
                  ≥ 80% = AI APPROVED
```

### Content Editor AI Experience

1. **Create article**: `/node/add/article`
2. **Write content**: Title, body, etc.
3. **Check AI quality**: Click "AI Analyze Now" in sidebar
4. **Review AI results**: See AI score and suggestions
5. **Improve content**: Fix AI-flagged problems
6. **Re-analyze with AI**: Score increases
7. **Attempt publish**: Change to "Published" state
8. **Success**: AI score ≥ 80%, content published

## AI Integration Details

### AI Validation Hook

The module uses `hook_form_node_form_alter()` to add AI validation:

```php
function edaitorial_real_time_checker_node_form_validate(&$form, FormStateInterface $form_state) {
  // Check if trying to publish
  $moderation_state = $form_state->getValue('moderation_state');
  
  if ($moderation_state[0]['value'] === 'published') {
    // AI analyze content
    $ai_analysis = $ai_analyzer->analyzeContent($title, $body, $type);
    
    // Block if AI score < minimum
    if ($ai_analysis['score'] < $min_score) {
      $form_state->setErrorByName('moderation_state', t('AI score too low'));
    }
  }
}
```

### AI AJAX Endpoint

Manual AI analysis triggered by "AI Analyze Now" button:

```
POST /edaitorial/real-time/ai-analyze
```

AI Response:
```json
{
  "ai_score": 75,
  "ai_score_class": "fair",
  "ai_category_scores": {
    "seo": 72,
    "accessibility": 85,
    "typos": 90,
    "links": 80,
    "content": 68
  },
  "ai_issues": [...],
  "ai_suggestions": [...]
}
```

### Integration with edaitorial AI

Uses the parent module's AI capabilities:
- `EdaitorialCheckerManager`: Batch AI analysis
- AI Checker plugins: SEO, Typos, Broken Links, etc.
- AI Scoring system: Consistent across modules

## AI Customization

### Change Minimum AI Score

Via settings UI or in `settings.php`:

```php
$config['edaitorial_real_time_checker.settings']['min_ai_score'] = 90;
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

### Disable AI Gate for Specific Roles

In your module:

```php
function mymodule_form_node_form_alter(&$form, FormStateInterface $form_state) {
  $user = \Drupal::currentUser();
  
  if ($user->hasPermission('bypass ai quality gate')) {
    // Remove AI quality gate for admins
    unset($form['edaitorial_ai_quality_gate']);
    unset($form['#validate']['edaitorial_real_time_checker_node_form_validate']);
  }
}
```

## AI Troubleshooting

### AI Panel not appearing

```bash
drush cr
drush config:get edaitorial_real_time_checker.settings
```

Verify:
- Module is enabled
- Content type is enabled in AI settings
- User has permission to view edaitorial AI
- AI provider is configured

### "AI Analyze Now" not working

Check:
1. AI provider is configured in edaitorial module
2. amazee.io API key is valid
3. Mistral models are selected
4. Browser console for JavaScript errors
5. Drupal logs: `drush watchdog:show --filter=edaitorial`

### AI Publishing still blocked at 80%+

This can happen if:
1. Content changed since last AI analysis
2. AI validation uses fresh analysis (not cached)
3. AI score dropped below threshold

**Solution**: Click "AI Analyze Now" before attempting to publish.

## AI Performance

### AI Analysis Speed

- **Single node**: ~2-5 seconds (depends on AI provider)
- **Batch**: Depends on amazee.io response time
- **Caching**: Not implemented (always fresh AI analysis)

### AI Optimization Recommendations

1. **Optimize AI prompts**: Shorter prompts = faster AI response
2. **Selective AI checks**: Only enable needed AI analyses
3. **Content type targeting**: Don't enable AI for all types
4. **AI provider**: amazee.io with Mistral models recommended

## AI Dependencies

- Drupal 10 or 11
- `drupal:workflows`
- `drupal:content_moderation`
- `edaitorial:edaitorial` (parent AI module)
- `drupal:ai` (^1.2)
- **amazee.io AI provider** (required)
- **Mistral models** (configured)

## Support

For issues and questions:
- GitHub: [https://github.com/Maxfire/edAItorial](https://github.com/Maxfire/edAItorial)
- Drupal.org: edaitorial project page

## License

GPL-2.0-or-later

---

**Part of the edAItorial suite for AI-powered content management.**
