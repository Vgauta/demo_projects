<?php
/**
 * Privacy-friendly logger wrapper.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Logs;

use WPDoctorAI\Database\Repository;

if (! defined('ABSPATH')) {
    exit;
}

final class Logger
{
    private $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function info(string $message, array $context = array()): void
    {
        $this->write('info', $message, $context);
    }

    public function warning(string $message, array $context = array()): void
    {
        $this->write('warning', $message, $context);
    }

    private function write(string $level, string $message, array $context = array()): void
    {
        $level = sanitize_key($level);
        $message = sanitize_text_field($message);
        $context = $this->sanitize_context($context);

        do_action('wp_doctor_ai_log', $level, $message, $context);

        $settings = get_option('wp_doctor_ai_settings', array());
        $settings = is_array($settings) ? $settings : array();
        if (empty($settings['debug_mode'])) {
            return;
        }

        $logs = get_option('wp_doctor_ai_debug_logs', array());
        $logs = is_array($logs) ? $logs : array();
        $logs[] = array(
            'time' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        );
        update_option('wp_doctor_ai_debug_logs', array_slice($logs, -100), false);
    }

    private function sanitize_context(array $context): array
    {
        $clean = array();
        foreach ($context as $key => $value) {
            $key = sanitize_key((string) $key);
            if (is_array($value)) {
                $clean[$key] = $this->sanitize_context($value);
            } elseif (false !== strpos($key, 'url') || false !== strpos($key, 'src') || 'asset' === $key) {
                $clean[$key] = esc_url_raw((string) $value) ?: sanitize_text_field((string) $value);
            } else {
                $clean[$key] = sanitize_text_field((string) $value);
            }
        }
        return $clean;
    }
}
