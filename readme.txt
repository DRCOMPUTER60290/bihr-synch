=== BIHR WooCommerce Importer ===
Contributors: drcomputer60290
Tags: woocommerce, bihr, synchronization, products, inventory, vehicle-compatibility
Requires at least: 5.6
Tested up to: 6.9
Stable tag: 1.4.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
BIHR WooCommerce Importer enables automatic synchronization of products, prices, inventory, and vehicle-product compatibility between the Bihr platform and WooCommerce. The plugin automates catalog imports and provides advanced inventory management features for motorcycle and powersports e-commerce stores.

**Key Features:**
* Automated Bihr catalog import (products, prices, stock, images, attributes)
* Scheduled price generation and updates
* Vehicle-to-product compatibility management
* Real-time inventory synchronization
* Detailed logging and diagnostic tools
* WooCommerce integration and order synchronization

== Installation ==
1. Upload the plugin files to `/wp-content/plugins/bihr-woocommerce-importer/`
2. Activate the plugin through the WordPress admin panel
3. Navigate to BIHR Dashboard and configure your Bihr API credentials
4. Set up product catalogs, vehicle compatibility, and synchronization schedules

== Frequently Asked Questions ==

= Which external services does this plugin use? =
The plugin integrates with the Bihr API (https://api.mybihr.com) for catalog synchronization. Optionally, it uses the OpenAI API for automatic product description enrichment.

= Is the plugin compatible with my WooCommerce version? =
The plugin requires WooCommerce and is tested up to WordPress 6.9.

= What Bihr API endpoints does it use? =
The plugin uses Bihr API v2.1 endpoints for catalog management, order synchronization, and vehicle compatibility data.

== Changelog ==

= 1.4.0 =
* Improved database query security and prepared statements
* Enhanced i18n support with translator comments
* Vehicle compatibility import with batch processing
* Order synchronization with Bihr API
* Real-time inventory syncing
* Added WP-Cron scheduling for automated updates