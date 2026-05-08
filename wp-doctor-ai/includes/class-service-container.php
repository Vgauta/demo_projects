<?php
/**
 * Service locator used to share modular domain services.
 *
 * @package WPDoctorAI
 */

namespace WPDoctorAI;

use WPDoctorAI\AI\AiManager;
use WPDoctorAI\Analyzers\ExplanationEngine;
use WPDoctorAI\Credits\CreditManager;
use WPDoctorAI\Database\Repository;
use WPDoctorAI\Detectors\DetectionEngine;
use WPDoctorAI\Licensing\LicenseManager;
use WPDoctorAI\Logs\Logger;
use WPDoctorAI\Scanners\ScannerEngine;
use WPDoctorAI\Translators\TranslationManager;

if (! defined('ABSPATH')) {
    exit;
}

final class ServiceContainer
{
    /** @var array<string,object> */
    private $services = array();

    public function register_defaults(): void
    {
        $this->services['repository'] = new Repository();
        $this->services['logger'] = new Logger($this->repository());
        $this->services['translations'] = new TranslationManager();
        $this->services['ai'] = new AiManager($this->credits(), $this->logger());
        $this->services['explanations'] = new ExplanationEngine($this->translations(), $this->ai());
        $this->services['detector'] = new DetectionEngine();
        $this->services['scanner'] = new ScannerEngine($this->detector(), $this->repository(), $this->logger());
        $this->services['licensing'] = new LicenseManager();
    }

    public function repository(): Repository
    {
        return $this->service('repository');
    }

    public function logger(): Logger
    {
        return $this->service('logger');
    }

    public function translations(): TranslationManager
    {
        return $this->service('translations');
    }

    public function explanations(): ExplanationEngine
    {
        return $this->service('explanations');
    }

    public function ai(): AiManager
    {
        return $this->service('ai');
    }

    public function credits(): CreditManager
    {
        if (! isset($this->services['credits'])) {
            $this->services['credits'] = new CreditManager($this->repository());
        }

        return $this->service('credits');
    }

    public function scanner(): ScannerEngine
    {
        return $this->service('scanner');
    }

    public function licensing(): LicenseManager
    {
        return $this->service('licensing');
    }

    /**
     * @template T of object
     * @param string $id Service ID.
     * @return T
     */
    private function service(string $id)
    {
        return $this->services[$id];
    }
}
