<?php

namespace App\Http\Controllers\Resource;

use App\User;
use App\UserRequests;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Controller;
use Exception;
use Storage;
use Setting;

class UserResource extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('demo', ['only' => ['store', 'update','destroy']]);
        $this->perpage = Setting::get('per_page', '10');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {   

                     
        if(!empty($request->page) && $request->page=='all'){

            if(@$_GET['corporate'])
            {
                $users = User::where('corporate_id',@$_GET['corporate'])->orderBy('id' , 'asc')->get();
            }
            else
            {
                $users = User::orderBy('id' , 'asc')->get();
            }
            
            return response()->json(array('success' => true, 'data'=>$users));
        }
        else{

            if(@$_GET['corporate'])
            {
                  $users = User::where('corporate_id',@$_GET['corporate'])->orderBy('created_at' , 'desc')->paginate($this->perpage);
            }
            else
            {
                 $users = User::orderBy('created_at' , 'desc')->paginate($this->perpage);
            }
           
            $pagination=(new Helper)->formatPagination($users);
            return view('admin.users.index', compact('users','pagination'));
        }

        
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|unique:users,email|email|max:255',
            'mobile' => 'digits_between:6,13',
            'picture' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
            'password' => 'required|min:6|confirmed',
        ]);

        try{

            $user = $request->all();

            $user['payment_mode'] = 'CASH';
            $user['password'] = bcrypt($request->password);
            $user['hit_from'] = 'UserResource.store';
            if($request->hasFile('picture')) {
                $user['picture'] = $request->picture->store('user/profile');
            }

            $user = User::create($user);

            return back()->with('flash_success', trans('admin.user_msgs.user_saved'));

        } 

        catch (Exception $e) {
            return back()->with('flash_error', trans('admin.user_msgs.user_not_found'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            return view('admin.users.user-details', compact('user'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $user = User::findOrFail($id);
            return view('admin.users.edit',compact('user'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'mobile' => 'digits_between:6,13',
            'picture' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
        ]);

        try {

            $user = User::findOrFail($id);

            if($request->hasFile('picture')) {
                Storage::delete($user->picture);
                $user->picture = $request->picture->store('user/profile');
            }

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->mobile = $request->mobile;
            $user->save();

            return redirect()->route('admin.user.index')->with('flash_success', trans('admin.user_msgs.user_update'));    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_error', trans('admin.user_msgs.user_not_found'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        
        try {

            User::find($id)->delete();
            return back()->with('message', trans('admin.user_msgs.user_delete'));
        } 
        catch (Exception $e) {
            return back()->with('flash_error', trans('admin.user_msgs.user_not_found'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function request($id){

        try{

            $requests = UserRequests::where('user_requests.user_id',$id)
                    ->RequestHistory()                    
                    ->paginate($this->perpage);

            $pagination=(new Helper)->formatPagination($requests);        

            return view('admin.request.index', compact('requests','pagination'));
        }

        catch (Exception $e) {
             return back()->with('flash_error', trans('admin.something_wrong'));
        }

    }

}
