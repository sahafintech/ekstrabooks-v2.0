import React, { useState, useEffect } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Textarea } from "@/Components/ui/textarea";
import InputError from "@/Components/InputError";
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
import { Popover, PopoverContent, PopoverTrigger } from "@/Components/ui/popover";
import { Calendar } from "@/Components/ui/calendar";
import { CalendarIcon, InfoIcon } from "lucide-react";
import { cn } from "@/lib/utils";
import { Alert, AlertDescription } from "@/Components/ui/alert";

export default function Create({ employees = [], date = null, message = null }) {
  const { errors, flash } = usePage().props;
  const [processing, setProcessing] = useState(false);
  const [selectedDate, setSelectedDate] = useState(date ? new Date(date) : new Date());
  const [showEmployeeList, setShowEmployeeList] = useState(!!date);
  const [attendanceData, setAttendanceData] = useState([]);

  useEffect(() => {
    if (employees && employees.length > 0) {
      const initialAttendanceData = employees.map((employee) => ({
        employee_id: employee.id,
        status: employee.attendance_status || "1", // Default to present if not defined
        leave_type: employee.leave_type || "",
        leave_duration: employee.attendance_leave_duration || employee.leave_duration || "full_day",
        remarks: employee.attendance_remarks || "",
      }));
      setAttendanceData(initialAttendanceData);
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

  const handleDateSelect = (date) => {
    setSelectedDate(date);
    
    // Format the date to YYYY-MM-DD for the API
    const formattedDate = format(date, "yyyy-MM-dd");
    
    // Redirect to the same page with the selected date as a parameter
    router.get(route("attendance.create"), { date: formattedDate });
  };

  const handleDateSubmit = (e) => {
    e.preventDefault();
    const formattedDate = format(selectedDate, "yyyy-MM-dd");
    router.post(route("attendance.create"), { date: formattedDate });
  };

  const handleStatusChange = (index, value) => {
    const updatedData = [...attendanceData];
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
    
    setAttendanceData(updatedData);
  };

  const handleInputChange = (index, field, value) => {
    const updatedData = [...attendanceData];
    updatedData[index][field] = value;
    setAttendanceData(updatedData);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setProcessing(true);
    
    const formData = {
      date: format(selectedDate, "yyyy-MM-dd"),
      employee_id: attendanceData.map(item => item.employee_id),
      status: attendanceData.map(item => item.status),
      leave_type: attendanceData.map(item => item.leave_type || ""),
      leave_duration: attendanceData.map(item => item.leave_duration || ""),
      remarks: attendanceData.map(item => item.remarks || ""),
    };
    
    router.post(route("attendance.store"), formData, {
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
            subpage="Add Attendance"
            url="attendance.index"
          />
          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            {!showEmployeeList ? (
              // Step 1: Date Selection
              <div className="rounded-md border p-6">
                <h3 className="text-lg font-semibold mb-4">Select Attendance Date</h3>
                <Separator className="my-2" />
                
                <form onSubmit={handleDateSubmit} className="mt-4">
                  <div className="grid grid-cols-1 gap-4">
                    <div>
                      <Label htmlFor="date">Date <span className="text-red-500">*</span></Label>
                      <div className="flex mt-2">
                        <Popover>
                          <PopoverTrigger asChild>
                            <Button
                              variant={"outline"}
                              className={cn(
                                "w-[240px] justify-start text-left font-normal",
                                !selectedDate && "text-muted-foreground"
                              )}
                            >
                              <CalendarIcon className="mr-2 h-4 w-4" />
                              {selectedDate ? (
                                format(selectedDate, "PPP")
                              ) : (
                                <span>Select date</span>
                              )}
                            </Button>
                          </PopoverTrigger>
                          <PopoverContent className="w-auto p-0" align="start">
                            <Calendar
                              mode="single"
                              selected={selectedDate}
                              onSelect={handleDateSelect}
                              initialFocus
                            />
                          </PopoverContent>
                        </Popover>
                      </div>
                      <InputError message={errors.date} className="mt-2" />
                    </div>
                    
                    <div className="mt-4">
                      <Button type="submit" disabled={!selectedDate}>
                        Next
                      </Button>
                    </div>
                  </div>
                </form>
              </div>
            ) : (
              // Step 2: Employee Attendance
              <div className="rounded-md border p-6">
                <h3 className="text-lg font-semibold mb-2">
                  Attendance for {selectedDate ? format(selectedDate, "PPP") : ""}
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
                    <p className="text-muted-foreground">No employees found to mark attendance.</p>
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
                                  value={attendanceData[index]?.status || "1"}
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
                                  value={attendanceData[index]?.leave_type || ""}
                                  onChange={(e) => handleInputChange(index, "leave_type", e.target.value)}
                                  disabled={attendanceData[index]?.status === "1"}
                                />
                              </td>
                              <td className="border px-4 py-2">
                                <Select
                                  value={attendanceData[index]?.leave_duration || "full_day"}
                                  onValueChange={(value) => handleInputChange(index, "leave_duration", value)}
                                  disabled={attendanceData[index]?.status === "1"}
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
                                  value={attendanceData[index]?.remarks || ""}
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
                        {processing ? "Saving..." : "Save Attendance"}
                      </Button>
                    </div>
                  </form>
                )}
              </div>
            )}
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
