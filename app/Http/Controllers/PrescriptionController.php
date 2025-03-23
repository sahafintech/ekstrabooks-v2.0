<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Prescription;
use App\Models\PrescriptionProduct;
use App\Models\PrescriptionProductItem;
use App\Models\Product;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrescriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Prescription::with('customer', 'medical_record')->select('prescriptions.*');

        // handle search
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('prescriptions.date', 'like', "%{$search}%")
                    ->orWhere('prescriptions.customer.name', 'like', "%{$search}%")
                    ->orWhere('prescriptions.customer.phone', 'like', "%{$search}%");
            });
        }

        // handle column filters
        if ($request->has('columnFilters')) {
            $columnFilters = $request->get('columnFilters');
            if (is_string($columnFilters)) {
                $columnFilters = json_decode($columnFilters, true);
            }
            if (is_array($columnFilters)) {
                foreach ($columnFilters as $column => $value) {
                    if ($value !== null && $value !== '') {
                        $query->where('prescriptions.' . $column, $value);
                    }
                }
            }
        }

        // handle pagination
        $per_page = $request->get('per_page', 10);
        $prescriptions = $query->paginate($per_page);

        return Inertia::render('Backend/User/Prescription/List', [
            'prescriptions' => $prescriptions->items(),
            'meta' => [
                'total' => $prescriptions->total(),
                'per_page' => $prescriptions->perPage(),
                'current_page' => $prescriptions->currentPage(),
                'last_page' => $prescriptions->lastPage(),
            ],
            'filters' => [
                'search' => $request->get('search', ''),
                'columnFilters' => $request->get('columnFilters', []),
                'sorting' => $request->get('sorting', []),
            ],
        ]);
    }

    public function create()
    {
        $customers = Customer::all();
        $products = Product::all();
        
        return Inertia::render('Backend/User/Prescription/Create', [
            'customers' => $customers,
            'products' => $products
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required',
            'date' => 'required',
            'result_date' => 'required',
        ]);

        $prescription = new Prescription();
        $prescription->customer_id = $request->customer_id;
        $prescription->date = Carbon::parse($request->date)->format('Y-m-d');
        $prescription->result_date = Carbon::parse($request->result_date)->format('Y-m-d');
        $prescription->dist_sph_re = $request->dist_sph_re;
        $prescription->dist_cyl_re = $request->dist_cyl_re;
        $prescription->dist_axis_re = $request->dist_axis_re;
        $prescription->dist_va_re = $request->dist_va_re;
        $prescription->dist_sph_le = $request->dist_sph_le;
        $prescription->dist_cyl_le = $request->dist_cyl_le;
        $prescription->dist_axis_le = $request->dist_axis_le;
        $prescription->dist_va_le = $request->dist_va_le;
        $prescription->near_sph_re = $request->near_sph_re;
        $prescription->near_cyl_re = $request->near_cyl_re;
        $prescription->near_axis_re = $request->near_axis_re;
        $prescription->near_va_re = $request->near_va_re;
        $prescription->near_sph_le = $request->near_sph_le;
        $prescription->near_cyl_le = $request->near_cyl_le;
        $prescription->near_axis_le = $request->near_axis_le;
        $prescription->near_va_le = $request->near_va_le;
        $prescription->ipd = $request->ipd;
        $prescription->glasses = $request->glasses;
        $prescription->plastic = $request->plastic;
        $prescription->polycarbonate = $request->polycarbonate;
        $prescription->contact_lenses = $request->contact_lenses;
        $prescription->photochromatic_lenses = $request->photochromatic_lenses;
        $prescription->bi_focal_lenses = $request->bi_focal_lenses;
        $prescription->progressive_lenses = $request->progressive_lenses;
        $prescription->anti_reflection_coating = $request->anti_reflection_coating;
        $prescription->high_index_lenses = $request->high_index_lenses;
        $prescription->single_vision = $request->single_vision;
        $prescription->white_lenses = $request->white_lenses;
        $prescription->blue_cut = $request->blue_cut;
        $prescription->status = 0;
        $prescription->description = $request->prescription_description;
        $prescription->save();

        $summary = $this->calculateTotal($request);

        $prescription_products                     = new PrescriptionProduct();
        $prescription_products->prescription_id    = $prescription->id;
        $prescription_products->sub_total          = $summary['subTotal'];
        $prescription_products->grand_total        = $summary['grandTotal'];
        $prescription_products->currency           = $request->activeBusiness->currency;
        $prescription_products->converted_total    = $summary['grandTotal'];
        $prescription_products->exchange_rate      = Currency::where('base_currency', 1)->first()->exchange_rate;
        $prescription_products->user_id            = auth()->user()->id;
        $prescription_products->business_id        = request()->activeBusiness->id;

        $prescription_products->save();

        for ($i = 0; $i < count($request->product_id); $i++) {
            $prescription_products->items()->save(new PrescriptionProductItem([
                'prescription_products_id'   => $prescription_products->id,
                'product_id'   => $request->product_id[$i],
                'product_name' => $request->product_name[$i],
                'description'  => $request->description[$i],
                'quantity'     => $request->quantity[$i],
                'unit_cost'    => $request->unit_cost[$i],
                'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
                'user_id'      => auth()->user()->id,
                'business_id'  => request()->activeBusiness->id,
            ]));
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Prescription Created';
        $audit->save();

        return redirect()->route('prescriptions.show', $prescription->id)->with('success', 'Prescription created successfully');
    }

    public function edit($id)
    {
        $prescription = Prescription::with("items")->find($id);
        return view('backend.user.prescription.edit', compact('prescription', 'id'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'customer_id' => 'required',
            'date' => 'required',
            'result_date' => 'required',
        ]);

        $prescription = Prescription::find($id);
        $prescription->customer_id = $request->customer_id;
        $prescription->date = Carbon::parse($request->date)->format('Y-m-d');
        $prescription->result_date = Carbon::parse($request->result_date)->format('Y-m-d');
        $prescription->dist_sph_re = $request->dist_sph_re;
        $prescription->dist_cyl_re = $request->dist_cyl_re;
        $prescription->dist_axis_re = $request->dist_axis_re;
        $prescription->dist_va_re = $request->dist_va_re;
        $prescription->dist_sph_le = $request->dist_sph_le;
        $prescription->dist_cyl_le = $request->dist_cyl_le;
        $prescription->dist_axis_le = $request->dist_axis_le;
        $prescription->dist_va_le = $request->dist_va_le;
        $prescription->near_sph_re = $request->near_sph_re;
        $prescription->near_cyl_re = $request->near_cyl_re;
        $prescription->near_axis_re = $request->near_axis_re;
        $prescription->near_va_re = $request->near_va_re;
        $prescription->near_sph_le = $request->near_sph_le;
        $prescription->near_cyl_le = $request->near_cyl_le;
        $prescription->near_axis_le = $request->near_axis_le;
        $prescription->near_va_le = $request->near_va_le;
        $prescription->ipd = $request->ipd;
        $prescription->glasses = $request->glasses;
        $prescription->plastic = $request->plastic;
        $prescription->polycarbonate = $request->polycarbonate;
        $prescription->contact_lenses = $request->contact_lenses;
        $prescription->photochromatic_lenses = $request->photochromatic_lenses;
        $prescription->bi_focal_lenses = $request->bi_focal_lenses;
        $prescription->progressive_lenses = $request->progressive_lenses;
        $prescription->anti_reflection_coating = $request->anti_reflection_coating;
        $prescription->high_index_lenses = $request->high_index_lenses;
        $prescription->single_vision = $request->single_vision;
        $prescription->white_lenses = $request->white_lenses;
        $prescription->blue_cut = $request->blue_cut;
        $prescription->description = $request->prescription_description;
        $prescription->save();

        foreach ($prescription->items as $item) {
            $item->delete();
        }

        $summary = $this->calculateTotal($request);

        $prescription_products                     = PrescriptionProduct::where('prescription_id', $prescription->id)->first();
        $prescription_products->sub_total          = $summary['subTotal'];
        $prescription_products->grand_total        = $summary['grandTotal'];
        $prescription_products->currency           = $request->activeBusiness->currency;
        $prescription_products->converted_total    = $summary['grandTotal'];
        $prescription_products->exchange_rate      = Currency::where('base_currency', 1)->first()->exchange_rate;
        $prescription_products->save();

        for ($i = 0; $i < count($request->product_id); $i++) {
            $prescription_products->items()->save(new PrescriptionProductItem([
                'prescription_products_id'   => $prescription_products->id,
                'product_id'   => $request->product_id[$i],
                'product_name' => $request->product_name[$i],
                'description'  => $request->description[$i],
                'quantity'     => $request->quantity[$i],
                'unit_cost'    => $request->unit_cost[$i],
                'sub_total'    => ($request->unit_cost[$i] * $request->quantity[$i]),
                'user_id'      => auth()->user()->id,
                'business_id'  => request()->activeBusiness->id,
            ]));
        }

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Prescription Updated';
        $audit->save();

        return redirect()->route('prescriptions.show', $prescription->id)->with('success', 'Prescription updated successfully');
    }

    public function destroy($id)
    {
        $prescription = Prescription::find($id);
        $prescription->delete();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Prescription Deleted';
        $audit->save();

        return redirect()->route('prescriptions.index')->with('success', 'Prescription deleted successfully');
    }

    public function show($id)
    {
        $prescription = Prescription::with(['customer', 'items.product'])->findOrFail($id);
        $customer = $prescription->customer;
        $prescriptionProduct = PrescriptionProduct::where('prescription_id', $prescription->id)->with('items')->first();
        
        return Inertia::render('Backend/User/Prescription/View', [
            'prescription' => $prescription,
            'customer' => $customer,
            'products' => $prescriptionProduct ? $prescriptionProduct->items : [],
        ]);
    }

    public function change_status(Request $request, $id)
    {
        $prescription = Prescription::find($id);
        $prescription->status = $request->status;
        $prescription->save();

        // audit log
        $audit = new AuditLog();
        $audit->date_changed = date('Y-m-d H:i:s');
        $audit->changed_by = auth()->user()->id;
        $audit->event = 'Prescription Status Updated';
        $audit->save();

        return redirect()->route('prescriptions.index')->with('success', 'Prescription status updated successfully');
    }

    public function edit_status($id)
    {
        $prescription = Prescription::find($id);
        return view('backend.user.prescription.modals.status', compact('prescription', 'id'));
    }

    private function calculateTotal(Request $request)
    {
        $subTotal       = 0;
        $grandTotal     = 0;

        for ($i = 0; $i < count($request->product_id); $i++) {
            //Calculate Sub Total
            $line_qnt       = $request->quantity[$i];
            $line_unit_cost = $request->unit_cost[$i];
            $line_total     = ($line_qnt * $line_unit_cost);

            //Show Sub Total
            $subTotal = ($subTotal + $line_total);
        }

        //Calculate Grand Total
        $grandTotal = $subTotal;

        return array(
            'subTotal'       => $subTotal,
            'grandTotal'     => $grandTotal,
        );
    }

    public function find_prescription_products($id)
    {
        return PrescriptionProduct::where('id', $id)->with('items', 'customer')->first();
    }
}
