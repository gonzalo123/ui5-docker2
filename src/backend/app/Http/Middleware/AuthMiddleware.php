<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthMiddleware
{
    public const NAME = 'auth';

    public function handle(Request $request, Closure $next)
    {
        $user = $request->getUser();
        $pass = $request->getPassword();

        if (!$this->validateDestinationCredentials($user, $pass)) {
            $headers = ['WWW-Authenticate' => 'Basic'];

            return response('Backend Login', 401, $headers);
        }

        $authorizationHeader = $request->header('Authorization2');
        if (!$this->validateApplicationToken($authorizationHeader)) {
            return response('Invalid token ', 403);
        }

        return $next($request);

    }

    private function validateApplicationToken($authorizationHeader)
    {
        $token = str_replace('Bearer ', null, $authorizationHeader);

        return $token === getenv('APP_TOKEN');
    }

    private function validateDestinationCredentials($user, $pass)
    {
        if (!($user === getenv('DESTINATION_USER') && $pass === getenv('DESTINATION_PASS'))) {
            return false;
        }

        return true;
    }
}