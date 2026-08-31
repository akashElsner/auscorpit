=== Zoho Inventory Import ===
Contributors: elsner
Tags: zoho, inventory, import, csv, products
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import products/items into Zoho Inventory from a CSV file with duplicate detection, progress reporting, and error logging.

== Description ==

Zoho Inventory Import allows WordPress administrators to bulk import items into Zoho Inventory using a CSV file.

Features:

* Admin upload page with CSV validation
* Row-by-row batch processing for large files
* Real-time import progress
* OAuth authentication via refresh token
* Duplicate detection by SKU (preferred) or item name
* Creates new items or updates existing ones
* Configurable default item type (required Zoho field)
* Import summary with downloadable error log

== Installation ==

1. Upload the `zoho-inventory-import` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Zoho Inventory Import → Settings** and enter your Zoho API credentials.
4. Go to **Zoho Inventory Import → Import CSV** and upload your CSV file.

== Configuration ==

1. Create a Server-based Application in the [Zoho API Console](https://api-console.zoho.com/).
2. Generate an authorization code with scope `ZohoInventory.fullaccess.all`.
3. Exchange the code for a refresh token via the Zoho OAuth token endpoint.
4. Enter Client ID, Client Secret, Refresh Token, Organization ID, and Data Centre in plugin settings.
5. Select the default **Item Type** (required by Zoho, not in CSV).

== CSV Format ==

Your CSV must use these column headers:

* **Name** → Zoho `name` (required)
* **Dynamic Supplies SKU** → Zoho `sku` (duplicate check)
* **Reseller Price Ex GST** → Zoho `rate`
* **RRP (inc GST)** → Zoho `purchase_rate`
* **Alternative Product Title** → Zoho `description` and `purchase_description`

Fields not in CSV use defaults from Settings:

* `unit` → default **10**
* `initial_stock` → default **10** (new items only)
* `upc`, `ean`, `isbn`, `part_number` → placeholder values
* `product_type` → default **goods**
* `item_type` → configured in Settings

See `examples/example-import.csv` in the plugin directory.

== Frequently Asked Questions ==

= How are duplicates handled? =

The plugin checks Zoho Inventory for an existing item by SKU first. If no SKU match is found, it searches by item name. Existing items are updated; new items are created.

= What happens if a row fails? =

Failed rows are logged. After import completes, download the error log for details.

== Changelog ==

= 1.0.0 =
* Initial release.
