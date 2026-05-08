<?php
/**
 * Premium admin dashboard template.
 *
 * @package WPDoctorAI
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap wpda-shell" id="wp-doctor-ai-app">
    <header class="wpda-hero">
        <div>
            <p class="wpda-eyebrow"><?php esc_html_e('WP Doctor AI', 'wp-doctor-ai'); ?></p>
            <h1><?php esc_html_e('Understand what’s breaking your website.', 'wp-doctor-ai'); ?></h1>
            <p><?php esc_html_e('Deterministic WordPress diagnostics with optional AI explanations. Raw scans are never charged Rescue Credits.', 'wp-doctor-ai'); ?></p>
        </div>
        <div class="wpda-credit-card">
            <span><?php esc_html_e('Rescue Credits', 'wp-doctor-ai'); ?></span>
            <strong data-wpda-credits><?php echo esc_html((string) $data['credits']); ?></strong>
            <small><?php echo esc_html(sprintf(__('%d free scans remaining', 'wp-doctor-ai'), $data['free_scans'])); ?></small>
        </div>
    </header>

    <section class="wpda-grid wpda-metrics">
        <article class="wpda-card"><span><?php esc_html_e('Total Issues', 'wp-doctor-ai'); ?></span><strong data-wpda-total><?php echo esc_html((string) count($data['issues'])); ?></strong></article>
        <article class="wpda-card danger"><span><?php esc_html_e('Critical', 'wp-doctor-ai'); ?></span><strong data-wpda-critical><?php echo esc_html((string) count(array_filter($data['issues'], static function ($issue) { return 'critical' === $issue['severity']; }))); ?></strong></article>
        <article class="wpda-card warning"><span><?php esc_html_e('AJAX Failures', 'wp-doctor-ai'); ?></span><strong data-wpda-ajax><?php echo esc_html((string) count(array_filter($data['issues'], static function ($issue) { return 'ajax_failure' === $issue['issue_type']; }))); ?></strong></article>
        <article class="wpda-card violet"><span><?php esc_html_e('Elementor Crashes', 'wp-doctor-ai'); ?></span><strong data-wpda-elementor><?php echo esc_html((string) count(array_filter($data['issues'], static function ($issue) { return 'elementor_crash' === $issue['issue_type']; }))); ?></strong></article>
    </section>

    <main class="wpda-layout">
        <section class="wpda-panel">
            <div class="wpda-panel-head">
                <div><h2><?php esc_html_e('Issue Explorer', 'wp-doctor-ai'); ?></h2><p><?php esc_html_e('Filter deterministic issue objects by severity, plugin, type, or affected page.', 'wp-doctor-ai'); ?></p></div>
                <button class="button button-primary wpda-run-scan"><?php esc_html_e('Run Browser Scan', 'wp-doctor-ai'); ?></button>
            </div>
            <div class="wpda-filters">
                <select data-filter="severity"><option value=""><?php esc_html_e('All severities', 'wp-doctor-ai'); ?></option><option>critical</option><option>high</option><option>medium</option><option>low</option></select>
                <select data-filter="issue_type"><option value=""><?php esc_html_e('All issue types', 'wp-doctor-ai'); ?></option><option value="console_error">console_error</option><option value="jquery_conflict">jquery_conflict</option><option value="duplicate_script">duplicate_script</option><option value="ajax_failure">ajax_failure</option><option value="elementor_crash">elementor_crash</option></select>
                <input type="search" data-filter="affected_plugin" placeholder="<?php esc_attr_e('Plugin slug', 'wp-doctor-ai'); ?>" />
            </div>
            <div class="wpda-issues" data-wpda-issues>
                <?php foreach ($data['issues'] as $issue) : ?>
                    <article class="wpda-issue" data-severity="<?php echo esc_attr($issue['severity']); ?>" data-type="<?php echo esc_attr($issue['issue_type']); ?>" data-plugin="<?php echo esc_attr($issue['affected_plugin']); ?>">
                        <button class="wpda-issue-toggle" type="button">
                            <span class="wpda-badge <?php echo esc_attr($issue['severity']); ?>"><?php echo esc_html($issue['severity']); ?></span>
                            <strong><?php echo esc_html($issue['issue_type']); ?></strong>
                            <em><?php echo esc_html($issue['confidence']); ?>%</em>
                        </button>
                        <div class="wpda-issue-body">
                            <p><?php echo esc_html($issue['probable_cause']); ?></p>
                            <p><strong><?php esc_html_e('Safe next step:', 'wp-doctor-ai'); ?></strong> <?php echo esc_html($issue['suggested_fix']); ?></p>
                            <pre><?php echo esc_html($issue['technical_details']); ?></pre>
                            <div class="wpda-explain-actions" data-issue="<?php echo esc_attr(wp_json_encode($issue)); ?>">
                                <button class="button" data-mode="beginner"><?php esc_html_e('Beginner', 'wp-doctor-ai'); ?></button>
                                <button class="button" data-mode="developer"><?php esc_html_e('Developer', 'wp-doctor-ai'); ?></button>
                                <button class="button" data-mode="store_owner"><?php esc_html_e('Store Owner', 'wp-doctor-ai'); ?></button>
                                <button class="button" data-mode="agency"><?php esc_html_e('Agency', 'wp-doctor-ai'); ?></button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="wpda-panel wpda-side">
            <h2><?php esc_html_e('Rescue Credits', 'wp-doctor-ai'); ?></h2>
            <p><?php esc_html_e('Use credits only for advanced AI explanations, translations, summaries, printable reports, and root-cause prioritization.', 'wp-doctor-ai'); ?></p>
            <div class="wpda-rescue-box"><?php esc_html_e('Optional advanced explanations can use Rescue Credits when AI features are enabled. Basic diagnostics remain available without credits.', 'wp-doctor-ai'); ?></div>
            <h3><?php esc_html_e('Exports', 'wp-doctor-ai'); ?></h3>
            <p><a class="button" href="<?php echo esc_url($data['scans'] ? admin_url('admin-post.php?action=wp_doctor_ai_export&format=txt&_wpnonce=' . wp_create_nonce('wp_doctor_ai_export')) : '#'); ?>">TXT</a> <a class="button" href="<?php echo esc_url(admin_url('admin-post.php?action=wp_doctor_ai_export&format=json&_wpnonce=' . wp_create_nonce('wp_doctor_ai_export'))); ?>">JSON</a> <a class="button" href="<?php echo esc_url(admin_url('admin-post.php?action=wp_doctor_ai_export&format=pdf&_wpnonce=' . wp_create_nonce('wp_doctor_ai_export'))); ?>"><?php esc_html_e('Printable HTML', 'wp-doctor-ai'); ?></a></p>
            <h3><?php esc_html_e('Safe Mode', 'wp-doctor-ai'); ?></h3>
            <p><?php esc_html_e('Prepared for isolated, temporary script-disabling tests with rollback. No permanent destructive actions are performed in v1.', 'wp-doctor-ai'); ?></p>
        </aside>
    </main>
</div>
