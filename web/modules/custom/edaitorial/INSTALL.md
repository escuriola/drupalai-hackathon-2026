# 📦 edAItorial - Installation Guide

Complete installation guide for the edAItorial module with automated and manual options.

---

## 🚀 Quick Install (Automated)

### Option 1: Using the Install Script

**IMPORTANT**: The script should be run from your **Drupal root directory**.

```bash
# From Drupal root directory
cd /path/to/your/drupal
./web/modules/custom/edaitorial/install.sh
```

Or if you're in the module directory, the script will automatically navigate to Drupal root:

```bash
# From module directory (script will auto-detect)
cd web/modules/custom/edaitorial
./install.sh
```

**With suggested modules** (pathauto, metatag, redirect):
```bash
./install.sh --with-suggested
```

### Option 2: One-Line Install

**From Drupal root directory:**

```bash
# Check if drupal/ai is installed
composer show drupal/ai

# If not installed:
composer require drupal/ai

# Enable modules
drush en ai edaitorial -y && drush cr
```

**Note**: If `drupal/ai` is already installed (like in Drupal CMS), just enable the modules:
```bash
drush en ai edaitorial -y && drush cr
```

---

## 📋 Requirements

All requirements are documented in `dependencies.json`.

### System Requirements

| Component | Requirement |
|-----------|-------------|
| **PHP** | >= 8.1 (8.2 recommended) |
| **Drupal** | 10.x or 11.x |
| **Memory** | 256M minimum |
| **Execution Time** | 60s minimum |

### PHP Extensions Required

- json
- curl
- mbstring
- xml
- dom

### Drupal Dependencies

**Required:**
- `drupal/core` (^10 || ^11)
- `drupal/ai` (^1.0)
- `drupal/node` (core)
- `drupal/user` (core)
- `drupal/system` (core)

**Suggested:**
- `drupal/pathauto` (^1.11) - Better URL management
- `drupal/metatag` (^2.0) - Enhanced SEO meta tags
- `drupal/redirect` (^1.9) - Redirect management
- `drupal/xmlsitemap` (^1.5) - XML sitemap generation

---

## 📖 Manual Installation

### Step 1: Check/Install Dependencies

**Check if drupal/ai is already installed:**

```bash
composer show drupal/ai
```

If the output shows a version (e.g., 1.2.6), it's already installed. Skip to Step 2.

**If not installed, install it:**

```bash
composer require drupal/ai
```

This will install the latest compatible version.

**Optional: Install suggested modules**
```bash
composer require drupal/pathauto:^1.11 drupal/metatag:^2.0 drupal/redirect:^1.9
```

**Note for Drupal CMS users:** If you're using Drupal CMS 2.x, `drupal/ai` is already included. You can skip the installation step and proceed directly to enabling the modules.

### Step 2: Enable AI Module

Via Drush:
```bash
drush en ai -y
```

Via UI:
1. Visit `/admin/modules`
2. Find "Drupal AI" module
3. Check the box and click "Install"

### Step 3: Configure AI Provider

**This step is REQUIRED for the module to work!**

Via UI:
1. Visit `/admin/config/system/ai/providers`
2. Add a provider (Mistral/OpenAI/Claude)
3. Enter your API key
4. Save configuration

Via Drush:
```bash
drush config:set ai.settings default_provider amazeeio -y
drush config:set ai.settings providers.amazeeio.configuration.model mistral-large-latest -y
```

**Note**: You still need to add the API key manually in the UI.

### Step 4: Enable edAItorial Module

Via Drush:
```bash
drush en edaitorial -y
```

Via UI:
1. Visit `/admin/modules`
2. Find "edAItorial" module
3. Check the box and click "Install"

### Step 5: Clear Cache

```bash
drush cr
```

### Step 6: Set Permissions

1. Visit `/admin/people/permissions`
2. Grant permissions:
   - **View edAItorial** - For content editors
   - **Administer edAItorial** - For administrators

---

## 🔧 Post-Installation Configuration

### 1. Configure AI Provider API Key

**Required!** The module needs an AI provider to function.

1. Visit: `/admin/config/system/ai/providers`
2. Select your provider (amazeeio, openai, anthropic)
3. Add your API key
4. Save configuration

### 2. Customize edAItorial Settings (Optional)

1. Visit: `/admin/config/content/edaitorial/settings`
2. Customize:
   - AI prompts for each checker
   - Batch analysis prompt
   - Enable/disable features
   - Score thresholds

### 3. Test the Installation

1. **Dashboard**: Visit `/admin/config/content/edaitorial`
   - Should load in <1 second
   - Shows metrics overview

2. **Content Audit List**: Visit `/admin/config/content/edaitorial/content-audit`
   - Shows all content items
   - Interactive table with filters

3. **Content Analysis**: Click any content item
   - AI analysis should complete in 3-5 seconds
   - Shows comprehensive issue breakdown

---

## 🔑 Getting API Keys

### Mistral AI (Recommended - via amazeeio provider)

1. Visit: https://mistral.ai/
2. Create account
3. Generate API key
4. Cost: ~$0.002 per 1K input tokens

### OpenAI

1. Visit: https://platform.openai.com/signup
2. Create account
3. Generate API key
4. Cost: Varies by model

### Anthropic (Claude)

1. Visit: https://www.anthropic.com/
2. Request API access
3. Generate API key
4. Cost: Varies by model

---

## 📊 Verify Installation

### Via Drush

```bash
# Check if modules are enabled
drush pm:list --status=enabled --filter=edaitorial
drush pm:list --status=enabled --filter=ai

# Test configuration
drush config:get edaitorial.settings use_ai
drush config:get ai.settings default_provider
```

### Via UI

1. **Check module status**: `/admin/modules` - Both "Drupal AI" and "edAItorial" should be enabled
2. **Access dashboard**: `/admin/config/content/edaitorial` - Should load successfully
3. **Run analysis**: Click any content in audit list - Should show AI analysis results

### Expected Results

✅ Dashboard loads in <1 second  
✅ Content list displays all nodes  
✅ Filters work (search, status, type)  
✅ Detail view shows AI analysis  
✅ Scores are calculated (0-100)  
✅ Issues grouped by type and severity  

---

## 🐛 Troubleshooting

### Module doesn't appear in module list

**Solution:**
```bash
drush cr
```

### "Module 'ai' is not installed" error

**Solution:**
```bash
composer require drupal/ai:^1.0
drush en ai -y
drush cr
```

### AI analysis fails or returns empty

**Possible causes:**
1. AI provider not configured
2. Missing/invalid API key
3. Network connectivity issues

**Solutions:**
1. Check AI provider config: `/admin/config/system/ai/providers`
2. Verify API key is correct
3. Check Drupal logs: `drush watchdog:show edaitorial`

### Dashboard shows errors

**Solution:**
```bash
# Reinstall the module
drush pm:uninstall edaitorial -y
drush en edaitorial -y
drush cr
```

### Slow performance (>10s for analysis)

**Possible causes:**
1. Server resources
2. Large content
3. Network latency

**Solutions:**
1. Increase PHP `memory_limit` to 512M
2. Increase `max_execution_time` to 120s
3. Check AI provider response times

---

## 📝 Installation Scripts

### Automated Install Script

Location: `./install.sh`

**Usage:**
```bash
# Basic install
./install.sh

# With suggested modules
./install.sh --with-suggested

# Skip AI configuration
./install.sh --skip-ai-config

# Show help
./install.sh --help
```

### Using dependencies.json

The `dependencies.json` file contains all dependency information and can be used for:

- Documentation
- Automated installation scripts
- Deployment automation
- CI/CD pipelines

**Reading dependencies:**
```bash
# View all dependencies
cat dependencies.json | jq '.composer_dependencies'

# View installation steps
cat dependencies.json | jq '.installation.automated_install.steps'

# View required permissions
cat dependencies.json | jq '.permissions'
```

---

## 🔄 Updating the Module

### Update via Composer

```bash
composer update drupal/ai maxfire/edaitorial
drush updb -y
drush cr
```

### Update via Drush

```bash
drush pm:update edaitorial
drush updb -y
drush cr
```

---

## 🗑️ Uninstalling

### Via Drush

```bash
drush pm:uninstall edaitorial -y
drush cr
```

### Via UI

1. Visit `/admin/modules/uninstall`
2. Check "edAItorial"
3. Click "Uninstall"
4. Confirm

**Note**: This will remove all module configuration and data.

---

## 📚 Additional Resources

- **README.md** - Module overview and features
- **QUICKSTART.md** - Quick start guide for developers
- **ARCHITECTURE.md** - Technical architecture documentation
- **BATCH_AI_OPTIMIZATION.md** - Token optimization details
- **PLUGINS_GUIDE.md** - Guide for creating custom checker plugins
- **dependencies.json** - Complete dependency reference
- **composer.json** - Composer package definition

---

## 🆘 Support

- **GitHub Issues**: https://github.com/Maxfire/edAItorial/issues
- **GitHub Repository**: https://github.com/Maxfire/edAItorial
- **Drupal AI Module**: https://www.drupal.org/project/ai

---

## 📄 License

GPL-2.0-or-later

---

**Version**: 1.0.0  
**Last Updated**: 2026-01-27  
**Module**: edAItorial
