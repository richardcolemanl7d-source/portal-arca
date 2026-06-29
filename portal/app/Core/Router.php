<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewareGroups = [];

    public function get(string $path, string|array $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, string|array $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, string|array $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, string|array $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function any(string $path, string|array $handler): self
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $path, $handler);
    }

    private function addRoute(array|string $methods, string $path, string|array $handler): self
    {
        $methods = is_array($methods) ? $methods : [$methods];
        
        foreach ($methods as $method) {
            $this->routes[] = [
                'method' => strtoupper($method),
                'path' => $this->normalizePath($path),
                'handler' => $handler,
                'middleware' => []
            ];
        }
        
        return $this;
    }

    public function middleware(array $middleware): self
    {
        $this->middlewareGroups = array_merge($this->middlewareGroups, $middleware);
        return $this;
    }

    public function group(array $attributes, callable $callback): void
    {
        $previousMiddleware = $this->middlewareGroups;
        
        if (isset($attributes['middleware'])) {
            $this->middlewareGroups = array_merge(
                $this->middlewareGroups, 
                (array) $attributes['middleware']
            );
        }
        
        $prefix = $attributes['prefix'] ?? '';
        
        $callback($this);
        
        // Update routes with prefix
        $routeCount = count($this->routes);
        for ($i = $routeCount - 1; $i >= 0; $i--) {
            if ($prefix && strpos($this->routes[$i]['path'], $prefix) === 0) {
                $this->routes[$i]['middleware'] = array_merge(
                    $this->middlewareGroups,
                    $this->routes[$i]['middleware']
                );
            }
        }
        
        $this->middlewareGroups = $previousMiddleware;
    }

    private function normalizePath(string $path): string
    {
        return '/' . trim($path, '/');
    }

    public function dispatch(Request $request): Response
    {
        $uri = $this->normalizePath($request->uri());
        $method = $request->method();
        
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $pattern = $this->convertToRegex($route['path']);
            
            if (preg_match($pattern, $uri, $matches)) {
                // Extract parameters
                $params = [];
                preg_match_all('/\{(\w+)\}/', $route['path'], $paramNames);
                
                foreach ($paramNames[1] as $index => $paramName) {
                    $params[$paramName] = $matches[$index + 1] ?? null;
                }
                
                // Execute middleware
                foreach ($route['middleware'] as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    $response = $middleware->handle($request);
                    
                    if ($response !== null) {
                        return $response;
                    }
                }
                
                // Execute handler
                return $this->executeHandler($route['handler'], $params);
            }
        }
        
        return Response::view('errors/404', [], 'error')->setStatusCode(404);
    }

    private function convertToRegex(string $path): string
    {
        // Convert {param} to regex capture group
        $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    private function executeHandler(string|array $handler, array $params): Response
    {
        if (is_string($handler)) {
            [$controllerClass, $method] = explode('@', $handler);
            
            if (!class_exists($controllerClass)) {
                throw new \RuntimeException("Controller {$controllerClass} not found");
            }
            
            $controller = new $controllerClass();
            
            if (!method_exists($controller, $method)) {
                throw new \RuntimeException("Method {$method} not found in {$controllerClass}");
            }
            
            return call_user_func_array([$controller, $method], $params);
        }
        
        return call_user_func_array($handler, $params);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
