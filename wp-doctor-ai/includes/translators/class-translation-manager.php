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
        $language = $this->normalize_language($language);
        $maps = apply_filters('wp_doctor_ai_translation_maps', $this->maps);
        return ($maps[$language][$key] ?? $maps['en'][$key] ?? $fallback) ?: $key;
    }

    public function normalize_language(string $language): string
    {
        $language = strtolower(trim($language));
        $language = str_replace(array('_', ' '), '-', $language);
        $aliases = array(
            'english' => 'en',
            'en-us' => 'en',
            'en-gb' => 'en',
            'hindi' => 'hi',
            'हिंदी' => 'hi',
            'spanish' => 'es',
            'espanol' => 'es',
            'español' => 'es',
            'german' => 'de',
            'deutsch' => 'de',
            'japanese' => 'ja',
            '日本語' => 'ja',
            'french' => 'fr',
            'francais' => 'fr',
            'français' => 'fr',
        );

        return sanitize_key($aliases[$language] ?? $language ?: 'en');
    }

    public function languages(): array
    {
        return array_keys($this->maps);
    }
}
