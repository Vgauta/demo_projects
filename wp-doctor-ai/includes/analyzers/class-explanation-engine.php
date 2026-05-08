<?php
/**
 * Converts structured issues into audience-specific explanations.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI\Analyzers;

use WPDoctorAI\AI\AiManager;
use WPDoctorAI\Translators\TranslationManager;

if (! defined('ABSPATH')) {
    exit;
}

final class ExplanationEngine
{
    private $translations;
    private $ai;

    public function __construct(TranslationManager $translations, AiManager $ai)
    {
        $this->translations = $translations;
        $this->ai = $ai;
    }

    public function explain(array $issue, string $mode = 'beginner', string $language = 'en', bool $advanced = false): array
    {
        $mode = in_array($mode, array('beginner', 'developer', 'store_owner', 'agency'), true) ? $mode : 'beginner';
        $base = $this->translations->translate($issue['translation_key'] ?? 'issue_detected', $language);
        $templates = array(
            'beginner' => __('Something on this page is loading incorrectly. You do not need to edit code first; start by checking the affected plugin and testing safely.', 'wp-doctor-ai'),
            'developer' => __('Review the structured technical details, stack trace, enqueue source, and network status before changing code.', 'wp-doctor-ai'),
            'store_owner' => __('This may affect customer actions such as clicking buttons, submitting forms, or completing checkout.', 'wp-doctor-ai'),
            'agency' => __('Prioritize a staging reproduction, document the affected component, then hand the client a low-risk remediation plan.', 'wp-doctor-ai'),
        );

        $result = array(
            'headline' => $base,
            'mode' => $mode,
            'language' => $language,
            'summary' => $templates[$mode],
            'probable_cause' => $issue['probable_cause'] ?? '',
            'safe_fix' => $issue['suggested_fix'] ?? '',
            'impact' => $issue['impact'] ?? '',
            'credit_required' => $advanced && ! $this->ai->is_enabled(),
        );

        if ($advanced && $this->ai->is_enabled()) {
            $result['ai_summary'] = $this->ai->summarize_issue($issue, $mode, $language);
        }

        return apply_filters('wp_doctor_ai_explanation', $result, $issue, $mode, $language, $advanced);
    }
}
