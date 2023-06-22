<?php

namespace App\Http\Middleware;

use App\Http\Middleware\ParseMultipartFormData\ParseInputStream;
use Closure;

class ParseMultipartFormData
{
    public function handle($request, Closure $next)
    {
        if ($request->method() == 'POST' or $request->method() == 'GET') {
            return $next($request);
        }

        if (preg_match('/multipart\/form-data/', $request->headers->get('Content-Type')) or
            preg_match('/multipart\/form-data/', $request->headers->get('content-type'))
        ) {
            $params = array();
            new ParseInputStream($params);
            $request->request->add($params);
        }
        return $next($request);
    }
}
