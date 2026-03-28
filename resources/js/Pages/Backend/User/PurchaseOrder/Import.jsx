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
  Loader2,
  Info
} from "lucide-react";

const SYSTEM_FIELDS = [
  { value: "order_number", label: "Order Number", required: false, description: "Rows with same order_number become one purchase order with multiple items" },
  { value: "supplier_name", label: "Supplier Name", required: false },
  { value: "title", label: "Title", required: false },
  { value: "order_date", label: "Order Date", required: true },
  { value: "transaction_currency", label: "Transaction Currency", required: false },
  { value: "discount_type", label: "Discount Type (0=%, 1=Fixed)", required: false },
  { value: "discount_value", label: "Discount Value", required: false },
  { value: "is_inventory", label: "Is Inventory (1=Yes, 0=No)", required: false, description: "1 uses Inventory account, 0 requires Expense Account" },
  { value: "expense_account", label: "Expense Account", required: false },
  { value: "note", label: "Note", required: false },
  { value: "footer", label: "Footer", required: false },
  { value: "product_name", label: "Product Name", required: true },
  { value: "description", label: "Item Description", required: false },
  { value: "quantity", label: "Quantity", required: true },
  { value: "unit_cost", label: "Unit Cost", required: true },
  { value: "tax", label: "Tax Name", required: false, description: "Optional. Use tax names from Tax Database" },
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

  useEffect(() => {
    if (previewData?.headers && !previewData?.total_rows) {
      setCurrentStep(1);
    } else if (previewData?.total_rows) {
      setCurrentStep(2);
    }
  }, [previewData]);

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
        } else if (normalizedHeader.includes("product") && field.value === "product_name") {
          score = 85;
        } else if ((normalizedHeader.includes("supplier") || normalizedHeader.includes("vendor")) && field.value === "supplier_name") {
          score = 85;
        } else if (normalizedHeader.includes("order") && field.value === "order_number") {
          score = 85;
        } else if (normalizedHeader.includes("date") && field.value === "order_date") {
          score = 85;
        } else if (normalizedHeader.includes("currency") && field.value === "transaction_currency") {
          score = 80;
        } else if ((normalizedHeader.includes("is_inventory") || normalizedHeader.includes("inventory")) && field.value === "is_inventory") {
          score = 90;
        } else if (normalizedHeader.includes("expense") && field.value === "expense_account") {
          score = 85;
        } else if (normalizedHeader.includes("qty") && field.value === "quantity") {
          score = 80;
        } else if (normalizedHeader.includes("cost") && field.value === "unit_cost") {
          score = 80;
        } else if (normalizedHeader.includes("price") && field.value === "unit_cost") {
          score = 75;
        } else if (normalizedHeader.includes("tax") && field.value === "tax") {
          score = 80;
        } else if (normalizedFieldValue === normalizedHeader && normalizedHeader.length > 2) {
          score = 75;
        } else if (normalizedHeader.includes(normalizedFieldLabel) && normalizedFieldLabel.length > 3) {
          score = 60;
        } else if (normalizedFieldLabel.includes(normalizedHeader) && normalizedHeader.length > 4) {
          score = 50;
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
    if (file && (file.name.endsWith(".xlsx") || file.name.endsWith(".xls") || file.name.endsWith(".csv"))) {
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
    router.post(route("purchase_orders.import.upload"), formData, {
      onSuccess: () => {
        setCurrentStep(1);
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
      route("purchase_orders.import.preview"),
      { mappings: fieldMappings },
      {
        onSuccess: () => {
          setCurrentStep(2);
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
      route("purchase_orders.import.execute"),
      { mappings: fieldMappings },
      {
        onSuccess: () => {
          router.visit(route("purchase_orders.index"));
        },
        onFinish: () => {
          setIsProcessing(false);
        }
      }
    );
  }, [fieldMappings]);

  const handleCancel = useCallback(() => {
    router.visit(route("purchase_orders.index"));
  }, []);

  const handleBack = useCallback(() => {
    if (currentStep > 0) {
      setCurrentStep(currentStep - 1);
    }
  }, [currentStep]);

  const isProductNameMapped = Object.values(fieldMappings).includes("product_name");
  const isOrderDateMapped = Object.values(fieldMappings).includes("order_date");
  const isQuantityMapped = Object.values(fieldMappings).includes("quantity");
  const isUnitCostMapped = Object.values(fieldMappings).includes("unit_cost");
  const isAllRequiredMapped = isProductNameMapped && isOrderDateMapped && isQuantityMapped && isUnitCostMapped;

  const steps = ["Upload File", "Map Fields", "Preview & Import"];

  return (
    <AuthenticatedLayout>
      <Head title="Import Purchase Orders" />
      <SidebarInset>
        <div className="main-content">
          <PageHeader page="Purchase Orders" subpage="Import" url="purchase_orders.index" />

          <div className="max-w-4xl mx-auto pb-8 px-4">
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
                  {index < steps.length - 1 && <ChevronRight className="size-4 text-muted-foreground" />}
                </div>
              ))}
            </div>

            {currentStep === 0 && (
              <Card>
                <CardHeader>
                  <CardTitle>Upload File</CardTitle>
                  <CardDescription>Select an Excel or CSV file to import purchase orders</CardDescription>
                </CardHeader>
                <CardContent className="space-y-5">
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
                        className={`cursor-pointer rounded-lg border-2 border-dashed p-8 text-center transition-colors ${
                          isDragOver
                            ? "border-primary bg-primary/5"
                            : "border-muted-foreground/25 hover:border-primary/50 hover:bg-muted/50"
                        }`}
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

                  <Alert className="border-blue-500/30 bg-blue-500/5">
                    <Info className="h-4 w-4 text-blue-600" />
                    <AlertDescription>
                      <h4 className="font-medium mb-2 text-blue-700">Multi-item Purchase Order Import</h4>
                      <ul className="text-sm space-y-1 text-blue-600">
                        <li>- Duplicate <strong>Order Number</strong> values are grouped into one purchase order with multiple items</li>
                        <li>- Use <strong>is_inventory</strong>: <strong>1</strong> uses Inventory account, <strong>0</strong> requires an Expense Account</li>
                        <li>- Header fields like supplier, date, currency, discount, note, and footer come from the first row in each order group</li>
                      </ul>
                    </AlertDescription>
                  </Alert>

                  <Alert>
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>
                      <h4 className="font-medium mb-2">Import Guidelines</h4>
                      <ul className="text-sm space-y-1">
                        <li>- Required fields: Product Name, Quantity, Unit Cost, Order Date</li>
                        <li>- Supplier is optional and matched by name when provided</li>
                        <li>- Optional <strong>Tax</strong> column accepts tax names from Tax Database</li>
                        <li>- Multiple taxes can be entered as comma-separated names like <strong>VAT, NBT</strong></li>
                        <li>- For <strong>is_inventory = 1</strong>, the product should already exist</li>
                        <li>- For <strong>is_inventory = 0</strong>, provide an Expense Account</li>
                      </ul>
                    </AlertDescription>
                  </Alert>

                  <div className="flex items-center justify-between p-4 border rounded-lg">
                    <div>
                      <p className="font-medium text-sm">Need a template?</p>
                      <p className="text-sm text-muted-foreground">Download our sample Excel file</p>
                    </div>
                    <a href="/uploads/media/default/sample_purchase_orders.xlsx" download>
                      <Button variant="outline" size="sm">
                        <Download className="size-4 mr-1.5" />
                        Download
                      </Button>
                    </a>
                  </div>
                </CardContent>
                <CardFooter className="flex justify-between">
                  <Button type="button" variant="outline" onClick={handleCancel}>
                    Cancel
                  </Button>
                  <Button type="button" onClick={handleUploadFile} disabled={!selectedFile || isUploading}>
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

            {currentStep === 1 && previewData?.headers && (
              <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                  <div>
                    <CardTitle>Map Columns</CardTitle>
                    <CardDescription>Map your file columns to purchase order fields</CardDescription>
                  </div>
                  <Button type="button" variant="outline" size="sm" onClick={handleAutoMap}>
                    Auto-Map Fields
                  </Button>
                </CardHeader>
                <CardContent>
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
                          {!isProductNameMapped && <span className="ml-1">"Product Name"</span>}
                          {!isOrderDateMapped && <span className="ml-1">"Order Date"</span>}
                          {!isQuantityMapped && <span className="ml-1">"Quantity"</span>}
                          {!isUnitCostMapped && <span className="ml-1">"Unit Cost"</span>}
                        </AlertDescription>
                      </>
                    )}
                  </Alert>

                  <div className="space-y-3">
                    {previewData.headers.map((header, index) => {
                      const mappedField = SYSTEM_FIELDS.find((f) => f.value === fieldMappings[header]);

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
                  <Button type="button" variant="outline" onClick={handleBack}>
                    <ArrowLeft className="size-4 mr-1.5" />
                    Back
                  </Button>
                  <Button type="button" onClick={handleGeneratePreview} disabled={isProcessing || !isAllRequiredMapped}>
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

            {currentStep === 2 && previewData && (
              <div className="space-y-6">
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <Card className="text-center p-4">
                    <p className="text-3xl font-bold">{previewData.total_rows}</p>
                    <p className="text-sm text-muted-foreground mt-1">Total Rows</p>
                  </Card>
                  <Card className="text-center p-4">
                    <p className="text-3xl font-bold text-blue-600">{previewData.unique_orders || 0}</p>
                    <p className="text-sm text-muted-foreground mt-1">Purchase Orders</p>
                  </Card>
                  <Card className="text-center p-4">
                    <p className="text-3xl font-bold text-green-600">{previewData.valid_count}</p>
                    <p className="text-sm text-muted-foreground mt-1">Valid Rows</p>
                  </Card>
                  <Card className="text-center p-4">
                    <p className="text-3xl font-bold text-destructive">{previewData.error_count}</p>
                    <p className="text-sm text-muted-foreground mt-1">Invalid Rows</p>
                  </Card>
                </div>

                {previewData.warning_count > 0 && (
                  <Alert className="border-yellow-500/30 bg-yellow-500/5">
                    <AlertCircle className="h-4 w-4 text-yellow-600" />
                    <AlertDescription className="text-yellow-700">
                      <span className="font-medium">{previewData.warning_count} warning(s)</span> detected. Rows may still import with blank supplier when the name is not found.
                    </AlertDescription>
                  </Alert>
                )}

                {previewData.error_count === 0 && (
                  <Alert className="border-green-500/30 bg-green-500/5">
                    <CheckCircle2 className="h-4 w-4 text-green-600" />
                    <AlertDescription className="text-green-600">
                      <span className="font-medium">All rows are valid and ready to import.</span>
                    </AlertDescription>
                  </Alert>
                )}

                {previewData.error_count > 0 && (
                  <Alert className="border-destructive/30 bg-destructive/5">
                    <AlertCircle className="h-4 w-4 text-destructive" />
                    <AlertDescription className="text-destructive">
                      <span className="font-medium">{previewData.error_count} row(s)</span> have validation issues and will be skipped.
                    </AlertDescription>
                  </Alert>
                )}

                {previewData.preview_records?.length > 0 && (
                  <Card>
                    <CardHeader>
                      <CardTitle className="text-base">Row Validation Preview</CardTitle>
                      <CardDescription>Each imported row with status and missing/invalid data details.</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0 overflow-auto">
                      <Table>
                        <TableHeader>
                          <TableRow>
                            <TableHead className="w-20">Row</TableHead>
                            <TableHead className="w-28">Status</TableHead>
                            <TableHead>Order</TableHead>
                            <TableHead>Supplier</TableHead>
                            <TableHead>Product</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead>Qty</TableHead>
                            <TableHead>Unit Cost</TableHead>
                            <TableHead>Inventory</TableHead>
                            <TableHead>Account</TableHead>
                            <TableHead>Tax</TableHead>
                            <TableHead>Issues</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {previewData.preview_records.map((record, index) => (
                            <TableRow key={index}>
                              <TableCell className="text-muted-foreground">{record.row}</TableCell>
                              <TableCell>
                                {record.status === "error" ? (
                                  <span className="inline-flex items-center gap-1.5 text-xs font-medium py-1 px-2.5 rounded bg-destructive/15 text-destructive">
                                    <AlertCircle className="size-3.5" /> Error
                                  </span>
                                ) : (
                                  <span className="inline-flex items-center gap-1.5 text-xs font-medium py-1 px-2.5 rounded bg-green-500/15 text-green-600">
                                    <CheckCircle2 className="size-3.5" /> Valid
                                  </span>
                                )}
                              </TableCell>
                              <TableCell>{record.data.order_number || "Auto"}</TableCell>
                              <TableCell>{record.data.supplier_name || "N/A"}</TableCell>
                              <TableCell>{record.data.product_name || "N/A"}</TableCell>
                              <TableCell>{record.data.order_date || "N/A"}</TableCell>
                              <TableCell>{record.data.quantity ?? "N/A"}</TableCell>
                              <TableCell>{record.data.unit_cost ?? "N/A"}</TableCell>
                              <TableCell>{record.data.is_inventory === 0 ? "No" : "Yes"}</TableCell>
                              <TableCell>{record.data.expense_account || (record.data.is_inventory === 0 ? "N/A" : "Inventory")}</TableCell>
                              <TableCell>{record.data.tax || "N/A"}</TableCell>
                              <TableCell>
                                {record.errors && record.errors.length > 0 ? (
                                  <ul className="space-y-1">
                                    {record.errors.map((error, i) => (
                                      <li key={i} className="text-sm text-destructive">- {error}</li>
                                    ))}
                                  </ul>
                                ) : (
                                  <span className="text-sm text-green-600">No issues</span>
                                )}
                              </TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </CardContent>
                  </Card>
                )}

                <Card>
                  <CardFooter className="flex justify-between pt-6">
                    <Button type="button" variant="outline" onClick={handleBack}>
                      <ArrowLeft className="size-4 mr-1.5" />
                      Back
                    </Button>
                    <div className="flex gap-3">
                      <Button type="button" variant="outline" onClick={handleCancel}>
                        Cancel
                      </Button>
                      <Button type="button" onClick={handleConfirmImport} disabled={isProcessing || previewData.valid_count === 0}>
                        {isProcessing ? (
                          <>
                            <Loader2 className="size-4 mr-2 animate-spin" />
                            Importing...
                          </>
                        ) : (
                          <>Import {previewData.valid_count} Row{previewData.valid_count !== 1 ? "s" : ""}</>
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
