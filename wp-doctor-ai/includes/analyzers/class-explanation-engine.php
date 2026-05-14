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
            'beginner' => 'summary_beginner',
            'developer' => 'summary_developer',
            'store_owner' => 'summary_store_owner',
            'agency' => 'summary_agency',
        );

        $issue_type = sanitize_key($issue['issue_type'] ?? 'issue');

        $result = array(
            'headline' => $base,
            'mode' => $mode,
            'language' => $language,
            'summary' => $this->translations->translate($templates[$mode], $language),
            'probable_cause' => $this->translations->translate('cause_' . $issue_type, $language, $issue['probable_cause'] ?? ''),
            'safe_fix' => $this->translations->translate('fix_' . $issue_type, $language, $issue['suggested_fix'] ?? ''),
            'impact' => $this->translations->translate('impact_' . $issue_type, $language, $issue['impact'] ?? ''),
            'credit_required' => $advanced && ! $this->ai->is_enabled(),
        );

        if ($advanced && $this->ai->is_enabled()) {
            $result['ai_summary'] = $this->ai->summarize_issue($issue, $mode, $language);
        }

        return apply_filters('wp_doctor_ai_explanation', $result, $issue, $mode, $language, $advanced);
    }
}
