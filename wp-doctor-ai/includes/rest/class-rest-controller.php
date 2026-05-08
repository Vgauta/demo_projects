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
                    'sanitize_callback' => 'sanitize_key',
                ),
                'advanced' => array(
                    'sanitize_callback' => 'rest_sanitize_boolean',
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
        if ($is_manual && $this->container->credits()->free_scans_remaining() > 0) {
            $this->container->credits()->use_free_scan();
        }
        $result = $this->container->scanner()->run((array) $request->get_json_params(), $is_manual ? 'manual' : 'browser_collector');
        $result['free_scans_remaining'] = $this->container->credits()->free_scans_remaining();
        return rest_ensure_response($result);
    }

    public function issues(WP_REST_Request $request): WP_REST_Response
    {
        return rest_ensure_response(array(
            'issues' => $this->container->repository()->issues($request->get_params()),
            'scans' => $this->container->repository()->recent_scans(10),
            'credits' => $this->container->credits()->balance(),
            'free_scans_remaining' => $this->container->credits()->free_scans_remaining(),
            'credit_logs' => $this->container->repository()->credit_logs(20),
        ));
    }

    public function explain(WP_REST_Request $request): WP_REST_Response
    {
        $params = (array) $request->get_json_params();
        $advanced = ! empty($params['advanced']);
        $explanation = $this->container->explanations()->explain(
            (array) ($params['issue'] ?? array()),
            sanitize_key($params['mode'] ?? 'beginner'),
            sanitize_key($params['language'] ?? 'en'),
            $advanced
        );
        return rest_ensure_response($explanation);
    }

    public function adjust_credits(WP_REST_Request $request): WP_REST_Response
    {
        $amount = (int) $request->get_param('amount');
        $notes = sanitize_textarea_field((string) $request->get_param('notes'));
        return rest_ensure_response(array('balance' => $this->container->credits()->adjust($amount, $notes)));
    }
}
