import React, { useState } from "react";
import { AlertTriangle, ChevronDown, ChevronUp, CheckCircle2 } from "lucide-react";
import { Button } from "@/Components/ui/button";
import { Alert, AlertDescription } from "@/Components/ui/alert";
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/Components/ui/collapsible";

export default function ImportStepPreview({ preview, onRefresh }) {
  const [expandedSections, setExpandedSections] = useState({
    ready: false,
    skipped: false,
    unmapped: false,
  });

  if (!preview) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="text-center">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-sm text-gray-600">Loading preview...</p>
        </div>
      </div>
    );
  }

  const {
    total_items = 0,
    ready_items = 0,
    skipped_items = 0,
    unmapped_fields = 0,
    ready_details = [],
    skipped_details = [],
    unmapped_fields_list = [],
  } = preview;

  const toggleSection = (section) => {
    setExpandedSections((prev) => ({
      ...prev,
      [section]: !prev[section],
    }));
  };

  return (
    <div className="space-y-6">
      {/* Summary Banner */}
      <Alert className="bg-blue-50 border-blue-200">
        <CheckCircle2 className="h-4 w-4 text-blue-600" />
        <AlertDescription className="text-blue-800">
          {ready_items} of {total_items} Items in your file are ready to be imported.
        </AlertDescription>
      </Alert>

      {/* Import Summary */}
      <div className="space-y-4">
        {/* Items Ready */}
        <Collapsible
          open={expandedSections.ready}
          onOpenChange={() => toggleSection("ready")}
        >
          <div className="border rounded-lg p-4">
            <CollapsibleTrigger className="w-full">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <span className="font-medium">
                    Items that are ready to be imported - {ready_items}
                  </span>
                </div>
                <div className="flex items-center gap-2">
                  <span className="text-sm text-blue-600 hover:underline">
                    View Details
                  </span>
                  {expandedSections.ready ? (
                    <ChevronUp className="w-4 h-4" />
                  ) : (
                    <ChevronDown className="w-4 h-4" />
                  )}
                </div>
              </div>
            </CollapsibleTrigger>
            <CollapsibleContent className="mt-4">
              {ready_details.length > 0 ? (
                <div className="space-y-2">
                  {ready_details.slice(0, 10).map((item, index) => (
                    <div
                      key={index}
                      className="text-sm text-gray-600 p-2 bg-green-50 rounded"
                    >
                      {item.row && (
                        <div className="font-medium mb-1">
                          Row {item.row}
                        </div>
                      )}
                      {item.data && typeof item.data === "object" ? (
                        <div className="text-xs">
                          {Object.entries(item.data).map(([key, value]) => (
                            <div key={key} className="mb-1">
                              <span className="font-medium">{key}:</span> {String(value || '')}
                            </div>
                          ))}
                        </div>
                      ) : typeof item === "object" ? (
                        <div className="text-xs">
                          {Object.entries(item).map(([key, value]) => (
                            <div key={key} className="mb-1">
                              <span className="font-medium">{key}:</span> {String(value || '')}
                            </div>
                          ))}
                        </div>
                      ) : (
                        <div>{String(item)}</div>
                      )}
                    </div>
                  ))}
                  {ready_details.length > 10 && (
                    <p className="text-sm text-gray-500">
                      ... and {ready_details.length - 10} more items
                    </p>
                  )}
                </div>
              ) : (
                <p className="text-sm text-gray-500">No items ready</p>
              )}
            </CollapsibleContent>
          </div>
        </Collapsible>

        {/* Skipped Records */}
        {skipped_items > 0 && (
          <Collapsible
            open={expandedSections.skipped}
            onOpenChange={() => toggleSection("skipped")}
          >
            <div className="border rounded-lg p-4 border-orange-200 bg-orange-50">
              <CollapsibleTrigger className="w-full">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <AlertTriangle className="w-5 h-5 text-orange-600" />
                    <span className="font-medium">
                      No. of Records skipped - {skipped_items}
                    </span>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="text-sm text-blue-600 hover:underline">
                      View Details
                    </span>
                    {expandedSections.skipped ? (
                      <ChevronUp className="w-4 h-4" />
                    ) : (
                      <ChevronDown className="w-4 h-4" />
                    )}
                  </div>
                </div>
              </CollapsibleTrigger>
              <CollapsibleContent className="mt-4">
                {skipped_details.length > 0 ? (
                  <div className="space-y-2">
                    {skipped_details.slice(0, 10).map((item, index) => (
                      <div
                        key={index}
                        className="text-sm text-gray-600 p-2 bg-white rounded border border-orange-200"
                      >
                        <div className="font-medium mb-1">
                          {item.row || `Row ${index + 1}`}
                        </div>
                        <div className="text-xs text-gray-500">
                          {item.reason || "Duplicate or invalid data"}
                        </div>
                      </div>
                    ))}
                    {skipped_details.length > 10 && (
                      <p className="text-sm text-gray-500">
                        ... and {skipped_details.length - 10} more records
                      </p>
                    )}
                  </div>
                ) : (
                  <p className="text-sm text-gray-500">No skipped records</p>
                )}
              </CollapsibleContent>
            </div>
          </Collapsible>
        )}

        {/* Unmapped Fields */}
        {unmapped_fields > 0 && (
          <Collapsible
            open={expandedSections.unmapped}
            onOpenChange={() => toggleSection("unmapped")}
          >
            <div className="border rounded-lg p-4 border-orange-200 bg-orange-50">
              <CollapsibleTrigger className="w-full">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <AlertTriangle className="w-5 h-5 text-orange-600" />
                    <span className="font-medium">
                      Unmapped Fields - {unmapped_fields}
                    </span>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="text-sm text-blue-600 hover:underline">
                      View Details
                    </span>
                    {expandedSections.unmapped ? (
                      <ChevronUp className="w-4 h-4" />
                    ) : (
                      <ChevronDown className="w-4 h-4" />
                    )}
                  </div>
                </div>
              </CollapsibleTrigger>
              <CollapsibleContent className="mt-4">
                {unmapped_fields_list.length > 0 ? (
                  <div className="space-y-2">
                    {unmapped_fields_list.map((field, index) => (
                      <div
                        key={index}
                        className="text-sm text-gray-600 p-2 bg-white rounded border border-orange-200"
                      >
                        {field}
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-gray-500">No unmapped fields</p>
                )}
              </CollapsibleContent>
            </div>
          </Collapsible>
        )}
      </div>

      {/* Refresh Button */}
      <div className="flex justify-end">
        <Button variant="outline" onClick={onRefresh}>
          Refresh Preview
        </Button>
      </div>
    </div>
  );
}

