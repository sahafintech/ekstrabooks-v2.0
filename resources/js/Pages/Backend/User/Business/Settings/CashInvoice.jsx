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

export default function CashInvoice({
    business,
    id,
    activeTab,
    receiptColumn,
}) {
    const { data, setData, post, processing, errors } = useForm({
        // Cash Invoice Settings
        receipt_title:
            business?.system_settings?.find(
                (setting) => setting.name === "receipt_title"
            )?.value || "Cash Invoice",
        receipt_prefix:
            business?.system_settings?.find(
                (setting) => setting.name === "receipt_prefix"
            )?.value || "CINV",
        receipt_number:
            business?.system_settings?.find(
                (setting) => setting.name === "receipt_number"
            )?.value || "1000",
        receipt_footer:
            business?.system_settings?.find(
                (setting) => setting.name === "receipt_footer"
            )?.value || "",
        receipt_column: receiptColumn || {
            name: { label: "Name", status: "1" },
            description: { label: "Description", status: "1" },
            quantity: { label: "Quantity", status: "1" },
            price: { label: "Price", status: "1" },
            amount: { label: "Amount", status: "1" },
        },
    });

    const submitCashInvoiceSettings = (e) => {
        e.preventDefault();

        post(route("business.store_receipt_settings", id), {
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
                            <form onSubmit={submitCashInvoiceSettings}>
                                <h2 className="text-xl font-semibold mb-6">
                                    Cash Invoice Settings
                                </h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label
                                        htmlFor="receipt_title"
                                        className="col-span-3 flex items-center"
                                    >
                                        Cash Invoice Title
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="receipt_title"
                                            type="text"
                                            value={data.receipt_title}
                                            onChange={(e) =>
                                                setData(
                                                    "receipt_title",
                                                    e.target.value
                                                )
                                            }
                                            className="w-full"
                                        />
                                        <InputError
                                            message={errors.receipt_title}
                                            className="mt-1"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label
                                        htmlFor="receipt_prefix"
                                        className="col-span-3 flex items-center"
                                    >
                                        Cash Invoice Auto Increment
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="receipt_prefix"
                                            type="text"
                                            value={data.receipt_prefix}
                                            onChange={(e) =>
                                                setData(
                                                    "receipt_prefix",
                                                    e.target.value
                                                )
                                            }
                                            className="w-full"
                                        />
                                        <InputError
                                            message={errors.receipt_prefix}
                                            className="mt-1"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label
                                        htmlFor="receipt_number"
                                        className="col-span-3 flex items-center"
                                    >
                                        Starting Number
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="receipt_number"
                                            type="text"
                                            value={data.receipt_number}
                                            onChange={(e) =>
                                                setData(
                                                    "receipt_number",
                                                    e.target.value
                                                )
                                            }
                                            className="w-full"
                                        />
                                        <InputError
                                            message={errors.receipt_number}
                                            className="mt-1"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label
                                        htmlFor="receipt_footer"
                                        className="col-span-3 flex items-center"
                                    >
                                        Cash Invoice Footer (HTML Allowed)
                                    </Label>
                                    <div className="col-span-9">
                                        <Textarea
                                            id="receipt_footer"
                                            value={data.receipt_footer}
                                            onChange={(e) =>
                                                setData(
                                                    "receipt_footer",
                                                    e.target.value
                                                )
                                            }
                                            className="w-full"
                                            rows={4}
                                        />
                                        <InputError
                                            message={errors.receipt_footer}
                                            className="mt-1"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label className="col-span-3 flex items-center">
                                        Cash Invoice Column Settings
                                    </Label>
                                    <div className="col-span-9">
                                        <div className="border rounded-md">
                                            <table className="w-full">
                                                <thead>
                                                    <tr className="border-b">
                                                        <th className="text-left px-4 py-2 w-1/3">
                                                            COLUMN NAME
                                                        </th>
                                                        <th className="text-left px-4 py-2 w-1/3">
                                                            LABEL
                                                        </th>
                                                        <th className="text-center px-4 py-2 w-1/6">
                                                            VISIBLE
                                                        </th>
                                                        <th className="text-center px-4 py-2 w-1/6">
                                                            HIDDEN
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {Object.entries(
                                                        data.receipt_column
                                                    ).map(([key, column]) => (
                                                        <tr
                                                            key={key}
                                                            className="border-b"
                                                        >
                                                            <td className="px-4 py-2 capitalize">
                                                                {key}
                                                            </td>
                                                            <td className="px-4 py-2">
                                                                <Input
                                                                    className="w-full"
                                                                    value={
                                                                        column.label ||
                                                                        ""
                                                                    }
                                                                    onChange={(
                                                                        e
                                                                    ) =>
                                                                        setData(
                                                                            "receipt_column",
                                                                            {
                                                                                ...data.receipt_column,
                                                                                [key]: {
                                                                                    ...column,
                                                                                    label: e
                                                                                        .target
                                                                                        .value,
                                                                                },
                                                                            }
                                                                        )
                                                                    }
                                                                />
                                                            </td>
                                                            <td className="px-4 py-2 text-center">
                                                                <div className="flex justify-center">
                                                                    <input
                                                                        type="radio"
                                                                        name={`receipt_column[${key}][status]`}
                                                                        value="1"
                                                                        className="h-4 w-4"
                                                                        checked={
                                                                            column.status ===
                                                                            "1"
                                                                        }
                                                                        onChange={(
                                                                            e
                                                                        ) =>
                                                                            setData(
                                                                                "receipt_column",
                                                                                {
                                                                                    ...data.receipt_column,
                                                                                    [key]: {
                                                                                        ...column,
                                                                                        status: e
                                                                                            .target
                                                                                            .value,
                                                                                    },
                                                                                }
                                                                            )
                                                                        }
                                                                    />
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-2 text-center">
                                                                <div className="flex justify-center">
                                                                    <input
                                                                        type="radio"
                                                                        name={`receipt_column[${key}][status]`}
                                                                        value="0"
                                                                        className="h-4 w-4"
                                                                        checked={
                                                                            column.status ===
                                                                            "0"
                                                                        }
                                                                        onChange={(
                                                                            e
                                                                        ) =>
                                                                            setData(
                                                                                "receipt_column",
                                                                                {
                                                                                    ...data.receipt_column,
                                                                                    [key]: {
                                                                                        ...column,
                                                                                        status: e
                                                                                            .target
                                                                                            .value,
                                                                                    },
                                                                                }
                                                                            )
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
                                        {processing
                                            ? "Saving..."
                                            : "Save Settings"}
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
