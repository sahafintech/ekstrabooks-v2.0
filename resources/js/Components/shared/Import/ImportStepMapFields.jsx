import React, { useState, useEffect } from "react";
import { router } from "@inertiajs/react";
import axios from "axios";
import { Info, X } from "lucide-react";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import { Alert, AlertDescription } from "@/Components/ui/alert";

export default function ImportStepMapFields({
  file,
  fileName,
  systemFields = [],
  onComplete,
  onPreview,
  mapFieldsRoute,
  previewRoute,
  fieldMappings = {},
  duplicateHandling = "skip",
  encoding = "UTF-8",
}) {
  const [fileHeaders, setFileHeaders] = useState([]);
  const [mappings, setMappings] = useState(fieldMappings);
  const [loading, setLoading] = useState(false);
  const [loadingHeaders, setLoadingHeaders] = useState(true);
  const [autoMatched, setAutoMatched] = useState(false);

  useEffect(() => {
    if (file) {
      loadFileHeaders();
    }
  }, [file]);

  const loadFileHeaders = () => {
    setLoadingHeaders(true);
    const formData = new FormData();
    formData.append("file", file);
    
    axios.post(mapFieldsRoute, formData, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
    .then((response) => {
      const data = response.data;
      const headers = data.headers || [];
      const autoMappings = data.autoMappings || {};
      
      setFileHeaders(headers);
      
      // Use functional update to get current mappings state
      setMappings((currentMappings) => {
        if (Object.keys(currentMappings).length === 0) {
          setAutoMatched(true);
          return autoMappings;
        } else {
          const merged = { ...autoMappings };
          Object.keys(currentMappings).forEach((systemField) => {
            if (currentMappings[systemField]) {
              merged[systemField] = currentMappings[systemField];
            }
          });
          return merged;
        }
      });
      
      setLoadingHeaders(false);
    })
    .catch((error) => {
      console.error('Error loading headers:', error);
      if (error.response?.data?.error) {
        console.error('Server error:', error.response.data.error);
      } else if (error.response?.data?.message) {
        console.error('Server message:', error.response.data.message);
      }
      setLoadingHeaders(false);
    });
  };

  const handleMappingChange = (systemField, fileHeader) => {
    setMappings((prev) => ({
      ...prev,
      [systemField]: fileHeader || null,
    }));
  };

  const handleClearMapping = (systemField) => {
    setMappings((prev) => {
      const newMappings = { ...prev };
      delete newMappings[systemField];
      return newMappings;
    });
  };

  const handlePreview = () => {
    setLoading(true);
    const formData = new FormData();
    formData.append("file", file);
    formData.append("field_mappings", JSON.stringify(mappings));
    formData.append("duplicate_handling", duplicateHandling);
    formData.append("encoding", encoding);

    // Use axios for preview since we need JSON response
    axios.post(previewRoute, formData, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'multipart/form-data',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
    .then((response) => {
      const preview = response.data?.preview || {};
      
      onComplete(mappings);
      if (onPreview) {
        onPreview(preview);
      }
      setLoading(false);
    })
    .catch((error) => {
      console.error('Preview error:', error);
      if (error.response?.data?.error) {
        console.error('Server error:', error.response.data.error);
        alert('Error generating preview: ' + error.response.data.error);
      } else if (error.response?.data?.message) {
        console.error('Server message:', error.response.data.message);
        alert('Error generating preview: ' + error.response.data.message);
      } else {
        alert('Error generating preview. Please try again.');
      }
      setLoading(false);
    });
  };

  const getSystemFieldLabel = (field) => {
    return field.label || field.key;
  };

  const getSystemFieldRequired = (field) => {
    return field.required || false;
  };

  if (loadingHeaders) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="text-center">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-sm text-gray-600">Loading file headers...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <p className="text-sm text-gray-600">
          Your Selected File: <span className="font-medium">{fileName}</span>
        </p>
        {autoMatched && (
          <Alert className="mt-4">
            <Info className="h-4 w-4" />
            <AlertDescription>
              The best match to each field on the selected file have been
              auto-selected.
            </AlertDescription>
          </Alert>
        )}
      </div>

      {/* Field Mapping Table */}
      <div>
        <h3 className="text-lg font-medium mb-4">Item Details</h3>
        <div className="border rounded-lg overflow-hidden">
          <div className="bg-gray-50 border-b p-4 grid grid-cols-2 gap-4">
            <div className="font-medium">EKSTRABOOKS BOOKS FIELD</div>
            <div className="font-medium">IMPORTED FILE HEADERS</div>
          </div>
          <div className="divide-y">
            {systemFields.map((systemField, index) => {
              const systemFieldKey = systemField.key || systemField;
              const currentMapping = mappings[systemFieldKey] || undefined;
              const isRequired = getSystemFieldRequired(systemField);

              return (
                <div
                  key={index}
                  className="p-4 grid grid-cols-2 gap-4 items-center hover:bg-gray-50"
                >
                  <div className="flex items-center">
                    <Label className="font-medium">
                      {getSystemFieldLabel(systemField)}
                      {isRequired && <span className="text-red-500 ml-1">*</span>}
                    </Label>
                  </div>
                  <div className="flex items-center gap-2">
                    <Select
                      value={currentMapping}
                      onValueChange={(value) =>
                        handleMappingChange(systemFieldKey, value)
                      }
                    >
                      <SelectTrigger className="flex-1">
                        <SelectValue placeholder="Select" />
                      </SelectTrigger>
                      <SelectContent>
                        {fileHeaders.map((header, headerIndex) => (
                          <SelectItem key={headerIndex} value={header}>
                            {header}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    {currentMapping && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleClearMapping(systemFieldKey)}
                        className="p-1 h-8 w-8"
                      >
                        <X className="h-4 w-4" />
                      </Button>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </div>

      {/* Preview Button */}
      <div className="flex justify-end">
        <Button onClick={handlePreview} disabled={loading}>
          {loading ? "Processing..." : "Preview &gt;"}
        </Button>
      </div>
    </div>
  );
}

