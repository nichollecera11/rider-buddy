<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(auth()->check() && auth()->user()->isAdmin()){
            return $next($request);
        }
        if($request->user()->is_banned){
            return response()->json([
                'message' => 'Banned users cannot perform admin actions'
            ], 403);
        }
        return response()->json([
            'message' => 'Access Denied. Admin Privileges Required'
        ], 403);
        
    }
}
