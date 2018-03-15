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
        // Set the default headers for cors If you only want this for OPTION method put this in the if below
        if (@$_SERVER['HTTP_ORIGIN']) {
            header('Access-Control-Allow-Origin: '. $_SERVER['HTTP_ORIGIN']);
        } else {
            header('Access-Control-Allow-Origin: '. '*');
        }

        $headers = ['Access-Control-Allow-Headers' =>
            'Content-Type, X-Auth-Token, Origin, Accept, Authorization, X-Request, X-Requested-With, Cache-Control',
            'Access-Control-Allow-Credentials' => 'true',
            'X-Content-Type-Options' => 'nosniff'
        ];


        // Set the allowed methods for the specific uri if the request method is OPTION
        if ($request->isMethod('options')) {
            return \Response::make('OK', 200, $headers);
            //$response->headers->set('Access-Control-Allow-Methods', $response->headers->get('Allow'));
        }

        $response = $next($request);
        foreach ($headers as $key => $value)
            $response->header($key, $value);

        return $response;
    }
}