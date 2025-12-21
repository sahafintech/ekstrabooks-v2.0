import React, { useEffect } from "react";
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

export default function General({ business, id, timezones = [], dateFormats = [], languages = [], financial_years = [], activeTab }) {    
    const { data, setData, post, processing, errors } = useForm({
        // General Settings
        timezone: business?.system_settings?.find((setting) => setting.name === "timezone")?.value || "Africa/Mogadishu",
        language: business?.system_settings?.find((setting) => setting.name === "language")?.value || "English---us",
        date_format: business?.system_settings?.find((setting) => setting.name === "date_format")?.value || "Y-m-d",
        time_format: business?.system_settings?.find((setting) => setting.name === "time_format")?.value || "24",
        financial_year: business?.system_settings?.find((setting) => setting.name === "financial_year")?.value || "January,December",
        period_start_day: business?.system_settings?.find((setting) => setting.name === "period_start_day")?.value || "1",
        period_end_day: business?.system_settings?.find((setting) => setting.name === "period_end_day")?.value || "31",
        journal_number: business?.system_settings?.find((setting) => setting.name === "journal_number")?.value || "100001",
    });

    const submitGeneralSettings = (e) => {
        e.preventDefault();
        post(route("business.store_general_settings", id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("General settings updated successfully");
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
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
