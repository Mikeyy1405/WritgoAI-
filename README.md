# WritgoCMS AI

AI-Powered Multi-Provider WordPress Plugin with AIML Integration.

![WritgoCMS AI Plugin](screenshot.png)

## 🚀 Features

### AI Integration
- **Text Generation**: OpenAI (GPT-4, GPT-3.5), Anthropic Claude, Google Gemini, Mistral AI
- **Image Generation**: DALL-E, Stability AI, Leonardo.ai, Replicate
- **Gutenberg Block Support**: AI-powered content generation directly in the block editor
- **Classic Editor Integration**: AI button for traditional editing experience

### Plugin Features
- **Multi-Provider Support**: Switch between AI providers easily
- **Rate Limiting**: Built-in rate limiting to prevent API abuse
- **Usage Statistics**: Track your AI generation usage
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
Note: The plugin directory should be named `WritgoCMS` to match the plugin slug.

## ⚙️ Configuration

### Setting Up AI Features
1. Go to WordPress Admin → Settings → WritgoCMS AIML
2. Enter your API keys for the providers you want to use:
   - OpenAI API Key
   - Anthropic Claude API Key
   - Google Gemini API Key
   - Mistral API Key
   - Stability AI API Key
   - Leonardo.ai API Key
   - Replicate API Key
3. Select your preferred default text and image providers
4. Save settings

### Using the Gutenberg Block
1. Create or edit a post/page
2. Add a new block and search for "AI Content Generator"
3. Enter your prompt and choose text or image generation
4. Click "Generate" and insert the result

### Using the Classic Editor Button
1. Create or edit a post/page with the Classic Editor
2. Click the AI button in the toolbar
3. Enter your prompt and generate content

## 📁 Plugin Structure

| Directory/File | Description |
|----------------|-------------|
| `writgo-cms.php` | Main plugin file with headers and initialization |
| `inc/class-aiml-provider.php` | Core AI provider class with API integrations |
| `inc/admin-aiml-settings.php` | Admin settings panel for configuration |
| `inc/gutenberg-aiml-block.php` | Gutenberg block registration |
| `inc/classic-editor-button.php` | TinyMCE button for Classic Editor |
| `assets/` | CSS and JavaScript assets |

## 📄 Requirements

- WordPress 5.9 or higher
- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.3

## 🔒 Security

- All user inputs are sanitized and escaped
- Nonce verification for AJAX requests
- Rate limiting for AI API calls
- Secure API key storage

## 📝 Changelog

### Version 1.0.0
- Initial release as WordPress Plugin
- AI integration with 4 text providers and 4 image providers
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

- Icons: [Lucide Icons](https://lucide.dev/)

---

Made with ❤️ for the WordPress community