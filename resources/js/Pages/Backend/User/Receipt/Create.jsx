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
import { Calendar } from "@/Components/ui/calendar";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/Components/ui/popover";
import { CalendarIcon, Plus, Trash2 } from "lucide-react";
import { format } from "date-fns";
import { cn } from "@/lib/utils";
import { useState, useEffect } from "react";

export default function Create({ customers = [], products = [], currencies = [], taxes = [], accounts = [] }) {
  const [receiptItems, setReceiptItems] = useState([{
    product_id: "",
    product_name: "",
    description: "",
    quantity: 1,
    unit_cost: 0,
    taxes: []
  }]);

  const [exchangeRate, setExchangeRate] = useState(1);
  const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);

  // Debug log the products data
  useEffect(() => {
    console.log("Available products:", products);
  }, [products]);

  const { data, setData, post, processing, errors, reset } = useForm({
    customer_id: "",
    title: "",
    receipt_number: "",
    order_number: "",
    receipt_date: format(new Date(), "yyyy-MM-dd"),
    currency: "",
    exchange_rate: 1,
    converted_total: 0,
    discount_type: "percentage",
    discount_value: 0,
    note: "",
    footer: "",
    account_id: "",
    product_id: [],
    product_name: [],
    description: [],
    quantity: [],
    unit_cost: [],
    taxes: []
  });

  const addReceiptItem = () => {
    setReceiptItems([...receiptItems, {
      product_id: "",
      product_name: "",
      description: "",
      quantity: 1,
      unit_cost: 0,
      taxes: []
    }]);
    setData("product_id", [...(Array.isArray(data.product_id) ? data.product_id : []), ""]);
    setData("product_name", [...(Array.isArray(data.product_name) ? data.product_name : []), ""]);
    setData("description", [...(Array.isArray(data.description) ? data.description : []), ""]);
    setData("quantity", [...(Array.isArray(data.quantity) ? data.quantity : []), 1]);
    setData("unit_cost", [...(Array.isArray(data.unit_cost) ? data.unit_cost : []), 0]);
    setData("taxes", [...(Array.isArray(data.taxes) ? data.taxes : []), []]);
  };

  const removeReceiptItem = (index) => {
    if (receiptItems.length === 1) {
      toast.error("You must have at least one item");
      return;
    }

    const updatedItems = [...receiptItems];
    updatedItems.splice(index, 1);
    setReceiptItems(updatedItems);

    setData("product_id", updatedItems.map(item => item.product_id));
    setData("product_name", updatedItems.map(item => item.product_name));
    setData("description", updatedItems.map(item => item.description));
    setData("quantity", updatedItems.map(item => item.quantity));
    setData("unit_cost", updatedItems.map(item => item.unit_cost));
    setData("taxes", updatedItems.map(item => item.taxes));
  };

  const updateReceiptItem = (index, field, value) => {
    const updatedItems = [...receiptItems];
    updatedItems[index][field] = value;

    if (field === "product_id") {
      const product = products.find(p => p.id === parseInt(value, 10));
      if (product) {
        console.log("Selected product:", product);
        updatedItems[index].product_name = product.name;
        updatedItems[index].unit_cost = product.selling_price || product.price;

        // Also update the description if it's empty
        if (!updatedItems[index].description) {
          updatedItems[index].description = product.description || "";
        }
      } else {
        console.warn("Product not found for ID:", value);
      }
    }

    setReceiptItems(updatedItems);
    setData("product_id", updatedItems.map(item => item.product_id));
    setData("product_name", updatedItems.map(item => item.product_name));
    setData("description", updatedItems.map(item => item.description));
    setData("quantity", updatedItems.map(item => item.quantity));
    setData("unit_cost", updatedItems.map(item => item.unit_cost));
    setData("taxes", updatedItems.map(item => item.taxes));
  };

  const calculateSubtotal = () => {
    return receiptItems.reduce((sum, item) => sum + (item.quantity * item.unit_cost), 0);
  };

  const calculateTaxes = () => {
    return receiptItems.reduce((sum, item) => {
      return sum + item.taxes.reduce((taxSum, tax) => {
        return taxSum + (item.quantity * item.unit_cost * tax.rate) / 100;
      }, 0);
    }, 0);
  };

  const calculateDiscount = () => {
    const subtotal = calculateSubtotal();
    if (data.discount_type === "percentage") {
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

  const convertCurrency = (amount) => {
    if (!exchangeRate || exchangeRate === 0) return amount;

    // Convert from selected currency to base currency
    // According to the ISO 4217 standard:
    // If selected is EUR with rate 0.92 and base is USD with rate 1
    // then 100 EUR = (100 / 0.92) = 108.70 USD
    console.log(`Converting ${amount} with exchange rate ${exchangeRate}`);

    // Ensure we're using floating point math with proper decimal precision
    return parseFloat((amount / parseFloat(exchangeRate)).toFixed(4));
  };

  // Format currency with proper currency code
  const formatCurrency = (amount, currencyCode) => {
    return `${currencyCode} ${amount.toFixed(2)}`;
  };

  // Find and set base currency on component mount
  useEffect(() => {
    // First try to find a currency with exchange_rate exactly equal to 1
    let baseC = currencies.find(c => parseFloat(c.exchange_rate) === 1);

    // If none found, check if there's a currency with base_currency = 1 flag
    if (!baseC) {
      baseC = currencies.find(c => c.base_currency === 1);
    }

    // If still none found, just take the first currency as a fallback
    if (!baseC && currencies.length > 0) {
      baseC = currencies[0];
      console.warn("No base currency found with exchange_rate=1 or base_currency=1, using first currency as fallback:", baseC);
    }

    if (baseC) {
      console.log("Base currency set to:", baseC);
      setBaseCurrencyInfo(baseC);
    } else {
      console.error("No currencies available to set as base currency");
    }
  }, [currencies]);

  // Update exchange rate whenever the selected currency changes
  const handleCurrencyChange = (currencyName) => {
    // Find currency object by name
    const currencyObj = currencies.find(currency => currency.name === currencyName);

    if (currencyObj) {
      console.log("Selected currency:", currencyObj);

      // Set the exchange rate directly from the selected currency object first as a fallback
      const currentRate = parseFloat(currencyObj.exchange_rate);
      setExchangeRate(currentRate);
      setData('exchange_rate', currentRate);

      // Then try to fetch the updated exchange rate from the API
      fetch(`/user/find_currency/${currencyObj.name}`)
        .then(response => response.json())
        .then(apiData => {
          console.log("API response for currency rate:", apiData);
          if (apiData && apiData.exchange_rate) {
            const apiRate = parseFloat(apiData.exchange_rate);
            console.log("Setting exchange rate from API:", apiRate);
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
    const convertedTotal = convertCurrency(total);
    setData('converted_total', convertedTotal);
  }, [data.currency, receiptItems, data.discount_type, data.discount_value, exchangeRate]);

  const renderTotal = () => {
    const total = calculateTotal();
    const selectedCurrency = currencies.find(c => c.name === data.currency);

    console.log("RENDERING TOTAL:", {
      total,
      selectedCurrency,
      baseCurrencyInfo
    });

    if (!selectedCurrency || !baseCurrencyInfo) {
      return (
        <div>
          <div>Total: {total.toFixed(2)}</div>
        </div>
      );
    }

    const convertedTotal = convertCurrency(total);

    return (
      <div>
        <div>Total: {formatCurrency(total, selectedCurrency.name)}</div>
        {selectedCurrency.name !== baseCurrencyInfo.name && (
          <div className="text-sm text-gray-500">
            Equivalent: {formatCurrency(convertedTotal, baseCurrencyInfo.name)}
          </div>
        )}
      </div>
    );
  };

  const TaxSelector = ({ index }) => {
    return (
      <div className="col-span-12 md:col-span-2">
        <Label>Taxes</Label>
        <SearchableCombobox
          multiple
          options={taxes?.map(tax => ({
            id: tax.id,
            name: `${tax.name} (${tax.rate}%)`
          })) || []}
          value={receiptItems[index].taxes.map(t => t.id)}
          onChange={(values) => {
            const updatedItems = [...receiptItems];
            updatedItems[index].taxes = taxes
              .filter(tax => values.includes(tax.id))
              .map(tax => ({ id: tax.id, rate: tax.rate }));
            setReceiptItems(updatedItems);
            setData("taxes", updatedItems.map(item => item.taxes));
          }}
          placeholder="Select taxes"
        />
      </div>
    );
  };

  const submit = (e) => {
    e.preventDefault();

    // Validate required fields
    if (!data.title) {
      toast.error("Title is required");
      return;
    }

    if (!data.receipt_date) {
      toast.error("Receipt date is required");
      return;
    }

    if (!data.currency) {
      toast.error("Currency is required");
      return;
    }

    if (!data.account_id) {
      toast.error("Payment account is required");
      return;
    }

    // Check if any items have no product_id or quantity
    for (let i = 0; i < receiptItems.length; i++) {
      if (!receiptItems[i].product_id) {
        toast.error(`Product is required for item ${i + 1}`);
        return;
      }
      if (receiptItems[i].quantity <= 0) {
        toast.error(`Quantity must be greater than 0 for item ${i + 1}`);
        return;
      }
    }

    // Make sure we have the selected currency
    const selectedCurrency = currencies.find(c => c.name === data.currency);
    if (!selectedCurrency) {
      toast.error("Invalid currency selected");
      return;
    }

    // Prepare form data
    const formData = {
      ...data,
      currency: selectedCurrency.name,
      exchange_rate: exchangeRate,
      product_id: receiptItems.map(item => item.product_id),
      product_name: receiptItems.map(item => item.product_name),
      description: receiptItems.map(item => item.description),
      quantity: receiptItems.map(item => item.quantity),
      unit_cost: receiptItems.map(item => item.unit_cost),
      taxes: Object.fromEntries(
        receiptItems.map(item => [
          item.product_id,
          item.taxes.map(tax => tax.id)
        ])
      )
    };

    console.log("Submitting form with data:", formData);

    // Post the form data directly instead of using setData first
    post(route("receipts.store"), formData, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Receipt created successfully");
        reset();
        setReceiptItems([{
          product_id: "",
          product_name: "",
          description: "",
          quantity: 1,
          unit_cost: 0,
          taxes: []
        }]);
      },
      onError: (errors) => {
        console.error("Form submission errors:", errors);
        // Toast will be shown from the Inertia's error handling
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Receipts" subpage="Create New" url="receipts.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <form onSubmit={submit}>
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
                />
                <InputError message={errors.title} className="text-sm" />
              </div>
            </div>

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

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="receipt_number" className="md:col-span-2 col-span-12">
                Receipt Number
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="receipt_number"
                  type="text"
                  value={data.receipt_number}
                  onChange={(e) => setData("receipt_number", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.receipt_number} className="text-sm" />
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
                Receipt Date *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Popover>
                  <PopoverTrigger asChild>
                    <Button
                      variant={"outline"}
                      className={cn(
                        "md:w-1/2 w-full justify-start text-left font-normal",
                        !data.receipt_date && "text-muted-foreground"
                      )}
                    >
                      <CalendarIcon className="mr-2 h-4 w-4" />
                      {data.receipt_date ? (
                        format(new Date(data.receipt_date), "PPP")
                      ) : (
                        <span>Pick a date</span>
                      )}
                    </Button>
                  </PopoverTrigger>
                  <PopoverContent className="w-auto p-0" align="start">
                    <Calendar
                      mode="single"
                      selected={data.receipt_date ? new Date(data.receipt_date) : undefined}
                      onSelect={(date) =>
                        setData("receipt_date", date ? format(date, "yyyy-MM-dd") : "")
                      }
                      initialFocus
                    />
                  </PopoverContent>
                </Popover>
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
              <Label htmlFor="account_id" className="md:col-span-2 col-span-12">
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
                    placeholder="Select payment account"
                  />
                </div>
                <InputError message={errors.account_id} className="text-sm" />
              </div>
            </div>

            <SidebarSeparator className="my-4" />

            <div className="space-y-4">
              <div className="flex justify-between items-center">
                <h3 className="text-lg font-medium">Receipt Items</h3>
                <Button variant="secondary" type="button" onClick={addReceiptItem}>
                  <Plus className="w-4 h-4 mr-2" />
                  Add Item
                </Button>
              </div>

              {receiptItems.map((item, index) => (
                <div key={index} className="border rounded-lg p-4 space-y-4 bg-gray-50">
                  {/* First Row */}
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                  <div className="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div className="md:col-span-6">
                      <Label>Description</Label>
                      <Textarea
                        value={item.description}
                        onChange={(e) => updateReceiptItem(index, "description", e.target.value)}
                        rows={1}
                      />
                    </div>

                    <div className="md:col-span-3">
                      <TaxSelector index={index} />
                    </div>

                    <div className="md:col-span-2">
                      <Label>Subtotal</Label>
                      <div className="p-2 bg-white rounded mt-2 text-right">
                        {(item.quantity * item.unit_cost).toFixed(2)}
                      </div>
                    </div>

                    <div className="md:col-span-1 flex items-end justify-end">
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
              <Label htmlFor="discount_type" className="md:col-span-2 col-span-12">
                Discount Type
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={[
                      { id: "percentage", name: "Percentage (%)" },
                      { id: "fixed", name: "Fixed Amount" }
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
                <div className="text-sm">Subtotal: {calculateSubtotal().toFixed(2)}</div>
                <div className="text-sm">Taxes: {calculateTaxes().toFixed(2)}</div>
                <div className="text-sm">Discount: {calculateDiscount().toFixed(2)}</div>
                <div className="text-lg font-bold">{renderTotal()}</div>
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
                      taxes: []
                    }]);
                  }}
                >
                  Reset
                </Button>
                <Button type="submit" disabled={processing}>
                  Create Receipt
                </Button>
              </div>
            </div>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
