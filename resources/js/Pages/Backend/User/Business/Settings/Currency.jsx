import React from "react";
import { Head, useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { cn } from "@/lib/utils";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { toast } from "sonner";
import InputError from "@/Components/InputError";
import { Link } from "@inertiajs/react";

export default function Currency({ business, id, activeTab }) {
    const { data, setData, post, processing, errors } = useForm({
        // Currency Settings
        thousand_separator: business?.system_settings?.find((setting) => setting.name === "thousand_separator")?.value || ",",
        decimal_separator: business?.system_settings?.find((setting) => setting.name === "decimal_separator")?.value || ".",
        decimal_places: business?.system_settings?.find((setting) => setting.name === "decimal_places")?.value || "2",
        currency_position: business?.system_settings?.find((setting) => setting.name === "currency_position")?.value || "left",
    });

    const submitCurrencySettings = (e) => {
        e.preventDefault();
        post(route("business.store_currency_settings", id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Currency settings updated successfully");
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
        { id: "pos_settings", label: "POS Settings", icon: "⬅️", url: route("business.settings", [business.id, "pos_settings"]) },
        { id: "payroll", label: "Payroll", icon: "💰", url: route("business.settings", [business.id, "payroll"]) },
    ];

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
                            <form onSubmit={submitCurrencySettings}>
                                <h2 className="text-xl font-semibold mb-6">Currency Preferences</h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="currency_position" className="col-span-3 flex items-center">
                                        Currency Position
                                    </Label>
                                    <div className="col-span-9">
                                        <Select
                                            value={data.currency_position}
                                            onValueChange={(value) => setData("currency_position", value)}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select currency position" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="left">Left</SelectItem>
                                                <SelectItem value="right">Right</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.currency_position} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="thousand_separator" className="col-span-3 flex items-center">
                                        Thousand Separator
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            type="text"
                                            value={data.thousand_separator}
                                            onChange={(e) => setData("thousand_separator", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.thousand_separator} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="decimal_separator" className="col-span-3 flex items-center">
                                        Decimal Separator
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            type="text"
                                            value={data.decimal_separator}
                                            onChange={(e) => setData("decimal_separator", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.decimal_separator} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="decimal_places" className="col-span-3 flex items-center">
                                        Decimal Places
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            type="text"
                                            value={data.decimal_places}
                                            onChange={(e) => setData("decimal_places", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.decimal_places} className="mt-1" />
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
