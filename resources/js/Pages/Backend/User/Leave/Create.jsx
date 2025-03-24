import React, { useState } from "react";
import { Head, router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import {
  SearchableCombobox
} from "@/Components/ui/searchable-combobox";
import { cn } from "@/lib/utils";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/Components/ui/popover";
import { Calendar } from "@/Components/ui/calendar";
import { CalendarIcon } from "lucide-react";
import { format } from "date-fns";
import { useToast } from "@/hooks/use-toast";
import { Toaster } from "@/Components/ui/toaster";
import PageHeader from "@/Components/PageHeader";

export default function Create({ staff = [] }) {
  const { errors } = usePage().props;
  const { toast } = useToast();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [form, setForm] = useState({
    employee_id: "",
    leave_type: "casual",
    leave_duration: "full_day",
    start_date: "",
    end_date: "",
    total_days: "",
    description: "",
    status: "pending"
  });

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
  };

  const handleSelectChange = (name, value) => {
    setForm(prev => ({ ...prev, [name]: value }));
  };

  const handleDateChange = (name, date) => {
    const formattedDate = date ? format(date, 'yyyy-MM-dd') : '';
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

    router.post(route("leaves.store"), form, {
      onSuccess: () => {
        toast({
          title: "Success",
          description: "Leave record created successfully",
        });
        setIsSubmitting(false);
      },
      onError: () => {
        toast({
          variant: "destructive",
          title: "Error",
          description: "Failed to create leave record",
        });
        setIsSubmitting(false);
      }
    });
  };

  return (
    <AuthenticatedLayout>
      <Head title="Create Leave" />
      <Toaster />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Leave Management"
            subpage="Create Leave"
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
                  <Popover>
                    <PopoverTrigger asChild>
                      <Button
                        variant={"outline"}
                        className={cn(
                          "md:w-1/2 w-full justify-start text-left font-normal",
                          !form.start_date && "text-muted-foreground",
                          errors.start_date && "border-red-500"
                        )}
                      >
                        <CalendarIcon className="mr-2 h-4 w-4" />
                        {form.start_date ? (
                          format(new Date(form.start_date), "PPP")
                        ) : (
                          <span>Select start date</span>
                        )}
                      </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-auto p-0" align="start">
                      <Calendar
                        mode="single"
                        selected={form.start_date ? new Date(form.start_date) : undefined}
                        onSelect={(date) => handleDateChange('start_date', date)}
                        initialFocus
                      />
                    </PopoverContent>
                  </Popover>
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
                  <Popover>
                    <PopoverTrigger asChild>
                      <Button
                        variant={"outline"}
                        className={cn(
                          "md:w-1/2 w-full justify-start text-left font-normal",
                          !form.end_date && "text-muted-foreground",
                          errors.end_date && "border-red-500"
                        )}
                      >
                        <CalendarIcon className="mr-2 h-4 w-4" />
                        {form.end_date ? (
                          format(new Date(form.end_date), "PPP")
                        ) : (
                          <span>Select end date</span>
                        )}
                      </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-auto p-0" align="start">
                      <Calendar
                        mode="single"
                        selected={form.end_date ? new Date(form.end_date) : undefined}
                        onSelect={(date) => handleDateChange('end_date', date)}
                        initialFocus
                      />
                    </PopoverContent>
                  </Popover>
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
                  Status *
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <SearchableCombobox
                    options={[
                      { id: 'pending', name: 'Pending' },
                      { id: 'approved', name: 'Approved' },
                      { id: 'rejected', name: 'Rejected' }
                    ]}
                    value={form.status}
                    onChange={(value) => handleSelectChange('status', value)}
                    placeholder="Select status"
                    emptyMessage="No status options found"
                    className={cn("md:w-1/2 w-full", errors.status && "border-red-500")}
                  />
                  {errors.status && (
                    <p className="text-red-500 text-sm">{errors.status}</p>
                  )}
                </div>
              </div>

              <div className="grid grid-cols-12 mt-6">
                <div className="md:col-span-2 col-span-12"></div>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Button
                    type="submit"
                    disabled={isSubmitting}
                  >
                    {isSubmitting ? "Creating..." : "Create Leave"}
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
