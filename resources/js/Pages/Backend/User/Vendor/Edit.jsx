import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { Head } from "@inertiajs/react";

export default function Edit({ vendor }) {
  const { data, setData, put, processing, errors } = useForm({
    name: vendor.name || "",
    company_name: vendor.company_name || "",
    email: vendor.email || "",
    password: "",
    registration_no: vendor.registration_no || "",
    vat_id: vendor.vat_id || "",
    mobile: vendor.mobile || "",
    country: vendor.country || "",
    city: vendor.city || "",
    contract_no: vendor.contract_no || "",
    zip: vendor.zip || "",
    address: vendor.address || "",
    profile_picture: null,
  });

  const submit = (e) => {
    e.preventDefault();
    put(route("vendors.update", vendor.id), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Vendor updated successfully");
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Vendors" subpage="Edit" url="vendors.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <form onSubmit={submit} encType="multipart/form-data">
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

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="password" className="md:col-span-2 col-span-12">
                Password
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="password"
                  type="password"
                  value={data.password}
                  onChange={(e) => setData("password", e.target.value)}
                  className="md:w-1/2 w-full"
                  placeholder="Leave blank to keep current password"
                />
                <InputError message={errors.password} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="registration_no" className="md:col-span-2 col-span-12">
                Registration No
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="registration_no"
                  type="text"
                  value={data.registration_no}
                  onChange={(e) => setData("registration_no", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.registration_no} className="text-sm" />
              </div>
            </div>

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

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="contract_no" className="md:col-span-2 col-span-12">
                Contract No
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

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="zip" className="md:col-span-2 col-span-12">
                ZIP Code
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

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="address" className="md:col-span-2 col-span-12">
                Address
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Textarea
                  id="address"
                  value={data.address}
                  onChange={(e) => setData("address", e.target.value)}
                  className="md:w-1/2 w-full"
                  rows={3}
                />
                <InputError message={errors.address} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="profile_picture" className="md:col-span-2 col-span-12">
                Profile Picture
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="profile_picture"
                  type="file"
                  onChange={(e) => setData("profile_picture", e.target.files[0])}
                  className="md:w-1/2 w-full"
                />
                {vendor.profile_picture && vendor.profile_picture !== 'default.png' && (
                  <div className="mt-2">
                    <p className="text-sm text-gray-600">Current profile picture:</p>
                    <img 
                      src={`/uploads/profile/${vendor.profile_picture}`} 
                      alt="Profile" 
                      className="w-20 h-20 object-cover rounded mt-1" 
                    />
                  </div>
                )}
                <InputError message={errors.profile_picture} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-4">
              <div className="md:col-span-2 col-span-12"></div>
              <div className="md:col-span-10 col-span-12">
                <Button type="submit" disabled={processing}>
                  {processing ? "Updating..." : "Update Vendor"}
                </Button>
              </div>
            </div>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
