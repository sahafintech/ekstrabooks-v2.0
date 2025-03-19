import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { 
  ArrowLeft, 
  Check, 
  ChevronDown, 
  ChevronUp, 
  CircleAlert, 
  Download, 
  Loader2, 
  Upload 
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { SidebarInset } from '@/Components/ui/sidebar';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import PageHeader from '@/Components/PageHeader';
import { Progress } from '@/Components/ui/progress';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import axios from 'axios';

export default function Process({ fileData, requiredFields }) {
  const [columns, setColumns] = useState([]);
  const [previewData, setPreviewData] = useState([]);
  const [columnMapping, setColumnMapping] = useState({});
  const [processing, setProcessing] = useState(false);
  const [progress, setProgress] = useState(0);
  const [progressStatus, setProgressStatus] = useState('pending'); // pending, processing, completed, error
  const [error, setError] = useState(null);
  const [mappingValid, setMappingValid] = useState(false);

  // This would typically come from the backend through Inertia props
  const requiredFieldsData = requiredFields || [
    { key: 'adjustment_date', name: 'Adjustment Date', required: true },
    { key: 'product_name', name: 'Product Name', required: true },
    { key: 'account_name', name: 'Account Name', required: true },
    { key: 'quantity_on_hand', name: 'Quantity on Hand', required: true },
    { key: 'adjusted_quantity', name: 'Adjusted Quantity', required: true },
    { key: 'description', name: 'Description', required: false },
  ];

  // Simulate fetching file data when component mounts
  useEffect(() => {
    // In a real implementation, this data would be passed from the backend
    if (!fileData) {
      // Simulate fetching preview data from the backend
      const mockColumns = ['Column 1', 'Column 2', 'Column 3', 'Column 4', 'Column 5', 'Column 6'];
      const mockData = [
        ['2023-01-01', 'Product A', 'Cash Account', '100', '10', 'January adjustment'],
        ['2023-01-02', 'Product B', 'Bank Account', '50', '-5', 'Stock correction'],
        ['2023-01-03', 'Product C', 'Inventory Account', '200', '25', 'New stock added'],
      ];
      
      setColumns(mockColumns);
      setPreviewData(mockData);
      
      // Create initial mapping (first attempt to match by name)
      const initialMapping = {};
      mockColumns.forEach((column, index) => {
        const matchedField = requiredFieldsData.find(field => 
          column.toLowerCase().includes(field.key.toLowerCase()) || 
          column.toLowerCase().includes(field.name.toLowerCase())
        );
        
        if (matchedField) {
          initialMapping[index] = matchedField.key;
        }
      });
      
      setColumnMapping(initialMapping);
    }
  }, [fileData, requiredFieldsData]);

  // Check if all required mappings are set
  useEffect(() => {
    const requiredKeys = requiredFieldsData
      .filter(field => field.required)
      .map(field => field.key);
    
    const mappedKeys = Object.values(columnMapping);
    const allRequiredMapped = requiredKeys.every(key => mappedKeys.includes(key));
    
    setMappingValid(allRequiredMapped);
  }, [columnMapping, requiredFieldsData]);

  const handleMappingChange = (columnIndex, fieldKey) => {
    setColumnMapping(prev => {
      // Remove this field from any other column it may be mapped to
      const newMapping = { ...prev };
      
      // Find if this field is already mapped to another column
      Object.keys(newMapping).forEach(idx => {
        if (newMapping[idx] === fieldKey) {
          delete newMapping[idx];
        }
      });
      
      // Set the new mapping
      if (fieldKey) {
        newMapping[columnIndex] = fieldKey;
      } else {
        delete newMapping[columnIndex];
      }
      
      return newMapping;
    });
  };

  const startImport = () => {
    setProcessing(true);
    setProgressStatus('processing');
    setProgress(0);
    
    // In a real implementation, you would send the column mapping to the server
    const payload = {
      columnMapping: columnMapping
    };

    // Simulate a progress update
    const timer = setInterval(() => {
      setProgress(prev => {
        if (prev >= 100) {
          clearInterval(timer);
          setProgressStatus('completed');
          return 100;
        }
        return prev + 5;
      });
    }, 300);

    // Simulate API call
    setTimeout(() => {
      clearInterval(timer);
      setProgress(100);
      setProgressStatus('completed');
      setProcessing(false);
      
      // In a real implementation, you would redirect to the list page after successful import
      // router.visit(route('inventory_adjustments.index'));
    }, 6000);

    // In a real implementation, you would make an API call like this:
    /*
    axios.post(route('inventory_adjustment.import.process'), payload)
      .then(response => {
        // Handle successful import
        setProgressStatus('completed');
        setProgress(100);
        setTimeout(() => {
          router.visit(route('inventory_adjustments.index'));
        }, 1000);
      })
      .catch(error => {
        // Handle error
        setProgressStatus('error');
        setError(error.response?.data?.message || 'An error occurred during import.');
      })
      .finally(() => {
        setProcessing(false);
      });
    */
  };

  const getFieldStatusColor = (fieldKey) => {
    const isMapped = Object.values(columnMapping).includes(fieldKey);
    const isRequired = requiredFieldsData.find(f => f.key === fieldKey)?.required;
    
    if (isRequired) {
      return isMapped ? 'text-green-500' : 'text-red-500';
    }
    
    return isMapped ? 'text-green-500' : 'text-gray-500';
  };

  const getColumnHeaderContent = (columnIndex) => {
    const mappedField = requiredFieldsData.find(field => field.key === columnMapping[columnIndex]);
    
    if (mappedField) {
      return (
        <div className="flex items-center">
          <span className="font-medium text-green-600">{mappedField.name}</span>
          <Check className="ml-1 h-4 w-4 text-green-600" />
        </div>
      );
    }
    
    return <span className="text-gray-500">Not Mapped</span>;
  };

  return (
    <AuthenticatedLayout>
      <Head title="Process Inventory Adjustments Import" />
      <SidebarInset>
        <div className="main-content">
          <PageHeader 
            page="Products" 
            subpage="Process Inventory Adjustments Import" 
            url="inventory_adjustments.index"
          />

          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            {/* Progress indicator */}
            <div className="mb-6">
              <div className="flex justify-between items-center mb-2">
                <span className="text-sm font-medium">
                  {progressStatus === 'pending' && 'Ready to import'}
                  {progressStatus === 'processing' && 'Processing import...'}
                  {progressStatus === 'completed' && 'Import completed'}
                  {progressStatus === 'error' && 'Import failed'}
                </span>
                <span className="text-sm font-medium">{progress}%</span>
              </div>
              <Progress value={progress} className="h-2" />
            </div>

            {error && (
              <Alert variant="destructive" className="mb-4">
                <CircleAlert className="h-4 w-4" />
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {/* Left side: Required fields */}
              <div className="md:col-span-1">
                <Card>
                  <CardHeader>
                    <CardTitle>Required Fields</CardTitle>
                    <CardDescription>
                      Map each required field to a column in your file
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    <ul className="space-y-2">
                      {requiredFieldsData.map((field) => (
                        <li 
                          key={field.key} 
                          className={`flex justify-between items-center p-2 rounded ${
                            Object.values(columnMapping).includes(field.key) 
                              ? 'bg-green-50' 
                              : field.required 
                                ? 'bg-red-50' 
                                : 'bg-gray-50'
                          }`}
                        >
                          <span className="font-medium">{field.name}</span>
                          <span className={`text-sm ${getFieldStatusColor(field.key)}`}>
                            {Object.values(columnMapping).includes(field.key) 
                              ? 'Mapped' 
                              : field.required 
                                ? 'Required' 
                                : 'Optional'}
                          </span>
                        </li>
                      ))}
                    </ul>
                  </CardContent>
                </Card>
              </div>

              {/* Right side: Data preview and mapping */}
              <div className="md:col-span-2">
                <Card>
                  <CardHeader>
                    <CardTitle>Column Mapping</CardTitle>
                    <CardDescription>
                      Preview your data and map columns to the required fields
                    </CardDescription>
                  </CardHeader>
                  <CardContent className="p-0 overflow-auto">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          {columns.map((column, index) => (
                            <TableHead key={index} className="min-w-[150px]">
                              <div className="space-y-1">
                                <div className="text-xs text-gray-500">{column}</div>
                                {getColumnHeaderContent(index)}
                                <Select 
                                  value={columnMapping[index] || ''}
                                  onValueChange={(value) => handleMappingChange(index, value)}
                                >
                                  <SelectTrigger className="w-full h-8 text-xs">
                                    <SelectValue placeholder="Map to field..." />
                                  </SelectTrigger>
                                  <SelectContent>
                                    <SelectGroup>
                                      <SelectItem value="">Not mapped</SelectItem>
                                      {requiredFieldsData.map((field) => (
                                        <SelectItem 
                                          key={field.key} 
                                          value={field.key}
                                          disabled={Object.values(columnMapping).includes(field.key) && columnMapping[index] !== field.key}
                                        >
                                          {field.name} {field.required ? '(Required)' : ''}
                                        </SelectItem>
                                      ))}
                                    </SelectGroup>
                                  </SelectContent>
                                </Select>
                              </div>
                            </TableHead>
                          ))}
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {previewData.map((row, rowIndex) => (
                          <TableRow key={rowIndex}>
                            {row.map((cell, cellIndex) => (
                              <TableCell key={cellIndex}>{cell}</TableCell>
                            ))}
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </CardContent>
                  <CardFooter className="flex justify-between border-t p-4 mt-2">
                    <Link href={route('inventory_adjustment.import')}>
                      <Button variant="outline">
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Back
                      </Button>
                    </Link>
                    <Button 
                      onClick={startImport} 
                      disabled={!mappingValid || processing || progressStatus === 'completed'}
                    >
                      {processing ? (
                        <>
                          <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                          Processing...
                        </>
                      ) : progressStatus === 'completed' ? (
                        <>
                          <Check className="mr-2 h-4 w-4" />
                          Completed
                        </>
                      ) : (
                        <>
                          <Upload className="mr-2 h-4 w-4" />
                          Start Import
                        </>
                      )}
                    </Button>
                  </CardFooter>
                </Card>
              </div>
            </div>
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
