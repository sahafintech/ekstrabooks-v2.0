import React, { useRef } from "react";
import { Link } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { 
    ArrowLeft, 
    Printer, 
    Edit, 
    User,
    Mail,
    Phone,
} from "lucide-react";
import { Badge } from "@/Components/ui/badge";
import { format, parseISO, isValid } from "date-fns";
import { useReactToPrint } from 'react-to-print';
import { Avatar, AvatarFallback, AvatarImage } from "@/Components/ui/avatar";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Textarea } from "@/Components/ui/textarea";
import { Separator } from "@/Components/ui/separator";

export default function View({ record, customer }) {
    const printRef = useRef();
    
    // Helper function to safely parse dates
    const safelyParseDate = (dateString) => {
        try {
            const parsedDate = dateString ? parseISO(dateString) : null;
            return parsedDate && isValid(parsedDate) ? parsedDate : null;
        } catch (e) {
            return null;
        }
    };
    
    // Format date for display
    const formatDate = (dateString) => {
        const parsedDate = safelyParseDate(dateString);
        return parsedDate ? format(parsedDate, "PPP") : "N/A";
    };
    
    // Handle printing functionality
    const handlePrint = useReactToPrint({
        content: () => printRef.current,
        documentTitle: `Medical Record - ${customer?.name || 'Patient'} - ${formatDate(record?.date)}`,
    });
    
    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader 
                    page="Medical Records" 
                    subpage="View" 
                    url="medical_records.index" 
                />
                
                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    {/* Top action bar */}
                    <div className="flex justify-between items-center mb-4">
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-semibold">
                                Medical Record #{record?.id}
                            </h1>
                            <Badge variant="outline" className="ml-2">
                                {formatDate(record?.date)}
                            </Badge>
                        </div>
                        
                        <div className="flex items-center gap-2">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                onClick={handlePrint}
                            >
                                <Printer className="h-4 w-4 mr-1" />
                                Print
                            </Button>
                            
                            <Button 
                                variant="outline" 
                                size="sm" 
                                asChild
                            >
                                <Link href={route('medical_records.edit', record?.id)}>
                                    <Edit className="h-4 w-4 mr-1" />
                                    Edit
                                </Link>
                            </Button>
                        </div>
                    </div>
                    
                    {/* Printable content */}
                    <div ref={printRef} className="bg-white rounded-lg p-6 print:p-0 print:shadow-none print:border-none">
                        {/* Medical Practice Header for Print */}
                        <div className="print:flex flex-col items-center text-center border-b pb-4 mb-6 hidden">
                            <h1 className="text-2xl font-bold">EkstraBooks</h1>
                            <p className="text-gray-500">Medical Records System</p>
                        </div>

                        {/* Patient Information Card */}
                        <Card className="mb-6 print:shadow-none print:border-none">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-lg flex items-center">
                                    <User className="h-5 w-5 mr-2" />
                                    Patient Information
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-start">
                                    <div className="mr-4">
                                        <Avatar className="h-16 w-16">
                                            <AvatarImage src="" alt={customer?.name} />
                                            <AvatarFallback className="text-lg">
                                                {customer?.name?.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()}
                                            </AvatarFallback>
                                        </Avatar>
                                    </div>
                                    <div className="flex-grow grid grid-cols-2 gap-4">
                                        <div>
                                            <h3 className="font-semibold text-lg">{customer?.name}</h3>
                                            <div className="text-sm text-gray-500 flex items-center mt-1">
                                                <Mail className="h-3.5 w-3.5 mr-1" />
                                                {customer?.email || 'No email provided'}
                                            </div>
                                            <div className="text-sm text-gray-500 flex items-center mt-1">
                                                <Phone className="h-3.5 w-3.5 mr-1" />
                                                {customer?.contact_number || 'No phone provided'}
                                            </div>
                                        </div>
                                        <div>
                                            <div className="text-sm flex items-center mt-1">
                                                <span className="font-medium mr-2">Record Date:</span>
                                                <span>{formatDate(record?.date)}</span>
                                            </div>
                                            <div className="text-sm flex items-center mt-1">
                                                <span className="font-medium mr-2">Patient ID:</span>
                                                <span>{record?.patient_id || 'N/A'}</span>
                                            </div>
                                            <div className="text-sm flex items-center mt-1">
                                                <span className="font-medium mr-2">Occupation:</span>
                                                <span>{record?.occupation || 'N/A'}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Medical Record Form View - Matches Create.jsx layout */}
                        <div className="print:border-t print:pt-4 space-y-8">
                            {/* Chief Complaint */}
                            <div className="mb-6">
                                <h3 className="text-center text-lg font-medium mb-4 border-b pb-2">Chief Complaint</h3>
                                <div className="grid grid-cols-1 gap-4">
                                    <div>
                                        <Label>Chief Complaint</Label>
                                        <div className="border p-3 rounded-md mt-1">{record?.chief_complaint || 'N/A'}</div>
                                    </div>
                                    <div>
                                        <Label>History of Present Illness</Label>
                                        <div className="border p-3 rounded-md mt-1">{record?.history_of_present_illness || 'N/A'}</div>
                                    </div>
                                </div>
                            </div>

                            {/* Past Ocular History */}
                            <div className="mb-6">
                                <h3 className="text-center text-lg font-medium mb-4 border-b pb-2">Past Ocular History</h3>
                                <div className="grid grid-cols-1 gap-4">
                                    <div>
                                        <Label>Past Ocular History</Label>
                                        <div className="border p-3 rounded-md mt-1">{record?.past_ocular_history || 'N/A'}</div>
                                    </div>
                                </div>
                            </div>

                            {/* Visual Acuity */}
                            <div className="mb-6">
                                <h3 className="text-center text-lg font-medium mb-4 border-b pb-2">Visual Acuity</h3>
                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div></div>
                                    <div className="text-center font-medium">Right Eye</div>
                                    <div></div>
                                    <div className="text-center font-medium">Left Eye</div>
                                    <div></div>
                                </div>

                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div className="py-2">VA unaided</div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.va_unaided_re || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.va_unaided_le || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                </div>

                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div className="py-2">VA aided (glasses)</div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.va_aided_re || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.va_aided_le || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                </div>

                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div className="py-2">Pinhole</div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.va_pinhole_re || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.va_pinhole_le || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                </div>
                            </div>

                            {/* Refraction Note */}
                            <div className="mb-6">
                                <h3 className="text-center text-lg font-medium mb-4 border-b pb-2">Refraction Note</h3>
                                
                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div className="py-2">Best Corrected VA</div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_best_corrected_re || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_best_corrected_le || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                </div>

                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div className="py-2">VA Test Type Used</div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.va_test_used_re || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.va_test_used_le || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                </div>

                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div className="py-2">Lensometer</div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_lensometer_re || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_lensometer_le || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                </div>

                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div className="py-2">Autorefraction</div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_autorefraction_re || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_autorefraction_le || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                </div>

                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div className="py-2">Dry Retinoscopy</div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_dry_retinoscopy_re || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_dry_retinoscopy_le || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                </div>

                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div className="py-2">Wet Retinoscopy (cyclo)</div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_wet_retinoscopy_re || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_wet_retinoscopy_le || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                </div>

                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div className="py-2">Subjective Refraction BCVA (distance)</div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_subjective_re || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_subjective_le || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                </div>

                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div className="py-2">Near (reading)</div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_near_re || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_near_le || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                </div>

                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div className="py-2">Final Prescription</div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_final_prescription_re || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                    <div>
                                        <div className="border p-2 rounded-md">{record?.rf_final_prescription_le || 'N/A'}</div>
                                    </div>
                                    <div></div>
                                </div>
                            </div>

                            {/* External Examination */}
                            <div className="mb-6">
                                <h3 className="text-center text-lg font-medium mb-4 border-b pb-2">External Examination</h3>
                                <div className="grid grid-cols-1 gap-4">
                                    <div>
                                        <Label>External Examination</Label>
                                        <div className="border p-3 rounded-md mt-1">{record?.external_examination || 'N/A'}</div>
                                    </div>
                                </div>
                            </div>

                            {/* Diagnosis */}
                            <div className="mb-6">
                                <h3 className="text-center text-lg font-medium mb-4 border-b pb-2">Diagnosis</h3>
                                <div className="grid grid-cols-1 gap-4">
                                    <div>
                                        <Label>Diagnosis</Label>
                                        <div className="border p-3 rounded-md mt-1">{record?.diagnosis || 'N/A'}</div>
                                    </div>
                                </div>
                            </div>

                            {/* Treatment Plan */}
                            <div className="mb-6">
                                <h3 className="text-center text-lg font-medium mb-4 border-b pb-2">Treatment Plan</h3>
                                <div className="grid grid-cols-1 gap-4">
                                    <div>
                                        <Label>Treatment Plan</Label>
                                        <div className="border p-3 rounded-md mt-1">{record?.treatment_plan || 'N/A'}</div>
                                    </div>
                                </div>
                            </div>

                            {/* Prescription */}
                            <div className="mb-6">
                                <h3 className="text-center text-lg font-medium mb-4 border-b pb-2">Prescription</h3>
                                <div className="grid grid-cols-1 gap-4">
                                    <div>
                                        <Label>Prescription</Label>
                                        <div className="border p-3 rounded-md mt-1">{record?.prescription || 'N/A'}</div>
                                    </div>
                                </div>
                            </div>

                            {/* Doctor Signature */}
                            <div className="mt-10 pt-10 border-t">
                                <div className="flex justify-end">
                                    <div className="text-center">
                                        <div className="h-16 border-b border-dashed w-64"></div>
                                        <p className="mt-2">Doctor's Signature & Stamp</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
