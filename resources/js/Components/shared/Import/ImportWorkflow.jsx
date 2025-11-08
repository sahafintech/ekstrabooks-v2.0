import React, { useState } from "react";
import { router } from "@inertiajs/react";
import axios from "axios";
import { X, CheckCircle2 } from "lucide-react";
import { Button } from "@/Components/ui/button";
import ImportStepConfigure from "./ImportStepConfigure";
import ImportStepMapFields from "./ImportStepMapFields";
import ImportStepPreview from "./ImportStepPreview";

export default function ImportWorkflow({
  show,
  onClose,
  title = "Import Items",
  configureRoute,
  mapFieldsRoute,
  previewRoute,
  executeRoute,
  sampleFileUrl,
  systemFields = [],
  maxFileSize = 25, // MB
  acceptedFormats = ["CSV", "TSV", "XLS", "XLSX"],
  onSuccess,
}) {
  const [currentStep, setCurrentStep] = useState(1);
  const [importData, setImportData] = useState({
    file: null,
    fileName: null,
    duplicateHandling: "skip", // "skip" or "overwrite"
    encoding: "UTF-8",
    fieldMappings: {},
    preview: null,
  });

  const steps = [
    { number: 1, label: "Configure", completed: currentStep > 1 },
    { number: 2, label: "Map Fields", completed: currentStep > 2 },
    { number: 3, label: "Preview", completed: false },
  ];

  const handleStep1Complete = (data) => {
    // Store step 1 data and move to step 2
    setImportData((prev) => ({ ...prev, ...data }));
    // Step 2 will automatically load file headers when it mounts
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

  const handleCancel = () => {
    setCurrentStep(1);
    setImportData({
      file: null,
      fileName: null,
      duplicateHandling: "skip",
      encoding: "UTF-8",
      fieldMappings: {},
      preview: null,
    });
    onClose();
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

    router.post(executeRoute, formData, {
      forceFormData: true,
      onSuccess: () => {
        if (onSuccess) {
          onSuccess();
        }
        handleCancel();
      },
    });
  };

  if (!show) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b">
          <h2 className="text-xl font-bold">{title}</h2>
          <button
            onClick={handleCancel}
            className="text-gray-400 hover:text-gray-600"
          >
            <X className="w-6 h-6" />
          </button>
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
        <div className="flex-1 overflow-y-auto p-6">
          {currentStep === 1 && (
            <ImportStepConfigure
              onComplete={handleStep1Complete}
              sampleFileUrl={sampleFileUrl}
              maxFileSize={maxFileSize}
              acceptedFormats={acceptedFormats}
              defaultDuplicateHandling={importData.duplicateHandling}
              defaultEncoding={importData.encoding}
            />
          )}

          {currentStep === 2 && (
            <ImportStepMapFields
              file={importData.file}
              fileName={importData.fileName}
              systemFields={systemFields}
              onComplete={handleStep2Complete}
              onPreview={(preview) => {
                handleStep3Complete(preview);
                setCurrentStep(3);
              }}
              mapFieldsRoute={mapFieldsRoute}
              previewRoute={previewRoute}
              fieldMappings={importData.fieldMappings}
              duplicateHandling={importData.duplicateHandling}
              encoding={importData.encoding}
            />
          )}

          {currentStep === 3 && (
            <ImportStepPreview
              preview={importData.preview}
              onRefresh={() => {
                // Re-fetch preview
                const formData = new FormData();
                formData.append("file", importData.file);
                formData.append("field_mappings", JSON.stringify(importData.fieldMappings));
                formData.append("duplicate_handling", importData.duplicateHandling);
                formData.append("encoding", importData.encoding);

                // Use axios for consistent JSON response
                axios.post(previewRoute, formData, {
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

        {/* Footer Actions */}
        <div className="flex items-center justify-between p-6 border-t">
          <div>
            {currentStep > 1 && (
              <Button variant="outline" onClick={handlePrevious}>
                &lt; Previous
              </Button>
            )}
          </div>
          <div className="flex gap-3">
            <Button variant="outline" onClick={handleCancel}>
              Cancel
            </Button>
            {currentStep === 3 && importData.preview && (
              <Button onClick={handleImport}>Import</Button>
            )}
            {currentStep < 3 && (
              <Button
                onClick={() => {
                  // Step navigation is handled by child components
                }}
                style={{ display: "none" }}
              >
                Next
              </Button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

