import { Head, Link, useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { cn } from "@/lib/utils";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { toast } from "sonner";
import InputError from "@/Components/InputError";
import { Textarea } from "@/Components/ui/textarea";

export default function General({ business, id, activeTab }) {
    const { data, setData, post, processing, errors } = useForm({
        // Bill Invoice Settings
        purchase_title: business?.system_settings?.find((setting) => setting.name === "purchase_title")?.value || "Bill Invoice",
        purchase_prefix: business?.system_settings?.find((setting) => setting.name === "purchase_prefix")?.value || "BILL",
        purchase_starting: business?.system_settings?.find((setting) => setting.name === "purchase_number")?.value || "1000",
        purchase_footer: business?.system_settings?.find((setting) => setting.name === "purchase_footer")?.value || "",
        purchase_columns: business?.system_settings?.find((setting) => setting.name === "purchase_column")?.value || [],
    });

    const submitBillInvoiceSettings = (e) => {
        e.preventDefault();
        post(route("business.settings.bill_invoice", id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Bill Invoice settings updated successfully");
            },
        });
    };

    // Define tabs for the settings pages
    const tabs = [
        { id: "general", label: "General Settings", icon: "⚙️", url: route("business.settings", [business.id, "general"]) },
        { id: "currency", label: "Currency", icon: "💰", url: route("business.settings", [business.id, "currency"]) },
        { id: "invoice", label: "Invoice", icon: "📄", url: route("business.settings", [business.id, "invoice"]) },
        { id: "cash_invoice", label: "Cash Invoice", icon: "💵", url: route("business.settings", [business.id, "cash_invoice"]) },
        { id: "bill_invoice", label: "Bill", icon: "📑", url: route("business.settings", [business.id, "bill_invoice"]) },
        { id: "sales_return", label: "Sales Return", icon: "🔄", url: route("business.settings", [business.id, "sales_return"]) },
        { id: "purchase_return", label: "Purchase Return", icon: "⬅️", url: route("business.settings", [business.id, "purchase_return"]) },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Business Settings" />
            <SidebarInset>
                <PageHeader
                    page="Business"
                    subpage="Settings"
                    url="business.index"
                />

                <div className="md:flex p-4">
                    {/* Left side - Tabs */}
                    <div className="mr-8">
                        {tabs.map((tab) => (
                            <Link
                                key={tab.id}
                                href={tab.url}
                                className={cn(
                                    "w-full text-left px-4 py-3 flex items-center rounded-md transition-colors mb-2",
                                    activeTab === tab.id
                                        ? "bg-gray-200 text-gray-700 font-medium"
                                        : "hover:bg-gray-100 text-gray-700 font-medium"
                                )}
                            >
                                <span className="mr-2">{tab.icon}</span>
                                <span className="text-sm md:text-base">{tab.label}</span>
                            </Link>
                        ))}
                    </div>

                    {/* Right side - Content */}
                    <div>
                        <div className="max-w-3xl mx-auto">
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
                                    <Label htmlFor="purchase_starting" className="col-span-3 flex items-center">
                                        Starting Number
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="purchase_starting"
                                            type="text"
                                            value={data.purchase_starting}
                                            onChange={(e) => setData("purchase_starting", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.purchase_starting} className="mt-1" />
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
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Name</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Name" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input
                                                                    type="radio"
                                                                    name="name_visibility"
                                                                    value="visible"
                                                                    className="h-4 w-4"
                                                                    defaultChecked
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input
                                                                    type="radio"
                                                                    name="name_visibility"
                                                                    value="hidden"
                                                                    className="h-4 w-4"
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Description</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Description" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input
                                                                    type="radio"
                                                                    name="description_visibility"
                                                                    value="visible"
                                                                    className="h-4 w-4"
                                                                    defaultChecked
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input
                                                                    type="radio"
                                                                    name="description_visibility"
                                                                    value="hidden"
                                                                    className="h-4 w-4"
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Quantity</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Quantity" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input
                                                                    type="radio"
                                                                    name="quantity_visibility"
                                                                    value="visible"
                                                                    className="h-4 w-4"
                                                                    defaultChecked
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input
                                                                    type="radio"
                                                                    name="quantity_visibility"
                                                                    value="hidden"
                                                                    className="h-4 w-4"
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Price</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Price" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input
                                                                    type="radio"
                                                                    name="price_visibility"
                                                                    value="visible"
                                                                    className="h-4 w-4"
                                                                    defaultChecked
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input
                                                                    type="radio"
                                                                    name="price_visibility"
                                                                    value="hidden"
                                                                    className="h-4 w-4"
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td className="px-4 py-2">Amount</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Amount" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input
                                                                    type="radio"
                                                                    name="amount_visibility"
                                                                    value="visible"
                                                                    className="h-4 w-4"
                                                                    defaultChecked
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input
                                                                    type="radio"
                                                                    name="amount_visibility"
                                                                    value="hidden"
                                                                    className="h-4 w-4"
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
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
