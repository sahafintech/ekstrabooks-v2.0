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
import { Plus, Trash2 } from "lucide-react";
import { formatCurrency } from "@/lib/utils";
import { useEffect, useState } from "react";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import DateTimePicker from "@/Components/DateTimePicker";

const parseNumericValue = (value) => {
    if (value === "" || value === null || value === undefined) return "";
    const parsedValue = parseFloat(value);
    return Number.isNaN(parsedValue) ? "" : parsedValue;
};

const createEmptyQuotationItem = () => ({
    product_id: "",
    product_name: "",
    description: "",
    quantity: 1,
    unit_cost: 0,
});

export default function Create({
    customers = [],
    products = [],
    currencies = [],
    taxes = [],
    quotation_title,
    base_currency,
}) {
    const [quotationItems, setQuotationItems] = useState([createEmptyQuotationItem()]);
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
        attachment: null,
        product_id: [],
        product_name: [],
        description: [],
        quantity: [],
        unit_cost: [],
        taxes: [],
    });

    const customerOptions = customers.map((c) => ({ id: c.id, name: c.name }));
    const productOptions  = products.map((p) => ({ id: p.id, name: p.name }));
    const currencyOptions = currencies.map((c) => ({
        id: c.name, value: c.name, label: c.name,
        name: `${c.name} - ${c.description} (${c.exchange_rate})`,
    }));
    const discountTypeOptions = [
        { id: "0", name: "Percentage (%)" },
        { id: "1", name: "Fixed Amount" },
    ];

    const syncItems = (items) => {
        setData("product_id",   items.map((i) => i.product_id));
        setData("product_name", items.map((i) => i.product_name));
        setData("description",  items.map((i) => i.description));
        setData("quantity",     items.map((i) => i.quantity));
        setData("unit_cost",    items.map((i) => i.unit_cost));
    };

    const updateItems = (items) => {
        setQuotationItems(items);
        syncItems(items);
    };

    const calculateItemSubtotal = (item) => (Number(item.quantity) || 0) * (Number(item.unit_cost) || 0);

    const autoResizeTextarea = (event, onChange) => {
        onChange(event.target.value);
        event.target.style.height = "auto";
        event.target.style.height = `${event.target.scrollHeight}px`;
    };

    const addQuotationItem = () => updateItems([...quotationItems, createEmptyQuotationItem()]);

    const removeQuotationItem = (index) =>
        updateItems(quotationItems.filter((_, i) => i !== index));

    const updateQuotationItem = (index, field, value) => {
        const updated = [...quotationItems];
        updated[index] = { ...updated[index], [field]: value };
        if (field === "product_id") {
            const product = products.find((p) => p.id === parseInt(value, 10));
            if (product) {
                updated[index].product_name = product.name;
                updated[index].unit_cost = product.selling_price;
                if (!updated[index].description) updated[index].description = product.description || "";
            }
        }
        updateItems(updated);
    };

    const calculateSubtotal = () => quotationItems.reduce((s, i) => s + calculateItemSubtotal(i), 0);
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

        post(route("quotations.store"), {
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
                            <Label htmlFor="po_so_number" className="md:col-span-2 col-span-12">Order Number</Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input id="po_so_number" type="text" value={data.po_so_number} onChange={(e) => setData("po_so_number", e.target.value)} className="md:w-1/2 w-full" />
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
                                <Button variant="secondary" type="button" onClick={addQuotationItem}>
                                    <Plus className="w-4 h-4 mr-2" />Add Item
                                </Button>
                            </div>

                            {quotationItems.map((item, index) => (
                                <div key={index} className="border rounded-lg p-4 space-y-4 bg-gray-50">
                                    <div className="grid grid-cols-1 md:grid-cols-6 gap-2 items-end">
                                        <div>
                                            <Label>Product *</Label>
                                            <SearchableCombobox options={productOptions} value={item.product_id} onChange={(v) => updateQuotationItem(index, "product_id", v)} placeholder="Select product" />
                                        </div>

                                        <div>
                                            <Label>Quantity *</Label>
                                            <Input type="number" step="0.01" value={item.quantity} onChange={(e) => updateQuotationItem(index, "quantity", parseNumericValue(e.target.value))} />
                                        </div>

                                        <div>
                                            <Label>Unit Cost *</Label>
                                            <Input type="number" step="0.01" value={item.unit_cost} onChange={(e) => updateQuotationItem(index, "unit_cost", parseNumericValue(e.target.value))} />
                                        </div>

                                        <div>
                                            <Label>Description</Label>
                                            <Textarea value={item.description} onChange={(e) => autoResizeTextarea(e, (v) => updateQuotationItem(index, "description", v))} className="min-h-[30px] resize-none overflow-hidden" rows={1} />
                                        </div>

                                        <div>
                                            <Label>Subtotal</Label>
                                            <div className="p-2 bg-white rounded text-right">{calculateItemSubtotal(item).toFixed(2)}</div>
                                        </div>

                                        <div className="flex items-center justify-end">
                                            {quotationItems.length > 1 && (
                                                <Button type="button" variant="ghost" size="icon" className="text-red-500" onClick={() => removeQuotationItem(index)}>
                                                    <Trash2 className="w-4 h-4" />
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <SidebarSeparator className="my-4" />

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
                                <Button type="button" variant="secondary" onClick={() => { reset(); setQuotationItems([createEmptyQuotationItem()]); }}>Reset</Button>
                                <Button type="submit" disabled={processing}>Create Quotation</Button>
                            </div>
                        </div>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
