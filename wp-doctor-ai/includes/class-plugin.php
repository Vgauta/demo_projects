<?php
/**
 * Main plugin container.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI;

use WPDoctorAI\Admin\Admin;
use WPDoctorAI\Database\Installer;
use WPDoctorAI\Rest\RestController;
use WPDoctorAI\Scanners\BrowserCollector;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Coordinates bootstrapping while keeping modules independently replaceable.
 */
final class Plugin
{
    /** @var self|null */
    private static $instance = null;

    /** @var ServiceContainer */
    private $container;

    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->container = new ServiceContainer();
    }

    public static function activate(): void
    {
        Installer::install();
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook('wp_doctor_ai_subscription_refill');
    }

    public function boot(): void
    {
        $this->container->register_defaults();

        (new Admin($this->container))->hooks();
        (new RestController($this->container))->hooks();
        (new BrowserCollector($this->container))->hooks();
        $this->container->fixes()->hooks();

        add_action('wp_doctor_ai_subscription_refill', array($this->container->credits(), 'run_subscription_refill'));
        if (! wp_next_scheduled('wp_doctor_ai_subscription_refill')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'wp_doctor_ai_subscription_refill');
        }
    }

    public function container(): ServiceContainer
    {
        return $this->container;
    }
}
