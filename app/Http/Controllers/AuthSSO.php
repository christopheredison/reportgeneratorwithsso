<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Models\User;

class AuthSSO extends Controller
{
    /**
     * Login redirector page
     * @param  Request $request 
     * @return Response
     * @route  /login
     */
    public function login(Request $request)
    {
        if ($request->token) {
            $client = new Client();
            try {
                $response = $client->request(
                    'GET', 
                    config('identity_provider.validation_url'), 
                    [
                        'headers' => [
                            'Authorization' => "Bearer $request->token",
                            'Accept'        => 'application/json'
                        ],
                    ],
                );
                if ($response->getStatusCode() == 200) {
                    \Session::put('jwt_token', $request->token);
                    $responseContent = json_decode($response->getBody()->getContents(), true);
                    if (!($responseContent['email'] ?? false) || !($responseContent['name'] ?? false)) {
                        abort(500, 'identity provider doesn\'t provide valid data');
                    }
                    $user = User::updateOrCreate(['email' => $responseContent['email']], ['name' => $responseContent['name']]);
                    return redirect(route('home'));
                }
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                throw $e;
                return;
            } catch (\Exception $e) {
            }
            \Session::forget('jwt_token');
        }
        return redirect(str_replace('%redirect_to%', urlencode(route('login')), config('identity_provider.login_url')));
    }
}
