=== AI Content Enhancer ===
Contributors: yourname
Tags: ai, content, enhancement, openai, editor, backup
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered content enhancement tool for WordPress posts with automatic backup and restore functionality.

== Description ==

AI Content Enhancer integrates OpenAI's powerful language models directly into your WordPress post editor, allowing you to enhance and improve your content with just a click. The plugin automatically creates backups of your original content, so you can always revert changes if needed.

### Key Features

* **One-Click Enhancement**: Add an AI enhance button directly in your post editor
* **Automatic Backups**: Every enhancement automatically creates a backup of your original content
* **Easy Restore**: View and restore from multiple backup versions with a simple interface
* **Customizable Prompts**: Configure system prompts and enhancement instructions to match your needs
* **Multiple AI Models**: Support for GPT-3.5 Turbo, GPT-4, and other OpenAI models
* **Safe & Secure**: Your content is only sent to OpenAI for enhancement, with proper error handling

### How It Works

1. Install and activate the plugin
2. Configure your OpenAI API key in Settings > AI Enhancer
3. Go to any post edit screen
4. Click the "🧠 AI Enhance" button next to the title
5. Your content will be automatically backed up and then enhanced
6. Use the "📝 Backups" button to view and restore previous versions

### Requirements

* WordPress 5.0 or higher
* PHP 7.4 or higher
* OpenAI API key (get one at https://platform.openai.com/api-keys)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/ai-content-enhancer` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > AI Enhancer to configure your OpenAI API key
4. Start enhancing your content!

== Frequently Asked Questions ==

= Do I need an OpenAI API key? =

Yes, you need an OpenAI API key to use this plugin. You can get one for free at https://platform.openai.com/api-keys. Note that API usage may incur costs based on OpenAI's pricing.

= Is my content sent to OpenAI? =

Yes, when you click the enhance button, your content is sent to OpenAI's API for processing. The enhanced content is then returned to your WordPress site. Your content is not stored by OpenAI beyond the API request.

= Can I customize how the AI enhances my content? =

Yes! You can customize both the system prompt (which defines the AI's role) and the enhancement instructions in the plugin settings.

= What happens to my original content? =

Every time you enhance content, the plugin automatically creates a backup of your original content. You can view and restore from these backups at any time using the backups button.

= How many backups are kept? =

By default, the plugin keeps the last 5 backups per post. You can adjust this number in the plugin settings (maximum 20 backups per post).

== Screenshots ==

1. The AI Enhance and Backups buttons in the post editor
2. Plugin settings page with OpenAI configuration
3. Backups modal showing content history
4. Enhancement in progress with loading indicator

== Changelog ==

= 1.0.0 =
* Initial release
* AI content enhancement with OpenAI integration
* Automatic backup and restore functionality
* Customizable prompts and settings
* Support for multiple AI models

== Upgrade Notice ==

= 1.0.0 =
Initial release of AI Content Enhancer.

== Privacy Policy ==

This plugin sends your post content to OpenAI's API when you use the enhancement feature. Please review OpenAI's privacy policy at https://openai.com/policies/privacy-policy to understand how your data is handled.

== Support ==

For support, feature requests, or bug reports, please visit the plugin's support forum or contact the developer.