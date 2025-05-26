import React from "react";
import { Head, Link } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { ArrowLeft, Edit, Printer } from "lucide-react";
import PageHeader from "@/Components/PageHeader";

const AdjustmentTypeBadge = ({ status }) => {
    const statusMap = {
        adds: {
            label: "Added",
            className: "text-green-600 bg-green-200 px-3 py-1 rounded text-sm",
        },
        deducts: {
            label: "Deducted",
            className: "text-red-600 bg-red-200 px-3 py-1 rounded text-sm",
        },
    };

    return (
        <span className={statusMap[status].className}>
            {statusMap[status].label}
        </span>
    );
};

export default function View({ adjustment }) {
    const handlePrint = () => {
        window.print();
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Inventory Adjustments"
                        subpage="View"
                        url="inventory_adjustments.index"
                    />
                    <div className="p-4">
                        <div className="flex justify-between items-center mb-6">
                            <Link href={route("inventory_adjustments.index")}>
                                <Button variant="outline" className="gap-2">
                                    <ArrowLeft className="h-4 w-4" />
                                    Back to List
                                </Button>
                            </Link>
                            <div className="flex gap-2">
                                <Link
                                    href={route(
                                        "inventory_adjustments.edit",
                                        adjustment.id
                                    )}
                                >
                                    <Button variant="outline" className="gap-2">
                                        <Edit className="h-4 w-4" />
                                        Edit
                                    </Button>
                                </Link>
                                <Button
                                    variant="outline"
                                    onClick={handlePrint}
                                    className="gap-2"
                                >
                                    <Printer className="h-4 w-4" />
                                    Print
                                </Button>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div className="w-full">
                                <div className="flex justify-between items-center mb-6">
                                    <h2 className="text-2xl font-bold">
                                        Adjustment Details
                                    </h2>
                                </div>
                                <div className="grid grid-cols-1 gap-6">
                                    <dl className="grid grid-cols-1 gap-4">
                                        <div className="flex justify-between py-2 border-b">
                                            <dt className="text-gray-500">
                                                Adjustment ID
                                            </dt>
                                            <dd className="font-medium">
                                                {adjustment.id}
                                            </dd>
                                        </div>
                                        <div className="flex justify-between py-2 border-b">
                                            <dt className="text-gray-500">
                                                Date
                                            </dt>
                                            <dd className="font-medium">
                                                {adjustment.adjustment_date}
                                            </dd>
                                        </div>
                                        <div className="flex justify-between py-2 border-b">
                                            <dt className="text-gray-500">
                                                Product
                                            </dt>
                                            <dd className="font-medium">
                                                {adjustment.product?.name}
                                            </dd>
                                        </div>
                                        <div className="flex justify-between py-2 border-b">
                                            <dt className="text-gray-500">
                                                Account
                                            </dt>
                                            <dd className="font-medium">
                                                {
                                                    adjustment.account
                                                        ?.account_name
                                                }
                                            </dd>
                                        </div>
                                        <div className="flex justify-between py-2 border-b">
                                            <dt className="text-gray-500">
                                                Adjustment Type
                                            </dt>
                                            <dd className="font-medium">
                                                <AdjustmentTypeBadge
                                                    status={
                                                        adjustment.adjustment_type
                                                    }
                                                />
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <div className="w-full">
                                <div className="flex justify-between items-center mb-6">
                                    <h2 className="text-2xl font-bold">
                                        Quantity Information
                                    </h2>
                                </div>
                                <div className="grid grid-cols-1 gap-6">
                                    <dl className="grid grid-cols-1 gap-4">
                                        <div className="flex justify-between py-2 border-b">
                                            <dt className="text-gray-500">
                                                Quantity on Hand
                                            </dt>
                                            <dd className="font-medium">
                                                {adjustment.quantity_on_hand}
                                            </dd>
                                        </div>
                                        <div className="flex justify-between py-2 border-b">
                                            <dt className="text-gray-500">
                                                Adjusted Quantity
                                            </dt>
                                            <dd className="font-medium">
                                                {adjustment.adjusted_quantity}
                                            </dd>
                                        </div>
                                        <div className="flex justify-between py-2 border-b">
                                            <dt className="text-gray-500">
                                                New Quantity
                                            </dt>
                                            <dd className="font-medium">
                                                {
                                                    adjustment.new_quantity_on_hand
                                                }
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        {adjustment.description && (
                            <div className="mt-6">
                                <h2 className="text-2xl font-bold">
                                    Description
                                </h2>
                                <p className="text-gray-700">
                                    {adjustment.description}
                                </p>
                            </div>
                        )}

                        <div className="mt-6">
                            <h2 className="text-2xl font-bold">
                                Additional Information
                            </h2>
                            <dl className="grid grid-cols-1 gap-4">
                                <div className="flex justify-between py-2 border-b">
                                    <dt className="text-gray-500">
                                        Created At
                                    </dt>
                                    <dd className="font-medium">
                                        {adjustment.created_at}
                                    </dd>
                                </div>
                                <div className="flex justify-between py-2 border-b">
                                    <dt className="text-gray-500">
                                        Last Updated
                                    </dt>
                                    <dd className="font-medium">
                                        {adjustment.updated_at}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
