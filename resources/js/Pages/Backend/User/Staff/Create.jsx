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
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Popover, PopoverContent, PopoverTrigger } from "@/Components/ui/popover";
import { Calendar } from "@/Components/ui/calendar";
import { CalendarIcon } from "lucide-react";
import { cn } from "@/lib/utils";

export default function Create({ departments = [], designations = [] }) {
  const { errors, flash } = usePage().props;
  const [processing, setProcessing] = useState(false);

  const [form, setForm] = useState({
    employee_id: "",
    name: "",
    date_of_birth: "",
    email: "",
    phone: "",
    city: "",
    country: "",
    department_id: "",
    designation_id: "",
    joining_date: format(new Date(), "yyyy-MM-dd"),
    end_date: "",
    basic_salary: "",
    bank_name: "",
    branch_name: "",
    account_name: "",
    account_number: "",
  });

  useEffect(() => {
    if (flash && flash.success) {
      toast.success("Staff created successfully");
    }

    if (flash && flash.error) {
      toast.error(flash.error);
    }
  }, [flash]);

  const handleSubmit = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(route("staffs.store"), form, {
      onSuccess: () => {
        setProcessing(false);
      },
      onError: () => {
        setProcessing(false);
      },
    });
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setForm({
      ...form,
      [name]: value,
    });
  };

  const handleSelectChange = (name, value) => {
    setForm({
      ...form,
      [name]: value,
    });
  };

  return (
    <AuthenticatedLayout>
      <Head title="Create Staff" />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Staff Management"
            subpage="Add New Staff"
            url="staffs.index"
          />
          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            <form onSubmit={handleSubmit}>
              <h3 className="text-lg font-semibold mb-4">Personal Information</h3>
              <Separator className="my-2" />

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="employee_id" className="md:col-span-2 col-span-12">
                  Employee ID <span className="text-red-500">*</span>
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    id="employee_id"
                    name="employee_id"
                    value={form.employee_id}
                    onChange={handleInputChange}
                    className="md:w-1/2 w-full"
                    required
                  />
                  <InputError message={errors.employee_id} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="name" className="md:col-span-2 col-span-12">
                  Full Name <span className="text-red-500">*</span>
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    id="name"
                    name="name"
                    value={form.name}
                    onChange={handleInputChange}
                    className="md:w-1/2 w-full"
                    required
                  />
                  <InputError message={errors.name} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="date_of_birth" className="md:col-span-2 col-span-12">
                  Date of Birth
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Popover>
                    <PopoverTrigger asChild>
                      <Button
                        variant={"outline"}
                        className={cn(
                          "md:w-1/2 w-full justify-start text-left font-normal",
                          !form.date_of_birth && "text-muted-foreground"
                        )}
                      >
                        <CalendarIcon className="mr-2 h-4 w-4" />
                        {form.date_of_birth ? (
                          format(new Date(form.date_of_birth), "PPP")
                        ) : (
                          <span>Pick a date</span>
                        )}
                      </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-auto p-0" align="start">
                      <Calendar
                        mode="single"
                        selected={form.date_of_birth ? new Date(form.date_of_birth) : undefined}
                        onSelect={(date) =>
                          setForm({
                            ...form,
                            date_of_birth: date ? format(date, "yyyy-MM-dd") : "",
                          })
                        }
                        initialFocus
                      />
                    </PopoverContent>
                  </Popover>
                  <InputError message={errors.date_of_birth} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="email" className="md:col-span-2 col-span-12">
                  Email Address
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    type="email"
                    id="email"
                    name="email"
                    value={form.email}
                    onChange={handleInputChange}
                    className="md:w-1/2 w-full"
                  />
                  <InputError message={errors.email} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="phone" className="md:col-span-2 col-span-12">
                  Phone Number
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    id="phone"
                    name="phone"
                    value={form.phone}
                    onChange={handleInputChange}
                    className="md:w-1/2 w-full"
                  />
                  <InputError message={errors.phone} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="city" className="md:col-span-2 col-span-12">
                  City
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    id="city"
                    name="city"
                    value={form.city}
                    onChange={handleInputChange}
                    className="md:w-1/2 w-full"
                  />
                  <InputError message={errors.city} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="country" className="md:col-span-2 col-span-12">
                  Country
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    id="country"
                    name="country"
                    value={form.country}
                    onChange={handleInputChange}
                    className="md:w-1/2 w-full"
                  />
                  <InputError message={errors.country} className="text-sm" />
                </div>
              </div>

              <h3 className="text-lg font-semibold mb-4 mt-8">Employment Information</h3>
              <Separator className="my-2" />

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="department_id" className="md:col-span-2 col-span-12">
                  Department <span className="text-red-500">*</span>
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <div className="md:w-1/2 w-full">
                    <SearchableCombobox
                      options={departments.map((department) => ({
                        id: department.id.toString(),
                        name: department.name,
                      }))}
                      value={form.department_id}
                      onChange={(value) => handleSelectChange("department_id", value)}
                      placeholder="Select department"
                    />
                  </div>
                  <InputError message={errors.department_id} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="designation_id" className="md:col-span-2 col-span-12">
                  Designation <span className="text-red-500">*</span>
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <div className="md:w-1/2 w-full">
                    <SearchableCombobox
                      options={designations.map((designation) => ({
                        id: designation.id.toString(),
                        name: designation.name,
                      }))}
                      value={form.designation_id}
                      onChange={(value) => handleSelectChange("designation_id", value)}
                      placeholder="Select designation"
                    />
                  </div>
                  <InputError message={errors.designation_id} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="joining_date" className="md:col-span-2 col-span-12">
                  Joining Date <span className="text-red-500">*</span>
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Popover>
                    <PopoverTrigger asChild>
                      <Button
                        variant={"outline"}
                        className={cn(
                          "md:w-1/2 w-full justify-start text-left font-normal",
                          !form.joining_date && "text-muted-foreground"
                        )}
                      >
                        <CalendarIcon className="mr-2 h-4 w-4" />
                        {form.joining_date ? (
                          format(new Date(form.joining_date), "PPP")
                        ) : (
                          <span>Pick a date</span>
                        )}
                      </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-auto p-0" align="start">
                      <Calendar
                        mode="single"
                        selected={form.joining_date ? new Date(form.joining_date) : undefined}
                        onSelect={(date) =>
                          setForm({
                            ...form,
                            joining_date: date ? format(date, "yyyy-MM-dd") : "",
                          })
                        }
                        initialFocus
                      />
                    </PopoverContent>
                  </Popover>
                  <InputError message={errors.joining_date} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="end_date" className="md:col-span-2 col-span-12">
                  End Date
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Popover>
                    <PopoverTrigger asChild>
                      <Button
                        variant={"outline"}
                        className={cn(
                          "md:w-1/2 w-full justify-start text-left font-normal",
                          !form.end_date && "text-muted-foreground"
                        )}
                      >
                        <CalendarIcon className="mr-2 h-4 w-4" />
                        {form.end_date ? (
                          format(new Date(form.end_date), "PPP")
                        ) : (
                          <span>Pick a date</span>
                        )}
                      </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-auto p-0" align="start">
                      <Calendar
                        mode="single"
                        selected={form.end_date ? new Date(form.end_date) : undefined}
                        onSelect={(date) =>
                          setForm({
                            ...form,
                            end_date: date ? format(date, "yyyy-MM-dd") : "",
                          })
                        }
                        initialFocus
                      />
                    </PopoverContent>
                  </Popover>
                  <InputError message={errors.end_date} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="basic_salary" className="md:col-span-2 col-span-12">
                  Basic Salary <span className="text-red-500">*</span>
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    type="number"
                    step="0.01"
                    id="basic_salary"
                    name="basic_salary"
                    value={form.basic_salary}
                    onChange={handleInputChange}
                    className="md:w-1/2 w-full"
                    required
                  />
                  <InputError message={errors.basic_salary} className="text-sm" />
                </div>
              </div>

              <h3 className="text-lg font-semibold mb-4 mt-8">Bank Details</h3>
              <Separator className="my-2" />

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="bank_name" className="md:col-span-2 col-span-12">
                  Bank Name
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    id="bank_name"
                    name="bank_name"
                    value={form.bank_name}
                    onChange={handleInputChange}
                    className="md:w-1/2 w-full"
                  />
                  <InputError message={errors.bank_name} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="branch_name" className="md:col-span-2 col-span-12">
                  Branch Name
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    id="branch_name"
                    name="branch_name"
                    value={form.branch_name}
                    onChange={handleInputChange}
                    className="md:w-1/2 w-full"
                  />
                  <InputError message={errors.branch_name} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="account_name" className="md:col-span-2 col-span-12">
                  Account Name
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    id="account_name"
                    name="account_name"
                    value={form.account_name}
                    onChange={handleInputChange}
                    className="md:w-1/2 w-full"
                  />
                  <InputError message={errors.account_name} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="account_number" className="md:col-span-2 col-span-12">
                  Account Number
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    id="account_number"
                    name="account_number"
                    value={form.account_number}
                    onChange={handleInputChange}
                    className="md:w-1/2 w-full"
                  />
                  <InputError message={errors.account_number} className="text-sm" />
                </div>
              </div>

              <div className="flex mt-8">
                <Button
                  variant="outline"
                  type="button"
                  onClick={() => router.get(route("staffs.index"))}
                  className="mr-2"
                >
                  Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                  {processing ? "Creating..." : "Create Staff"}
                </Button>
              </div>
            </form>
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
