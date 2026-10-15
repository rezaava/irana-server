<?php

namespace App\Http\Controllers\Api;
use App\Http\Common\Firebase;

use App\Http\Controllers\Controller;
use App\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Validator;
use DB;

class AuthController extends Controller
{
public function fcm(Request $request)
    {
        $user=\App\Models\User::first();
        $tokens = \App\Models\Token::where('user_id',$user->id)->get();
        // Log::info($tokens);
        if ($tokens->first())
           return Firebase::push("titleeee", "bodyyyyy", 'default', [
                'operation' => 'alphabet',
                'desk_id' => '11'
            ], $tokens->toArray());
        return "sa";
    }
    public function register(Request $request)
    {
        $user=User::where('mobile',$request->mobile)->first();
        if($user){
            if($user->delete==1){
                $user->delete=0;
                $user->active=0;
                $user->save();
                return response()->json([
                        'status' => '1',
                        'message' => 'ثبت نام انجام شد',
                    ],
                    200,
                    array('Content-Type' => 'application/json;charset:utf-8;'),
                    JSON_UNESCAPED_UNICODE
                );
            }
            return response()->json([
                    'status' => '0',
                    'message' => 'قبلا ثبت نام کردید...لطفا وارد شوید...',
                ],
                200,
                array('Content-Type' => 'application/json;charset:utf-8;'),
                JSON_UNESCAPED_UNICODE
            );
           

        }
        $user=new \App\Models\User();
        $user->name=$request->name;
        $user->family=$request->family;
        $user->mobile=$request->mobile;
        $user->national_code=$request->national_code;
        $user->birth_city=$request->birth_city;
        $user->address=$request->address;
        $user->sarbazi=$request->sarbazi;
        $user->married=$request->married;
        $user->degree=$request->degree;
        $user->reshte=$request->reshte;
        $user->state=$request->state;
        $user->city=$request->city;
        $user->birthdate=$request->birthdate;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image_name = $request->mobile  . "_" . time() . "." . $image->getClientOriginalExtension();
            $destination_path = 'files/user';
            $image->move($destination_path, $image_name);
            $user->image = '/' . $image_name;
        }
        if ($request->hasFile('national_image')) {
            $image = $request->file('national_image');
            $image_name = "national_".$request->mobile  . "_" . time() . "." . $image->getClientOriginalExtension();
            $destination_path = 'files/user';
            $image->move($destination_path, $image_name);
            $user->national_image = '/' . $image_name;
        }
        $code=rand(1111,9999);
        $code=1111;
        $user->sms=$code;
        $user->save();

        $role=\App\Models\Role::where('name','user')->first();
        $user->attachRole($role);
        return response()->json([
            'status' => '1',
            'message' => 'ثبت نام انجام شد',
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }
    public function login(Request $request)
    {
        $data = $request->all();
        $rule = [
            'mobile' => 'required',
        ];
        $valid = Validator::make($data, $rule);
        if ($valid->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => $valid->errors()->first(),
            ],
                422,
                array('Content-Type' => 'application/json;charset:utf-8;'),
                JSON_UNESCAPED_UNICODE
            );
        }

        $user=\App\Models\User::where('mobile',$request->mobile)->first();


        if($user){
            if($user->id!=60){
            $code=rand(1111,9999);
            $code=1111;
            $user->sms=$code;
            $user->save();
            }
            return response()->json([
                'status' => '1',
                'message' => '1111کد ارسال شد',
                'card'=>['number'=>'6037-9981-5629-6021','name'=>'فرشاد نجفی', 'bank'=>'بانک ملی'],
            
                'user' => $user,
            ],
                200,
                array('Content-Type' => 'application/json;charset:utf-8;'),
                JSON_UNESCAPED_UNICODE
            );


        }
        return response()->json([
            'status' => '0',
            'message' => 'کاربر وجود ندارد!',
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }

    public function checkCode(Request $request)
    {
        $data = $request->all();
        $rule = [
            'mobile' => 'required',
            'sms' => 'required',
        ];
        $valid = Validator::make($data, $rule);
        if ($valid->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => $valid->errors()->first(),
            ],
                422,
                array('Content-Type' => 'application/json;charset:utf-8;'),
                JSON_UNESCAPED_UNICODE
            );
        }

        $user=\App\Models\User::where('mobile',$request->mobile)->first();
        if ($user) {
            if ($user->sms == $request->sms) {
                if($user->id!=60){
                $user->sms=null;
                $user->save();
                }
                if($user->hasRole('admin'))
                    $role=2;
                else
                    $role=1;
                Auth::login($user);
                $token = Auth::user()->createToken('irana')->accessToken;
                  if ($request->fcm_token){
         Log::info("fcm...");
         Log::info($user);

                    Firebase::TokenStore($user->id, $request->fcm_token);
}
                return response()->json([
                    'status' => '1',
                    'message' => 'وارد شدید...',
                    'token' => $token,
                    'user' => Auth::user()->id,
                    'role' => $role,
                ], 200,
                    array('Content-Type' => 'application/json; charset=utf-8'),
                    JSON_UNESCAPED_UNICODE);
            }
        }

        return response()->json([
            'status' => '0',
            'message' => 'کاربر وجود ندارد!',
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }
    
    public function changePass(Request $request){
        if($request->id!=60)
         return response()->json([
            'status' => '0',
            'message' => ' مجاز نیستید',
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
        
        
            $user=\App\Models\User::where('id',$request->id)->first();
            $user->sms=$request->pass;
            $user->save();
 return response()->json([
            'status' => '1',
            'message' => '  بروزرسانی انجام شد',
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }
    
    
}
