import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

export default function Edit({ customer }) {
  const { data, setData, post, processing, errors, reset } = useForm({
    name: customer.name,
    company_name: customer.company_name,
    email: customer.email,
    mobile: customer.mobile,
    age: customer.age,
    gender: customer.gender,
    country: customer.country,
    vat_id: customer.vat_id,
    registration_no: customer.registration_no,
    city: customer.city,
    contract_no: customer.contract_no,
    zip: customer.zip,
    address: customer.address,
    remarks: customer.remarks,
    _method: "PUT",
  });

  const submit = (e) => {
    e.preventDefault();
    post(route("customers.update", customer.id), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Customer updated successfully");
        reset();
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Customers" subpage="Edit" url="customers.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <form onSubmit={submit}>
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="name" className="md:col-span-2 col-span-12">
                Name *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="name"
                  type="text"
                  value={data.name}
                  onChange={(e) => setData("name", e.target.value)}
                  className="md:w-1/2 w-full"
                  required
                />
                <InputError message={errors.name} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="company_name" className="md:col-span-2 col-span-12">
                Company Name
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="company_name"
                  type="text"
                  value={data.company_name}
                  onChange={(e) => setData("company_name", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.company_name} className="text-sm" />
              </div>
            </div>

            {/* email */}
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="email" className="md:col-span-2 col-span-12">
                Email
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="email"
                  type="email"
                  value={data.email}
                  onChange={(e) => setData("email", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.email} className="text-sm" />
              </div>
            </div>

            {/* mobile */}
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="mobile" className="md:col-span-2 col-span-12">
                Mobile
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="mobile"
                  type="text"
                  value={data.mobile}
                  onChange={(e) => setData("mobile", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.mobile} className="text-sm" />
              </div>
            </div>

            {/* age */}
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="age" className="md:col-span-2 col-span-12">
                Age
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="age"
                  type="number"
                  value={data.age}
                  onChange={(e) => setData("age", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.age} className="text-sm" />
              </div>
            </div>

            {/* gender */}
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="gender" className="md:col-span-2 col-span-12">
                Gender
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={[
                      { id: "male", name: "Male" },
                      { id: "female", name: "Female" },
                    ]}
                    value={data.gender}
                    onChange={(value) => setData("gender", value)}
                    placeholder="Select gender"
                  />
                </div>
                <InputError message={errors.gender} className="text-sm" />
              </div>
            </div>

            {/* country */}
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="country" className="md:col-span-2 col-span-12">
                Country
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="country"
                  type="text"
                  value={data.country}
                  onChange={(e) => setData("country", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.country} className="text-sm" />
              </div>
            </div>

            <SidebarSeparator className="my-4" />

            {/* vat_id */}
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="vat_id" className="md:col-span-2 col-span-12">
                VAT ID
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="vat_id"
                  type="text"
                  value={data.vat_id}
                  onChange={(e) => setData("vat_id", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.vat_id} className="text-sm" />
              </div>
            </div>

            {/* reg_no */}
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="reg_no" className="md:col-span-2 col-span-12">
                Registration Number
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="reg_no"
                  type="text"
                  value={data.reg_no}
                  onChange={(e) => setData("reg_no", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.reg_no} className="text-sm" />
              </div>
            </div>

            {/* city */}
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="city" className="md:col-span-2 col-span-12">
                City
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="city"
                  type="text"
                  value={data.city}
                  onChange={(e) => setData("city", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.city} className="text-sm" />
              </div>
            </div>

            {/* contract_no */}
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="contract_no" className="md:col-span-2 col-span-12">
                Contract Number
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="contract_no"
                  type="text"
                  value={data.contract_no}
                  onChange={(e) => setData("contract_no", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.contract_no} className="text-sm" />
              </div>
            </div>

            {/* zip */}
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="zip" className="md:col-span-2 col-span-12">
                Zip
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="zip"
                  type="text"
                  value={data.zip}
                  onChange={(e) => setData("zip", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.zip} className="text-sm" />
              </div>
            </div>

            {/* address */}
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="address" className="md:col-span-2 col-span-12">
                Address
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="address"
                  type="text"
                  value={data.address}
                  onChange={(e) => setData("address", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.address} className="text-sm" />
              </div>
            </div>

            {/* remarks */}
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="remarks" className="md:col-span-2 col-span-12">
                Remarks
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="remarks"
                  type="text"
                  value={data.remarks}
                  onChange={(e) => setData("remarks", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.remarks} className="text-sm" />
              </div>
            </div>

            <Button type="submit" disabled={processing} className="mt-4">
              {processing ? "Creating..." : "Create Customer"}
            </Button>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
