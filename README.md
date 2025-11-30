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
| `inc/admin-aiml-settings.php` | Admin settings panel for AIMLAPI configuration |
| `inc/gutenberg-aiml-block.php` | Gutenberg block registration |
| `inc/classic-editor-button.php` | TinyMCE button for Classic Editor |
| `assets/` | CSS and JavaScript assets |

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

## 🔒 Security

- All user inputs are sanitized and escaped
- Nonce verification for AJAX requests
- Rate limiting for API calls
- Secure API key storage in WordPress options

## 📝 Changelog

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
