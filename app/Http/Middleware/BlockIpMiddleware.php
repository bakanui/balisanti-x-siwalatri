<?php
  
namespace App\Http\Middleware;
  
use Closure;
use Illuminate\Http\Request;
  
class BlockIpMiddleware
{
    public $blockIps = ['114.125.95.183'];
  
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->ip(), $this->blockIps)) {
            abort(403, "Your IP address is blocked.");
        }
  
        return $next($request);
    }
}