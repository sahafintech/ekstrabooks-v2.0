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
import { formatCurrency } from "@/lib/utils";
import { useState, useEffect } from "react";
import DateTimePicker from "@/Components/DateTimePicker";
import { Plus, Trash2, X } from "lucide-react";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";

export default function Create({ vendors = [], products = [], currencies = [], taxes = [], accounts = [], purchase_title, inventory, base_currency }) {
  const [billItems, setBillItems] = useState([{
    product_id: "",
    product_name: "",
    description: "",
    quantity: 1,
    unit_cost: 0,
    account_id: inventory.id
  }]);

  const [billAccounts, setBillAccounts] = useState([]);
  const [exchangeRate, setExchangeRate] = useState(1);
  const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);
  const [attachments, setAttachments] = useState([]);

  const { data, setData, post, processing, errors, reset } = useForm({
    vendor_id: "",
    title: purchase_title,
    bill_no: "",
    po_so_number: "",
    purchase_date: new Date(),
    due_date: "",
    currency: base_currency,
    exchange_rate: 1,
    converted_total: 0,
    discount_type: "0",
    discount_value: 0,
    template: "",
    note: "",
    footer: "",
    attachments: [],
    product_id: [],
    product_name: [],
    description: [],
    quantity: [],
    unit_cost: [],
    taxes: [],
    account_id: [],
    benificiary: "",
  });

  // ------------------------------------------------
  // Keep Inertia form arrays in sync with our two local lists
  const syncFormArrays = () => {
    setData("product_id", billItems.map(i => i.product_id));
    setData("product_name", billItems.map(i => i.product_name)
      .concat(billAccounts.map(a => a.product_name || "")));
    setData("account_id", billAccounts.map(a => a.account_id)
      .concat(billItems.map(a => a.account_id || "")));
    setData("description", billItems.map(i => i.description)
      .concat(billAccounts.map(a => a.description || "")));
    setData("quantity", billItems.map(i => i.quantity)
      .concat(billAccounts.map(a => a.quantity || 1)));
    setData("unit_cost", billItems.map(i => i.unit_cost)
      .concat(billAccounts.map(a => a.unit_cost)));

    setData("attachments", attachments);
  };

  useEffect(() => {
    syncFormArrays();
  }, [billItems, billAccounts, attachments]);
  // ------------------------------------------------

  const addBillItem = () => {
    setBillItems([...billItems, {
      product_id: "",
      product_name: "",
      description: "",
      quantity: 1,
      unit_cost: 0,
      account_id: inventory.id
    }]);
  };

  const addBillAccount = () => {
    setBillAccounts([...billAccounts, {
      account_id: "",
      unit_cost: 0,
      quantity: 1,
      description: "",
      product_name: "" // Initialize product_name for account entries
    }]);
  };

  const removeBillAccount = (index) => {
    const updatedAccounts = billAccounts.filter((_, i) => i !== index);
    setBillAccounts(updatedAccounts);
  };

  const removeInvoiceItem = (index) => {
    const updatedItems = billItems.filter((_, i) => i !== index);
    setBillItems(updatedItems);
  };

  const updateInvoiceItem = (index, field, value) => {
    const updatedItems = [...billItems];
    updatedItems[index][field] = value;

    if (field === "product_id") {
      const product = products.find(p => p.id === parseInt(value, 10));
      if (product) {
        updatedItems[index].product_name = product.name;
        updatedItems[index].unit_cost = product.selling_price;
        updatedItems[index].account_id = inventory.id;

        // Also update the description if it's empty
        if (!updatedItems[index].description) {
          updatedItems[index].description = product.description || "";
        }
      } else {
      }
    }

    setBillItems(updatedItems);
  };

  const calculateSubtotal = () => {
    const productSubtotal = billItems.reduce((sum, item) => sum + (item.quantity * item.unit_cost), 0);
    const accountSubtotal = billAccounts.reduce((sum, account) => sum + parseFloat(account.quantity * account.unit_cost || 0), 0);
    return productSubtotal + accountSubtotal;
  };

  // build this once, outside of calculateTaxes
  const taxRateMap = new Map(taxes.map(t => [t.id, Number(t.rate)]));

  const calculateTaxes = () => {
    // merge both lists into one
    const allLines = [...billItems, ...billAccounts];

    return allLines.reduce((sum, line) => {
      const base = Number(line.quantity) * Number(line.unit_cost);
      const lineTax = data.taxes.reduce((taxSum, taxIdStr) => {
        const rate = taxRateMap.get(Number(taxIdStr)) || 0;
        return taxSum + (base * rate) / 100;
      }, 0);
      return sum + lineTax;
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
        });
    }
  };

  // Update converted_total whenever relevant values change
  useEffect(() => {
    const total = calculateTotal();
    const convertedTotal = total;
    setData('converted_total', convertedTotal);
  }, [data.currency, billItems, data.discount_type, data.discount_value, exchangeRate]);

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
      product_id: billItems.map(item => item.product_id),
      product_name: billItems.map(item => item.product_name),
      description: billItems.map(item => item.description).concat(
        billAccounts.map(account => account.description || "")
      ),
      quantity: billItems.map(item => item.quantity).concat(
        billAccounts.map(account => account.quantity || 1)
      ),
      unit_cost: billItems.map(item => item.unit_cost),
      account_id: [
        ...billItems.map(item => item.account_id),
        ...billAccounts.map(account => account.account_id)
      ],
      unit_cost: [
        ...billItems.map(item => item.unit_cost * item.quantity),
        ...billAccounts.map(account => account.unit_cost)
      ]
    };

    // Post the form data directly instead of using setData first
    post(route("bill_invoices.store"), formData, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Bill created successfully");
        reset();
        setBillItems([{
          product_id: "",
          product_name: "",
          description: "",
          quantity: 1,
          unit_cost: 0,
          account_id: ""
        }]);
        setBillAccounts([]);
        setAttachments([]);
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Bill Invoices" subpage="Create New" url="bill_invoices.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <form onSubmit={submit}>
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="vendor_id" className="md:col-span-2 col-span-12">
                Suppliers *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={vendors.map(vendor => ({
                      id: vendor.id,
                      name: vendor.name
                    }))}
                    value={data.vendor_id}
                    onChange={(value) => setData("vendor_id", value)}
                    placeholder="Select vendor"
                    required
                  />
                </div>
                <InputError message={errors.vendor_id} className="text-sm" />
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
              <Label htmlFor="po_so_number" className="md:col-span-2 col-span-12">
                Order Number
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
              <Label htmlFor="purchase_date" className="md:col-span-2 col-span-12">
                Purchase Date *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <DateTimePicker
                  value={data.purchase_date}
                  onChange={(date) => setData("purchase_date", date)}
                  className="md:w-1/2 w-full"
                  required
                />
                <InputError message={errors.purchase_date} className="text-sm" />
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

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="benificiary" className="md:col-span-2 col-span-12">
                Benificiary *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <Textarea
                    className="mt-1"
                    value={data.benificiary}
                    onChange={(e) => setData("benificiary", e.target.value)}
                    placeholder="Enter benificiary details"
                  />
                </div>
                <InputError message={errors.benificiary} className="text-sm" />
              </div>
            </div>

            <SidebarSeparator className="my-4" />

            <div className="space-y-4">
              <div className="flex justify-between items-center">
                <h3 className="text-lg font-medium">Purchase Items</h3>
                <div className="flex space-x-2">
                  <Button
                    type="button"
                    variant="secondary"
                    onClick={addBillItem}
                  >
                    <Plus className="w-4 h-4 mr-2" />
                    Add Item
                  </Button>
                  <Button
                    type="button"
                    variant="secondary"
                    onClick={addBillAccount}
                  >
                    <Plus className="w-4 h-4 mr-2" />
                    Add Account
                  </Button>
                </div>
              </div>

              {billItems.map((item, index) => (
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

                    <div className="md:col-span-2">
                      <Label>Subtotal</Label>
                      <div className="p-2 bg-white rounded text-right">
                        {(item.quantity * item.unit_cost).toFixed(2)}
                      </div>
                    </div>

                    <div className="md:col-span-1 flex items-center justify-end">
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="text-red-500"
                        onClick={() => removeInvoiceItem(index)}
                      >
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
                </div>
              ))}

              {billAccounts.map((accountItem, index) => (
                <div key={`account-${index}`} className="border rounded-lg p-4 space-y-4 bg-gray-50">
                  {/* First Row */}
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div>
                      <Label>Account *</Label>
                      <SearchableCombobox
                        options={accounts.map(account => ({
                          id: account.id,
                          name: account.account_name
                        }))}
                        value={accountItem.account_id}
                        onChange={(value) => {
                          const updatedAccounts = [...billAccounts];
                          updatedAccounts[index].account_id = value;
                          setBillAccounts(updatedAccounts);
                          setData("account_id", updatedAccounts.map(account => account.account_id));
                        }}
                        placeholder="Select account"
                      />
                    </div>

                    <div>
                      <Label>Product Name *</Label>
                      <Input
                        type="text"
                        value={accountItem.product_name || ""}
                        onChange={(e) => {
                          const updatedAccounts = [...billAccounts];
                          updatedAccounts[index].product_name = e.target.value;
                          setBillAccounts(updatedAccounts);
                          setData("product_name", [
                            ...billItems.map(item => item.product_name),
                            ...updatedAccounts.map(account => account.product_name || "")
                          ]);
                        }}
                        placeholder="Enter product name"
                      />
                    </div>

                    <div>
                      <Label>Amount *</Label>
                      <Input
                        type="number"
                        step="0.01"
                        min="0"
                        value={accountItem.unit_cost}
                        onChange={(e) => {
                          const updatedAccounts = [...billAccounts];
                          updatedAccounts[index].unit_cost = parseFloat(e.target.value);
                          setBillAccounts(updatedAccounts);
                          setData("unit_cost", updatedAccounts.map(account => account.unit_cost));
                        }}
                      />
                    </div>
                  </div>

                  {/* Second Row */}
                  <div className="grid grid-cols-1 md:grid-cols-12 gap-2">
                    <div className="md:col-span-3">
                      <Label>Quantity *</Label>
                      <Input
                        type="number"
                        min="1"
                        value={accountItem.quantity || 1}
                        onChange={(e) => {
                          const updatedAccounts = [...billAccounts];
                          updatedAccounts[index].quantity = parseInt(e.target.value);
                          setBillAccounts(updatedAccounts);
                          setData("quantity", [
                            ...billItems.map(item => item.quantity),
                            ...updatedAccounts.map(account => account.quantity)
                          ]);
                        }}
                      />
                    </div>

                    <div className="md:col-span-5">
                      <Label>Description</Label>
                      <Textarea
                        value={accountItem.description || ""}
                        onChange={(e) => {
                          const updatedAccounts = [...billAccounts];
                          updatedAccounts[index].description = e.target.value;
                          setBillAccounts(updatedAccounts);
                          setData("description", [
                            ...billItems.map(item => item.description),
                            ...updatedAccounts.map(account => account.description)
                          ]);
                        }}
                        rows={1}
                      />
                    </div>

                    <div className="md:col-span-3">
                      <Label>Subtotal</Label>
                      <div className="p-2 bg-white rounded mt-1 text-right">
                        {(accountItem.quantity * accountItem.unit_cost).toFixed(2)}
                      </div>
                    </div>

                    <div className="md:col-span-1 flex items-center justify-end">
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="text-red-500"
                        onClick={() => removeBillAccount(index)}
                      >
                        <Trash2 className="w-4 h-4" />
                      </Button>
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

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="attachments" className="md:col-span-2 col-span-12">
                Attachments
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2 space-y-2">
                <div className="border rounded-md overflow-hidden">
                  <table className="w-full">
                    <thead className="bg-gray-50 border-b">
                      <tr>
                        <th className="text-left py-2 px-4 font-medium text-gray-700 w-1/3">File Name</th>
                        <th className="text-left py-2 px-4 font-medium text-gray-700">Attachment</th>
                        <th className="text-center py-2 px-4 font-medium text-gray-700 w-24">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      {attachments.map((item, index) => (
                        <tr key={`attachment-${index}`} className="border-b last:border-b-0">
                          <td className="py-3 px-4">
                            <Input
                              id={`filename-${index}`}
                              type="text"
                              placeholder="Enter file name"
                              value={item.file_name}
                              onChange={(e) => {
                                const newAttachments = [...attachments];
                                newAttachments[index] = {
                                  ...newAttachments[index],
                                  file_name: e.target.value
                                };
                                setAttachments(newAttachments);
                              }}
                              className="w-full"
                            />
                          </td>
                          <td className="py-3 px-4">
                            <Input
                              id={`attachment-${index}`}
                              type="file"
                              onChange={(e) => {
                                const newAttachments = [...attachments];
                                newAttachments[index] = {
                                  ...newAttachments[index],
                                  file: e.target.files[0],
                                };
                                setAttachments(newAttachments);
                              }}
                              className="w-full"
                            />
                            {item.file && (
                              <div className="text-xs text-gray-500 mt-1 flex items-center justify-between truncate">
                                <span className="truncate">
                                  {typeof item.file === 'string'
                                    ? item.file.split('/').pop()
                                    : item.file.name}
                                </span>
                                <button
                                  type="button"
                                  onClick={() => {
                                    const newAttachments = [...attachments];
                                    newAttachments[index] = { ...newAttachments[index], file: null };
                                    setAttachments(newAttachments);
                                  }}
                                  className="ml-2 text-red-500 hover:text-red-700"
                                  title="Remove file"
                                >
                                  <X className="w-6 h-6" />
                                </button>
                              </div>
                            )}
                          </td>
                          <td className="py-3 px-4 text-center">
                            <Button
                              type="button"
                              variant="ghost"
                              size="icon"
                              className="text-red-500"
                              onClick={() => {
                                const newAttachments = attachments.filter((_, i) => i !== index);
                                setAttachments(newAttachments);
                              }}
                            >
                              <Trash2 className="h-4 w-4 text-destructive" />
                            </Button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => setAttachments([...attachments, { file: null, file_name: "" }])}
                  className="mt-2"
                >
                  <Plus className="h-4 w-4 mr-2" /> Add Attachment
                </Button>
                <InputError message={errors.attachments} className="text-sm" />
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
                    setBillItems([{
                      product_id: "",
                      product_name: "",
                      description: "",
                      quantity: 1,
                      unit_cost: 0,
                      account_id: ""
                    }]);
                    setBillAccounts([]);
                    setAttachments([]);
                  }}
                >
                  Reset
                </Button>
                <Button type="submit" disabled={processing}>
                  Create Bill
                </Button>
              </div>
            </div>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
