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
import { formatCurrency, parseDateObject } from "@/lib/utils";
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
    if (value === "" || value === null || value === undefined) return "";
    const parsedValue = parseFloat(value);
    return Number.isNaN(parsedValue) ? "" : parsedValue;
};

const createEmptyCoverageEntry = () => ({
    limit_per_family: "",
    contribution_per_family: "",
    total_contribution: "",
});

const createEmptyCoverageConfiguration = () =>
    MEDICAL_COVERAGE_SECTIONS.reduce((cfg, section) => {
        cfg[section.key] = createEmptyCoverageEntry();
        return cfg;
    }, {});

const calculateCoverageTotalContribution = (members, contributionPerFamily) => {
    if (contributionPerFamily === "" || contributionPerFamily === null || contributionPerFamily === undefined) return "";
    return (Number(members) || 0) * (Number(contributionPerFamily) || 0);
};

const normalizeMedicalCoverageConfiguration = (coverageConfiguration = {}, members = 0) =>
    MEDICAL_COVERAGE_SECTIONS.reduce((cfg, section) => {
        const sv = coverageConfiguration?.[section.key] ?? {};
        const cpf = parseNumericValue(sv.contribution_per_family);
        cfg[section.key] = {
            limit_per_family: parseNumericValue(sv.limit_per_family),
            contribution_per_family: cpf,
            total_contribution: calculateCoverageTotalContribution(members, cpf),
        };
        return cfg;
    }, {});

const calculateMedicalCoverageRate = (coverageConfiguration = {}) =>
    MEDICAL_COVERAGE_SECTIONS.reduce(
        (sum, section) => sum + (Number(coverageConfiguration?.[section.key]?.total_contribution) || 0),
        0
    );

const parseFamilySizeValue = (familySize) => {
    if (!familySize) return null;
    const n = String(familySize).trim().toUpperCase();
    const m = n.match(/^M\s*\+\s*(\d+(?:\.\d+)?)$/);
    if (m) return 1 + Number(m[1]);
    if (/^\d+(?:\.\d+)?$/.test(n)) return Number(n);
    const c = n.match(/^(\d+(?:\.\d+)?)\s*\+\s*(\d+(?:\.\d+)?)$/);
    if (c) return Number(c[1]) + Number(c[2]);
    return null;
};

const calculateMembersAndFamilyValue = (members, familySize) => {
    if (members === "" || members === null || members === undefined || !familySize) return "";
    const fsv = parseFamilySizeValue(familySize);
    if (fsv === null) return "";
    return (Number(members) || 0) * fsv;
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

const categoryOptions = [
    { id: "medical", name: "Medical Insurance" },
    { id: "other", name: "General Insurance" },
];

const formatQuotationItem = (item = {}) => ({
    product_id: item.product_id ?? "",
    product_name: item.product_name ?? "",
    description: item.description ?? "",
    quantity: item.quantity ?? 1,
    unit_cost: item.unit_cost ?? 0,
    family_size: item.family_size ?? "",
    sum_insured: item.sum_insured ?? "",
    coverage_configuration: normalizeMedicalCoverageConfiguration(
        {
            inpatient: {
                limit_per_family: item.inpatient_limit_per_family,
                contribution_per_family: item.inpatient_contribution_per_family,
            },
            maternity: {
                limit_per_family: item.maternity_limit_per_family,
                contribution_per_family: item.maternity_contribution_per_family,
            },
            outpatient: {
                limit_per_family: item.outpatient_limit_per_family,
                contribution_per_family: item.outpatient_contribution_per_family,
            },
            dental: {
                limit_per_family: item.dental_limit_per_family,
                contribution_per_family: item.dental_contribution_per_family,
            },
            optical: {
                limit_per_family: item.optical_limit_per_family,
                contribution_per_family: item.optical_contribution_per_family,
            },
            telemedicine: {
                limit_per_family: item.telemedicine_limit_per_family,
                contribution_per_family: item.telemedicine_contribution_per_family,
            },
        },
        item.quantity ?? 1
    ),
});

const getInitialQuotationItems = (quotation) =>
    quotation?.items?.length
        ? quotation.items.map((item) => {
              const formattedItem = formatQuotationItem(item);
              return quotation?.invoice_category === "medical"
                  ? { ...formattedItem, unit_cost: calculateMedicalCoverageRate(formattedItem.coverage_configuration) }
                  : formattedItem;
          })
        : [createEmptyQuotationItem()];

const buildInitialFormData = (quotation, taxIds) => {
    const initialItems = getInitialQuotationItems(quotation);
    return {
        customer_id: quotation.customer_id,
        title: quotation.title,
        quotation_number: quotation.quotation_number,
        po_so_number: quotation.po_so_number ?? quotation.order_number ?? "",
        quotation_date: parseDateObject(quotation.quotation_date),
        expired_date: parseDateObject(quotation.expired_date),
        currency: quotation.currency,
        exchange_rate: quotation.exchange_rate,
        converted_total: 0,
        discount_type: String(quotation.discount_type ?? "0"),
        discount_value: quotation.discount_value ?? 0,
        template: quotation.template,
        note: quotation.note,
        footer: quotation.footer,
        exclusions_remarks: quotation.exclusions_remarks || quotation.footer || "",
        coverage_summary: quotation.coverage_summary || quotation.note || "",
        attachment: null,
        invoice_category: quotation.invoice_category ?? "",
        product_id: initialItems.map((i) => i.product_id),
        product_name: initialItems.map((i) => i.product_name),
        description: initialItems.map((i) => i.description),
        quantity: initialItems.map((i) => i.quantity),
        unit_cost: initialItems.map((i) => i.unit_cost),
        family_size: initialItems.map((i) => i.family_size),
        sum_insured: initialItems.map((i) => i.sum_insured),
        coverage_configuration: initialItems.map((i) => i.coverage_configuration),
        taxes: taxIds ?? [],
        _method: "PUT",
    };
};

export default function Edit({
    customers = [],
    products = [],
    currencies = [],
    taxes = [],
    taxIds,
    quotation,
    familySizes = [],
}) {
    const initialQuotationItems = getInitialQuotationItems(quotation);
    const initialFormData = buildInitialFormData(quotation, taxIds);

    const [quotationItems, setQuotationItems] = useState(initialQuotationItems);
    const [openCoverageIndex, setOpenCoverageIndex] = useState(
        quotation?.invoice_category === "medical" && initialQuotationItems.length > 0 ? 0 : null
    );
    const [exchangeRate, setExchangeRate] = useState(quotation.exchange_rate ?? 1);
    const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);

    const { data, setData, post, processing, errors, reset } = useForm(initialFormData);

    const isMedical = data.invoice_category === "medical";
    const isOther   = data.invoice_category === "other";
    const canManageItems = data.invoice_category !== "";

    const gridClassName = isMedical
        ? "grid grid-cols-1 md:grid-cols-8 gap-2 items-end"
        : isOther
            ? "grid grid-cols-1 md:grid-cols-7 gap-2 items-end"
            : "grid grid-cols-1 md:grid-cols-6 gap-2 items-end";

    const customerOptions   = customers.map((c) => ({ id: c.id, name: c.name }));
    const productOptions    = products.map((p) => ({ id: p.id, name: p.name }));
    const familySizeOptions = familySizes.map((s) => ({ id: s.size, name: s.size }));
    const currencyOptions   = currencies.map((c) => ({
        id: c.name, value: c.name, label: c.name,
        name: `${c.name} - ${c.description} (${c.exchange_rate})`,
    }));
    const discountTypeOptions = [
        { id: "0", name: "Percentage (%)" },
        { id: "1", name: "Fixed Amount" },
    ];

    const syncItems = (items) => {
        setData("product_id", items.map((i) => i.product_id));
        setData("product_name", items.map((i) => i.product_name));
        setData("description", items.map((i) => i.description));
        setData("quantity", items.map((i) => i.quantity));
        setData("unit_cost", items.map((i) => i.unit_cost));
        setData("family_size", items.map((i) => i.family_size));
        setData("sum_insured", items.map((i) => i.sum_insured));
        setData("coverage_configuration", items.map((i) => i.coverage_configuration));
    };

    const sanitizeItems = (items, category) =>
        items.map((item) => ({
            ...item,
            family_size: category === "medical" ? item.family_size ?? "" : "",
            sum_insured: category === "other" ? item.sum_insured ?? "" : "",
            coverage_configuration:
                category === "medical"
                    ? normalizeMedicalCoverageConfiguration(item.coverage_configuration, item.quantity)
                    : createEmptyCoverageConfiguration(),
        }));

    const updateItems = (items) => {
        const normalized = items
            .map((item) => ({
                ...item,
                coverage_configuration: normalizeMedicalCoverageConfiguration(item.coverage_configuration, item.quantity),
            }))
            .map((item) => ({
                ...item,
                unit_cost: isMedical ? calculateMedicalCoverageRate(item.coverage_configuration) : item.unit_cost,
            }));
        setQuotationItems(normalized);
        syncItems(normalized);
    };

    const calculateItemRate = (item) =>
        isMedical ? calculateMedicalCoverageRate(item.coverage_configuration) : Number(item.unit_cost) || 0;

    const calculateItemSubtotal = (item) => {
        if (isMedical) return calculateItemRate(item);
        if (isOther) return ((Number(item.unit_cost) || 0) / 100) * (Number(item.sum_insured) || 0);
        return (Number(item.quantity) || 0) * (Number(item.unit_cost) || 0);
    };

    const autoResizeTextarea = (event, onChange) => {
        onChange(event.target.value);
        event.target.style.height = "auto";
        event.target.style.height = `${event.target.scrollHeight}px`;
    };

    useEffect(() => {
        if (!isMedical || quotationItems.length === 0) { setOpenCoverageIndex(null); return; }
        setOpenCoverageIndex((ci) => (ci !== null && ci < quotationItems.length ? ci : 0));
    }, [isMedical, quotationItems.length]);

    const addItem = () => {
        const updated = [...quotationItems, createEmptyQuotationItem()];
        updateItems(updated);
        if (isMedical) setOpenCoverageIndex(updated.length - 1);
    };

    const removeItem = (index) => {
        const updated = quotationItems.filter((_, i) => i !== index);
        updateItems(updated);
        setOpenCoverageIndex((ci) => {
            if (ci === null) return null;
            if (ci === index) return updated.length > 0 ? Math.max(0, index - 1) : null;
            return ci > index ? ci - 1 : ci;
        });
    };

    const handleCategoryChange = (value) => {
        setData("invoice_category", value);
        updateItems(sanitizeItems(quotationItems, value));
    };

    const updateItem = (index, field, value) => {
        const updated = [...quotationItems];
        updated[index] = { ...updated[index], [field]: value };

        if (field === "product_id") {
            const product = products.find((p) => p.id === parseInt(value, 10));
            if (product) {
                updated[index].product_name = product.name;
                if (!isMedical) updated[index].unit_cost = product.selling_price;
                if (!updated[index].description) updated[index].description = product.description || "";
            }
        }
        updateItems(updated);
    };

    const updateCoverage = (index, sectionKey, field, value) => {
        const updated = [...quotationItems];
        const current = updated[index].coverage_configuration ?? createEmptyCoverageConfiguration();
        updated[index] = {
            ...updated[index],
            coverage_configuration: {
                ...current,
                [sectionKey]: { ...(current[sectionKey] ?? createEmptyCoverageEntry()), [field]: value },
            },
        };
        updateItems(updated);
    };

    const calculateSubtotal = () => quotationItems.reduce((s, item) => s + calculateItemSubtotal(item), 0);
    const taxRateMap = new Map(taxes.map((t) => [t.id, Number(t.rate)]));

    const calculateTaxes = () =>
        quotationItems.reduce((sum, item) => {
            const base = calculateItemSubtotal(item);
            return sum + data.taxes.reduce((ts, id) => ts + (base * (taxRateMap.get(Number(id)) || 0)) / 100, 0);
        }, 0);

    const calculateDiscount = () => {
        const sub = calculateSubtotal();
        const dv  = Number(data.discount_value) || 0;
        return data.discount_type === "0" ? (sub * dv) / 100 : dv;
    };

    const calculateTotal = () => calculateSubtotal() + calculateTaxes() - calculateDiscount();

    useEffect(() => {
        let baseC = currencies.find((c) => c.base_currency === 1) || currencies[0];
        if (baseC) setBaseCurrencyInfo(baseC);
    }, [currencies]);

    const handleCurrencyChange = (currencyName) => {
        const currencyObj = currencies.find((c) => c.name === currencyName);
        if (currencyObj) {
            const rate = parseFloat(currencyObj.exchange_rate);
            setExchangeRate(rate);
            setData("exchange_rate", rate);
            fetch(`/user/find_currency/${currencyObj.name}`)
                .then((r) => r.json())
                .then((d) => { if (d?.exchange_rate) { const ar = parseFloat(d.exchange_rate); setExchangeRate(ar); setData("exchange_rate", ar); } })
                .catch(() => {});
        }
    };

    useEffect(() => {
        setData("converted_total", calculateTotal());
    }, [data.currency, quotationItems, data.discount_type, data.discount_value, exchangeRate]);

    const renderTotal = () => {
        const total    = calculateTotal();
        const selected = currencies.find((c) => c.name === data.currency);
        if (!selected) return <h2 className="text-xl font-bold">Total: 0.00</h2>;
        if (baseCurrencyInfo && selected.name !== baseCurrencyInfo.name && exchangeRate && exchangeRate !== 1) {
            return (
                <div>
                    <h2 className="text-xl font-bold">Total: {formatCurrency({ amount: total, currency: selected.name })}</h2>
                    <p className="text-sm text-gray-600">Equivalent to {formatCurrency({ amount: total / exchangeRate, currency: baseCurrencyInfo.name })}</p>
                </div>
            );
        }
        return <h2 className="text-xl font-bold">Total: {formatCurrency({ amount: total, currency: selected.name })}</h2>;
    };

    const submit = (e) => {
        e.preventDefault();
        const selected = currencies.find((c) => c.name === data.currency);
        if (!selected) { toast.error("Please select a valid currency"); return; }
        if (!data.invoice_category) { toast.error("Please select an insurance category"); return; }

        post(route("underwriting_quotes.update", quotation.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Underwriting quote updated successfully");
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Underwriting Quotes" subpage="Edit" url="underwriting_quotes.index" />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="customer_id" className="md:col-span-2 col-span-12">Customer *</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox options={customerOptions} value={data.customer_id} onChange={(v) => setData("customer_id", v)} placeholder="Select customer" />
                                </div>
                                <InputError message={errors.customer_id} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="title" className="md:col-span-2 col-span-12">Title *</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input id="title" type="text" value={data.title} onChange={(e) => setData("title", e.target.value)} className="md:w-1/2 w-full" required />
                                <InputError message={errors.title} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="invoice_category" className="md:col-span-2 col-span-12">Insurance Category *</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox options={categoryOptions} value={data.invoice_category} onChange={handleCategoryChange} placeholder="Select insurance category" />
                                </div>
                                <InputError message={errors.invoice_category} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="po_so_number" className="md:col-span-2 col-span-12">Policy Number</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input id="po_so_number" type="text" value={data.po_so_number} onChange={(e) => setData("po_so_number", e.target.value)} className="md:w-1/2 w-full" />
                                <InputError message={errors.po_so_number} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="quotation_date" className="md:col-span-2 col-span-12">Quotation Date *</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker value={data.quotation_date} onChange={(d) => setData("quotation_date", d)} className="md:w-1/2 w-full" required />
                                <InputError message={errors.quotation_date} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="expired_date" className="md:col-span-2 col-span-12">Expired Date *</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker value={data.expired_date} onChange={(d) => setData("expired_date", d)} className="md:w-1/2 w-full" required />
                                <InputError message={errors.expired_date} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="currency" className="md:col-span-2 col-span-12">Currency *</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox className="mt-1" options={currencyOptions} value={data.currency} onChange={(v) => { setData("currency", v); handleCurrencyChange(v); }} placeholder="Select currency" />
                                </div>
                                <InputError message={errors.currency} className="text-sm" />
                            </div>
                        </div>

                        <SidebarSeparator className="my-4" />

                        <div className="space-y-4">
                            <div className="flex justify-between items-center">
                                <h3 className="text-lg font-medium">Quote Items</h3>
                                <Button variant="secondary" type="button" onClick={addItem} disabled={!canManageItems}>
                                    <Plus className="w-4 h-4 mr-2" />Add Item
                                </Button>
                            </div>

                            {!data.invoice_category ? (
                                <div className="border rounded-lg p-4 bg-gray-50 text-sm text-muted-foreground">
                                    Select an insurance category above to unlock item fields.
                                </div>
                            ) : (
                                quotationItems.map((item, index) => (
                                    <div key={index} className="border rounded-lg p-4 space-y-4 bg-gray-50">
                                        <div className={gridClassName}>
                                            <div>
                                                <Label>Product *</Label>
                                                <SearchableCombobox options={productOptions} value={item.product_id} onChange={(v) => updateItem(index, "product_id", v)} placeholder="Select product" />
                                            </div>

                                            <div>
                                                <Label>{isMedical ? "Members *" : "Quantity *"}</Label>
                                                <Input type="number" step="0.01" value={item.quantity} onChange={(e) => updateItem(index, "quantity", parseNumericValue(e.target.value))} />
                                            </div>

                                            {isMedical && (
                                                <div>
                                                    <Label>Family Size *</Label>
                                                    <SearchableCombobox options={familySizeOptions} value={item.family_size} onChange={(v) => updateItem(index, "family_size", v)} placeholder="Select family size" />
                                                </div>
                                            )}

                                            {isMedical && (
                                                <div>
                                                    <Label>Members + Family</Label>
                                                    <Input type="number" step="0.01" value={calculateMembersAndFamilyValue(item.quantity, item.family_size)} disabled readOnly />
                                                </div>
                                            )}

                                            <div>
                                                <Label>{isOther ? "Rate (%) *" : "Rate *"}</Label>
                                                <Input type="number" step="0.01" value={calculateItemRate(item)} onChange={(e) => updateItem(index, "unit_cost", parseNumericValue(e.target.value))} disabled={isMedical} readOnly={isMedical} />
                                            </div>

                                            {isOther && (
                                                <div>
                                                    <Label>Sum Insured</Label>
                                                    <Input type="number" step="0.01" value={item.sum_insured} onChange={(e) => updateItem(index, "sum_insured", parseNumericValue(e.target.value))} />
                                                </div>
                                            )}

                                            <div>
                                                <Label>Description</Label>
                                                <Textarea value={item.description} onChange={(e) => autoResizeTextarea(e, (v) => updateItem(index, "description", v))} className="min-h-[30px] resize-none overflow-hidden" rows={1} />
                                            </div>

                                            <div>
                                                <Label>Subtotal</Label>
                                                <div className="p-2 bg-white rounded text-right">{calculateItemSubtotal(item).toFixed(2)}</div>
                                            </div>

                                            <div className="md:col-span-1 flex items-center justify-end">
                                                {quotationItems.length > 1 && (
                                                    <Button type="button" variant="ghost" size="icon" className="text-red-500" onClick={() => removeItem(index)}>
                                                        <Trash2 className="w-4 h-4" />
                                                    </Button>
                                                )}
                                            </div>
                                        </div>

                                        {isMedical && (
                                            <Collapsible
                                                open={openCoverageIndex === index}
                                                onOpenChange={(open) => setOpenCoverageIndex(open ? index : null)}
                                                className="rounded-lg border border-dashed border-slate-300 bg-white"
                                            >
                                                <CollapsibleTrigger asChild>
                                                    <button type="button" className="group flex w-full items-center justify-between px-4 py-3 text-left">
                                                        <div>
                                                            <div className="text-sm font-semibold text-slate-900">Coverage Configuration</div>
                                                            <p className="text-xs text-muted-foreground">Limit per family and contribution per family are editable; total contribution updates from members.</p>
                                                        </div>
                                                        <ChevronDown className="h-4 w-4 text-slate-500 transition-transform group-data-[state=open]:rotate-180" />
                                                    </button>
                                                </CollapsibleTrigger>

                                                <CollapsibleContent className="border-t border-slate-200 px-4 py-4">
                                                    <div className="grid grid-cols-1 gap-3 xl:grid-cols-2">
                                                        {MEDICAL_COVERAGE_SECTIONS.map((section) => {
                                                            const cfg = item.coverage_configuration?.[section.key] ?? createEmptyCoverageEntry();
                                                            return (
                                                                <div key={section.key} className="rounded-md border bg-slate-50 p-4">
                                                                    <h4 className="text-sm font-semibold text-slate-900">{section.label}</h4>
                                                                    <div className="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                                                                        <div>
                                                                            <Label>Limit Per Family</Label>
                                                                            <Input type="number" step="0.01" min="0" value={cfg.limit_per_family} onChange={(e) => updateCoverage(index, section.key, "limit_per_family", parseNumericValue(e.target.value))} />
                                                                        </div>
                                                                        <div>
                                                                            <Label>Contribution Per Family</Label>
                                                                            <Input type="number" step="0.01" value={cfg.contribution_per_family} onChange={(e) => updateCoverage(index, section.key, "contribution_per_family", parseNumericValue(e.target.value))} />
                                                                        </div>
                                                                        <div>
                                                                            <Label>Auto-calculated Total</Label>
                                                                            <Input type="number" step="0.01" value={cfg.total_contribution} disabled readOnly />
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
                            <Label htmlFor="coverage_summary" className="md:col-span-2 col-span-12">Coverage Summary</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea id="coverage_summary" value={data.coverage_summary} onChange={(e) => setData("coverage_summary", e.target.value)} className="md:w-1/2 w-full" rows={4} />
                                <InputError message={errors.coverage_summary} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="exclusions_remarks" className="md:col-span-2 col-span-12">Exclusions and Remarks</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea id="exclusions_remarks" value={data.exclusions_remarks} onChange={(e) => setData("exclusions_remarks", e.target.value)} className="md:w-1/2 w-full" rows={4} />
                                <InputError message={errors.exclusions_remarks} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="taxes" className="md:col-span-2 col-span-12">Tax</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableMultiSelectCombobox options={taxes?.map((t) => ({ id: t.id, name: `${t.name} (${t.rate}%)` }))} value={data.taxes} onChange={(v) => setData("taxes", v)} placeholder="Select taxes" />
                                </div>
                                <InputError message={errors.taxes} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="discount_type" className="md:col-span-2 col-span-12">Discount Type</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox options={discountTypeOptions} value={data.discount_type} onChange={(v) => setData("discount_type", v)} placeholder="Select discount type" />
                                </div>
                                <InputError message={errors.discount_type} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="discount_value" className="md:col-span-2 col-span-12">Discount Value</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input id="discount_value" type="number" step="0.01" min="0" value={data.discount_value} onChange={(e) => setData("discount_value", parseNumericValue(e.target.value))} className="md:w-1/2 w-full" />
                                <InputError message={errors.discount_value} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="note" className="md:col-span-2 col-span-12">Note</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea id="note" value={data.note} onChange={(e) => setData("note", e.target.value)} className="md:w-1/2 w-full" rows={4} />
                                <InputError message={errors.note} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="footer" className="md:col-span-2 col-span-12">Footer</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea id="footer" value={data.footer} onChange={(e) => setData("footer", e.target.value)} className="md:w-1/2 w-full" rows={4} />
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
                                        setQuotationItems(getInitialQuotationItems(quotation));
                                        setExchangeRate(quotation.exchange_rate ?? 1);
                                        setOpenCoverageIndex(
                                            quotation?.invoice_category === "medical" && initialQuotationItems.length > 0 ? 0 : null
                                        );
                                    }}
                                >
                                    Reset
                                </Button>
                                <Button type="submit" disabled={processing}>Update Underwriting Quote</Button>
                            </div>
                        </div>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
