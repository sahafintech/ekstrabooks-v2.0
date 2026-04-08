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
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import SettingsNavigation from "./Components/SettingsNavigation";

export default function Checkers({ business, id, users = [], activeTab }) {
    const getSettingValue = (names, defaultValue = "") => {
        const keys = Array.isArray(names) ? names : [names];

        for (const name of keys) {
            const value = business?.system_settings?.find((setting) => setting.name === name)?.value;
            if (value !== undefined && value !== null && value !== "") {
                return value;
            }
        }

        return defaultValue;
    };

    const getArraySettingValue = (names) => {
        const value = getSettingValue(names, "[]");
        try {
            return JSON.parse(value);
        } catch {
            return [];
        }
    };

    const { data, setData, post, processing, errors } = useForm({
        purchase_checker_required_count: getSettingValue("purchase_checker_required_count", "1"),
        purchase_checker_users: getArraySettingValue("purchase_checker_users"),

        hospital_purchase_checker_required_count: getSettingValue(
            ["hospital_purchase_checker_required_count", "purchase_checker_required_count"],
            "1"
        ),
        hospital_purchase_checker_users: getArraySettingValue([
            "hospital_purchase_checker_users",
            "purchase_checker_users",
        ]),
    });

    const submitCheckerSettings = (e) => {
        e.preventDefault();
        post(route("business.store_checker_settings", id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Checker settings updated successfully");
            },
        });
    };

    const userOptions = users.map((user) => ({
        id: user.id.toString(),
        name: `${user.name} (${user.email})`,
    }));

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Business"
                    subpage="Settings"
                    url="business.index"
                />

                <div className="md:flex p-4">
                    <SettingsNavigation activeTab={activeTab} businessId={business.id} />

                    <div className="flex-1">
                        <div className="max-w-4xl mx-auto">
                            <form onSubmit={submitCheckerSettings}>
                                <h2 className="text-xl font-semibold mb-6">Checker Workflows</h2>

                                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                        <span className="mr-2">✅</span>
                                        Purchases Checker Workflow
                                    </h3>
                                    <p className="text-sm text-gray-600 mb-4">
                                        Checker validation occurs after approval. Finance experts verify the accuracy of double-entry records.
                                    </p>

                                    <div className="grid grid-cols-12 mb-4">
                                        <Label htmlFor="purchase_checker_required_count" className="col-span-12 md:col-span-3 flex items-center mb-2 md:mb-0">
                                            Number of Checks Required
                                        </Label>
                                        <div className="col-span-12 md:col-span-9">
                                            <Input
                                                id="purchase_checker_required_count"
                                                type="number"
                                                min="0"
                                                max="10"
                                                value={data.purchase_checker_required_count}
                                                onChange={(e) => setData("purchase_checker_required_count", e.target.value)}
                                                className="w-full"
                                                disabled={processing}
                                            />
                                            <p className="text-xs text-gray-500 mt-1">
                                                Minimum number of verifications needed before a purchase is marked as verified
                                            </p>
                                            <InputError message={errors.purchase_checker_required_count} className="mt-1" />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-12 mb-4">
                                        <Label htmlFor="purchase_checker_users" className="col-span-12 md:col-span-3 flex items-center mb-2 md:mb-0">
                                            Checker Users
                                        </Label>
                                        <div className="col-span-12 md:col-span-9">
                                            <SearchableMultiSelectCombobox
                                                options={userOptions}
                                                value={data.purchase_checker_users}
                                                onChange={(values) => setData("purchase_checker_users", values)}
                                                placeholder="Select checker users"
                                                emptyMessage="No users found"
                                            />
                                            <p className="text-xs text-gray-500 mt-1">
                                                Finance experts who validate the accuracy of purchase records
                                            </p>
                                            <InputError message={errors.purchase_checker_users} className="mt-1" />
                                        </div>
                                    </div>
                                </div>

                                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                        <span className="mr-2">🏥</span>
                                        Hospital Purchases Checker Workflow
                                    </h3>
                                    <p className="text-sm text-gray-600 mb-4">
                                        Checker validation occurs after approval. Finance experts verify the accuracy of hospital purchase records.
                                    </p>

                                    <div className="grid grid-cols-12 mb-4">
                                        <Label htmlFor="hospital_purchase_checker_required_count" className="col-span-12 md:col-span-3 flex items-center mb-2 md:mb-0">
                                            Number of Checks Required
                                        </Label>
                                        <div className="col-span-12 md:col-span-9">
                                            <Input
                                                id="hospital_purchase_checker_required_count"
                                                type="number"
                                                min="0"
                                                max="10"
                                                value={data.hospital_purchase_checker_required_count}
                                                onChange={(e) => setData("hospital_purchase_checker_required_count", e.target.value)}
                                                className="w-full"
                                                disabled={processing}
                                            />
                                            <p className="text-xs text-gray-500 mt-1">
                                                Minimum number of verifications needed before a hospital purchase is marked as verified
                                            </p>
                                            <InputError message={errors.hospital_purchase_checker_required_count} className="mt-1" />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-12 mb-4">
                                        <Label htmlFor="hospital_purchase_checker_users" className="col-span-12 md:col-span-3 flex items-center mb-2 md:mb-0">
                                            Checker Users
                                        </Label>
                                        <div className="col-span-12 md:col-span-9">
                                            <SearchableMultiSelectCombobox
                                                options={userOptions}
                                                value={data.hospital_purchase_checker_users}
                                                onChange={(values) => setData("hospital_purchase_checker_users", values)}
                                                placeholder="Select checker users"
                                                emptyMessage="No users found"
                                            />
                                            <p className="text-xs text-gray-500 mt-1">
                                                Finance experts who validate the accuracy of hospital purchase records
                                            </p>
                                            <InputError message={errors.hospital_purchase_checker_users} className="mt-1" />
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
