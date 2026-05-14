=== WP Doctor AI Pro ===
Contributors: starlineinfotech
Tags: diagnostics, ai, gemini, troubleshooting, wordpress
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Premium add-on for WP Doctor AI that unlocks unlimited scans and Google AI Studio / Gemini one-click solution plans with safe automatic duplicate-script mitigation and automatic rescan verification.

== Description ==

WP Doctor AI Pro is the premium add-on for the free WP Doctor AI diagnostics plugin. It does not replace the deterministic scanner. Instead, it unlocks premium workflows in the base plugin:

* Unlimited manual scans.
* Premium one-click AI solution plans with safe automatic duplicate-script mitigation and automatic rescan verification.
* Google AI Studio / Gemini API key support.
* Custom AI language requests for languages supported by the AI provider.
* Premium checkout/account links for Starline Infotech.

AI explanations and solution plans use structured issue data already detected by WP Doctor AI. The AI response is a safe, review-first plan. It does not edit core files, disable plugins, modify themes, or perform destructive operations. For duplicate-script issues, Pro can safely enable a reversible dequeue mitigation on both frontend and admin screens that keeps the first matching canonical script URL and prevents later exact duplicates from printing after the next page load, then triggers a verification rescan.

== Installation ==

1. Install and activate the free `wp-doctor-ai` plugin.
2. Upload and activate `wp-doctor-ai-pro`.
3. Pro unlocks automatically after activation; no separate WP Doctor AI license key is needed in this build.
4. Open **WP Doctor AI** in the WordPress admin.
5. Add your Google AI Studio API key in **Premium AI setup** for AI one-click solution plans with safe automatic duplicate-script mitigation and automatic rescan verification.
6. Run a scan, open an issue, and click **One-click solution**.

== Frequently Asked Questions ==

= Does Pro work without the free plugin? =

No. WP Doctor AI Pro is an add-on. The free WP Doctor AI plugin must be installed and active.

= Does Pro include unlimited scans? =

Yes. When Pro is active, WP Doctor AI treats the site as a pro plan and manual scans are unlimited.

= Does Pro need a license key? =

No separate WP Doctor AI license key is needed in this build. Activating the Pro add-on unlocks the pro plan locally. A future licensing server can be added later if you want per-customer license validation.

= Does Pro need a Google AI Studio API key? =

Yes, premium AI one-click solution plans with safe automatic duplicate-script mitigation and automatic rescan verification require an administrator-provided Google AI Studio / Gemini API key. If the key is missing, WP Doctor AI asks the admin to add it first.

= Does AI really fix the website automatically? =

Pro can apply safe reversible mitigations where WP Doctor AI has a deterministic fix, such as duplicate-script de-duplication. It does not edit core files, disable plugins, modify themes, or apply risky changes.

== Changelog ==

= 1.0.0 =
* Initial Pro add-on with unlimited scans and Gemini AI solution plan unlocking.
