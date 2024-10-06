<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WhatsAppServerAuth
{
    public function handle(Request $request, Closure $next)
    {
        $validToken = config('services.whatsapp.token');

        if ($request->header('X-WhatsApp-Server-Token') !== $validToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
