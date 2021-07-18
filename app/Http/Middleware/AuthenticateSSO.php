<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Models\User;

class AuthenticateSSO
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = session('jwt_token');
        if (!$token) {
            return response(view('need_login'));
        }

        $client = new Client();
        try {
            $response = $client->request(
                'GET', 
                config('identity_provider.validation_url'), 
                [
                    'headers' => [
                        'Authorization' => "Bearer $token",
                        'Accept'        => 'application/json'
                    ],
                ],
            );
            if ($response->getStatusCode() == 200) {
                $responseContent = json_decode($response->getBody()->getContents(), true);
                if (!($responseContent['email'] ?? false) || !($responseContent['name'] ?? false)) {
                    abort(500, 'identity provider doesn\'t provide valid data');
                }
                $user = User::updateOrCreate(['email' => $responseContent['email']], ['name' => $responseContent['name']]);
            } else {
                abort(401);
            }
        } catch (\Exception $e) {
            \Session::forget('jwt_token');
            return redirect(route('login'));
        }

        $request->merge(['user' => $user ]);

        //add this
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        return $next($request);
    }
}
