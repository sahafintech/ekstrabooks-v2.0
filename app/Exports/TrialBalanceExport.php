<?php

namespace App\Exports;

use App\Models\Account;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TrialBalanceExport implements FromView, WithTitle, WithColumnFormatting
{
    /**
     * @return \Illuminate\Support\Collection
     */

    protected $date1;
    protected $date2;

    public function __construct($date1, $date2)
    {
        $this->date1 = $date1;
        $this->date2 = $date2;
    }

    public function view(): View
    {
        $data = array();
        $date1 = $this->date1;
        $date2 = $this->date2;

        $data['report_data']['fixed_asset'] = Account::where(function ($query) {
            $query->where('account_type', 'Fixed Asset');
        })
            ->whereHas('transactions', function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '>=', $date1)
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
            ->whereHas('transactions', function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '>=', $date1)
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
            ->whereHas('transactions', function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '>=', $date1)
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
            ->whereHas('transactions', function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '>=', $date1)
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
            ->whereHas('transactions', function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['sales_and_income'] = Account::where(function ($query) {
            $query->where('account_type', 'Other Income')
                ->orWhere('account_type', 'Sales');
        })
            ->where(function ($query) {
                $query->where('business_id', '=', request()->activeBusiness->id)
                    ->orWhere('business_id', '=', null);
            })
            ->whereHas('transactions', function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['cost_of_sale'] = Account::where('account_type', 'Cost Of Sale')
            ->where(function ($query) {
                $query->where('business_id', '=', request()->activeBusiness->id)
                    ->orWhere('business_id', '=', null);
            })
            ->whereHas('transactions', function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['direct_expenses'] = Account::where('account_type', 'Direct Expenses')
            ->where(function ($query) {
                $query->where('business_id', '=', request()->activeBusiness->id)
                    ->orWhere('business_id', '=', null);
            })
            ->whereHas('transactions', function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $data['report_data']['other_expenses'] = Account::where('account_type', 'Other Expenses')
            ->where(function ($query) {
                $query->where('business_id', '=', request()->activeBusiness->id)
                    ->orWhere('business_id', '=', null);
            })
            ->whereHas('transactions', function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            })
            ->with(['transactions' => function ($query) use ($date1, $date2) {
                $query->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }])
            ->withSum(['transactions as dr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'dr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->withSum(['transactions as cr_amount' => function ($query) use ($date1, $date2) {
                $query->where('dr_cr', 'cr')
                    ->whereDate('trans_date', '>=', $date1)
                    ->whereDate('trans_date', '<=', $date2);
            }], 'base_currency_amount')
            ->get();

        $total_debit = 0;
        $total_credit = 0;

        foreach ($data['report_data']['fixed_asset'] as $fixed_asset) {
            $total_debit += $fixed_asset->dr_amount;
            $total_credit += $fixed_asset->cr_amount;
        }

        foreach ($data['report_data']['current_asset'] as $current_asset) {
            $total_debit += $current_asset->dr_amount;
            $total_credit += $current_asset->cr_amount;
        }

        foreach ($data['report_data']['current_liability'] as $current_liability) {
            $total_debit += $current_liability->dr_amount;
            $total_credit += $current_liability->cr_amount;
        }

        foreach ($data['report_data']['long_term_liability'] as $long_term_liability) {
            $total_debit += $long_term_liability->dr_amount;
            $total_credit += $long_term_liability->cr_amount;
        }

        foreach ($data['report_data']['equity'] as $equity) {
            $total_debit += $equity->dr_amount;
            $total_credit += $equity->cr_amount;
        }

        foreach ($data['report_data']['sales_and_income'] as $sales_and_income) {
            $total_debit += $sales_and_income->dr_amount;
            $total_credit += $sales_and_income->cr_amount;
        }

        foreach ($data['report_data']['cost_of_sale'] as $cost_of_sale) {
            $total_debit += $cost_of_sale->dr_amount;
            $total_credit += $cost_of_sale->cr_amount;
        }

        foreach ($data['report_data']['direct_expenses'] as $direct_expenses) {
            $total_debit += $direct_expenses->dr_amount;
            $total_credit += $direct_expenses->cr_amount;
        }

        foreach ($data['report_data']['other_expenses'] as $other_expenses) {
            $total_debit += $other_expenses->dr_amount;
            $total_credit += $other_expenses->cr_amount;
        }

        $data['total_debit'] = $total_debit;
        $data['total_credit'] = $total_credit;
        $data['date1'] = Carbon::parse($date1);
        $data['date2'] = Carbon::parse($date2);

        return view('backend.user.reports.exports.trial_balance', $data);
    }

    public function title(): string
    {
        return 'Trial Balance';
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
        ];
    }
}
