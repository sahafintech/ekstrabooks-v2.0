<?php

namespace App\Http\Controllers;

use App\Models\Package;
use DataTables;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        date_default_timezone_set(get_option('timezone', 'Asia/Dhaka'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $assets = ['datatable'];
        $users = User::where('user_type', 'user')
            ->with('package')->get();
        return view('backend.admin.user.list', compact('users', 'assets'));
    }

    public function get_table_data() {
        $users = User::where('user_type', 'user')
            ->with('package');

        return Datatables::eloquent($users)
            ->editColumn('name', function ($user) {
                return '<div class="d-flex align-items-center">'
                . '<img src="' . profile_picture($user->profile_picture) . '" class="thumb-sm img-thumbnail rounded-circle mr-3">'
                . '<div><span class="d-block text-height-0"><b>' . $user->name . '</b></span><span class="d-block">' . $user->email . '</span></div>'
                    . '</div>';
            })
            ->filterColumn('name', function ($query, $keyword) {
                return $query->where("name", "like", "{$keyword}%")
                    ->orWhere("email", "like", "{$keyword}%");
            }, true)
            ->editColumn('package.name', function ($user) {
                return $user->package->name != null ? $user->package->name . ' (' . ucwords($user->package->package_type) . ')' : '';
            })
            ->editColumn('membership_type', function ($user) {
                if ($user->membership_type == 'member') {
                    return show_status(ucwords($user->membership_type), 'success');
                } else {
                    return show_status(ucwords($user->membership_type), 'danger');
                }
            })
            ->editColumn('status', function ($user) {
                return status($user->status);
            })
            ->addColumn('action', function ($user) {
                return '<div class="dropdown text-center">'
                . '<button class="btn btn-outline-primary btn-xs dropdown-toggle" type="button" data-toggle="dropdown">' . _lang('Action') . '</button>'
                . '<div class="dropdown-menu">'
                . '<a class="dropdown-item" href="' . route('users.edit', $user['id']) . '"><i class="ti-pencil-alt"></i> ' . _lang('Edit') . '</a>'
                . '<a class="dropdown-item" href="' . route('users.show', $user['id']) . '"><i class="ti-eye"></i>  ' . _lang('View') . '</a>'
                . '<a class="dropdown-item" href="' . route('users.login_as_user', $user->id) . '"><i class="ti-user"></i>  ' . _lang('Login as User') . '</a>'
                . '<form action="' . route('users.destroy', $user['id']) . '" method="post">'
                . csrf_field()
                . '<input name="_method" type="hidden" value="DELETE">'
                . '<button class="dropdown-item btn-remove" type="submit"><i class="ti-trash"></i> ' . _lang('Delete') . '</button>'
                    . '</form>'
                    . '</div>'
                    . '</div>';
            })
            ->setRowId(function ($user) {
                return "row_" . $user->id;
            })
            ->rawColumns(['name', 'membership_type', 'status', 'valid_to', 'action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
        $alert_col = 'col-lg-10 offset-lg-1';
        return view('backend.admin.user.create', compact('alert_col'));

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|max:60',
            'email'           => 'required|email|unique:users|max:191',
            'status'          => 'required',
            'profile_picture' => 'nullable|image',
            'password'        => 'required|min:6',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('users.create')
                    ->withErrors($validator)
                    ->withInput();
            }
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

        if ($user->id > 0) {
            return redirect()->route('users.create')->with('success', _lang('Saved Sucessfully'));
        } else {
            return redirect()->route('users.create')->with('error', _lang('Error Occured. Please try again'));
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id) {
        $user = User::find($id);
        return view('backend.admin.user.view', compact('user', 'id'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
        $alert_col = 'col-lg-10 offset-lg-1';
        $user      = User::find($id);
        return view('backend.admin.user.edit', compact('user', 'id', 'alert_col'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
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
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('users.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        if ($request->hasfile('profile_picture')) {
            $file            = $request->file('profile_picture');
            $profile_picture = time() . $file->getClientOriginalName();
            $file->move(public_path() . "/uploads/profile/", $profile_picture);
        }

        $package = Package::find($request->input('package_id'));

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

    public function login_as_user($id) {
        $user = User::find($id);
        session(['login_as_user' => true, 'admin' => auth()->user()]);
        Auth::login($user);
        return redirect()->route('dashboard.index');
    }

    public function back_to_admin() {
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
    public function destroy($id) {
        DB::beginTransaction();

        $user = User::find($id);
        Product::where('user_id', $id)->delete();
        $user->delete();
        
        DB::commit();
        return redirect()->route('users.index')->with('success', _lang('Deleted Sucessfully'));
    }
}