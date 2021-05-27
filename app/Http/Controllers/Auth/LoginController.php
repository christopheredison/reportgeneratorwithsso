<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Auth;
use DB;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = 'reports.index';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function loginSSO($email, $page)
    {
        $this->redirectTo = route($page);
        //check if already login or not login

        $userReport = DB::table('koolreport.users')->where( 'email' , $email )->first();
        if ( !$userReport ) {
            //inject resto
            DB::table('koolreport.users')->insert(
            [
                'password'          => '$2y$10$RTwvchSwMwAJ628lrTOKEevx5h11iYmDYIEGtoCsLMtGX06Ht.oCa',
                'name'              => $email,
                'email'             => $email,
                'created_at'        => date('Y-m-d H:i:s'),
            ]
            );
            
        }

        if (Auth::check()) {
            $login_email = Auth::user()->email;
            if($email!=$login_email){
                Auth::logout();
                $get_email = $email;
                //echo "dif";
                return view('auth.loginsso')->with('email', $get_email)->with('page', $page);
            }
            else{
                return redirect()->route($page);
                //echo "same";
            }
        }
        else{
            
            $get_email = $email;
            return view('auth.loginsso')->with('email', $get_email)->with('page', $page);
        }
    }
}
