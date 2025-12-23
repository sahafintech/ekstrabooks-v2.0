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

export default function Approvals({ business, id, users = [], activeTab }) {
    // Get existing settings values
    const getSettingValue = (name, defaultValue = "") => {
        return business?.system_settings?.find((setting) => setting.name === name)?.value || defaultValue;
    };

    // Parse JSON array settings
    const getArraySettingValue = (name) => {
        const value = getSettingValue(name, "[]");
        try {
            return JSON.parse(value);
        } catch {
            return [];
        }
    };

    const { data, setData, post, processing, errors } = useForm({
        // Purchases Approval Settings
        purchase_approval_required_count: getSettingValue("purchase_approval_required_count", "1"),
        purchase_approval_users: getArraySettingValue("purchase_approval_users"),
        
        // Payroll Approval Settings
        payroll_approval_required_count: getSettingValue("payroll_approval_required_count", "1"),
        payroll_approval_users: getArraySettingValue("payroll_approval_users"),

        // Journal Approval Settings
        journal_approval_required_count: getSettingValue("journal_approval_required_count", "1"),
        journal_approval_users: getArraySettingValue("journal_approval_users"),
    });

    const submitApprovalSettings = (e) => {
        e.preventDefault();
        post(route("business.store_approval_settings", id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Approval settings updated successfully");
            },
        });
    };

    // Transform users for the combobox
    const userOptions = users.map(user => ({
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
                    {/* Left side - Tabs */}
                    <SettingsNavigation activeTab={activeTab} businessId={business.id} />

                    {/* Right side - Content */}
                    <div className="flex-1">
                        <div className="max-w-4xl mx-auto">
                            <form onSubmit={submitApprovalSettings}>
                                <h2 className="text-xl font-semibold mb-6">Approval Workflows</h2>

                                {/* Purchases Workflow */}
                                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                        <span className="mr-2">🛒</span>
                                        Purchases Workflow
                                    </h3>
                                    
                                    <div className="grid grid-cols-12 mb-4">
                                        <Label htmlFor="purchase_approval_required_count" className="col-span-12 md:col-span-3 flex items-center mb-2 md:mb-0">
                                            Number of Approvals Required
                                        </Label>
                                        <div className="col-span-12 md:col-span-9">
                                            <Input
                                                id="purchase_approval_required_count"
                                                type="number"
                                                min="0"
                                                max="10"
                                                value={data.purchase_approval_required_count}
                                                onChange={(e) => setData("purchase_approval_required_count", e.target.value)}
                                                className="w-full"
                                                disabled={processing}
                                            />
                                            <p className="text-xs text-gray-500 mt-1">
                                                Minimum number of approvals needed before a purchase is approved
                                            </p>
                                            <InputError message={errors.purchase_approval_required_count} className="mt-1" />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-12 mb-4">
                                        <Label htmlFor="purchase_approval_users" className="col-span-12 md:col-span-3 flex items-center mb-2 md:mb-0">
                                            Approver Users
                                        </Label>
                                        <div className="col-span-12 md:col-span-9">
                                            <SearchableMultiSelectCombobox
                                                options={userOptions}
                                                value={data.purchase_approval_users}
                                                onChange={(values) => setData("purchase_approval_users", values)}
                                                placeholder="Select approver users"
                                                emptyMessage="No users found"
                                            />
                                            <p className="text-xs text-gray-500 mt-1">
                                                Users who are permitted to approve purchases
                                            </p>
                                            <InputError message={errors.purchase_approval_users} className="mt-1" />
                                        </div>
                                    </div>
                                </div>

                                {/* Payroll Workflow */}
                                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                        <span className="mr-2">💵</span>
                                        Payroll Workflow
                                    </h3>
                                    
                                    <div className="grid grid-cols-12 mb-4">
                                        <Label htmlFor="payroll_approval_required_count" className="col-span-12 md:col-span-3 flex items-center mb-2 md:mb-0">
                                            Number of Approvals Required
                                        </Label>
                                        <div className="col-span-12 md:col-span-9">
                                            <Input
                                                id="payroll_approval_required_count"
                                                type="number"
                                                min="0"
                                                max="10"
                                                value={data.payroll_approval_required_count}
                                                onChange={(e) => setData("payroll_approval_required_count", e.target.value)}
                                                className="w-full"
                                                disabled={processing}
                                            />
                                            <p className="text-xs text-gray-500 mt-1">
                                                Minimum number of approvals needed before payroll is approved
                                            </p>
                                            <InputError message={errors.payroll_approval_required_count} className="mt-1" />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-12 mb-4">
                                        <Label htmlFor="payroll_approval_users" className="col-span-12 md:col-span-3 flex items-center mb-2 md:mb-0">
                                            Approver Users
                                        </Label>
                                        <div className="col-span-12 md:col-span-9">
                                            <SearchableMultiSelectCombobox
                                                options={userOptions}
                                                value={data.payroll_approval_users}
                                                onChange={(values) => setData("payroll_approval_users", values)}
                                                placeholder="Select approver users"
                                                emptyMessage="No users found"
                                            />
                                            <p className="text-xs text-gray-500 mt-1">
                                                Users who will be assigned to approve payroll
                                            </p>
                                            <InputError message={errors.payroll_approval_users} className="mt-1" />
                                        </div>
                                    </div>
                                </div>

                                {/* Journal Workflow */}
                                <div className="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                        <span className="mr-2">📒</span>
                                        Journal Workflow
                                    </h3>
                                    
                                    <div className="grid grid-cols-12 mb-4">
                                        <Label htmlFor="journal_approval_required_count" className="col-span-12 md:col-span-3 flex items-center mb-2 md:mb-0">
                                            Number of Approvals Required
                                        </Label>
                                        <div className="col-span-12 md:col-span-9">
                                            <Input
                                                id="journal_approval_required_count"
                                                type="number"
                                                min="0"
                                                max="10"
                                                value={data.journal_approval_required_count}
                                                onChange={(e) => setData("journal_approval_required_count", e.target.value)}
                                                className="w-full"
                                                disabled={processing}
                                            />
                                            <p className="text-xs text-gray-500 mt-1">
                                                Minimum number of approvals needed before a journal entry is approved
                                            </p>
                                            <InputError message={errors.journal_approval_required_count} className="mt-1" />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-12 mb-4">
                                        <Label htmlFor="journal_approval_users" className="col-span-12 md:col-span-3 flex items-center mb-2 md:mb-0">
                                            Approver Users
                                        </Label>
                                        <div className="col-span-12 md:col-span-9">
                                            <SearchableMultiSelectCombobox
                                                options={userOptions}
                                                value={data.journal_approval_users}
                                                onChange={(values) => setData("journal_approval_users", values)}
                                                placeholder="Select approver users"
                                                emptyMessage="No users found"
                                            />
                                            <p className="text-xs text-gray-500 mt-1">
                                                Users who are permitted to approve journal entries
                                            </p>
                                            <InputError message={errors.journal_approval_users} className="mt-1" />
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
