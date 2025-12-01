# DataForSEO API Setup Guide

This guide explains how to set up DataForSEO integration for keyword research in WritgoAI.

## Table of Contents

- [What is DataForSEO](#what-is-dataforseo)
- [Getting Started](#getting-started)
- [Configuration](#configuration)
- [Credit Costs](#credit-costs)
- [Using Keyword Research](#using-keyword-research)
- [API Features](#api-features)
- [Troubleshooting](#troubleshooting)

## What is DataForSEO

DataForSEO is a comprehensive SEO data API that provides:

- Search volume data
- Keyword difficulty metrics
- Competition analysis
- SERP (Search Engine Results Page) data
- Related keywords suggestions
- Historical trends

WritgoAI uses DataForSEO to power the keyword research features.

## Getting Started

### Step 1: Create DataForSEO Account

1. Go to [DataForSEO](https://app.dataforseo.com/register)
2. Sign up for an account
3. Choose a pricing plan:
   - **Free Trial**: Limited requests for testing
   - **Pay-as-you-go**: $0.0001 per data row
   - **Monthly Plans**: Starting from $50/month

### Step 2: Get API Credentials

1. Log in to [DataForSEO Dashboard](https://app.dataforseo.com)
2. Go to **Settings → API Credentials**
3. Copy your **Login** (email)
4. Copy or reset your **Password**
5. Keep these secure - you'll need them for WordPress

### Step 3: Fund Your Account (If Required)

For paid plans:
1. Go to **Billing**
2. Add payment method
3. Add funds or set up auto-recharge
4. Monitor usage in dashboard

## Configuration

### WordPress Plugin Setup

1. Go to **WordPress Admin → WritgoAI → Instellingen**
2. Scroll to **DataForSEO API Settings** section
3. Enter your **Login** (email from DataForSEO)
4. Enter your **Password**
5. Click **Test Connection**
6. Wait for confirmation: ✅ **Connected**
7. Click **💾 Opslaan** to save

### Test Connection

The test connection verifies:
- Credentials are correct
- API is accessible
- Account has available credits
- No network/firewall issues

## Credit Costs

### WritgoAI Credits

WritgoAI uses its own credit system for DataForSEO features:

| Feature | WritgoAI Credits | Typical DataForSEO Cost |
|---------|-----------------|------------------------|
| Keyword Search | 15 credits | ~$0.001-0.01 |
| Related Keywords | 5 credits | ~$0.001-0.005 |
| SERP Analysis | 10 credits | ~$0.005-0.02 |

### DataForSEO Pricing

DataForSEO charges per API request:

- **Keyword Data**: ~$0.0001-0.001 per keyword
- **Related Keywords**: ~$0.0001 per keyword
- **SERP Data**: ~$0.005-0.02 per query
- **Bulk Operations**: Volume discounts available

Check [DataForSEO Pricing](https://dataforseo.com/apis/pricing) for current rates.

## Using Keyword Research

### Basic Keyword Search

1. Go to **WritgoAI → Keyword Research**
2. Enter a keyword (e.g., "wordpress seo")
3. Click **🔍 Search** (costs 15 credits)
4. View results:
   - **Search Volume**: Monthly searches
   - **Difficulty**: 0-100 (lower is easier)
   - **CPC**: Cost per click in ads
   - **Competition**: Low/Medium/High

### Getting Related Keywords

1. After searching a keyword
2. Click **Load Related Keywords** (5 credits)
3. View semantic variations and long-tail keywords
4. Click on any keyword to search it

### SERP Analysis

1. Search for a keyword first
2. Click **📊 View SERP** (10 credits)
3. See top 10 results:
   - Position in search results
   - Title and URL
   - Meta description
   - Domain authority indicators

### Saving Keywords

1. After searching a keyword
2. Review the data
3. Click **💾 Save to Plan**
4. Keyword is saved for later reference
5. View saved keywords at the bottom of the page

## API Features

### Available Data

DataForSEO provides:

1. **Search Volume**
   - Monthly search volume
   - Seasonal trends (if available)
   - Historical data

2. **Keyword Difficulty**
   - 0-30: Easy to rank
   - 31-60: Medium difficulty
   - 61-100: Hard to rank

3. **Commercial Intent**
   - CPC (Cost Per Click)
   - Competition level
   - Advertiser interest

4. **Related Keywords**
   - Semantic variations
   - Question keywords
   - Long-tail suggestions
   - People Also Ask queries

5. **SERP Features**
   - Organic results
   - Featured snippets
   - Knowledge panels
   - Image results

### Data Freshness

- **Search Volume**: Updated monthly
- **SERP Data**: Real-time
- **Keyword Difficulty**: Updated weekly
- **Cache**: 24 hours in WritgoAI

## Best Practices

### Keyword Research Strategy

1. **Start Broad**
   - Begin with general topic keywords
   - Use related keywords to expand
   - Look for long-tail variations

2. **Analyze Competition**
   - Check keyword difficulty
   - Review SERP results
   - Look for ranking opportunities

3. **Consider Intent**
   - Informational: How-to, guides
   - Commercial: Reviews, comparisons
   - Transactional: Buy, pricing

4. **Save and Organize**
   - Save promising keywords
   - Export for content planning
   - Track which keywords you target

### Credit Management

1. **Cache Awareness**
   - Recent searches are cached (24h)
   - Re-searching doesn't use credits
   - Clear cache if you need fresh data

2. **Batch Research**
   - Research multiple keywords in one session
   - Use related keywords feature
   - Plan content strategy before using credits

3. **Focus on Value**
   - Prioritize high-volume, low-difficulty keywords
   - Use SERP analysis for competitive keywords
   - Skip related keywords for well-known terms

## Troubleshooting

### Connection Failed

**Problem**: Cannot connect to DataForSEO API.

**Solutions**:
- Verify login and password are correct
- Check if account is active
- Ensure you have available balance
- Test API at DataForSEO dashboard
- Check firewall/proxy settings

### No Results Returned

**Problem**: Search returns no data or errors.

**Solutions**:
- Verify keyword isn't too specific/obscure
- Try a more general keyword
- Check DataForSEO account status
- Review API limits and quotas
- Check for typos in keyword

### Rate Limit Exceeded

**Problem**: "Rate limit exceeded" or similar error.

**Solutions**:
- Wait a few minutes before retrying
- DataForSEO has rate limits
- Check your account limits
- Upgrade plan if consistently hitting limits

### Insufficient Balance

**Problem**: "Insufficient credits" or "Payment required" error.

**Solutions**:
- Add funds to DataForSEO account
- Check billing status
- Set up auto-recharge
- Upgrade to monthly plan

### Invalid API Response

**Problem**: Data seems incorrect or incomplete.

**Solutions**:
- Clear WritgoAI cache (wait 24h or contact support)
- Verify DataForSEO API status
- Check if specific endpoint has issues
- Review DataForSEO changelog for API changes

## Data Privacy

### Data Collection

WritgoAI stores:
- Keywords you search
- Search volume and metrics
- Related keywords
- SERP data (when requested)

### Data Sharing

- No data is shared with third parties
- DataForSEO only receives your search queries
- All data stays in your WordPress database

### Data Retention

- Keyword data cached for 24 hours
- Saved keywords stored indefinitely
- Can be deleted manually anytime

## API Limits

### Rate Limits

DataForSEO standard limits:
- 2,000 requests/minute
- 100,000 requests/day
- 3,000,000 requests/month

WritgoAI implements:
- Request throttling
- Automatic retry on rate limit
- Cache to reduce duplicate requests

### Usage Monitoring

Monitor your usage:
1. **DataForSEO Dashboard**: Real-time usage
2. **WritgoAI Credits**: Check WordPress admin bar
3. **Billing Alerts**: Set up in DataForSEO

## Advanced Features

### Location and Language

Currently, WritgoAI uses:
- **Location**: United States (2840)
- **Language**: English (en)

Future versions will support:
- Multiple locations
- Multiple languages
- Custom location targeting

### Bulk Operations

For bulk keyword research:
1. Use CSV import (future feature)
2. API direct access via WP-CLI
3. Contact support for custom solutions

## Support Resources

### DataForSEO

- [Documentation](https://docs.dataforseo.com/)
- [Support](https://dataforseo.com/contact)
- [API Status](https://status.dataforseo.com/)
- [Changelog](https://docs.dataforseo.com/v3/changelog/)

### WritgoAI

- GitHub Issues
- Plugin documentation
- WordPress support forums

## Cost Examples

### Monthly Budget Planning

Example usage for 30 days:

| Activity | Frequency | Credits/Month | DataForSEO Cost |
|----------|-----------|---------------|-----------------|
| Main keyword research | 10/day | 4,500 | ~$3-10 |
| Related keywords | 5/day | 750 | ~$1-5 |
| SERP analysis | 3/day | 900 | ~$5-15 |
| **Total** | - | **6,150** | **~$9-30** |

Recommended WritgoAI plan: **Pro** (3,000 credits/month) or higher

## Next Steps

- [Complete setup guide](./SETUP.md)
- [Google Search Console setup](./SEARCH-CONSOLE.md)
- Start keyword research
- Plan content strategy
- Monitor and optimize

## Tips for Success

1. **Research Before Creating**
   - Always research keywords before writing
   - Look for gaps in competitor content
   - Find questions people are asking

2. **Track Results**
   - Save keywords you target
   - Monitor rankings in GSC
   - Adjust strategy based on performance

3. **Stay Updated**
   - Search volumes change seasonally
   - Monitor trending keywords
   - Refresh data periodically

4. **Combine Data Sources**
   - Use with Google Search Console
   - Cross-reference with your analytics
   - Consider user intent and behavior
