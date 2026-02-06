# Help Docs

Help Docs helps you in creating a help section in the WordPress admin.

Help Docs does this by creating a custom post type that is only visible on the backend. You then create content just like any WordPress post or page, publish it, and you're done.

You can create as many pages as you want, assign parents, and it'll all be displayed on the main Help Docs page.

Help Docs supports both the Classic Editor and Gutenberg Block Editor. You can enable Gutenberg support in the Settings page if you prefer using the block editor for your documentation.

If you enable Gutenberg, the help docs will appear in the API, but will not be visible on the front end of your site as we're blocking access to the custom post type.

At this time, the plugin doesn't auto update so check back periodically for updates.

## System Requirements

- **WordPress:** 6.0 or greater
- **PHP:** 7.4 or greater
- **Database:** MySQL 8.0 or greater OR MariaDB 10.6 or greater
- **HTTPS:** Required for admin pages (enforce at site/server level)

## Features

- Admin-only custom post type for internal documentation
- Hierarchical page structure with parent/child relationships
- Optional Gutenberg (Block Editor) support
- Customizable admin page heading (configured in Settings)
- Restricted REST API access (logged-in users only when Gutenberg enabled)
- Clean, simple interface optimized for documentation

### Release Notes

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
