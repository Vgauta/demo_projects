<?php
/**
 * Translation maps for deterministic issue copy.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Translators;

if (! defined('ABSPATH')) {
    exit;
}

final class TranslationManager
{
    private $maps;

    public function __construct()
    {
        $this->maps = require WP_DOCTOR_AI_PATH . 'languages/explanations.php';
    }

    public function translate(string $key, string $language = 'en', string $fallback = ''): string
    {
        $language = sanitize_key($language);
        $maps = apply_filters('wp_doctor_ai_translation_maps', $this->maps);
        return ($maps[$language][$key] ?? $maps['en'][$key] ?? $fallback) ?: $key;
    }

    public function languages(): array
    {
        return array_keys($this->maps);
    }
}
