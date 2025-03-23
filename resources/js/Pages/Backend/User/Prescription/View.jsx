import React, { useRef } from "react";
import { Head, Link } from "@inertiajs/react";
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
    Package,
    Eye
} from "lucide-react";
import { Badge } from "@/Components/ui/badge";
import { format, parseISO, isValid } from "date-fns";
import { useReactToPrint } from 'react-to-print';
import { Avatar, AvatarFallback, AvatarImage } from "@/Components/ui/avatar";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";

export default function View({ prescription, customer }) {
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

    // Format currency
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD', // This should ideally come from your app's base currency
        }).format(amount);
    };
    
    // Handle printing functionality
    const handlePrint = useReactToPrint({
        content: () => printRef.current,
        documentTitle: `Prescription - ${customer?.name || 'Patient'} - ${formatDate(prescription?.date)}`,
    });
    
    return (
        <AuthenticatedLayout>
            <Head title={`Prescription - ${customer?.name || 'Patient'}`} />
            <SidebarInset>
                <PageHeader 
                    page="Prescriptions" 
                    subpage="View" 
                    url="prescriptions.index" 
                />
                
                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    {/* Top action bar */}
                    <div className="flex justify-between items-center mb-4">
                        <div className="flex items-center gap-2">
                            <h1 className="text-xl font-semibold">
                                Prescription #{prescription?.id}
                            </h1>
                            <Badge variant="outline" className="ml-2">
                                {formatDate(prescription?.date)}
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
                                <Link href={route('prescriptions.edit', prescription?.id)}>
                                    <Edit className="h-4 w-4 mr-1" />
                                    Edit
                                </Link>
                            </Button>
                        </div>
                    </div>
                    
                    {/* Printable content */}
                    <div ref={printRef} className="bg-white rounded-lg p-6 print:p-0 print:shadow-none print:border-none">
                        {/* Practice Header for Print */}
                        <div className="print:flex flex-col items-center text-center border-b pb-4 mb-6 hidden">
                            <h1 className="text-2xl font-bold">EkstraBooks</h1>
                            <p className="text-gray-500">Optical Prescription</p>
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
                                                <span className="font-medium mr-2">Prescription Date:</span>
                                                <span>{formatDate(prescription?.date)}</span>
                                            </div>
                                            <div className="text-sm flex items-center mt-1">
                                                <span className="font-medium mr-2">Result Date:</span>
                                                <span>{formatDate(prescription?.result_date)}</span>
                                            </div>
                                            <div className="text-sm flex items-center mt-1">
                                                <span className="font-medium mr-2">IPD:</span>
                                                <span>{prescription?.ipd || 'N/A'}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Prescription Details */}
                        <div className="print:border-t print:pt-4 space-y-8">
                            {/* Rx Measurements */}
                            <Card className="mb-6 print:shadow-none print:border">
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-lg flex items-center">
                                        <Eye className="h-5 w-5 mr-2" />
                                        Prescription Measurements
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-6">
                                        {/* Distance Vision */}
                                        <div>
                                            <h3 className="font-medium mb-3 border-b pb-1">Distance Vision</h3>
                                            <div className="grid grid-cols-6 gap-2 mb-3">
                                                <div></div>
                                                <div className="text-center text-sm font-medium">SPH</div>
                                                <div className="text-center text-sm font-medium">CYL</div>
                                                <div className="text-center text-sm font-medium">AXIS</div>
                                                <div className="text-center text-sm font-medium">VA</div>
                                                <div></div>
                                            </div>
                                            
                                            <div className="grid grid-cols-6 gap-2 mb-3">
                                                <div className="text-sm font-medium">Right Eye</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.dist_sph_re || 'N/A'}</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.dist_cyl_re || 'N/A'}</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.dist_axis_re || 'N/A'}</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.dist_va_re || 'N/A'}</div>
                                                <div></div>
                                            </div>
                                            
                                            <div className="grid grid-cols-6 gap-2">
                                                <div className="text-sm font-medium">Left Eye</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.dist_sph_le || 'N/A'}</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.dist_cyl_le || 'N/A'}</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.dist_axis_le || 'N/A'}</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.dist_va_le || 'N/A'}</div>
                                                <div></div>
                                            </div>
                                        </div>
                                        
                                        {/* Near Vision */}
                                        <div>
                                            <h3 className="font-medium mb-3 border-b pb-1">Near Vision</h3>
                                            <div className="grid grid-cols-6 gap-2 mb-3">
                                                <div></div>
                                                <div className="text-center text-sm font-medium">SPH</div>
                                                <div className="text-center text-sm font-medium">CYL</div>
                                                <div className="text-center text-sm font-medium">AXIS</div>
                                                <div className="text-center text-sm font-medium">VA</div>
                                                <div></div>
                                            </div>
                                            
                                            <div className="grid grid-cols-6 gap-2 mb-3">
                                                <div className="text-sm font-medium">Right Eye</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.near_sph_re || 'N/A'}</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.near_cyl_re || 'N/A'}</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.near_axis_re || 'N/A'}</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.near_va_re || 'N/A'}</div>
                                                <div></div>
                                            </div>
                                            
                                            <div className="grid grid-cols-6 gap-2">
                                                <div className="text-sm font-medium">Left Eye</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.near_sph_le || 'N/A'}</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.near_cyl_le || 'N/A'}</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.near_axis_le || 'N/A'}</div>
                                                <div className="border rounded-md p-2 text-center">{prescription?.near_va_le || 'N/A'}</div>
                                                <div></div>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Lens Options */}
                            <Card className="mb-6 print:shadow-none print:border">
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-lg">Lens Options</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <div className="flex items-center space-x-2">
                                            <div className={`h-4 w-4 rounded border ${prescription?.glasses ? 'bg-primary' : ''}`}></div>
                                            <span>Glasses</span>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <div className={`h-4 w-4 rounded border ${prescription?.plastic ? 'bg-primary' : ''}`}></div>
                                            <span>Plastic</span>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <div className={`h-4 w-4 rounded border ${prescription?.polycarbonate ? 'bg-primary' : ''}`}></div>
                                            <span>Polycarbonate</span>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <div className={`h-4 w-4 rounded border ${prescription?.contact_lenses ? 'bg-primary' : ''}`}></div>
                                            <span>Contact Lenses</span>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <div className={`h-4 w-4 rounded border ${prescription?.photochromatic_lenses ? 'bg-primary' : ''}`}></div>
                                            <span>Photochromatic</span>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <div className={`h-4 w-4 rounded border ${prescription?.bi_focal_lenses ? 'bg-primary' : ''}`}></div>
                                            <span>Bi-Focal</span>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <div className={`h-4 w-4 rounded border ${prescription?.progressive_lenses ? 'bg-primary' : ''}`}></div>
                                            <span>Progressive</span>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <div className={`h-4 w-4 rounded border ${prescription?.anti_reflection_coating ? 'bg-primary' : ''}`}></div>
                                            <span>Anti-Reflection</span>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <div className={`h-4 w-4 rounded border ${prescription?.high_index_lenses ? 'bg-primary' : ''}`}></div>
                                            <span>High Index</span>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <div className={`h-4 w-4 rounded border ${prescription?.single_vision ? 'bg-primary' : ''}`}></div>
                                            <span>Single Vision</span>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <div className={`h-4 w-4 rounded border ${prescription?.white_lenses ? 'bg-primary' : ''}`}></div>
                                            <span>White Lenses</span>
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <div className={`h-4 w-4 rounded border ${prescription?.blue_cut ? 'bg-primary' : ''}`}></div>
                                            <span>Blue Cut</span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Description */}
                            {prescription?.description && (
                                <Card className="mb-6 print:shadow-none print:border">
                                    <CardHeader className="pb-2">
                                        <CardTitle className="text-lg">Additional Notes</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-sm">{prescription.description}</p>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Products */}
                            <Card className="mb-6 print:shadow-none print:border">
                                <CardHeader className="pb-2">
                                    <CardTitle className="text-lg flex items-center">
                                        <Package className="h-5 w-5 mr-2" />
                                        Products
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {prescription?.items && prescription.items.length > 0 ? (
                                        <>
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>Product</TableHead>
                                                        <TableHead>Description</TableHead>
                                                        <TableHead className="text-right">Quantity</TableHead>
                                                        <TableHead className="text-right">Price</TableHead>
                                                        <TableHead className="text-right">Amount</TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {prescription.items.map((item, index) => (
                                                        <TableRow key={index}>
                                                            <TableCell className="font-medium">{item.product_name}</TableCell>
                                                            <TableCell>{item.description}</TableCell>
                                                            <TableCell className="text-right">{item.quantity}</TableCell>
                                                            <TableCell className="text-right">{formatCurrency(item.unit_cost)}</TableCell>
                                                            <TableCell className="text-right">{formatCurrency(item.sub_total)}</TableCell>
                                                        </TableRow>
                                                    ))}
                                                </TableBody>
                                            </Table>
                                            
                                            <div className="mt-4 flex justify-end">
                                                <div className="w-1/3">
                                                    <div className="flex justify-between py-1">
                                                        <span className="text-sm font-medium">Subtotal:</span>
                                                        <span className="text-sm">{formatCurrency(prescription.sub_total)}</span>
                                                    </div>
                                                    <div className="flex justify-between py-1 border-t border-t-gray-200 font-semibold">
                                                        <span>Total:</span>
                                                        <span>{formatCurrency(prescription.grand_total)}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </>
                                    ) : (
                                        <div className="text-center text-sm text-gray-500 py-4">No products added to this prescription</div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Footer */}
                            <div className="print:block hidden mt-8 pt-8 border-t">
                                <div className="text-center text-sm text-gray-500">
                                    <p>Prescription issued by: {prescription?.user?.name || 'System User'}</p>
                                    <p className="mt-1">Generated on {new Date().toLocaleDateString()}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
