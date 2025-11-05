import React, { useState, useEffect } from "react";
import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { RadioGroup, RadioGroupItem } from "@/Components/ui/radio-group";
import InputError from "@/Components/InputError";
import { toast } from "sonner";
import PageHeader from "@/Components/PageHeader";
import { Separator } from "@/Components/ui/separator";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";

export default function Create({ departments = [] }) {
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
    joining_date: new Date(),
    end_date: "",
    basic_salary: "",
    working_hours: "",
    time_sheet_based: 0,
    max_overtime_hours: 0,
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
                  <DateTimePicker
                    value={form.date_of_birth}
                    onChange={(date) => setForm({ ...form, date_of_birth: date })}
                    className="md:w-1/2 w-full"
                    required
                  />
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
                      options={departments.find(department => Number(department.id) === Number(form.department_id))?.designations.map((designation) => ({
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
                  <DateTimePicker
                    value={form.joining_date}
                    onChange={(date) => setForm({ ...form, joining_date: date })}
                    className="md:w-1/2 w-full"
                    required
                  />
                  <InputError message={errors.joining_date} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="end_date" className="md:col-span-2 col-span-12">
                  End Date
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <DateTimePicker
                    value={form.end_date}
                    onChange={(date) => setForm({ ...form, end_date: date })}
                    className="md:w-1/2 w-full"
                    required
                  />
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

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="working_hours" className="md:col-span-2 col-span-12">
                  Working Hours *
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    type="number"
                    id="working_hours"
                    name="working_hours"
                    value={form.working_hours}
                    onChange={handleInputChange}
                    className="md:w-1/2 w-full"
                    required
                  />
                  <InputError message={errors.working_hours} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-4">
                <Label htmlFor="time_sheet_based" className="md:col-span-2 col-span-12">
                  Time Sheet Based
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <RadioGroup
                    value={form.time_sheet_based}
                    onValueChange={(value) => handleSelectChange("time_sheet_based", value)}
                    className="flex gap-4"
                  >
                    <div className="flex items-center space-x-2">
                      <RadioGroupItem value="0" id="no" />
                      <Label htmlFor="no">No</Label>
                    </div>
                    <div className="flex items-center space-x-2">
                      <RadioGroupItem value="1" id="yes" />
                      <Label htmlFor="yes">Yes</Label>
                    </div>
                  </RadioGroup>
                  <InputError message={errors.time_sheet_based} className="text-sm" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-4">
                <Label htmlFor="max_overtime_hours" className="md:col-span-2 col-span-12">
                  Max Overtime Hours/Day
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    type="number"
                    id="max_overtime_hours"
                    name="max_overtime_hours"
                    value={form.max_overtime_hours}
                    onChange={handleInputChange}
                    className="md:w-1/2 w-full"
                  />
                  <InputError message={errors.max_overtime_hours} className="text-sm" />
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
