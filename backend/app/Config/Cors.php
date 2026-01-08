<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Cross-Origin Resource Sharing (CORS) Configuration
 */
class Cors extends BaseConfig
{
    /**
     * The default CORS configuration.
     */
    public array $default = [
        'allowedOrigins'         => [], // We will set this in constructor
        'allowedOriginsPatterns' => [],
        'supportsCredentials'    => true,
        'allowedHeaders'         => [
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'Accept',
            'Accept-Language',
            'Origin',
        ],
        'exposedHeaders'         => [],
        'allowedMethods'         => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'maxAge'                 => 7200,
    ];

    public function __construct()
    {
        parent::__construct();

        // Dynamically set based on environment
        $originsEnv = env('CORS_ALLOWED_ORIGINS', 'http://localhost:8104,http://127.0.0.1:8104,http://localhost:3000');
        $this->default['allowedOrigins'] = array_map('trim', explode(',', $originsEnv));
    }
}
