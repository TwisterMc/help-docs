# Help Docs

Create a help section directly in your WordPress admin dashboard.

## Description

Help Docs makes it easy to build internal documentation for your WordPress site. The plugin creates a custom post type that exists exclusively in the backend, allowing you to build a knowledge base that's always accessible to your site administrators and editors.

## Features

- **Simple Content Creation**: Create help documentation just like any standard WordPress post or page
- **Hierarchical Organization**: Build nested documentation with parent-child page relationships for easy navigation
- **Flexible Editor Support**: Works with both the Classic Editor (default) and Gutenberg Block Editor
- **WordPress Admin Only Access**: All documentation is accessible only within the WordPress admin area

## How It Works

1. Create new help documents using the familiar WordPress editor
2. Organize your content with parent pages and nested subpages
3. Publish your documentation
4. Access everything from the main Help Docs page in your admin dashboard

## Gutenberg Support

Gutenberg (Block Editor) support can be enabled in the plugin settings. When enabled:

- You can use the full block editor for creating your help documentation
- Help docs will be accessible via the WordPress REST API
- Content remains blocked to unauthenticated users

## Updates

Currently, this plugin does not auto-update. Please check back periodically for new versions and improvements.

## Support

For questions, issues, or feature requests, please reach out through GitHub.

## System Requirements

- **WordPress:** 6.0 or greater
- **PHP:** 8.0 or greater
- **Database:** MySQL 8.0 or greater OR MariaDB 10.6 or greater
- **HTTPS:** Required for admin pages (enforce at site/server level)

### Release Notes

**Feb 6, 2026**

- Version 0.3
- Improved documentation
- Raised PHP requirement to 8.0+, MySQL to 8.0+, and MariaDB to 10.6+ to drop support for insecure, end-of-life versions.
- Updated post status' to be private.
- Updated some internal strings.

**Jan 5, 2026**

- Version 0.2
- Added Settings page for customization options
- Customizable page title (default: "Help Docs")
- Optional Gutenberg support - can be enabled/disabled in Settings

**Jan 29, 2020**

- Better Read Me
- Relative to Absolute URLs

**Oct 22, 2019**

- Re-working out we're outputting pages so that we can get children of children and so on.
- Disabled the settings page for now since there are no options.

**Oct 20, 2019**

- Setting up a foundation for custoimizing the menu label.

**Oct 17, 2019**

- Code cleanup. Trying to be better aligned with WordPress standards.
- Checked to see if we have a topic ID instead of assuming we do.
- Move the edit button.
- Some styles

**Oct 15, 2019**

- Reorganizing files
- New page query to show hierarchical
- Code cleanup
- Removing private parts

**Oct 14, 2019**

- First Commit!
