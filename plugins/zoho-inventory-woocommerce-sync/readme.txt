=== Zoho Inventory WooCommerce Sync ===
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
WC requires at least: 7.0
WC tested up to: 8.x
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bi-directional synchronisation between WooCommerce and Zoho Inventory — customers,
products, orders, invoices, and inventory kept in sync automatically.

== Description ==

Zoho Inventory WooCommerce Sync connects your WooCommerce store to Zoho Inventory via
the official Zoho Inventory REST API v1. It keeps your inventory records up to date
without manual data entry by syncing customers, products/items, sales orders,
invoices, and stock levels in real time — in both directions.

Key capabilities:

* OAuth 2.0 authentication — no passwords stored, tokens refreshed automatically
* Real-time sync triggered by WooCommerce hooks (no cron dependency)
* Queue-based processing with per-item retry and exponential back-off
* Full sync log visible in the WordPress admin with status, entity, and error detail
* Manual "Sync All" bulk-import for customers, products, and orders
* Webhook receiver for Zoho Inventory → WooCommerce updates (contacts, items, invoices,
  sales orders)
* Every sync feature individually toggleable — enable only what you need
* Warehouse support — items can have per-warehouse stock levels in Zoho Inventory
* Optional Purchase Orders sync — syncs WooCommerce stock receipts and admin stock
  increases as Purchase Orders in Zoho Inventory

== Features ==

= Customers =
* New and updated WooCommerce customers are synced to Zoho Inventory Contacts
* Billing and shipping addresses mapped to the Zoho contact record
* Existing contacts matched by email to avoid duplicates
* Zoho → WC: contact.created webhook creates a new WC customer account
* Zoho → WC: contact.updated webhook updates name/email on the linked WC user

= Products / Items (WooCommerce → Zoho) =
* WooCommerce products synced to Zoho Inventory Items on create/update
* SKU, price, description, and tax status mapped automatically
* Non-taxable products handled correctly (tax exemption field omitted)
* Product featured image uploaded to the Zoho Inventory item record
* Per-warehouse stock support: items in Zoho Inventory can track stock across
  multiple warehouses; the default warehouse is used unless otherwise configured

= Products / Items (Zoho → WooCommerce) =
* item.updated webhook updates price, name, description, and stock on any
  WooCommerce product already linked to the Zoho item
* item.created webhook can optionally create a new WooCommerce product — controlled
  by the "Create WC Products from new Zoho Items" toggle (off by default)

= Orders / Sales Orders =
* New WooCommerce orders create a Zoho Inventory Sales Order automatically
* Sales orders confirmed immediately after creation (never left as draft)
* Order edits and status changes keep the Zoho sales order up to date
* Cancelled orders void the sales order (and invoice if one exists) in Zoho
* Refunded orders void the sales order and invoice in Zoho
* Zoho Inventory sales order status flow: confirmed → packed → shipped → delivered

= Invoices =
* Automatic invoice creation triggered by a configurable Zoho Inventory order status —
  choose Confirmed, Packed, Shipped, or Delivered in settings
* Invoice confirmed (marked as Sent) immediately after creation
* Invoice linked to the originating sales order; Zoho Inventory automatically updates
  the SO status to Invoiced
* Invoice creation is optional — disable the toggle to manage invoices manually in
  Zoho Inventory
* Cancelled / refunded orders automatically void both the invoice and sales order

= Inventory =
* WC → Zoho: stock quantity changes in WooCommerce synced to the Zoho Inventory item
* Zoho → WC (polling): hourly poll of recently-modified Zoho items applies updated
  stock_on_hand values back to WooCommerce products
* Zoho → WC (sales orders): when a sales order is created manually in Zoho Inventory,
  the ordered quantities can be automatically deducted from WooCommerce stock —
  controlled by the "Deduct WC Stock when Sales Order Created in Zoho" toggle
  (off by default). Requires the salesorder.created webhook to be configured.
* Sync-loop prevention: stock updates originating from Zoho suppress the WC → Zoho
  hook to avoid infinite loops
* Warehouse support: Zoho Inventory tracks stock per warehouse; this plugin reads the
  total stock_on_hand field, which reflects the aggregate across all warehouses

= Purchase Orders (optional) =
* When enabled, WooCommerce stock increases and admin stock receipts are synced to
  Zoho Inventory as Purchase Orders
* Keeps your Zoho Inventory purchase history accurate alongside WooCommerce stock
  adjustments
* Toggle independently from other sync features

= Sync Queue =
* All sync tasks flow through a database queue (wp_zoho_inv_queue)
* Up to 3 automatic retry attempts with exponential back-off on failure
* Configurable batch size (1–200 items per run)
* Queue status visible on the Dashboard tab

= Logging =
* Every sync action logged with timestamp, entity type, action, status, and message
* Errors surfaced immediately in the log — no digging through PHP error logs
* Log viewer in the admin panel with colour-coded Success / Error rows
* Debug mode available for verbose file logging (wp-content/debug.log)

= Webhooks (Zoho → WooCommerce) =
* REST endpoint: POST /wp-json/zoho-inventory-sync/v1/webhook
* Optional shared-secret token verification (X-Zoho-Webhook-Token header)
* Supported inbound events:

  contact.created   — creates a new WooCommerce customer
  contact.updated   — updates name/email on the linked WC user
  item.created      — optionally creates a new WC product (toggle)
  item.updated      — updates price/name/stock on the linked WC product
  invoice.created   — links the Zoho invoice ID to the matching WC order meta
  invoice.updated   — updates the Zoho invoice ID on the matching WC order meta
  salesorder.created — optionally deducts line item quantities from WC stock (toggle)

== Requirements ==

* WordPress 6.0 or higher
* WooCommerce 7.0 or higher
* PHP 8.0 or higher
* A Zoho Inventory account (any paid plan includes API access)
* A Zoho API client (Server-based application) with the correct redirect URI

== Installation ==

1. Upload the `zoho-inventory-woocommerce-sync` folder to `wp-content/plugins/`.
2. Activate the plugin through Plugins > Installed Plugins.
3. Follow the Configuration steps below to connect to Zoho Inventory.

== Configuration ==

=== Step 1 — Create a Zoho API Client ===

1. Go to https://api-console.zoho.com/ and sign in with your Zoho account.
2. Click "Add Client" and choose "Server-based Applications".
3. Fill in:
   - Client Name: anything descriptive (e.g. "My WooCommerce Store")
   - Homepage URL: your WordPress site URL
   - Authorized Redirect URIs: copy this from the plugin's OAuth tab (see Step 2)
4. Click Create. Copy the Client ID and Client Secret.

=== Step 2 — Connect the Plugin ===

1. In WordPress admin go to Zoho Inventory Sync > OAuth / Connection.
2. Enter your Client ID, Client Secret, Organisation ID, and select your Zoho
   data centre (e.g. .com, .eu, .in, .com.au, .jp).
3. Copy the Redirect URI shown on the page and confirm it matches what you entered
   in the Zoho API Console.
4. Click "Connect to Zoho Inventory". You will be redirected to Zoho to authorise
   access using the ZohoInventory.fullaccess.all scope.
5. After authorising, you are redirected back to WordPress. The status should show
   "Connected" with your organisation name.

To find your Organisation ID:
- Log in to Zoho Inventory > Settings > Organisation Profile.
- The Organisation ID is shown at the bottom of the page.

=== Step 3 — Configure Sync Settings ===

Go to Zoho Inventory Sync > Settings and configure:

**Sync Toggles**

- Sync Customers
  Syncs new and updated WooCommerce customers to Zoho Inventory Contacts.

- Sync Products / Items (WooCommerce → Zoho)
  Syncs WooCommerce products to Zoho Inventory Items on save.

- Create WC Products from new Zoho Items (Zoho → WooCommerce)
  When enabled, a new WooCommerce product is created whenever Zoho Inventory fires an
  item.created webhook. Requires the item.created event to be subscribed in Zoho.
  Off by default.

- Sync Orders
  Syncs new WooCommerce orders to Zoho Inventory Sales Orders.

- Sync Stock / Inventory (WooCommerce → Zoho)
  Pushes WooCommerce stock quantity changes to the linked Zoho Inventory item.

- Deduct WC Stock when Sales Order Created in Zoho (Zoho → WooCommerce)
  When enabled, a salesorder.created webhook from Zoho Inventory deducts each line
  item's ordered quantity from the matching WooCommerce product stock. Useful when
  orders are placed directly in Zoho Inventory (e.g. phone orders, walk-ins). Requires
  the salesorder.created event to be subscribed in Zoho. Off by default.

- Sync Purchase Orders
  When enabled, WooCommerce stock increases and admin stock receipts are synced to
  Zoho Inventory as Purchase Orders. Keeps purchase history accurate alongside
  WooCommerce stock adjustments. Off by default.

- Debug Mode
  Writes verbose log output to wp-content/debug.log.

**Invoice Settings**

- Auto-Create Invoice
  Automatically creates a Zoho Inventory invoice when an order reaches the trigger
  status below. The invoice is linked to the sales order and confirmed (marked as
  Sent) immediately.

- Create Invoice on Status
  Choose which Zoho Inventory sales order status triggers invoice creation:
  "Confirmed", "Packed", "Shipped", or "Delivered".

- Cancelled / Refunded Orders
  Both the invoice (if any) and the sales order are voided in Zoho Inventory
  automatically. This cannot be disabled.

**Performance**

- Batch Size
  Number of queue items processed per cron run (default 50, max 200).

**Webhooks** (optional but recommended for Zoho → WC sync)

- Webhook URL
  Configure this URL as the notification endpoint in Zoho Inventory > Settings >
  Webhooks to receive Zoho events in WooCommerce.

- Webhook Secret Token
  Set the same value in Zoho and this field. The plugin checks the
  X-Zoho-Webhook-Token header on every inbound request. Leave blank to accept
  webhooks without verification (not recommended on public servers).

=== Step 4 — Configure Zoho Inventory Webhooks (for Zoho → WC features) ===

To receive events from Zoho Inventory:

1. In Zoho Inventory go to Settings > Automation > Webhooks > New Webhook.
2. Set the Notification URL to the Webhook URL copied from the plugin settings.
3. Add the X-Zoho-Webhook-Token header with the same secret you set in the plugin.
4. Subscribe to the events you need:

   For Zoho → WC contact sync:     contact.created, contact.updated
   For Zoho → WC item sync:        item.created (if toggle on), item.updated
   For Zoho → WC invoice linking:  invoice.created, invoice.updated
   For Zoho → WC stock deduction:  salesorder.created (if toggle on)

=== Step 5 — Initial Import (optional) ===

Go to Zoho Inventory Sync > Manual Sync to bulk-import existing records:

- Sync All Customers — imports all WooCommerce customers to Zoho Inventory Contacts
- Sync All Products  — imports all WooCommerce products to Zoho Inventory Items
- Sync All Orders    — imports existing WooCommerce orders to Zoho Inventory Sales Orders

Bulk imports are queued and processed in batches. Watch the Dashboard tab for
progress.

== Data Flow ==

WooCommerce → Zoho Inventory:

  WooCommerce event fires (order placed, product saved, stock changed …)
        |
        v
  Hook callback enqueues item into wp_zoho_inv_queue
        |
        v
  Late-priority WC hook calls Sync_Queue::process() in the same request
        |
        v
  API call to Zoho Inventory REST API v1
        |
        v
  Success → item marked "completed" + logged
  Failure → retry up to 3× with exponential back-off → marked "failed" + logged

Zoho Inventory → WooCommerce:

  Zoho Inventory fires a webhook to POST /wp-json/zoho-inventory-sync/v1/webhook
        |
        v
  Token verified → event_type routed to correct handler
        |
        v
  Handler updates WC (customer, product, stock, order meta …)
        |
        v
  Action logged

== Frequently Asked Questions ==

= Does this work with variable products? =
Simple and variable products are supported. Each product variation syncs as a
separate Zoho Inventory item.

= What happens if a sync fails? =
Failed items are retried automatically up to three times using exponential
back-off (2 min, 4 min, 8 min). After three failures the item is marked as
"failed" and logged with the error message for manual investigation.

= Is customer data stored securely? =
OAuth tokens are stored in the WordPress options table, encoded with base64.
No Zoho passwords are ever stored. For production use on a public server,
ensure your wp-config.php contains strong, unique secret keys.

= Can I sync just orders without syncing customers? =
Yes. The Customer sync toggle is independent. When Sync Customers is off, the
plugin will still create/update a Zoho contact on the fly for each order so that
the sales order has a valid customer — it simply won't sync standalone customer
profile updates.

= What Zoho data centres are supported? =
All Zoho data centres: .com (US), .eu (Europe), .in (India), .com.au (Australia),
.jp (Japan).

= Does the plugin support Zoho Inventory warehouses? =
Yes. Zoho Inventory tracks stock per warehouse. This plugin reads the total
stock_on_hand field, which reflects the aggregate across all warehouses, when
syncing stock back to WooCommerce.

= What OAuth scope is required? =
The plugin requests the ZohoInventory.fullaccess.all scope during authorisation.
This covers all Inventory API resources: contacts, items, sales orders, invoices,
and purchase orders.

= Will deducting stock from a Zoho sales order cause a WooCommerce → Zoho loop? =
No. The deduction suppresses the WC stock-change hook during the update so no
reverse sync is triggered.

= Do I need to re-enter OAuth credentials after updating the plugin? =
Only if noted in the changelog. Credentials are stored in the WordPress database
and survive plugin updates.

== Changelog ==

= 1.0.0 =
* Initial release
* OAuth 2.0 connection with all Zoho data centres using ZohoInventory.fullaccess.all scope
* Real-time customer, product, order, and inventory sync (WooCommerce → Zoho)
* Sales order confirmed automatically after creation (not left as draft)
* Configurable invoice trigger status (Confirmed, Packed, Shipped, or Delivered)
* Invoice confirmed (Sent) immediately after creation
* Invoice linked to sales order via salesorder_ids; SO auto-marked as Invoiced
* Invoice and sales order voided automatically on order cancellation or refund
* Optional: create WC products from new Zoho Inventory items via webhook
* Optional: deduct WC stock when a sales order is created manually in Zoho Inventory
* Optional: sync Purchase Orders from WooCommerce stock receipts to Zoho Inventory
* Zoho → WC webhook support: contacts, items, invoices, sales orders
* Queue-based processing with retry, back-off, and per-item logging
* Manual bulk-sync tools for customers, products, and orders
* Product image upload to Zoho Inventory items
* Sync-loop prevention for all Zoho → WC stock updates
* Warehouse-aware stock polling (reads aggregate stock_on_hand)

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required.
