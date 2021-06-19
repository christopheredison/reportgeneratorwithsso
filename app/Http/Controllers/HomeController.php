<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use DB;
use Illuminate\Support\Facades\Hash;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    /*
    public function __construct()
    {
        $this->middleware('auth');
    }
    */
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $user = $request->user();
        return view('home', compact('user'));
    }

    public function loginSSO($email, $password, $page)
    {
        //$this->redirectTo = route($page);
        //check if already login or not login

        $userReport = DB::table('koolreport.users')->where( 'email' , $email )->first();
        if ( !$userReport ) {
            //inject resto
            DB::table('koolreport.users')->insert(
            [
                'password'          => Hash::make($password),
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
                return view('auth.loginsso')->with('email', $get_email)->with('page', $page)->with('password', $password);
            }
            else{
                return redirect()->route($page);
                //echo "same";
            }
        }
        else{
            //echo "not check";
            $get_email = $email;
            return view('auth.loginsso')->with('email', $get_email)->with('page', $page)->with('password', $password);
        }
    }
}
