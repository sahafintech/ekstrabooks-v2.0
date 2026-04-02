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
import { formatCurrency, parseDateObject } from "@/lib/utils";
import { useEffect, useState } from "react";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import DateTimePicker from "@/Components/DateTimePicker";

const createEmptyQuotationItem = () => ({
  product_id: "",
  product_name: "",
  description: "",
  quantity: 1,
  unit_cost: 0,
  benefits: "",
  family_size: "",
  sum_insured: "",
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

const formatQuotationItem = (item = {}) => ({
  product_id: item.product_id ?? "",
  product_name: item.product_name ?? "",
  description: item.description ?? "",
  quantity: item.quantity ?? 1,
  unit_cost: item.unit_cost ?? 0,
  benefits: item.benefits ?? "",
  family_size: item.family_size ?? "",
  sum_insured: item.sum_insured ?? "",
});

const getInitialQuotationItems = (quotation) =>
  quotation?.items?.length
    ? quotation.items.map((item) => formatQuotationItem(item))
    : [createEmptyQuotationItem()];

const buildInitialFormData = (quotation, taxIds) => {
  const initialQuotationItems = getInitialQuotationItems(quotation);

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
    attachment: null,
    is_deffered: String(quotation.is_deffered ?? 0),
    invoice_category: quotation.invoice_category ?? "",
    product_id: initialQuotationItems.map((item) => item.product_id),
    product_name: initialQuotationItems.map((item) => item.product_name),
    description: initialQuotationItems.map((item) => item.description),
    quantity: initialQuotationItems.map((item) => item.quantity),
    unit_cost: initialQuotationItems.map((item) => item.unit_cost),
    benefits: initialQuotationItems.map((item) => item.benefits),
    family_size: initialQuotationItems.map((item) => item.family_size),
    sum_insured: initialQuotationItems.map((item) => item.sum_insured),
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

  const [exchangeRate, setExchangeRate] = useState(quotation.exchange_rate ?? 1);
  const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);

  const { data, setData, post, processing, errors, reset } = useForm(initialFormData);

  const isDeferredQuotation = data.is_deffered === "1";
  const canManageItems = !isDeferredQuotation || data.invoice_category !== "";
  const isMedicalDeferredQuotation = isDeferredQuotation && data.invoice_category === "medical";
  const isOtherDeferredQuotation = isDeferredQuotation && data.invoice_category === "other";
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
    setData("benefits", items.map((item) => item.benefits));
    setData("family_size", items.map((item) => item.family_size));
    setData("sum_insured", items.map((item) => item.sum_insured));
  };

  const sanitizeDeferredItemFields = (items, deferred, category) =>
    items.map((item) => ({
      ...item,
      benefits: deferred ? item.benefits ?? "" : "",
      family_size: deferred && category === "medical" ? item.family_size ?? "" : "",
      sum_insured: deferred && category === "other" ? item.sum_insured ?? "" : "",
    }));

  const updateItems = (items) => {
    setQuotationItems(items);
    syncQuotationItems(items);
  };

  const parseNumericValue = (value) => {
    if (value === "" || value === null || value === undefined) {
      return "";
    }

    const parsedValue = parseFloat(value);
    return Number.isNaN(parsedValue) ? "" : parsedValue;
  };

  const autoResizeTextarea = (event, onChange) => {
    onChange(event.target.value);
    event.target.style.height = "auto";
    event.target.style.height = `${event.target.scrollHeight}px`;
  };

  const addQuotationItem = () => {
    updateItems([...quotationItems, createEmptyQuotationItem()]);
  };

  const removeQuotationItem = (index) => {
    updateItems(quotationItems.filter((_, itemIndex) => itemIndex !== index));
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
        updatedItems[index].unit_cost = product.selling_price;

        if (!updatedItems[index].description) {
          updatedItems[index].description = product.description || "";
        }
      }
    }

    updateItems(updatedItems);
  };

  const calculateSubtotal = () =>
    quotationItems.reduce(
      (sum, item) => sum + (Number(item.quantity) || 0) * (Number(item.unit_cost) || 0),
      0
    );

  // build this once, outside of calculateTaxes
  const taxRateMap = new Map(taxes.map((tax) => [tax.id, Number(tax.rate)]));

  const calculateTaxes = () => {
    return quotationItems.reduce((sum, item) => {
      const base = (Number(item.quantity) || 0) * (Number(item.unit_cost) || 0);

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
      benefits: quotationItems.map(item => item.benefits),
      family_size: quotationItems.map(item => item.family_size),
      sum_insured: quotationItems.map(item => item.sum_insured),
    };

    post(route("quotations.update", quotation.id), formData, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Quotation updated successfully");
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Quotations" subpage="Edit" url="quotations.index" />

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
                    <div className="grid grid-cols-1 md:grid-cols-6 gap-2">
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

                      <div>
                        <Label>{isDeferredQuotation ? "Rate *" : "Unit Cost *"}</Label>
                        <Input
                          type="number"
                          step="0.01"
                          value={item.unit_cost}
                          onChange={(e) => updateQuotationItem(index, "unit_cost", parseNumericValue(e.target.value))}
                        />
                      </div>

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
                          {((Number(item.quantity) || 0) * (Number(item.unit_cost) || 0)).toFixed(2)}
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

                    {isDeferredQuotation && (
                      <div className={`grid grid-cols-1 gap-2 ${isMedicalDeferredQuotation || isOtherDeferredQuotation ? "md:grid-cols-2" : ""}`}>
                        <div>
                          <Label>Benefits</Label>
                          <Textarea
                            value={item.benefits}
                            onChange={(e) =>
                              autoResizeTextarea(e, (value) => updateQuotationItem(index, "benefits", value))
                            }
                            className="min-h-[30px] resize-none overflow-hidden"
                            rows={1}
                          />
                        </div>

                        {isMedicalDeferredQuotation && (
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

                        {isOtherDeferredQuotation && (
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
                      </div>
                    )}
                  </div>
                ))
              )}
            </div>

            <SidebarSeparator className="my-4" />

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
                <div className="text-sm">Subtotal: {Number(calculateSubtotal()).toFixed(2)}</div>
                <div className="text-sm">Taxes: {Number(calculateTaxes()).toFixed(2)}</div>
                <div className="text-sm">Discount: {Number(calculateDiscount()).toFixed(2)}</div>
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
                  }}
                >
                  Reset
                </Button>
                <Button type="submit" disabled={processing}>
                  Update Quotation
                </Button>
              </div>
            </div>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
