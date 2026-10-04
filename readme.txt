=== Easy WP Snippets Manager ===
Contributors: yourwporgusername
Tags: snippets, header, footer, custom code, php
Requires at least: 5.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Paste HTML, CSS, JavaScript or PHP code and choose where it runs. The code type is detected automatically.

== Description ==

Easy WP Snippets Manager lets you add custom code to your site without editing theme files.

Just paste your code. The plugin detects whether it is HTML, CSS, JavaScript or PHP, so you do not need to choose a code type.

**Features**

* Add tracking codes, scripts and styles to the header, top of body or footer.
* Run PHP functions and hooks (actions, filters, shortcodes) as snippets.
* Run a snippet on the website, in the admin dashboard, or both.
* Turn any snippet on or off without deleting it.
* Code editor with syntax highlighting.
* PHP snippets are syntax-checked before they run. A snippet that causes an error is deactivated automatically.
* Translation ready.

**Security**

Only users with the `manage_options` capability (Administrators) can create or edit snippets. Code is output exactly as entered, because that is the purpose of the plugin, so only paste code from sources you trust.

To turn off all PHP snippets in an emergency, add this line to your `wp-config.php` file:

`define( 'EWPSM_DISABLE_PHP', true );`

== Installation ==

1. Upload the `nn-easy-wp-snippets-manager` folder to the `/wp-content/plugins/` directory, or install the plugin from the Plugins screen.
2. Activate the plugin through the Plugins screen.
3. Go to Snippets > Add New, paste your code, choose where it should go and publish.

== Frequently Asked Questions ==

= Do I need to choose HTML, CSS or JavaScript? =

No. The type is detected automatically. If detection is wrong, change "Code type" above the editor.

= Do I need to write the style or script tags? =

No. CSS and JavaScript are wrapped in the correct tags automatically. If you include the tags yourself, they are used as is.

= Do I need to write the opening PHP tag? =

No. Write only the PHP code. An opening tag is accepted but not required.

= My site broke after adding a PHP snippet. What do I do? =

Add `define( 'EWPSM_DISABLE_PHP', true );` to your `wp-config.php` file, then fix or remove the snippet and remove that line.

= Does this plugin connect to any external service? =

No. It does not send or collect any data.

= What happens to my snippets if I delete the plugin? =

All snippets and settings are removed when the plugin is deleted.

== Screenshots ==

1. The snippet editor with automatic code type detection.
2. The snippets list.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
