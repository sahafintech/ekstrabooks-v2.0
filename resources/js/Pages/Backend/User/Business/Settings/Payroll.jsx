import React, { useEffect } from "react";
import { Head, Link, useForm } from "@inertiajs/react";
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

export default function Payroll({ business, id, timezones = [], dateFormats = [], languages = [], financial_years = [], activeTab }) {    
    const { data, setData, post, processing, errors } = useForm({
        // General Settings
        overtime_rate_multiplier: business?.system_settings?.find((setting) => setting.name === "overtime_rate_multiplier")?.value || "1.5",
        public_holiday_rate_multiplier: business?.system_settings?.find((setting) => setting.name === "public_holiday_rate_multiplier")?.value || "2",
        weekly_holiday_rate_multiplier: business?.system_settings?.find((setting) => setting.name === "weekly_holiday_rate_multiplier")?.value || "1.5",
    });

    const submitGeneralSettings = (e) => {
        e.preventDefault();
        post(route("business.store_general_settings", id), {
            preserveScroll: true,
        });
    };

    const submitPayrollSettings = (e) => {
        e.preventDefault();
        post(route("business.store_payroll_settings", id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Payroll settings updated successfully");
            },
        });
    };

    // Calculate period_end_day by adding 5 days to period_start_day, handling month boundaries
    useEffect(() => {
        // Convert to number to ensure proper calculation
        const startDay = parseInt(data.period_start_day, 10);
        // Calculate end day - wrap around to next month if it exceeds the maximum day
        const endDay = ((startDay + 5 - 1) % 31) + 1;
        setData("period_end_day", endDay);
    }, [data.period_start_day]);
    
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
                        {/* General Settings */}
                        {activeTab === "general" && (
                            <form onSubmit={submitGeneralSettings}>
                                <h2 className="text-xl font-semibold mb-6">General Settings</h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="timezone" className="col-span-3 flex items-center">
                                        Timezone
                                    </Label>
                                    <div className="col-span-9">
                                        <Select
                                            value={data.timezone}
                                            onValueChange={(value) => setData("timezone", value)}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select timezone" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {timezones && timezones.length > 0 ? (
                                                    timezones.map(timezone => (
                                                        <SelectItem key={timezone.id} value={timezone.value}>
                                                            {timezone.name}
                                                        </SelectItem>
                                                    ))
                                                ) : (
                                                    <SelectItem value="default">No timezones available</SelectItem>
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.timezone} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="language" className="col-span-3 flex items-center">
                                        Language
                                    </Label>
                                    <div className="col-span-9">
                                        <Select
                                            value={data.language}
                                            onValueChange={(value) => setData("language", value)}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select language" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {languages.map((language) => (
                                                    <SelectItem key={language.code} value={language.code}>
                                                        {language.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.language} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="date_format" className="col-span-3 flex items-center">
                                        Date Format
                                    </Label>
                                    <div className="col-span-9">
                                        <Select
                                            value={data.date_format}
                                            onValueChange={(value) => setData("date_format", value)}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select date format" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {dateFormats && dateFormats.length > 0 ? (
                                                    dateFormats.map(format => (
                                                        <SelectItem key={format.format} value={format.format}>
                                                            {format.example}
                                                        </SelectItem>
                                                    ))
                                                ) : (
                                                    <SelectItem value="default">No date formats available</SelectItem>
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.date_format} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="time_format" className="col-span-3 flex items-center">
                                        Time Format
                                    </Label>
                                    <div className="col-span-9">
                                        <Select
                                            value={data.time_format}
                                            onValueChange={(value) => setData("time_format", value)}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select time format" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="24">24 Hours</SelectItem>
                                                <SelectItem value="12">12 Hours</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.time_format} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="financial_year" className="col-span-3 flex items-center">
                                        Financial Year
                                    </Label>
                                    <div className="col-span-9">
                                        <Select
                                            value={data.financial_year}
                                            onValueChange={(value) => setData("financial_year", value)}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select financial year" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {financial_years.map(year => (
                                                    <SelectItem key={year.id} value={year.id}>
                                                        {year.value}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.financial_year} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="period_start_day" className="col-span-3 flex items-center">
                                        Period Start Day
                                    </Label>
                                    <div className="col-span-9">
                                        <Select
                                            value={data.period_start_day}
                                            onValueChange={(value) => setData("period_start_day", value)}
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select period start day" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {Array.from({ length: 31 }, (_, i) => i + 1).map(day => (
                                                    <SelectItem key={day} value={day.toString()}>{day}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.period_start_day} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="period_end_day" className="col-span-3 flex items-center">
                                        Period End Day (Next Month)
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="period_end_day"
                                            type="text"
                                            value={data.period_end_day}
                                            onChange={(e) => setData("period_end_day", e.target.value)}
                                            className="w-full"
                                            disabled
                                        />
                                        <InputError message={errors.period_end_day} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="journal_number" className="col-span-3 flex items-center">
                                        Journal Number
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="journal_number"
                                            type="text"
                                            value={data.journal_number}
                                            onChange={(e) => setData("journal_number", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.journal_number} className="mt-1" />
                                    </div>
                                </div>

                                <div className="flex justify-start mt-6">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? "Saving..." : "Save Changes"}
                                    </Button>
                                </div>
                            </form>
                        )}

                        {/* Payroll Settings */}
                        {activeTab === "payroll" && (
                            <form onSubmit={submitPayrollSettings}>
                                <h2 className="text-xl font-semibold mb-6">Payroll Settings</h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="overtime_rate_multiplier" className="col-span-3 flex items-center">
                                        Overtime Rate Multiplier
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="overtime_rate_multiplier"
                                            type="number"
                                            step="0.1"
                                            value={data.overtime_rate_multiplier}
                                            onChange={(e) => setData("overtime_rate_multiplier", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.overtime_rate_multiplier} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="public_holiday_rate_multiplier" className="col-span-3 flex items-center">
                                        Public Holiday Rate Multiplier
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="public_holiday_rate_multiplier"
                                            type="number"
                                            step="0.1"
                                            value={data.public_holiday_rate_multiplier}
                                            onChange={(e) => setData("public_holiday_rate_multiplier", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.public_holiday_rate_multiplier} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="weekly_holiday_rate_multiplier" className="col-span-3 flex items-center">
                                        Weekly Holiday Rate Multiplier
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="weekly_holiday_rate_multiplier"
                                            type="number"
                                            step="0.1"
                                            value={data.weekly_holiday_rate_multiplier}
                                            onChange={(e) => setData("weekly_holiday_rate_multiplier", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.weekly_holiday_rate_multiplier} className="mt-1" />
                                    </div>
                                </div>

                                <div className="flex justify-start mt-6">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? "Saving..." : "Save Changes"}
                                    </Button>
                                </div>
                            </form>
                        )}
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
