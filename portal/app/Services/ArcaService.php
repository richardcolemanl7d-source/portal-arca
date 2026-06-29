<?php

namespace App\Services;

/**
 * Servicio para conectar con la API ARCA
 * 
 * Maneja las peticiones HTTP hacia los endpoints de ARCA
 * utilizando cURL nativo de PHP.
 */
class ArcaService
{
    private string $baseUrl;
    private string $token;
    private int $timeout;

    public function __construct()
    {
        // Obtención de variables de entorno
        $this->baseUrl = getenv('ARCA_BASE_URL') ?: 'https://api.arca.gob.ar/v1';
        $this->token = getenv('ARCA_SERVICE_TOKEN') ?: '';
        $this->timeout = (int)(getenv('ARCA_TIMEOUT') ?: 30);

        if (empty($this->token)) {
            throw new \Exception("Token de API ARCA no configurado. Verifica la variable ARCA_SERVICE_TOKEN");
        }
    }

    /**
     * Construye los headers por defecto para las peticiones
     * 
     * @return array
     */
    private function buildHeaders(): array
    {
        return [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->token,
            'User-Agent: Portal-ARCA/1.0'
        ];
    }

    /**
     * Ejecuta una petición cURL genérica
     * 
     * @param string $method Método HTTP (GET, POST, PUT, DELETE)
     * @param string $endpoint Endpoint relativo (ej: /companies)
     * @param array|null $data Datos a enviar (para POST/PUT)
     * @param bool $isFileUpload Si es true, envía como multipart/form-data
     * @param string|null $filePath Ruta del archivo a subir
     * @return array Respuesta decodificada de JSON
     * @throws \Exception Si hay error en la petición
     */
    private function request(string $method, string $endpoint, ?array $data = null, bool $isFileUpload = false, ?string $filePath = null): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        
        $headers = $this->buildHeaders();
        
        if ($isFileUpload && $filePath !== null) {
            // Remover Content-Type automático para que cURL lo ponga con boundary
            $headers = array_filter($headers, function($h) {
                return strpos($h, 'Content-Type: application/json') === false;
            });
            
            if (!file_exists($filePath)) {
                throw new \Exception("Archivo no encontrado: {$filePath}");
            }
            
            $postData = [
                'file' => new \CURLFile($filePath, mime_content_type($filePath), basename($filePath))
            ];
            
            // Agregar datos adicionales si existen
            if ($data !== null) {
                foreach ($data as $key => $value) {
                    $postData[$key] = $value;
                }
            }
            
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        } elseif ($data !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Para desarrollo local con certificados autofirmados (DESACTIVAR EN PRODUCCIÓN)
        if (getenv('ARCA_SKIP_SSL_VERIFY') === 'true') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        
        curl_close($ch);

        if ($errno !== 0) {
            throw new \Exception("Error cURL ({$errno}): {$error}");
        }

        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['message'] ?? $errorData['error'] ?? "Error HTTP {$httpCode}";
            throw new \Exception("Error API ARCA ({$httpCode}): {$errorMsg}");
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Petición GET
     * 
     * @param string $endpoint Endpoint relativo
     * @return array Respuesta de la API
     */
    public function get(string $endpoint): array
    {
        return $this->request('GET', $endpoint);
    }

    /**
     * Petición POST
     * 
     * @param string $endpoint Endpoint relativo
     * @param array $data Datos a enviar
     * @return array Respuesta de la API
     */
    public function post(string $endpoint, array $data): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Petición PUT
     * 
     * @param string $endpoint Endpoint relativo
     * @param array $data Datos a enviar
     * @return array Respuesta de la API
     */
    public function put(string $endpoint, array $data): array
    {
        return $this->request('PUT', $endpoint, $data);
    }

    /**
     * Petición DELETE
     * 
     * @param string $endpoint Endpoint relativo
     * @return array Respuesta de la API
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Subida de archivos (Certificados, Keys, etc.)
     * 
     * @param string $endpoint Endpoint relativo
     * @param string $filePath Ruta completa del archivo local
     * @param array $additionalData Datos adicionales para enviar
     * @return array Respuesta de la API
     */
    public function upload(string $endpoint, string $filePath, array $additionalData = []): array
    {
        return $this->request('POST', $endpoint, $additionalData, true, $filePath);
    }
}
