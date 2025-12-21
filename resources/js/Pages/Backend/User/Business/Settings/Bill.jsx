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

export default function Bill({ business, id, activeTab, purchaseColumn }) {
    const { data, setData, post, processing, errors } = useForm({
        // Bill Invoice Settings
        purchase_title: business?.system_settings?.find((setting) => setting.name === "purchase_title")?.value || "Bill Invoice",
        purchase_prefix: business?.system_settings?.find((setting) => setting.name === "purchase_prefix")?.value || "BILL",
        purchase_number: business?.system_settings?.find((setting) => setting.name === "purchase_number")?.value || "1000",
        purchase_footer: business?.system_settings?.find((setting) => setting.name === "purchase_footer")?.value || "",
        purchase_column: purchaseColumn || {
            name: { label: "Name", status: "1" },
            description: { label: "Description", status: "1" },
            quantity: { label: "Quantity", status: "1" },
            price: { label: "Price", status: "1" },
            amount: { label: "Amount", status: "1" },
        },
    });

    const submitBillInvoiceSettings = (e) => {
        e.preventDefault();
        post(route("business.store_purchase_settings", id), {
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
                            <form onSubmit={submitBillInvoiceSettings}>
                                <h2 className="text-xl font-semibold mb-6">Bill Invoice Settings</h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="purchase_title" className="col-span-3 flex items-center">
                                        Bill Invoice Title
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="purchase_title"
                                            type="text"
                                            value={data.purchase_title}
                                            onChange={(e) => setData("purchase_title", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.purchase_title} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="purchase_prefix" className="col-span-3 flex items-center">
                                        Bill Invoice Auto Increment
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="purchase_prefix"
                                            type="text"
                                            value={data.purchase_prefix}
                                            onChange={(e) => setData("purchase_prefix", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.purchase_prefix} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="purchase_number" className="col-span-3 flex items-center">
                                        Starting Number
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="purchase_number"
                                            type="text"
                                            value={data.purchase_number}
                                            onChange={(e) => setData("purchase_number", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.purchase_number} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="purchase_footer" className="col-span-3 flex items-center">
                                        Bill Invoice Footer (HTML Allowed)
                                    </Label>
                                    <div className="col-span-9">
                                        <Textarea
                                            id="purchase_footer"
                                            value={data.invoice_footer}
                                            onChange={(e) => setData("invoice_footer", e.target.value)}
                                            className="w-full"
                                            rows={4}
                                        />
                                        <InputError message={errors.invoice_footer} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label className="col-span-3 flex items-center">
                                        Bill Invoice Column Settings
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
                                                    {Object.entries(data.purchase_column).map(([key, column]) => (
                                                        <tr key={key} className="border-b">
                                                            <td className="px-4 py-2 capitalize">{key}</td>
                                                            <td className="px-4 py-2">
                                                                <Input
                                                                    className="w-full"
                                                                    value={column.label || ""}
                                                                    onChange={(e) => setData("purchase_column", {
                                                                        ...data.purchase_column,
                                                                        [key]: { ...column, label: e.target.value }
                                                                    })}
                                                                />
                                                            </td>
                                                            <td className="px-4 py-2 text-center">
                                                                <div className="flex justify-center">
                                                                    <input
                                                                        type="radio"
                                                                        name={`purchase_column[${key}][status]`}
                                                                        value="1"
                                                                        className="h-4 w-4"
                                                                        checked={column.status === "1"}
                                                                        onChange={(e) => setData("purchase_column", {
                                                                            ...data.purchase_column,
                                                                            [key]: { ...column, status: e.target.value }
                                                                        })}
                                                                    />
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-2 text-center">
                                                                <div className="flex justify-center">
                                                                    <input
                                                                        type="radio"
                                                                        name={`purchase_column[${key}][status]`}
                                                                        value="0"
                                                                        className="h-4 w-4"
                                                                        checked={column.status === "0"}
                                                                        onChange={(e) => setData("purchase_column", {
                                                                            ...data.purchase_column,
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
                                        {processing ? "Saving..." : "Save Settings"}
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
