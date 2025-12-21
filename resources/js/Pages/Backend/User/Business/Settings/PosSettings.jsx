import React from "react";
import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import { toast } from "sonner";
import InputError from "@/Components/InputError";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Switch } from "@/Components/ui/switch";
import SettingsNavigation from "./Components/SettingsNavigation";

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
