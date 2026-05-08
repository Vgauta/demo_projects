# WP Doctor AI WordPress.org Submission Checklist

## Package structure

- [ ] ZIP contains a single top-level `wp-doctor-ai/` directory.
- [ ] Main plugin file is `wp-doctor-ai/wp-doctor-ai.php`.
- [ ] `readme.txt` is present in the plugin root and validates with the official WordPress.org readme validator.
- [ ] `uninstall.php` is present in the plugin root.
- [ ] Development-only files are excluded from the release ZIP when appropriate.

## WordPress.org assets

Recommended WordPress.org SVN assets are documented in `assets/wordpress-org/README.md`:

- [ ] `banner-772x250.png`.
- [ ] `banner-1544x500.png`.
- [ ] `icon-128x128.png`.
- [ ] `icon-256x256.png`.
- [ ] Screenshots matching the readme screenshot list.

## Screenshots to capture

1. Dashboard overview.
2. Issue Explorer.
3. Detailed issue explanation.
4. Explain Like feature.
5. Rescue Credits modal or panel.
6. Safe mode testing information.

## Compatibility

- [ ] Requires at least: WordPress 6.0.
- [ ] Tested up to: WordPress 6.9.
- [ ] Requires PHP: 7.4.
- [ ] Tested on a clean WordPress install.
- [ ] Tested with common admin environments and no JavaScript console regressions.

## Translation readiness

- [ ] Plugin text domain is `wp-doctor-ai`.
- [ ] User-facing PHP strings use WordPress internationalization functions.
- [ ] JavaScript strings are passed through localized data or prepared for script translations.
- [ ] POT generation has been tested before release.

## Security review

- [ ] Direct file access guards exist in PHP files.
- [ ] REST routes require `manage_options`.
- [ ] Admin actions use nonces and capability checks.
- [ ] Input is sanitized with WordPress functions.
- [ ] Output is escaped for its context.
- [ ] SQL writes use `$wpdb` helpers or prepared statements for dynamic queries.
- [ ] No unsafe remote calls are made by default.
- [ ] External AI integrations are opt-in and clearly disclosed before use.

## Privacy and GDPR

- [ ] No telemetry is enabled by default.
- [ ] Diagnostic data is stored locally by default.
- [ ] Any future external provider integration has clear disclosure and consent.
- [ ] Uninstall behavior preserves user diagnostic history unless explicit deletion is enabled.

## Freemium compliance

- [ ] Free scans provide real diagnostic value.
- [ ] No spammy admin notices.
- [ ] No fake urgency or deceptive upgrade prompts.
- [ ] No dashboard hijacking.
- [ ] Paid/credit features are presented as optional advanced features.

## Performance review

- [ ] Collector loads only for administrators who can manage options.
- [ ] Collector does not continuously scan aggressively.
- [ ] Admin assets load only on the plugin dashboard.
- [ ] Database queries are limited and indexed.

## Plugin slug suggestions

Preferred slug: `wp-doctor-ai`.

Alternative slugs if unavailable:

- `doctor-ai-diagnostics`.
- `wp-doctor-diagnostics`.
- `website-doctor-ai`.
