import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/Components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { Button } from "@/Components/ui/button";

export default function ActiveSubscription({ pkg, lastPayments }) {

    const PaymentStatusBadge = ({ status }) => {
        const statusMap = {
          0: { label: "Pending", className: "text-yellow-500" },
          1: { label: "Paid", className: "text-green-500" },
        };

        return (
          <span className={statusMap[status].className}>
            {statusMap[status].label}
          </span>
        );
      };

    const PackageStatusBadge = ({ status }) => {
        const statusMap = {
          0: { label: "Inactive", className: "text-red-500" },
          1: { label: "Active", className: "text-green-500" },
        };

        return (
          <span className={statusMap[status].className}>
            {statusMap[status].label}
          </span>
        );
      };

    // Calculate days remaining if package exists
    const getDaysRemaining = () => {
        if (!pkg || !pkg.valid_to) return "N/A";
        
        const validTo = new Date(pkg.valid_to);
        const today = new Date();
        const diffTime = validTo - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        return diffDays > 0 ? `${diffDays} days` : "Expired";
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Profile" subpage="Active Subscription" url="membership.index" />

                <div className="flex flex-1 flex-col gap-6 p-4 pt-0">
                    {/* Package Information Card */}
                    <Card>
                        <CardHeader className="pb-3">
                            <div className="flex justify-between items-center">
                                <CardTitle>Subscription Information</CardTitle>
                                {pkg && (
                                    <PackageStatusBadge status={pkg.status} />
                                )}
                            </div>
                            <CardDescription>
                                Your current subscription package and details
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {pkg ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-4">
                                        <div>
                                            <h3 className="text-sm font-medium text-gray-500">Package Name</h3>
                                            <p className="mt-1 text-lg font-semibold">{pkg.name}</p>
                                        </div>
                                        <div>
                                            <h3 className="text-sm font-medium text-gray-500">Subscription Date</h3>
                                            <p className="mt-1">{pkg.subscription_date}</p>
                                        </div>
                                        <div>
                                            <h3 className="text-sm font-medium text-gray-500">Valid Date</h3>
                                            <p className="mt-1">{pkg.valid_to}</p>
                                        </div>
                                    </div>
                                    <div className="space-y-4">
                                        <div>
                                            <h3 className="text-sm font-medium text-gray-500">Subscription Status</h3>
                                            <PackageStatusBadge status={pkg.status} />
                                        </div>
                                        <div>
                                            <h3 className="text-sm font-medium text-gray-500">Time Remaining</h3>
                                            <p className="mt-1 font-medium">{getDaysRemaining()}</p>
                                        </div>
                                        <div>
                                            <h3 className="text-sm font-medium text-gray-500">Business Limit</h3>
                                            <p className="mt-1">{pkg.business_limit < 0 ? "Unlimited" : pkg.business_limit}</p>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="text-center py-6">
                                    <p className="text-gray-500">No active subscription found</p>
                                    <Button className="mt-4" variant="outline">
                                        View Plans
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                        {pkg && (
                            <CardFooter className="bg-gray-50 border-t px-6 py-4">
                                <div className="w-full">
                                    <h3 className="text-sm font-medium text-gray-700 mb-4">Package Features</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        {/* Usage Limits */}
                                        <div className="bg-white rounded-lg border p-4 shadow-sm">
                                            <h4 className="font-medium text-sm mb-2 text-primary">Usage Limits</h4>
                                            <div className="space-y-2">
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600">Users:</span>
                                                    <span className="font-medium">{pkg.user_limit < 0 ? "Unlimited" : pkg.user_limit}</span>
                                                </div>
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600">Invoices:</span>
                                                    <span className="font-medium">{pkg.invoice_limit < 0 ? "Unlimited" : pkg.invoice_limit}</span>
                                                </div>
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600">Quotations:</span>
                                                    <span className="font-medium">{pkg.quotation_limit < 0 ? "Unlimited" : pkg.quotation_limit}</span>
                                                </div>
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600">Customers:</span>
                                                    <span className="font-medium">{pkg.customer_limit < 0 ? "Unlimited" : pkg.customer_limit}</span>
                                                </div>
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600">Businesses:</span>
                                                    <span className="font-medium">{pkg.business_limit < 0 ? "Unlimited" : pkg.business_limit}</span>
                                                </div>
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600">Storage:</span>
                                                    <span className="font-medium">{pkg.storage_limit < 0 ? "Unlimited" : `${pkg.storage_limit} MB`}</span>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Core Features */}
                                        <div className="bg-white rounded-lg border p-4 shadow-sm col-span-1 md:col-span-2">
                                            <h4 className="font-medium text-sm mb-2 text-primary">Available Modules</h4>
                                            <div className="grid grid-cols-2 gap-2">
                                                <div className={`border rounded p-2 flex items-center space-x-2 ${pkg.online_invoice_payment ? "border-green-200 bg-green-50" : "border-gray-200 bg-gray-50"}`}>
                                                    <div className={`w-3 h-3 rounded-full ${pkg.online_invoice_payment ? "bg-green-500" : "bg-gray-300"}`}></div>
                                                    <span className="text-sm">Online Invoice Payment</span>
                                                </div>
                                                <div className={`border rounded p-2 flex items-center space-x-2 ${pkg.medical_record ? "border-green-200 bg-green-50" : "border-gray-200 bg-gray-50"}`}>
                                                    <div className={`w-3 h-3 rounded-full ${pkg.medical_record ? "bg-green-500" : "bg-gray-300"}`}></div>
                                                    <span className="text-sm">Medical Records</span>
                                                </div>
                                                <div className={`border rounded p-2 flex items-center space-x-2 ${pkg.prescription ? "border-green-200 bg-green-50" : "border-gray-200 bg-gray-50"}`}>
                                                    <div className={`w-3 h-3 rounded-full ${pkg.prescription ? "bg-green-500" : "bg-gray-300"}`}></div>
                                                    <span className="text-sm">Prescription</span>
                                                </div>
                                                <div className={`border rounded p-2 flex items-center space-x-2 ${pkg.payroll_module ? "border-green-200 bg-green-50" : "border-gray-200 bg-gray-50"}`}>
                                                    <div className={`w-3 h-3 rounded-full ${pkg.payroll_module ? "bg-green-500" : "bg-gray-300"}`}></div>
                                                    <span className="text-sm">Payroll Module</span>
                                                </div>
                                                <div className={`border rounded p-2 flex items-center space-x-2 ${pkg.pos ? "border-green-200 bg-green-50" : "border-gray-200 bg-gray-50"}`}>
                                                    <div className={`w-3 h-3 rounded-full ${pkg.pos ? "bg-green-500" : "bg-gray-300"}`}></div>
                                                    <span className="text-sm">POS System</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardFooter>
                        )}
                    </Card>

                    {/* Payment History Table */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Payment History</CardTitle>
                            <CardDescription>
                                Your recent subscription payment transactions
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {lastPayments ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Transaction ID</TableHead>
                                            <TableHead>Date</TableHead>
                                            <TableHead>Amount</TableHead>
                                            <TableHead>Method</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow>
                                            <TableCell className="font-medium">{lastPayments.order_id || "-"}</TableCell>
                                            <TableCell>{lastPayments.created_at}</TableCell>
                                            <TableCell>{lastPayments.amount || "0.00"}</TableCell>
                                            <TableCell>{lastPayments.payment_method || "N/A"}</TableCell>
                                            <TableCell>
                                                <PaymentStatusBadge status={lastPayments.status} />
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            ) : (
                                <div className="text-center py-6">
                                    <p className="text-gray-500">No payment records found</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
