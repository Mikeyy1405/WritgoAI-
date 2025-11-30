# WritgoCMS AI

AI-Powered WordPress Plugin with AIMLAPI Integration.

![WritgoCMS AI Plugin](screenshot.png)

## 🚀 Features

### AI Integration via AIMLAPI
- **Unified API Access**: Single AIMLAPI key provides access to multiple AI models
- **Text Generation Models**: GPT-4o, GPT-4, GPT-4 Turbo, GPT-3.5 Turbo, Claude 3 (Opus, Sonnet, Haiku), Mistral (Large, Medium, Small)
- **Image Generation Models**: DALL-E 3, DALL-E 2, Stable Diffusion XL, Flux Schnell
- **OpenAI-Compatible API**: Uses the standard chat/completions endpoint format

### Plugin Features
- **Gutenberg Block Support**: AI-powered content generation directly in the block editor
- **Classic Editor Integration**: AI button for traditional editing experience
- **Rate Limiting**: Built-in rate limiting to prevent API abuse
- **Usage Statistics**: Track your AI generation usage over 30 days
- **Caching**: Response caching for improved performance
- **Media Library Integration**: Generated images are automatically saved to the Media Library

### 🗺️ Content Planner - Topical Authority Map Generator
The Content Planner is an AI-powered tool that helps you create comprehensive content strategies:

- **Topical Authority Maps**: Generate complete content plans with pillar topics and cluster articles
- **AI-Powered Planning**: Let AI do the strategic thinking for your content
- **Keyword Suggestions**: Get relevant keywords for each article in your plan
- **Priority Levels**: Articles are prioritized (high, medium, low) for publishing order
- **Detailed Content Outlines**: Generate comprehensive article outlines with sections, subsections, and key points
- **Save & Export**: Save your content plans for later or export them as JSON
- **Internal Linking Suggestions**: Get recommendations for internal link opportunities
- **CTA Suggestions**: Receive call-to-action ideas for each piece of content

### 📊 Google Search Console Integration
Complete GSC integration for data-driven content decisions:

- **OAuth 2.0 Authentication**: Secure connection with Google Search Console
- **Automatic Data Sync**: Daily synchronization of search data (6 months historical data)
- **Metrics Dashboard**: View impressions, clicks, CTR, and average position
- **Keyword Opportunity Detection**:
  - **Quick Wins**: Keywords on position 11-20 that can reach page 1
  - **Low CTR**: Pages with high impressions but below benchmark CTR
  - **Declining Rankings**: Keywords losing positions (-3 or more)
  - **Content Gaps**: High-volume keywords without strong rankings

### ✨ CTR Optimization Tool
AI-powered meta content optimization:

- **Meta Analysis**: Analyze current meta titles and descriptions
- **CTR Benchmarking**: Compare your CTR with industry benchmarks by position
- **AI Suggestions**: Generate improved meta titles and descriptions with AI
- **Expected Improvement**: See estimated CTR improvement for each suggestion
- **One-Click Copy**: Copy suggestions directly to your clipboard

## 📋 Installation

### Method 1: Upload ZIP via WordPress Admin
1. Download the plugin as a ZIP file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin"
4. Choose the ZIP file and click "Install Now"
5. Click "Activate Plugin"

### Method 2: FTP Upload
1. Extract the plugin ZIP file
2. Upload the `WritgoCMS` folder to `/wp-content/plugins/`
3. Go to WordPress Admin → Plugins
4. Find "WritgoCMS AI" and click "Activate"

### Method 3: Git Clone
```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/Mikeyy1405/WritgoCMS.git WritgoCMS
```

## ⚙️ Configuration

### Setting Up AIMLAPI
1. Go to WordPress Admin → Settings → WritgoCMS AIML
2. Enter your **AIMLAPI Key** (get one at [aimlapi.com](https://aimlapi.com))
3. Select your preferred **Default Text Model** (e.g., `gpt-4o`)
4. Select your preferred **Default Image Model** (e.g., `dall-e-3`)
5. Adjust temperature and max tokens settings if needed
6. Save settings

### Using the Content Planner
1. Go to Settings → WritgoCMS AIML → Content Planner tab
2. Enter your main niche/topic (e.g., "Digital Marketing", "Home Fitness")
3. Select your website type (blog, e-commerce, SaaS, etc.)
4. Optionally describe your target audience
5. Click "Generate Topical Authority Map"
6. Review your generated pillar content and cluster articles
7. Click "Generate Detailed Plan" on any article to get a full content outline
8. Save your plan or export it as JSON for reference

### Setting Up Google Search Console
1. Go to the [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. Create a new OAuth 2.0 Client ID
3. Add the redirect URI from WritgoAI → GSC Settings
4. Copy the Client ID and Client Secret
5. Go to WritgoAI → GSC Instellingen
6. Enter your Client ID and Client Secret
7. Click "Verbind met Google" to authorize
8. Select your website from the available sites
9. Click "Synchroniseer Nu" to fetch initial data

### Using the GSC Dashboard
1. Go to WritgoAI → Search Console
2. View your metrics overview (clicks, impressions, CTR, position)
3. Browse keyword opportunities by type:
   - Quick Wins: Keywords close to page 1
   - Lage CTR: Underperforming CTR
   - Dalende Rankings: Declining positions
   - Content Gaps: Untapped keywords
4. View top keywords and top pages
5. Data syncs automatically every day

### Using the CTR Optimizer
1. Go to WritgoAI → CTR Optimalisatie
2. Select a post from the list
3. View the current meta title and description analysis
4. Optionally enter a target keyword
5. Click "Genereer AI Suggesties" to get improvement suggestions
6. Copy the suggested meta content to your SEO plugin

### Using the Gutenberg Block
1. Create or edit a post/page
2. Add a new block and search for "AI Content Generator"
3. Select text or image generation mode
4. Enter your prompt and click "Generate"
5. Insert the generated content as a block

### Using the Classic Editor Button
1. Create or edit a post/page with the Classic Editor
2. Click the AI button in the toolbar
3. Enter your prompt and generate content

### Using the Test Interface
1. Go to Settings → WritgoCMS AIML → Test & Preview tab
2. Select the generation type (Text or Image)
3. Choose a model from the dropdown
4. Enter a prompt and click "Generate"

## 📁 Plugin Structure

| Directory/File | Description |
|----------------|-------------|
| `writgo-cms.php` | Main plugin file with headers and initialization |
| `inc/class-aiml-provider.php` | Core AIMLAPI provider class with API integrations |
| `inc/class-content-planner.php` | Content Planner with Topical Authority Map generation |
| `inc/class-gsc-provider.php` | Google Search Console OAuth 2.0 provider |
| `inc/class-gsc-data-handler.php` | GSC data storage, sync, and opportunity detection |
| `inc/class-ctr-optimizer.php` | CTR analysis and AI-powered optimization |
| `inc/admin-aiml-settings.php` | Admin settings panel for AIMLAPI configuration |
| `inc/admin-gsc-settings.php` | Admin settings panel for GSC configuration |
| `inc/gutenberg-aiml-block.php` | Gutenberg block registration |
| `inc/classic-editor-button.php` | TinyMCE button for Classic Editor |
| `assets/` | CSS and JavaScript assets |

## 🗄️ Database Tables

The plugin creates the following database tables for GSC data:

| Table | Description |
|-------|-------------|
| `wp_writgoai_gsc_queries` | Search query data (query, clicks, impressions, CTR, position, date) |
| `wp_writgoai_gsc_pages` | Page performance data (url, post_id, clicks, impressions, CTR, position, date) |
| `wp_writgoai_gsc_opportunities` | Keyword opportunities (keyword, type, score, suggested_action) |

## 🔧 Available Models

### Text Generation
| Model ID | Name |
|----------|------|
| `gpt-4o` | GPT-4o (default) |
| `gpt-4` | GPT-4 |
| `gpt-4-turbo` | GPT-4 Turbo |
| `gpt-3.5-turbo` | GPT-3.5 Turbo |
| `claude-3-opus-20240229` | Claude 3 Opus |
| `claude-3-sonnet-20240229` | Claude 3 Sonnet |
| `claude-3-haiku-20240307` | Claude 3 Haiku |
| `mistral-large-latest` | Mistral Large |
| `mistral-medium-latest` | Mistral Medium |
| `mistral-small-latest` | Mistral Small |

### Image Generation
| Model ID | Name |
|----------|------|
| `dall-e-3` | DALL-E 3 (default) |
| `dall-e-2` | DALL-E 2 |
| `stable-diffusion-xl-1024-v1-0` | Stable Diffusion XL |
| `flux-schnell` | Flux Schnell |

## 📄 Requirements

- WordPress 5.9 or higher
- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.3
- AIMLAPI account and API key
- Google Cloud Console project (for GSC integration)

## 🔒 Security

- All user inputs are sanitized and escaped
- Nonce verification for AJAX requests
- Rate limiting for API calls
- Secure API key storage in WordPress options

## 📝 Changelog

### Version 1.1.0
- Google Search Console integration with OAuth 2.0
- Keyword opportunity detection (Quick Wins, Low CTR, Declining, Content Gaps)
- CTR optimization tool with AI-powered suggestions
- GSC Dashboard with metrics overview
- Automatic daily data synchronization
- Dutch language support for GSC interface

### Version 1.0.0
- Initial release as WordPress Plugin
- AIMLAPI integration with unified API access
- Support for multiple text and image generation models
- Gutenberg block support
- Classic Editor integration
- Usage statistics dashboard

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📜 License

This plugin is licensed under the GNU General Public License v2 or later.
See [LICENSE](http://www.gnu.org/licenses/gpl-2.0.html) for more information.

## 👨‍💻 Author

**Mikeyy1405**
- GitHub: [@Mikeyy1405](https://github.com/Mikeyy1405)

## 🙏 Credits

- [AIMLAPI](https://aimlapi.com) - Unified AI API provider
- Icons: [Lucide Icons](https://lucide.dev/)

---

Made with ❤️ for the WordPress community
