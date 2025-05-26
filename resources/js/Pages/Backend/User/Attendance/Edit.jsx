import React, { useState, useEffect } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { format } from "date-fns";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import { toast } from "sonner";
import PageHeader from "@/Components/PageHeader";
import { Separator } from "@/Components/ui/separator";
import { Alert, AlertDescription } from "@/Components/ui/alert";

export default function Edit({ attendanceData = {}, employees = [], message = null }) {
  const { flash } = usePage().props;
  const [processing, setProcessing] = useState(false);
  const [selectedDate, setSelectedDate] = useState(
    attendanceData.date ? new Date(attendanceData.date) : new Date()
  );
  const [attendanceRecords, setAttendanceRecords] = useState([]);

  useEffect(() => {
    if (employees && employees.length > 0) {
      const initialAttendanceRecords = employees.map((employee) => ({
        employee_id: employee.id,
        status: employee.attendance_status || "1", // Default to present
        leave_type: employee.leave_type || "",
        leave_duration: employee.attendance_leave_duration || employee.leave_duration || "full_day",
        remarks: employee.attendance_remarks || "",
      }));
      setAttendanceRecords(initialAttendanceRecords);
    }
  }, [employees]);

  useEffect(() => {
    if (flash && flash.success) {
      toast.success(flash.success);
    }

    if (flash && flash.error) {
      toast.error(flash.error);
    }
  }, [flash]);

  const handleStatusChange = (index, value) => {
    const updatedData = [...attendanceRecords];
    updatedData[index].status = value;
    
    // If status is present, clear leave type and duration
    if (value === "1") {
      updatedData[index].leave_type = "";
      updatedData[index].leave_duration = "";
    } else if (value === "0") {
      // If status is absent, set leave duration to full_day by default
      updatedData[index].leave_type = "";
      updatedData[index].leave_duration = "full_day";
    }
    
    setAttendanceRecords(updatedData);
  };

  const handleInputChange = (index, field, value) => {
    const updatedData = [...attendanceRecords];
    updatedData[index][field] = value;
    setAttendanceRecords(updatedData);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    const formData = {
      date: format(selectedDate, "yyyy-MM-dd"),
      employee_id: attendanceRecords.map(item => item.employee_id),
      status: attendanceRecords.map(item => item.status),
      leave_type: attendanceRecords.map(item => item.leave_type || ""),
      leave_duration: attendanceRecords.map(item => item.leave_duration || ""),
      remarks: attendanceRecords.map(item => item.remarks || ""),
    };
    
    router.post(route("attendance.update"), formData, {
      onSuccess: () => {
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Attendance Management"
            subpage="Edit Attendance"
            url="attendance.index"
          />
          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div className="rounded-md border p-6">
              <h3 className="text-lg font-semibold mb-2">
                Edit Attendance for {selectedDate ? format(selectedDate, "PPP") : ""}
              </h3>
              <Separator className="my-2" />
              
              {message && (
                <Alert className="my-4">
                  <InfoIcon className="h-4 w-4" />
                  <AlertDescription>{message}</AlertDescription>
                </Alert>
              )}
              
              {employees.length === 0 ? (
                <div className="text-center p-4">
                  <p className="text-muted-foreground">No employees found to edit attendance.</p>
                </div>
              ) : (
                <form onSubmit={handleSubmit} className="mt-4">
                  <div className="overflow-x-auto">
                    <table className="w-full border-collapse">
                      <thead>
                        <tr className="bg-muted">
                          <th className="border px-4 py-2 text-left">Employee</th>
                          <th className="border px-4 py-2 text-left">Status</th>
                          <th className="border px-4 py-2 text-left">Leave Type</th>
                          <th className="border px-4 py-2 text-left">Leave Duration</th>
                          <th className="border px-4 py-2 text-left">Remarks</th>
                        </tr>
                      </thead>
                      <tbody>
                        {employees.map((employee, index) => (
                          <tr key={employee.id} className="border-b">
                            <td className="border px-4 py-2">
                              <input 
                                type="hidden" 
                                name={`employee_id[${index}]`} 
                                value={employee.id} 
                              />
                              {employee.name}
                            </td>
                            <td className="border px-4 py-2">
                              <Select
                                value={attendanceRecords[index]?.status || "1"}
                                onValueChange={(value) => handleStatusChange(index, value)}
                              >
                                <SelectTrigger className="w-full">
                                  <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                  <SelectItem value="1">Present</SelectItem>
                                  <SelectItem value="0">Absent</SelectItem>
                                  <SelectItem value="2">Leave</SelectItem>
                                </SelectContent>
                              </Select>
                            </td>
                            <td className="border px-4 py-2">
                              <Input
                                type="text"
                                placeholder="Leave type"
                                value={attendanceRecords[index]?.leave_type || ""}
                                onChange={(e) => handleInputChange(index, "leave_type", e.target.value)}
                                disabled={attendanceRecords[index]?.status === "1"}
                              />
                            </td>
                            <td className="border px-4 py-2">
                              <Select
                                value={attendanceRecords[index]?.leave_duration || "full_day"}
                                onValueChange={(value) => handleInputChange(index, "leave_duration", value)}
                                disabled={attendanceRecords[index]?.status === "1"}
                              >
                                <SelectTrigger className="w-full">
                                  <SelectValue placeholder="Select duration" />
                                </SelectTrigger>
                                <SelectContent>
                                  <SelectItem value="full_day">Full Day</SelectItem>
                                  <SelectItem value="half_day">Half Day</SelectItem>
                                </SelectContent>
                              </Select>
                            </td>
                            <td className="border px-4 py-2">
                              <Input
                                type="text"
                                placeholder="Remarks"
                                value={attendanceRecords[index]?.remarks || ""}
                                onChange={(e) => handleInputChange(index, "remarks", e.target.value)}
                              />
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                  
                  <div className="mt-6 flex justify-end space-x-3">
                    <Link href={route("attendance.index")}>
                      <Button type="button" variant="secondary">
                        Cancel
                      </Button>
                    </Link>
                    <Button type="submit" disabled={processing}>
                      {processing ? "Updating..." : "Update Attendance"}
                    </Button>
                  </div>
                </form>
              )}
            </div>
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
