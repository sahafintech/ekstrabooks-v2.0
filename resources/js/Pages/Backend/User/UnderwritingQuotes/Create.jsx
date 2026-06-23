import { useEffect } from "react";
import { useForm, usePage } from "@inertiajs/react";
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
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import DateTimePicker from "@/Components/DateTimePicker";
import QuoteItemsEditor from "./QuoteItemsEditor";
import { createEmptyQuotationItem } from "@/lib/quote-form-utils";
import { useQuoteForm } from "@/hooks/useQuoteForm";

export default function Create({
    customers = [],
    products = [],
    currencies = [],
    taxes = [],
    insuranceCategories = [],
    ratingRules = [],
    quotation_title,
    base_currency,
}) {
    const { flash = {} } = usePage().props;

    const { data, setData, post, processing, errors, reset } = useForm({
        customer_id:           "",
        insurance_category_id: "",
        title:                 quotation_title,
        quotation_number:      "",
        quotation_date:        new Date(),
        expired_date:          "",
        currency:              base_currency,
        exchange_rate:         1,
        converted_total:       0,
        discount_type:         "0",
        discount_value:        0,
        template:              "",
        note:                  "",
        footer:                "",
        attachment:            null,
        product_id:            [],
        product_name:          [],
        description:           [],
        rating_rule_id:        [],
        calculation_type:      [],
        rate_type:             [],
        rate_value:            [],
        basis_amount:          [],
        basis_quantity:        [],
        quantity:              [],
        unit_cost:             [],
        sections:              [],
        taxes:                 [],
    });

    const {
        quotationItems,
        setQuotationItems,
        sections,
        syncSections,
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
        initialItems:     [createEmptyQuotationItem()],
        initialSections:  [],
        initialExchangeRate: 1,
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

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash?.success, flash?.error]);

    const submit = (e) => {
        e.preventDefault();
        const selected = currencies.find((c) => c.name === data.currency);
        if (!selected)                    { toast.error("Please select a valid currency"); return; }
        if (!data.insurance_category_id)  { toast.error("Please select an insurance category"); return; }
        if (quotationItems.some((item) => !item.product_id)) {
            toast.error("Please select a product for each item");
            return;
        }

        post(route("underwriting_quotes.store"), {
            preserveScroll: true,
            onSuccess: (page) => {
                if (page.props.flash?.error) return;

                toast.success("Underwriting quote created successfully");
                reset();
                setQuotationItems([createEmptyQuotationItem()]);
                syncSections([]);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Underwriting Quotes" subpage="Create New" url="underwriting_quotes.index" />

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
                                <p className="text-xs text-gray-400 mt-0.5">Selecting a category loads its quotation sections and available rating rules.</p>
                                <InputError message={errors.insurance_category_id} className="text-sm" />
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
                                        setQuotationItems([createEmptyQuotationItem()]);
                                        syncSections([]);
                                    }}
                                >
                                    Reset
                                </Button>
                                <Button type="submit" disabled={processing}>Create Underwriting Quote</Button>
                            </div>
                        </div>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
