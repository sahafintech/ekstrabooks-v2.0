import React, { useState } from "react";
import { router, Head } from "@inertiajs/react";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { CheckCircle2, ArrowLeft } from "lucide-react";
import PageHeader from "@/Components/PageHeader";
import ImportStepConfigure from "@/Components/shared/Import/ImportStepConfigure";
import ImportStepMapFields from "@/Components/shared/Import/ImportStepMapFields";
import ImportStepPreview from "@/Components/shared/Import/ImportStepPreview";

export default function Import() {
  const [currentStep, setCurrentStep] = useState(1);
  const [importData, setImportData] = useState({
    file: null,
    fileName: null,
    duplicateHandling: "skip",
    encoding: "UTF-8",
    fieldMappings: {},
    preview: null,
  });

  const steps = [
    { number: 1, label: "Configure", completed: currentStep > 1 },
    { number: 2, label: "Map Fields", completed: currentStep > 2 },
    { number: 3, label: "Preview", completed: false },
  ];


  // Define system fields for product import
  const productSystemFields = [
    { key: 'id', label: 'ID', required: true },
    { key: 'name', label: 'Item Name', required: true },
    { key: 'type', label: 'Type', required: false },
    { key: 'unit', label: 'Unit', required: false },
    { key: 'purchase_cost', label: 'Purchase Cost', required: false },
    { key: 'selling_price', label: 'Selling Price', required: false },
    { key: 'descriptions', label: 'Description', required: false },
    { key: 'initial_stock', label: 'Initial Stock', required: false },
    { key: 'stock_management', label: 'Stock Management', required: false },
    { key: 'allow_for_selling', label: 'Allow for Selling', required: false },
    { key: 'income_account_name', label: 'Income Account', required: false },
    { key: 'allow_for_purchasing', label: 'Allow for Purchasing', required: false },
    { key: 'expense_account_name', label: 'Expense Account', required: false },
    { key: 'status', label: 'Status', required: false },
    { key: 'expiry_date', label: 'Expiry Date', required: false },
    { key: 'code', label: 'Code', required: false },
    { key: 'image', label: 'Image', required: false },
    { key: 'sub_category', label: 'Sub Category', required: false },
    { key: 'brand', label: 'Brand', required: false },
  ];

  const handleStep1Complete = (data) => {
    setImportData((prev) => ({ ...prev, ...data }));
    setCurrentStep(2);
  };

  const handleStep2Complete = (fieldMappings) => {
    setImportData((prev) => ({ ...prev, fieldMappings }));
    setCurrentStep(3);
  };

  const handleStep3Complete = (preview) => {
    setImportData((prev) => ({ ...prev, preview }));
  };

  const handlePrevious = () => {
    if (currentStep > 1) {
      setCurrentStep(currentStep - 1);
    }
  };

  const handleImport = () => {
    if (!importData.preview) {
      return;
    }

    const formData = new FormData();
    formData.append("products_file", importData.file);
    formData.append("duplicate_handling", importData.duplicateHandling);
    formData.append("encoding", importData.encoding);
    formData.append("field_mappings", JSON.stringify(importData.fieldMappings));

    router.post(route('products.import'), formData, {
      forceFormData: true,
      onSuccess: () => {
        router.visit(route('products.index'));
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <Head title="Import Products" />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Products"
            subpage="Import"
            url="products.index"
          />
            {/* Header */}
            <div className="flex items-center justify-between p-6 border-b">
              <div className="flex items-center gap-4">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => router.visit(route('products.index'))}
                  className="flex items-center gap-2"
                >
                  <ArrowLeft className="w-4 h-4" />
                  Back
                </Button>
                <h2 className="text-2xl font-bold">Items - Select File</h2>
              </div>
            </div>

            {/* Steps Indicator */}
            <div className="px-6 pt-6">
              <div className="flex items-center justify-between">
                {steps.map((step, index) => (
                  <React.Fragment key={step.number}>
                    <div className="flex flex-col items-center flex-1">
                      <div
                        className={`w-10 h-10 rounded-full flex items-center justify-center font-semibold ${
                          currentStep === step.number
                            ? "bg-blue-600 text-white"
                            : step.completed
                            ? "bg-green-500 text-white"
                            : "bg-gray-200 text-gray-600"
                        }`}
                      >
                        {step.completed ? (
                          <CheckCircle2 className="w-6 h-6" />
                        ) : (
                          step.number
                        )}
                      </div>
                      <span
                        className={`mt-2 text-sm font-medium ${
                          currentStep === step.number
                            ? "text-blue-600"
                            : step.completed
                            ? "text-green-600"
                            : "text-gray-500"
                        }`}
                      >
                        {step.label}
                      </span>
                    </div>
                    {index < steps.length - 1 && (
                      <div
                        className={`flex-1 h-1 mx-2 ${
                          step.completed ? "bg-green-500" : "bg-gray-200"
                        }`}
                      />
                    )}
                  </React.Fragment>
                ))}
              </div>
            </div>

            {/* Step Content */}
            <div className="p-6">
              {currentStep === 1 && (
                <ImportStepConfigure
                  onComplete={handleStep1Complete}
                  sampleFileUrl="/uploads/media/default/sample_items.xlsx"
                  maxFileSize={25}
                  acceptedFormats={['CSV', 'TSV', 'XLS', 'XLSX']}
                  defaultDuplicateHandling={importData.duplicateHandling}
                  defaultEncoding={importData.encoding}
                />
              )}

              {currentStep === 2 && (
                <ImportStepMapFields
                  file={importData.file}
                  fileName={importData.fileName}
                  systemFields={productSystemFields}
                  onComplete={handleStep2Complete}
                  onPreview={(preview) => {
                    handleStep3Complete(preview);
                    setCurrentStep(3);
                  }}
                  mapFieldsRoute={route('products.import.map_fields')}
                  previewRoute={route('products.import.preview')}
                  fieldMappings={importData.fieldMappings}
                  duplicateHandling={importData.duplicateHandling}
                  encoding={importData.encoding}
                />
              )}

              {currentStep === 3 && (
                <ImportStepPreview
                  preview={importData.preview}
                  onRefresh={() => {
                    const formData = new FormData();
                    formData.append("file", importData.file);
                    formData.append("field_mappings", JSON.stringify(importData.fieldMappings));
                    formData.append("duplicate_handling", importData.duplicateHandling);
                    formData.append("encoding", importData.encoding);

                    // Use axios for consistent JSON response
                    axios.post(route('products.import.preview'), formData, {
                      headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'multipart/form-data',
                        'X-Requested-With': 'XMLHttpRequest',
                      },
                    })
                    .then((response) => {
                      const preview = response.data?.preview || {};
                      handleStep3Complete(preview);
                    })
                    .catch((error) => {
                      console.error('Refresh preview error:', error);
                      if (error.response?.data?.error) {
                        alert('Error refreshing preview: ' + error.response.data.error);
                      } else {
                        alert('Error refreshing preview. Please try again.');
                      }
                    });
                  }}
                />
              )}
            </div>

            {/* Footer Actions - Only show for steps that need it */}
            {currentStep > 1 && (
              <div className="flex items-center justify-between p-6 border-t">
                <div>
                  <Button variant="outline" onClick={handlePrevious}>
                    &lt; Previous
                  </Button>
                </div>
                <div className="flex gap-3">
                  <Button variant="outline" onClick={() => router.visit(route('products.index'))}>
                    Cancel
                  </Button>
                  {currentStep === 3 && importData.preview && (
                    <Button onClick={handleImport}>Import</Button>
                  )}
                </div>
              </div>
            )}
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}

