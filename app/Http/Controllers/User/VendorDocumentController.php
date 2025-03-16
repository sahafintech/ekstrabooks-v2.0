<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VendorDocument;
use App\Models\Vendor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VendorDocumentController extends Controller
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
        $vendor = Vendor::where('id', $id)
            ->where('business_id', $business_id)
            ->first();
            
        if (!$vendor) {
            return redirect()->back()->with('error', _lang('Vendor not found!'));
        }
        
        return view('backend.user.vendor.tabs.modal.create', compact('vendor'));
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
            'vendor_id' => 'required|exists:vendors,id',
            'name' => 'required|string|max:191',
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsx,csv|max:2048'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Get active business
        $business_id = request()->activeBusiness->id;

        // Check if vendor belongs to active business
        $vendor = Vendor::where('id', $request->vendor_id)
            ->where('business_id', $business_id)
            ->first();

        if (!$vendor) {
            return redirect()->back()->with('error', _lang('Vendor not found!'));
        }

        // Upload file
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/documents'), $fileName);
        }

        // Create document
        $document = new VendorDocument();
        $document->vendor_id = $request->vendor_id;
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
        
        $document = VendorDocument::where('id', $id)
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
