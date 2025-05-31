import { Link } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/Components/ui/card";
import { Pencil, ArrowLeft } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import PageHeader from "@/Components/PageHeader";
import { Badge } from "@/Components/ui/badge";
import { formatCurrency } from "@/lib/utils";

export default function View({ employee }) {

  return (
    <AuthenticatedLayout>
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Staff Management"
            subpage="Staff Details"
            url="staffs.index"
          />
          <div className="p-4">
            <div className="grid gap-6">
              {/* Personal Information */}
              <div>
                <div className="text-xl font-bold">Personal Information</div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Employee ID</p>
                      <p className="font-medium">{employee.employee_id}</p>
                    </div>
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Full Name</p>
                      <p className="font-medium">{employee.name}</p>
                    </div>
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Email Address</p>
                      <p className="font-medium">{employee.email || "-"}</p>
                    </div>
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Phone Number</p>
                      <p className="font-medium">{employee.phone || "-"}</p>
                    </div>
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Date of Birth</p>
                      <p className="font-medium">{employee.date_of_birth}</p>
                    </div>
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Address</p>
                      <p className="font-medium">
                        {employee.city && employee.country 
                          ? `${employee.city}, ${employee.country}`
                          : employee.city || employee.country || "-"}
                      </p>
                    </div>
                  </div>
              </div>

              {/* Employment Information */}
              <div>
                <div className="text-xl font-bold">Employment Information</div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Department</p>
                      <p className="font-medium">{employee.department?.name || "-"}</p>
                    </div>
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Designation</p>
                      <p className="font-medium">{employee.designation?.name || "-"}</p>
                    </div>
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Joining Date</p>
                      <p className="font-medium">{employee.joining_date}</p>
                    </div>
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">End Date</p>
                      <p className="font-medium">
                        {employee.end_date ? employee.end_date : 
                          <Badge variant="success">Active</Badge>}
                      </p>
                    </div>
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Basic Salary</p>
                      <p className="font-medium">{formatCurrency({ amount: employee.basic_salary })}</p>
                    </div>
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Employment Status</p>
                      <p className="font-medium">
                        {!employee.end_date || new Date(employee.end_date) > new Date() ? (
                          <Badge variant="success">Active</Badge>
                        ) : (
                          <Badge variant="destructive">Inactive</Badge>
                        )}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Bank Details */}
              <div>
                <div className="text-xl font-bold">Bank Details</div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Bank Name</p>
                      <p className="font-medium">{employee.bank_name || "-"}</p>
                    </div>
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Branch Name</p>
                      <p className="font-medium">{employee.branch_name || "-"}</p>
                    </div>
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Account Name</p>
                      <p className="font-medium">{employee.account_name || "-"}</p>
                    </div>
                    <div className="space-y-1">
                      <p className="text-sm font-medium text-gray-500">Account Number</p>
                      <p className="font-medium">{employee.account_number || "-"}</p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Employment History */}
              {employee.department_history && employee.department_history.length > 0 && (
                <div>
                  <div className="text-xl font-bold">Employment History</div>
                  <div className="space-y-4">
                    <div className="space-y-4">
                      {employee.department_history.map((history, index) => {
                        const details = typeof history.details === 'string' 
                          ? JSON.parse(history.details) 
                          : history.details;
                        
                        return (
                          <div key={index} className="p-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <div className="space-y-1">
                                <p className="text-sm font-medium text-gray-500">Department</p>
                                <p className="font-medium">{details.department?.name || "-"}</p>
                              </div>
                              <div className="space-y-1">
                                <p className="text-sm font-medium text-gray-500">Designation</p>
                                <p className="font-medium">{details.designation?.name || "-"}</p>
                              </div>
                              <div className="space-y-1">
                                <p className="text-sm font-medium text-gray-500">From</p>
                                <p className="font-medium">{details.joining_date}</p>
                              </div>
                              <div className="space-y-1">
                                <p className="text-sm font-medium text-gray-500">To</p>
                                <p className="font-medium">{details.end_date ? details.end_date : "Present"}</p>
                              </div>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
