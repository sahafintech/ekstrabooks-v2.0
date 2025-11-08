import React, { useState, useRef } from "react";
import { Upload, Download, HelpCircle } from "lucide-react";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
  RadioGroup,
  RadioGroupItem,
} from "@/Components/ui/radio-group";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import { Alert, AlertDescription } from "@/Components/ui/alert";
import { Lightbulb } from "lucide-react";

export default function ImportStepConfigure({
  onComplete,
  sampleFileUrl,
  maxFileSize = 25,
  acceptedFormats = ["CSV", "TSV", "XLS", "XLSX"],
  defaultDuplicateHandling = "skip",
  defaultEncoding = "UTF-8",
}) {
  const [file, setFile] = useState(null);
  const [fileName, setFileName] = useState("");
  const [duplicateHandling, setDuplicateHandling] = useState(defaultDuplicateHandling);
  const [encoding, setEncoding] = useState(defaultEncoding);
  const [dragActive, setDragActive] = useState(false);
  const fileInputRef = useRef(null);

  const handleDrag = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === "dragenter" || e.type === "dragover") {
      setDragActive(true);
    } else if (e.type === "dragleave") {
      setDragActive(false);
    }
  };

  const handleDrop = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);

    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFile(e.dataTransfer.files[0]);
    }
  };

  const handleFileChange = (e) => {
    if (e.target.files && e.target.files[0]) {
      handleFile(e.target.files[0]);
    }
  };

  const handleFile = (selectedFile) => {
    // Validate file size
    const fileSizeMB = selectedFile.size / (1024 * 1024);
    if (fileSizeMB > maxFileSize) {
      alert(`File size exceeds maximum of ${maxFileSize} MB`);
      return;
    }

    // Validate file format
    const fileExtension = selectedFile.name.split(".").pop().toUpperCase();
    if (!acceptedFormats.includes(fileExtension)) {
      alert(`File format not supported. Please use: ${acceptedFormats.join(", ")}`);
      return;
    }

    setFile(selectedFile);
    setFileName(selectedFile.name);
  };

  const handleNext = (e) => {
    if (e) {
      e.preventDefault();
    }
    
    if (!file) {
      alert("Please select a file to import");
      return;
    }

    onComplete({
      file,
      fileName,
      duplicateHandling,
      encoding,
    });
  };

  const formatLabel = acceptedFormats.join(" or ");

  return (
    <div className="space-y-6">
      {/* File Upload Area */}
      <div>
        <div
          className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
            dragActive
              ? "border-blue-500 bg-blue-50"
              : "border-gray-300 hover:border-gray-400"
          }`}
          onDragEnter={handleDrag}
          onDragLeave={handleDrag}
          onDragOver={handleDrag}
          onDrop={handleDrop}
        >
          <Upload className="w-12 h-12 mx-auto mb-4 text-gray-400" />
          <p className="text-sm text-gray-600 mb-2">
            Drag and drop file to import
          </p>
          <Button
            type="button"
            variant="outline"
            onClick={() => fileInputRef.current?.click()}
            className="mb-2"
          >
            Choose File
          </Button>
          <input
            ref={fileInputRef}
            type="file"
            className="hidden"
            accept={acceptedFormats.map((f) => `.${f.toLowerCase()}`).join(",")}
            onChange={handleFileChange}
          />
          <p className="text-xs text-gray-500 mt-2">
            Maximum File Size: {maxFileSize} MB • File Format: {formatLabel}
          </p>
          {fileName && (
            <p className="text-sm text-blue-600 mt-2 font-medium">
              Selected: {fileName}
            </p>
          )}
        </div>

        {sampleFileUrl && (
          <p className="text-sm text-gray-600 mt-2">
            Download a{" "}
            <a href={sampleFileUrl} download className="text-blue-600 hover:underline">
              sample csv file
            </a>{" "}
            or{" "}
            <a href={sampleFileUrl} download className="text-blue-600 hover:underline">
              sample xls file
            </a>{" "}
            and compare it to your import file to ensure you have the file perfect for
            the import.
          </p>
        )}
      </div>

      {/* Duplicate Handling */}
      <div>
        <div className="flex items-center gap-2 mb-3">
          <Label className="text-sm font-medium">
            Duplicate Handling: <span className="text-red-500">*</span>
          </Label>
          <HelpCircle className="w-4 h-4 text-gray-400" />
        </div>
        <RadioGroup
          value={duplicateHandling}
          onValueChange={setDuplicateHandling}
        >
          <div className="flex items-start space-x-2 mb-3">
            <RadioGroupItem value="skip" id="skip" className="mt-1" />
            <div className="flex-1">
              <Label htmlFor="skip" className="font-normal cursor-pointer">
                Skip Duplicates
              </Label>
              <p className="text-sm text-gray-500">
                Retains the items in the system and does not import the duplicates in
                the import file.
              </p>
            </div>
          </div>
          <div className="flex items-start space-x-2">
            <RadioGroupItem value="overwrite" id="overwrite" className="mt-1" />
            <div className="flex-1">
              <Label htmlFor="overwrite" className="font-normal cursor-pointer">
                Overwrite items
              </Label>
              <p className="text-sm text-gray-500">
                Imports the duplicates in the import file and overwrites the existing
                items in the system.
              </p>
            </div>
          </div>
        </RadioGroup>
      </div>

      {/* Character Encoding */}
      <div>
        <div className="flex items-center gap-2 mb-3">
          <Label className="text-sm font-medium">Character Encoding</Label>
          <HelpCircle className="w-4 h-4 text-gray-400" />
        </div>
        <Select value={encoding} onValueChange={setEncoding}>
          <SelectTrigger className="w-full">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="UTF-8">UTF-8 (Unicode)</SelectItem>
            <SelectItem value="ISO-8859-1">ISO-8859-1 (Latin-1)</SelectItem>
            <SelectItem value="Windows-1252">Windows-1252</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Page Tips */}
      <Alert>
        <Lightbulb className="h-4 w-4" />
        <AlertDescription>
          <div className="space-y-2 text-sm">
            <p>
              • You can download the sample xls file to get detailed information about
              the data fields used while importing.
            </p>
            <p>
              • If you have files in other formats, you can convert it to an accepted
              file format using any online/offline converter.
            </p>
            <p>
              • You can configure your import settings and save them for future too!
            </p>
          </div>
        </AlertDescription>
      </Alert>

      {/* Next Button */}
      <div className="flex justify-end">
        <Button onClick={handleNext} disabled={!file}>
          Next &gt;
        </Button>
      </div>
    </div>
  );
}

