<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class OnedriveController extends BaseController
{
    public function redirect(Request $request) {
        if ($request->has('access_token')) {
            $request->session()->put('access_token', $request->access_token);
            return redirect()->away($request->session()->get('return_url'));
        }
        else {
            return view('redirect');
        }
    }

    public function test(Request $request) {
        var_dump($request->session()->all());
    }
}