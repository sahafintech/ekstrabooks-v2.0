import React from "react";
import { Head, useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { cn } from "@/lib/utils";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import { toast } from "sonner";
import InputError from "@/Components/InputError";
import { Link } from "@inertiajs/react";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Switch } from "@/Components/ui/switch";

export default function PosSettings({ business, id, activeTab, taxes, currencies }) {
    const { data, setData, post, processing, errors } = useForm({
        // Currency Settings
        pos_default_taxes: business?.system_settings?.find((setting) => setting.name === "pos_default_taxes")?.value || "",
        pos_default_currency_change: business?.system_settings?.find((setting) => setting.name === "pos_default_currency_change")?.value || [],
        pos_product_image: business?.system_settings?.find((setting) => setting.name === "pos_product_image")?.value || 0,
    });

    const submitCurrencySettings = (e) => {
        e.preventDefault();
        post(route("business.store_pos_settings", id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("POS settings updated successfully");
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
                                <h2 className="text-xl font-semibold mb-6">POS Settings</h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="pos_default_taxes" className="col-span-3 flex items-center">
                                        Default Taxes
                                    </Label>
                                    <div className="col-span-9 ml-4">
                                        <SearchableMultiSelectCombobox
                                            options={taxes?.map(tax => ({
                                                id: tax.id,
                                                name: `${tax.name} (${tax.rate}%)`
                                            }))}
                                            value={data.pos_default_taxes}
                                            onChange={(values) => setData("pos_default_taxes", values)}
                                            placeholder="Select taxes"
                                        />
                                        <InputError message={errors.pos_default_taxes} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="pos_default_currency_change" className="col-span-3 flex items-center">
                                        Default Currency Change
                                    </Label>
                                    <div className="col-span-9 ml-4">
                                        <SearchableCombobox
                                            options={currencies?.map(currency => ({
                                                id: currency.name,
                                                value: currency.name,
                                                label: currency.name,
                                                name: `${currency.name} - ${currency.description} (${currency.exchange_rate})`
                                            }))}
                                            value={data.pos_default_currency_change}
                                            onChange={(values) => setData("pos_default_currency_change", values)}
                                            placeholder="Select currency"
                                        />
                                        <InputError message={errors.pos_default_currency_change} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12">
                                    <Label htmlFor="pos_product_image" className="col-span-3 flex items-center">
                                        POS Product Image
                                    </Label>
                                    <div className="col-span-9 ml-4">
                                        <Switch
                                            checked={data.pos_product_image == 1}
                                            onCheckedChange={(checked) => setData("pos_product_image", checked ? 1 : 0)}
                                        />

                                        <InputError message={errors.pos_product_image} className="mt-1" />
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
