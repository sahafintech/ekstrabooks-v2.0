<?php

namespace App\Exports;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BalanceSheetExport implements FromView, WithTitle, WithColumnFormatting
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $date2;

    public function __construct($date2)
    {
        $this->date2 = $date2;
    }

    public function view(): View
    {
        $data = array();
        $date2 = $this->date2;

        $data['report_data']['fixed_asset'] = Account::where(function ($query) {
            $query->where('account_type', 'Fixed Asset');
        })
            ->whereHas('transactions', function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['current_asset'] = Account::where(function ($query) {
            $query->where('account_type', '=', 'Bank')
                ->orWhere('account_type', '=', 'Cash')
                ->orWhere('account_type', '=', 'Other Current Asset');
        })->where(function ($query) {
            $query->where('business_id', '=', request()->activeBusiness->id)
                ->orWhere('business_id', '=', null);
        })
            ->whereHas('transactions', function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['current_liability'] = Account::where(function ($query) {
            $query->where('account_type', 'Current Liability');
        })
            ->where(function ($query) {
                $query->where('business_id', '=', request()->activeBusiness->id)
                    ->orWhere('business_id', '=', null);
            })
            ->whereHas('transactions', function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['long_term_liability'] = Account::where(function ($query) {
            $query->where('account_type', 'Long Term Liability');
        })
            ->where(function ($query) {
                $query->where('business_id', '=', request()->activeBusiness->id)
                    ->orWhere('business_id', '=', null);
            })
            ->whereHas('transactions', function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['equity'] = Account::where(function ($query) {
            $query->where('account_type', 'Equity');
        })
            ->where(function ($query) {
                $query->where('business_id', '=', request()->activeBusiness->id)
                    ->orWhere('business_id', '=', null);
            })
            ->whereHas('transactions', function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['sales_and_income'] = Account::where(function ($query) {
            $query->where('account_type', 'Other Income')
                ->orWhere('account_type', 'Sales');
        })
            ->whereHas('transactions', function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
            ->whereHas('transactions', function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
            ->whereHas('transactions', function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['other_expenses'] = Account::where('account_type', 'Other Expenses')
            ->whereHas('transactions', function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date2) {
                $query->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as dr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as cr_amount' => function ($query) use ($date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $total_debit_asset = 0;
        $total_credit_asset = 0;

        $total_debit_liability = 0;
        $total_credit_liability = 0;

        $total_debit_equity = 0;
        $total_credit_equity = 0;

        $total_debit_sales_and_income = 0;
        $total_credit_sales_and_income = 0;

        $total_debit_cost_of_sale = 0;
        $total_credit_cost_of_sale = 0;

        $total_debit_direct_expenses = 0;
        $total_credit_direct_expenses = 0;

        $total_debit_other_expenses = 0;
        $total_credit_other_expenses = 0;

        foreach ($data['report_data']['sales_and_income'] as $sales_and_income) {
            $total_debit_sales_and_income += $sales_and_income->dr_amount;
            $total_credit_sales_and_income += $sales_and_income->cr_amount;
        }

        foreach ($data['report_data']['cost_of_sale'] as $cost_of_sale) {
            $total_debit_cost_of_sale += $cost_of_sale->dr_amount;
            $total_credit_cost_of_sale += $cost_of_sale->cr_amount;
        }

        foreach ($data['report_data']['direct_expenses'] as $direct_expenses) {
            $total_debit_direct_expenses += $direct_expenses->dr_amount;
            $total_credit_direct_expenses += $direct_expenses->cr_amount;
        }

        foreach ($data['report_data']['other_expenses'] as $other_expenses) {
            $total_debit_other_expenses += $other_expenses->dr_amount;
            $total_credit_other_expenses += $other_expenses->cr_amount;
        }

        $income_statement = (($data['report_data']['sales_and_income']->sum('cr_amount') - $data['report_data']['sales_and_income']->sum('dr_amount')) - ($data['report_data']['cost_of_sale']->sum('dr_amount') - $data['report_data']['cost_of_sale']->sum('cr_amount'))) - ((($data['report_data']['direct_expenses']->sum('dr_amount') - $data['report_data']['direct_expenses']->sum('cr_amount'))) + (($data['report_data']['other_expenses']->sum('dr_amount') - $data['report_data']['other_expenses']->sum('cr_amount'))));

        foreach ($data['report_data']['fixed_asset'] as $fixed_asset) {
            $total_debit_asset += $fixed_asset->dr_amount;
            $total_credit_asset += $fixed_asset->cr_amount;
        }

        foreach ($data['report_data']['current_asset'] as $current_asset) {
            $total_debit_asset += $current_asset->dr_amount;
            $total_credit_asset += $current_asset->cr_amount;
        }

        foreach ($data['report_data']['current_liability'] as $current_liability) {
            $total_debit_liability += $current_liability->dr_amount;
            $total_credit_liability += $current_liability->cr_amount;
        }

        foreach ($data['report_data']['long_term_liability'] as $long_term_liability) {
            $total_debit_liability += $long_term_liability->dr_amount;
            $total_credit_liability += $long_term_liability->cr_amount;
        }

        foreach ($data['report_data']['equity'] as $equity) {
            $total_debit_equity += $equity->dr_amount;
            $total_credit_equity += $equity->cr_amount;
        }

        $data['report_data']['equity'][] = (object) [
            'account_code' => '',
            'account_name' => 'Profit & Loss',
            'dr_amount' => $total_debit_sales_and_income + $total_debit_cost_of_sale + $total_debit_direct_expenses + $total_debit_other_expenses,
            'cr_amount' => $total_credit_sales_and_income + $total_credit_cost_of_sale + $total_credit_direct_expenses + $total_credit_other_expenses,
        ];

        $data['total_debit_asset'] = $total_debit_asset;
        $data['total_credit_asset'] = $total_credit_asset;
        $data['total_debit_liability'] = $total_debit_liability;
        $data['total_credit_liability'] = $total_credit_liability;
        $data['total_debit_equity'] = $total_debit_equity;
        $data['total_credit_equity'] = $total_credit_equity;
        $data['income_statement'] = $income_statement;
        $data['date2'] = Carbon::parse($date2);

        return view('backend.user.reports.exports.balance_sheet', $data);
    }

    public function title(): string
    {
        return 'Balance Sheet';
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
