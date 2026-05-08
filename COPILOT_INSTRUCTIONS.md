# Copilot Instructions — WordPress Plugin Standards (help-docs)

Purpose

- Provide concise, enforceable rules that GitHub Copilot assistants should follow when suggesting edits or code for this repository.
- Ensure suggestions follow current WordPress coding standards, modern PHP practices, and repo-owner preferences.

Scope

- Applies to all code suggestions, diffs, and advice for this plugin. This plugin is admin-only (not public-facing); all guidance should prioritize admin security, performance, and accessibility.

Global constraints

- Do NOT create commits or open PRs without explicit permission from the repo owner. The owner will make and push commits.
- Keep changes minimal and focused. Provide a short rationale and a minimal code example or patch.
- Prefer WordPress APIs and canonical solutions over bespoke implementations.

Admin-only plugin constraints

- Do not add public-facing REST endpoints, shortcodes, or rewrite rules unless explicitly required and approved.
- When registering post types or routes, prefer `'public' => false`, `'publicly_queryable' => false`, `'exclude_from_search' => true`, and `'show_in_rest' => false`.
- Ensure any data exposed by admin screens is only accessible to users with the correct capabilities.

Security (must-follow)

- Validate input using the correct sanitizer for the data type: `absint()`, `sanitize_text_field()`, `sanitize_key()`, `wp_filter_nohtml_kses()`, `wp_kses_post()`, etc.
- Escape all output with the appropriate function based on context: `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_kses_post()` for safe HTML.
- Protect state-changing operations with nonces (`wp_nonce_field()` / `check_admin_referer()`) and verify capabilities (`current_user_can()`) early in handlers.
- Use parameterized queries (`$wpdb->prepare()`), or WP APIs (`get_posts()`, `get_post()`, `wp_insert_post()`), instead of raw SQL.
- For AJAX and REST callbacks, always add permission checks and nonce verification; for REST routes prefer a strict `permission_callback` that checks capabilities.
- Never expose internal file paths, credentials, or debug output to the admin UI or logs in production.

Performance & Scalability

- Avoid expensive queries during admin page loads. Use `WP_Query` carefully and set `no_found_rows => true` when pagination is not needed.
- Cache results with transients or object caching when appropriate and invalidate caches on updates.
- Defer heavy processing to background jobs (WP-Cron or a background processing library) instead of blocking page loads.
- Load assets only on admin pages that need them (use the `$hook_suffix` parameter in `admin_enqueue_scripts` or `get_current_screen()`).
- For large lists use pagination and `WP_List_Table` to prevent memory/timeout issues.

Modern PHP & Code Quality

- Follow WordPress recommended PHP minimums; for this project the minimum supported PHP version is **8.0 or greater**. Use modern language features where compatible (typed properties, union types, readonly properties, attributes, and return types) but ensure CI tests across all supported PHP versions.
- Prefer object-oriented design and dependency injection for testability. Avoid global state and excessive procedural helper functions.
- Use namespaces where appropriate, consistent with WordPress conventions for plugin code.
- Add comprehensive PHPDoc, use `@since` tags and return types where possible; consider `declare( strict_types=1 );` in internal modules where safe.
- Run static analysis (PHPStan/Psalm) at appropriate levels and include type-level checks in CI to catch issues early.

Accessibility (Admin UI)

- Ensure admin UI follows WordPress accessibility guidelines: proper headings (`h1`/`h2`), labels for form fields, table headers (`<th>`), and meaningful link text.
- Use `aria-*` attributes only when necessary and ensure keyboard focus management for dialogs and dynamic content.
- Use `esc_html__()` / `esc_attr__()` for translatable strings in attributes and ensure strings are understandable to screen readers.
- Add `screen-reader-text` where helpful for hidden labels or context.

Enqueuing Assets (Admin-only)

- Use `admin_enqueue_scripts` and enqueue assets using `plugins_url( 'path', __FILE__ )` or `plugin_dir_url( __FILE__ )`.
- Provide a `version` (plugin version constant or `filemtime()`) and set `in_footer` for scripts when appropriate.
- Only enqueue scripts/styles on the plugin's admin pages. Example:

```php
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( 'toplevel_page_help-docs' !== $hook ) {
        return;
    }
    wp_enqueue_style( 'help-docs-admin', plugin_dir_url( __FILE__ ) . 'style/style.css', [], PLUGIN_VERSION );
} );
```

Internationalization (i18n)

- Use translation functions with the plugin text-domain: `__( 'Text', 'help_docs' )`, `esc_html_e( 'Text', 'help_docs' )`.
- Add translator context comments (`/* translators: ... */`) when the meaning could be ambiguous.
- Load the text domain on `plugins_loaded`:

```php
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'help_docs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );
```

Coding standards & tooling

- Follow WordPress PHP Coding Standards (WPCS). Use PHPCS with the WordPress ruleset in CI and enforce on PRs.
- Use automated linting, unit tests (PHPUnit + WP test suite), and static analysis (PHPStan/Psalm) in CI.
- Provide a `phpcs.xml` and a GitHub Actions workflow that runs linting + tests (ask owner before adding workflows or files).

Testing & CI

- Add unit tests for core logic and integration tests for admin UI handlers where possible.
- Run tests across supported WordPress and PHP versions. Use GitHub Actions or other CI with a clear test matrix.

Compatibility

- Follow WordPress recommended minimums for platform requirements. This plugin **requires PHP 8.0 or greater**.
- Database: require **MySQL 8.0 or greater** OR **MariaDB 10.6 or greater**.
- HTTPS: this plugin is admin-only and **requires HTTPS support**; document that admin pages must be served over HTTPS and recommend enforcing HTTPS at the site/server level. Ensure code uses `is_ssl()` where relevant and sets secure cookie flags.
- Document minimum requirements in the plugin header (`Requires PHP: 8.0`) and `README.md` so site admins and CI know expected environments.
- Test against the latest two major WordPress releases and all supported PHP versions. When proposing breaking changes, clearly state required minimum WP/PHP/DB versions and provide migration steps.

Examples (do / don't)

- BAD (exposes data / unsafe):

```php
// Publicly accessible endpoint (bad for admin-only plugin)
add_action( 'rest_api_init', function() {
    register_rest_route( 'help-docs/v1', '/dump', [
        'methods'  => 'GET',
        'callback' => function() { return get_option( 'help_docs_internal' ); },
    ] );
} );
```

- GOOD (admin-only, secure):

```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Insufficient permissions', 'help_docs' ) );
}
$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
if ( $id && $post = get_post( $id ) ) {
    echo '<h1>' . esc_html( get_the_title( $id ) ) . '</h1>';
    echo wp_kses_post( apply_filters( 'the_content', $post->post_content ) );
}
```

Quick checklist for reviews

- [ ] Admin capability checked at the start of each admin handler.
- [ ] Nonce verification for state-changing actions and forms.
- [ ] Inputs validated and sanitized; outputs escaped.
- [ ] Assets enqueued only where needed and versioned.
- [ ] No public REST endpoints or public routes added without explicit approval.
- [ ] Accessibility checks for admin screens (ARIA, labels, headings).
- [ ] Tests and PHPCS configured or proposed in PR.

Operational notes for Copilot

- When proposing edits: include a minimal patch or code block and a 1–2 sentence rationale focused on security, performance, or accessibility.
- Ask the repo owner before adding workflows or CI files, or increasing the minimum PHP/WP requirement.
- When a change affects security or data, highlight the impact and any migration or rollout notes.

Contact

- If uncertain about a change, ask the repo owner (human) for clarification rather than applying the change automatically.

---

File created and maintained by the repository owner to guide Copilot suggestions. Keep it current as WP/PHP standards evolve.
