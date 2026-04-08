import React from "react";
import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Textarea } from "@/Components/ui/textarea";
import InputError from "@/Components/InputError";
import SettingsNavigation from "./Components/SettingsNavigation";

const defaultHospitalPurchaseColumns = {
    name: { label: "Name", status: "1" },
    description: { label: "Description", status: "1" },
    quantity: { label: "Quantity", status: "1" },
    price: { label: "Price", status: "1" },
    amount: { label: "Amount", status: "1" },
};

export default function HospitalPurchase({
    business,
    id,
    activeTab,
    hospitalPurchaseColumn,
    purchaseColumn,
}) {
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

    const resolvedHospitalPurchaseColumns =
        hospitalPurchaseColumn ||
        purchaseColumn ||
        defaultHospitalPurchaseColumns;

    const { data, setData, post, processing, errors } = useForm({
        hospital_purchase_title: getSettingValue(["hospital_purchase_title", "purchase_title"], "Hospital Purchase"),
        hospital_purchase_number: getSettingValue(["hospital_purchase_number", "purchase_number"], "1000"),
        hospital_purchase_footer: getSettingValue(["hospital_purchase_footer", "purchase_footer"], ""),
        hospital_purchase_column: resolvedHospitalPurchaseColumns,
    });

    const submitHospitalPurchaseSettings = (e) => {
        e.preventDefault();
        post(route("business.store_hospital_purchase_settings", id), {
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
                    <SettingsNavigation activeTab={activeTab} businessId={business.id} />

                    <div className="flex-1">
                        <div className="max-w-4xl mx-auto">
                            <form onSubmit={submitHospitalPurchaseSettings}>
                                <h2 className="text-xl font-semibold mb-6">Hospital Purchase Settings</h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="hospital_purchase_title" className="col-span-3 flex items-center">
                                        Hospital Purchase Title
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="hospital_purchase_title"
                                            type="text"
                                            value={data.hospital_purchase_title}
                                            onChange={(e) => setData("hospital_purchase_title", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.hospital_purchase_title} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="hospital_purchase_number" className="col-span-3 flex items-center">
                                        Starting Number
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="hospital_purchase_number"
                                            type="text"
                                            value={data.hospital_purchase_number}
                                            onChange={(e) => setData("hospital_purchase_number", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.hospital_purchase_number} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="hospital_purchase_footer" className="col-span-3 flex items-center">
                                        Hospital Purchase Footer (HTML Allowed)
                                    </Label>
                                    <div className="col-span-9">
                                        <Textarea
                                            id="hospital_purchase_footer"
                                            value={data.hospital_purchase_footer}
                                            onChange={(e) => setData("hospital_purchase_footer", e.target.value)}
                                            className="w-full"
                                            rows={4}
                                        />
                                        <InputError message={errors.hospital_purchase_footer} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label className="col-span-3 flex items-center">
                                        Hospital Purchase Column Settings
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
                                                    {Object.entries(data.hospital_purchase_column).map(([key, column]) => (
                                                        <tr key={key} className="border-b">
                                                            <td className="px-4 py-2 capitalize">{key}</td>
                                                            <td className="px-4 py-2">
                                                                <Input
                                                                    className="w-full"
                                                                    value={column.label || ""}
                                                                    onChange={(e) =>
                                                                        setData("hospital_purchase_column", {
                                                                            ...data.hospital_purchase_column,
                                                                            [key]: {
                                                                                ...column,
                                                                                label: e.target.value,
                                                                            },
                                                                        })
                                                                    }
                                                                />
                                                            </td>
                                                            <td className="px-4 py-2 text-center">
                                                                <div className="flex justify-center">
                                                                    <input
                                                                        type="radio"
                                                                        name={`hospital_purchase_column[${key}][status]`}
                                                                        value="1"
                                                                        className="h-4 w-4"
                                                                        checked={column.status === "1"}
                                                                        onChange={(e) =>
                                                                            setData("hospital_purchase_column", {
                                                                                ...data.hospital_purchase_column,
                                                                                [key]: {
                                                                                    ...column,
                                                                                    status: e.target.value,
                                                                                },
                                                                            })
                                                                        }
                                                                    />
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-2 text-center">
                                                                <div className="flex justify-center">
                                                                    <input
                                                                        type="radio"
                                                                        name={`hospital_purchase_column[${key}][status]`}
                                                                        value="0"
                                                                        className="h-4 w-4"
                                                                        checked={column.status === "0"}
                                                                        onChange={(e) =>
                                                                            setData("hospital_purchase_column", {
                                                                                ...data.hospital_purchase_column,
                                                                                [key]: {
                                                                                    ...column,
                                                                                    status: e.target.value,
                                                                                },
                                                                            })
                                                                        }
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
