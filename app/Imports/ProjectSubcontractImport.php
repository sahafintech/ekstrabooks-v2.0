<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\BusinessSetting;
use App\Models\CostCode;
use App\Models\Currency;
use App\Models\PendingTransaction;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectSubcontract;
use App\Models\ProjectSubcontractTask;
use App\Models\ProjectSubcontractTax;
use App\Models\ProjectTask;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseItemTax;
use App\Models\Tax;
use App\Models\Transaction;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ProjectSubcontractImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        $default_accounts = ['Accounts Payable', 'Purchase Tax Payable', 'Purchase Discount Allowed', 'Inventory'];

        // if these accounts are not exists then create it
        foreach ($default_accounts as $account) {
            if (!Account::where('account_name', $account)->where('business_id', request()->activeBusiness->id)->exists()) {
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
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Purchase Discount Allowed') {
                    $account_obj->dr_cr   = 'cr';
                } elseif ($account == 'Inventory') {
                    $account_obj->dr_cr   = 'dr';
                }
                $account_obj->business_id = request()->activeBusiness->id;
                $account_obj->user_id     = request()->activeBusiness->user->id;
                $account_obj->opening_date   = now()->format('Y-m-d');
                $account_obj->save();
            }
        }

        $rowNumber = 1;

        foreach ($rows as $row) {

            $summary = $this->calculateTotal($row);

            $vendor = Vendor::where('name', 'like', '%' . $row['supplier_name'] . '%')->first();

            $start_date = Date::excelToDateTimeObject($row['start_date'])->format('Y-m-d');
            $end_date = Date::excelToDateTimeObject($row['end_date'])->format('Y-m-d');

            $month = Carbon::parse($start_date)->format('F');
            $year = Carbon::parse($start_date)->format('Y');
            $today = now()->format('d');

            // financial year
            $financial_year = BusinessSetting::where('name', 'fiscal_year')->first()->value;
            $end_month = explode(',', $financial_year)[1];
            $start_day = BusinessSetting::where('name', 'start_day')->first()->value;
            $end_day = $start_day + 5;

            // if login as this user dont check the financial year
            if (false) {
                if (($month !== now()->format('F') || $year !== now()->format('Y')) || ($today <= $end_day && $month == now()->subMonth()->format('F') && $year == now()->format('Y')) || ($today <= 25 && $month == $end_month && $year == now()->subYear()->format('Y'))) {
                    return redirect()->back()->withInput()->with('error', _lang('The date on row ' . $rowNumber . ' . Period Closed'));
                }
            }

            $projectSubcontract = new ProjectSubcontract();
            $projectSubcontract->vendor_id = $vendor->id;
            $projectSubcontract->subcontract_no = $row['subcontract_no'];
            $projectSubcontract->project_id = Project::where('project_code', $row['project_code'])->first()->id;
            $projectSubcontract->start_date = $start_date;
            $projectSubcontract->end_date = $end_date;
            $projectSubcontract->description = $row['description'];
            $projectSubcontract->sub_total = $summary['subTotal'];
            $projectSubcontract->grand_total = $summary['grandTotal'];
            $projectSubcontract->converted_total = convert_currency(request()->activeBusiness->currency, $row['currency'], $summary['grandTotal']);
            $projectSubcontract->exchange_rate   = Currency::where('name', 'like', '%' . $row['currency'] . '%')->first()->exchange_rate;
            $projectSubcontract->currency   = $row['currency'];
            $projectSubcontract->paid = 0;
            $projectSubcontract->discount = $summary['discountAmount'];
            $projectSubcontract->discount_type = $row['discount_type'];
            $projectSubcontract->discount_value = $row['discount_value'] ?? 0;
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

            $currentTime = now();


            $projectSubcontract->tasks()->save(new ProjectSubcontractTask([
                'project_subcontract_id' => $projectSubcontract->id,
                'project_task_id' => ProjectTask::where('task_code', $row['project_task_code'])->first()->id,
                'cost_code_id' => CostCode::where('code', $row['cost_code'])->first()->id,
                'uom' => $row['uom'],
                'quantity' => $row['quantity'],
                'unit_cost' => $row['unit_cost'],
                'sub_total' => ($row['unit_cost'] * $row['quantity']),
                'account_id' => Account::where('account_name', $row['account_name'])->first()->id,
            ]));

            if (has_permission('project_subcontracts.approve') || request()->isOwner) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($start_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = Account::where('account_name', $row['account_name'])->first()->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_currency    = $row['currency'];
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($row['currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['currency'], ($row['unit_cost'] * $row['quantity'])));
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['currency'], ($row['unit_cost'] * $row['quantity']));
                $transaction->description = _lang('Project Subcontract') . ' #' . $projectSubcontract->subcontract_no;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->ref_type    = 'project subcontract';
                $transaction->save();
            } else {
                $transaction              = new PendingTransaction();
                $transaction->trans_date  = Carbon::parse($start_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                $transaction->account_id  = Account::where('account_name', $row['account_name'])->first()->id;
                $transaction->dr_cr       = 'dr';
                $transaction->transaction_currency    = $row['currency'];
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($row['currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['currency'], ($row['unit_cost'] * $row['quantity'])));
                $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['currency'], ($row['unit_cost'] * $row['quantity']));
                $transaction->description = _lang('Project Subcontract') . ' #' . $projectSubcontract->subcontract_no;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->ref_type    = 'project subcontract';
                $transaction->save();
            }

            if (isset($row['tax'])) {
                $tax = Tax::where('name', $row['tax'])->first();

                $projectSubcontract->taxes()->save(new ProjectSubcontractTax([
                    'project_subcontract_id' => $projectSubcontract->id,
                    'tax_id' => $tax->id,
                    'name' => $tax->name . ' ' . $tax->rate . ' %',
                    'amount' => ($projectSubcontract->sub_total / 100) * $tax->rate,
                ]));

                if (has_permission('project_subcontracts.approve') || request()->isOwner) {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($start_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_currency    = $row['currency'];
                    $transaction->currency_rate = $projectSubcontract->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($row['currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['currency'], (($projectSubcontract->sub_total / $projectSubcontract->exchange_rate) / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['currency'], (($projectSubcontract->sub_total / $projectSubcontract->exchange_rate) / 100) * $tax->rate);
                    $transaction->description = _lang('Project Subcontract Tax') . ' #' . $projectSubcontract->subcontract_no;
                    $transaction->ref_id      = $projectSubcontract->id;
                    $transaction->ref_type    = 'project subcontract tax';
                    $transaction->tax_id      = $tax->id;
                    $transaction->save();
                } else {
                    $transaction              = new PendingTransaction();
                    $transaction->trans_date  = Carbon::parse($start_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = $tax->account_id;
                    $transaction->dr_cr       = 'dr';
                    $transaction->transaction_currency    = $row['currency'];
                    $transaction->currency_rate = $projectSubcontract->exchange_rate;
                    $transaction->base_currency_amount = convert_currency($row['currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['currency'], (($projectSubcontract->sub_total / $projectSubcontract->exchange_rate) / 100) * $tax->rate));
                    $transaction->transaction_amount      = convert_currency(request()->activeBusiness->currency, $row['currency'], (($projectSubcontract->sub_total / $projectSubcontract->exchange_rate) / 100) * $tax->rate);
                    $transaction->description = _lang('Project Subcontract Tax') . ' #' . $projectSubcontract->subcontract_no;
                    $transaction->ref_id      = $projectSubcontract->id;
                    $transaction->ref_type    = 'project subcontract tax';
                    $transaction->tax_id      = $tax->id;
                    $transaction->save();
                }
            }


            if (has_permission('project_subcontracts.approve') || request()->isOwner) {
                $transaction              = new Transaction();
                $transaction->trans_date  = Carbon::parse($start_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = get_account('Accounts Payable')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($row['currency'], request()->activeBusiness->currency, $summary['grandTotal']);
                $transaction->transaction_currency    = $row['currency'];
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($row['currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['currency'], $summary['grandTotal']));
                $transaction->ref_type    = 'project subcontract';
                $transaction->vendor_id   = $projectSubcontract->vendor_id;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->description = 'Project Subcontract Payable #' . $projectSubcontract->subcontract_no;
                $transaction->save();

                if ($row['discount_value'] > 0) {
                    $transaction              = new Transaction();
                    $transaction->trans_date  = Carbon::parse($start_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = get_account('Purchase Discount Allowed')->id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = convert_currency($row['currency'], request()->activeBusiness->currency, $summary['discountAmount']);
                    $transaction->transaction_currency    = $row['currency'];
                    $transaction->currency_rate           = $projectSubcontract->exchange_rate;
                    $transaction->base_currency_amount    = convert_currency($row['currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['currency'], $summary['discountAmount']));
                    $transaction->description = _lang('Project Subcontract Discount') . ' #' . $projectSubcontract->subcontract_no;
                    $transaction->ref_id      = $projectSubcontract->id;
                    $transaction->ref_type    = 'project subcontract';
                    $transaction->vendor_id   = $projectSubcontract->vendor_id;
                    $transaction->save();
                }
            } else {
                $transaction              = new PendingTransaction();
                $transaction->trans_date  = Carbon::parse($start_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i');
                $transaction->account_id  = get_account('Accounts Payable')->id;
                $transaction->dr_cr       = 'cr';
                $transaction->transaction_amount      = convert_currency($row['currency'], request()->activeBusiness->currency, $summary['grandTotal']);
                $transaction->transaction_currency    = $row['currency'];
                $transaction->currency_rate = $projectSubcontract->exchange_rate;
                $transaction->base_currency_amount = convert_currency($row['currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['currency'], $summary['grandTotal']));
                $transaction->ref_type    = 'project subcontract';
                $transaction->vendor_id   = $projectSubcontract->vendor_id;
                $transaction->ref_id      = $projectSubcontract->id;
                $transaction->description = 'Project Subcontract Payable #' . $projectSubcontract->subcontract_no;
                $transaction->save();

                if ($row['discount_value'] > 0) {
                    $transaction              = new PendingTransaction();
                    $transaction->trans_date  = Carbon::parse($start_date)->setTime($currentTime->hour, $currentTime->minute, $currentTime->second)->format('Y-m-d H:i:s');
                    $transaction->account_id  = get_account('Purchase Discount Allowed')->id;
                    $transaction->dr_cr       = 'cr';
                    $transaction->transaction_amount      = convert_currency($row['currency'], request()->activeBusiness->currency, $summary['discountAmount']);
                    $transaction->transaction_currency    = $row['currency'];
                    $transaction->currency_rate           = $projectSubcontract->exchange_rate;
                    $transaction->base_currency_amount    = convert_currency($row['currency'], request()->activeBusiness->currency, convert_currency(request()->activeBusiness->currency, $row['currency'], $summary['discountAmount']));
                    $transaction->description = _lang('Project Subcontract Discount') . ' #' . $projectSubcontract->subcontract_no;
                    $transaction->ref_id      = $projectSubcontract->id;
                    $transaction->ref_type    = 'project subcontract';
                    $transaction->vendor_id   = $projectSubcontract->vendor_id;
                    $transaction->save();
                }
            }
        }
        $rowNumber++;
    }

    private function calculateTotal($row)
    {
        $subTotal       = 0;
        $taxAmount      = 0;
        $discountAmount = 0;
        $grandTotal     = 0;

        //Calculate Sub Total
        $line_qnt       = $row['quantity'];
        $line_unit_cost = $row['unit_cost'];
        $line_total     = ($line_qnt * $line_unit_cost);

        //Show Sub Total
        $subTotal = ($subTotal + $line_total);

        //Calculate Taxes
        if (isset($row['tax'])) {
            $tax         = Tax::where('name', 'like', '%' . $row['tax'] . '%')->first();
            $taxAmount   = ($line_total / 100) * $tax->rate;
        }

        //Calculate Discount
        if ($row['discount_type'] == '0') {
            $discountAmount = ($subTotal / 100) * $row['discount_value'];
        } else if ($row['discount_type'] == '1') {
            $discountAmount = $row['discount_value'];
        }

        //Calculate Grand Total
        $grandTotal = ($subTotal + $taxAmount) - $discountAmount;

        return array(
            'subTotal'       => $subTotal,
            'taxAmount'      => $taxAmount,
            'discountAmount' => $discountAmount,
            'grandTotal'     => $grandTotal,
        );
    }

    public function rules(): array
    {
        return [
            'start_date' => 'required',
            'end_date'     => 'required',
            'supplier_name' => 'required|exists:vendors,name',
            'project_task_code' => 'required|exists:project_tasks,task_code',
            'quantity'     => 'required|numeric',
            'unit_cost'    => 'required|numeric',
            'currency' => 'required|exists:currency,name',
            'discount_type' => 'nullable|in:0,1',
            'discount_value' => 'nullable|numeric',
            'cost_code' => 'required|exists:cost_codes,code',
            'description' => 'nullable',
            'subcontract_no' => 'required',
            'project_code' => 'required|exists:projects,project_code',
            'account_name' => 'required|exists:accounts,account_name',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'start_date.required' => _lang('Start Date is required'),
            'end_date.required'     => _lang('End Date is required'),
            'supplier_name.required' => _lang('Supplier Name is required'),
            'project_task_code.required' => _lang('Project Task Code is required'),
            'quantity.required'     => _lang('Quantity is required'),
            'quantity.numeric'      => _lang('Quantity must be a number'),
            'unit_cost.required'    => _lang('Unit Cost is required'),
            'unit_cost.numeric'     => _lang('Unit Cost must be a number'),
            'currency.required' => _lang('Currency is required'),
            'cost_code.required' => _lang('Cost Code is required'),
            'cost_code.exists' => _lang('Cost Code must be exists'),
            'subcontract_no.required' => _lang('Subcontract No is required'),
            'project_code.required' => _lang('Project Code is required'),
            'project_code.exists' => _lang('Project Code must be exists'),
            'account_name.required' => _lang('Account Name is required'),
            'account_name.exists' => _lang('Account Name must be exists'),
        ];
    }
}
