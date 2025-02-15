<?php

namespace App\Http\Controllers;

use App\Models\AccountType;
use Illuminate\Http\Request;

class AccountTypeController extends Controller
{
    public function index() {
        $account_types = AccountType::all();
        return view('backend.admin.account_type.list', compact('account_types'));
    }

    public function create() {
        return view('backend.admin.account_type.create');
    }

    public function store(Request $request) {
        $request->validate([
            'main' => 'required',
            'type' => 'required'
        ]);

        $account_type = new AccountType();
        $account_type->main = $request->main;
        $account_type->type = $request->type;
        $account_type->save();

        return redirect()->route('account_types.index')->with('success', 'Account Type Saved Successfully');
    }

    public function edit($id) {
        $account_type = AccountType::find($id);
        return view('backend.admin.account_type.edit', compact('account_type', 'id'));
    }

    public function update(Request $request, $id) {
        $request->validate([
            'main' => 'required',
            'type' => 'required'
        ]);

        $account_type = AccountType::find($id);
        $account_type->main = $request->main;
        $account_type->type = $request->type;
        $account_type->save();

        return redirect()->route('account_types.index')->with('success', 'Account Type Updated Successfully');
    }

    public function destroy($id) {
        $account_type = AccountType::find($id);
        $account_type->delete();

        return redirect()->route('account_types.index')->with('success', 'Account Type Deleted Successfully');
    }
}
