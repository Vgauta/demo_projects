<?php
/**
 * Lightweight PSR-4 style autoloader for WP Doctor AI.
 *
 * @package WPDoctorAI
 */

if (! defined('ABSPATH')) {
    exit;
}

spl_autoload_register(static function ($class) {
    $prefix = 'WPDoctorAI\\';

    if (0 !== strpos($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $relative = str_replace('\\', '/', $relative);
    $parts = explode('/', $relative);
    $class_name = array_pop($parts);
    $class_file = 'class-' . strtolower(str_replace('_', '-', preg_replace('/(?<!^)[A-Z]/', '-$0', $class_name))) . '.php';
    $path = WP_DOCTOR_AI_PATH . 'includes/';

    if (! empty($parts)) {
        $path .= strtolower(implode('/', $parts)) . '/';
    }

    $file = $path . $class_file;

    if (is_readable($file)) {
        require_once $file;
    }
});
