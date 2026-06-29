<?php

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $body = '';

    public static function make(string $body = '', int $statusCode = 200, array $headers = []): self
    {
        return (new self())->setBody($body)->setStatusCode($statusCode)->setHeaders($headers);
    }

    public static function json(array $data, int $statusCode = 200): self
    {
        return (new self())
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($data))
            ->setStatusCode($statusCode);
    }

    public static function view(string $view, array $data = [], string $layout = 'main'): self
    {
        extract($data);
        
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';
        $layoutPath = __DIR__ . '/../Views/Layouts/' . $layout . '.php';
        
        ob_start();
        include $viewPath;
        $content = ob_get_clean();
        
        if (file_exists($layoutPath)) {
            ob_start();
            include $layoutPath;
            $fullContent = ob_get_clean();
        } else {
            $fullContent = $content;
        }
        
        return (new self())->setBody($fullContent)->setHeader('Content-Type', 'text/html');
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        return (new self())
            ->setStatusCode($statusCode)
            ->setHeader('Location', $url);
    }

    public static function download(string $filePath, string $fileName): self
    {
        if (!file_exists($filePath)) {
            return self::make('File not found', 404);
        }
        
        return (new self())
            ->setHeader('Content-Type', 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->setHeader('Content-Length', (string) filesize($filePath))
            ->setBody(file_get_contents($filePath));
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        
        echo $this->body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
