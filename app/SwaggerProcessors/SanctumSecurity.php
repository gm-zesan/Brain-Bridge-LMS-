<?php

namespace App\SwaggerProcessors;

use OpenApi\Analysis;
use OpenApi\Annotations\PathItem;
use Illuminate\Support\Facades\Route;

/**
 * Processor to add bearerAuth security requirement to operations
 * for routes that use the 'auth:sanctum' middleware.
 *
 * This processor runs during swagger-php analysis and attempts to
 * match PathItem paths + HTTP methods to Laravel routes. When a
 * matching route contains 'auth:sanctum' it appends the security
 * requirement to the operation.
 *
 * Note: matching is best-effort (tries with and without '/api' prefix
 * and normalises route parameters). If you use custom prefixes or
 * host-based routing, you may need to extend the matching logic.
 */
class SanctumSecurity
{
    public function __invoke(Analysis $analysis)
    {
        // Build a lightweight representation of Laravel routes
        $routeList = [];
        try {
            $routes = Route::getRoutes();
            foreach ($routes as $route) {
                // uri() returns route URI without leading slash, e.g. 'me' or 'transactions/{transaction}'
                $uri = '/'.trim($route->uri(), '/');
                $methods = $route->methods();
                // gatherMiddleware is available and returns middleware for the route
                $middlewares = [];
                if (method_exists($route, 'gatherMiddleware')) {
                    $middlewares = $route->gatherMiddleware();
                }

                $routeList[] = [
                    'uri' => $uri,
                    'methods' => $methods,
                    'middlewares' => $middlewares,
                ];
            }
        } catch (\Throwable $e) {
            // If route collection isn't available for some reason, bail silently.
            return;
        }

        // Iterate path items from the generated OpenAPI analysis
        $pathItems = $analysis->getAnnotationsOfType(PathItem::class);
        $httpMethods = ['get', 'post', 'put', 'delete', 'patch', 'options', 'head'];

        foreach ($pathItems as $pathItem) {
            $path = $pathItem->path;
            if (!$path) {
                continue;
            }

            foreach ($httpMethods as $method) {
                if (empty($pathItem->$method)) {
                    continue;
                }

                $operation = $pathItem->$method; // an Operation annotation
                $opMethod = strtoupper($method);

                // Try to find a matching Laravel route
                foreach ($routeList as $r) {
                    // Try both with and without the common 'api' prefix
                    $variants = [$r['uri'], '/api'.ltrim($r['uri'], '/')];
                    foreach ($variants as $variant) {
                        // Normalise parameter placeholders by replacing {name} with {}
                        $patternSwagger = preg_replace('/\{[^}]+\}/', '{}', $path);
                        $patternRoute = preg_replace('/\{[^}]+\}/', '{}', $variant);

                        if ($patternSwagger === $patternRoute && in_array($opMethod, $r['methods'])) {
                            // If the route has auth:sanctum, add security requirement
                            if (in_array('auth:sanctum', $r['middlewares'], true)) {
                                // Try to set the operation security to bearerAuth.
                                // Some Operation annotation implementations may not accept direct assignment.
                                try {
                                    if (is_object($operation)) {
                                        $operation->security = [ [ 'bearerAuth' => [] ] ];
                                    }
                                } catch (\Throwable $e) {
                                    // If we cannot set security directly, skip silently.
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
