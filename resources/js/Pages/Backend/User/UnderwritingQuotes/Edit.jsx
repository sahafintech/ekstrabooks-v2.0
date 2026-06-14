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
import { parseDateObject } from "@/lib/utils";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import DateTimePicker from "@/Components/DateTimePicker";
import QuoteItemsEditor from "./QuoteItemsEditor";
import {
    getDefaultRateType,
    createEmptyQuotationItem,
    sectionsFromTemplate,
} from "@/lib/quote-form-utils";
import { useQuoteForm } from "@/hooks/useQuoteForm";

// ── Helpers to hydrate saved data back into form shape ────────────────────────

const formatQuotationItem = (item = {}) => {
    const metadata        = item.metadata_json ?? {};
    const calculationType = item.calculation_type ?? metadata.calculation_type ?? "manual_premium";
    return {
        product_id:       item.product_id ?? "",
        product_name:     item.product_name ?? "",
        description:      item.description ?? "",
        rating_rule_id:   item.rating_rule_id ?? metadata.rating_rule_id ?? "",
        calculation_type: calculationType,
        rate_type:        item.rate_type ?? metadata.rate_type ?? getDefaultRateType(calculationType),
        rate_value:       item.rate_value ?? item.unit_cost ?? 0,
        basis_amount:     item.basis_amount ?? metadata.sum_insured ?? "",
        basis_quantity:   item.basis_quantity ?? item.quantity ?? 1,
        minimum_premium:  item.minimum_premium ?? "",
        quantity:         item.quantity ?? item.basis_quantity ?? 1,
        unit_cost:        item.unit_cost ?? item.rate_value ?? 0,
    };
};

const getInitialQuotationItems = (quotation) =>
    quotation?.items?.length
        ? quotation.items.map((item) => formatQuotationItem(item))
        : [createEmptyQuotationItem()];

const formatSavedSections = (savedSections = []) =>
    savedSections.map((section, index) => {
        const dataJson = section.data_json ?? {};
        const columns  = dataJson.columns ?? [""];
        return {
            id:                            section.id,
            insurance_category_section_id: section.insurance_category_section_id ?? null,
            title:      section.title ?? "",
            type:       section.type ?? "fields",
            sort_order: section.sort_order ?? index,
            fields:     dataJson.fields ?? [],
            columns,
            rows:       dataJson.rows && dataJson.rows.length > 0
                ? dataJson.rows
                : [Array(Math.max(columns.length, 1)).fill("")],
            content:    section.content ?? "",
        };
    });

const getInitialSections = (quotation, insuranceCategories = []) => {
    if (quotation?.sections?.length) return formatSavedSections(quotation.sections);
    const category = insuranceCategories.find((c) => String(c.id) === String(quotation?.insurance_category_id));
    return sectionsFromTemplate(category?.sections);
};

const buildInitialFormData = (quotation, taxIds, initialSections) => {
    const initialItems = getInitialQuotationItems(quotation);
    return {
        customer_id:           quotation.customer_id,
        insurance_category_id: quotation.insurance_category_id ?? "",
        title:                 quotation.title,
        quotation_number:      quotation.quotation_number,
        quotation_date:        parseDateObject(quotation.quotation_date),
        expired_date:          parseDateObject(quotation.expired_date),
        currency:              quotation.currency,
        exchange_rate:         quotation.exchange_rate,
        converted_total:       0,
        discount_type:         String(quotation.discount_type ?? "0"),
        discount_value:        quotation.discount_value ?? 0,
        template:              quotation.template,
        note:                  quotation.note,
        footer:                quotation.footer,
        attachment:            null,
        product_id:            initialItems.map((i) => i.product_id),
        product_name:          initialItems.map((i) => i.product_name),
        description:           initialItems.map((i) => i.description),
        rating_rule_id:        initialItems.map((i) => i.rating_rule_id ?? ""),
        calculation_type:      initialItems.map((i) => i.calculation_type),
        rate_type:             initialItems.map((i) => i.rate_type),
        rate_value:            initialItems.map((i) => i.rate_value),
        basis_amount:          initialItems.map((i) => i.basis_amount),
        basis_quantity:        initialItems.map((i) => i.basis_quantity || i.quantity || 1),
        minimum_premium:       initialItems.map((i) => i.minimum_premium),
        quantity:              initialItems.map((i) => i.quantity),
        unit_cost:             initialItems.map((i) => i.unit_cost),
        sections:              initialSections,
        taxes:                 taxIds ?? [],
        _method:               "PUT",
    };
};

// ── Component ─────────────────────────────────────────────────────────────────

export default function Edit({
    customers = [],
    products = [],
    currencies = [],
    taxes = [],
    taxIds,
    quotation,
    insuranceCategories = [],
    ratingRules = [],
}) {
    const initialQuotationItems = getInitialQuotationItems(quotation);
    const initialSections       = getInitialSections(quotation, insuranceCategories);
    const initialFormData       = buildInitialFormData(quotation, taxIds, initialSections);

    const { data, setData, post, processing, errors, reset } = useForm(initialFormData);

    const {
        quotationItems,
        setQuotationItems,
        sections,
        syncSections,
        setExchangeRate,
        handleInsuranceCategoryChange,
        addItem,
        removeItem,
        updateItem,
        calculateSubtotal,
        calculateTaxes,
        calculateDiscount,
        handleCurrencyChange,
        renderTotal,
    } = useQuoteForm({
        data,
        setData,
        products,
        currencies,
        taxes,
        insuranceCategories,
        ratingRules,
        initialItems:        initialQuotationItems,
        initialSections,
        initialExchangeRate: quotation.exchange_rate ?? 1,
    });

    const customerOptions          = customers.map((c) => ({ id: c.id, name: c.name }));
    const insuranceCategoryOptions = insuranceCategories.map((c) => ({ id: c.id, name: c.name }));
    const currencyOptions          = currencies.map((c) => ({
        id: c.name, value: c.name, label: c.name,
        name: `${c.name} - ${c.description} (${c.exchange_rate})`,
    }));
    const discountTypeOptions = [
        { id: "0", name: "Percentage (%)" },
        { id: "1", name: "Fixed Amount" },
    ];

    const submit = (e) => {
        e.preventDefault();
        const selected = currencies.find((c) => c.name === data.currency);
        if (!selected)                   { toast.error("Please select a valid currency"); return; }
        if (!data.insurance_category_id) { toast.error("Please select an insurance category"); return; }

        post(route("underwriting_quotes.update", quotation.id), {
            preserveScroll: true,
            onSuccess: () => toast.success("Underwriting quote updated successfully"),
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
                            <Label htmlFor="insurance_category_id" className="md:col-span-2 col-span-12">Insurance Category</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={insuranceCategoryOptions}
                                        value={data.insurance_category_id}
                                        onChange={handleInsuranceCategoryChange}
                                        placeholder="Select category"
                                        emptyMessage="No categories found. Add them in Underwriting Configuration."
                                    />
                                </div>
                                <p className="text-xs text-gray-400 mt-0.5">Changing the category will reload sections from the new template.</p>
                                <InputError message={errors.insurance_category_id} className="text-sm" />
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
                                    <SearchableCombobox
                                        className="mt-1"
                                        options={currencyOptions}
                                        value={data.currency}
                                        onChange={(v) => { setData("currency", v); handleCurrencyChange(v); }}
                                        placeholder="Select currency"
                                    />
                                </div>
                                <InputError message={errors.currency} className="text-sm" />
                            </div>
                        </div>

                        <SidebarSeparator className="my-4" />

                        <QuoteItemsEditor
                            quotationItems={quotationItems}
                            sections={sections}
                            insuranceCategoryId={data.insurance_category_id}
                            products={products}
                            ratingRules={ratingRules}
                            onAddItem={addItem}
                            onRemoveItem={removeItem}
                            onUpdateItem={updateItem}
                            onSectionsUpdate={syncSections}
                        />

                        <SidebarSeparator className="my-4" />

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="taxes" className="md:col-span-2 col-span-12">Tax</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableMultiSelectCombobox
                                        options={taxes?.map((t) => ({ id: t.id, name: `${t.name} (${t.rate}%)` }))}
                                        value={data.taxes}
                                        onChange={(v) => setData("taxes", v)}
                                        placeholder="Select taxes"
                                    />
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
                                <Input id="discount_value" type="number" step="0.01" min="0" value={data.discount_value} onChange={(e) => setData("discount_value", parseFloat(e.target.value) || 0)} className="md:w-1/2 w-full" />
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
                                        setQuotationItems(initialQuotationItems);
                                        syncSections(initialSections);
                                        setExchangeRate(quotation.exchange_rate ?? 1);
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
