import React, { useRef, useState } from "react";
import { Head, Link } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { 
    Printer, 
    Edit, 
    User,
    Mail,
    Phone,
    Package,
    Eye,
    FileText,
    Check
} from "lucide-react";
import { Badge } from "@/Components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/Components/ui/avatar";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { formatCurrency } from "@/lib/utils";

export default function View({ prescription, customer, prescriptionProduct }) {
    const [isLoading, setIsLoading] = useState({
        print: false
    });

    const handlePrint = () => {
        setIsLoading(prev => ({ ...prev, print: true }));
        setTimeout(() => {
            window.print();
            setIsLoading(prev => ({ ...prev, print: false }));
        }, 300);
    };
    
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
                            <h1 className="text-xl font-semibold p-4">
                                Prescription #{prescription?.id}
                            </h1>
                            <Badge variant="outline" className="ml-2">
                                {prescription?.date}
                            </Badge>
                        </div>
                        
                        <div className="flex items-center gap-2">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                onClick={handlePrint}
                                disabled={isLoading.print}
                            >
                                <Printer className="h-4 w-4 mr-1" />
                                {isLoading.print ? "Printing..." : "Print"}
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
                    <div className="print-container">
                        <div className="bg-white rounded-lg p-6 print:p-0 print:shadow-none print:border-none">
                            {/* Practice Header for Print */}
                            <div className="print:flex flex-col items-center text-center border-b pb-4 mb-6 hidden">
                                <h1 className="text-2xl font-bold">EkstraBooks</h1>
                                <p className="text-gray-500">Optical Prescription</p>
                            </div>

                            {/* Patient Information Card */}
                            <div className="mb-6 print:shadow-none print:border-none">
                                <div className="pb-2">
                                    <h3 className="text-lg flex items-center">
                                        <User className="h-5 w-5 mr-2" />
                                        Patient Information
                                    </h3>
                                </div>
                                <div>
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
                                                    <span>{prescription?.date}</span>
                                                </div>
                                                <div className="text-sm flex items-center mt-1">
                                                    <span className="font-medium mr-2">Result Date:</span>
                                                    <span>{prescription?.result_date}</span>
                                                </div>
                                                <div className="text-sm flex items-center mt-1">
                                                    <span className="font-medium mr-2">IPD:</span>
                                                    <span>{prescription?.ipd || 'N/A'}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Prescription Details */}
                            <div className="print:border-t print:pt-4 space-y-8">
                                {/* Rx Measurements */}
                                <div className="mb-6 print:shadow-none print:border">
                                    <div className="pb-2">
                                        <h3 className="text-lg flex items-center">
                                            <Eye className="h-5 w-5 mr-2" />
                                            Prescription Measurements
                                        </h3>
                                    </div>
                                    <div>
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
                                    </div>
                                </div>

                                {/* Lens Options */}
                                <div className="mb-6 print:shadow-none print:border">
                                    <div className="pb-2">
                                        <h3 className="text-lg flex items-center">
                                            <Package className="h-5 w-5 mr-2" />
                                            Lens Options
                                        </h3>
                                    </div>
                                    <div>
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                                            <div className="flex items-center space-x-2">
                                                <div className="h-4 w-4 flex items-center justify-center rounded border">
                                                    {prescription?.glasses == 1 && <Check className="h-4 w-4 text-green-600" />}
                                                </div>
                                                <span>Glasses</span>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <div className="h-4 w-4 flex items-center justify-center rounded border">
                                                    {prescription?.plastic == 1 && <Check className="h-4 w-4 text-green-600" />}
                                                </div>
                                                <span>Plastic</span>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <div className="h-4 w-4 flex items-center justify-center rounded border">
                                                    {prescription?.polycarbonate == 1 && <Check className="h-4 w-4 text-green-600" />}
                                                </div>
                                                <span>Polycarbonate</span>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <div className="h-4 w-4 flex items-center justify-center rounded border">
                                                    {prescription?.contact_lenses == 1 && <Check className="h-4 w-4 text-green-600" />}
                                                </div>
                                                <span>Contact Lenses</span>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <div className="h-4 w-4 flex items-center justify-center rounded border">
                                                    {prescription?.photochromatic_lenses == 1 && <Check className="h-4 w-4 text-green-600" />}
                                                </div>
                                                <span>Photochromatic</span>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <div className="h-4 w-4 flex items-center justify-center rounded border">
                                                    {prescription?.bi_focal_lenses == 1 && <Check className="h-4 w-4 text-green-600" />}
                                                </div>
                                                <span>Bi-Focal</span>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <div className="h-4 w-4 flex items-center justify-center rounded border">
                                                    {prescription?.progressive_lenses == 1 && <Check className="h-4 w-4 text-green-600" />}
                                                </div>
                                                <span>Progressive</span>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <div className="h-4 w-4 flex items-center justify-center rounded border">
                                                    {prescription?.anti_reflection_coating == 1 && <Check className="h-4 w-4 text-green-600" />}
                                                </div>
                                                <span>Anti-Reflection</span>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <div className="h-4 w-4 flex items-center justify-center rounded border">
                                                    {prescription?.high_index_lenses == 1 && <Check className="h-4 w-4 text-green-600" />}
                                                </div>
                                                <span>High Index</span>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <div className="h-4 w-4 flex items-center justify-center rounded border">
                                                    {prescription?.single_vision == 1 && <Check className="h-4 w-4 text-green-600" />}
                                                </div>
                                                <span>Single Vision</span>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <div className="h-4 w-4 flex items-center justify-center rounded border">
                                                    {prescription?.white_lenses == 1 && <Check className="h-4 w-4 text-green-600" />}
                                                </div>
                                                <span>White Lenses</span>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <div className="h-4 w-4 flex items-center justify-center rounded border">
                                                    {prescription?.blue_cut == 1 && <Check className="h-4 w-4 text-green-600" />}
                                                </div>
                                                <span>Blue Cut</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Description */}
                                {prescription?.description && (
                                    <div className="mb-6 print:shadow-none print:border">
                                        <div className="pb-2">
                                            <h3 className="text-lg flex items-center">
                                                <FileText className="h-5 w-5 mr-2" />
                                                Additional Notes
                                            </h3>
                                        </div>
                                        <div>
                                            <p className="text-sm">{prescription.description}</p>
                                        </div>
                                    </div>
                                )}

                                {/* Products */}
                                <div className="mb-6 print:shadow-none print:border">
                                    <div className="pb-2">
                                        <h3 className="text-lg flex items-center">
                                            <Package className="h-5 w-5 mr-2" />
                                            Products
                                        </h3>
                                    </div>
                                    <div>
                                        {prescriptionProduct?.items && prescriptionProduct.items.length > 0 ? (
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
                                                        {prescriptionProduct.items.map((item, index) => (
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
                                                            <span className="text-sm">{formatCurrency(prescriptionProduct.sub_total)}</span>
                                                        </div>
                                                        <div className="flex justify-between py-1 border-t border-t-gray-200 font-semibold">
                                                            <span>Total:</span>
                                                            <span>{formatCurrency(prescriptionProduct.grand_total)}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </>
                                        ) : (
                                            <div className="text-center text-sm text-gray-500 py-4">No products added to this prescription</div>
                                        )}
                                    </div>
                                </div>

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
                </div>
            </SidebarInset>

            {/* Print Styles */}
            <style jsx global>{`
                @media print {
                    body * {
                        visibility: hidden;
                    }
                    .print-container,
                    .print-container * {
                        visibility: visible;
                    }
                    .print-container {
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100%;
                    }

                    /* Hide action buttons when printing */
                    button,
                    .dropdown,
                    .flex.space-x-2 {
                        display: none !important;
                    }
                }
            `}</style>
        </AuthenticatedLayout>
    );
}
