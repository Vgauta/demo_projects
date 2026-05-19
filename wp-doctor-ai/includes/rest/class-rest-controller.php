<?php
/**
 * Secure REST API.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WPDoctorAI\ServiceContainer;

if (! defined('ABSPATH')) {
    exit;
}

final class RestController
{
    private $container;

    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    public function hooks(): void
    {
        add_action('rest_api_init', array($this, 'routes'));
    }

    public function routes(): void
    {
        register_rest_route(WP_DOCTOR_AI_REST_NAMESPACE, '/scan', array(
            'methods' => 'POST',
            'callback' => array($this, 'scan'),
            'permission_callback' => array($this, 'can_manage'),
        ));
        register_rest_route(WP_DOCTOR_AI_REST_NAMESPACE, '/issues', array(
            'methods' => 'GET',
            'callback' => array($this, 'issues'),
            'permission_callback' => array($this, 'can_manage'),
            'args' => array(
                'severity' => array(
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => array($this, 'validate_severity'),
                ),
                'issue_type' => array(
                    'sanitize_callback' => 'sanitize_key',
                ),
                'affected_plugin' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        register_rest_route(WP_DOCTOR_AI_REST_NAMESPACE, '/explain', array(
            'methods' => 'POST',
            'callback' => array($this, 'explain'),
            'permission_callback' => array($this, 'can_manage'),
            'args' => array(
                'mode' => array(
                    'sanitize_callback' => 'sanitize_key',
                ),
                'language' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'advanced' => array(
                    'sanitize_callback' => 'rest_sanitize_boolean',
                ),
            ),
        ));
        register_rest_route(WP_DOCTOR_AI_REST_NAMESPACE, '/solution', array(
            'methods' => 'POST',
            'callback' => array($this, 'solution'),
            'permission_callback' => array($this, 'can_manage'),
            'args' => array(
                'language' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        register_rest_route(WP_DOCTOR_AI_REST_NAMESPACE, '/fixes', array(
            'methods' => 'GET',
            'callback' => array($this, 'fixes'),
            'permission_callback' => array($this, 'can_manage'),
        ));
        register_rest_route(WP_DOCTOR_AI_REST_NAMESPACE, '/fixes/rollback', array(
            'methods' => 'POST',
            'callback' => array($this, 'rollback_fix'),
            'permission_callback' => array($this, 'can_manage'),
            'args' => array(
                'fix_id' => array(
                    'sanitize_callback' => 'sanitize_key',
                ),
            ),
        ));
        register_rest_route(WP_DOCTOR_AI_REST_NAMESPACE, '/credits/adjust', array(
            'methods' => 'POST',
            'callback' => array($this, 'adjust_credits'),
            'permission_callback' => array($this, 'can_manage'),
            'args' => array(
                'amount' => array(
                    'sanitize_callback' => array($this, 'sanitize_integer'),
                ),
                'notes' => array(
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
            ),
        ));
    }

    public function can_manage(): bool
    {
        return current_user_can('manage_options');
    }

    public function validate_severity($value): bool
    {
        return empty($value) || in_array($value, array('low', 'medium', 'high', 'critical'), true);
    }

    public function sanitize_integer($value): int
    {
        return (int) $value;
    }

    public function scan(WP_REST_Request $request): WP_REST_Response
    {
        $is_manual = (bool) $request->get_param('manual');
        $is_premium = $this->container->licensing()->is_premium();

        if ($is_manual && ! $is_premium && $this->container->credits()->free_scans_remaining() <= 0) {
            return rest_ensure_response(array(
                'error' => 'free_scan_limit_reached',
                'message' => __('Free scans are finished. Upgrade to premium for unlimited scans.', 'wp-doctor-ai'),
                'checkout_url' => $this->container->licensing()->checkout_url(),
            ));
        }

        if ($is_manual && ! $is_premium) {
            $this->container->credits()->use_free_scan();
        }

        $result = $this->container->scanner()->run((array) $request->get_json_params(), $is_manual ? 'manual' : 'browser_collector');
        $result['free_scans_remaining'] = $is_premium ? 'unlimited' : $this->container->credits()->free_scans_remaining();
        $result['unlimited_scans'] = $is_premium;
        return rest_ensure_response($result);
    }

    public function issues(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response(array(
            'issues' => $this->container->repository()->issues($request->get_params()),
            'scans' => $this->container->repository()->recent_scans(10),
            'credits' => $this->container->credits()->balance(),
            'free_scans_remaining' => $this->container->licensing()->is_premium() ? 'unlimited' : $this->container->credits()->free_scans_remaining(),
            'unlimited_scans' => $this->container->licensing()->is_premium(),
            'credit_logs' => $this->container->repository()->credit_logs(20),
        ));
    }

    public function explain(WP_REST_Request $request): WP_REST_Response
    {
        $params = (array) $request->get_json_params();
        $advanced = ! empty($params['advanced']) || $this->container->licensing()->is_premium();
        $explanation = $this->container->explanations()->explain(
            (array) ($params['issue'] ?? array()),
            sanitize_key($params['mode'] ?? 'beginner'),
            sanitize_text_field((string) ($params['language'] ?? 'en')),
            $advanced
        );
        return rest_ensure_response($explanation);
    }


    public function solution(WP_REST_Request $request): WP_REST_Response
    {
        $params = (array) $request->get_json_params();
        $language = sanitize_text_field((string) ($params['language'] ?? 'en'));
        $issue = (array) ($params['issue'] ?? array());
        $translations = $this->container->translations();
        $licensing = $this->container->licensing();

        if (! $licensing->feature_enabled('one_click_solution')) {
            $checkout_url = $licensing->checkout_url();

            return rest_ensure_response(array(
                'premium_required' => true,
                'checkout_url' => $checkout_url,
                'message' => $translations->translate('premium_solution_required', $language),
                'payment_note' => $checkout_url ? '' : $translations->translate('premium_checkout_not_configured', $language),
            ));
        }

        $plan = $this->container->ai()->solution_plan($issue, $language);

        if (! empty($plan['needs_api_key'])) {
            return rest_ensure_response(array(
                'premium_required' => false,
                'needs_api_key' => true,
                'message' => $plan['message'],
                'safe_steps' => array(),
                'applied_fix' => array(
                    'applied' => false,
                    'reload_required' => false,
                    'message' => __('No automatic fix was applied because the AI API key is required first.', 'wp-doctor-ai'),
                ),
            ));
        }

        $safe_fix = $this->container->fixes()->apply_safe_fix($issue);

        if (! empty($safe_fix['applied'])) {
            return rest_ensure_response(array(
                'premium_required' => false,
                'needs_api_key' => false,
                'message' => $safe_fix['message'],
                'applied_fix' => $safe_fix,
                'safe_steps' => array(),
                'reload_url' => add_query_arg('wpda_autoscan', '1', wp_get_referer() ?: admin_url('admin.php?page=wp-doctor-ai')),
            ));
        }

        return rest_ensure_response(array(
            'premium_required' => false,
            'needs_api_key' => false,
            'message' => $plan['message'] ?: $translations->translate('solution_preview_ready', $language),
            'applied_fix' => $safe_fix,
            'safe_steps' => $plan['steps'] ?: array_values(array_filter(array(
                sanitize_text_field($issue['probable_cause'] ?? ''),
                sanitize_text_field($issue['suggested_fix'] ?? ''),
                $translations->translate('solution_safe_mode_step', $language),
            ))),
        ));
    }

    public function fixes(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response(array(
            'active_fixes' => $this->container->fixes()->active_fixes(),
            'debug_logs' => get_option('wp_doctor_ai_debug_logs', array()),
        ));
    }

    public function rollback_fix(WP_REST_Request $request): WP_REST_Response
    {
        $params = (array) $request->get_json_params();
        $fix_id = sanitize_key((string) ($params['fix_id'] ?? $request->get_param('fix_id') ?? 'duplicate_script_dedupe'));
        return rest_ensure_response($this->container->fixes()->rollback_fix($fix_id));
    }

    public function adjust_credits(WP_REST_Request $request): WP_REST_Response
    {
        $amount = (int) $request->get_param('amount');
        $notes = sanitize_textarea_field((string) $request->get_param('notes'));
        return rest_ensure_response(array('balance' => $this->container->credits()->adjust($amount, $notes)));
    }
}
