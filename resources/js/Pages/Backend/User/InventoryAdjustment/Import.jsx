import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, FileUp, Upload } from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import PageHeader from '@/Components/PageHeader';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { SidebarInset } from '@/Components/ui/sidebar';
import { Checkbox } from '@/Components/ui/checkbox';
import InputError from '@/Components/InputError';

export default function Import() {
  const [file, setFile] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [errors, setErrors] = useState({});
  const [hasHeading, setHasHeading] = useState(true);

  const handleFileChange = (e) => {
    const selectedFile = e.target.files[0];
    setFile(selectedFile);
    setErrors({});
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setUploading(true);
    
    if (!file) {
      setErrors({ import_file: 'Please select a file to upload' });
      setUploading(false);
      return;
    }
    
    const formData = new FormData();
    formData.append('import_file', file);
    formData.append('heading', hasHeading ? '1' : '0'); // Convert boolean to string "1" or "0"
    
    // Skip onSuccess handler - let the backend redirect
    router.post(route('inventory_adjustments.import.store'), formData, {
      forceFormData: true,
      preserveState: false,
      preserveScroll: false,
      onError: (errors) => {
        setErrors(errors);
        setUploading(false);
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <Head title="Import Inventory Adjustments" />
      <SidebarInset>
        <div className="main-content">
          <PageHeader 
            page="Products" 
            subpage="Import Inventory Adjustments" 
            url="inventory_adjustments.index"
          />
          
          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            <Alert variant="warning" className="mb-4">
              <AlertTriangle className="h-4 w-4" />
              <AlertTitle>Important Information</AlertTitle>
              <AlertDescription>
                <p className="mb-2">Please follow these guidelines when importing inventory adjustments:</p>
                <ul className="list-disc pl-5 space-y-1">
                  <li>Use the template file for correct formatting</li>
                  <li>Make sure all products exist in the system</li>
                  <li>Adjusted quantities should be positive for additions, negative for deductions</li>
                  <li>All required fields must be filled</li>
                  <li>Maximum file size: 10MB</li>
                  <li>Supported formats: .xlsx, .xls, .csv</li>
                </ul>
              </AlertDescription>
            </Alert>
            
            <div className="bg-white shadow-sm rounded-lg p-6">
              <form onSubmit={handleSubmit} className="space-y-6" encType="multipart/form-data">
                <div>
                  <label htmlFor="import_file" className="block text-sm font-medium text-gray-700 mb-1">
                    Select File
                  </label>
                  <div className="flex flex-col space-y-2">
                    <Input
                      id="import_file"
                      name="import_file"
                      type="file"
                      accept=".xlsx,.xls,.csv"
                      onChange={handleFileChange}
                      className={errors.import_file ? 'border-red-500' : ''}
                    />
                    <InputError className="mt-2" message={errors.import_file} />
                    {file && (
                      <div className="text-sm text-gray-500 mt-1">
                        Selected: {file.name} ({(file.size / 1024).toFixed(2)} KB)
                      </div>
                    )}
                    {errors.import_file && (
                      <p className="text-sm text-red-600 mt-1">
                        {errors.import_file}
                      </p>
                    )}
                  </div>
                </div>
                
                <div className="flex items-center space-x-2">
                  <Checkbox 
                    id="hasHeading" 
                    name="heading"
                    checked={hasHeading} 
                    onCheckedChange={setHasHeading} 
                  />
                  <label 
                    htmlFor="hasHeading" 
                    className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                  >
                    First row contains column headers
                  </label>
                </div>
                
                <div className="flex justify-between items-center border-t pt-4 mt-4">
                  <a 
                    href="/uploads/media/defaults/sample_adjustments.xlsx" 
                    download="sample_adjustments.xlsx"
                    className="text-blue-600 hover:text-blue-800 text-sm flex items-center"
                  >
                    <FileUp className="h-4 w-4 mr-1" />
                    Download Template
                  </a>
                  
                  <div className="space-x-2">
                    <Link href={route('inventory_adjustments.index')}>
                      <Button type="button" variant="outline">
                        Cancel
                      </Button>
                    </Link>
                    
                    <Button type="submit" disabled={uploading}>
                      {uploading ? (
                        <>
                          <span className="animate-spin mr-2">⟳</span>
                          Uploading...
                        </>
                      ) : (
                        <>
                          <Upload className="h-4 w-4 mr-2" />
                          Continue
                        </>
                      )}
                    </Button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
