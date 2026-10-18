# Schema Frameworks

**Schema Frameworks** is a WordPress plugin for configuring and reviewing Schema.org structured data across pages, posts, and supported public custom post types. It provides modular schema controls, content-level schema settings, post-type mapping, diagnostic tools, and filtered CSV exports.

- **Author:** Naushad A.
- **Version:** 1.7.9
- **License:** GPL-2.0-or-later
- **Platform:** WordPress
- **SEO integration:** Yoast SEO

## Author’s Note

Schema Frameworks is published on GitHub to make its schema-management features freely accessible to WordPress users, including features often associated with premium SEO extensions. After being unable to complete registration on the official WordPress plugin portal, we decided to share the plugin directly so users can access it without a purchase fee.

## Features

- Modular Schema.org configuration.
- Page schema settings and page-level controls.
- Post and supported custom post type schema generation through Yoast integration.
- Post-type mapping recommendations, confirmation, and overrides.
- Per-content schema controls and supported schema-type overrides.
- Custom FAQ question-and-answer editor with FAQ structured-data output when enabled.
- Diagnostic dashboard and batch scanning for published content.
- Schema validation and diagnostic snapshots.
- Filter-aware CSV export of diagnostic results.
- WordPress capability checks, nonce verification, and sanitized settings for administrative actions.

## Requirements and compatibility

- WordPress installation with administrator access for plugin configuration.
- PHP 8.x and WordPress 7.1.x is the development/test environment; verify compatibility on your hosting environment before use.
- Yoast SEO is used for the plugin's schema graph integration.

## Installation

1. Back up your website and database.
2. In WordPress, go to **Plugins → Add New Plugin → Upload Plugin**.
3. Upload the `schema-frameworks-v1.7.9.zip` package.
4. Activate **Schema Frameworks**.
5. Open the **Schema Frameworks** menu in the WordPress admin area and review the available settings.
6. Test representative pages and posts, schema output, diagnostic scanning, and CSV export before enabling it broadly on a production site.

If replacing an existing version, take a backup first and preserve the existing plugin settings.

## Post-type mapping and content schema

Schema Frameworks provides a post-type mapping interface for reviewing recommendations and confirming or overriding mappings. Supported published posts and public custom post types can use supported schema types when the relevant module and mapping are enabled. Unmapped, unsupported, or ineligible content may be skipped.

Pages use the plugin's page-schema configuration path. Review the generated structured data on the front end after changing mappings or content-level settings.

## Custom FAQ schema

The custom FAQ editor allows questions and answers to be managed for supported content. When the relevant FAQ functionality is enabled, the plugin can include FAQ structured data in its output.

Structured data does not automatically display FAQ content on the public page. If visitors need to read the questions and answers, ensure that the page also presents that content visibly.

## Diagnostics and CSV export

The diagnostic dashboard includes a batch scanner for published content in public post types, progress reporting, and diagnostic information. The scanner may request same-site URLs in batches, so keep the dashboard open while a scan is running. Available controls may allow a scan to be paused and resumed.

CSV export follows the selected diagnostic filters. Review exported data before sharing it externally.

## Security and support

Administrative features should only be used by appropriately privileged WordPress users. Keep WordPress, PHP, Yoast SEO, and other plugins updated, and test changes on staging before deploying to production.

For bug reports, include the plugin version, WordPress version, PHP version, Yoast SEO version, steps to reproduce, and relevant sanitized error messages. Do not post passwords, API keys, personal data, or other secrets.

## Changelog

Initial Release on GitHub: v1.7.9
