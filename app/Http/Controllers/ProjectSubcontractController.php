<?php

namespace App\Http\Controllers;

use App\Exports\ProjectSubcontractExport;
use App\Http\Controllers\Controller;
use App\Imports\ProjectSubcontractImport;
use App\Models\Account;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\BusinessSetting;
use App\Models\CostCode;
use App\Models\Currency;
use App\Models\EmailTemplate;
use App\Models\PendingTransaction;
use App\Models\Project;
use App\Models\ProjectSubcontract;
use App\Models\ProjectSubcontractPayment;
use App\Models\ProjectSubcontractTask;
use App\Models\ProjectSubcontractTax;
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\TransactionMethod;
use App\Models\Vendor;
use App\Notifications\SendProjectSubcontract;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Validator;

class ProjectSubcontractController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware(function ($request, $next) {
            if (package()->construction_module != 1) {
                return back()->with('error', _lang('Sorry, This module is not available in your current package !'));
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 50);
        $sorting = $request->get('sorting', []);
        $sortColumn = $sorting['column'] ?? 'id';
        $sortDirection = $sorting['direction'] ?? 'desc';
        $vendorId = $request->get('vendor_id', '');
        $dateRange = $request->get('date_range', '');
        $contractStatus = $request->get('contract_status', '');
        $status = $request->get('status', '');

        $query = ProjectSubcontract::with('project', 'vendor');

        // Handle sorting
        if ($sortColumn === 'vendor.name') {
            $query->join('vendors', 'project_subcontracts.vendor_id', '=', 'vendors.id')
                ->orderBy('vendors.name', $sortDirection)
                ->select('project_subcontracts.*');
        } else {
            $query->orderBy('project_subcontracts.' . $sortColumn, $sortDirection);
        }

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('project_subcontracts.subcontract_no', 'like', "%$search%")
                    ->orWhere('project_subcontracts.title', 'like', "%$search%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%$search%");
                    });
            });
        }

        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        if ($dateRange) {
            $query->whereDate('start_date', '>=', Carbon::parse($dateRange[0])->format('Y-m-d'))
                ->whereDate('end_date', '<=', Carbon::parse($dateRange[1])->format('Y-m-d'));
        }

        if ($contractStatus) {
            $query->where('contract_status', $contractStatus);
        }

        if ($status) {
            $query->where('status', $status);
        }

        // Get summary data before pagination
        $summaryQuery = clone $query;
        $summary = [
            'total_subcontracts' => $summaryQuery->count(),
            'total_amount' => $summaryQuery->sum('grand_total'),
            'total_paid' => $summaryQuery->sum('paid'),
        ];

        $subcontracts = $query->with('vendor')->paginate($perPage)->withQueryString();
        $vendors = Vendor::all();

        return Inertia::render('Backend/User/Construction/Project/Subcontract/List', [
            'subcontracts' => $subcontracts->items(),
            'meta' => [
                'current_page' => $subcontracts->currentPage(),
                'per_page' => $subcontracts->perPage(),
                'last_page' => $subcontracts->lastPage(),
                'total' => $subcontracts->total(),
            ],
            'filters' => [
                'search' => $search,
                'vendor_id' => $vendorId,
                'date_range' => $dateRange,
                'contract_status' => $contractStatus,
                'status' => $status,
                'sorting' => $sorting,
            ],
            'vendors' => $vendors,
            'summary' => $summary,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $vendors = Vendor::orderBy('id', 'desc')
            ->get();

        $costCodes = CostCode::orderBy('id', 'desc')
            ->get();

        $taxes = Tax::orderBy('id', 'desc')
            ->get();

        $projects = Project::orderBy('id', 'desc')->with('tasks')->get();

        $currencies = Currency::orderBy('id', 'desc')
            ->get();

        $accounts = Account::orderBy('id', 'desc')
            ->get();

        return Inertia::render('Backend/User/Construction/Project/Subcontract/Create', [
            'vendors' => $vendors,
            'costCodes' => $costCodes,
            'taxes' => $taxes,
            'projects' => $projects,
            'currencies' => $currencies,
            'base_currency' => $request->activeBusiness->currency,
            'accounts' => $accounts,
        ]);
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
            'vendor_id' => 'required',
            'subcontract_no' => 'required',
            'project_id' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|after_or_equal:start_date',
            'project_task_id' => 'required',
            'description' => 'nullable',
        ], [
            'project_task_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('project_subcontracts.create')
                ->withErrors($validator)
                ->withInput();
        }


        $default_accounts = ['Accounts Payable', 'Purchase Tax Payable', 'Purchase Discount Allowed', 'Inventory'];

        // if these accounts are not exists then create it
        foreach ($default_accounts as $account) {
            if (!Account::where('account_name', $account)->where('business_id', $request->activeBusiness->id)->exists()) {
                $account_obj = new Account();
                if ($account == 'Accounts Payable') {
                    $account_obj->account_code = '2100';
                } elseif ($account == 'Purchase Tax Payable') {
                    $account_obj->account_code = '2201';
                } elseif ($account == 'Purchase Discount Allowed') {
                    $account_obj->account_code = '6003';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_code = '1000';
                }
                $account_obj->account_name = $account;
                if ($account == 'Accounts Payable') {
                    $account_obj->account_type = 'Current Liability';
                } elseif ($account == 'Purchase Tax Payable') {
                    $account_obj->account_type = 'Current Liability';
                } elseif ($account == 'Purchase Discount Allowed') {
                    $account_obj->account_type = 'Cost Of Sale';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_type = 'Other Current Asset';
                }
                if ($account == 'Accounts Payable') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Purchase Tax Payable') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Purchase Discount Allowed') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Inventory') {
                    $account_obj->dr_cr   = 'dr';
                }
                $account_obj->business_id = $request->activeBusiness->id;
                $account_obj->user_id     = $request->activeBusiness->user->id;
                $account_obj->opening_date   = now()->format('Y-m-d');
                $account_obj->save();
            }
        }

        $month = Carbon::parse($request->start_date)->format('F');
        $year = Carbon::parse($request->start_date)->format('Y');
        $today = now()->format('d');

        // financial year
        $financial_year = BusinessSetting::where('name', 'fiscal_year')->first()->value;
        $end_month = explode(',', $financial_year)[1];
        $start_day = BusinessSetting::where('name', 'start_day')->first()->value;
        $end_day = $start_day + 5;

        // if login as this user dont check the financial year
        if (false) {
            if (($month !== now()->format('F') || $year !== now()->format('Y')) || ($today <= $end_day && $month == now()->subMonth()->format('F') && $year == now()->format('Y')) || ($today <= 25 && $month == $end_month && $year == now()->subYear()->format('Y'))) {
                return redirect()->back()->withInput()->with('error', _lang('Period Closed'));
            }
        }

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $projectSubcontract = new ProjectSubcontract();
        $projectSubcontract->vendor_id = $request->input('vendor_id');
        $projectSubcontract->subcontract_no = $request->input('subcontract_no');
        $projectSubcontract->project_id = $request->input('project_id');
        $projectSubcontract->start_date = Carbon::parse($request->input('start_date'))->format('Y-m-d');
        $projectSubcontract->end_date = Carbon::parse($request->input('end_date'))->format('Y-m-d');
        $projectSubcontract->description = $request->input('description');
        $projectSubcontract->sub_total = $summary['subTotal'];
        $projectSubcontract->grand_total = $summary['grandTotal'];
        $projectSubcontract->converted_total = $request->input('converted_total');
        $projectSubcontract->exchange_rate   = $request->input('exchange_rate');
        $projectSubcontract->currency   = $request->input('currency');
        $projectSubcontract->paid = 0;
        $projectSubcontract->discount = $summary['discountAmount'];
        $projectSubcontract->discount_type = $request->input('discount_type');
        $projectSubcontract->discount_value = $request->input('discount_value') ?? 0;
        $projectSubcontract->status = 0;
        if (has_permission('project_subcontracts.approve') || request()->isOwner) {
            $projectSubcontract->contract_status = 1;
        } else {
            $projectSubcontract->contract_status = 0;
        }
        $projectSubcontract->created_by = auth()->user()->id;
        if (has_permission('project_subcontracts.approve') || request()->isOwner) {
            $projectSubcontract->approved_by = auth()->user()->id;
        } else {
            $projectSubcontract->approved_by = null;
        }
        $projectSubcontract->short_code = rand(100000, 9999999) . uniqid();

        $projectSubcontract->save();


        // if attachments then upload
        if (isset($request->attachments)) {
            if ($request->attachments != null) {
                for ($i = 0; $i < count($request->attachments); $i++) {
                    $theFile = $request->file("attachments.$i.file");
                    if ($theFile == null) {
                        continue;
                    }
                    $theAttachment = rand() . time() . $theFile->getClientOriginalName();
                    $theFile->move(public_path() . "/uploads/media/attachments/", $theAttachment);

                    $attachment = new Attachment();
                    $attachment->file_name = $request->attachments[$i]['file_name'];
                    $attachment->path = "/uploads/media/attachments/" . $theAttachment;
                    $attachment->ref_type = 'project subcontract';
                    $attachment->ref_id = $projectSubcontract->id;
                    $attachment->save();
                }
            }
        }

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->project_task_id); $i++) {
            $projectSubcontract->tasks()->save(new ProjectSubcontractTask([
                'project_subcontract_id' => $projectSubcontract->id,
                'project_task_id' => $request->project_task_id[$i],
                'cost_code_id' => $request->cost_code_id[$i],
                'uom' => $request->uom[$i],
                'quantity' => $request->quantity[$i],
                'unit_cost' => $request->unit_cost[$i],
                'sub_total' => ($request->unit_cost[$i] * $request->quantity[$i]),
                'account_id' => $request->account_id[$i],
            ]));

            if (has_permission('project_subcontracts.approve') || request()->isOwner) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $request->account_id[$i];
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($request->unit_cost[$i] * $request->quantity[$i])));
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($request->unit_cost[$i] * $request->quantity[$i]));
                $transaction->description = _lang('Project Subcontract') . ' #' . $projectSubcontract->subcontract_no;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->ref_type    = 'project subcontract';
                $transaction->save();
            } else {
                $transaction              = new PendingTransaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $request->account_id[$i];
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($request->unit_cost[$i] * $request->quantity[$i])));
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($request->unit_cost[$i] * $request->quantity[$i]));
                $transaction->description = _lang('Project Subcontract') . ' #' . $projectSubcontract->subcontract_no;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->ref_type    = 'project subcontract';
                $transaction->save();
            }
        }

        if (isset($request->taxes)) {
            foreach ($request->taxes as $taxId) {
                $tax = Tax::find($taxId);

                $projectSubcontract->taxes()->save(new ProjectSubcontractTax([
                    'project_subcontract_id' => $projectSubcontract->id,
                    'tax_id' => $taxId,
                    'name' => $tax->name . ' ' . $tax->rate . ' %',
                    'amount' => ($projectSubcontract->sub_total / 100) * $tax->rate,
                ]));

                if (has_permission('project_subcontracts.approve') || request()->isOwner) {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $projectSubcontract->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($projectSubcontract->sub_total / $projectSubcontract->exchange_rate) / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($projectSubcontract->sub_total / $projectSubcontract->exchange_rate) / 100) * $tax->rate);
                    $transaction->description = _lang('Project Subcontract Tax') . ' #' . $projectSubcontract->subcontract_no;
                    $transaction->ref_id      = $projectSubcontract->id;
                    $transaction->ref_type    = 'project subcontract tax';
                    $transaction->tax_id      = $tax->id;
                    $transaction->save();
                } else {
                    $transaction              = new PendingTransaction();
                    $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $projectSubcontract->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($projectSubcontract->sub_total / $projectSubcontract->exchange_rate) / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($projectSubcontract->sub_total / $projectSubcontract->exchange_rate) / 100) * $tax->rate);
                    $transaction->description = _lang('Project Subcontract Tax') . ' #' . $projectSubcontract->subcontract_no;
                    $transaction->ref_id      = $projectSubcontract->id;
                    $transaction->ref_type    = 'project subcontract tax';
                    $transaction->tax_id      = $tax->id;
                    $transaction->save();
                }
            }
        }

        DB::commit();

        if (has_permission('project_subcontracts.approve') || request()->isOwner) {
            if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = get_account('Accounts Payable')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']));
                $transaction->ref_type    = 'project subcontract';
                $transaction->vendor_id   = $projectSubcontract->vendor_id;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->description = 'Project Subcontract Payable #' . $projectSubcontract->subcontract_no;
                $transaction->save();
            } else {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = get_account('Accounts Payable')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
                $transaction->ref_type    = 'project subcontract';
                $transaction->vendor_id   = $projectSubcontract->vendor_id;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->description = 'Project Subcontract Payable #' . $projectSubcontract->subcontract_no;
                $transaction->save();
            }

            if ($request->input('discount_value') > 0) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Purchase Discount Allowed')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->description = _lang('Project Subcontract Discount') . ' #' . $projectSubcontract->subcontract_no;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->ref_type    = 'project subcontract';
                $transaction->vendor_id   = $projectSubcontract->vendor_id;
                $transaction->save();
            }
        } else {
            if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
                $transaction              = new PendingTransaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = get_account('Accounts Payable')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']));
                $transaction->ref_type    = 'project subcontract';
                $transaction->vendor_id   = $projectSubcontract->vendor_id;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->description = 'Project Subcontract Payable #' . $projectSubcontract->subcontract_no;
                $transaction->save();
            } else {
                $transaction              = new PendingTransaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = get_account('Accounts Payable')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
                $transaction->ref_type    = 'project subcontract';
                $transaction->vendor_id   = $projectSubcontract->vendor_id;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->description = 'Project Subcontract Payable #' . $projectSubcontract->subcontract_no;
                $transaction->save();
            }

            if ($request->input('discount_value') > 0) {
                $transaction              = new PendingTransaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Purchase Discount Allowed')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->description = _lang('Project Subcontract Discount') . ' #' . $projectSubcontract->subcontract_no;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->ref_type    = 'project subcontract';
                $transaction->vendor_id   = $projectSubcontract->vendor_id;
                $transaction->save();
            }
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Project Subcontract Updated' . ' ' . $projectSubcontract->subcontract_no;
        $audit->save();


        return redirect()->route('project_subcontracts.show', $projectSubcontract->id)->with('success', _lang('Updated Successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $projectSubcontract = ProjectSubcontract::find($id);
        $projectSubcontract->deleted_by = auth()->user()->id;
        $projectSubcontract->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Project Subcontract Deleted' . ' ' . $projectSubcontract->subcontract_no;
        $audit->save();

        // delete transactions
        $transactions = Transaction::where('ref_id', $projectSubcontract->id)
            ->where(function ($query) {
                $query->where('ref_type', 'project subcontract')
                    ->orWhere('ref_type', 'project subcontract tax')
                    ->orWhere('ref_type', 'project subcontract payment');
            })
            ->get();

        foreach ($transactions as $transaction) {
            $transaction->delete();
        }

        $projectSubcontractPayments = ProjectSubcontractPayment::where('project_subcontract_id', $projectSubcontract->id)->get();

        foreach ($projectSubcontractPayments as $projectSubcontractPayment) {
            $projectSubcontractPayment->delete();
        }

        // delete attachments
        $attachments = Attachment::where('ref_id', $projectSubcontract->id)->where('ref_type', 'project subcontract')->get();
        foreach ($attachments as $attachment) {
            $filePath = public_path($attachment->path);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $attachment->delete();
        }

        $projectSubcontract->delete();
        return redirect()->route('project_subcontracts.index')->with('success', _lang('Deleted Successfully'));
    }

    public function bulk_destroy(Request $request)
    {
        foreach ($request->ids as $id) {
            $projectSubcontract = ProjectSubcontract::find($id);
            $projectSubcontract->deleted_by = auth()->user()->id;
            $projectSubcontract->save();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Project Subcontract Deleted' . ' ' . $projectSubcontract->subcontract_no;
            $audit->save();

            // delete transactions
            $transactions = Transaction::where('ref_id', $projectSubcontract->id)
                ->where(function ($query) {
                    $query->where('ref_type', 'project subcontract')
                        ->orWhere('ref_type', 'project subcontract tax')
                        ->orWhere('ref_type', 'project subcontract payment');
                })
                ->get();

            foreach ($transactions as $transaction) {
                $transaction->delete();
            }

            $projectSubcontractPayments = ProjectSubcontractPayment::where('project_subcontract_id', $projectSubcontract->id)->get();

            foreach ($projectSubcontractPayments as $projectSubcontractPayment) {
                $projectSubcontractPayment->delete();
            }

            // delete attachments
            $attachments = Attachment::where('ref_id', $projectSubcontract->id)->where('ref_type', 'project subcontract')->get();
            foreach ($attachments as $attachment) {
                $filePath = public_path($attachment->path);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $attachment->delete();
            }

            $projectSubcontract->delete();
        }

        return redirect()->route('project_subcontracts.index')->with('success', _lang('Deleted Successfully'));
    }

    private function calculateTotal(Request $request)
    {
        $subTotal = 0;
        $taxAmount = 0;
        $discountAmount = 0;
        $grandTotal = 0;

        for ($i = 0; $i < count($request->project_task_id); $i++) {
            //Calculate Sub Total
            $line_qnt = $request->quantity[$i];
            $line_unit_cost = $request->unit_cost[$i];
            $line_total = ($line_qnt * $line_unit_cost);

            //Show Sub Total
            $subTotal = ($subTotal + $line_total);

            //Calculate Taxes
            if (isset($request->taxes)) {
                for ($j = 0; $j < count($request->taxes); $j++) {
                    $taxId = $request->taxes[$j];
                    $tax = Tax::find($taxId);
                    $task_tax = ($line_total / 100) * $tax->rate;
                    $taxAmount += $task_tax;
                }
            }

            //Calculate Discount
            if ($request->discount_type == '0') {
                $discountAmount = ($subTotal / 100) * $request->discount_value ?? 0;
            } else if ($request->discount_type == '1') {
                $discountAmount = $request->discount_value ?? 0;
            }
        }

        //Calculate Grand Total
        $grandTotal = ($subTotal + $taxAmount) - $discountAmount;

        return array(
            'subTotal' => $subTotal / $request->exchange_rate,
            'taxAmount' => $taxAmount / $request->exchange_rate,
            'discountAmount' => $discountAmount / $request->exchange_rate,
            'grandTotal' => $grandTotal / $request->exchange_rate,
        );
    }

    public function show($id, Request $request)
    {
        $projectSubcontract = ProjectSubcontract::with(['business', 'tasks.task', 'tasks.cost_code', 'vendor', 'project', 'payments.vendor'])->find($id);
        $attachments = Attachment::where('ref_type', 'project subcontract')->where('ref_id', $id)->get();
        $email_templates = EmailTemplate::whereIn('slug', ['NEW_PROJECT_SUBCONTRACT_CREATED'])
            ->where('email_status', 1)
            ->get();
        $methods = TransactionMethod::all();
        $accounts = Account::orderBy('id', 'desc')->get();
        $activeTab = $request->get('tab', 'contract');

        return Inertia::render('Backend/User/Construction/Project/Subcontract/View', [
            'projectSubcontract' => $projectSubcontract,
            'attachments' => $attachments,
            'email_templates' => $email_templates,
            'methods' => $methods,
            'accounts' => $accounts,
            'activeTab' => $activeTab,
        ]);
    }

    public function show_public_project_subcontract($short_code)
    {
        $projectSubcontract = ProjectSubcontract::withoutGlobalScopes()->with(['business', 'tasks.task', 'tasks.cost_code', 'vendor', 'project'])
            ->where('short_code', $short_code)
            ->first();

        $request = request();
        // add activeBusiness object to request
        $request->merge(['activeBusiness' => $projectSubcontract->business]);

        return Inertia::render('Backend/User/Construction/Project/Subcontract/PublicView', [
            'projectSubcontract' => $projectSubcontract,
        ]);
    }

    public function edit($id)
    {
        $projectSubcontract = ProjectSubcontract::with(['business', 'tasks', 'taxes', 'vendor'])->find($id);

        if (!has_permission('project_subcontracts.approve') && !request()->isOwner && $projectSubcontract->approval_status == 1) {
            return back()->with('error', _lang('Permission denied'));
        }

        $theAttachments = Attachment::where('ref_id', $id)->where('ref_type', 'project subcontract')->get();
        $taxIds = $projectSubcontract->taxes
            ->pluck('tax_id')
            ->map(fn($id) => (string) $id)
            ->toArray();
        $vendors = Vendor::orderBy('id', 'desc')
            ->get();

        $costCodes = CostCode::orderBy('id', 'desc')
            ->get();

        $taxes = Tax::orderBy('id', 'desc')
            ->get();

        $projects = Project::orderBy('id', 'desc')->with('tasks')->get();

        $currencies = Currency::orderBy('id', 'desc')
            ->get();

        $accounts = Account::orderBy('id', 'desc')
            ->get();

        return Inertia::render('Backend/User/Construction/Project/Subcontract/Edit', [
            'projectSubcontract' => $projectSubcontract,
            'theAttachments' => $theAttachments,
            'accounts' => $accounts,
            'currencies' => $currencies,
            'vendors' => $vendors,
            'taxes' => $taxes,
            'taxIds' => $taxIds,
            'costCodes' => $costCodes,
            'projects' => $projects,
        ]);
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
            'vendor_id' => 'required',
            'subcontract_no' => 'required',
            'project_id' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|after_or_equal:start_date',
            'project_task_id' => 'required',
            'description' => 'nullable',
        ], [
            'project_task_id.required' => _lang('You must add at least one item'),
        ]);

        if ($validator->fails()) {
            return redirect()->route('project_subcontracts.create')
                ->withErrors($validator)
                ->withInput();
        }


        $default_accounts = ['Accounts Payable', 'Purchase Tax Payable', 'Purchase Discount Allowed', 'Inventory'];

        // if these accounts are not exists then create it
        foreach ($default_accounts as $account) {
            if (!Account::where('account_name', $account)->where('business_id', $request->activeBusiness->id)->exists()) {
                $account_obj = new Account();
                if ($account == 'Accounts Payable') {
                    $account_obj->account_code = '2100';
                } elseif ($account == 'Purchase Tax Payable') {
                    $account_obj->account_code = '2201';
                } elseif ($account == 'Purchase Discount Allowed') {
                    $account_obj->account_code = '6003';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_code = '1000';
                }
                $account_obj->account_name = $account;
                if ($account == 'Accounts Payable') {
                    $account_obj->account_type = 'Current Liability';
                } elseif ($account == 'Purchase Tax Payable') {
                    $account_obj->account_type = 'Current Liability';
                } elseif ($account == 'Purchase Discount Allowed') {
                    $account_obj->account_type = 'Cost Of Sale';
                } elseif ($account == 'Inventory') {
                    $account_obj->account_type = 'Other Current Asset';
                }
                if ($account == 'Accounts Payable') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Purchase Tax Payable') {
                    $account_obj->dr_cr   = 'dr';
                } elseif ($account == 'Purchase Discount Allowed') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Inventory') {
                    $account_obj->dr_cr   = 'dr';
                }
                $account_obj->business_id = $request->activeBusiness->id;
                $account_obj->user_id     = $request->activeBusiness->user->id;
                $account_obj->opening_date   = now()->format('Y-m-d');
                $account_obj->save();
            }
        }

        $month = Carbon::parse($request->start_date)->format('F');
        $year = Carbon::parse($request->start_date)->format('Y');
        $today = now()->format('d');

        // financial year
        $financial_year = BusinessSetting::where('name', 'fiscal_year')->first()->value;
        $end_month = explode(',', $financial_year)[1];
        $start_day = BusinessSetting::where('name', 'start_day')->first()->value;
        $end_day = $start_day + 5;

        // if login as this user dont check the financial year
        if (false) {
            if (($month !== now()->format('F') || $year !== now()->format('Y')) || ($today <= $end_day && $month == now()->subMonth()->format('F') && $year == now()->format('Y')) || ($today <= 25 && $month == $end_month && $year == now()->subYear()->format('Y'))) {
                return redirect()->back()->withInput()->with('error', _lang('Period Closed'));
            }
        }

        DB::beginTransaction();

        $summary = $this->calculateTotal($request);

        $projectSubcontract = ProjectSubcontract::find($id);
        $projectSubcontract->vendor_id = $request->input('vendor_id');
        $projectSubcontract->subcontract_no = $request->input('subcontract_no');
        $projectSubcontract->project_id = $request->input('project_id');
        $projectSubcontract->start_date = Carbon::parse($request->input('start_date'))->format('Y-m-d');
        $projectSubcontract->end_date = Carbon::parse($request->input('end_date'))->format('Y-m-d');
        $projectSubcontract->description = $request->input('description');
        $projectSubcontract->sub_total = $summary['subTotal'];
        $projectSubcontract->grand_total = $summary['grandTotal'];
        $projectSubcontract->converted_total = $request->input('converted_total');
        $projectSubcontract->exchange_rate   = $request->input('exchange_rate');
        $projectSubcontract->currency   = $request->input('currency');
        $projectSubcontract->discount = $summary['discountAmount'];
        $projectSubcontract->discount_type = $request->input('discount_type');
        $projectSubcontract->discount_value = $request->input('discount_value') ?? 0;
        $projectSubcontract->updated_by = auth()->user()->id;
        $projectSubcontract->short_code = rand(100000, 9999999) . uniqid();

        $projectSubcontract->save();

        // delete old attachments
        $attachments = Attachment::where('ref_id', $projectSubcontract->id)->where('ref_type', 'project subcontract')->get(); // Get attachments from the database

        if (isset($request->attachments)) {
            $incomingFiles = collect($request->attachments)->pluck('file')->toArray();

            foreach ($attachments as $attachment) {
                if (!in_array($attachment->path, $incomingFiles)) {
                    $filePath = public_path($attachment->path);
                    if (file_exists($filePath)) {
                        unlink($filePath); // Delete the file
                    }
                    $attachment->delete(); // Delete the database record
                }
            }
        }

        // if attachments then upload
        if (isset($request->attachments)) {
            if ($request->attachments != null) {
                for ($i = 0; $i < count($request->attachments); $i++) {
                    $theFile = $request->file("attachments.$i.file");
                    if ($theFile == null) {
                        continue;
                    }
                    $theAttachment = rand() . time() . $theFile->getClientOriginalName();
                    $theFile->move(public_path() . "/uploads/media/attachments/", $theAttachment);

                    $attachment = new Attachment();
                    $attachment->file_name = $request->attachments[$i]['file_name'];
                    $attachment->path = "/uploads/media/attachments/" . $theAttachment;
                    $attachment->ref_type = 'project subcontract';
                    $attachment->ref_id = $projectSubcontract->id;
                    $attachment->save();
                }
            }
        }

        //Update Invoice item
        foreach ($projectSubcontract->tasks as $projectSubcontractTask) {
            $projectSubcontractTask->forceDelete();

            // delete transaction
            $transaction = Transaction::where('ref_id', $projectSubcontract->id)->where('ref_type', 'project subcontract')
                ->where('account_id', $projectSubcontractTask->account_id)
                ->first();

            if ($transaction != null) {
                $transaction->delete();
            }

            // delete pending transaction
            $pending_transaction = PendingTransaction::where('ref_id', $projectSubcontract->id)->where('ref_type', 'project subcontract')
                ->where('account_id', $projectSubcontractTask->account_id)
                ->first();

            if ($pending_transaction != null) {
                $pending_transaction->delete();
            }
        }

        foreach ($projectSubcontract->taxes as $projectSubcontractTax) {
            $projectSubcontractTax->forceDelete();

            // delete transaction
            $transactions = Transaction::where('ref_id', $projectSubcontract->id)->where('ref_type', 'project subcontract tax')
                ->get();

            foreach ($transactions as $transaction) {
                $transaction->delete();
            }

            // delete pending transaction
            $pending_transactions = PendingTransaction::where('ref_id', $projectSubcontract->id)->where('ref_type', 'project subcontract tax')
                ->get();

            foreach ($pending_transactions as $pending_transaction) {
                $pending_transaction->delete();
            }
        }

        $currentTime = Carbon::now();

        for ($i = 0; $i < count($request->project_task_id); $i++) {
            $projectSubcontract->tasks()->save(new ProjectSubcontractTask([
                'project_subcontract_id' => $projectSubcontract->id,
                'project_task_id' => $request->project_task_id[$i],
                'cost_code_id' => $request->cost_code_id[$i],
                'uom' => $request->uom[$i],
                'quantity' => $request->quantity[$i],
                'unit_cost' => $request->unit_cost[$i],
                'sub_total' => ($request->unit_cost[$i] * $request->quantity[$i]),
                'account_id' => $request->account_id[$i],
            ]));

            if (has_permission('project_subcontracts.approve') || request()->isOwner) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $request->account_id[$i];
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($request->unit_cost[$i] * $request->quantity[$i])));
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($request->unit_cost[$i] * $request->quantity[$i]));
                $transaction->description = _lang('Project Subcontract') . ' #' . $projectSubcontract->subcontract_no;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->ref_type    = 'project subcontract';
                $transaction->save();
            } else {
                $transaction              = new PendingTransaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = $request->account_id[$i];
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, ($request->unit_cost[$i] * $request->quantity[$i])));
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, ($request->unit_cost[$i] * $request->quantity[$i]));
                $transaction->description = _lang('Project Subcontract') . ' #' . $projectSubcontract->subcontract_no;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->ref_type    = 'project subcontract';
                $transaction->save();
            }
        }

        if (isset($request->taxes)) {
            foreach ($request->taxes as $taxId) {
                $tax = Tax::find($taxId);

                $projectSubcontract->taxes()->save(new ProjectSubcontractTax([
                    'project_subcontract_id' => $projectSubcontract->id,
                    'tax_id' => $taxId,
                    'name' => $tax->name . ' ' . $tax->rate . ' %',
                    'amount' => ($projectSubcontract->sub_total / 100) * $tax->rate,
                ]));

                if (has_permission('project_subcontracts.approve') || request()->isOwner) {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $projectSubcontract->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($projectSubcontract->sub_total / $projectSubcontract->exchange_rate) / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($projectSubcontract->sub_total / $projectSubcontract->exchange_rate) / 100) * $tax->rate);
                    $transaction->description = _lang('Project Subcontract Tax') . ' #' . $projectSubcontract->subcontract_no;
                    $transaction->ref_id      = $projectSubcontract->id;
                    $transaction->ref_type    = 'project subcontract tax';
                    $transaction->tax_id      = $tax->id;
                    $transaction->save();
                } else {
                    $transaction              = new PendingTransaction();
                    $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_currency    = $request->currency;
                    $transaction->currency_rate = $projectSubcontract->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, (($projectSubcontract->sub_total / $projectSubcontract->exchange_rate) / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, (($projectSubcontract->sub_total / $projectSubcontract->exchange_rate) / 100) * $tax->rate);
                    $transaction->description = _lang('Project Subcontract Tax') . ' #' . $projectSubcontract->subcontract_no;
                    $transaction->ref_id      = $projectSubcontract->id;
                    $transaction->ref_type    = 'project subcontract tax';
                    $transaction->tax_id      = $tax->id;
                    $transaction->save();
                }
            }
        }

        DB::commit();

        if (has_permission('project_subcontracts.approve') || request()->isOwner) {
            if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = get_account('Accounts Payable')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']));
                $transaction->ref_type    = 'project subcontract';
                $transaction->vendor_id   = $projectSubcontract->vendor_id;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->description = 'Project Subcontract Payable #' . $projectSubcontract->subcontract_no;
                $transaction->save();
            } else {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = get_account('Accounts Payable')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
                $transaction->ref_type    = 'project subcontract';
                $transaction->vendor_id   = $projectSubcontract->vendor_id;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->description = 'Project Subcontract Payable #' . $projectSubcontract->subcontract_no;
                $transaction->save();
            }

            if ($request->input('discount_value') > 0) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Purchase Discount Allowed')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->description = _lang('Project Subcontract Discount') . ' #' . $projectSubcontract->subcontract_no;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->ref_type    = 'project subcontract';
                $transaction->vendor_id   = $projectSubcontract->vendor_id;
                $transaction->save();
            }
        } else {
            if (isset($request->withholding_tax) && $request->withholding_tax == 1) {
                $transaction              = new PendingTransaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = get_account('Accounts Payable')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal'] - $summary['taxAmount']));
                $transaction->ref_type    = 'project subcontract';
                $transaction->vendor_id   = $projectSubcontract->vendor_id;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->description = 'Project Subcontract Payable #' . $projectSubcontract->subcontract_no;
                $transaction->save();
            } else {
                $transaction              = new PendingTransaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = get_account('Accounts Payable')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['grandTotal']));
                $transaction->ref_type    = 'project subcontract';
                $transaction->vendor_id   = $projectSubcontract->vendor_id;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->description = 'Project Subcontract Payable #' . $projectSubcontract->subcontract_no;
                $transaction->save();
            }

            if ($request->input('discount_value') > 0) {
                $transaction              = new PendingTransaction();
                $transaction->trans_date  = Carbon::parse($request->input('start_date'))->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = get_account('Purchase Discount Allowed')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']);
                $transaction->transaction_currency    = $request->currency;
                $transaction->currency_rate           = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount    = convert_currency($request->currency, $request->activeBusiness->currency, convert_currency($request->activeBusiness->currency, $request->currency, $summary['discountAmount']));
                $transaction->description = _lang('Project Subcontract Discount') . ' #' . $projectSubcontract->subcontract_no;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->ref_type    = 'project subcontract';
                $transaction->vendor_id   = $projectSubcontract->vendor_id;
                $transaction->save();
            }
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Updated Project Subcontract ' . $projectSubcontract->subcontract_no;
        $audit->save();

        return redirect()->route('project_subcontracts.show', $projectSubcontract->id)->with('success', _lang('Updated Successfully'));
    }

    public function send_email(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'email'   => 'required|email',
            'subject' => 'required',
            'message' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $customMessage = [
            'subject' => $request->subject,
            'message' => $request->message,
        ];

        $projectSubcontract = ProjectSubcontract::find($id);
        $vendor = $projectSubcontract->vendor;
        $vendor->email = $request->email;

        try {
            Notification::send($vendor, new SendProjectSubcontract($projectSubcontract, $customMessage, $request->template));
            $projectSubcontract->email_send = 1;
            $projectSubcontract->email_send_at = now();
            $projectSubcontract->save();

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Sent Project Subcontract ' . $projectSubcontract->subcontract_no . ' to ' . $vendor->email;
            $audit->save();

            return redirect()->back()->with('success', _lang('Email has been sent'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function import_project_subcontracts(Request $request)
    {
        $request->validate([
            'subcontracts_file' => 'required|mimes:xls,xlsx',
        ]);

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Project Subcontracts Imported ' . $request->file('subcontracts_file')->getClientOriginalName();
        $audit->save();

        try {
            Excel::import(new ProjectSubcontractImport, $request->file('subcontracts_file'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('project_subcontracts.index')->with('success', _lang('Project Subcontracts Imported'));
    }

    public function export_project_subcontracts()
    {
        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Project Subcontracts Exported';
        $audit->save();

        return Excel::download(new ProjectSubcontractExport, 'project_subcontracts ' . now()->format('d m Y') . '.xlsx');
    }

    public function bulk_approve(Request $request)
    {
        foreach ($request->ids as $id) {
            $projectSubcontract = ProjectSubcontract::find($id);
            $projectSubcontract->contract_status = 1;
            $projectSubcontract->approved_by = auth()->user()->id;
            $projectSubcontract->save();

            // select from pending transactions and insert into transactions
            $transactions = PendingTransaction::where('ref_id', $projectSubcontract->id)->get();

            foreach ($transactions as $transaction) {
                // Create a new Transaction instance and replicate data from pending
                $new_transaction = $transaction->replicate();
                $new_transaction->setTable('transactions'); // Change the table to 'transactions'
                $new_transaction->save();

                // Delete the pending transaction
                $transaction->delete();
            }


            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Approved Project Subcontract ' . $projectSubcontract->subcontract_no;
            $audit->save();
        }

        return redirect()->route('project_subcontracts.index')->with('success', _lang('Approved Successfully'));
    }

    public function bulk_reject(Request $request)
    {
        foreach ($request->ids as $id) {
            $projectSubcontract = ProjectSubcontract::find($id);
            if ($projectSubcontract->contract_status == 0) {
                continue;
            }
            $projectSubcontract->contract_status = 0;
            $projectSubcontract->approved_by = null;
            $projectSubcontract->save();


            // delete all transactions
            $transactions = Transaction::where('ref_id', $projectSubcontract->id)
                ->where(function ($query) {
                    $query->where('ref_type', 'project subcontract')
                        ->orWhere('ref_type', 'project subcontract tax');
                })
                ->get();

            foreach ($transactions as $transaction) {
                $new_transaction = $transaction->replicate();
                $new_transaction->setTable('pending_transactions');
                $new_transaction->save();

                $transaction->delete();
            }

            // audit log
            $audit = new AuditLog();
            $audit->date_changed = date('Y-m-d H:i:s');
            $audit->changed_by = auth()->user()->id;
            $audit->event = 'Rejected Project Subcontract ' . $projectSubcontract->subcontract_no;
            $audit->save();
        }

        return redirect()->route('project_subcontracts.index')->with('success', _lang('Rejected Successfully'));
    }
}
