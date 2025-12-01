# WritgoAI Setup Guide

This guide will help you set up and configure the WritgoAI plugin for WordPress.

## Table of Contents

- [Installation](#installation)
- [Basic Configuration](#basic-configuration)
- [Getting API Keys](#getting-api-keys)
- [Initial Site Analysis](#initial-site-analysis)
- [Troubleshooting](#troubleshooting)

## Installation

### Method 1: WordPress Admin Upload

1. Download the WritgoAI plugin ZIP file
2. Go to **WordPress Admin → Plugins → Add New**
3. Click **Upload Plugin**
4. Choose the ZIP file and click **Install Now**
5. Click **Activate Plugin**

### Method 2: FTP Upload

1. Extract the plugin ZIP file
2. Upload the `WritgoAI-plugin` folder to `/wp-content/plugins/`
3. Go to **WordPress Admin → Plugins**
4. Find "WritgoCMS AI" and click **Activate**

### Method 3: Git Clone (Developers)

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/Mikeyy1405/WritgoAI-plugin.git
```

Then activate through WordPress admin.

## Basic Configuration

After activating the plugin, you'll need to configure several services:

### 1. AI Service Configuration

The AI service should be configured by a system administrator:

1. Go to **WritgoAI → Instellingen**
2. Check the **AI Service Status** section
3. Contact your administrator if the service is not configured

### 2. Credit System

WritgoAI uses a credit-based system:

| Action | Credits |
|--------|---------|
| AI Rewrite (small) | 10 |
| AI Rewrite (paragraph) | 25 |
| AI Rewrite (full) | 50 |
| AI Image | 100 |
| SEO Analysis | 20 |
| Internal Links | 5 |
| Keyword Research | 15 |
| Related Keywords | 5 |
| SERP Analysis | 10 |

Check your credit balance in the WordPress admin bar or dashboard widget.

## Getting API Keys

### Google Search Console API

See [SEARCH-CONSOLE.md](./SEARCH-CONSOLE.md) for detailed setup instructions.

Quick steps:
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project
3. Enable the Search Console API
4. Create OAuth 2.0 credentials
5. Configure in **WritgoAI → GSC Instellingen**

### DataForSEO API

See [DATAFORSEO.md](./DATAFORSEO.md) for detailed setup instructions.

Quick steps:
1. Sign up at [DataForSEO](https://app.dataforseo.com)
2. Get your login and password
3. Configure in **WritgoAI → Instellingen** (scroll to DataForSEO section)
4. Click **Test Connection** to verify

## Initial Site Analysis

After configuration, run your first site analysis:

1. Go to **WritgoAI → Dashboard**
2. Click **Start Analysis** in the Workflow Progress section
3. Wait for the analysis to complete (may take a few minutes)
4. Review your website health score and metrics

The analysis will:
- Scan all published posts
- Calculate SEO scores
- Detect your site niche
- Find internal linking opportunities
- Generate health metrics

## Recommended Workflow

1. **Initial Setup** (First Time)
   - Configure AI service
   - Connect Google Search Console
   - Set up DataForSEO API
   - Run initial site analysis

2. **Daily Tasks**
   - Check dashboard for health score
   - Review quick stats
   - Monitor rankings and traffic
   - Analyze declining posts

3. **Weekly Tasks**
   - Research new keywords
   - Create content strategy
   - Optimize low-performing posts
   - Review SEO opportunities

4. **Automated Tasks** (Handled by Cron)
   - Daily GSC data sync (3 AM)
   - Weekly full site analysis (Sunday 3 AM)
   - Performance email reports

## Troubleshooting

### Site Analysis Not Working

**Problem**: Analysis button doesn't work or shows errors.

**Solutions**:
- Check if you have enough credits
- Verify database tables were created (check activation)
- Check WordPress error logs
- Ensure PHP version is 7.4 or higher

### Dashboard Shows No Data

**Problem**: Dashboard displays zeros or empty metrics.

**Solutions**:
- Run a site analysis first (Dashboard → Start Analysis)
- Check if you have published posts
- Verify cron jobs are running (`wp cron event list`)
- Check GSC connection status

### Credits Not Deducting

**Problem**: Credits don't decrease after using features.

**Solutions**:
- Verify license is active
- Check credit manager initialization
- Review WordPress error logs
- Contact support if issue persists

### Cron Jobs Not Running

**Problem**: Data doesn't sync automatically.

**Solutions**:
- Check if WP-Cron is disabled in `wp-config.php`
- Verify cron events are scheduled: `wp cron event list`
- Manually trigger: `wp cron event run writgocms_daily_sync`
- Set up system cron if WP-Cron is disabled

### API Connection Failures

**Problem**: Cannot connect to DataForSEO or Google Search Console.

**Solutions**:
- Verify API credentials are correct
- Check internet connectivity from server
- Review firewall/proxy settings
- Test API endpoints manually
- Check API rate limits and quotas

## System Requirements

- **WordPress**: 5.8 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or MariaDB 10.3
- **PHP Extensions**: curl, json
- **Memory**: 256MB minimum (512MB recommended)
- **Disk Space**: 50MB for plugin files + database

## Next Steps

- [Configure Google Search Console](./SEARCH-CONSOLE.md)
- [Set up DataForSEO](./DATAFORSEO.md)
- [Read the main README](../README.md)

## Support

For issues or questions:
- GitHub Issues: https://github.com/Mikeyy1405/WritgoAI-plugin/issues
- Email: support@writgoai.com (if available)
- Documentation: Check the docs folder
