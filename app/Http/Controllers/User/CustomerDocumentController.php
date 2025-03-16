<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerDocument;
use App\Models\Customer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CustomerDocumentController extends Controller
{
    /**
     * Show the form for creating a new document.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {
        $business_id = request()->activeBusiness->id;
        $customer = Customer::where('id', $id)
            ->where('business_id', $business_id)
            ->first();
            
        if (!$customer) {
            return redirect()->back()->with('error', _lang('Customer not found!'));
        }
        
        return view('backend.user.customer.tabs.modal.create', compact('customer'));
    }

    /**
     * Store a newly created document in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:191',
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsx,csv|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => _lang('Validation Error'),
                'errors' => $validator->errors()
            ], 422);
        }

        // Get active business
        $business_id = request()->activeBusiness->id;

        // Check if customer belongs to active business
        $customer = Customer::where('id', $request->customer_id)
            ->where('business_id', $business_id)
            ->first();

        if (!$customer) {
            return redirect()->back()->with('error', _lang('Customer not found!'));
        }

        // Upload file
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/documents'), $fileName);
        }

        // Create document
        $document = new CustomerDocument();
        $document->customer_id = $request->customer_id;
        $document->name = $request->name;
        $document->document = $fileName;
        $document->business_id = $business_id;
        $document->save();

        return redirect()->back()->with('success', _lang('Document uploaded successfully!'));
    }

    /**
     * Remove the specified document from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $business_id = request()->activeBusiness->id;
        
        $document = CustomerDocument::where('id', $id)
            ->where('business_id', $business_id)
            ->first();

        if (!$document) {
            return redirect()->back()->with('error', _lang('Document not found!'));
        }

        // Delete file from storage
        if (file_exists(public_path('uploads/documents/' . $document->document))) {
            unlink(public_path('uploads/documents/' . $document->document));
        }

        $document->delete();

        return redirect()->back()->with('success', _lang('Document deleted successfully!'));
    }
}
