<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuditLogger middleware
 *
 * Logs all state-changing HTTP requests (POST, PUT, PATCH, DELETE) to the audit_logs collection.
 * Registered in the api middleware group for mutating routes.
 */
class AuditLogger
{
    public function __construct(private readonly AuditService $auditService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log mutations on successful responses
        if (
            in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])
            && $response->getStatusCode() < 400
            && $request->user()
        ) {
            $this->auditService->log(
                actor:      $request->user(),
                action:     strtolower($request->method()),
                entityType: $this->resolveEntityType($request),
                entityId:   $request->route('id') ?? null,
                changes:    [], // detailed diffs handled at the service layer
            );
        }

        return $response;
    }

    /**
     * Derive a human-readable entity type from the route path.
     * e.g. /api/v1/documents/xyz → "Document"
     */
    private function resolveEntityType(Request $request): string
    {
        $segments = array_values(array_filter(explode('/', $request->path())));
        // Find the last non-{id} path segment
        foreach (array_reverse($segments) as $segment) {
            if (!preg_match('/^[0-9a-f]{24}$/i', $segment)) {
                return ucfirst(rtrim($segment, 's')); // naive singularization
            }
        }
        return 'Unknown';
    }
}
