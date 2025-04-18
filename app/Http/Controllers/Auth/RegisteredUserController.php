<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationMail;
use App\Models\Admin;
use App\Models\Package;
use App\Models\Purchase;
use App\Models\User;
use App\Models\UserLedger;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use function GuzzleHttp\Promise\all;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request)
    {
        $captcha_code = rand(00000,99999);
        $ref_by = $request->query('inviteCode');
        return view('app.auth.registration', compact('ref_by', 'captcha_code'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'phone' => ['required', 'numeric', 'unique:users,phone'],
            'password' => ['required'],
            ]);
        if ($validate->fails()){
            $user = User::where('phone', $request->phone)->orWhere('email', $request->email)->first();
            if ($user){
                return back()->with('message', 'Phone number or email address exist');
            }
            return back()->with('message', $validate->errors());
        }


        $getIp = \Request::ip();
        /*
        $checkUserIp = DB::table('users')->where('ip', $getIp)->exists();
        if ($checkUserIp){
            return back()->with('message', 'Have an account your device.');
        }
*/
        if ($request->ref_by){
            $getUser = User::where('ref_id', $request->ref_by)->first();
            if ($getUser){
                $first_level_users = User::where('ref_by', $getUser->ref_id)->count();
                if ($first_level_users <= setting('total_member_register_reword')){
                    $getUser->balance = $getUser->balance + setting('total_member_register_reword_amount');
                    $getUser->save();
                }
            }
        }

        //Check refer code is next time edit
        $user = User::create([
            'name' => 'User'.rand(22,99),
            'username' => 'uname'.$request->phone,
            'ref_id' => $this->ref_code().$this->ref_code(),
            'ref_by' => $request->ref_by ?? $this->ref_code().$this->ref_code(),
            'email' => time().rand(0,999).'@gmail.com',
            'password' => Hash::make($request->password),
            'type' => 'user',
            'withdraw_password' => $request->withdraw_password,
            'phone' => $request->phone,
            'balance' => setting('registration_bonus'),
            'phone_code' => '+880',
            'ip' => $getIp,
            'remember_token' => Str::random(30),
        ]);

        if ($user){
            Auth::login($user);
            return redirect()->route('dashboard');
        }else{
            return back()->with('message', 'Registration Fail');
        }

    }

    public function ref_code()
    {
        $str1 = rand(0,99);
        $rand = rand(000,999);

        if (rand(111,999) % 2 == 0){
            $refCode = $str1.$rand;
        }else{
            $refCode = $rand.$str1;
        }
        return $refCode;
    }

    public function refreshCaptcha()
    {
        return response()->json(['captcha'=> captcha_img()]);
    }
}

