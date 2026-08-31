# Dynamic Supplies Datafeed Plugin

Downloads `ds-standard-datafeed.csv` from the Dynamic Supplies SFTP server once per day and saves it to your WordPress uploads folder.

## Requirements

One of the following must be available on your server:

| Option | How to enable |
|--------|--------------|
| **phpseclib** (recommended) | Run `composer install` inside the plugin directory |
| **php-ssh2 extension** | `sudo apt install php-ssh2` (or your distro equivalent) |

## Installation

1. Upload the `dynamic-supplies-datafeed` folder to `/wp-content/plugins/`.
2. Inside the plugin folder run:
   ```bash
   composer install --no-dev
   ```
3. Activate the plugin in **Plugins → Installed Plugins**.

## Usage

- The plugin schedules an automatic download every day at midnight (site timezone).
- Go to **DS Datafeed** in the WordPress admin sidebar to see the status and run the download manually.

## File locations

| Item | Path |
|------|------|
| Downloaded CSV | `wp-content/uploads/dynamic-supplies/ds-standard-datafeed.csv` |
| Log file | `wp-content/uploads/dynamic-supplies/dsdf.log` |

## Credentials (stored in plugin constants)

| Setting | Value |
|---------|-------|
| Host | `dsdatafeeds.blob.core.windows.net` |
| Port | `22` |
| Username | `dsdatafeeds.auswid` |
| Remote path | `/AUSWID/ds-standard-datafeed.csv` |
