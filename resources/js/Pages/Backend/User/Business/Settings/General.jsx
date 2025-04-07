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
import { Textarea } from "@/Components/ui/textarea";

export default function General({ business, id, timezones = [], dateFormats = [], languages = [], financial_years = [], activeTab: initialTab = 'general' }) {    
    const [activeTab, setActiveTab] = React.useState(initialTab);    
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
        
        // Currency Settings
        thousand_separator: business?.system_settings?.find((setting) => setting.name === "thousand_separator")?.value || ",",
        decimal_separator: business?.system_settings?.find((setting) => setting.name === "decimal_separator")?.value || ".",
        decimal_places: business?.system_settings?.find((setting) => setting.name === "decimal_places")?.value || "2",
        currency_position: business?.system_settings?.find((setting) => setting.name === "currency_position")?.value || "left",
        
        // Credit Invoice Settings
        invoice_title: business?.system_settings?.find((setting) => setting.name === "invoice_title")?.value || "Credit Invoice",
        invoice_prefix: business?.system_settings?.find((setting) => setting.name === "invoice_prefix")?.value || "INV",
        invoice_starting: business?.system_settings?.find((setting) => setting.name === "invoice_number")?.value || "1000",
        invoice_footer: business?.system_settings?.find((setting) => setting.name === "invoice_footer")?.value || "",
        invoice_primary_color: business?.system_settings?.find((setting) => setting.name === "invoice_primary_color")?.value || "#FFFFFF",
        invoice_text_color: business?.system_settings?.find((setting) => setting.name === "invoice_text_color")?.value || "#000000",
        invoice_columns: business?.system_settings?.find((setting) => setting.name === "invoice_column")?.value || [],
        
        // Cash Invoice Settings
        receipt_title: business?.system_settings?.find((setting) => setting.name === "receipt_title")?.value || "Cash Invoice",
        receipt_prefix: business?.system_settings?.find((setting) => setting.name === "receipt_prefix")?.value || "CINV",
        receipt_number: business?.system_settings?.find((setting) => setting.name === "receipt_number")?.value || "1000",
        receipt_footer: business?.system_settings?.find((setting) => setting.name === "receipt_footer")?.value || "",
        receipt_columns: business?.system_settings?.find((setting) => setting.name === "receipt_column")?.value || [],
        
        // Bill Invoice Settings
        purchase_title: business?.system_settings?.find((setting) => setting.name === "purchase_title")?.value || "Bill Invoice",
        purchase_prefix: business?.system_settings?.find((setting) => setting.name === "purchase_prefix")?.value || "BILL",
        purchase_starting: business?.system_settings?.find((setting) => setting.name === "purchase_number")?.value || "1000",
        purchase_footer: business?.system_settings?.find((setting) => setting.name === "purchase_footer")?.value || "",
        purchase_columns: business?.system_settings?.find((setting) => setting.name === "purchase_column")?.value || [],
        
        // Sales Return Settings
        sales_return_title: business?.system_settings?.find((setting) => setting.name === "sales_return_title")?.value || "Sales Return",
        sales_return_prefix: business?.system_settings?.find((setting) => setting.name === "sales_return_prefix")?.value || "SR",
        sales_return_starting: business?.system_settings?.find((setting) => setting.name === "sales_return_number")?.value || "1000",
        sales_return_footer: business?.system_settings?.find((setting) => setting.name === "sales_return_footer")?.value || "",
        sales_return_columns: business?.system_settings?.find((setting) => setting.name === "sales_return_column")?.value || [],
        
        // Purchase Return Settings
        purchase_return_title: business?.system_settings?.find((setting) => setting.name === "purchase_return_title")?.value || "Purchase Return",
        purchase_return_prefix: business?.system_settings?.find((setting) => setting.name === "purchase_return_prefix")?.value || "PR",
        purchase_return_starting: business?.system_settings?.find((setting) => setting.name === "purchase_return_number")?.value || "1000",
        purchase_return_footer: business?.system_settings?.find((setting) => setting.name === "purchase_return_footer")?.value || "",
        purchase_return_columns: business?.system_settings?.find((setting) => setting.name === "purchase_return_column")?.value || []
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

    const submitCurrencySettings = (e) => {
        e.preventDefault();
        post(route("business.settings.currency", id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Currency settings updated successfully");
            },
        });
    };

    const submitInvoiceSettings = (e) => {
        e.preventDefault();
        post(route("business.settings.credit_invoice", id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Credit Invoice settings updated successfully");
            },
        });
    };
    
    const submitCashInvoiceSettings = (e) => {
        e.preventDefault();
        post(route("business.settings.cash_invoice", id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Cash Invoice settings updated successfully");
            },
        });
    };
    
    const submitBillInvoiceSettings = (e) => {
        e.preventDefault();
        post(route("business.settings.bill_invoice", id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Bill Invoice settings updated successfully");
            },
        });
    };
    
    const submitSalesReturnSettings = (e) => {
        e.preventDefault();
        post(route("business.settings.sales_return", id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Sales Return settings updated successfully");
            },
        });
    };
    
    const submitPurchaseReturnSettings = (e) => {
        e.preventDefault();
        post(route("business.settings.purchase_return", id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Purchase Return settings updated successfully");
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
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Business Settings" />
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

                        {/* Currency Settings */}
                        {activeTab === "currency" && (
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
                        )}

                        {/* Credit Invoice Settings */}
                        {activeTab === "invoice" && (
                            <form onSubmit={submitInvoiceSettings}>
                                <h2 className="text-xl font-semibold mb-6">Credit Invoice Settings</h2>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="invoice_title" className="col-span-3 flex items-center">
                                        Invoice Title
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="invoice_title"
                                            type="text"
                                            value={data.invoice_title}
                                            onChange={(e) => setData("invoice_title", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.invoice_title} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="invoice_prefix" className="col-span-3 flex items-center">
                                        Invoice Auto Increment
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="invoice_prefix"
                                            type="text"
                                            value={data.invoice_prefix}
                                            onChange={(e) => setData("invoice_prefix", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.invoice_prefix} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="invoice_starting" className="col-span-3 flex items-center">
                                        Starting Number
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="invoice_starting"
                                            type="text"
                                            value={data.invoice_starting}
                                            onChange={(e) => setData("invoice_starting", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.invoice_starting} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="invoice_footer" className="col-span-3 flex items-center">
                                        Invoice Footer (HTML Allowed)
                                    </Label>
                                    <div className="col-span-9">
                                        <Textarea
                                            id="invoice_footer"
                                            value={data.invoice_footer}
                                            onChange={(e) => setData("invoice_footer", e.target.value)}
                                            className="w-full"
                                            rows={4}
                                        />
                                        <InputError message={errors.invoice_footer} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="invoice_primary_color" className="col-span-3 flex items-center">
                                        Invoice Primary Color
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="invoice_primary_color"
                                            type="color"
                                            value={data.invoice_primary_color}
                                            onChange={(e) => setData("invoice_primary_color", e.target.value)}
                                            className="w-full h-10"
                                        />
                                        <InputError message={errors.invoice_primary_color} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="invoice_text_color" className="col-span-3 flex items-center">
                                        Invoice Text Color
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="invoice_text_color"
                                            type="color"
                                            value={data.invoice_text_color}
                                            onChange={(e) => setData("invoice_text_color", e.target.value)}
                                            className="w-full h-10"
                                        />
                                        <InputError message={errors.invoice_text_color} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label className="col-span-3 flex items-center">
                                        Invoice Column Settings
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
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Name</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Name" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="name_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="name_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Description</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Description" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="description_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="description_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Quantity</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Quantity" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="quantity_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="quantity_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Price</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Price" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="price_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="price_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td className="px-4 py-2">Amount</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Amount" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="amount_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="amount_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
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
                        )}

                        {/* Cash Invoice Settings */}
                        {activeTab === "cash_invoice" && (
                            <form onSubmit={submitCashInvoiceSettings}>
                                <h2 className="text-xl font-semibold mb-6">Cash Invoice Settings</h2>
                                
                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="receipt_title" className="col-span-3 flex items-center">
                                        Cash Invoice Title
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="receipt_title"
                                            type="text"
                                            value={data.receipt_title}
                                            onChange={(e) => setData("receipt_title", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.receipt_title} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="receipt_prefix" className="col-span-3 flex items-center">
                                        Cash Invoice Auto Increment
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="receipt_prefix"
                                            type="text"
                                            value={data.receipt_prefix}
                                            onChange={(e) => setData("receipt_prefix", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.receipt_prefix} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="receipt_number" className="col-span-3 flex items-center">
                                        Starting Number
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="receipt_number"
                                            type="text"
                                            value={data.receipt_number}
                                            onChange={(e) => setData("receipt_number", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.receipt_number} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="receipt_footer" className="col-span-3 flex items-center">
                                        Cash Invoice Footer (HTML Allowed)
                                    </Label>
                                    <div className="col-span-9">
                                        <Textarea
                                            id="receipt_footer"
                                            value={data.receipt_footer}
                                            onChange={(e) => setData("receipt_footer", e.target.value)}
                                            className="w-full"
                                            rows={4}
                                        />
                                        <InputError message={errors.receipt_footer} className="mt-1" />
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
                                                        <th className="text-left px-4 py-2 w-1/3">COLUMN NAME</th>
                                                        <th className="text-left px-4 py-2 w-1/3">LABEL</th>
                                                        <th className="text-center px-4 py-2 w-1/6">VISIBLE</th>
                                                        <th className="text-center px-4 py-2 w-1/6">HIDDEN</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Name</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Name" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="name_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="name_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Description</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Description" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="description_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="description_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Quantity</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Quantity" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="quantity_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="quantity_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Price</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Price" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="price_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="price_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td className="px-4 py-2">Amount</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Amount" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="amount_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="amount_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex justify-start mt-6">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? "Saving..." : "Save Settings"}
                                    </Button>
                                </div>
                            </form>
                        )}

                        {/* Bill Invoice Settings */}
                        {activeTab === "bill_invoice" && (
                            <form onSubmit={submitBillInvoiceSettings}>
                                <h2 className="text-xl font-semibold mb-6">Bill Invoice Settings</h2>
                                
                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="purchase_title" className="col-span-3 flex items-center">
                                        Bill Invoice Title
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="purchase_title"
                                            type="text"
                                            value={data.purchase_title}
                                            onChange={(e) => setData("purchase_title", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.purchase_title} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="purchase_prefix" className="col-span-3 flex items-center">
                                        Bill Invoice Auto Increment
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="purchase_prefix"
                                            type="text"
                                            value={data.purchase_prefix}
                                            onChange={(e) => setData("purchase_prefix", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.purchase_prefix} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="purchase_starting" className="col-span-3 flex items-center">
                                        Starting Number
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="purchase_starting"
                                            type="text"
                                            value={data.purchase_starting}
                                            onChange={(e) => setData("purchase_starting", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.purchase_starting} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="purchase_footer" className="col-span-3 flex items-center">
                                        Bill Invoice Footer (HTML Allowed)
                                    </Label>
                                    <div className="col-span-9">
                                        <Textarea
                                            id="purchase_footer"
                                            value={data.invoice_footer}
                                            onChange={(e) => setData("invoice_footer", e.target.value)}
                                            className="w-full"
                                            rows={4}
                                        />
                                        <InputError message={errors.invoice_footer} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label className="col-span-3 flex items-center">
                                        Bill Invoice Column Settings
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
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Name</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Name" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="name_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="name_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Description</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Description" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="description_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="description_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Quantity</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Quantity" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="quantity_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="quantity_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Price</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Price" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="price_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="price_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td className="px-4 py-2">Amount</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Amount" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="amount_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="amount_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex justify-start mt-6">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? "Saving..." : "Save Settings"}
                                    </Button>
                                </div>
                            </form>
                        )}

                        {/* Sales Return Settings */}
                        {activeTab === "sales_return" && (
                            <form onSubmit={submitSalesReturnSettings}>
                                <h2 className="text-xl font-semibold mb-6">Sales Return Settings</h2>
                                
                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="sales_return_title" className="col-span-3 flex items-center">
                                        Sales Return Title
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="sales_return_title"
                                            type="text"
                                            value={data.sales_return_title}
                                            onChange={(e) => setData("sales_return_title", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.sales_return_title} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="sales_return_prefix" className="col-span-3 flex items-center">
                                        Sales Return Auto Increment
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="sales_return_prefix"
                                            type="text"
                                            value={data.sales_return_prefix}
                                            onChange={(e) => setData("sales_return_prefix", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.sales_return_prefix} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="sales_return_starting" className="col-span-3 flex items-center">
                                        Starting Number
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="sales_return_starting"
                                            type="text"
                                            value={data.sales_return_starting}
                                            onChange={(e) => setData("sales_return_starting", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.sales_return_starting} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="sales_return_footer" className="col-span-3 flex items-center">
                                        Sales Return Footer (HTML Allowed)
                                    </Label>
                                    <div className="col-span-9">
                                        <Textarea
                                            id="sales_return_footer"
                                            value={data.sales_return_footer}
                                            onChange={(e) => setData("sales_return_footer", e.target.value)}
                                            className="w-full"
                                            rows={4}
                                        />
                                        <InputError message={errors.sales_return_footer} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label className="col-span-3 flex items-center">
                                        Sales Return Column Settings
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
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Name</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Name" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="name_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="name_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Description</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Description" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="description_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="description_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Quantity</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Quantity" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="quantity_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="quantity_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Price</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Price" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="price_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="price_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td className="px-4 py-2">Amount</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Amount" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="amount_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="amount_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex justify-start mt-6">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? "Saving..." : "Save Settings"}
                                    </Button>
                                </div>
                            </form>
                        )}

                        {/* Purchase Return Settings */}
                        {activeTab === "purchase_return" && (
                            <form onSubmit={submitPurchaseReturnSettings}>
                                <h2 className="text-xl font-semibold mb-6">Purchase Return Settings</h2>
                                
                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="purchase_return_title" className="col-span-3 flex items-center">
                                        Purchase Return Title
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="purchase_return_title"
                                            type="text"
                                            value={data.purchase_return_title}
                                            onChange={(e) => setData("purchase_return_title", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.purchase_return_title} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="purchase_return_prefix" className="col-span-3 flex items-center">
                                        Purchase Return Auto Increment
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="purchase_return_prefix"
                                            type="text"
                                            value={data.purchase_return_prefix}
                                            onChange={(e) => setData("purchase_return_prefix", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.purchase_return_prefix} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="purchase_return_starting" className="col-span-3 flex items-center">
                                        Starting Number
                                    </Label>
                                    <div className="col-span-9">
                                        <Input
                                            id="purchase_return_starting"
                                            type="text"
                                            value={data.purchase_return_starting}
                                            onChange={(e) => setData("purchase_return_starting", e.target.value)}
                                            className="w-full"
                                        />
                                        <InputError message={errors.purchase_return_starting} className="mt-1" />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mb-4">
                                    <Label htmlFor="purchase_return_footer" className="col-span-3 flex items-center">
                                        Purchase Return Footer (HTML Allowed)
                                    </Label>
                                    <div className="col-span-9">
                                        <Textarea
                                            id="purchase_return_footer"
                                            value={data.purchase_return_footer}
                                            onChange={(e) => setData("purchase_return_footer", e.target.value)}
                                            className="w-full"
                                            rows={4}
                                        />
                                        <InputError message={errors.purchase_return_footer} className="mt-1" />
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
                                                        <th className="text-left px-4 py-2 w-1/3">COLUMN NAME</th>
                                                        <th className="text-left px-4 py-2 w-1/3">LABEL</th>
                                                        <th className="text-center px-4 py-2 w-1/6">VISIBLE</th>
                                                        <th className="text-center px-4 py-2 w-1/6">HIDDEN</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Name</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Name" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="name_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="name_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Description</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Description" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="description_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="description_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Quantity</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Quantity" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="quantity_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="quantity_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr className="border-b">
                                                        <td className="px-4 py-2">Price</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Price" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="price_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="price_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td className="px-4 py-2">Amount</td>
                                                        <td className="px-4 py-2">
                                                            <Input className="w-full" value="Amount" />
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="amount_visibility" 
                                                                    value="visible" 
                                                                    className="h-4 w-4" 
                                                                    defaultChecked 
                                                                />
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-2 text-center">
                                                            <div className="flex justify-center">
                                                                <input 
                                                                    type="radio" 
                                                                    name="amount_visibility" 
                                                                    value="hidden" 
                                                                    className="h-4 w-4" 
                                                                />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex justify-start mt-6">
                                    <Button type="submit" disabled={processing}>
                                        {processing ? "Saving..." : "Save Settings"}
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
