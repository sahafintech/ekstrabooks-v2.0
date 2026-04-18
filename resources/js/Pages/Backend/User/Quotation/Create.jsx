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
import { ChevronDown, Plus, Trash2 } from "lucide-react";
import { formatCurrency } from "@/lib/utils";
import { useEffect, useState } from "react";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import DateTimePicker from "@/Components/DateTimePicker";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/Components/ui/collapsible";

const MEDICAL_COVERAGE_SECTIONS = [
    { key: "inpatient", label: "Inpatient" },
    { key: "maternity", label: "Maternity" },
    { key: "outpatient", label: "Outpatient" },
    { key: "dental", label: "Dental" },
    { key: "optical", label: "Optical" },
    { key: "telemedicine", label: "Telemedicine" },
];

const parseNumericValue = (value) => {
    if (value === "" || value === null || value === undefined) {
        return "";
    }

    const parsedValue = parseFloat(value);
    return Number.isNaN(parsedValue) ? "" : parsedValue;
};

const createEmptyCoverageEntry = () => ({
    limit_per_family: "",
    contribution_per_family: "",
    total_contribution: "",
});

const createEmptyCoverageConfiguration = () =>
    MEDICAL_COVERAGE_SECTIONS.reduce((configuration, section) => {
        configuration[section.key] = createEmptyCoverageEntry();
        return configuration;
    }, {});

const calculateCoverageTotalContribution = (members, contributionPerFamily) => {
    if (contributionPerFamily === "" || contributionPerFamily === null || contributionPerFamily === undefined) {
        return "";
    }

    return (Number(members) || 0) * (Number(contributionPerFamily) || 0);
};

const normalizeMedicalCoverageConfiguration = (coverageConfiguration = {}, members = 0) =>
    MEDICAL_COVERAGE_SECTIONS.reduce((configuration, section) => {
        const sectionValues = coverageConfiguration?.[section.key] ?? {};
        const contributionPerFamily = parseNumericValue(sectionValues.contribution_per_family);

        configuration[section.key] = {
            limit_per_family: parseNumericValue(sectionValues.limit_per_family),
            contribution_per_family: contributionPerFamily,
            total_contribution: calculateCoverageTotalContribution(members, contributionPerFamily),
        };

        return configuration;
    }, {});

const calculateMedicalCoverageRate = (coverageConfiguration = {}) =>
    MEDICAL_COVERAGE_SECTIONS.reduce(
        (sum, section) => sum + (Number(coverageConfiguration?.[section.key]?.total_contribution) || 0),
        0
    );

const parseFamilySizeValue = (familySize) => {
    if (!familySize) {
        return null;
    }

    const normalizedFamilySize = String(familySize).trim().toUpperCase();
    const memberFormatMatch = normalizedFamilySize.match(/^M\s*\+\s*(\d+(?:\.\d+)?)$/);

    if (memberFormatMatch) {
        return 1 + Number(memberFormatMatch[1]);
    }

    if (/^\d+(?:\.\d+)?$/.test(normalizedFamilySize)) {
        return Number(normalizedFamilySize);
    }

    const combinedFamilySizeMatch = normalizedFamilySize.match(/^(\d+(?:\.\d+)?)\s*\+\s*(\d+(?:\.\d+)?)$/);

    if (combinedFamilySizeMatch) {
        return Number(combinedFamilySizeMatch[1]) + Number(combinedFamilySizeMatch[2]);
    }

    return null;
};

const calculateMembersAndFamilyValue = (members, familySize) => {
    if (members === "" || members === null || members === undefined || !familySize) {
        return "";
    }

    const familySizeValue = parseFamilySizeValue(familySize);

    if (familySizeValue === null) {
        return "";
    }

    return (Number(members) || 0) * familySizeValue;
};

const createEmptyQuotationItem = () => ({
    product_id: "",
    product_name: "",
    description: "",
    quantity: 1,
    unit_cost: 0,
    family_size: "",
    sum_insured: "",
    coverage_configuration: createEmptyCoverageConfiguration(),
});

const quotationTypeOptions = [
    { id: "0", name: "Normal Quotation" },
    { id: "1", name: "Deferred Quotation" },
];

const deferredQuotationCategoryOptions = [
    { id: "medical", name: "Medical Insurance Quotation" },
    { id: "gpa", name: "GPA Insurance Quotation" },
    { id: "other", name: "Other Insurance Quotation" },
];

export default function Create({
    customers = [],
    products = [],
    currencies = [],
    taxes = [],
    familySizes = [],
    quotation_title,
    base_currency,
}) {
    const [quotationItems, setQuotationItems] = useState([createEmptyQuotationItem()]);
    const [openCoverageConfigurationIndex, setOpenCoverageConfigurationIndex] = useState(null);

    const [exchangeRate, setExchangeRate] = useState(1);
    const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        customer_id: "",
        title: quotation_title,
        quotation_number: "",
        po_so_number: "",
        quotation_date: new Date(),
        expired_date: "",
        currency: base_currency,
        exchange_rate: 1,
        converted_total: 0,
        discount_type: "0",
        discount_value: 0,
        template: "",
        note: "",
        footer: "",
        exclusions_remarks: "",
        coverage_summary: "",
        attachment: null,
        is_deffered: "0",
        invoice_category: "",
        product_id: [],
        product_name: [],
        description: [],
        quantity: [],
        unit_cost: [],
        family_size: [],
        sum_insured: [],
        coverage_configuration: [],
        taxes: [],
    });

    const isDeferredQuotation = data.is_deffered === "1";
    const canManageItems = !isDeferredQuotation || data.invoice_category !== "";
    const isMedicalDeferredQuotation = isDeferredQuotation && data.invoice_category === "medical";
    const isOtherDeferredQuotation = isDeferredQuotation && data.invoice_category === "other";
    const showFamilySizeField = isMedicalDeferredQuotation;
    const showSumInsuredField = isOtherDeferredQuotation;
    const quotationItemGridClassName =
        showFamilySizeField
            ? "grid grid-cols-1 md:grid-cols-8 gap-2 items-end"
            : showSumInsuredField
                ? "grid grid-cols-1 md:grid-cols-7 gap-2 items-end"
                : "grid grid-cols-1 md:grid-cols-6 gap-2 items-end";
    const customerOptions = customers.map((customer) => ({
        id: customer.id,
        name: customer.name,
    }));
    const productOptions = products.map((product) => ({
        id: product.id,
        name: product.name,
    }));
    const familySizeOptions = familySizes.map((size) => ({
        id: size.size,
        name: size.size,
    }));
    const currencyOptions = currencies.map((currency) => ({
        id: currency.name,
        value: currency.name,
        label: currency.name,
        name: `${currency.name} - ${currency.description} (${currency.exchange_rate})`,
    }));
    const discountTypeOptions = [
        { id: "0", name: "Percentage (%)" },
        { id: "1", name: "Fixed Amount" },
    ];

    const syncQuotationItems = (items) => {
        setData("product_id", items.map((item) => item.product_id));
        setData("product_name", items.map((item) => item.product_name));
        setData("description", items.map((item) => item.description));
        setData("quantity", items.map((item) => item.quantity));
        setData("unit_cost", items.map((item) => item.unit_cost));
        setData("family_size", items.map((item) => item.family_size));
        setData("sum_insured", items.map((item) => item.sum_insured));
        setData("coverage_configuration", items.map((item) => item.coverage_configuration));
    };

    const sanitizeDeferredItemFields = (items, deferred, category) =>
        items.map((item) => ({
            ...item,
            family_size: deferred && category === "medical" ? item.family_size ?? "" : "",
            sum_insured: deferred && category === "other" ? item.sum_insured ?? "" : "",
            coverage_configuration:
                deferred && category === "medical"
                    ? normalizeMedicalCoverageConfiguration(item.coverage_configuration, item.quantity)
                    : createEmptyCoverageConfiguration(),
        }));

    const updateItems = (items) => {
        const normalizedItems = items.map((item) => ({
            ...item,
            coverage_configuration: normalizeMedicalCoverageConfiguration(
                item.coverage_configuration,
                item.quantity
            ),
        })).map((item) => ({
            ...item,
            unit_cost: isMedicalDeferredQuotation
                ? calculateMedicalCoverageRate(item.coverage_configuration)
                : item.unit_cost,
        }));

        setQuotationItems(normalizedItems);
        syncQuotationItems(normalizedItems);
    };

    const calculateItemRate = (item) =>
        isMedicalDeferredQuotation
            ? calculateMedicalCoverageRate(item.coverage_configuration)
            : Number(item.unit_cost) || 0;

    const calculateItemSubtotal = (item) =>
        isMedicalDeferredQuotation
            ? calculateItemRate(item)
            : (Number(item.quantity) || 0) * (Number(item.unit_cost) || 0);

    const autoResizeTextarea = (event, onChange) => {
        onChange(event.target.value);
        event.target.style.height = "auto";
        event.target.style.height = `${event.target.scrollHeight}px`;
    };

    useEffect(() => {
        if (!isMedicalDeferredQuotation || quotationItems.length === 0) {
            setOpenCoverageConfigurationIndex(null);
            return;
        }

        setOpenCoverageConfigurationIndex((currentIndex) =>
            currentIndex !== null && currentIndex < quotationItems.length ? currentIndex : 0
        );
    }, [isMedicalDeferredQuotation, quotationItems.length]);

    const addQuotationItem = () => {
        const updatedItems = [...quotationItems, createEmptyQuotationItem()];
        updateItems(updatedItems);

        if (isMedicalDeferredQuotation) {
            setOpenCoverageConfigurationIndex(updatedItems.length - 1);
        }
    };

    const removeQuotationItem = (index) => {
        const updatedItems = quotationItems.filter((_, itemIndex) => itemIndex !== index);
        updateItems(updatedItems);
        setOpenCoverageConfigurationIndex((currentIndex) => {
            if (currentIndex === null) {
                return null;
            }

            if (currentIndex === index) {
                return updatedItems.length > 0 ? Math.max(0, index - 1) : null;
            }

            return currentIndex > index ? currentIndex - 1 : currentIndex;
        });
    };

    const handleQuotationTypeChange = (value) => {
        setData("is_deffered", value);

        const nextCategory = value === "1" ? data.invoice_category : "";
        if (value !== "1") {
            setData("invoice_category", "");
        }

        updateItems(sanitizeDeferredItemFields(quotationItems, value === "1", nextCategory));
    };

    const handleInvoiceCategoryChange = (value) => {
        setData("invoice_category", value);
        updateItems(sanitizeDeferredItemFields(quotationItems, true, value));
    };

    const updateQuotationItem = (index, field, value) => {
        const updatedItems = [...quotationItems];
        updatedItems[index] = {
            ...updatedItems[index],
            [field]: value,
        };

        if (field === "product_id") {
            const product = products.find((productItem) => productItem.id === parseInt(value, 10));
            if (product) {
                updatedItems[index].product_name = product.name;
                if (!isMedicalDeferredQuotation) {
                    updatedItems[index].unit_cost = product.selling_price;
                }

                if (!updatedItems[index].description) {
                    updatedItems[index].description = product.description || "";
                }
            }
        }

        updateItems(updatedItems);
    };

    const updateCoverageConfiguration = (index, sectionKey, field, value) => {
        const updatedItems = [...quotationItems];
        const currentCoverageConfiguration =
            updatedItems[index].coverage_configuration ?? createEmptyCoverageConfiguration();

        updatedItems[index] = {
            ...updatedItems[index],
            coverage_configuration: {
                ...currentCoverageConfiguration,
                [sectionKey]: {
                    ...(currentCoverageConfiguration[sectionKey] ?? createEmptyCoverageEntry()),
                    [field]: value,
                },
            },
        };

        updateItems(updatedItems);
    };

    const calculateSubtotal = () =>
        quotationItems.reduce(
            (sum, item) => sum + calculateItemSubtotal(item),
            0
        );

    const taxRateMap = new Map(taxes.map((tax) => [tax.id, Number(tax.rate)]));

    const calculateTaxes = () => {
        return quotationItems.reduce((sum, item) => {
            const base = calculateItemSubtotal(item);

            const itemTax = data.taxes.reduce((taxSum, taxIdStr) => {
                // convert the incoming tax‐ID string to a Number
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
        const discountValue = Number(data.discount_value) || 0;
        if (data.discount_type === "0") {
            return (subtotal * discountValue) / 100;
        }
        return discountValue;
    };

    const calculateTotal = () => calculateSubtotal() + calculateTaxes() - calculateDiscount();

    // Find and set base currency on component mount
    useEffect(() => {
        // First try to find a currency with base_currency flag set to 1
        let baseC = currencies.find(c => c.base_currency === 1);

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
        const currencyObj = currencies.find(currency => currency.name === currencyName);

        if (currencyObj) {

            // Set the exchange rate directly from the selected currency object first as a fallback
            const currentRate = parseFloat(currencyObj.exchange_rate);
            setExchangeRate(currentRate);
            setData('exchange_rate', currentRate);

            // Then try to fetch the updated exchange rate from the API
            fetch(`/user/find_currency/${currencyObj.name}`)
                .then(response => response.json())
                .then(apiData => {
                    if (apiData && apiData.exchange_rate) {
                        const apiRate = parseFloat(apiData.exchange_rate);
                        setExchangeRate(apiRate);
                        setData('exchange_rate', apiRate);
                    }
                })
                .catch(error => {
                    console.error("Error fetching currency rate:", error);
                    // Already set the fallback exchange rate above
                });
        }
    };

    // Update converted_total whenever relevant values change
    useEffect(() => {
        const total = calculateTotal();
        const convertedTotal = total;
        setData('converted_total', convertedTotal);
    }, [data.currency, quotationItems, data.discount_type, data.discount_value, exchangeRate]);

    const renderTotal = () => {
        const total = calculateTotal();
        const selectedCurrency = currencies.find(c => c.name === data.currency);

        if (!selectedCurrency) {
            return (
                <div>
                    <h2 className="text-xl font-bold">Total: 0.00</h2>
                </div>
            );
        }

        // If we have a base currency AND the selected currency is different from base
        if (baseCurrencyInfo &&
            selectedCurrency.name !== baseCurrencyInfo.name &&
            exchangeRate &&
            exchangeRate !== 1) {

            // Calculate the base currency equivalent
            const baseCurrencyTotal = total / exchangeRate;

            return (
                <div>
                    <h2 className="text-xl font-bold">Total: {formatCurrency({ amount: total, currency: selectedCurrency.name })}</h2>
                    <p className="text-sm text-gray-600">
                        Equivalent to {formatCurrency({ amount: baseCurrencyTotal, currency: baseCurrencyInfo.name })}
                    </p>
                </div>
            );
        }

        return (
            <div>
                <h2 className="text-xl font-bold">Total: {formatCurrency({ amount: total, currency: selectedCurrency.name })}</h2>
            </div>
        );
    };

    const submit = (e) => {
        e.preventDefault();

        const selectedCurrency = currencies.find(c => c.name === data.currency);

        if (!selectedCurrency) {
            toast.error("Please select a valid currency");
            return;
        }

        if (isDeferredQuotation && !data.invoice_category) {
            toast.error("Please select a deferred quotation category");
            return;
        }

        const formData = {
            ...data,
            currency: selectedCurrency.name,
            exchange_rate: exchangeRate,
            product_id: quotationItems.map(item => item.product_id),
            product_name: quotationItems.map(item => item.product_name),
            description: quotationItems.map(item => item.description),
            quantity: quotationItems.map(item => item.quantity),
            unit_cost: quotationItems.map(item => item.unit_cost),
            family_size: quotationItems.map(item => item.family_size),
            sum_insured: quotationItems.map(item => item.sum_insured),
            coverage_configuration: quotationItems.map((item) => item.coverage_configuration),
        };

        post(route("quotations.store"), formData, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Quotation created successfully");
                reset();
                setQuotationItems([createEmptyQuotationItem()]);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Quotations" subpage="Create New" url="quotations.index" />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="customer_id" className="md:col-span-2 col-span-12">
                                Customer *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={customerOptions}
                                        value={data.customer_id}
                                        onChange={(value) => setData("customer_id", value)}
                                        placeholder="Select customer"
                                    />
                                </div>
                                <InputError message={errors.customer_id} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="title" className="md:col-span-2 col-span-12">
                                Title *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData("title", e.target.value)}
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError message={errors.title} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="is_deffered" className="md:col-span-2 col-span-12">
                                Quotation Type *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={quotationTypeOptions}
                                        value={data.is_deffered}
                                        onChange={handleQuotationTypeChange}
                                        placeholder="Select quotation type"
                                    />
                                </div>
                                <InputError message={errors.is_deffered} className="text-sm" />
                            </div>
                        </div>

                        {isDeferredQuotation && (
                            <div className="grid grid-cols-12 mt-2">
                                <Label htmlFor="invoice_category" className="md:col-span-2 col-span-12">
                                    Deferred Category *
                                </Label>
                                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                    <div className="md:w-1/2 w-full">
                                        <SearchableCombobox
                                            options={deferredQuotationCategoryOptions}
                                            value={data.invoice_category}
                                            onChange={handleInvoiceCategoryChange}
                                            placeholder="Select deferred quotation category"
                                        />
                                    </div>
                                    <InputError message={errors.invoice_category} className="text-sm" />
                                </div>
                            </div>
                        )}

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="po_so_number" className="md:col-span-2 col-span-12">
                                {isDeferredQuotation ? "Quotation Number" : "Order Number"}
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="po_so_number"
                                    type="text"
                                    value={data.po_so_number}
                                    onChange={(e) => setData("po_so_number", e.target.value)}
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.po_so_number} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="quotation_date" className="md:col-span-2 col-span-12">
                                Quotation Date *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker
                                    value={data.quotation_date}
                                    onChange={(date) => setData("quotation_date", date)}
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError message={errors.quotation_date} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="expired_date" className="md:col-span-2 col-span-12">
                                Expired Date *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker
                                    value={data.expired_date}
                                    onChange={(date) => setData("expired_date", date)}
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError message={errors.expired_date} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="currency" className="md:col-span-2 col-span-12">
                                Currency *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        className="mt-1"
                                        options={currencyOptions}
                                        value={data.currency}
                                        onChange={(selectedValue) => {
                                            setData("currency", selectedValue);
                                            handleCurrencyChange(selectedValue);
                                        }}
                                        placeholder="Select currency"
                                    />
                                </div>
                                <InputError message={errors.currency} className="text-sm" />
                            </div>
                        </div>

                        <SidebarSeparator className="my-4" />

                        <div className="space-y-4">
                            <div className="flex justify-between items-center">
                                <h3 className="text-lg font-medium">Quotation Items</h3>
                                <Button
                                    variant="secondary"
                                    type="button"
                                    onClick={addQuotationItem}
                                    disabled={!canManageItems}
                                >
                                    <Plus className="w-4 h-4 mr-2" />
                                    Add Item
                                </Button>
                            </div>

                            {isDeferredQuotation && !data.invoice_category ? (
                                <div className="border rounded-lg p-4 bg-gray-50 text-sm text-muted-foreground">
                                    Select a deferred quotation category first to unlock the matching insurance item fields.
                                </div>
                            ) : (
                                quotationItems.map((item, index) => (
                                    <div key={index} className="border rounded-lg p-4 space-y-4 bg-gray-50">
                                        <div className={quotationItemGridClassName}>
                                            <div>
                                                <Label>Product *</Label>
                                                <SearchableCombobox
                                                    options={productOptions}
                                                    value={item.product_id}
                                                    onChange={(value) => updateQuotationItem(index, "product_id", value)}
                                                    placeholder="Select product"
                                                />
                                            </div>

                                            <div>
                                                <Label>{isMedicalDeferredQuotation ? "Members *" : "Quantity *"}</Label>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    value={item.quantity}
                                                    onChange={(e) => updateQuotationItem(index, "quantity", parseNumericValue(e.target.value))}
                                                />
                                            </div>

                                            {showFamilySizeField && (
                                                <div>
                                                    <Label>Family Size *</Label>
                                                    <SearchableCombobox
                                                        options={familySizeOptions}
                                                        value={item.family_size}
                                                        onChange={(value) => updateQuotationItem(index, "family_size", value)}
                                                        placeholder="Select family size"
                                                    />
                                                </div>
                                            )}

                                            {showFamilySizeField && (
                                                <div>
                                                    <Label>Members + Family</Label>
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        value={calculateMembersAndFamilyValue(item.quantity, item.family_size)}
                                                        disabled
                                                        readOnly
                                                    />
                                                </div>
                                            )}

                                            <div>
                                                <Label>{isDeferredQuotation ? "Rate *" : "Unit Cost *"}</Label>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    value={calculateItemRate(item)}
                                                    onChange={(e) => updateQuotationItem(index, "unit_cost", parseNumericValue(e.target.value))}
                                                    disabled={isMedicalDeferredQuotation}
                                                    readOnly={isMedicalDeferredQuotation}
                                                />
                                            </div>

                                            {showSumInsuredField && (
                                                <div>
                                                    <Label>Sum Insured</Label>
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        value={item.sum_insured}
                                                        onChange={(e) => updateQuotationItem(index, "sum_insured", parseNumericValue(e.target.value))}
                                                    />
                                                </div>
                                            )}

                                            <div>
                                                <Label>Description</Label>
                                                <Textarea
                                                    value={item.description}
                                                    onChange={(e) =>
                                                        autoResizeTextarea(e, (value) => updateQuotationItem(index, "description", value))
                                                    }
                                                    className="min-h-[30px] resize-none overflow-hidden"
                                                    rows={1}
                                                />
                                            </div>

                                            <div>
                                                <Label>Subtotal</Label>
                                                <div className="p-2 bg-white rounded text-right">
                                                    {calculateItemSubtotal(item).toFixed(2)}
                                                </div>
                                            </div>

                                            <div className="md:col-span-1 flex items-center justify-end">
                                                {quotationItems.length > 1 && (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        className="text-red-500"
                                                        onClick={() => removeQuotationItem(index)}
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                )}
                                            </div>
                                        </div>

                                        {isMedicalDeferredQuotation && (
                                            <Collapsible
                                                open={openCoverageConfigurationIndex === index}
                                                onOpenChange={(isOpen) =>
                                                    setOpenCoverageConfigurationIndex(isOpen ? index : null)
                                                }
                                                className="rounded-lg border border-dashed border-slate-300 bg-white"
                                            >
                                                <CollapsibleTrigger asChild>
                                                    <button
                                                        type="button"
                                                        className="group flex w-full items-center justify-between px-4 py-3 text-left"
                                                    >
                                                        <div>
                                                            <div className="text-sm font-semibold text-slate-900">
                                                                Coverage Configuration
                                                            </div>
                                                            <p className="text-xs text-muted-foreground">
                                                                Limit per family and contribution per family are editable, and total contribution updates from members.
                                                            </p>
                                                        </div>
                                                        <ChevronDown className="h-4 w-4 text-slate-500 transition-transform group-data-[state=open]:rotate-180" />
                                                    </button>
                                                </CollapsibleTrigger>

                                                <CollapsibleContent className="border-t border-slate-200 px-4 py-4">
                                                    <div className="grid grid-cols-1 gap-3 xl:grid-cols-2">
                                                        {MEDICAL_COVERAGE_SECTIONS.map((section) => {
                                                            const coverageConfiguration =
                                                                item.coverage_configuration?.[section.key] ??
                                                                createEmptyCoverageEntry();

                                                            return (
                                                                <div
                                                                    key={section.key}
                                                                    className="rounded-md border bg-slate-50 p-4"
                                                                >
                                                                    <h4 className="text-sm font-semibold text-slate-900">
                                                                        {section.label}
                                                                    </h4>

                                                                    <div className="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                                                                        <div>
                                                                            <Label>Limit Per Family</Label>
                                                                            <Input
                                                                                type="number"
                                                                                step="0.01"
                                                                                min="0"
                                                                                value={coverageConfiguration.limit_per_family}
                                                                                onChange={(e) =>
                                                                                    updateCoverageConfiguration(
                                                                                        index,
                                                                                        section.key,
                                                                                        "limit_per_family",
                                                                                        parseNumericValue(e.target.value)
                                                                                    )
                                                                                }
                                                                            />
                                                                        </div>

                                                                        <div>
                                                                            <Label>Contribution Per Family</Label>
                                                                            <Input
                                                                                type="number"
                                                                                step="0.01"
                                                                                value={coverageConfiguration.contribution_per_family}
                                                                                onChange={(e) =>
                                                                                    updateCoverageConfiguration(
                                                                                        index,
                                                                                        section.key,
                                                                                        "contribution_per_family",
                                                                                        parseNumericValue(e.target.value)
                                                                                    )
                                                                                }
                                                                            />
                                                                        </div>

                                                                        <div>
                                                                            <Label>Auto-calculated Total Contribution</Label>
                                                                            <Input
                                                                                type="number"
                                                                                step="0.01"
                                                                                value={coverageConfiguration.total_contribution}
                                                                                disabled
                                                                                readOnly
                                                                            />
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            );
                                                        })}
                                                    </div>
                                                </CollapsibleContent>
                                            </Collapsible>
                                        )}
                                    </div>
                                ))
                            )}
                        </div>

                        <SidebarSeparator className="my-4" />

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="coverage_summary" className="md:col-span-2 col-span-12">
                                Coverage Summary
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea
                                    id="coverage_summary"
                                    value={data.coverage_summary}
                                    onChange={(e) => setData("coverage_summary", e.target.value)}
                                    className="md:w-1/2 w-full"
                                    rows={4}
                                />
                                <InputError message={errors.coverage_summary} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="exclusions_remarks" className="md:col-span-2 col-span-12">
                                Exclusions and Remarks
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea
                                    id="exclusions_remarks"
                                    value={data.exclusions_remarks}
                                    onChange={(e) => setData("exclusions_remarks", e.target.value)}
                                    className="md:w-1/2 w-full"
                                    rows={4}
                                />
                                <InputError message={errors.exclusions_remarks} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="taxes" className="md:col-span-2 col-span-12">
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
                                        onChange={(values) => setData("taxes", values)}
                                        placeholder="Select taxes"
                                    />
                                </div>
                                <InputError message={errors.taxes} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="discount_type" className="md:col-span-2 col-span-12">
                                Discount Type
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={discountTypeOptions}
                                        value={data.discount_type}
                                        onChange={(value) => setData("discount_type", value)}
                                        placeholder="Select discount type"
                                    />
                                </div>
                                <InputError message={errors.discount_type} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="discount_value" className="md:col-span-2 col-span-12">
                                Discount Value
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="discount_value"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.discount_value}
                                    onChange={(e) => setData("discount_value", parseNumericValue(e.target.value))}
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.discount_value} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="note" className="md:col-span-2 col-span-12">
                                Note
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea
                                    id="note"
                                    value={data.note}
                                    onChange={(e) => setData("note", e.target.value)}
                                    className="md:w-1/2 w-full"
                                    rows={4}
                                />
                                <InputError message={errors.note} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="footer" className="md:col-span-2 col-span-12">
                                Footer
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea
                                    id="footer"
                                    value={data.footer}
                                    onChange={(e) => setData("footer", e.target.value)}
                                    className="md:w-1/2 w-full"
                                    rows={4}
                                />
                                <InputError message={errors.footer} className="text-sm" />
                            </div>
                        </div>

                        <div className="mt-6 space-y-2">
                            <div className="space-y-2">
                                <div className="text-sm">Subtotal: {calculateSubtotal().toFixed(2)}</div>
                                <div className="text-sm">Taxes: {calculateTaxes().toFixed(2)}</div>
                                <div className="text-sm">Discount: {calculateDiscount().toFixed(2)}</div>
                                {renderTotal()}
                            </div>

                            <div className="space-x-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => {
                                        reset();
                                        setQuotationItems([createEmptyQuotationItem()]);
                                        setOpenCoverageConfigurationIndex(null);
                                    }}
                                >
                                    Reset
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Create Quotation
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
