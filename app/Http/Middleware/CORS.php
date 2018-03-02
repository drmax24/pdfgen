<?php namespace App\Http\Middleware;

use Closure;

class CORS
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /*
         * Get the response like normal.
         * When laravel cannot find the exact route it will try to find the same route for different methods
         * If the method is OPTION and there are other methods for the uri,
         * it will then return a 200 response with an Allow header
         *
         * Else it will throw an exception in which case the user is trying to do something it should not do.
         */
        $response = $next($request);

        // Set the default headers for cors If you only want this for OPTION method put this in the if below
        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            $response->headers->set('Access-Control-Allow-Origin', $_SERVER['HTTP_ORIGIN']);
        } else {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $response->headers->set('Access-Control-Allow-Headers',
            'content-type, X-Requested-With, X-Auth-Token, Origin, Accept, Authorization, X-Request');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

        // Set the allowed methods for the specific uri if the request method is OPTION
        //if ($request->isMethod('options')) {
        //    $response->headers->set('Access-Control-Allow-Methods', $response->headers->get('Allow'));
        //}

        return $response;
    }
}