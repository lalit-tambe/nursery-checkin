<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function __construct(private TenantContext $tenant)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('X-Tenant-ID');

        if ($header === null || ! ctype_digit($header) || (int) $header < 1) {
            return response()->json(
                ['message' => 'A valid X-Tenant-ID header is required.'],
                400
            );
        }

        $this->tenant->set((int) $header);

        return $next($request);
    }
}