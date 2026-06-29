<?php

return [
    'app_name' => 'Portal ARCA',
    'app_url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => 'America/Argentina/Buenos_Aires',
    'locale' => 'es_AR',
    
    // API ARCA Configuration
    'arca_api_url' => getenv('ARCA_API_URL') ?: 'https://api.arca.gob.ar',
    'arca_api_timeout' => 30,
    
    // File Upload Settings
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'allowed_certificate_types' => ['crt', 'cer', 'pem'],
    'allowed_key_types' => ['key', 'pem'],
    
    // Session Settings
    'session_lifetime' => 120, // minutes
    
    // Security
    'csrf_enabled' => true,
];
