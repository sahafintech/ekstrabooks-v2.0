import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { 
  AlertCircle, 
  ArrowLeft, 
  CheckCircle2, 
  Loader2, 
  RefreshCw 
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { SidebarInset } from '@/Components/ui/sidebar';
import { Button } from '@/Components/ui/button';
import PageHeader from '@/Components/PageHeader';
import { Progress } from '@/Components/ui/progress';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import axios from 'axios';

export default function ImportProgress({ errors }) {
  const [progress, setProgress] = useState(0);
  const [status, setStatus] = useState('waiting'); // waiting, processing, completed, failed
  const [message, setMessage] = useState('Click "Start Processing" to begin import');
  const [processingInfo, setProcessingInfo] = useState({
    total: 0,
    processed: 0,
    succeeded: 0,
    failed: 0
  });
  const [errorMessages, setErrorMessages] = useState([]);
  const [isProcessing, setIsProcessing] = useState(false);

  // Check progress using server endpoint
  const checkProgress = () => {
    axios.get(route('inventory_adjustments.import.status'))
      .then(response => {
        const data = response.data;
        console.log('Import progress data:', data);
        
        if (data.status) {
          setStatus(data.status);
          setMessage(data.message || '');
          
          // Update progress
          if (data.progress !== undefined) {
            setProgress(data.progress);
          }
          
          // Update processing info
          if (data.info) {
            setProcessingInfo({
              total: data.info.total || 0,
              processed: data.info.processed || 0,
              succeeded: data.info.succeeded || 0,
              failed: data.info.failed || 0
            });
          }

          // Update error messages if any
          if (data.errors && Array.isArray(data.errors)) {
            setErrorMessages(data.errors);
          }

          // If processing is complete, stop the loading state
          if (data.status === 'completed' || data.status === 'failed') {
            setIsProcessing(false);
          }
        }
      })
      .catch(error => {
        console.error('Error checking import progress:', error);
        setMessage('Error connecting to server. Please try refreshing the page.');
      });
  };

  const handleManualRefresh = () => {
    checkProgress();
  };

  const startProcessing = () => {
    // Set status to processing to show we're starting
    setIsProcessing(true);
    setStatus('processing');
    setMessage('Starting import process...');
    setProcessingInfo({
      total: 0,
      processed: 0,
      succeeded: 0,
      failed: 0
    });
    setErrorMessages([]);
    setProgress(0);
    console.log('Starting import processing...');

    // Make a POST request to the process endpoint
    axios.post(route('inventory_adjustments.import.process'))
      .then(response => {
        console.log('Processing response:', response.data);
        
        if (response.data.success) {
          // Direct processing is complete
          console.log('Processing completed, stats:', response.data.stats);
          setProcessingInfo({
            total: response.data.stats.total || 0,
            processed: response.data.stats.processed || 0,
            succeeded: response.data.stats.succeeded || 0,
            failed: response.data.stats.failed || 0
          });
          setProgress(100);
          setStatus('completed');
          setMessage(response.data.message || 'Import completed successfully');
          setIsProcessing(false);
        } else {
          console.error('Processing failed:', response.data.message);
          setStatus('failed');
          setMessage(response.data.message || 'Failed to process the file');
          setIsProcessing(false);
        }
      })
      .catch(error => {
        console.error('Processing error:', error);
        setStatus('failed');
        setMessage('Failed to process the file: ' + (error.response?.data?.message || error.message));
        setIsProcessing(false);
      });
  };

  // Check if we have errors from the server
  useEffect(() => {
    if (errors && Object.keys(errors).length > 0) {
      setStatus('failed');
      setMessage(Object.values(errors).join('. '));
    }
    
    // Initial status check
    checkProgress();
  }, [errors]);

  const getStatusBadge = () => {
    switch (status) {
      case 'waiting':
        return (
          <div className="flex items-center text-gray-700 bg-gray-50 px-3 py-1 rounded-full">
            <ArrowLeft className="h-4 w-4 mr-2" />
            <span>Waiting</span>
          </div>
        );
      case 'processing':
        return (
          <div className="flex items-center text-blue-700 bg-blue-50 px-3 py-1 rounded-full">
            <Loader2 className="h-4 w-4 mr-2 animate-spin" />
            <span>Processing</span>
          </div>
        );
      case 'completed':
        return (
          <div className="flex items-center text-green-700 bg-green-50 px-3 py-1 rounded-full">
            <CheckCircle2 className="h-4 w-4 mr-2" />
            <span>Completed</span>
          </div>
        );
      case 'failed':
        return (
          <div className="flex items-center text-red-700 bg-red-50 px-3 py-1 rounded-full">
            <AlertCircle className="h-4 w-4 mr-2" />
            <span>Failed</span>
          </div>
        );
      default:
        return null;
    }
  };

  return (
    <AuthenticatedLayout>
      <Head title="Import Progress" />
      <SidebarInset>
        <div className="main-content">
          <PageHeader 
            page="Inventory Adjustments" 
            subpage="Import Progress" 
            url="inventory_adjustments.index"
          />

          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            <Card>
              <CardHeader className="pb-3">
                <div className="flex justify-between items-center">
                  <div>
                    <CardTitle>Import Progress</CardTitle>
                    <CardDescription>
                      Tracking the progress of your inventory adjustment import
                    </CardDescription>
                  </div>
                  {getStatusBadge()}
                </div>
              </CardHeader>
              <CardContent>
                <div className="space-y-6">
                  {/* Progress bar */}
                  <div className="space-y-2">
                    <div className="flex justify-between text-sm">
                      <span>Progress</span>
                      <span>{progress}%</span>
                    </div>
                    <Progress value={progress} className="h-2" />
                  </div>
                  
                  {/* Stats */}
                  <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4">
                    <div className="bg-gray-50 p-3 rounded-lg">
                      <div className="text-sm text-gray-500">Total Records</div>
                      <div className="text-2xl font-semibold">{processingInfo.total}</div>
                    </div>
                    <div className="bg-blue-50 p-3 rounded-lg">
                      <div className="text-sm text-blue-500">Processed</div>
                      <div className="text-2xl font-semibold">{processingInfo.processed}</div>
                    </div>
                    <div className="bg-green-50 p-3 rounded-lg">
                      <div className="text-sm text-green-500">Succeeded</div>
                      <div className="text-2xl font-semibold">{processingInfo.succeeded}</div>
                    </div>
                    <div className="bg-red-50 p-3 rounded-lg">
                      <div className="text-sm text-red-500">Failed</div>
                      <div className="text-2xl font-semibold">{processingInfo.failed}</div>
                    </div>
                  </div>
                  
                  {/* Message */}
                  {message && (
                    <Alert 
                      variant={status === 'failed' ? 'destructive' : status === 'completed' ? 'success' : 'default'}
                    >
                      {status === 'failed' && <AlertCircle className="h-4 w-4" />}
                      {status === 'completed' && <CheckCircle2 className="h-4 w-4" />}
                      <AlertTitle>
                        {status === 'failed' ? 'Error' : status === 'completed' ? 'Success' : 'Information'}
                      </AlertTitle>
                      <AlertDescription>{message}</AlertDescription>
                    </Alert>
                  )}

                  {/* Error messages */}
                  {errorMessages.length > 0 && (
                    <div className="mt-4">
                      <h3 className="text-md font-semibold mb-2">Error Details</h3>
                      <div className="bg-red-50 p-3 rounded-lg text-sm text-red-800 max-h-40 overflow-y-auto">
                        <ul className="list-disc pl-5 space-y-1">
                          {errorMessages.map((error, index) => (
                            <li key={index}>{error}</li>
                          ))}
                        </ul>
                      </div>
                    </div>
                  )}
                </div>
              </CardContent>
              <CardFooter className="border-t pt-4 flex justify-between">
                <Button 
                  variant="outline" 
                  onClick={handleManualRefresh}
                >
                  <RefreshCw className="h-4 w-4 mr-2" />
                  Refresh Status
                </Button>
                
                {(status === 'waiting' || status === 'failed') && (
                  <Button 
                    onClick={startProcessing}
                    disabled={isProcessing}
                  >
                    {isProcessing ? (
                      <>
                        <span className="animate-spin mr-2">⟳</span>
                        Processing...
                      </>
                    ) : (
                      <>
                        Start Processing
                      </>
                    )}
                  </Button>
                )}
                
                {status === 'completed' && (
                  <Link href={route('inventory_adjustments.index')}>
                    <Button>
                      Go to Inventory Adjustments
                    </Button>
                  </Link>
                )}
              </CardFooter>
            </Card>
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
