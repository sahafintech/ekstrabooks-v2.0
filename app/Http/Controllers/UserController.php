<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class UserController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        date_default_timezone_set(get_option('timezone', 'Asia/Dhaka'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::where('user_type', 'user')
            ->with('package')->get();
        return Inertia::render('Backend/Admin/Users/List', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        return Inertia::render('Backend/Admin/Users/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|max:60',
            'email'           => 'required|email|unique:users|max:191',
            'status'          => 'required',
            'profile_picture' => 'nullable|image',
            'password'        => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return redirect()->route('users.create')
                ->withErrors($validator)
                ->withInput();
        }

        $profile_picture = "default.png";
        if ($request->hasfile('profile_picture')) {
            $file            = $request->file('profile_picture');
            $profile_picture = rand() . time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/profile/", $profile_picture);
        }

        $user                    = new User();
        $user->name              = $request->input('name');
        $user->email             = $request->input('email');
        $user->user_type         = 'user';
        $user->status            = $request->input('status');
        $user->profile_picture   = $profile_picture;
        $user->password          = Hash::make($request->password);
        $user->phone             = $request->input('phone');
        $user->city              = $request->input('city');
        $user->state             = $request->input('state');
        $user->zip               = $request->input('zip');
        $user->address           = $request->input('address');
        $user->save();

        return redirect()->route('users.index')->with('success', _lang('Saved Sucessfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $user = User::find($id);
        return Inertia::render('Backend/Admin/Users/View', compact('user', 'id'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $user      = User::find($id);
        return Inertia::render('Backend/Admin/Users/Edit', compact('user', 'id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|max:191',
            'email'           => [
                'required',
                'email',
                Rule::unique('users')->ignore($id),
            ],
            'status'          => 'required',
            'profile_picture' => 'nullable|image',
            'password'        => 'nullable|min:6',
        ]);

        if ($validator->fails()) {
            return redirect()->route('users.edit', $id)
                ->withErrors($validator)
                ->withInput();
        }

        if ($request->hasfile('profile_picture')) {
            $file            = $request->file('profile_picture');
            $profile_picture = time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/profile/", $profile_picture);
        }

        $user                  = User::find($id);
        $user->name            = $request->input('name');
        $user->email           = $request->input('email');
        $user->status          = $request->input('status');

        if ($request->hasfile('profile_picture')) {
            $user->profile_picture = $profile_picture;
        }

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->phone   = $request->input('phone');
        $user->city    = $request->input('city');
        $user->state   = $request->input('state');
        $user->zip     = $request->input('zip');
        $user->address = $request->input('address');
        $user->save();

        return redirect()->route('users.index')->with('success', _lang('Updated Sucessfully'));
    }

    public function login_as_user($id)
    {
        $user = User::find($id);
        session(['login_as_user' => true, 'admin' => auth()->user()]);
        Auth::login($user);
        return redirect()->route('dashboard.index');
    }

    public function back_to_admin()
    {
        if (session('login_as_user') == true && session('admin') != null) {
            Auth::login(session('admin'));
            session(['login_as_user' => null, 'admin' => null]);
            return redirect()->route('dashboard.index');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        $user = User::find($id);
        Product::where('user_id', $id)->delete();
        $user->delete();

        DB::commit();
        return redirect()->route('users.index')->with('success', _lang('Deleted Sucessfully'));
    }
}
