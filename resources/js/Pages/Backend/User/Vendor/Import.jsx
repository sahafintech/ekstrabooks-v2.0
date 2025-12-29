import React, { useState, useCallback, useEffect, useRef } from "react";
import { router, usePage, Head } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/Components/ui/card";
import { Alert, AlertDescription } from "@/Components/ui/alert";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { 
  Check, 
  Download, 
  FileSpreadsheet, 
  Upload, 
  X, 
  AlertCircle, 
  CheckCircle2, 
  ArrowRight,
  ArrowLeft,
  ChevronRight,
  Loader2
} from "lucide-react";

const SYSTEM_FIELDS = [
  { value: "id", label: "ID", required: false },
  { value: "name", label: "Name", required: true },
  { value: "company_name", label: "Company Name", required: false },
  { value: "email", label: "Email", required: false },
  { value: "mobile", label: "Mobile", required: false },
  { value: "country", label: "Country", required: false },
  { value: "city", label: "City", required: false },
  { value: "address", label: "Address", required: false },
  { value: "vat_id", label: "VAT ID", required: false },
  { value: "registration_no", label: "Registration No", required: false },
  { value: "contract_no", label: "Contract No", required: false },
  { value: "skip", label: "Skip this column", required: false }
];

export default function Import() {
  const { previewData } = usePage().props;
  const [currentStep, setCurrentStep] = useState(0);
  const [selectedFile, setSelectedFile] = useState(null);
  const [isUploading, setIsUploading] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  const [fieldMappings, setFieldMappings] = useState({});
  const [isDragOver, setIsDragOver] = useState(false);
  const fileInputRef = useRef(null);

  // Auto-advance step based on previewData
  useEffect(() => {
    if (previewData?.headers && !previewData?.total_rows) {
      setCurrentStep(1);
    } else if (previewData?.total_rows) {
      setCurrentStep(2);
    }
  }, [previewData]);

  // Auto-map fields based on common names
  const handleAutoMap = useCallback(() => {
    if (!previewData?.headers) return;

    const newMappings = {};
    const usedFields = new Set();

    previewData.headers.forEach((header) => {
      const normalizedHeader = header.toLowerCase().replace(/[^a-z0-9]/g, "_").replace(/_+/g, "_").replace(/^_|_$/g, "");

      let bestMatch = null;
      let bestScore = 0;

      for (const field of SYSTEM_FIELDS) {
        if (field.value === "skip" || usedFields.has(field.value)) continue;

        const normalizedFieldValue = field.value.toLowerCase().replace(/[^a-z0-9]/g, "_").replace(/_+/g, "_");
        const normalizedFieldLabel = field.label.toLowerCase().replace(/[^a-z0-9]/g, "_").replace(/_+/g, "_");

        let score = 0;

        if (normalizedHeader === normalizedFieldValue) {
          score = 100;
        } else if (normalizedHeader === normalizedFieldLabel) {
          score = 90;
        } else if (normalizedHeader === normalizedFieldValue.split("_")[0] && normalizedFieldValue.split("_").length === 1) {
          score = 80;
        } else if (normalizedFieldValue === normalizedHeader && normalizedHeader.length > 2) {
          score = 75;
        } else if (normalizedHeader.includes(normalizedFieldLabel) && normalizedFieldLabel.length > 3) {
          score = 60;
        } else if (normalizedFieldLabel.includes(normalizedHeader) && normalizedHeader.length > 4) {
          score = 50;
        } else if (normalizedHeader.includes(normalizedFieldValue) && normalizedFieldValue.length > 4) {
          score = 40;
        }

        if (score > bestScore) {
          bestScore = score;
          bestMatch = field;
        }
      }

      if (bestMatch && bestScore >= 40) {
        newMappings[header] = bestMatch.value;
        usedFields.add(bestMatch.value);
      }
    });

    setFieldMappings(newMappings);
  }, [previewData?.headers]);

  useEffect(() => {
    if (currentStep === 1 && previewData?.headers) {
      handleAutoMap();
    }
  }, [currentStep, previewData?.headers, handleAutoMap]);

  // Drag and drop handlers
  const handleDragOver = useCallback((e) => {
    e.preventDefault();
    setIsDragOver(true);
  }, []);

  const handleDragLeave = useCallback((e) => {
    e.preventDefault();
    setIsDragOver(false);
  }, []);

  const handleDrop = useCallback((e) => {
    e.preventDefault();
    setIsDragOver(false);
    const file = e.dataTransfer.files?.[0];
    if (file && (file.name.endsWith('.xlsx') || file.name.endsWith('.xls') || file.name.endsWith('.csv'))) {
      setSelectedFile(file);
    }
  }, []);

  const handleFileSelect = useCallback((event) => {
    const file = event.target.files?.[0];
    if (file) {
      setSelectedFile(file);
    }
  }, []);

  const handleUploadFile = useCallback(() => {
    if (!selectedFile) return;

    const formData = new FormData();
    formData.append("file", selectedFile);

    setIsUploading(true);
    router.post(route("vendors.import.upload"), formData, {
      onSuccess: () => {
        setCurrentStep(1);
      },
      onError: (errors) => {
        console.error("Upload error:", errors);
      },
      onFinish: () => {
        setIsUploading(false);
      }
    });
  }, [selectedFile]);

  const handleRemoveFile = useCallback(() => {
    setSelectedFile(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = "";
    }
  }, []);

  const handleFieldMappingChange = useCallback((excelColumn, systemField) => {
    setFieldMappings((prev) => ({
      ...prev,
      [excelColumn]: systemField
    }));
  }, []);

  const handleGeneratePreview = useCallback(() => {
    setIsProcessing(true);
    router.post(
      route("vendors.import.preview"),
      { mappings: fieldMappings },
      {
        onSuccess: () => {
          setCurrentStep(2);
        },
        onError: (errors) => {
          console.error("Preview error:", errors);
        },
        onFinish: () => {
          setIsProcessing(false);
        }
      }
    );
  }, [fieldMappings]);

  const handleConfirmImport = useCallback(() => {
    setIsProcessing(true);
    router.post(
      route("vendors.import.execute"),
      { mappings: fieldMappings },
      {
        onSuccess: () => {
          router.visit(route("vendors.index"));
        },
        onFinish: () => {
          setIsProcessing(false);
        }
      }
    );
  }, [fieldMappings]);

  const handleCancel = useCallback(() => {
    router.visit(route("vendors.index"));
  }, []);

  const handleBack = useCallback(() => {
    if (currentStep > 0) {
      setCurrentStep(currentStep - 1);
    }
  }, [currentStep]);

  // Check if required fields are mapped
  const isNameMapped = Object.values(fieldMappings).includes("name");
  const isAllRequiredMapped = isNameMapped;

  const steps = ["Upload File", "Map Fields", "Preview & Import"];

  return (
    <AuthenticatedLayout>
      <Head title="Import Suppliers" />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Suppliers"
            subpage="Import"
            url="vendors.index"
          />

          <div className="max-w-4xl mx-auto pb-8 px-4">
            {/* Step Indicator */}
            <div className="flex items-center gap-2 mb-6 text-sm">
              {steps.map((step, index) => (
                <div key={index} className="flex items-center gap-2">
                  <div className={`flex items-center gap-2 px-3 py-1.5 rounded-full transition-colors ${
                    currentStep === index 
                      ? "bg-primary text-primary-foreground" 
                      : currentStep > index 
                        ? "bg-green-500/15 text-green-600" 
                        : "bg-muted text-muted-foreground"
                  }`}>
                    {currentStep > index ? (
                      <Check className="size-3.5" />
                    ) : (
                      <span className="font-semibold">{index + 1}</span>
                    )}
                    <span className="font-medium">{step}</span>
                  </div>
                  {index < steps.length - 1 && (
                    <ChevronRight className="size-4 text-muted-foreground" />
                  )}
                </div>
              ))}
            </div>

            {/* Step 1: Upload File */}
            {currentStep === 0 && (
              <Card>
                <CardHeader>
                  <CardTitle>Upload File</CardTitle>
                  <CardDescription>Select an Excel or CSV file to import suppliers</CardDescription>
                </CardHeader>
                <CardContent className="space-y-5">
                  {/* File Upload Area */}
                  <div>
                    <label className="block font-medium text-sm mb-2">
                      Select File <span className="text-destructive">*</span>
                    </label>
                    <input
                      ref={fileInputRef}
                      type="file"
                      accept=".xlsx,.xls,.csv"
                      onChange={handleFileSelect}
                      className="hidden"
                    />

                    {!selectedFile ? (
                      <div
                        onClick={() => fileInputRef.current?.click()}
                        onDragOver={handleDragOver}
                        onDragLeave={handleDragLeave}
                        onDrop={handleDrop}
                        className={`
                          cursor-pointer rounded-lg border-2 border-dashed p-8 text-center transition-colors
                          ${isDragOver 
                            ? "border-primary bg-primary/5" 
                            : "border-muted-foreground/25 hover:border-primary/50 hover:bg-muted/50"
                          }
                        `}
                      >
                        <Upload className={`size-10 mx-auto mb-3 ${isDragOver ? "text-primary" : "text-muted-foreground"}`} />
                        <p className="font-medium mb-1">
                          Drop your file here, or <span className="text-primary">browse</span>
                        </p>
                        <p className="text-sm text-muted-foreground">
                          Supports .xlsx, .xls, .csv (max 10MB)
                        </p>
                      </div>
                    ) : (
                      <div className="rounded-lg border border-green-500/40 bg-green-500/5 p-4">
                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-3">
                            <div className="p-2 bg-green-500/15 rounded-lg">
                              <FileSpreadsheet className="size-6 text-green-600" />
                            </div>
                            <div>
                              <p className="font-medium">{selectedFile.name}</p>
                              <p className="text-sm text-muted-foreground">
                                {(selectedFile.size / 1024).toFixed(1)} KB
                              </p>
                            </div>
                          </div>
                          <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={handleRemoveFile}
                            className="text-destructive hover:text-destructive hover:bg-destructive/10"
                          >
                            <X className="size-5" />
                          </Button>
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Info Box */}
                  <Alert>
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>
                      <h4 className="font-medium mb-2">Import Guidelines</h4>
                      <ul className="text-sm space-y-1">
                        <li>• First row should contain column headers</li>
                        <li>• "Name" is required for each supplier</li>
                        <li>• If "ID" is provided, existing suppliers will be updated</li>
                        <li>• If "Email" matches an existing supplier, it will be updated</li>
                      </ul>
                    </AlertDescription>
                  </Alert>

                  {/* Template Download */}
                  <div className="flex items-center justify-between p-4 border rounded-lg">
                    <div>
                      <p className="font-medium text-sm">Need a template?</p>
                      <p className="text-sm text-muted-foreground">Download our sample Excel file</p>
                    </div>
                    <a href="/uploads/media/default/sample_suppliers.xlsx" download>
                      <Button variant="outline" size="sm">
                        <Download className="size-4 mr-1.5" />
                        Download
                      </Button>
                    </a>
                  </div>
                </CardContent>
                <CardFooter className="flex justify-between">
                  <Button
                    type="button"
                    variant="outline"
                    onClick={handleCancel}
                  >
                    Cancel
                  </Button>
                  <Button
                    type="button"
                    onClick={handleUploadFile}
                    disabled={!selectedFile || isUploading}
                  >
                    {isUploading ? (
                      <>
                        <Loader2 className="size-4 mr-2 animate-spin" />
                        Uploading...
                      </>
                    ) : (
                      <>
                        Continue
                        <ArrowRight className="size-4 ml-1.5" />
                      </>
                    )}
                  </Button>
                </CardFooter>
              </Card>
            )}

            {/* Step 2: Map Fields */}
            {currentStep === 1 && previewData?.headers && (
              <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                  <div>
                    <CardTitle>Map Columns</CardTitle>
                    <CardDescription>Map your file columns to supplier fields</CardDescription>
                  </div>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={handleAutoMap}
                  >
                    Auto-Map Fields
                  </Button>
                </CardHeader>
                <CardContent>
                  {/* Validation Status */}
                  <Alert className={`mb-5 ${
                    isAllRequiredMapped 
                      ? "border-green-500/50 bg-green-500/10" 
                      : "border-yellow-500/50 bg-yellow-500/10"
                  }`}>
                    {isAllRequiredMapped ? (
                      <>
                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                        <AlertDescription className="text-green-600 font-medium">
                          All required fields mapped - ready to continue
                        </AlertDescription>
                      </>
                    ) : (
                      <>
                        <AlertCircle className="h-4 w-4 text-yellow-600" />
                        <AlertDescription className="text-yellow-600 font-medium">
                          Map the required fields to continue: 
                          {!isNameMapped && <span className="ml-1">"Name"</span>}
                        </AlertDescription>
                      </>
                    )}
                  </Alert>

                  {/* Mapping List */}
                  <div className="space-y-3">
                    {previewData.headers.map((header, index) => {
                      const mappedField = SYSTEM_FIELDS.find(
                        (f) => f.value === fieldMappings[header]
                      );
                      
                      return (
                        <div key={index} className="flex items-center gap-4 p-3 rounded-lg bg-muted/50 border">
                          <div className="flex items-center gap-2 min-w-[180px]">
                            <FileSpreadsheet className="size-4 text-muted-foreground" />
                            <span className="font-medium text-sm truncate">{header}</span>
                          </div>
                          <ArrowRight className="size-4 text-muted-foreground shrink-0" />
                          <div className="flex-1 max-w-xs">
                            <SearchableCombobox
                              options={SYSTEM_FIELDS.map((field) => ({
                                id: field.value,
                                name: field.label + (field.required ? " *" : "")
                              }))}
                              value={fieldMappings[header] || ""}
                              onChange={(value) => handleFieldMappingChange(header, value)}
                              placeholder="Select field..."
                              emptyMessage="No matching field found."
                            />
                          </div>
                          {mappedField?.required && (
                            <span className="text-xs font-medium text-destructive bg-destructive/10 px-2 py-0.5 rounded shrink-0">
                              Required
                            </span>
                          )}
                        </div>
                      );
                    })}
                  </div>
                </CardContent>
                <CardFooter className="flex justify-between">
                  <Button
                    type="button"
                    variant="outline"
                    onClick={handleBack}
                  >
                    <ArrowLeft className="size-4 mr-1.5" />
                    Back
                  </Button>
                  <Button
                    type="button"
                    onClick={handleGeneratePreview}
                    disabled={isProcessing || !isAllRequiredMapped}
                  >
                    {isProcessing ? (
                      <>
                        <Loader2 className="size-4 mr-2 animate-spin" />
                        Processing...
                      </>
                    ) : (
                      <>
                        Preview
                        <ArrowRight className="size-4 ml-1.5" />
                      </>
                    )}
                  </Button>
                </CardFooter>
              </Card>
            )}

            {/* Step 3: Preview & Import */}
            {currentStep === 2 && previewData && (
              <div className="space-y-6">
                {/* Summary Stats */}
                <div className="grid grid-cols-3 gap-4">
                  <Card className="text-center p-4">
                    <p className="text-3xl font-bold">{previewData.total_rows}</p>
                    <p className="text-sm text-muted-foreground mt-1">Total Records</p>
                  </Card>
                  <Card className="text-center p-4">
                    <p className="text-3xl font-bold text-green-600">{previewData.valid_count}</p>
                    <p className="text-sm text-muted-foreground mt-1">Ready to Import</p>
                  </Card>
                  <Card className="text-center p-4">
                    <p className="text-3xl font-bold text-destructive">{previewData.error_count}</p>
                    <p className="text-sm text-muted-foreground mt-1">Will be Skipped</p>
                  </Card>
                </div>

                {/* Success Notice - All records valid */}
                {previewData.error_count === 0 && (
                  <Alert className="border-green-500/30 bg-green-500/5">
                    <CheckCircle2 className="h-4 w-4 text-green-600" />
                    <AlertDescription className="text-green-600">
                      <span className="font-medium">All {previewData.total_rows} record(s)</span> are valid and ready to import!
                    </AlertDescription>
                  </Alert>
                )}

                {/* Error Notice - Some errors found */}
                {previewData.error_count > 0 && (
                  <Alert className="border-destructive/30 bg-destructive/5">
                    <AlertCircle className="h-4 w-4 text-destructive" />
                    <AlertDescription className="text-destructive">
                      <span className="font-medium">{previewData.error_count} record(s)</span> have validation errors and will be skipped during import.
                    </AlertDescription>
                  </Alert>
                )}

                {/* Preview Table - Only show if there are errors */}
                {previewData.error_count > 0 && previewData.preview_records?.length > 0 && (
                  <Card>
                    <CardHeader>
                      <CardTitle className="text-base">Records with Errors ({previewData.error_count})</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                      <Table>
                        <TableHeader>
                          <TableRow>
                            <TableHead className="w-20">Row</TableHead>
                            <TableHead className="w-28">Status</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Issues</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {previewData.preview_records.map((record, index) => (
                            <TableRow key={index}>
                              <TableCell className="text-muted-foreground">{record.row}</TableCell>
                              <TableCell>
                                <span className="inline-flex items-center gap-1.5 text-xs font-medium py-1 px-2.5 rounded bg-destructive/15 text-destructive">
                                  <AlertCircle className="size-3.5" /> Error
                                </span>
                              </TableCell>
                              <TableCell className="text-muted-foreground">
                                {record.data.name || <span className="italic">—</span>}
                              </TableCell>
                              <TableCell className="text-muted-foreground">
                                {record.data.email || <span className="italic">—</span>}
                              </TableCell>
                              <TableCell>
                                {record.errors && record.errors.length > 0 ? (
                                  <ul className="space-y-1">
                                    {record.errors.map((error, i) => (
                                      <li key={i} className="text-sm text-destructive">• {error}</li>
                                    ))}
                                  </ul>
                                ) : (
                                  <span className="text-sm text-muted-foreground">No issues</span>
                                )}
                              </TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </CardContent>
                  </Card>
                )}

                {/* Action Buttons */}
                <Card>
                  <CardFooter className="flex justify-between pt-6">
                    <Button
                      type="button"
                      variant="outline"
                      onClick={handleBack}
                    >
                      <ArrowLeft className="size-4 mr-1.5" />
                      Back
                    </Button>
                    <div className="flex gap-3">
                      <Button
                        type="button"
                        variant="outline"
                        onClick={handleCancel}
                      >
                        Cancel
                      </Button>
                      <Button
                        type="button"
                        onClick={handleConfirmImport}
                        disabled={isProcessing || previewData.valid_count === 0}
                      >
                        {isProcessing ? (
                          <>
                            <Loader2 className="size-4 mr-2 animate-spin" />
                            Importing...
                          </>
                        ) : (
                          <>
                            Import {previewData.valid_count} Supplier{previewData.valid_count !== 1 ? 's' : ''}
                          </>
                        )}
                      </Button>
                    </div>
                  </CardFooter>
                </Card>
              </div>
            )}
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
