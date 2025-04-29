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
import { formatCurrency, parseDateObject } from "@/lib/utils";
import { useState, useEffect } from "react";
import DateTimePicker from "@/Components/DateTimePicker";
import { Plus, Trash2 } from "lucide-react";

export default function Edit({ customers = [], products = [], currencies = [], taxes = [], invoice }) {
  const [invoiceItems, setInvoiceItems] = useState([{
    product_id: "",
    product_name: "",
    description: "",
    quantity: 1,
    unit_cost: 0,
    taxes: []
  }]);

  const [exchangeRate, setExchangeRate] = useState(1);
  const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);

  const { data, setData, put, processing, errors, reset } = useForm({
    customer_id: invoice.customer_id || "",
    title: invoice.title || "",
    invoice_number: invoice.invoice_number || "",
    order_number: invoice.order_number || "",
    invoice_date: parseDateObject(invoice.invoice_date),
    due_date: parseDateObject(invoice.due_date),
    currency: invoice.currency || "",
    exchange_rate: invoice.exchange_rate || 1,
    converted_total: invoice.converted_total || 0,
    discount_type: invoice.discount_type || "0",
    discount_value: invoice.discount_value || 0,
    template: invoice.template || "",
    note: invoice.note || "",
    footer: invoice.footer || "",
    attachment: null,
    product_id: [],
    product_name: [],
    description: [],
    quantity: [],
    unit_cost: [],
    taxes: []
  });

  // Initialize invoice items from existing invoice
  useEffect(() => {
    if (invoice && invoice.items && invoice.items.length > 0) {
      const formattedItems = invoice.items.map(item => ({
        product_id: item.product_id,
        product_name: item.product_name,
        description: item.description,
        quantity: item.quantity,
        unit_cost: item.unit_cost,
        taxes: item.taxes || []
      }));
      setInvoiceItems(formattedItems);
    }

    // Set the initial currency and exchange rate
    if (invoice && invoice.currency) {
      setData('currency', invoice.currency);
      setExchangeRate(invoice.exchange_rate);
    }

    setData({
      ...data,
      product_id: invoice.items.map(item => item.product_id),
      product_name: invoice.items.map(item => item.product_name),
      description: invoice.items.map(item => item.description),
      quantity: invoice.items.map(item => item.quantity),
      unit_cost: invoice.items.map(item => item.unit_cost),
      taxes: invoice.items.map(item => item.taxes)
    })

  }, [invoice]);

  const addInvoiceItem = () => {
    setInvoiceItems([...invoiceItems, {
      product_id: "",
      product_name: "",
      description: "",
      quantity: 1,
      unit_cost: 0,
      taxes: []
    }]);
    setData("product_id", [...data.product_id, ""]);
    setData("product_name", [...data.product_name, ""]);
    setData("description", [...data.description, ""]);
    setData("quantity", [...data.quantity, 1]);
    setData("unit_cost", [...data.unit_cost, 0]);
    setData("taxes", [...data.taxes, []]);
  };

  const removeInvoiceItem = (index) => {
    const updatedItems = invoiceItems.filter((_, i) => i !== index);
    setInvoiceItems(updatedItems);
    setData("product_id", updatedItems.map(item => item.product_id));
    setData("product_name", updatedItems.map(item => item.product_name));
    setData("description", updatedItems.map(item => item.description));
    setData("quantity", updatedItems.map(item => item.quantity));
    setData("unit_cost", updatedItems.map(item => item.unit_cost));
    setData("taxes", updatedItems.map(item => item.taxes));
  };

  const updateInvoiceItem = (index, field, value) => {
    const updatedItems = [...invoiceItems];
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

    setInvoiceItems(updatedItems);
    setData("product_id", updatedItems.map(item => item.product_id));
    setData("product_name", updatedItems.map(item => item.product_name));
    setData("description", updatedItems.map(item => item.description));
    setData("quantity", updatedItems.map(item => item.quantity));
    setData("unit_cost", updatedItems.map(item => item.unit_cost));
    setData("taxes", updatedItems.map(item => item.taxes));
  };

  const calculateSubtotal = () => {
    return invoiceItems.reduce((sum, item) => sum + (item.quantity * item.unit_cost), 0);
  };

  const calculateTaxes = () => {
    return invoiceItems.reduce((sum, item) => {
      return sum + item.taxes.reduce((taxSum, tax) => {
        return taxSum + (item.quantity * item.unit_cost * tax.rate) / 100;
      }, 0);
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
  }, [data.currency, invoiceItems, data.discount_type, data.discount_value, exchangeRate]);

  // Parse date strings safely for the date picker
  const parseDate = (dateString) => {
    if (!dateString) return undefined;

    try {
      // Handle dd/mm/yyyy format
      if (typeof dateString === 'string' && dateString.includes('/')) {
        const [day, month, year] = dateString.split('/');
        const parsedDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));

        if (!isNaN(parsedDate.getTime())) {
          console.log("Successfully parsed date:", dateString, "to", parsedDate);
          return parsedDate;
        }
      }

      // Try to parse the date directly
      const date = new Date(dateString);

      // Check if date is valid
      if (isNaN(date.getTime())) {
        console.warn("Invalid date:", dateString);
        return undefined;
      }

      console.log("Parsed date:", dateString, "to", date);
      return date;
    } catch (error) {
      console.error("Error parsing date:", error, "for input:", dateString);
      return undefined;
    }
  };

  // Format date to display format (dd/mm/yyyy)
  const formatDateDisplay = (date) => {
    if (!date) return "";
    try {
      const parsedDate = parseDate(date);
      if (!parsedDate) return "";
      return format(parsedDate, "dd/MM/yyyy");
    } catch (error) {
      console.error("Error formatting date for display:", error);
      return "";
    }
  };

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
          value={invoiceItems[index].taxes.map(t => t.id)}
          onChange={(values) => {
            const updatedItems = [...invoiceItems];
            updatedItems[index].taxes = taxes
              .filter(tax => values.includes(tax.id))
              .map(tax => ({ id: tax.id, rate: tax.rate }));
            setInvoiceItems(updatedItems);
            setData("items", updatedItems);
          }}
          placeholder="Select taxes"
        />
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
      product_id: invoiceItems.map(item => item.product_id),
      product_name: invoiceItems.map(item => item.product_name),
      description: invoiceItems.map(item => item.description),
      quantity: invoiceItems.map(item => item.quantity),
      unit_cost: invoiceItems.map(item => item.unit_cost),
      taxes: Object.fromEntries(
        invoiceItems.map(item => [
          item.product_id,
          item.taxes.map(tax => tax.id)
        ])
      )
    };

    // Log the data being sent to help debug
    console.log("Submitting form with data:", formData);

    // Put the form data instead of post for updating
    put(route("invoices.update", invoice.id), formData, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Invoice updated successfully");
        // Don't reset the form after updating
      },
      onError: (errors) => {
        console.error("Error updating invoice:", errors);
        toast.error("Failed to update invoice");
      }
    });
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Invoices" subpage="Edit Invoice" url="invoices.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <form onSubmit={submit}>
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="customer_id" className="md:col-span-2 col-span-12">
                Customer *
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
              <Label htmlFor="invoice_date" className="md:col-span-2 col-span-12">
                Invoice Date *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <DateTimePicker
                  value={data.invoice_date}
                  onChange={(date) => setData("invoice_date", date)}
                  className="md:w-1/2 w-full"
                  required
                />
                <InputError message={errors.invoice_date} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="due_date" className="md:col-span-2 col-span-12">
                Due Date *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <DateTimePicker
                  value={data.due_date}
                  onChange={(date) => setData("due_date", date)}
                  className="md:w-1/2 w-full"
                  required
                />
                <InputError message={errors.due_date} className="text-sm" />
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

            <SidebarSeparator className="my-4" />

            <div className="space-y-4">
              <div className="flex justify-between items-center">
                <h3 className="text-lg font-medium">Invoice Items</h3>
                <Button variant="secondary" type="button" onClick={addInvoiceItem}>
                  <Plus className="w-4 h-4 mr-2" />
                  Add Item
                </Button>
              </div>

              {invoiceItems.map((item, index) => (
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
                        onChange={(value) => updateInvoiceItem(index, "product_id", value)}
                        placeholder="Select product"
                      />
                    </div>

                    <div>
                      <Label>Quantity *</Label>
                      <Input
                        type="number"
                        min="1"
                        value={item.quantity}
                        onChange={(e) => updateInvoiceItem(index, "quantity", parseInt(e.target.value))}
                      />
                    </div>

                    <div>
                      <Label>Unit Cost *</Label>
                      <Input
                        type="number"
                        step="0.01"
                        value={item.unit_cost}
                        onChange={(e) => updateInvoiceItem(index, "unit_cost", parseFloat(e.target.value))}
                      />
                    </div>
                  </div>

                  {/* Second Row */}
                  <div className="grid grid-cols-1 md:grid-cols-12 gap-2">
                    <div className="md:col-span-6">
                      <Label>Description</Label>
                      <Textarea
                        value={item.description}
                        onChange={(e) => updateInvoiceItem(index, "description", e.target.value)}
                        rows={1}
                      />
                    </div>

                    <div className="md:col-span-3">
                      <TaxSelector index={index} />
                    </div>

                    <div className="md:col-span-2">
                      <Label>Subtotal</Label>
                      <div className="p-2 bg-white rounded text-right">
                        {(item.quantity * item.unit_cost).toFixed(2)}
                      </div>
                    </div>

                    <div className="md:col-span-1 flex items-center justify-end">
                      {invoiceItems.length > 1 && (
                        <Button
                          type="button"
                          variant="ghost"
                          size="icon"
                          className="text-red-500"
                          onClick={() => removeInvoiceItem(index)}
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

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="attachment" className="md:col-span-2 col-span-12">
                Attachment
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="attachment"
                  type="file"
                  onChange={(e) => setData("attachment", e.target.files[0])}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.attachment} className="text-sm" />
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
                    setInvoiceItems([{
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
                  Update Invoice
                </Button>
              </div>
            </div>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
