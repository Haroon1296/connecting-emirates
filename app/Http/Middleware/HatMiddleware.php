<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HatMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->user()->user_type == 'hat'){
            return $next($request);
        } else {
            $getPathInfo = $request->getPathInfo();
            $explod = explode('/', str_replace("/api","", $getPathInfo));

            return response()->json([
                'status'  => 0,
                'message' => 'Only hat can ' . $explod[2] . ' ' . $explod[1]
            ], 400);
        }
    }
}
