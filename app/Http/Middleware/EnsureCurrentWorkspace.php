<?php

namespace App\Http\Middleware;

use App\Services\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentWorkspace
{
    public function __construct(private readonly CurrentWorkspace $currentWorkspace) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        if ($this->currentWorkspace->forUser() === null) {
            return redirect()->route('workspaces.index');
        }

        return $next($request);
    }
}
