<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // CORS Headers
        $headers = [
            'Access-Control-Allow-Origin'      => '*',  // Change * to your frontend domain if needed
            'Access-Control-Allow-Methods'     => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With',
            'Access-Control-Allow-Credentials' => 'true'
        ];

        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            return response()->json('OK', 200, $headers);
        }

        // Proceed with the request
        $response = $next($request);
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
