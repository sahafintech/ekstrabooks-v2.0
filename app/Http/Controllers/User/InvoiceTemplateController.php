<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\InvoiceTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InvoiceTemplateController extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware(function ($request, $next) {
            if (package()->invoice_builder != 1) {
                if (!$request->ajax()) {
                    return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
                } else {
                    return response()->json(['result' => 'error', 'message' => _lang('Sorry, This module is not available in your current package !')]);
                }
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $assets           = ['datatable'];
        $invoicetemplates = InvoiceTemplate::all()->sortByDesc("id");
        return view('backend.user.invoice_template.list', compact('invoicetemplates', 'assets'));
    }

    public function get_element($element) {
        require resource_path("views/backend/user/invoice_template/elements/$element.php");

        $option_fields = create_option_field(option_fields());

        return json_encode(
            array(
                'element'       => element(),
                'option_fields' => $option_fields,
            )
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {
        return view('backend.user.invoice_template.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'          => [
                'required',
                'string',
                Rule::unique('invoice_templates')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->activeBusiness->user_id)
                        ->where('business_id', $request->activeBusiness->id)
                        ->where('type', $request->template_type);
                }),
            ],
            'template_type' => 'required',
            'body'          => 'required',
            'editor'        => 'required',
        ], [
            'body.required'   => 'Content is required',
            'editor.required' => 'Content is required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('invoice_templates.create')
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $invoicetemplate             = new InvoiceTemplate();
        $invoicetemplate->name       = $request->input('name');
        $invoicetemplate->type       = $request->input('template_type');
        $invoicetemplate->body       = $request->input('body');
        $invoicetemplate->editor     = $request->input('editor');
        $invoicetemplate->custom_css = $request->input('custom_css');

        $invoicetemplate->save();

        if (!$request->ajax()) {
            return redirect()->route('invoice_templates.create')->with('success', _lang('Saved Sucessfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'store', 'message' => _lang('Saved Sucessfully'), 'data' => $invoicetemplate]);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id) {
        $invoicetemplate = InvoiceTemplate::find($id);
        return view('backend.user.invoice_template.view', compact('invoicetemplate', 'id'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id) {
        $invoicetemplate = InvoiceTemplate::find($id);
        return view('backend.user.invoice_template.edit', compact('invoicetemplate', 'id'));
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
            'name'          => [
                'required',
                'string',
                Rule::unique('invoice_templates')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->activeBusiness->user_id)
                        ->where('business_id', $request->activeBusiness->id)
                        ->where('type', $request->template_type);
                })->ignore($id),
            ],
            'template_type' => 'required',
            'body'          => 'required',
            'editor'        => 'required',
        ], [
            'body.required'   => 'Content is required',
            'editor.required' => 'Content is required',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['result' => 'error', 'message' => $validator->errors()->all()]);
            } else {
                return redirect()->route('invoice_templates.edit', $id)
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $invoicetemplate             = InvoiceTemplate::find($id);
        $invoicetemplate->name       = $request->input('name');
        $invoicetemplate->type       = $request->input('template_type');
        $invoicetemplate->body       = $request->input('body');
        $invoicetemplate->editor     = $request->input('editor');
        $invoicetemplate->custom_css = $request->input('custom_css');

        $invoicetemplate->save();

        if (!$request->ajax()) {
            return redirect()->route('invoice_templates.index')->with('success', _lang('Updated Sucessfully'));
        } else {
            return response()->json(['result' => 'success', 'action' => 'update', 'message' => _lang('Updated Sucessfully'), 'data' => $invoicetemplate]);
        }

    }

    /**
     * Clone Template
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function clone (Request $request, $id) {
        $invoicetemplate       = InvoiceTemplate::find($id)->replicate();
        $invoicetemplate->name = $invoicetemplate->name . '-' . uniqid();
        $invoicetemplate->save();
        return redirect()->route('invoice_templates.edit', $invoicetemplate->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $invoicetemplate = InvoiceTemplate::find($id);
        $invoicetemplate->delete();
        return redirect()->route('invoice_templates.index')->with('success', _lang('Deleted Sucessfully'));
    }
}
