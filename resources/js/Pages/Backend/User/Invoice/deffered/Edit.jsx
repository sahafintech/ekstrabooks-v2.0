import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Textarea } from "@/Components/ui/textarea";
import { Plus, Trash2, X } from "lucide-react";
import Attachment from "@/Components/ui/attachment";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import { useState, useEffect } from "react";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import DateTimePicker from "@/Components/DateTimePicker";

export default function Edit({
    customers = [],
    products = [],
    currencies = [],
    taxes = [],
    taxIds = [],
    decimalPlace,
    familySizes = [],
    benefits = [],
    invoice,
    theAttachments,
}) {
    const [invoiceItems, setInvoiceItems] = useState([
        {
            product_id: "",
            product_name: "",
            description: "",
            quantity: 1,
            unit_cost: 0,
        },
    ]);

    const [attachments, setAttachments] = useState([]);

    const [exchangeRate, setExchangeRate] = useState(1);
    const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);

    // ------------------------------------------------
    // Keep Inertia form arrays in sync with our local state
    const syncFormArrays = () => {
        setData("product_id", invoiceItems.map(i => i.product_id));
        setData("product_name", invoiceItems.map(i => i.product_name));
        setData("description", invoiceItems.map(i => i.description));
        setData("quantity", invoiceItems.map(i => i.quantity));
        setData("unit_cost", invoiceItems.map(i => i.unit_cost));
        setData("sum_insured", invoiceItems.map(i => i.sum_insured));
        setData("benefits", invoiceItems.map(i => i.benefits));
        setData("family_size", invoiceItems.map(i => i.family_size));
        setData("attachments", attachments);
    };

    useEffect(() => {
        syncFormArrays();
    }, [invoiceItems, attachments]);
    // ------------------------------------------------


    const { data, setData, post, processing, errors, reset } = useForm({
        customer_id: invoice.customer_id,
        title: invoice.title,
        invoice_number: invoice.invoice_number,
        order_number: invoice.order_number,
        invoice_date: parseDateObject(invoice.invoice_date),
        due_date: parseDateObject(invoice.due_date),
        currency: invoice.currency,
        exchange_rate: invoice.exchange_rate,
        deffered_start: parseDateObject(invoice.deffered_start),
        deffered_end: parseDateObject(invoice.deffered_end),
        active_days: invoice.active_days,
        cost_per_day: Number(invoice.cost_per_day).toFixed(decimalPlace),
        invoice_category: invoice.invoice_category,
        converted_total: invoice.converted_total,
        discount_type: invoice.discount_type,
        discount_value: invoice.discount_value,
        template: invoice.template,
        note: invoice.note,
        footer: invoice.footer,
        attachments: [],
        product_id: [],
        product_name: [],
        description: [],
        quantity: [],
        unit_cost: [],
        sum_insured: [],
        benefits: [],
        family_size: [],
        taxes: taxIds,
        deffered_total: 0,
        earnings: [],
        _method: "PUT",
    });

    const invoiceCategories = [
        { id: "1", option: "Medical Insurance Invoice", value: "medical" },
        { id: "2", option: "GPA Insurance Invoice", value: "gpa" },
        { id: "3", option: "Other Insurance Invoice", value: "other" },
    ];

    // Initialize invoice items from existing invoice - runs only once on mount
    useEffect(() => {
        if (invoice && invoice.items && invoice.items.length > 0) {
            const formattedItems = invoice.items.map((item) => ({
                product_id: item.product_id,
                product_name: item.product_name,
                description: item.description,
                quantity: item.quantity,
                unit_cost: item.unit_cost,
                sum_insured: item.sum_insured,
                benefits: item.benefits,
                family_size: item.family_size,
            }));
            setInvoiceItems(formattedItems);
        }

        // Initialize attachments from existing invoice
        if (theAttachments && theAttachments.length > 0) {
            const formattedAttachments = theAttachments.map((attachment) => attachment.path);
            setAttachments(formattedAttachments);
        } else {
            // If no attachments, initialize with empty state
            setAttachments([]);
        }

        // Set the initial currency and exchange rate
        if (invoice && invoice.currency) {
            setData("currency", invoice.currency);
            setExchangeRate(invoice.exchange_rate);
        }

        // Format earnings data from the backend
        let formattedEarnings = [];

        if (invoice.deffered_earnings && invoice.deffered_earnings.length > 0) {
            formattedEarnings = invoice.deffered_earnings.map((earning) => ({
                start_date: parseDateObject(earning.start_date),
                end_date: parseDateObject(earning.end_date),
                number_of_days: earning.days,
                amount: parseFloat(earning.amount),
                status: earning.status,
            }));
        }

        // Use a functional update to ensure we're getting the latest state
        setData((prevData) => ({
            ...prevData,
            product_id: invoice.items.map((item) => item.product_id),
            product_name: invoice.items.map((item) => item.product_name),
            description: invoice.items.map((item) => item.description),
            quantity: invoice.items.map((item) => item.quantity),
            unit_cost: invoice.items.map((item) => item.unit_cost),
            sum_insured: invoice.items.map((item) => item.sum_insured),
            benefits: invoice.items.map((item) => item.benefits),
            family_size: invoice.items.map((item) => item.family_size),
            earnings: formattedEarnings,
            deffered_total: formattedEarnings.reduce(
                (sum, e) => sum + e.amount,
                0
            ),
        }));
    }, []);

    const addInvoiceItem = () => {
        setInvoiceItems([
            ...invoiceItems,
            {
                product_id: "",
                product_name: "",
                description: "",
                quantity: 1,
                unit_cost: 0,
            },
        ]);
        setData("product_id", [...data.product_id, ""]);
        setData("product_name", [...data.product_name, ""]);
        setData("description", [...data.description, ""]);
        setData("quantity", [...data.quantity, 1]);
        setData("unit_cost", [...data.unit_cost, 0]);
        setData("sum_insured", [...data.sum_insured, 0]);
        setData("benefits", [...data.benefits, ""]);
        setData("family_size", [...data.family_size, 0]);
    };

    const removeInvoiceItem = (index) => {
        const updatedItems = invoiceItems.filter((_, i) => i !== index);
        setInvoiceItems(updatedItems);
        setData(
            "product_id",
            updatedItems.map((item) => item.product_id)
        );
        setData(
            "product_name",
            updatedItems.map((item) => item.product_name)
        );
        setData(
            "description",
            updatedItems.map((item) => item.description)
        );
        setData(
            "quantity",
            updatedItems.map((item) => item.quantity)
        );
        setData(
            "unit_cost",
            updatedItems.map((item) => item.unit_cost)
        );
        setData(
            "sum_insured",
            updatedItems.map((item) => item.sum_insured)
        );
        setData(
            "benefits",
            updatedItems.map((item) => item.benefits)
        );
        setData(
            "family_size",
            updatedItems.map((item) => item.family_size)
        );
    };

    const updateInvoiceItem = (index, field, value) => {
        const updatedItems = [...invoiceItems];
        updatedItems[index][field] = value;

        if (field === "product_id") {
            const product = products.find((p) => p.id === parseInt(value, 10));
            if (product) {
                updatedItems[index].product_name = product.name;
                updatedItems[index].unit_cost = product.selling_price;

                // Also update the description if it's empty
                if (!updatedItems[index].description) {
                    updatedItems[index].description = product.description || "";
                }
            }
        }

        setInvoiceItems(updatedItems);
        // Mark that user has manually modified data
        setData((prevData) => ({
            ...prevData,
            _manuallyModified: true,
            product_id: updatedItems.map((item) => item.product_id),
            product_name: updatedItems.map((item) => item.product_name),
            description: updatedItems.map((item) => item.description),
            quantity: updatedItems.map((item) => item.quantity),
            unit_cost: updatedItems.map((item) => item.unit_cost),
            sum_insured: updatedItems.map((item) => item.sum_insured),
            benefits: updatedItems.map((item) => item.benefits),
            family_size: updatedItems.map((item) => item.family_size),
        }));
    };

    const calculateSubtotal = () => {
        return invoiceItems.reduce(
            (sum, item) => sum + item.quantity * item.unit_cost,
            0
        );
    };

    const calculateEarnings = () => {
        const startDate = new Date(data.deffered_start);
        const endDate = new Date(data.deffered_end);
        const subTotal = calculateSubtotal();
        const dp = decimalPlace;

        const msPerDay = 24 * 60 * 60 * 1000;
        // inclusive days
        const totalDays = Math.floor((endDate - startDate) / msPerDay) + 1;

        // convert subtotal → "cents" (or smallest unit)
        const factor = Math.pow(10, dp);
        const subTotalUnits = Math.round(subTotal * factor);

        // integer per-day cost (floor)
        const unitsPerDay = Math.floor(subTotalUnits / totalDays);

        // build the schedule in integer units
        const scheduleUnits = [];
        let cursor = new Date(startDate);
        let usedUnits = 0;

        while (cursor <= endDate) {
            const year = cursor.getFullYear();
            const month = cursor.getMonth();

            const sliceStart =
                year === startDate.getFullYear() &&
                month === startDate.getMonth()
                    ? new Date(startDate)
                    : new Date(year, month, 1);

            const monthEnd = new Date(year, month + 1, 0);

            const sliceEnd = monthEnd < endDate ? monthEnd : new Date(endDate);

            const days = Math.floor((sliceEnd - sliceStart) / msPerDay) + 1;
            const sliceUnits = unitsPerDay * days;

            scheduleUnits.push({ sliceStart, sliceEnd, days, sliceUnits });
            usedUnits += sliceUnits;

            cursor = new Date(year, month + 1, 1);
        }

        // leftover cents
        const remainder = subTotalUnits - usedUnits;
        if (scheduleUnits.length && remainder !== 0) {
            scheduleUnits[scheduleUnits.length - 1].sliceUnits += remainder;
        }

        // finally, convert back to decimals
        const schedule = scheduleUnits.map((u) => ({
            start_date: u.sliceStart,
            end_date: u.sliceEnd,
            number_of_days: u.days,
            amount: +(u.sliceUnits / factor).toFixed(dp),
        }));

        // return everything you need
        // Avoid division by zero
        const costPerDay =
            totalDays > 0 ? subTotalUnits / factor / totalDays : 0;
        return { schedule, totalDays, costPerDay };
    };

    // Calculate earnings whenever relevant fields change - but only if the user has modified values
    useEffect(() => {
        // Don't recalculate on initial render or if the data is already loaded from database
        // Only recalculate if user has changed something
        const subtotal = calculateSubtotal();

        if (data.deffered_start && data.deffered_end && subtotal > 0) {
            // Skip calculation if this is the initial data from the database
            // and the user hasn't modified any fields yet
            const isUserModified = data._manuallyModified || false;

            if (isUserModified || data.earnings.length === 0) {
                try {
                    const { schedule, totalDays, costPerDay } =
                        calculateEarnings();

                    // Ensure values are valid and not NaN
                    const safeCostPerDay = costPerDay ? costPerDay : 0;
                    const safeTotal = schedule.reduce((sum, e) => {
                        const amount = e.amount ? e.amount : 0;
                        return sum + amount;
                    }, 0);

                    // Use functional updates to avoid stale closures and ensure atomic updates
                    setData((prevData) => ({
                        ...prevData,
                        earnings: schedule,
                        active_days: totalDays,
                        cost_per_day: safeCostPerDay.toFixed(decimalPlace),
                        deffered_total: safeTotal.toFixed(decimalPlace),
                    }));
                } catch (error) {
                    console.error("Error calculating earnings:", error);
                }
            }
        }
    }, [data.deffered_start, data.deffered_end, invoiceItems]);

    // build this once, outside of calculateTaxes
    const taxRateMap = new Map(taxes.map((t) => [t.id, Number(t.rate)]));

    const calculateTaxes = () => {
        return invoiceItems.reduce((sum, item) => {
            const base = Number(item.quantity) * Number(item.unit_cost);

            const itemTax = data.taxes.reduce((taxSum, taxIdStr) => {
                // convert the incoming tax-ID string to a Number
                const taxId = Number(taxIdStr);

                // look up the rate; if missing, default to 0
                const rate = taxRateMap.get(taxId) || 0;

                return taxSum + (base * rate) / 100;
            }, 0);

            return sum + itemTax;
        }, 0);
    };

    const calculateDiscount = () => {
        const subtotal = calculateSubtotal();
        if (data.discount_type === "0") {
            return (subtotal * data.discount_value) / 100;
        }
        return data.discount_value;
    };

    const calculateTotal = () => {
        const subtotal = calculateSubtotal();
        const taxes = calculateTaxes();
        const discount = calculateDiscount();
        return subtotal + taxes - discount;
    };

    // Find and set base currency on component mount
    useEffect(() => {
        // First try to find a currency with base_currency flag set to 1
        let baseC = currencies.find((c) => c.base_currency === 1);

        // If still none found, just take the first currency as a fallback
        if (!baseC && currencies.length > 0) {
            baseC = currencies[0];
        }

        if (baseC) {
            setBaseCurrencyInfo(baseC);
        }
    }, [currencies]);

    // Update exchange rate whenever the selected currency changes
    const handleCurrencyChange = (currencyName) => {
        // Find currency object by name
        const currencyObj = currencies.find(
            (currency) => currency.name === currencyName
        );

        if (currencyObj) {
            // Set the exchange rate directly from the selected currency object first as a fallback
            const currentRate = parseFloat(currencyObj.exchange_rate);
            setExchangeRate(currentRate);
            setData("exchange_rate", currentRate);

            // Then try to fetch the updated exchange rate from the API
            fetch(`/user/find_currency/${currencyObj.name}`)
                .then((response) => response.json())
                .then((apiData) => {
                    if (apiData && apiData.exchange_rate) {
                        const apiRate = parseFloat(apiData.exchange_rate);
                        setExchangeRate(apiRate);
                        setData("exchange_rate", apiRate);
                    }
                })
                .catch((error) => {
                    console.error("Error fetching currency rate:", error);
                    // Already set the fallback exchange rate above
                });
        }
    };

    // Update converted_total whenever relevant values change
    useEffect(() => {
        const total = calculateTotal();
        const convertedTotal = total;
        setData("converted_total", convertedTotal);
    }, [
        data.currency,
        invoiceItems,
        data.discount_type,
        data.discount_value,
        exchangeRate,
    ]);

    const renderTotal = () => {
        const total = calculateTotal();
        const selectedCurrency = currencies.find(
            (c) => c.name === data.currency
        );

        if (!selectedCurrency) {
            return (
                <div>
                    <h2 className="text-xl font-bold">Total: 0.00</h2>
                </div>
            );
        }

        // If we have a base currency AND the selected currency is different from base
        if (
            baseCurrencyInfo &&
            selectedCurrency.name !== baseCurrencyInfo.name &&
            exchangeRate &&
            exchangeRate !== 1
        ) {
            // Calculate the base currency equivalent
            const baseCurrencyTotal = total / exchangeRate;

            return (
                <div>
                    <h2 className="text-xl font-bold">
                        Total:{" "}
                        {formatCurrency(
                            total,
                            selectedCurrency.name,
                            decimalPlace
                        )}
                    </h2>
                    <p className="text-sm text-gray-600">
                        Equivalent to{" "}
                        {formatCurrency(
                            baseCurrencyTotal,
                            baseCurrencyInfo.name,
                            decimalPlace
                        )}
                    </p>
                </div>
            );
        }

        return (
            <div>
                <h2 className="text-xl font-bold">
                    Total:{" "}
                    {formatCurrency(total, selectedCurrency.name, decimalPlace)}
                </h2>
            </div>
        );
    };

    const submit = (e) => {
        e.preventDefault();

        // Find the selected currency object to get its name
        const selectedCurrency = currencies.find(
            (c) => c.name === data.currency
        );

        if (!selectedCurrency) {
            toast.error("Please select a valid currency");
            return;
        }

        // Create a new data object with all the required fields
        const formData = {
            ...data,
            currency: selectedCurrency.name,
            exchange_rate: exchangeRate,
            product_id: invoiceItems.map((item) => item.product_id),
            product_name: invoiceItems.map((item) => item.product_name),
            description: invoiceItems.map((item) => item.description),
            quantity: invoiceItems.map((item) => item.quantity),
            unit_cost: invoiceItems.map((item) => item.unit_cost),
            sum_insured: invoiceItems.map((item) => item.sum_insured),
            benefits: invoiceItems.map((item) => item.benefits),
            family_size: invoiceItems.map((item) => item.family_size),
            // attachments are synced via syncFormArrays
        };

        // Post the form data directly instead of using setData first
        post(route("deffered_invoices.update", invoice.id), formData, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Invoice updated successfully");
                reset();
                setInvoiceItems([
                    {
                        product_id: "",
                        product_name: "",
                        description: "",
                        quantity: 1,
                        unit_cost: 0,
                        sum_insured: 0,
                        benefits: "",
                        family_size: "",
                    },
                ]);
                setAttachments([]);
            },
        });
    };

    const recalculateUnearnedEarnings = (newSubtotal) => {
        // Separate earned and unearned earnings
        const earned = data.earnings.filter(e => e.status === 1);
        const unearned = data.earnings.filter(e => e.status !== 1);

        // Calculate total earned
        const totalEarned = earned.reduce((sum, e) => sum + (e.amount || 0), 0);

        // Calculate new unearned amount
        const newUnearned = newSubtotal - totalEarned;

        // Distribute new unearned amount across unearned periods
        const n = unearned.length;
        if (n === 0) return data.earnings; // nothing to update

        const perPeriod = Math.floor((newUnearned * 100) / n) / 100; // round to 2 decimals
        let distributed = 0;

        const updatedUnearned = unearned.map((e, i) => {
            // Last period gets the remainder to ensure total matches
            const amount = (i === n - 1)
                ? +(newUnearned - distributed).toFixed(2)
                : perPeriod;
            distributed += amount;
            return { ...e, amount };
        });

        // Merge back, keeping order
        let earnedIdx = 0, unearnedIdx = 0;
        const updatedEarnings = data.earnings.map(e => 
            e.status === 1 ? earned[earnedIdx++] : updatedUnearned[unearnedIdx++]
        );

        // Update deferred total
        const deffered_total = updatedEarnings.reduce((sum, e) => sum + (e.amount || 0), 0);

        setData(prev => ({
            ...prev,
            earnings: updatedEarnings,
            deffered_total: deffered_total.toFixed(decimalPlace),
        }));
    };

    useEffect(() => {
        const subtotal = calculateSubtotal();
        if (data.earnings && data.earnings.length > 0) {
            recalculateUnearnedEarnings(subtotal);
        }
        // ...other logic
    }, [invoiceItems]);

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Deffered Invoices"
                    subpage="Edit Invoice"
                    url="deffered_invoices.index"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="customer_id"
                                className="md:col-span-2 col-span-12"
                            >
                                Customer *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={customers.map((customer) => ({
                                            id: customer.id,
                                            name: customer.name,
                                        }))}
                                        value={data.customer_id}
                                        onChange={(value) =>
                                            setData("customer_id", value)
                                        }
                                        placeholder="Select customer"
                                    />
                                </div>
                                <InputError
                                    message={errors.customer_id}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="title"
                                className="md:col-span-2 col-span-12"
                            >
                                Title *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(e) =>
                                        setData("title", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.title}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="order_number"
                                className="md:col-span-2 col-span-12"
                            >
                                Policy Number *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="order_number"
                                    type="text"
                                    value={data.order_number}
                                    onChange={(e) =>
                                        setData("order_number", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.order_number}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="invoice_date"
                                className="md:col-span-2 col-span-12"
                            >
                                Invoice Date *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker
                                    value={data.invoice_date}
                                    onChange={(date) =>
                                        setData("invoice_date", date)
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.invoice_date}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="due_date"
                                className="md:col-span-2 col-span-12"
                            >
                                Due Date *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker
                                    value={data.due_date}
                                    onChange={(date) =>
                                        setData("due_date", date)
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.due_date}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="invoice_category"
                                className="md:col-span-2 col-span-12"
                            >
                                Invoice Category *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        className="mt-1"
                                        options={invoiceCategories.map(
                                            (category) => ({
                                                id: category.value,
                                                label: category.option,
                                                name: category.option,
                                            })
                                        )}
                                        value={data.invoice_category}
                                        onChange={(selectedValue) =>
                                            setData(
                                                "invoice_category",
                                                selectedValue
                                            )
                                        }
                                        placeholder="Select invoice category"
                                    />
                                </div>
                                <InputError
                                    message={errors.invoice_category}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="currency"
                                className="md:col-span-2 col-span-12"
                            >
                                Currency *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        className="mt-1"
                                        options={currencies.map((currency) => ({
                                            id: currency.name,
                                            value: currency.name,
                                            label: currency.name,
                                            name: `${currency.name} - ${currency.description} (${currency.exchange_rate})`,
                                        }))}
                                        value={data.currency}
                                        onChange={(selectedValue) => {
                                            setData("currency", selectedValue);
                                            handleCurrencyChange(selectedValue);
                                        }}
                                        placeholder="Select currency"
                                    />
                                </div>
                                <InputError
                                    message={errors.currency}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <SidebarSeparator className="my-4" />

                        <div className="space-y-4">
                            <div className="flex justify-between items-center">
                                <h3 className="text-lg font-medium">
                                    Invoice Items
                                </h3>
                                <Button
                                    variant="secondary"
                                    type="button"
                                    onClick={addInvoiceItem}
                                >
                                    <Plus className="w-4 h-4 mr-2" />
                                    Add Item
                                </Button>
                            </div>

                            {data.invoice_category !== "" ? (
                                invoiceItems.map((item, index) => (
                                    <div
                                        key={index}
                                        className="border rounded-lg p-4 space-y-4 bg-gray-50"
                                    >
                                        {/* First Row */}
                                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                            <div>
                                                <Label>Service *</Label>
                                                <SearchableCombobox
                                                    options={products.map(
                                                        (product) => ({
                                                            id: product.id,
                                                            name: product.name,
                                                        })
                                                    )}
                                                    value={item.product_id}
                                                    onChange={(value) =>
                                                        updateInvoiceItem(
                                                            index,
                                                            "product_id",
                                                            value
                                                        )
                                                    }
                                                    placeholder="Select service"
                                                />
                                            </div>

                                            <div>
                                                {data.invoice_category ===
                                                "medical" ? (
                                                    <Label>Members *</Label>
                                                ) : (
                                                    <Label>Quantity *</Label>
                                                )}
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    value={item.quantity}
                                                    onChange={(e) =>
                                                        updateInvoiceItem(
                                                            index,
                                                            "quantity",
                                                            parseFloat(
                                                                e.target.value
                                                            )
                                                        )
                                                    }
                                                />
                                            </div>

                                            {data.invoice_category ===
                                                "medical" && (
                                                <div>
                                                    <Label>Family Size *</Label>
                                                    <SearchableCombobox
                                                        options={familySizes.map(
                                                            (size) => ({
                                                                id: size.size,
                                                                name: size.size,
                                                            })
                                                        )}
                                                        value={item.family_size}
                                                        onChange={(value) =>
                                                            updateInvoiceItem(
                                                                index,
                                                                "family_size",
                                                                value
                                                            )
                                                        }
                                                        placeholder="Select family size"
                                                    />
                                                </div>
                                            )}

                                            <div>
                                                <Label>Benefits</Label>
                                                <Textarea
                                                    value={item.benefits}
                                                    onChange={(e) => {
                                                    updateInvoiceItem(index, "benefits", e.target.value);
                                                    e.target.style.height = 'auto';
                                                    e.target.style.height = e.target.scrollHeight + 'px';
                                                    }}
                                                    className="min-h-[30px] resize-none overflow-hidden"
                                                    rows={1}
                                                />
                                            </div>
                                        </div>

                                        {/* Second Row */}
                                        <div className="grid grid-cols-1 md:grid-cols-12 lg:grid-cols-4 gap-2">
                                            {data.invoice_category ===
                                                "other" && (
                                                <div>
                                                    <Label>Sum Insured</Label>
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        value={item.sum_insured}
                                                        onChange={(e) =>
                                                            updateInvoiceItem(
                                                                index,
                                                                "sum_insured",
                                                                parseFloat(
                                                                    e.target
                                                                        .value
                                                                )
                                                            )
                                                        }
                                                    />
                                                </div>
                                            )}

                                            <div>
                                                <Label>Rate *</Label>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    value={item.unit_cost}
                                                    onChange={(e) =>
                                                        updateInvoiceItem(
                                                            index,
                                                            "unit_cost",
                                                            parseFloat(
                                                                e.target.value
                                                            )
                                                        )
                                                    }
                                                />
                                            </div>

                                            <div>
                                                <Label>Subtotal</Label>
                                                <div className="p-2 bg-white rounded text-right">
                                                    {(
                                                        item.quantity *
                                                        item.unit_cost
                                                    ).toFixed(2)}
                                                </div>
                                            </div>

                                            <div className="flex items-end justify-end">
                                                {invoiceItems.length > 1 && (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        className="text-red-500"
                                                        onClick={() =>
                                                            removeInvoiceItem(
                                                                index
                                                            )
                                                        }
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <p>No category selected</p>
                            )}
                        </div>

                        <SidebarSeparator className="my-4" />

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="deffered_start"
                                className="md:col-span-2 col-span-12"
                            >
                                Policy Start *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker
                                    value={data.deffered_start}
                                    onChange={(date) =>
                                        setData("deffered_start", date)
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.deffered_start}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="deffered_end"
                                className="md:col-span-2 col-span-12"
                            >
                                Policy End *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker
                                    value={data.deffered_end}
                                    onChange={(date) =>
                                        setData("deffered_end", date)
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.deffered_end}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="active_days"
                                className="md:col-span-2 col-span-12"
                            >
                                Active Days *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="active_days"
                                    type="number"
                                    value={data.active_days}
                                    onChange={(e) =>
                                        setData(
                                            "active_days",
                                            parseInt(e.target.value)
                                        )
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                    readOnly
                                />
                                <InputError
                                    message={errors.active_days}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="cost_per_day"
                                className="md:col-span-2 col-span-12"
                            >
                                Cost Per Days *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="cost_per_day"
                                    type="number"
                                    value={data.cost_per_day}
                                    onChange={(e) =>
                                        setData(
                                            "cost_per_day",
                                            parseInt(e.target.value)
                                        )
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                    readOnly
                                />
                                <InputError
                                    message={errors.cost_per_day}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <SidebarSeparator className="my-4" />

                        <div>
                            <p>Deferred Earning Schedule</p>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>
                                            Earning Start Date
                                        </TableHead>
                                        <TableHead>Earning End Date</TableHead>
                                        <TableHead>No Of Days</TableHead>
                                        <TableHead>
                                            Earning recognized
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {data.earnings &&
                                    data.earnings.length > 0 ? (
                                        data.earnings.map((earning, index) => (
                                            <TableRow
                                                key={index}
                                                className={
                                                    earning.status === 1
                                                        ? "bg-gray-100"
                                                        : ""
                                                }
                                            >
                                                <TableCell>
                                                    {new Date(
                                                        earning.start_date
                                                    ).toLocaleDateString()}
                                                </TableCell>
                                                <TableCell>
                                                    {new Date(
                                                        earning.end_date
                                                    ).toLocaleDateString()}
                                                </TableCell>
                                                <TableCell>
                                                    {earning.number_of_days}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({
                                                        amount: earning.amount,
                                                    })}
                                                    {earning.status === 1 && (
                                                        <span className="ml-2 text-xs text-green-600">
                                                            (Earned)
                                                        </span>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell
                                                colSpan={10}
                                                className="h-24 text-center"
                                            >
                                                No earnings found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    <TableRow>
                                        <TableCell>TOTAL:</TableCell>
                                        <TableCell
                                            colSpan={9}
                                            className="text-right"
                                        >
                                            {formatCurrency({
                                                amount: data.deffered_total,
                                            })}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <SidebarSeparator className="my-4" />

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="discount_type"
                                className="md:col-span-2 col-span-12"
                            >
                                Tax
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableMultiSelectCombobox
                                        options={taxes?.map((tax) => ({
                                            id: tax.id,
                                            name: `${tax.name} (${tax.rate}%)`,
                                        }))}
                                        value={data.taxes}
                                        onChange={(values) =>
                                            setData("taxes", values)
                                        }
                                        placeholder="Select taxes"
                                    />
                                </div>
                                <InputError
                                    message={errors.taxes}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="discount_type"
                                className="md:col-span-2 col-span-12"
                            >
                                Discount Type
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={[
                                            { id: "0", name: "Percentage (%)" },
                                            { id: "1", name: "Fixed Amount" },
                                        ]}
                                        value={data.discount_type}
                                        onChange={(value) =>
                                            setData("discount_type", value)
                                        }
                                        placeholder="Select discount type"
                                    />
                                </div>
                                <InputError
                                    message={errors.discount_type}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="discount_value"
                                className="md:col-span-2 col-span-12"
                            >
                                Discount Value
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="discount_value"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.discount_value}
                                    onChange={(e) =>
                                        setData(
                                            "discount_value",
                                            parseFloat(e.target.value)
                                        )
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError
                                    message={errors.discount_value}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="note"
                                className="md:col-span-2 col-span-12"
                            >
                                Note
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea
                                    id="note"
                                    value={data.note}
                                    onChange={(e) =>
                                        setData("note", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                    rows={4}
                                />
                                <InputError
                                    message={errors.note}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="footer"
                                className="md:col-span-2 col-span-12"
                            >
                                Footer
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea
                                    id="footer"
                                    value={data.footer}
                                    onChange={(e) =>
                                        setData("footer", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                    rows={4}
                                />
                                <InputError
                                    message={errors.footer}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="attachments"
                                className="md:col-span-2 col-span-12"
                            >
                                Attachments
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2 space-y-2 md:w-1/2 w-full">
                                <Attachment
                                    files={attachments}
                                    databaseAttachments={theAttachments}
                                    onAdd={files => setAttachments(prev => [...prev, ...files])}
                                    onRemove={idx => setAttachments(prev => prev.filter((_, i) => i !== idx))}
                                    maxSize={20}
                                />
                                <InputError message={errors.attachments} className="text-sm" />
                            </div>
                        </div>

                        <div className="mt-6 space-y-2">
                            <div className="space-y-2">
                                <div className="text-sm">
                                    Subtotal:{" "}
                                    {calculateSubtotal().toFixed(decimalPlace)}
                                </div>
                                <div className="text-sm">
                                    Taxes:{" "}
                                    {calculateTaxes().toFixed(decimalPlace)}
                                </div>
                                <div className="text-sm">
                                    Discount: {calculateDiscount()}
                                </div>
                                {renderTotal()}
                            </div>

                            <div className="space-x-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => {
                                        reset();
                                        setInvoiceItems([
                                            {
                                                product_id: "",
                                                product_name: "",
                                                description: "",
                                                quantity: 1,
                                                unit_cost: 0,
                                            },
                                        ]);
                                    }}
                                >
                                    Reset
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Update Deffered Invoice
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
