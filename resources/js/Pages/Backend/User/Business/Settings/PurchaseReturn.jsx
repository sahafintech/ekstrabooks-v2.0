import React, { useEffect } from "react";
import { useForm, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import InputError from "@/Components/InputError";
import { Textarea } from "@/Components/ui/textarea";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import SettingsNavigation from "./Components/SettingsNavigation";

export default function PurchaseReturn({
    business,
    id,
    activeTab,
    purchaseReturnColumn,
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const { data, setData, post, processing, errors } = useForm({
        // Purchase Return Settings
        purchase_return_title:
            business?.system_settings?.find(
                (setting) => setting.name === "purchase_return_title"
            )?.value || "Purchase Return",
        purchase_return_prefix:
            business?.system_settings?.find(
                (setting) => setting.name === "purchase_return_prefix"
            )?.value || "PR",
        purchase_return_number:
            business?.system_settings?.find(
                (setting) => setting.name === "purchase_return_number"
            )?.value || "1000",
        purchase_return_footer:
            business?.system_settings?.find(
                (setting) => setting.name === "purchase_return_footer"
            )?.value || "",
        "purchase_return-column": purchaseReturnColumn || {
            name: { label: "Name", status: "1" },
            description: { label: "Description", status: "1" },
            quantity: { label: "Quantity", status: "1" },
            price: { label: "Price", status: "1" },
            amount: { label: "Amount", status: "1" },
        },
    });

    const submitPurchaseReturnSettings = (e) => {
        e.preventDefault();
        post(route("business.store_purchase_return_settings", id), {
            preserveScroll: true,
        });
    };

    useEffect(() => {
        if (flash && flash.success) {
            toast({
                title: "Success",
                description: flash.success,
            });
        }

        if (flash && flash.error) {
            toast({
                variant: "destructive",
                title: "Error",
                description: flash.error,
            });
        }
    }, [flash, toast]);

    return (
        <AuthenticatedLayout>
            <Toaster />
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
                            <form onSubmit={submitPurchaseReturnSettings}>
                                <h2 className="text-xl font-semibold mb-6">
                                    Purchase Return Settings
                                </h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label
                                        htmlFor="purchase_return_title"
                                        className="col-span-3 flex items-center"
                                    >
                                        Purchase Return Title
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="purchase_return_title"
                                            type="text"
                                            value={data.purchase_return_title}
                                            onChange={(e) =>
                                                setData(
                                                    "purchase_return_title",
                                                    e.target.value
                                                )
                                            }
                                            className="w-full"
                                        />
                                        <InputError
                                            message={
                                                errors.purchase_return_title
                                            }
                                            className="mt-1"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label
                                        htmlFor="purchase_return_prefix"
                                        className="col-span-3 flex items-center"
                                    >
                                        Purchase Return Auto Increment
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="purchase_return_prefix"
                                            type="text"
                                            value={data.purchase_return_prefix}
                                            onChange={(e) =>
                                                setData(
                                                    "purchase_return_prefix",
                                                    e.target.value
                                                )
                                            }
                                            className="w-full"
                                        />
                                        <InputError
                                            message={
                                                errors.purchase_return_prefix
                                            }
                                            className="mt-1"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label
                                        htmlFor="purchase_return_number"
                                        className="col-span-3 flex items-center"
                                    >
                                        Starting Number
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="purchase_return_number"
                                            type="text"
                                            value={data.purchase_return_number}
                                            onChange={(e) =>
                                                setData(
                                                    "purchase_return_number",
                                                    e.target.value
                                                )
                                            }
                                            className="w-full"
                                        />
                                        <InputError
                                            message={
                                                errors.purchase_return_number
                                            }
                                            className="mt-1"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label
                                        htmlFor="purchase_return_footer"
                                        className="col-span-3 flex items-center"
                                    >
                                        Purchase Return Footer (HTML Allowed)
                                    </Label>
                                    <div className="col-span-9">
                                        <Textarea
                                            id="purchase_return_footer"
                                            value={data.purchase_return_footer}
                                            onChange={(e) =>
                                                setData(
                                                    "purchase_return_footer",
                                                    e.target.value
                                                )
                                            }
                                            className="w-full"
                                            rows={4}
                                        />
                                        <InputError
                                            message={
                                                errors.purchase_return_footer
                                            }
                                            className="mt-1"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label className="col-span-3 flex items-center">
                                        Purchase Return Column Settings
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
                                                        data[
                                                            "purchase_return-column"
                                                        ]
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
                                                                            "purchase_return-column",
                                                                            {
                                                                                ...data[
                                                                                    "purchase_return-column"
                                                                                ],
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
                                                                        name={`purchase_return-column[${key}][status]`}
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
                                                                                "purchase_return-column",
                                                                                {
                                                                                    ...data[
                                                                                        "purchase_return-column"
                                                                                    ],
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
                                                                        name={`purchase_return-column[${key}][status]`}
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
                                                                                "purchase_return-column",
                                                                                {
                                                                                    ...data[
                                                                                        "purchase_return-column"
                                                                                    ],
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
