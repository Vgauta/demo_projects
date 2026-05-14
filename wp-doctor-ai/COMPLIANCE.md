# WP Doctor AI Compliance Notes

This document summarizes the release rules used to prepare WP Doctor AI for WordPress.org review.

## Security and privacy

- REST API routes are restricted to administrators with `manage_options`.
- Admin export actions require a WordPress nonce and capability check.
- Input is sanitized before storage or filtering.
- Output is escaped in the admin template for the appropriate context.
- Diagnostic data is stored locally by default.
- AI features are disabled by default and must remain opt-in.
- The plugin does not make remote calls by default. Premium AI solution plans call Google Gemini only after an administrator saves a Google AI Studio API key.
- Telemetry is disabled by default.

## Freemium rules

- Raw scans must remain available without Rescue Credits.
- Rescue Credits are reserved for optional advanced explanations, summaries, translations, and reports. Premium AI one-click solution plans require a premium license and administrator-provided API key.
- Do not add fake urgency, deceptive lock screens, or upgrade prompts that interrupt normal WordPress administration.
- Free diagnostics must continue to provide severity, confidence, affected component details, and safe next steps.
- Pro functionality is delivered through a separate add-on that requires the base plugin and unlocks premium flags without deceptive lock screens.

## Admin notice rules

- Do not show global promotional banners.
- Do not show fake security warnings or fake urgency.
- Any future notice must be dismissible.
- Any future notice must be capability-checked.
- Any future dismiss action must use a nonce.
- Notices should be limited to setup, compatibility, or important operational information.

## Data removal policy

The default uninstall routine removes temporary runtime data and scheduled jobs while preserving diagnostic history, credit logs, and settings. Full data deletion is available only when the site owner explicitly enables `wp_doctor_ai_delete_data_on_uninstall` before uninstalling.
