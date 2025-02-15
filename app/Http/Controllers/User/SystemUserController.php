<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\EmailTemplate;
use App\Models\Invite;
use App\Models\Role;
use App\Models\User;
use App\Notifications\InviteUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Validator;

class SystemUserController extends Controller {

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->middleware(function ($request, $next) {

			$route_name = request()->route()->getName();
			if ($route_name == 'system_users.send_invitation') {
				$user = request()->activeBusiness->user;
				if (has_limit('business_users', 'user_limit', true, "owner_id = $user->id AND user_id != $user->id") <= 0) {
					if (!$request->ajax()) {
						return back()->with('error', _lang('Sorry, Your have already reached your package quota !'));
					} else {
						return response()->json(['result' => 'error', 'message' => _lang('Sorry, Your have already reached your package quota !')]);
					}
				}
			}

			return $next($request);
		});
	}

	public function change_role(Request $request, $userId, $businessId) {
		if (!$request->ajax()) {return back();}

		if ($request->isMethod('get')) {
			$business = Business::owner()->find($businessId);
			$user = $business->users->find($userId);
			return view('backend.user.system_user.modal.edit', compact('user', 'business'));
		} else {
			$validator = Validator::make($request->all(), [
				'business_id' => 'required',
				'role_id' => 'required',
			]);

			if ($validator->fails()) {
				return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
			}

			$business = Business::owner()->find($request->business_id);
			$role = Role::find($request->role_id);

			if (!$business) {
				return response()->json(['result' => 'error', 'message' => _lang('No business found')]);
			}

			$business->users()->detach($userId);
			$business->users()->attach($userId, [
				'owner_id' => $business->user_id,
				'role_id' => $role->id,
				'is_active' => 1,
			]);

			// audit log
			$audit = new AuditLog();
			$audit->date_changed = date('Y-m-d H:i:s');
			$audit->changed_by = auth()->user()->id;
			$audit->event = $business->users->find($userId)->name . ' ' . _lang('Role Changed to') . ' ' . $role->name;
			$audit->save();

			return response()->json(['result' => 'success', 'message' => _lang('Role Updated Successfully')]);

		}
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function invite(Request $request, $businessId) {
		if (!$request->ajax()) {
			return back();
		} else {
			return view('backend.user.system_user.modal.create', compact('businessId'));
		}
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function send_invitation(Request $request) {
		$validator = Validator::make($request->all(), [
			'email' => 'required|email|max:191',
			'business_id' => 'required',
			'role_id' => 'required',
		]);

		if ($validator->fails()) {
			if ($request->ajax()) {
				return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
			} else {
				return redirect()->route('system_users.create')
					->withErrors($validator)
					->withInput();
			}
		}

		$business = Business::find($request->business_id);
		if ($business->users->where('email', $request->email)->first()) {
			return response()->json(['result' => 'error', 'message' => _lang('User is already assigned to your business')]);
		}

		$template = EmailTemplate::where('slug', 'INVITE_USER')->where('email_status', 1)->first();
		if (!$template) {
			return response()->json(['result' => 'error', 'message' => _lang('Sorry, Email template is disabled ! Contact with your administrator.')]);
		}

		$user = User::where('email', $request->email)->first();

		if(User::where('email', $request->email)->exists()) {
			return redirect()->back()->with('error', _lang('User already exists'));
		}

		if ($user) {
			$invite = Invite::updateOrCreate([
				'email' => $request->email,
				'sender_id' => auth()->id(),
				'business_id' => $request->business_id,
				'role_id' => $request->role_id,
				'user_id' => $user->id,
				'status' => 1,
			]);

			$invite->message = $request->message;
			$invite->save();
		} else {
			$invite = Invite::updateOrCreate([
				'email' => $request->email,
				'sender_id' => auth()->id(),
				'business_id' => $request->business_id,
				'role_id' => $request->role_id,
				'user_id' => null,
				'status' => 1,
			]);

			$invite->message = $request->message;
			$invite->save();
		}

		// audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
		$audit->event = 'Invitation Sent to ' . $invite->email;
        $audit->save();

		try {
			//Send Email Notification
			Notification::send($invite, new InviteUser($invite));
		} catch (\Exception $e) {
			return response()->json(['result' => 'error', 'message' => $e->getMessage()]);
		}

		if (!$request->ajax()) {
			return redirect()->back()->with('success', _lang('Invitations have been sent'));
		} else {
			return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Invitations have been sent'), 'data' => $user, 'table' => '#users_table']);
		}

	}

	public function accept_invitation($id) {
		$id = decrypt($id);

		DB::beginTransaction();

		$invite = Invite::active()->find($id);
		if (!$invite) {
			return redirect()->route('login')->with('error', _lang('Invitation has been invalid or expired!'));
		}
		$invite->status = 0;

		if ($invite->user_id == null) {
			$user = new User();
			$user->name = explode('@', $invite->email)[0];
			$user->email = $invite->email;
			$user->user_type = 'user';
			$user->status = 1;
			$user->profile_picture = 'default.png';
			$user->password = null;
			$user->save();

			$invite->user_id = $user->id;
		} else {
			$user = User::find($invite->user_id);
		}
		$invite->save();

		//Store Business User
		$business = Business::withoutGlobalScopes()->find($invite->business_id);
		$business->users()->detach($invite->user_id);
		$business->users()->attach($invite->user_id, [
			'owner_id' => $business->user_id,
			'role_id' => $invite->role_id,
			'is_active' => 1,
		]);

		DB::commit();

		Auth::login($user, true);

		// audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
		$audit->event = 'Invitation Accepted by ' . $user->name;
        $audit->save();

		if ($user->password == NULL) {
			return redirect()->route('profile.change_password')->with('success', _lang('Invitation Accepted. Please create your password for further login'));
		} else {
			return redirect()->route('dashboard.index')->with('success', _lang('Invitation Accepted'));
		}
	}

	public function invitation_history($businessId) {
		$assets = ['datatable'];
		$invitation_list = Invite::where('business_id', $businessId)->orderBy('id', 'desc')->get();
		return view('backend.user.business.invitation_history', compact('invitation_list', 'businessId', 'assets'));
	}

	public function destroy_invitation($id) {
		$invite = Invite::find($id);

		// audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
		$audit->event = 'Invitation Deleted' . ' ' . $invite->email;
        $audit->save();

		$invite->delete();
		return back()->with('success', _lang('Deleted Sucessfully'));
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id) {
		$user = User::staff()->find($id);

		// audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
		$audit->event = 'User Deleted' . ' ' . $user->name;
        $audit->save();

		$user->business()->detach();
		return back()->with('success', _lang('Deleted Sucessfully'));
	}
}