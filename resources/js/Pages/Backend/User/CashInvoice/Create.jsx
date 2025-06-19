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
import { useState, useEffect } from "react";
import DateTimePicker from "@/Components/DateTimePicker";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";

export default function Create({ customers = [], products = [], currencies = [], taxes = [], receipt_title, accounts, base_currency, projects, construction_module }) {
  const [receiptItems, setReceiptItems] = useState([{
    product_id: "",
    product_name: "",
    description: "",
    quantity: 1,
    unit_cost: 0,
  }]);

  const [exchangeRate, setExchangeRate] = useState(1);
  const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);

  const { data, setData, post, processing, errors, reset } = useForm({
    customer_id: "",
    title: receipt_title,
    receipt_number: "",
    order_number: "",
    receipt_date: new Date(),
    currency: base_currency,
    exchange_rate: 1,
    converted_total: 0,
    discount_type: "0",
    discount_value: 0,
    template: "",
    note: "",
    footer: "",
    project_id: "",
    product_id: [],
    product_name: [],
    description: [],
    quantity: [],
    unit_cost: [],
    taxes: [],
    account_id: ""
  });

  const addReceiptItem = () => {
    setReceiptItems([...receiptItems, {
      product_id: "",
      product_name: "",
      description: "",
      quantity: 1,
      unit_cost: 0,
    }]);
    setData("product_id", [...data.product_id, ""]);
    setData("product_name", [...data.product_name, ""]);
    setData("description", [...data.description, ""]);
    setData("quantity", [...data.quantity, 1]);
    setData("unit_cost", [...data.unit_cost, 0]);
  };

  const removeReceiptItem = (index) => {
    const updatedItems = receiptItems.filter((_, i) => i !== index);
    setReceiptItems(updatedItems);
    setData("product_id", updatedItems.map(item => item.product_id));
    setData("product_name", updatedItems.map(item => item.product_name));
    setData("description", updatedItems.map(item => item.description));
    setData("quantity", updatedItems.map(item => item.quantity));
    setData("unit_cost", updatedItems.map(item => item.unit_cost));
  };

  const updateReceiptItem = (index, field, value) => {
    const updatedItems = [...receiptItems];
    updatedItems[index][field] = value;

    if (field === "product_id") {
      const product = products.find(p => p.id === parseInt(value, 10));
      if (product) {
        updatedItems[index].product_name = product.name;
        updatedItems[index].unit_cost = product.selling_price;

        // Also update the description if it's empty
        if (!updatedItems[index].description) {
          updatedItems[index].description = product.description || "";
        }
      }
    }

    setReceiptItems(updatedItems);
    setData("product_id", updatedItems.map(item => item.product_id));
    setData("product_name", updatedItems.map(item => item.product_name));
    setData("description", updatedItems.map(item => item.description));
    setData("quantity", updatedItems.map(item => item.quantity));
    setData("unit_cost", updatedItems.map(item => item.unit_cost));
  };

  const calculateSubtotal = () => {
    return receiptItems.reduce((sum, item) => sum + (item.quantity * item.unit_cost), 0);
  };

  // build this once, outside of calculateTaxes
  const taxRateMap = new Map(taxes.map(t => [t.id, Number(t.rate)]));

  const calculateTaxes = () => {
    return receiptItems.reduce((sum, item) => {
      const base = Number(item.quantity) * Number(item.unit_cost);

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
    if (data.discount_type === "0") {
      return (subtotal * data.discount_value) / 100;
    }
    return data.discount_value;
  };

  const calculateTotal = () => {
    const subtotal = calculateSubtotal();
    const taxes = calculateTaxes();
    const discount = calculateDiscount();
    return (subtotal + taxes) - discount;
  };

  useEffect(() => {
    let baseC = currencies.find(c => c.base_currency === 1);

    if (!baseC && currencies.length > 0) {
      baseC = currencies[0];
    }

    if (baseC) {
      setBaseCurrencyInfo(baseC);
    }
  }, [currencies]);

  const handleCurrencyChange = (currencyName) => {
    const currencyObj = currencies.find(currency => currency.name === currencyName);

    if (currencyObj) {
      const currentRate = parseFloat(currencyObj.exchange_rate);
      setExchangeRate(currentRate);
      setData('exchange_rate', currentRate);

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

  useEffect(() => {
    const total = calculateTotal();
    const convertedTotal = total;
    setData('converted_total', convertedTotal);
  }, [data.currency, receiptItems, data.discount_type, data.discount_value, exchangeRate]);

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

    // Find the selected currency object to get its name
    const selectedCurrency = currencies.find(c => c.name === data.currency);

    if (!selectedCurrency) {
      toast.error("Please select a valid currency");
      return;
    }

    // Create a new data object with all the required fields
    const formData = {
      ...data,
      currency: selectedCurrency.name,
      exchange_rate: exchangeRate,
      product_id: receiptItems.map(item => item.product_id),
      product_name: receiptItems.map(item => item.product_name),
      description: receiptItems.map(item => item.description),
      quantity: receiptItems.map(item => item.quantity),
      unit_cost: receiptItems.map(item => item.unit_cost),
    };

    // Log the data being sent to help debug
    console.log("Submitting form with data:", formData);

    // Post the form data directly instead of using setData first
    post(route("receipts.store"), formData, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Cash invoice created successfully');
        reset();
        setReceiptItems([{
          product_id: "",
          product_name: "",
          description: "",
          quantity: 1,
          unit_cost: 0,
        }]);
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Cash Invoices" subpage="Create New" url="receipts.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <form onSubmit={submit}>
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="customer_id" className="md:col-span-2 col-span-12">
                Customer
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={customers.map(customer => ({
                      id: customer.id,
                      name: customer.name
                    }))}
                    value={data.customer_id}
                    onChange={(value) => setData("customer_id", value)}
                    placeholder="Select customer"
                  />
                </div>
                <InputError message={errors.customer_id} className="text-sm" />
              </div>
            </div>

            {construction_module == 1 && (
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="project_id" className="md:col-span-2 col-span-12">
                Project
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={projects.map(project => ({
                      id: project.id,
                      name: project.project_name
                    }))}
                    value={data.project_id}
                    onChange={(value) => setData("project_id", value)}
                    placeholder="Select project"
                  />
                </div>
                  <InputError message={errors.project_id} className="text-sm" />
                </div>
              </div>
            )}

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
              <Label htmlFor="order_number" className="md:col-span-2 col-span-12">
                Order Number
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="order_number"
                  type="text"
                  value={data.order_number}
                  onChange={(e) => setData("order_number", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.order_number} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="receipt_date" className="md:col-span-2 col-span-12">
                Invoice Date *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <DateTimePicker
                  value={data.receipt_date}
                  onChange={(date) => setData("receipt_date", date)}
                  className="md:w-1/2 w-full"
                  required
                />
                <InputError message={errors.receipt_date} className="text-sm" />
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
                    options={currencies.map(currency => ({
                      id: currency.name,
                      value: currency.name,
                      label: currency.name,
                      name: `${currency.name} - ${currency.description} (${currency.exchange_rate})`
                    }))}
                    value={data.currency}
                    onChange={(selectedValue) => {
                      console.log("Currency selected:", selectedValue);
                      setData("currency", selectedValue);
                      handleCurrencyChange(selectedValue);
                    }}
                    placeholder="Select currency"
                  />
                </div>
                <InputError message={errors.currency} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="customer_id" className="md:col-span-2 col-span-12">
                Payment Account *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={accounts.map(account => ({
                      id: account.id,
                      name: account.account_name
                    }))}
                    value={data.account_id}
                    onChange={(value) => setData("account_id", value)}
                    placeholder="Select account"
                    required
                  />
                </div>
                <InputError message={errors.account_id} className="text-sm" />
              </div>
            </div>

            <SidebarSeparator className="my-4" />

            <div className="space-y-4">
              <div className="flex justify-between items-center">
                <h3 className="text-lg font-medium">Invoice Items</h3>
                <Button variant="secondary" type="button" onClick={addReceiptItem}>
                  <Plus className="w-4 h-4 mr-2" />
                  Add Item
                </Button>
              </div>

              {receiptItems.map((item, index) => (
                <div key={index} className="border rounded-lg p-4 space-y-4 bg-gray-50">
                  {/* First Row */}
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div>
                      <Label>Product *</Label>
                      <SearchableCombobox
                        options={products.map(product => ({
                          id: product.id,
                          name: product.name
                        }))}
                        value={item.product_id}
                        onChange={(value) => updateReceiptItem(index, "product_id", value)}
                        placeholder="Select product"
                      />
                    </div>

                    <div>
                      <Label>Quantity *</Label>
                      <Input
                        type="number"
                        min="1"
                        value={item.quantity}
                        onChange={(e) => updateReceiptItem(index, "quantity", parseInt(e.target.value))}
                      />
                    </div>

                    <div>
                      <Label>Unit Cost *</Label>
                      <Input
                        type="number"
                        step="0.01"
                        value={item.unit_cost}
                        onChange={(e) => updateReceiptItem(index, "unit_cost", parseFloat(e.target.value))}
                      />
                    </div>
                  </div>

                  {/* Second Row */}
                  <div className="grid grid-cols-1 md:grid-cols-12 gap-2">
                    <div className="md:col-span-6">
                      <Label>Description</Label>
                      <Textarea
                        value={item.description}
                        onChange={(e) => updateReceiptItem(index, "description", e.target.value)}
                        rows={1}
                      />
                    </div>

                    <div className="md:col-span-2">
                      <Label>Subtotal</Label>
                      <div className="p-2 bg-white rounded mt-2 text-right">
                        {formatCurrency({ amount: item.quantity * item.unit_cost, currency: data.currency })}
                      </div>
                    </div>

                    <div className="md:col-span-1 flex items-center justify-end">
                      {receiptItems.length > 1 && (
                        <Button
                          type="button"
                          variant="ghost"
                          size="icon"
                          className="text-red-500"
                          onClick={() => removeReceiptItem(index)}
                        >
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
              <Label htmlFor="taxes" className="md:col-span-2 col-span-12">
                Tax
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableMultiSelectCombobox
                    options={taxes?.map(tax => ({
                      id: tax.id,
                      name: `${tax.name} (${tax.rate}%)`
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
                    options={[
                      { id: "0", name: "Percentage (%)" },
                      { id: "1", name: "Fixed Amount" }
                    ]}
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
                  onChange={(e) => setData("discount_value", parseFloat(e.target.value))}
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
                    setReceiptItems([{
                      product_id: "",
                      product_name: "",
                      description: "",
                      quantity: 1,
                      unit_cost: 0,
                    }]);
                  }}
                >
                  Reset
                </Button>
                <Button type="submit" disabled={processing}>
                  Create Cash Invoice
                </Button>
              </div>
            </div>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
