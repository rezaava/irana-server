<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\City;
use App\Models\Setting;
use App\Models\Clear;
use App\Models\Requestuser;
use App\Models\State;
use App\Models\User;
use App\Models\User2;
use App\Models\Seen;
use App\Models\Special;
use App\Models\Specialuser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Brand;
use App\Models\Ano;
use App\Models\Type;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    
    
    public function home()
{
    $now = Carbon::now();

    // آمار کاربران
    $users = User::withCount([
        'requestusers as reqs7' => function ($query) use ($now) {
            $query->where('status', 1)
                ->where('created_at', '>', $now->copy()->subDays(7));
        },

        'requestusers as reqs30' => function ($query) use ($now) {
            $query->where('status', 1)
                ->where('created_at', '>', $now->copy()->subDays(30));
        },

        'requestusers as reqs90' => function ($query) use ($now) {
            $query->where('status', 1)
                ->where('created_at', '>', $now->copy()->subDays(90));
        },

        'requestusers as reqs180' => function ($query) use ($now) {
            $query->where('status', 1)
                ->where('created_at', '>', $now->copy()->subDays(180));
        },
    ])->get();


    // مرتب‌سازی کاربران
    $users7 = $users->sortByDesc('reqs7')->values();
    $users30 = $users->sortByDesc('reqs30')->values();
    $users90 = $users->sortByDesc('reqs90')->values();
    $users180 = $users->sortByDesc('reqs180')->values();


    // تابع گرفتن آمار استان‌ها
    $getCities = function ($days) use ($now) {

        return DB::table('requests')
            ->select(
                'state',
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>', $now->copy()->subDays($days))
            ->groupBy('state')
            ->orderByDesc('count')
            ->get();
    };


    // آمار 7، 30، 90 و 180 روز
    $cities7 = $getCities(7);
    $cities30 = $getCities(30);
    $cities90 = $getCities(90);
    $cities180 = $getCities(180);


    // گرفتن تمام استان‌های مورد نیاز با یک Query
    $stateIds = collect()
        ->merge($cities7->pluck('state'))
        ->merge($cities30->pluck('state'))
        ->merge($cities90->pluck('state'))
        ->merge($cities180->pluck('state'))
        ->filter()
        ->unique()
        ->values();

    $states = State::whereIn('id', $stateIds)
        ->get()
        ->keyBy('id');


    // اضافه کردن اطلاعات استان به نتیجه
    foreach ([$cities7, $cities30, $cities90, $cities180] as $cities) {

        foreach ($cities as $item) {
            $item->city_model = $states->get($item->state);
        }
    }


    // تعداد تغییرات
    $changes = User2::where('status', 0)->count();


    // خروجی
    return response()->json([
        'status' => '1',
        'message' => 'خانه ارسال شد',

        'users7' => $users7,
        'users30' => $users30,
        'users90' => $users90,
        'users180' => $users180,

        'cities7' => $cities7,
        'cities30' => $cities30,
        'cities90' => $cities90,
        'cities180' => $cities180,

        'changes' => $changes,

    ], 200, [
        'Content-Type' => 'application/json;charset=utf-8;'
    ], JSON_UNESCAPED_UNICODE);
}

    public function cities(Request $request)
    {
        if (isset($request->id)) {
            $cities = City::where('state_id', $request->id)->get();
            return response()->json([
                'status' => '1',
                'message' => 'شهرها ارسال شد',
                'cities' => $cities,
            ],
                200,
                array('Content-Type' => 'application/json;charset:utf-8;'),
                JSON_UNESCAPED_UNICODE
            );
        }
        $states = State::all();
        return response()->json([
            'status' => '1',
            'message' => 'شهرها ارسال شد',
            'states' => $states,
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function create(Request $request)
    {
        Log::info('create');
        Log::info($request);
        $old = \App\Models\Request::where('code', 'like',  $request->code.'%' )->count();
        Log::info($old);
        Log::info($old);

        if ($old>0) {
            // $firstCode = substr($old->code, 0, 8);
            // $theRest = substr($old->code, 8);
            // $theRest = (int)$theRest + 1;
            $old=$old+1;
            $old=str_pad($old, 4, "0", STR_PAD_LEFT);
            $theRest=$request->code.($old);
        } else
            $theRest=$request->code.'0001';

        $req = new \App\Models\Request();
        $req->code = $theRest;
        if(isset($request->parent)){
            $req->parent=$request->parent;
        }
        $req->name = $request->name;
        $req->mobile = $request->mobile;
        $req->mobile2 = $request->mobile2;
        $req->state = $request->state;
//        $req->city = $request->city;
        $req->device_brand = $request->device_brand;
        $req->device_type = $request->device_type;
        $req->address = $request->address;
        $req->problem = $request->problem;
        $req->description = $request->description;
        $req->time = $request->time;
        $req->save();
        return response()->json([
            'status' => '1',
            'message' => 'ذخیره شد',
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function list(Request $request){
                // Log::info('teste alan');

        $user = Auth::user();
        // Log::info($user);

        if ($user->hasRole('admin')){
            $reqs = \App\Models\Request::orderBy('id','DESC')->get();
            // Log::info('admine');
        }
        // $reqs = \App\Models\Request::where('status',0)->get();
        else {
            // Log::info('admin ni');
            $rejects = Requestuser::where('user_id', $user->id)->pluck('request_id');
            // Log::info($rejects->count());

            $reqs = \App\Models\Request::where('status', 0)->whereNotIn('id', $rejects)->orderBy('id','DESC')->get();
            // Log::info($reqs);

        }
        foreach ($reqs as $req) {
            $req['state_model'] = State::find($req->state);
//            $req['city_model']=City::find($req->city);
            $req['repairman_model'] = User::find($req->repairman_id);
        }
        
        // Log::info('ttttt');
        // Log::info($reqs);
        return response()->json([
            'status' => '1',
            'message' => 'لیست درخواست ها',
            'requests' => $reqs,
            'user' => $user,
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function myList(Request $request)
    {

        $user = Auth::user();
        $all = Requestuser::where('user_id', $user->id)->pluck('request_id');
        $reqs = \App\Models\Request::whereIn('id', $all)->orderBy('id','DESC')->get();
        foreach ($reqs as $req) {
            $req['state_model'] = State::find($req->state);
//            $req['city_model']=City::find($req->city);
            $req['repairman_model'] = User::find($req->repairman_id);
        }
        return response()->json([
            'status' => '1',
            'message' => 'لیست درخواست های من',
            'requests' => $reqs,
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function detail(Request $request)
    {
        Log::info('sss');
        Log::info($request);
        
        $req = \App\Models\Request::find($request->id);
// $clear=null;
                if($req){
        $req['state_model'] = State::find($req->state);
        $father=\App\Models\Request::find($req->parent);
        $req['father']=$father;
        if($father){
        $req['siblings']=\App\Models\Request::where('parent',$father->id)->where('id','!=',$request->id)->orderBy('id','DESC')->get();
        
        
        }else{
        $req['siblings']=\App\Models\Request::where('parent',$request->id)->orderBy('id','DESC')->get();    
        }
//        $req['city_model']=City::find($req->city);
        $req['repairman_model'] = User::find($req->repairman_id);
        $clear=Clear::where('text1',$req->code)->orderBy('id','desc')->first();
        if($clear)
        $req['file1']=$clear->file1;
        else
        $req['file1']=null;
                }
                       Log::info($req);

        return response()->json([
            'status' => '1',
            'message' => 'جزئیات درخواست ',
            'request' => $req,
            'clear' =>$clear,
            
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
 
    }

    public function accept(Request $request)
    {
        $req = \App\Models\Request::find($request->id);
        $user = Auth::user();
        $rs=\App\Models\Request::where('repairman_id',$user->id)->where('cleared',0)->where('end','!=',2)->where('status',1)->count();
        if($rs>=2){
             return response()->json([
            'status' => '0',
            'message' => 'ابتدا درخواست های تسویه نشده را تسویه کنید ',
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
            
        }
        if($req->status==1){
            
             return response()->json([
            'status' => '1',
            'message' => ' درخواست شما قبلا توسط شخض دیگری تائید شده است',
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
        }
        $old = Requestuser::where('user_id', $user->id)->where('request_id', $req->id)->first();
        if (!$old) {
            $old = new Requestuser();
            $old->user_id = $user->id;
            $old->request_id = $req->id;
        }
        $old->status = $request->status;
        $old->save();

        if ($request->status == 1) {
            $req->repairman_id = $user->id;
            $req->status = 1;
            $req->save();
        }
        return response()->json([
            'status' => '1',
            'message' => 'ثبت شد ',
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function showProfile(Request $request)
    {
        log::info($request);
        if(isset($request->id)){
            $user=User::find($request->id);
        }else{
                   $user = Auth::user();
 
        }
        $user['state_model'] = State::find($user->state);
        $user['city_model'] = City::find($user->city);
        return response()->json([
            'status' => '1',
            'message' => 'پروفایل ',
            'user' => $user,
            'pic' => 'files/user',
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function editProfile(Request $request)
    {
        $user = Auth::user();
        $user->name = $request->name;
        $user->family = $request->family;
        // $user->mobile=$request->mobile;
        $user->national_code = $request->national_code;
        $user->birth_city = $request->birth_city;
        $user->address = $request->address;
        $user->sarbazi = $request->sarbazi;
        $user->married = $request->married;
        $user->degree = $request->degree;
        $user->reshte = $request->reshte;
        $user->state = $request->state;
        $user->city = $request->city;
        $user->birthdate = $request->birthdate;
        // $user->specials = $request->specials;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image_name = $request->mobile . "_" . time() . "." . $image->getClientOriginalExtension();
            $destination_path = 'files/user';
            $image->move($destination_path, $image_name);
            $user->image = '/' . $image_name;
        }
       if ($request->hasFile('national_image')) {
            $image = $request->file('national_image');
            $image_name = "national_".$user->mobile  . "_" . time() . "." . $image->getClientOriginalExtension();
            $destination_path = 'files/user';
            $image->move($destination_path, $image_name);
            $user->national_image = '/' . $image_name;
        }
        $user->save();
        return response()->json([
            'status' => '1',
            'message' => 'ذخیره شد ',
            'user' => $user,
            'pic' => 'files/user',
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function users(Request $request)
    {
        if(isset($request->id)){
            $user=User::find($request->id);
            $user['state_model'] = State::find($user->state);
            $user['city_model'] = City::find($user->city);
            return response()->json([
                    'status' => '1',
                    'message' => 'کاربر',
                    'user' => $user,
                ],
                200,
                array('Content-Type' => 'application/json;charset:utf-8;'),
                JSON_UNESCAPED_UNICODE
            );
        }
        
        $users=User::where('delete',0);
        
        if(isset($request->special)){
            //$users = $users->where('specials','['.$request->special.']');
            $users = $users->whereRaw("json_contains(specials, '" . $request->special . "')");
        }
        if(isset($request->state)){
            $users= $users->where('state',$request->state);
        }
        if(isset($request->city)){
            $users= $users->where('city',$request->city);
        }
        
        $users = $users->orderBy('id', 'desc')->get();
        
        Log::info('Users found {users} by {sp}', ['users' => sizeof($users),'sp' => $request->special??null]);
        
        
        // $users = User::where('delete', 0)->get();
        foreach ($users as $user) {

            $user['state_model'] = State::find($user->state);
            $user['city_model'] = City::find($user->city);
        }
        return response()->json([
            'status' => '1',
            'message' => 'لیست کاربران',
            'user' => $users,

        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }

    public function active(Request $request)
    {
        $user = User::find($request->id);
        if ($user->active) {
            $user->active = 0;
            $user->save();
            return response()->json([
                'status' => '1',
                'message' => 'کاربر غیرفعال شد ',
                'user' => $user,

            ],
                200,
                array('Content-Type' => 'application/json;charset:utf-8;'),
                JSON_UNESCAPED_UNICODE
            );
        }
        $user->active = 1;
        $user->save();
        return response()->json([
            'status' => '1',
            'message' => 'کاربر فعال شد ',
            'user' => $user,

        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }

    public function delete(Request $request)
    {
        $user = User::find($request->id);
        $user->delete = 1;
        $user->save();
        return response()->json([
            'status' => '1',
            'message' => 'کاربر حذف شد ',

        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function check(Request $request)
    {
        $anos=null;
        Log::info($request);

        $user = Auth::user();    
        Log::info($user);

        if ($user->active == 1 && $user->delete == 0) {
                           Log::info('1');

                 
                    $chats_seens = Seen::where('user_id', $user->id)->whereNotNull('chat_id')->pluck('chat_id');
        Log::info($chats_seens);

                    if ($user->hasRole('admin')){
                        $chats=Chat::whereNotIn('id', $chats_seens)->get();
                    }else
                    {
                        $anoSeen=Seen::where('user_id', $user->id)->whereNotNull('ano_id')->pluck('ano_id');
                        $anos=Ano::whereNotIn('id', $anoSeen)->get();
                        $chats=Chat::whereNotIn('id', $chats_seens)->where('user_id',$user->id)->get();
                    }
 foreach ($chats as $key => $item) {

$item['sender']=User::find($item->sender_id);
                $sn = new Seen();
                $sn->user_id = $user->id;
                $sn->chat_id = $item->id;
                $sn->save();

            }
            
                                if (!$user->hasRole('admin')){

             foreach ($anos as $key => $item) {

                $sn = new Seen();
                $sn->user_id = $user->id;
                $sn->ano_id = $item->id;
                $sn->save();

            }
                                }
            
            $seens = Seen::where('user_id', $user->id)->whereNotNull('request_id')->pluck('request_id');
            
            $reqs = \App\Models\Request::whereNotIn('id', $seens)->orderBy('id','DESC')->get();
            foreach ($reqs as $key => $item) {

                $sn = new Seen();
                $sn->user_id = $user->id;
                $sn->request_id = $item->id;
                $sn->save();

                // if($item->state != $user->state){
                //     $reqs->forget($key);
                // }
            }
            // $reqs=null;
            return response()->json([
                'status' => '1',
                'message' => 'درخواست های جدید ',
                'requests' => $reqs,
                'anos' => $anos,
                'chats' => $chats,

            ],
                200,
                array('Content-Type' => 'application/json;charset:utf-8;'),
                JSON_UNESCAPED_UNICODE
            );
        }
        return response()->json([
            'status' => '0',
            'message' => 'درخواست های جدید ',
            'requests' => null,
             'chats' => null,

        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }

    public function deleteRequest(Request $request)
    {
        $req = \App\Models\Request::where('id', $request->id)->first();
        if (!$req) {
            return response()->json([
                'status' => '0',
                'message' => 'درخواست وجود ندارد ',
            ],

                200,
                array('Content-Type' => 'application/json;charset:utf-8;'),
                JSON_UNESCAPED_UNICODE
            );
        }
        $req->status = -1;
        $req->save();
        return response()->json([
            'status' => '1',

            'message' => 'درخواست حذف شد ',

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }
    
    public function refreshRequest(Request $request)
    {
        $req = \App\Models\Request::where('id', $request->id)->first();
        if (!$req) {
            return response()->json([
                'status' => '0',
                'message' => 'درخواست وجود ندارد ',
            ],

                200,
                array('Content-Type' => 'application/json;charset:utf-8;'),
                JSON_UNESCAPED_UNICODE
            );
        }
        
        
        $oldcode=substr($req->code, 0,8); 

        $old = \App\Models\Request::where('code', 'like',  $oldcode.'%' )->count();
        Log::info($old);

        if ($old>0) {
            // $firstCode = substr($old->code, 0, 8);
            // $theRest = substr($old->code, 8);
            // $theRest = (int)$theRest + 1;
            $theRest=$oldcode.($old+1);
        } else
            $theRest=$request->code.'1';
        $newreq = new \App\Models\Request();
        $newreq->code = $theRest;
        $newreq->name = $req->name;
        $newreq->mobile = $req->mobile;
        $newreq->mobile2 = $req->mobile2;
        $newreq->state = $req->state;
//        $req->city = $request->city;
        $newreq->device_brand = $req->device_brand;
        $newreq->device_type = $req->device_type;
        $newreq->address = $req->address;
        $newreq->problem = $req->problem;
        $newreq->description = $req->description;
        $newreq->time = $req->time;
        $newreq->save();
        
        $req->status=4;
        $req->end=2;
        $req->save();
        return response()->json([
            'status' => '1',

            'message' => 'درخواست مجددا فعال شد ',

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function addBrand(Request $request)
    {
        if (isset($request->brand_id))
            $brand = Brand::find($request->brand_id);
        else
            $brand = new Brand();
        $brand->name = $request->name;
        $brand->save();

        return response()->json([
            'status' => '1',

            'message' => 'برند ثبت شد ',
            'model' => $brand,

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );


    }

    public function deleteBrand(Request $request)
    {
        $brand = Brand::find($request->brand_id);
        $brand->delete();

        return response()->json([
            'status' => '1',

            'message' => 'برند حذف شد ',

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );


    }

    public function addType(Request $request)
    {
        if (isset($request->type_id))
            $type = Type::find($request->type_id);
        else
            $type = new Type();
        $type->name = $request->name;
        $type->save();

        return response()->json([
            'status' => '1',

            'message' => 'نوع ثبت شد ',
            'model' => $type,


        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );


    }

    public function deleteType(Request $request)
    {
        $type = Type::find($request->type_id);


        $type->delete();

        return response()->json([
            'status' => '1',

            'message' => 'نوع حذف شد ',

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );


    }

    public function listType()
    {
        $types = Type::orderBy('id' , "DESC")->get();
        $brands = Brand::orderBy('id' , "DESC")->get();

        return response()->json([
            'status' => '1',

            'message' => 'لیست نوع و برندها ',
            'types' => $types,
            'brands' => $brands,

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function userHistory(Request $request)
    {
        $ru = \App\Models\Request::where('repairman_id', $request->id)->orderBy('id','DESC')->get();
        foreach ($ru as $req) {
            $req['state_model'] = State::find($req->state);
//            $req['city_model']=City::find($req->city);
            $req['repairman_model'] = User::find($req->repairman_id);
        }
        return response()->json([
            'status' => '1',

            'message' => 'لیست درخواست ها ',
            'requests' => $ru,

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }

    public function endRequest(Request $request)
    {
        $ru = \App\Models\Request::where('id', $request->id)->first();

        $ru->end = $request->end;
        
        $ru->sum = $request->sum;
        $ru->abzar = $request->abzar;
        // $ru->porsant = $request->porsant;
        // $ru->porsant = $request->porsant;
        $ru->porsant = $request->sum-$request->abzar;
        $ru->end_description = $request->end_description;
        $ru->abzar_details = $request->abzar_details;
        $ru->save();

        return response()->json([
            'status' => '1',

            'message' => 'ثبت شد ',

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }

    public function clearPost(Request $request)
    {
        $user = Auth::user();

        $clear = new Clear();
        $clear->user_id=$user->id;
        $clear->description = $request->desc;
        $clear->text1 = $request->text1;
        $clear->text2 = $request->text2;
        $clear->text3 = $request->text3;
        $clear->sum = $request->sum;
        if ($request->hasFile('file1')) {
            $file = $request->file('file1');
            $file_name = $file->getClientOriginalName(). "_" . time() . "." . $file->getClientOriginalExtension();
            $destination_path = 'files/clear';
            $file->move($destination_path, $file_name);
            $clear->file1 = '/' . $file_name;
        }
        if ($request->hasFile('file2')) {
            $file = $request->file('file2');
            $file_name =  $file->getClientOriginalName() . "_" . time() . "." . $file->getClientOriginalExtension();
            $destination_path = 'files/clear';
            $file->move($destination_path, $file_name);
            $clear->file2 = '/' . $file_name;
        }

        if ($request->hasFile('file3')) {
            $file = $request->file('file3');
            $file_name =  $file->getClientOriginalName() . "_" . time() . "." . $file->getClientOriginalExtension();
            $destination_path = 'files/clear';
            $file->move($destination_path, $file_name);
            $clear->file3 = '/' . $file_name;
        }

        $clear->save();

        return response()->json([
            'status' => '1',

            'message' => 'ثبت شد.اکنون میتوانید درخواست های جدید را تائید کنید ',

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }

    public function clear()
    {
        $clears = Clear::all();
        foreach($clears as $clear){
            $clear['user']=User::find($clear->user_id);
            $clear['user']['city_model']=City::find($clear['user']->city);
            $clear['user']['state_model']=state::find($clear['user']->state);
        }


        return response()->json([
            'status' => '1',

            'message' => 'لیست تسویه ها',
            'clears' => $clears,

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }
    
    public function clear2($id)
    {
        $clear = Clear::find($id);
        
            $clear['user']=User::find($clear->user_id);
            $clear['user']['city_model']=City::find($clear['user']->city);
            $clear['user']['state_model']=state::find($clear['user']->state);
        


        return response()->json([
            'status' => '1',

            'message' => 'لیست تسویه ها',
            'clears' => $clear,

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }

    public function messages(Request $request)
    {
        if(isset($request->user_id))
        {
            $user=User::where('id',$request->user_id)->first();
        }
        else
            $user=Auth::user();
        $messages=Chat::where('user_id',$user->id)->get();
        foreach($messages as $msg){
            $msg['sender']=User::find($msg->sender_id);
            
        }
        return response()->json([
            'status' => '1',

            'message' => 'لیست پیام ها',
            'messages' => $messages,

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }

    public function messagesPost(Request $request)
    {
        Log::info('chat');
        Log::info($request->text);

        $message=new Chat();
        if(isset($request->user_id))
        {
            $user=User::where('id',$request->user_id)->first();
        }
        else
        {
            $user=Auth::user();
            
        }
        $message->sender_id=Auth::user()->id;
        if ($request->hasFile('file')) {
            $image = $request->file('file');
            $image_name = $user->id . "_" . time() . "." . $image->getClientOriginalExtension();
            $destination_path = 'files/chats';
            $image->move($destination_path, $image_name);
            $message->file = '/' . $image_name;
            $message->hash = $request->hash;
            $message->size = $request->size;
        }
        $message->user_id=$user->id;
        $message->text=$request->text;
        $message->save();
        $seen=new Seen();
        $seen->user_id=Auth::user()->id;
        $seen->chat_id=$message->id;
        $seen->save();
        return response()->json([
            'status' => '1',

            'message' => 'پیام ارسال شد',
            'text' => $message,


        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }
    public function messageDelete(Request $request)
    {
       
        $message=Chat::where('id',$request->id)->first();
        
        $message->delete();
        return response()->json([
            'status' => '1',

            'message' => 'پیام حذف شد',

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }
     public function anoPost(Request $request)
    {
        $user = Auth::user();

        $ano = new Ano();
        $ano->user_id=$user->id;
        $ano->text = $request->text;
        $ano->title = $request->title;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $file_name = $file->getClientOriginalName(). "_" . time() . "." . $file->getClientOriginalExtension();
            $destination_path = 'files/ano';
            $file->move($destination_path, $file_name);
            $ano->file =$destination_path. '/' .$file_name;
        }
       

        $ano->save();

        return response()->json([
            'status' => '1',

            'message' => 'ثبت شد ',

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }
    public function anoGet(Request $request){
        if(isset($request->id)){
            $anos=Ano::find($request->id);
        }
        else
            $anos=Ano::all();
         return response()->json([
            'status' => '1',

            'message' => 'اعلانات ',
            'anos' => $anos

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }
    
    public function anoDelete($id){
        $ano = Ano::where('id' , $id)->first();
        $ano->delete();
        return response()->json([
            "message" => 'با موفقیت حذف شد ',
            "status" => "1"
            ]);
    }
public function excell(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | Get Requests
    |--------------------------------------------------------------------------
    */

    $ru = \App\Models\Request::where('time', '>=', $request->start)
        ->where('time', '<=', $request->end)
        ->orderBy('id', 'DESC')
        ->get();

    Log::info('EXCELL START: ' . $request->start);
    Log::info('EXCELL END: ' . $request->end);
    Log::info('EXCELL REQUEST COUNT: ' . $ru->count());

    /*
    |--------------------------------------------------------------------------
    | Add Related Data
    |--------------------------------------------------------------------------
    */

    foreach ($ru as $req) {

        $req->state_model = $req->state
            ? State::find($req->state)
            : null;

        $req->repairman_model = $req->repairman_id
            ? User::find($req->repairman_id)
            : null;
    }

    Log::info('EXCELL DATA:', $ru->toArray());

    /*
    |--------------------------------------------------------------------------
    | Spreadsheet
    |--------------------------------------------------------------------------
    */

    $spreadsheet = new Spreadsheet();

    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setTitle('گزارش');
    $sheet->setRightToLeft(true);

    /*
    |--------------------------------------------------------------------------
    | Header
    |--------------------------------------------------------------------------
    */

    $headers = [
        'A1' => '#',
        'B1' => 'شماره درخواست',
        'C1' => 'نام تعمیرکار',
        'D1' => 'نام مشتری',
        'E1' => 'استان',
        'F1' => 'وضعیت',
        'G1' => 'دلیل کنسلی',
        'H1' => 'کل دریافتی',
        'I1' => 'هزینه لوازم مصرفی',
        'J1' => 'کل اجرت',
        'K1' => 'پورسانت (50%)',
    ];

    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }

    /*
    |--------------------------------------------------------------------------
    | Header Style
    |--------------------------------------------------------------------------
    */

    $headerStyle = $sheet->getStyle('A1:K1');

    $headerStyle->getFont()
        ->setName('Arial')
        ->setSize(10)
        ->setBold(true);

    $headerStyle->getFill()
        ->setFillType(
            \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
        )
        ->getStartColor()
        ->setRGB('33CCCC');

    $headerStyle->getAlignment()
        ->setHorizontal(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        )
        ->setVertical(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        );

    $headerStyle->getBorders()->getAllBorders()
        ->setBorderStyle(
            \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
        );

    /*
    |--------------------------------------------------------------------------
    | Data
    |--------------------------------------------------------------------------
    */

    $row = 2;
    $counter = 1;

    $totalReceived = 0;
    $totalToolsCost = 0;
    $totalWage = 0;
    $totalCommission = 0;

    foreach ($ru as $req) {

        /*
        |--------------------------------------------------------------------------
        | Repairman
        |--------------------------------------------------------------------------
        */

        $repairmanName = '';

        if ($req->repairman_model) {

            $repairmanName = trim(
                ($req->repairman_model->name ?? '') . ' ' .
                ($req->repairman_model->family ?? '')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        */

        $customerName = $req->name ?? '';

        /*
        |--------------------------------------------------------------------------
        | State
        |--------------------------------------------------------------------------
        */

        $stateName = '';

        if ($req->state_model) {
            $stateName = $req->state_model->name ?? '';
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $status = '';

        switch ((int) $req->status) {

            case 0:
                $status = 'ایجاد';
                break;

            case 1:
                $status = 'قبول';
                break;

            case -1:
                $status = 'رد';
                break;

            case 2:
                $status = 'پایان';
                break;

            case 3:
                $status = 'لغو';
                break;

            case 4:
                $status = 'بروزرسانی';
                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Money
        |--------------------------------------------------------------------------
        */

        $received = (float) ($req->sum ?? 0);

        $tools = (float) ($req->abzar ?? 0);

        $wage = $received - $tools;

        $commission = (float) ($req->porsant ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Totals
        |--------------------------------------------------------------------------
        */

        $totalReceived += $received;
        $totalToolsCost += $tools;
        $totalWage += $wage;
        $totalCommission += $commission;

        /*
        |--------------------------------------------------------------------------
        | Excel Row
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue("A{$row}", $counter);
        $sheet->setCellValue("B{$row}", $req->id);
        $sheet->setCellValue("C{$row}", $repairmanName);
        $sheet->setCellValue("D{$row}", $customerName);
        $sheet->setCellValue("E{$row}", $stateName);
        $sheet->setCellValue("F{$row}", $status);

        $sheet->setCellValue(
            "G{$row}",
            $req->end_description ?? ''
        );

        $sheet->setCellValue(
            "H{$row}",
            $received
        );

        $sheet->setCellValue(
            "I{$row}",
            $tools
        );

        $sheet->setCellValue(
            "J{$row}",
            $wage
        );

        $sheet->setCellValue(
            "K{$row}",
            $commission
        );

        $row++;
        $counter++;
    }

    /*
    |--------------------------------------------------------------------------
    | Total Row
    |--------------------------------------------------------------------------
    */

    $totalRow = $row;

    $sheet->setCellValue("B{$totalRow}", 'جمع کل');
    $sheet->setCellValue("G{$totalRow}", 'جمع');

    $sheet->setCellValue("H{$totalRow}", $totalReceived);
    $sheet->setCellValue("I{$totalRow}", $totalToolsCost);
    $sheet->setCellValue("J{$totalRow}", $totalWage);
    $sheet->setCellValue("K{$totalRow}", $totalCommission);

    /*
    |--------------------------------------------------------------------------
    | Total Style
    |--------------------------------------------------------------------------
    */

    $totalStyle = $sheet->getStyle("A{$totalRow}:K{$totalRow}");

    $totalStyle->getFont()
        ->setName('Arial')
        ->setSize(10)
        ->setBold(true);

    $totalStyle->getFill()
        ->setFillType(
            \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
        )
        ->getStartColor()
        ->setRGB('D9EAD3');

    $totalStyle->getAlignment()
        ->setHorizontal(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        )
        ->setVertical(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
        );

    $totalStyle->getBorders()->getAllBorders()
        ->setBorderStyle(
            \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
        );

    /*
    |--------------------------------------------------------------------------
    | Body Style
    |--------------------------------------------------------------------------
    */

    if ($row > 2) {

        $bodyStyle = $sheet->getStyle("A2:K" . ($row - 1));

        $bodyStyle->getFont()
            ->setName('Arial')
            ->setSize(10);

        $bodyStyle->getAlignment()
            ->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            );

        $bodyStyle->getBorders()->getAllBorders()
            ->setBorderStyle(
                \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Number Format
    |--------------------------------------------------------------------------
    */

    $sheet->getStyle("H2:K{$totalRow}")
        ->getNumberFormat()
        ->setFormatCode('#,##0');

    /*
    |--------------------------------------------------------------------------
    | Column Width
    |--------------------------------------------------------------------------
    */

    $widths = [
        'A' => 8,
        'B' => 18,
        'C' => 25,
        'D' => 25,
        'E' => 18,
        'F' => 18,
        'G' => 30,
        'H' => 18,
        'I' => 22,
        'J' => 18,
        'K' => 20,
    ];

    foreach ($widths as $column => $width) {
        $sheet->getColumnDimension($column)->setWidth($width);
    }

    /*
    |--------------------------------------------------------------------------
    | Row Height
    |--------------------------------------------------------------------------
    */

    $sheet->getRowDimension(1)->setRowHeight(25);

    if ($row > 2) {
        for ($i = 2; $i < $row; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(20);
        }
    }

    $sheet->getRowDimension($totalRow)->setRowHeight(22);

    /*
    |--------------------------------------------------------------------------
    | Filter
    |--------------------------------------------------------------------------
    */

    if ($row > 2) {
        $sheet->setAutoFilter("A1:K" . ($row - 1));
    }

    /*
    |--------------------------------------------------------------------------
    | Freeze Header
    |--------------------------------------------------------------------------
    */

    $sheet->freezePane('A2');

    /*
    |--------------------------------------------------------------------------
    | Download
    |--------------------------------------------------------------------------
    */

    $fileName = 'report-' . date('Y-m-d-H-i-s') . '.xlsx';

    $writer = new Xlsx($spreadsheet);

    return response()->streamDownload(
        function () use ($writer) {
            $writer->save('php://output');
        },
        $fileName,
        [
            'Content-Type' =>
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]
    );
}
    
    public function clearRequest(Request $request){
      
            $ru = \App\Models\Request::where('id', $request->id)->first();
            
            if($ru->cleared==0)
                $ru->cleared=1;
            else
                $ru->cleared=0;
            $ru->save();
            
            $clear=Clear::where('text1',$ru->code)->first();
            if($clear){
                if($request->desc){
                    $clear->desc=$request->desc;
                    $clear->save();
                }
            }
            
            return response()->json([
            'status' => '1',

            'message' => 'انجام شد',
            'request' => $ru

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }

  public function update(Request $request){
      
            $set = \App\Models\Setting::orderBy('id','desc')->first();
            
            $update=$force=0;
            $change='';
            $link1='http://iranaserviceapp.ir/test/files/admin.apk';
            $link2='http://iranaserviceapp.ir/test/files/repairman.apk';
            $link=null;
            if($request->admin==1){
                if($set->version_admin>$request->version){
                    $update=1;
                    $link=$link1;
                    if($set->force_update_admin==1){
                        $force=1;
                    }
                    $change=$set->changes_admin;
                }
            }
            if($request->admin==0){
                if($set->version_repairman>$request->version){
                    $update=1;
                                        $link=$link2;

                    if($set->force_update_repairman==1){
                        $force=1;
                    }
                    $change=$set->changes_repairman;
                }
            }
            
            
            return response()->json([
            'status' => '1',

            'message' => 'تنظیمات',
            'force' => $force,
            'update' => $update,
            'link' => $link,
            'changes' => $change,
           

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }
    
    
    public function edit(Request $request,$id){
      
            $r = \App\Models\Request::where('id', $id)->first();
            
            $clear=Clear::where('text1',$r->code)->first();
            return response()->json([
            'status' => '1',

            'message' => 'ویرایش درخواست',
            'request' => $r,
            'clears'=>$clear

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }
    
    public function editPost(Request $request,$id){
      
            $ru = \App\Models\Request::where('id', $id)->first();
            // $ru->end = $request->end;
        
        $ru->sum = $request->sum;
        $ru->abzar = $request->abzar;
        // $ru->porsant = $request->porsant;
        $ru->porsant = $request->sum-$request->abzar;
        $ru->abzar_details = $request->abzar_details;
        $ru->end_description = $request->end_description;
        $ru->save();
        
        
        if($request->hasFile('file1') || $request->hasFile('file2') || $request->hasFile('file3')){
                $clear=Clear::where('text1',$ru->code)->first();
                 $clear->text1 = $request->text1;
            $clear->text2 = $request->text2;
            $clear->text3 = $request->text3;
            $clear->sum = $request->commission;
            if ($request->hasFile('file1')) {
                $file = $request->file('file1');
                $file_name = $file->getClientOriginalName(). "_" . time() . "." . $file->getClientOriginalExtension();
                $destination_path = 'files/clear';
                $file->move($destination_path, $file_name);
                $clear->file1 = '/' . $file_name;
            }
            if ($request->hasFile('file2')) {
                $file = $request->file('file2');
                $file_name =  $file->getClientOriginalName() . "_" . time() . "." . $file->getClientOriginalExtension();
                $destination_path = 'files/clear';
                $file->move($destination_path, $file_name);
                $clear->file2 = '/' . $file_name;
            }
    
            if ($request->hasFile('file3')) {
                $file = $request->file('file3');
                $file_name =  $file->getClientOriginalName() . "_" . time() . "." . $file->getClientOriginalExtension();
                $destination_path = 'files/clear';
                $file->move($destination_path, $file_name);
                $clear->file3 = '/' . $file_name;
            }
        }
        
            return response()->json([
            'status' => '1',

            'message' => 'ویرایش درخواست انجام شد',

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }
    public function editClear(Request $request,$id){
      
            $clear = Clear::where('id', $id)->first();
             $clear->text1 = $request->text1;
        $clear->text2 = $request->text2;
        $clear->text3 = $request->text3;
        $clear->sum = $request->sum;
        if ($request->hasFile('file1')) {
            $file = $request->file('file1');
            $file_name = $file->getClientOriginalName(). "_" . time() . "." . $file->getClientOriginalExtension();
            $destination_path = 'files/clear';
            $file->move($destination_path, $file_name);
            $clear->file1 = '/' . $file_name;
        }
        if ($request->hasFile('file2')) {
            $file = $request->file('file2');
            $file_name =  $file->getClientOriginalName() . "_" . time() . "." . $file->getClientOriginalExtension();
            $destination_path = 'files/clear';
            $file->move($destination_path, $file_name);
            $clear->file2 = '/' . $file_name;
        }

        if ($request->hasFile('file3')) {
            $file = $request->file('file3');
            $file_name =  $file->getClientOriginalName() . "_" . time() . "." . $file->getClientOriginalExtension();
            $destination_path = 'files/clear';
            $file->move($destination_path, $file_name);
            $clear->file3 = '/' . $file_name;
        }

        $clear->save();
            return response()->json([
            'status' => '1',

            'message' => 'ویرایش درخواست انجام شد',

        ],

            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }
    
        public function register2(Request $request)
    {
        $my = Auth::user();

        $changes=User2::where('user_id',$my->id)->where('status',0)->get();
        foreach($changes as $ch){
            $ch->status=-1;
            $ch->save();
        }
        $user=new User2();

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
        $user->specials=$request->specials;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image_name = $request->mobile  . "_" . time() . "." . $image->getClientOriginalExtension();
            $destination_path = 'files/user';
            $image->move($destination_path, $image_name);
            $user->image = '/' . $image_name;
        }else{
            $user->image=$my->image;
        }
        if ($request->hasFile('national_image')) {
            $image = $request->file('national_image');
            $image_name = "national_".$request->mobile  . "_" . time() . "." . $image->getClientOriginalExtension();
            $destination_path = 'files/user';
            $image->move($destination_path, $image_name);
            $user->national_image = '/' . $image_name;
        }
        else{
            $user->national_image=$my->national_image;
        }
        $user->user_id=$my->id;
        $user->status=0;
        $user->save();

        return response()->json([
            'status' => '1',
            'message' => 'درخواست تغییر پروفایل شما ثبت شد.برای تائید با ادمین در تماس باشید.',
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }
    public function registerAccept(Request $req,$id){
        $user=User2::find($id);
        $user->status=$req->status;
        $user->save();
        if($req->status==1){
        $original=User::find($user->user_id);
        $original->name=$user->name;
        $original->family=$user->family;
        $original->mobile=$user->mobile;
        $original->national_code=$user->national_code;
        $original->birth_city=$user->birth_city;
        $original->address=$user->address;
        $original->sarbazi=$user->sarbazi;
        $original->married=$user->married;
        $original->degree=$user->degree;
        $original->reshte=$user->reshte;
        $original->state=$user->state;
        $original->city=$user->city;
        $original->birthdate=$user->birthdate;
        $original->specials=$user->specials;
        if($user->image)
            $original->image=$user->image;
        if($user->national_image)
            $original->national_image=$user->national_image;
        $original->save();
        }
        
        if($req->status==1)
        $text='تغییر تائید شد';
        else
        $text='تغییر رد شد';
         return response()->json([
            'status' => '1',
            'message' => $text,
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }
    
    public function registerChanges(Request $req){
        
        if(isset($req->id)){
            $users=User2::where('status',0)->where('user_id',$req->id)->first();
            
            if($users){
                $users['state_model'] = State::find($users->state);
                $users['city_model'] = City::find($users->city);
                
                $currentInfo = User::find($users->user_id);
                $currentInfo['state_model'] = State::find($currentInfo->state);
                $currentInfo['city_model'] = City::find($currentInfo->city);
                $users['current_info'] = $currentInfo;
            }
        }
        else{
            $users=User2::where('status',0)->get();
            foreach($users as $user){
                $user['state_model'] = State::find($user->state);
                $user['city_model'] = City::find($user->city);
                
                $currentInfo = User::find($user->user_id);
                $currentInfo['state_model'] = State::find($currentInfo->state);
                $currentInfo['city_model'] = City::find($currentInfo->city);
                $user['current_info'] = $currentInfo;
            }
        }
        
       
         return response()->json([
            'status' => '1',
            'message' => 'list',
            'users' => $users,
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );

    }
    
    
    
    
    
    public function editReqPost(Request $request,$id)
    {
        $req = \App\Models\Request::where('id', $id)->first();
        $req->name = $request->name;
        $req->mobile = $request->mobile;
        $req->mobile2 = $request->mobile2;
        $req->state = $request->state;
//        $req->city = $request->city;
        $req->device_brand = $request->device_brand;
        $req->device_type = $request->device_type;
        $req->address = $request->address;
        $req->problem = $request->problem;
        $req->description = $request->description;
        $req->save();
        return response()->json([
            'status' => '1',
            'message' => 'ذخیره شد',
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }
    
    
        
    
      public function search(Request $request)
    {
       
       
      if(isset($request->city) && isset($request->special) ){
            $spu=Speciauser::where('special_id',$request->special)->pluck('user_id');
            $users=User::whereIn('id',$spu)->where('city',$request->city)->get();
          
      }elseif(isset($request->city)){
        $users=User::where('city',$request->city)->get(); 
        }elseif(isset($request->special)){
            $spu=Speciauser::where('special_id',$request->special)->pluck('user_id');
            $users=User::whereIn('id',$spu)->get();
        }
        else{
            $users=User::all();
        }
        return response()->json([
            'status' => '1',
            'message' => $users,
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }
    
    public function ttt(){
                $users=User2::where('status',0)->get();
        
                return $users;
    }


public function specials()
    {
        $specials=Special::orderBy('id' , "DESC")->get();
        
        if(count($specials)==0){
             return response()->json([
            'status' => '1',
            'message'=>'لیست تخصص ها',
            'specials' => $specials,

        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
        }
        
         return response()->json([
            'status' => '1',
            'message'=>'لیست تخصص ها',
            'specials' => $specials,
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }
    
    public function special(Request $req)
    {
        $special=Special::find($req->id);
        if(!$special){
             return response()->json([
            'status' => '0',
            'message'=>'تخصص',
                        'special' => $special,

        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
        }
         return response()->json([
            'status' => '1',
            'message'=>'تخصص',
            'special' => $special,
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }
    
    
    public function specialPost(Request $req)
    {
        if(isset($req->id))
            $special=Special::find($req->id);
        else
            $special=new Special();
        $special->name=$req->name;
        $special->save();
         return response()->json([
            'status' => '1',
            'message' => 'ذخیره شد',
            'model' => $special,
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }
    
        
    
    public function specialDelete(Request $req)
    {
        
            $special=Special::find($req->id);
        
        
        $special->delete();
         return response()->json([
            'status' => '1',
            'message' => 'حذف شد',
        ],
            200,
            array('Content-Type' => 'application/json;charset:utf-8;'),
            JSON_UNESCAPED_UNICODE
        );
    }

}
