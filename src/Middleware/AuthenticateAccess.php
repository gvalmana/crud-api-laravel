<?php

namespace App\Http\Middleware;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {   
        try {
            $client = new Client();
            $headers['Authorization'] = $request->header('Authorization');
            $headers['Content-Type'] = 'application/json';
            $headers['Accept'] = 'application/json';
            $response = $client->get(config('oauth.server').'/api/user',['headers'=>$headers]);
            
            $data = json_decode($response->getBody()->getContents());
            if ($response->getStatusCode() == 200 && $data->success=true) {
                $user = new GenericUser(array($data));
                Auth::setUser($user);
                $request->setUserResolver(function () use ($data) {
                    return $data;
                });
                return $next($request);
            }
        } catch (\Throwable $exception) {
            if ($exception instanceof ClientException) {
                return response([
                    'success'=>false,
                    'error'=>'Error de autenticación en este servicio.'], 
                Response::HTTP_UNAUTHORIZED);
            }
            throw $exception;
        }
        return abort(Response::HTTP_UNAUTHORIZED, 'Error de autenticación en este servicio.');
    }
}
