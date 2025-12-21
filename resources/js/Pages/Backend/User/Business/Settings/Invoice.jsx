import React from "react";
import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { toast } from "sonner";
import InputError from "@/Components/InputError";
import { Textarea } from "@/Components/ui/textarea";
import SettingsNavigation from "./Components/SettingsNavigation";

export default function Invoice({ business, id, activeTab, invoiceColumn }) {
    const { data, setData, post, processing, errors } = useForm({
        // Credit Invoice Settings
        invoice_title: business?.system_settings?.find((setting) => setting.name === "invoice_title")?.value || "Credit Invoice",
        invoice_prefix: business?.system_settings?.find((setting) => setting.name === "invoice_prefix")?.value || "INV",
        invoice_number: business?.system_settings?.find((setting) => setting.name === "invoice_number")?.value || "1000",
        invoice_footer: business?.system_settings?.find((setting) => setting.name === "invoice_footer")?.value || "",
        invoice_primary_color: business?.system_settings?.find((setting) => setting.name === "invoice_primary_color")?.value || "#FFFFFF",
        invoice_text_color: business?.system_settings?.find((setting) => setting.name === "invoice_text_color")?.value || "#000000",
        invoice_column: invoiceColumn || {
            name: { label: "Name", status: "1" },
            description: { label: "Description", status: "1" },
            quantity: { label: "Quantity", status: "1" },
            price: { label: "Price", status: "1" },
            amount: { label: "Amount", status: "1" }
        },
    });

    const submitInvoiceSettings = (e) => {
        e.preventDefault();
        
        post(route("business.store_invoice_settings", id), {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Business"
                    subpage="Settings"
                    url="business.index"
                />

                <div className="md:flex p-4">
                    {/* Left side - Tabs */}
                    <SettingsNavigation activeTab={activeTab} businessId={business.id} />

                    {/* Right side - Content */}
                    <div className="flex-1">
                        <div className="max-w-4xl mx-auto">
                            <form onSubmit={submitInvoiceSettings}>
                                <h2 className="text-xl font-semibold mb-6">Credit Invoice Settings</h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="invoice_title" className="col-span-3 flex items-center">
                                        Invoice Title
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="invoice_title"
                                            type="text"
                                            value={data.invoice_title}
                                            onChange={(e) => setData("invoice_title", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.invoice_title} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="invoice_prefix" className="col-span-3 flex items-center">
                                        Invoice Auto Increment
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="invoice_prefix"
                                            type="text"
                                            value={data.invoice_prefix}
                                            onChange={(e) => setData("invoice_prefix", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.invoice_prefix} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="invoice_number" className="col-span-3 flex items-center">
                                        Starting Number
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="invoice_number"
                                            type="text"
                                            value={data.invoice_number}
                                            onChange={(e) => setData("invoice_number", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.invoice_number} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="invoice_footer" className="col-span-3 flex items-center">
                                        Invoice Footer (HTML Allowed)
                                    </Label>
                                    <div className="col-span-9">
                                        <Textarea
                                            id="invoice_footer"
                                            value={data.invoice_footer}
                                            onChange={(e) => setData("invoice_footer", e.target.value)}
                                            className="w-full"
                                            rows={4}
                                        />
                                        <InputError message={errors.invoice_footer} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="invoice_primary_color" className="col-span-3 flex items-center">
                                        Invoice Primary Color
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="invoice_primary_color"
                                            type="color"
                                            value={data.invoice_primary_color}
                                            onChange={(e) => setData("invoice_primary_color", e.target.value)}
                                            className="w-full h-10"
                                        />
                                        <InputError message={errors.invoice_primary_color} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="invoice_text_color" className="col-span-3 flex items-center">
                                        Invoice Text Color
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="invoice_text_color"
                                            type="color"
                                            value={data.invoice_text_color}
                                            onChange={(e) => setData("invoice_text_color", e.target.value)}
                                            className="w-full h-10"
                                        />
                                        <InputError message={errors.invoice_text_color} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label className="col-span-3 flex items-center">
                                        Invoice Column Settings
                                    </Label>
                                    <div className="col-span-9">
                                        <div className="border rounded-md">
                                            <table className="w-full">
                                                <thead>
                                                    <tr className="border-b">
                                                        <th className="text-left px-4 py-2 w-1/3">COLUMN NAME</th>
                                                        <th className="text-left px-4 py-2 w-1/3">LABEL</th>
                                                        <th className="text-center px-4 py-2 w-1/6">VISIBLE</th>
                                                        <th className="text-center px-4 py-2 w-1/6">HIDDEN</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {Object.entries(data.invoice_column).map(([key, column]) => (
                                                        <tr key={key} className="border-b">
                                                            <td className="px-4 py-2 capitalize">{key}</td>
                                                            <td className="px-4 py-2">
                                                                <Input
                                                                    className="w-full"
                                                                    value={column.label || ""}
                                                                    onChange={(e) => setData("invoice_column", {
                                                                        ...data.invoice_column,
                                                                        [key]: { ...column, label: e.target.value }
                                                                    })}
                                                                />
                                                            </td>
                                                            <td className="px-4 py-2 text-center">
                                                                <div className="flex justify-center">
                                                                    <input
                                                                        type="radio"
                                                                        name={`invoice_column[${key}][status]`}
                                                                        value="1"
                                                                        className="h-4 w-4"
                                                                        checked={column.status === "1"}
                                                                        onChange={(e) => setData("invoice_column", {
                                                                            ...data.invoice_column,
                                                                            [key]: { ...column, status: e.target.value }
                                                                        })}
                                                                    />
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-2 text-center">
                                                                <div className="flex justify-center">
                                                                    <input
                                                                        type="radio"
                                                                        name={`invoice_column[${key}][status]`}
                                                                        value="0"
                                                                        className="h-4 w-4"
                                                                        checked={column.status === "0"}
                                                                        onChange={(e) => setData("invoice_column", {
                                                                            ...data.invoice_column,
                                                                            [key]: { ...column, status: e.target.value }
                                                                        })}
                                                                    />
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex justify-start mt-6">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? "Saving..." : "Save Changes"}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
