# WritgoCMS

AI-Powered Full Site Editing (FSE) Block Theme with AIML Multi-Provider Integration.

![WritgoCMS Theme](screenshot.png)

## 🚀 Features

### Full Site Editing (FSE) Block Theme
- **Fully Block-Based**: All templates are editable via the WordPress Site Editor
- **Block Patterns**: 12+ pre-built patterns for quick page building
- **Template Parts**: Reusable header, footer, sidebar, and post meta components
- **Custom Templates**: About, Contact, and more page templates

### AI Integration
- **Text Generation**: OpenAI (GPT-4, GPT-3.5), Anthropic Claude, Google Gemini, Mistral AI
- **Image Generation**: DALL-E, Stability AI, Leonardo.ai, Replicate
- **Gutenberg Block Support**: AI-powered content generation directly in the editor
- **Classic Editor Integration**: AI button for traditional editing experience

### Theme Features
- **9 Block Templates**: Homepage, Single Post, Page, Archive, Search, 404, About, Contact
- **4 Template Parts**: Header, Footer, Sidebar, Post Meta
- **12+ Block Patterns**: Heroes, Features, Blog Grids, CTAs, Team, Stats, Testimonials
- **Fully Responsive Design**: Mobile-first approach with breakpoints for all devices
- **Modern CSS Variables**: Easy customization of colors, fonts, and spacing
- **Block Editor Support**: Full support for Gutenberg with custom styles

## 📁 Full Site Editing Structure

### Block Templates (`/templates/`)

| Template | Description |
|----------|-------------|
| `index.html` | Default fallback template with post grid |
| `home.html` | Homepage with hero, features, blog preview, CTA |
| `page.html` | Default page template |
| `page-about.html` | About Us page with team section |
| `page-contact.html` | Contact page with info cards |
| `single.html` | Single post with author box and sidebar |
| `archive.html` | Archive/category pages with post grid |
| `search.html` | Search results page |
| `404.html` | Custom 404 not found page |

### Template Parts (`/parts/`)

| Part | Description |
|------|-------------|
| `header.html` | Site header with navigation |
| `footer.html` | Multi-column footer with social links |
| `sidebar.html` | Sidebar with search, categories, recent posts |
| `post-meta.html` | Reusable post metadata (date, author, category) |

### Block Patterns (`/patterns/`)

**Hero Patterns:**
- `hero-gradient.php` - Full-width hero with gradient background
- `hero-image.php` - Hero with background image overlay
- `hero-minimal.php` - Clean minimal hero section

**Feature Patterns:**
- `features-grid-3col.php` - 3-column feature grid with icons
- `features-cards.php` - Feature cards with colored icons

**Blog Patterns:**
- `blog-grid-3col.php` - 3-column blog post grid with query loop

**CTA Patterns:**
- `cta-boxed.php` - Boxed call-to-action section
- `cta-fullwidth.php` - Full-width CTA with gradient

**Content Patterns:**
- `team-grid.php` - Team member grid
- `stats-row.php` - Statistics counter row
- `testimonials.php` - Testimonial cards
- `contact-info.php` - Contact information cards

## 📋 Installation

### Method 1: Direct Upload
1. Download the theme as a ZIP file
2. Go to WordPress Admin → Appearance → Themes
3. Click "Add New" → "Upload Theme"
4. Choose the ZIP file and click "Install Now"
5. Activate the theme

### Method 2: FTP Upload
1. Extract the theme ZIP file
2. Upload the `WritgoCMS` folder to `/wp-content/themes/`
3. Go to WordPress Admin → Appearance → Themes
4. Find WritgoCMS and click "Activate"

### Method 3: Git Clone
```bash
cd /path/to/wordpress/wp-content/themes/
git clone https://github.com/Mikeyy1405/WritgoCMS.git
```

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

### Editing Templates with Site Editor
1. Go to WordPress Admin → Appearance → Editor
2. Click on "Templates" or "Template Parts" in the sidebar
3. Select any template to edit
4. Use the block editor to customize content
5. Save your changes

### Using Block Patterns
1. Open any page or post in the editor
2. Click the "+" button to add a block
3. Go to the "Patterns" tab
4. Select "WritgoCMS" categories to see available patterns:
   - Hero Sections
   - Features
   - Blog Layouts
   - Call to Action
   - Content Sections
5. Click a pattern to insert it

## 🎨 Customization

### Dark Professional Theme
The theme features a modern dark design with the following color scheme:

```css
:root {
    /* Primary Blue & Orange Colors */
    --color-primary: #1877F2;
    --color-primary-dark: #1565c0;
    --color-secondary: #f97316;
    --color-accent: #fb923c;
    
    /* Dark Background Colors */
    --color-bg: #0f172a;
    --color-bg-alt: #1e293b;
    --color-bg-dark: #020617;
    
    /* Light Text Colors */
    --color-text: #f1f5f9;
    --color-text-light: #94a3b8;
    --color-heading: #f8fafc;
    
    /* Typography */
    --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --font-size-base: 1rem;
    
    /* Spacing */
    --container-max-width: 1200px;
    --spacing-md: 1rem;
    
    /* Border Radius */
    --radius-md: 0.5rem;
}
```

### theme.json
The `theme.json` file provides block editor configuration:
- Color palette for blocks
- Typography settings
- Spacing presets
- Layout widths

## 📁 Legacy Template Files

These PHP templates are still available for backward compatibility:

| File | Description |
|------|-------------|
| `header.php` | Legacy site header (use Site Editor for FSE) |
| `footer.php` | Legacy site footer (use Site Editor for FSE) |
| `index.php` | Fallback template for blog archives |
| `front-page.php` | Legacy homepage template |
| `page.php` | Legacy single page template |
| `single.php` | Legacy single post template |
| `sidebar.php` | Legacy sidebar widget area |
| `functions.php` | Theme setup and functionality |

## 🔌 JavaScript Features

The theme includes `assets/js/theme.js` with:
- **Mobile Navigation Toggle**: Responsive hamburger menu
- **Smooth Scrolling**: Smooth scroll to anchor links
- **Lazy Loading**: Image lazy loading for performance
- **Sticky Header**: Header behavior on scroll
- **Back to Top Button**: Scroll-to-top functionality
- **Form Validation**: Enhanced form validation
- **Keyboard Navigation**: Improved accessibility

## 📱 Responsive Breakpoints

```css
/* Mobile First */
/* Default styles for mobile */

/* Tablet */
@media (min-width: 768px) { }

/* Desktop */
@media (min-width: 1024px) { }

/* Large Desktop */
@media (min-width: 1200px) { }
```

## 🌐 Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Opera (latest)

## 📄 Requirements

- WordPress 5.9 or higher (for Full Site Editing)
- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.3

## 🔒 Security

- All user inputs are sanitized and escaped
- Nonce verification for AJAX requests
- Rate limiting for AI API calls
- Secure API key storage

## 📝 Changelog

### Version 2.0.0
- **Full Site Editing**: Complete FSE block theme transformation
- **Block Templates**: 9 block-based templates for all page types
- **Template Parts**: Reusable header, footer, sidebar, post-meta components
- **Block Patterns**: 12+ patterns (heroes, features, CTAs, team, stats, testimonials)
- **Enhanced theme.json**: Custom templates, template parts, pattern categories
- **All content editable**: No hardcoded content - everything via blocks

### Version 1.0.0
- Initial release
- AI integration with 4 text providers and 4 image providers
- Responsive design with CSS variables
- Block editor support
- Widget areas and menu locations
- JavaScript functionality for interactivity

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📜 License

This theme is licensed under the GNU General Public License v2 or later.
See [LICENSE](http://www.gnu.org/licenses/gpl-2.0.html) for more information.

## 👨‍💻 Author

**Mikeyy1405**
- GitHub: [@Mikeyy1405](https://github.com/Mikeyy1405)

## 🙏 Credits

- Icons: [Lucide Icons](https://lucide.dev/)
- Fonts: System font stack

---

Made with ❤️ for the WordPress community