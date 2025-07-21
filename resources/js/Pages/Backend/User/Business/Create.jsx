import { useForm } from "@inertiajs/react";
import { useState } from "react";
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
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { SidebarSeparator } from "@/Components/ui/sidebar";
import { PlusIcon, TrashIcon } from "lucide-react";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";

export default function Create({ business_types = [], currencies = [], countries = [] }) {
    const [logoPreview, setLogoPreview] = useState(null);
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        business_type_id: "",
        reg_no: "",
        vat_id: "",
        email: "",
        business_email: "",
        phone: "",
        country: "",
        contract_no: "",
        zip: "",
        address: "",
        logo: "",
        currency: "",
        status: "1",
        default: "0",
        bank_accounts: {
            bank_name: [],
            account_name: [],
            account_number: [],
            account_currency: [],
            branch: [],
            swift_code: [],
            display_on_invoice: [],
        },
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("business.store"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Business created successfully");
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Business" subpage="Add New" url="business.index" />

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
                            <Label htmlFor="business_type_id" className="md:col-span-2 col-span-12">
                                Business Type *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <SearchableCombobox
                                    options={business_types.map(business_type => ({
                                        id: business_type.id,
                                        value: business_type.id,
                                        label: business_type.name,
                                        name: business_type.name
                                    }))}
                                    value={data.business_type_id}
                                    onChange={(selectedValue) => {
                                        setData("business_type_id", selectedValue);
                                    }}
                                    className="md:w-1/2 w-full"
                                    placeholder="Select Business Type"
                                    required
                                />
                                <InputError message={errors.business_type_id} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="business_email" className="md:col-span-2 col-span-12">
                                Business Email *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="business_email"
                                    type="email"
                                    value={data.business_email}
                                    onChange={(e) => setData("business_email", e.target.value)}
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError message={errors.business_email} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="email" className="md:col-span-2 col-span-12">
                                Operation Email *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData("email", e.target.value)}
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError message={errors.email} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="registration_no" className="md:col-span-2 col-span-12">
                                Registration No
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
                            <Label htmlFor="phone" className="md:col-span-2 col-span-12">
                                Phone
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="phone"
                                    type="text"
                                    value={data.phone}
                                    onChange={(e) => setData("phone", e.target.value)}
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.phone} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="currency" className="md:col-span-2 col-span-12">
                                Currency *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        className="mt-1"
                                        options={currencies.map(currency => ({
                                            id: currency.name,
                                            value: currency.name,
                                            label: currency.name,
                                            name: `${currency.name} - ${currency.description} (${currency.exchange_rate})`
                                        }))}
                                        value={data.currency}
                                        onChange={(selectedValue) => {
                                            console.log("Currency selected:", selectedValue);
                                            setData("currency", selectedValue);
                                        }}
                                        placeholder="Select Currency"
                                        required
                                    />
                                    <InputError message={errors.currency} className="text-sm" />
                                    <InputError message="You will be not able to change this currency once you made any transaction!" className="text-sm" />
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="country" className="md:col-span-2 col-span-12">
                                Country *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <SearchableCombobox
                                    options={countries.map(country => ({
                                        id: country.id,
                                        value: country.name,
                                        label: country.name,
                                        name: country.name
                                    }))}
                                    value={data.country}
                                    onChange={(selectedValue) => {
                                        setData("country", selectedValue);
                                    }}
                                    className="md:w-1/2 w-full"
                                    placeholder="Select Country"
                                    required
                                />
                                <InputError message={errors.country} className="text-sm" />
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
                            <Label htmlFor="logo" className="md:col-span-2 col-span-12">
                                Logo
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                {logoPreview && (
                                    <div className="mb-4">
                                        <p className="text-sm text-gray-500 mb-2">Logo Preview:</p>
                                        <img 
                                            src={logoPreview} 
                                            alt="Logo Preview" 
                                            className="w-32 h-32 object-cover border rounded-md"
                                        />
                                    </div>
                                )}
                                <Input
                                    id="logo"
                                    type="file"
                                    onChange={(e) => {
                                        const file = e.target.files[0];
                                        setData("logo", file);
                                        
                                        // Create preview URL
                                        if (file) {
                                            const reader = new FileReader();
                                            reader.onload = (e) => {
                                                setLogoPreview(e.target.result);
                                            };
                                            reader.readAsDataURL(file);
                                        } else {
                                            setLogoPreview(null);
                                        }
                                    }}
                                    className="md:w-1/2 w-full"
                                    accept="image/*"
                                />
                                <InputError message={errors.logo} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="status" className="md:col-span-2 col-span-12">
                                Status
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <Select
                                        value={data.status}
                                        onValueChange={(value) => setData("status", value)}
                                    >
                                        <SelectTrigger id="status" className="w-full">
                                            <SelectValue placeholder="Select Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">Active</SelectItem>
                                            <SelectItem value="0">Inactive</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.status} className="text-sm" />
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="default" className="md:col-span-2 col-span-12">
                                Default
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <Select
                                        value={data.default}
                                        onValueChange={(value) => setData("default", value)}
                                    >
                                        <SelectTrigger id="default" className="w-full">
                                            <SelectValue value={data.default} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">Yes</SelectItem>
                                            <SelectItem value="0">No</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.default} className="text-sm" />
                                </div>
                            </div>
                        </div>

                        <SidebarSeparator className="my-4" />

                        {/* bank accounts with add item functionality */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label className="md:col-span-2 col-span-12 font-medium">
                                Bank Accounts
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="overflow-x-auto w-full">
                                    <table className="w-full border-collapse">
                                        <thead>
                                            <tr className="bg-gray-100">
                                                <th className="border px-4 py-2 text-left">Bank Name</th>
                                                <th className="border px-4 py-2 text-left">Account Name</th>
                                                <th className="border px-4 py-2 text-left">Account Number</th>
                                                <th className="border px-4 py-2 text-left">Currency</th>
                                                <th className="border px-4 py-2 text-left">Branch</th>
                                                <th className="border px-4 py-2 text-left">Swift Code</th>
                                                <th className="border px-4 py-2 text-left">Display on Invoice</th>
                                                <th className="border px-4 py-2 text-left">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {data.bank_accounts.bank_name.map((_, index) => (
                                                <tr key={index} className="border-b">
                                                    <td className="border px-4 py-2">
                                                        <Input
                                                            type="text"
                                                            value={data.bank_accounts.bank_name[index] || ""}
                                                            onChange={(e) => {
                                                                const updatedBankNames = [...data.bank_accounts.bank_name];
                                                                updatedBankNames[index] = e.target.value;
                                                                setData("bank_accounts", {
                                                                    ...data.bank_accounts,
                                                                    bank_name: updatedBankNames,
                                                                });
                                                            }}
                                                            className="w-full"
                                                        />
                                                    </td>
                                                    <td className="border px-4 py-2">
                                                        <Input
                                                            type="text"
                                                            value={data.bank_accounts.account_name[index] || ""}
                                                            onChange={(e) => {
                                                                const updatedAccountNames = [...data.bank_accounts.account_name];
                                                                updatedAccountNames[index] = e.target.value;
                                                                setData("bank_accounts", {
                                                                    ...data.bank_accounts,
                                                                    account_name: updatedAccountNames,
                                                                });
                                                            }}
                                                            className="w-full"
                                                        />
                                                    </td>
                                                    <td className="border px-4 py-2">
                                                        <Input
                                                            type="text"
                                                            value={data.bank_accounts.account_number[index] || ""}
                                                            onChange={(e) => {
                                                                const updatedAccountNumbers = [...data.bank_accounts.account_number];
                                                                updatedAccountNumbers[index] = e.target.value;
                                                                setData("bank_accounts", {
                                                                    ...data.bank_accounts,
                                                                    account_number: updatedAccountNumbers,
                                                                });
                                                            }}
                                                            className="w-full"
                                                        />
                                                    </td>
                                                    <td className="border px-4 py-2">
                                                        <Input
                                                            type="text"
                                                            value={data.bank_accounts.account_currency[index] || ""}
                                                            onChange={(e) => {
                                                                const updatedCurrencies = [...data.bank_accounts.account_currency];
                                                                updatedCurrencies[index] = e.target.value;
                                                                setData("bank_accounts", {
                                                                    ...data.bank_accounts,
                                                                    account_currency: updatedCurrencies,
                                                                });
                                                            }}
                                                            className="w-full"
                                                        />
                                                    </td>
                                                    <td className="border px-4 py-2">
                                                        <Input
                                                            type="text"
                                                            value={data.bank_accounts.branch[index] || ""}
                                                            onChange={(e) => {
                                                                const updatedBranches = [...data.bank_accounts.branch];
                                                                updatedBranches[index] = e.target.value;
                                                                setData("bank_accounts", {
                                                                    ...data.bank_accounts,
                                                                    branch: updatedBranches,
                                                                });
                                                            }}
                                                            className="w-full"
                                                        />
                                                    </td>
                                                    <td className="border px-4 py-2">
                                                        <Input
                                                            type="text"
                                                            value={data.bank_accounts.swift_code[index] || ""}
                                                            onChange={(e) => {
                                                                const updatedSwiftCodes = [...data.bank_accounts.swift_code];
                                                                updatedSwiftCodes[index] = e.target.value;
                                                                setData("bank_accounts", {
                                                                    ...data.bank_accounts,
                                                                    swift_code: updatedSwiftCodes,
                                                                });
                                                            }}
                                                            className="w-full"
                                                        />
                                                    </td>
                                                    <td className="border px-4 py-2">
                                                        <Select
                                                            value={data.bank_accounts.display_on_invoice[index] || "0"}
                                                            onValueChange={(value) => {
                                                                const updatedDisplayOnInvoice = [...data.bank_accounts.display_on_invoice];
                                                                updatedDisplayOnInvoice[index] = value;
                                                                setData("bank_accounts", {
                                                                    ...data.bank_accounts,
                                                                    display_on_invoice: updatedDisplayOnInvoice,
                                                                });
                                                            }}
                                                        >
                                                            <SelectTrigger id="display_on_invoice" className="w-full">
                                                                <SelectValue placeholder="Select" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="1">Yes</SelectItem>
                                                                <SelectItem value="0">No</SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </td>
                                                    <td className="border px-4 py-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                const bank_accounts = { ...data.bank_accounts };

                                                                // Remove item at specified index for each array
                                                                Object.keys(bank_accounts).forEach(key => {
                                                                    bank_accounts[key] = bank_accounts[key].filter((_, i) => i !== index);
                                                                });

                                                                setData("bank_accounts", bank_accounts);
                                                            }}
                                                            className="text-red-500 hover:text-red-700"
                                                        >
                                                            <TrashIcon className="h-5 w-5" />
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="mt-4 flex items-center"
                                        onClick={() => {
                                            const bank_accounts = { ...data.bank_accounts };

                                            // Add empty item to each array
                                            Object.keys(bank_accounts).forEach(key => {
                                                bank_accounts[key] = [...bank_accounts[key], key === 'display_on_invoice' ? "0" : ""];
                                            });

                                            setData("bank_accounts", bank_accounts);
                                        }}
                                    >
                                        <PlusIcon className="w-4 h-4 mr-2" />
                                        Add Bank Account
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-4">
                            <div className="md:col-span-2 col-span-12"></div>
                            <div className="md:col-span-10 col-span-12">
                                <Button type="submit" disabled={processing}>
                                    {processing ? "Creating..." : "Create Business"}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
