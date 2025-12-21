import React from "react";
import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { toast } from "sonner";
import InputError from "@/Components/InputError";
import SettingsNavigation from "./Components/SettingsNavigation";

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
