# Google Search Console Integration Guide

This guide explains how to set up Google Search Console integration with WritgoAI.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Google Cloud Console Setup](#google-cloud-console-setup)
- [Plugin Configuration](#plugin-configuration)
- [Authorizing Access](#authorizing-access)
- [Data Synchronization](#data-synchronization)
- [Using GSC Features](#using-gsc-features)
- [Troubleshooting](#troubleshooting)

## Prerequisites

Before starting, make sure you have:

- A Google account
- Google Search Console access to your website
- Admin access to your WordPress site
- WritgoAI plugin activated

## Google Cloud Console Setup

### Step 1: Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click **Select a project** → **New Project**
3. Enter project name (e.g., "WritgoAI")
4. Click **Create**

### Step 2: Enable Search Console API

1. In your project, go to **APIs & Services → Library**
2. Search for "Google Search Console API"
3. Click on it and click **Enable**

### Step 3: Create OAuth 2.0 Credentials

1. Go to **APIs & Services → Credentials**
2. Click **Create Credentials → OAuth client ID**
3. If prompted, configure the OAuth consent screen:
   - User Type: **External**
   - App name: WritgoAI
   - User support email: Your email
   - Developer contact: Your email
   - Click **Save and Continue**
   - Scopes: Skip (click **Save and Continue**)
   - Test users: Add your Google account
   - Click **Save and Continue**

4. Back to Create OAuth client ID:
   - Application type: **Web application**
   - Name: WritgoAI WordPress Plugin
   - Authorized redirect URIs: (Get this from WritgoAI settings - see next section)

### Step 4: Get Redirect URI

1. Go to **WordPress Admin → WritgoAI → GSC Instellingen**
2. Copy the **Redirect URI** shown at the top
3. Go back to Google Cloud Console
4. Paste the URI in **Authorized redirect URIs**
5. Click **Create**
6. Copy the **Client ID** and **Client Secret**

## Plugin Configuration

### Step 1: Enter Credentials

1. Go to **WordPress Admin → WritgoAI → GSC Instellingen**
2. Paste your **Client ID**
3. Paste your **Client Secret**
4. Click **Save Settings**

### Step 2: Authorize Connection

1. Click the **Verbind met Google** button
2. You'll be redirected to Google's authorization page
3. Select your Google account
4. Review permissions:
   - View Search Console data for your sites
5. Click **Allow**
6. You'll be redirected back to WordPress
7. You should see "Connection Successful"

### Step 3: Select Website

1. From the dropdown, select your website
2. Click **Select Site**
3. Click **Synchroniseer Nu** to start initial data sync

## Data Synchronization

### Initial Sync

The initial sync will fetch:
- Last 6 months of search query data
- Page performance data
- Click, impression, CTR, and position metrics
- Keyword opportunities

This may take a few minutes depending on your site's data volume.

### Automatic Sync

After initial setup, data syncs automatically:

- **Daily at 3:00 AM**: Fetch new data from last 30 days
- **On-Demand**: Click "Synchroniseer Nu" anytime
- **Cron-based**: Uses WordPress cron system

### Manual Sync

To manually trigger a sync:

1. Go to **WritgoAI → Search Console**
2. Click **Synchroniseer Nu**
3. Wait for the sync to complete

Or via WP-CLI:
```bash
wp cron event run writgocms_daily_sync
```

## Using GSC Features

### Dashboard Metrics

View GSC data on the main dashboard:

- **Monthly Traffic**: Total clicks from last 30 days
- **Average Ranking**: Mean position across all queries
- **Top Performing Pages**: Posts with most traffic
- **Keyword Opportunities**: Quick wins and improvements

### Keyword Opportunities

WritgoAI automatically detects opportunities:

1. **Quick Wins** (Position 11-20)
   - Keywords just outside page 1
   - Small optimization can reach page 1
   - High potential for traffic boost

2. **Low CTR**
   - Pages with impressions but low click-through
   - Need better meta titles/descriptions
   - Use CTR Optimizer tool

3. **Declining Rankings**
   - Keywords dropping 3+ positions
   - Need content refresh or optimization
   - Competitive analysis recommended

4. **Content Gaps**
   - High-volume keywords without strong ranking
   - Opportunity for new content
   - Check SERP competition

### CTR Optimization

Use the CTR Optimizer tool:

1. Go to **WritgoAI → CTR Optimalisatie**
2. Select a post from the list
3. Review current meta title and description
4. Enter target keyword (optional)
5. Click **Genereer AI Suggesties**
6. Copy improved suggestions to your SEO plugin

### Post-Level Analytics

View GSC data for individual posts:

1. Go to **Posts → All Posts**
2. See columns:
   - **Ranking**: Average position
   - **Traffic (30d)**: Clicks from last 30 days
3. Click on a post to see detailed analytics

## Troubleshooting

### Authorization Fails

**Problem**: Can't connect to Google Search Console.

**Solutions**:
- Verify Client ID and Secret are correct
- Check redirect URI matches exactly
- Ensure Search Console API is enabled
- Try clearing browser cache and cookies
- Check if OAuth consent screen is configured

### No Data After Sync

**Problem**: Sync completes but no data shown.

**Solutions**:
- Verify you selected the correct website
- Check if your site has Search Console data (may take 2-3 days after adding)
- Ensure the selected Google account has access to Search Console
- Check WordPress error logs
- Try disconnecting and reconnecting

### Sync Times Out

**Problem**: Sync takes too long or fails.

**Solutions**:
- Large sites may need multiple syncs
- Reduce date range in sync settings
- Check server timeout limits
- Increase PHP max_execution_time
- Run sync via WP-CLI for better performance

### Invalid Grant Error

**Problem**: "Invalid grant" or "Token expired" error.

**Solutions**:
- Token may have expired
- Disconnect and reconnect your account
- Check if OAuth consent screen is in production mode
- Verify app isn't suspended in Google Cloud Console

### Rate Limit Exceeded

**Problem**: "Rate limit exceeded" or "Quota exceeded" error.

**Solutions**:
- Wait 24 hours for quota to reset
- Reduce sync frequency
- Check your Google Cloud Console quotas
- Request quota increase if needed

### Missing Permissions

**Problem**: "Insufficient permissions" error.

**Solutions**:
- Verify OAuth scopes include Search Console read access
- Ensure the Google account is a verified owner in Search Console
- Re-authorize with correct account
- Check if API restrictions are blocking access

## Data Privacy

### What Data is Stored

WritgoAI stores:
- Search queries and their metrics
- Page URLs and performance data
- Historical data for trend analysis
- Aggregated statistics

### What Data is NOT Stored

- No personal user data
- No individual searcher information
- No data outside your selected property

### Data Retention

- Default: 6 months of historical data
- Older data is automatically archived
- Can be configured in settings

## Best Practices

1. **Regular Monitoring**
   - Check dashboard daily
   - Review weekly trends
   - Act on opportunities quickly

2. **Optimize Based on Data**
   - Focus on quick wins first
   - Improve low CTR pages
   - Update declining content

3. **Combine with Keyword Research**
   - Use GSC data to find actual search terms
   - Research related keywords
   - Create content around winning keywords

4. **Track Changes**
   - Note when you make optimizations
   - Monitor impact in GSC dashboard
   - Document what works

## API Limits

Google Search Console API has limits:

- **Queries per day**: 1,200 (default)
- **Queries per 100 seconds**: 100
- **Rows per request**: 25,000

WritgoAI respects these limits through:
- Rate limiting
- Request batching
- Caching responses

## Support

For GSC integration issues:

- Check [Google Search Console Help](https://support.google.com/webmasters/)
- Review [Google Cloud Console Documentation](https://cloud.google.com/docs)
- Contact WritgoAI support with error logs

## Next Steps

- [Set up DataForSEO](./DATAFORSEO.md)
- [Complete setup guide](./SETUP.md)
- Use CTR Optimizer tool
- Analyze keyword opportunities
