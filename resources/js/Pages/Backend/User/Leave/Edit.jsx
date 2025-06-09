import React, { useState } from "react";
import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import {
  SearchableCombobox
} from "@/Components/ui/searchable-combobox";
import { cn, parseDateObject } from "@/lib/utils";
import { useToast } from "@/hooks/use-toast";
import { Toaster } from "@/Components/ui/toaster";
import PageHeader from "@/Components/PageHeader";
import DateTimePicker from "@/Components/DateTimePicker";

export default function Edit({ leave, staff = [] }) {
  const { errors } = usePage().props;
  const { toast } = useToast();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [form, setForm] = useState({
    employee_id: leave.employee_id?.toString() || "",
    leave_type: leave.leave_type || "casual",
    leave_duration: leave.leave_duration || "full_day",
    start_date: parseDateObject(leave.start_date),
    end_date: parseDateObject(leave.end_date),
    total_days: leave.total_days?.toString(),
    description: leave.description,
    status: leave.status
  });

  const LeaveStatusBadge = ({ status }) => {
    const statusMap = {
        0: {
            label: "Pending",
            className: "text-primary bg-primary/10 px-3 py-1 rounded text-xs",
        },
        1: {
            label: "Approved",
            className:
                "text-secondary bg-secondary/10 px-3 py-1 rounded text-xs",
        },
        2: {
            label: "Cancelled",
            className: "text-danger bg-danger/10 px-3 py-1 rounded text-xs",
        },
    };

    return (
        <span className={statusMap[status].className}>
            {statusMap[status].label}
        </span>
    );
};

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
  };

  const handleSelectChange = (name, value) => {
    setForm(prev => ({ ...prev, [name]: value }));
  };

  const handleDateChange = (name, date) => {
    const formattedDate = date;
    setForm(prev => ({ ...prev, [name]: formattedDate }));

    // Calculate total days when both dates are set
    if (name === 'start_date' && form.end_date) {
      calculateTotalDays(formattedDate, form.end_date);
    } else if (name === 'end_date' && form.start_date) {
      calculateTotalDays(form.start_date, formattedDate);
    }
  };

  const calculateTotalDays = (startDate, endDate) => {
    try {
      const start = new Date(startDate);
      const end = new Date(endDate);

      // Check if dates are valid
      if (isNaN(start.getTime()) || isNaN(end.getTime())) return;

      // Calculate difference in days
      const diffTime = Math.abs(end - start);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // Include both start and end dates

      setForm(prev => ({ ...prev, total_days: diffDays.toString() }));
    } catch (error) {
      console.error("Error calculating days:", error);
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setIsSubmitting(true);

    router.put(route("leaves.update", leave.id), form, {
      onSuccess: () => {
        toast({
          title: "Success",
          description: "Leave record updated successfully",
        });
        setIsSubmitting(false);
      },
      onError: () => {
        toast({
          variant: "destructive",
          title: "Error",
          description: "Failed to update leave record",
        });
        setIsSubmitting(false);
      }
    });
  };

  return (
    <AuthenticatedLayout>
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Leave Management"
            subpage="Edit Leave"
            url="leaves.index"
          />

          <div className="p-4">
            <form onSubmit={handleSubmit}>
              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="employee_id" className="md:col-span-2 col-span-12">
                  Employee *
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <SearchableCombobox
                    options={staff}
                    value={form.employee_id}
                    onChange={(value) => handleSelectChange('employee_id', value)}
                    placeholder="Select employee"
                    emptyMessage="No employees found"
                    className={cn("md:w-1/2 w-full", errors.employee_id && "border-red-500")}
                  />
                  {errors.employee_id && (
                    <p className="text-red-500 text-sm">{errors.employee_id}</p>
                  )}
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="leave_type" className="md:col-span-2 col-span-12">
                  Leave Type *
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <SearchableCombobox
                    options={[
                      { id: 'casual', name: 'Casual Leave' },
                      { id: 'sick', name: 'Sick Leave' },
                      { id: 'maternity', name: 'Maternity Leave' },
                      { id: 'other', name: 'Other Leave' }
                    ]}
                    value={form.leave_type}
                    onChange={(value) => handleSelectChange('leave_type', value)}
                    placeholder="Select leave type"
                    emptyMessage="No leave types found"
                    className={cn("md:w-1/2 w-full", errors.leave_type && "border-red-500")}
                  />
                  {errors.leave_type && (
                    <p className="text-red-500 text-sm">{errors.leave_type}</p>
                  )}
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="leave_duration" className="md:col-span-2 col-span-12">
                  Duration *
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <SearchableCombobox
                    options={[
                      { id: 'full_day', name: 'Full Day' },
                      { id: 'half_day', name: 'Half Day' }
                    ]}
                    value={form.leave_duration}
                    onChange={(value) => handleSelectChange('leave_duration', value)}
                    placeholder="Select duration"
                    emptyMessage="No durations found"
                    className={cn("md:w-1/2 w-full", errors.leave_duration && "border-red-500")}
                  />
                  {errors.leave_duration && (
                    <p className="text-red-500 text-sm">{errors.leave_duration}</p>
                  )}
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="start_date" className="md:col-span-2 col-span-12">
                  Start Date *
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <DateTimePicker
                    value={form.start_date}
                    onChange={(date) => handleDateChange('start_date', date)}
                    className="md:w-1/2 w-full"
                    required
                  />
                  {errors.start_date && (
                    <p className="text-red-500 text-sm">{errors.start_date}</p>
                  )}
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="end_date" className="md:col-span-2 col-span-12">
                  End Date *
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <DateTimePicker
                    value={form.end_date}
                    onChange={(date) => handleDateChange('end_date', date)}
                    className="md:w-1/2 w-full"
                    required
                  />
                  {errors.end_date && (
                    <p className="text-red-500 text-sm">{errors.end_date}</p>
                  )}
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="total_days" className="md:col-span-2 col-span-12">
                  Total Days *
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    id="total_days"
                    type="number"
                    value={form.total_days}
                    onChange={handleInputChange}
                    name="total_days"
                    readOnly
                    className={cn("md:w-1/2 w-full", errors.total_days && "border-red-500")}
                  />
                  {errors.total_days && (
                    <p className="text-red-500 text-sm">{errors.total_days}</p>
                  )}
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="description" className="md:col-span-2 col-span-12">
                  Description
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Textarea
                    id="description"
                    value={form.description}
                    onChange={handleInputChange}
                    name="description"
                    className={cn("md:w-1/2 w-full", errors.description && "border-red-500")}
                  />
                  {errors.description && (
                    <p className="text-red-500 text-sm">{errors.description}</p>
                  )}
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="status" className="md:col-span-2 col-span-12">
                  Status
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <LeaveStatusBadge status={form.status} />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-6">
                <div className="md:col-span-2 col-span-12"></div>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Button
                    type="submit"
                    disabled={isSubmitting}
                  >
                    {isSubmitting ? "Updating..." : "Update Leave"}
                  </Button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
