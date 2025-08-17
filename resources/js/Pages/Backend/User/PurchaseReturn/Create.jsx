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
import { Plus, Trash2, X } from "lucide-react";
import { Textarea } from "@/Components/ui/textarea";
import { formatCurrency } from "@/lib/utils";
import { useState, useEffect } from "react";
import DateTimePicker from "@/Components/DateTimePicker";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/Components/ui/tooltip";
import Attachment from "@/Components/ui/attachment";

export default function Create({ vendors = [], products = [], currencies = [], taxes = [], accounts = [], purchase_return_title, inventory, base_currency }) {
  const [purchaseReturnItems, setPurchaseReturnItems] = useState([{
    product_id: "",
    product_name: "",
    description: "",
    quantity: 1,
    unit_cost: 0,
    account_id: inventory.id,
  }]);

  const [purchaseReturnAccounts, setPurchaseReturnAccounts] = useState([]);
  const [exchangeRate, setExchangeRate] = useState(1);
  const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);
  const [attachments, setAttachments] = useState([]);

  const { data, setData, post, processing, errors, reset } = useForm({
    vendor_id: "",
    title: purchase_return_title,
    return_number: "",
    return_date: new Date(),
    currency: base_currency,
    exchange_rate: 1,
    converted_total: 0,
    discount_type: "0",
    discount_value: 0,
    note: "",
    footer: "",
    attachments: attachments,
    product_id: [],
    product_name: [],
    description: [],
    quantity: [],
    unit_cost: [],
    taxes: [],
    account_id: [],
  });

  // ------------------------------------------------
  // Keep Inertia form arrays in sync with our two local lists
  const syncFormArrays = () => {
    setData("product_id", purchaseReturnItems.map(i => i.product_id));
    setData("product_name", purchaseReturnItems.map(i => i.product_name)
      .concat(purchaseReturnAccounts.map(a => a.product_name || "")));
    setData("account_id", purchaseReturnAccounts.map(a => a.account_id)
      .concat(purchaseReturnItems.map(a => a.account_id || "")));
    setData("description", purchaseReturnItems.map(i => i.description)
      .concat(purchaseReturnAccounts.map(a => a.description || "")));
    setData("quantity", purchaseReturnItems.map(i => i.quantity)
      .concat(purchaseReturnAccounts.map(a => a.quantity || 1)));
    setData("unit_cost", purchaseReturnItems.map(i => i.unit_cost)
      .concat(purchaseReturnAccounts.map(a => a.unit_cost)));

    setData("attachments", attachments);
  };

  useEffect(() => {
    syncFormArrays();
  }, [purchaseReturnItems, purchaseReturnAccounts, attachments]);
  // ------------------------------------------------

  const addPurchaseReturnItem = () => {
    setPurchaseReturnItems([...purchaseReturnItems, {
      product_id: "",
      product_name: "",
      description: "",
      quantity: 1,
      unit_cost: 0,
      account_id: inventory.id,
    }]);
  };

  const addPurchaseReturnAccount = () => {
    setPurchaseReturnAccounts([...purchaseReturnAccounts, {
      account_id: "",
      unit_cost: 0,
      quantity: 1,
      description: "",
      product_name: "",
    }]);
  };

  const removePurchaseReturnAccount = (index) => {
    const updatedAccounts = purchaseReturnAccounts.filter((_, i) => i !== index);
    setPurchaseReturnAccounts(updatedAccounts);
  };

  const removePurchaseReturnItem = (index) => {
    const updatedItems = purchaseReturnItems.filter((_, i) => i !== index);
    setPurchaseReturnItems(updatedItems);
  };

  const updatePurchaseReturnItem = (index, field, value) => {
    const updatedItems = [...purchaseReturnItems];
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

    setPurchaseReturnItems(updatedItems);
  };

  const calculateSubtotal = () => {
    const productSubtotal = purchaseReturnItems.reduce((sum, item) => sum + (item.quantity * item.unit_cost), 0);
    const accountSubtotal = purchaseReturnAccounts.reduce((sum, account) => sum + parseFloat(account.quantity * account.unit_cost || 0), 0);
    return productSubtotal + accountSubtotal;
  };

  // build this once, outside of calculateTaxes
  const taxRateMap = new Map(taxes.map(t => [t.id, Number(t.rate)]));

  const calculateTaxes = () => {
    // merge both lists into one
    const allLines = [...purchaseReturnItems, ...purchaseReturnAccounts];

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
  }, [data.currency, purchaseReturnItems, data.discount_type, data.discount_value, exchangeRate, purchaseReturnItems, purchaseReturnAccounts]);

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
      toast.error("Please select a valid currency.");
      return;
    }

    // Create a new data object with all the required fields
    const formData = {
      ...data,
      currency: selectedCurrency.name,
      exchange_rate: exchangeRate,
      product_id: purchaseReturnItems.map(item => item.product_id),
      product_name: purchaseReturnItems.map(item => item.product_name),
      description: purchaseReturnItems.map(item => item.description).concat(
        purchaseReturnAccounts.map(account => account.description || "")
      ),
      quantity: purchaseReturnItems.map(item => item.quantity).concat(
        purchaseReturnAccounts.map(account => account.quantity || 1)
      ),
      unit_cost: purchaseReturnItems.map(item => item.unit_cost),
      account_id: [
        ...purchaseReturnItems.map(item => item.account_id || "Inventory"),
        ...purchaseReturnAccounts.map(account => account.account_id)
      ],
      unit_cost: [
        ...purchaseReturnItems.map(item => item.unit_cost * item.quantity),
        ...purchaseReturnAccounts.map(account => account.unit_cost)
      ],
    };

    // Post the form data directly instead of using setData first
    post(route("purchase_returns.store"), formData, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Purchase return created successfully.");
        reset();
        setPurchaseReturnItems([{
          product_id: "",
          product_name: "",
          description: "",
          quantity: 1,
          unit_cost: 0,
          account_id: inventory.id,
        }]);
        setPurchaseReturnAccounts([]);
        setAttachments([]);
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Purchase Returns" subpage="Create New" url="purchase_returns.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <form onSubmit={submit}>
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="vendor_id" className="md:col-span-2 col-span-12">
                Supplier
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
                    placeholder="Select supplier"
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
              <Label htmlFor="return_date" className="md:col-span-2 col-span-12">
                Return Date *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <DateTimePicker
                  value={data.return_date}
                  onChange={(date) => setData("return_date", date)}
                  className="md:w-1/2 w-full"
                  required
                />
                <InputError message={errors.return_date} className="text-sm" />
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
                <h3 className="text-lg font-medium">Purchase Return Lines</h3>
                <div className="flex space-x-2">
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <Button
                        type="button"
                        variant="secondary"
                        onClick={addPurchaseReturnItem}
                      >
                        <Plus className="w-4 h-4 mr-2" />
                        Add Item
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Use <strong>Add Item</strong> for inventory/physical goods you’re returning to the supplier (e.g., lenses, equipment).</p>
                    </TooltipContent>
                  </Tooltip>
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <Button
                        type="button"
                        variant="secondary"
                        onClick={addPurchaseReturnAccount}
                      >
                        <Plus className="w-4 h-4 mr-2" />
                        Add Account
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Use <strong>Add Account</strong> for service or non-inventory lines (e.g., shipping fees, professional services).</p>
                    </TooltipContent>
                  </Tooltip>
                </div>
              </div>

              {purchaseReturnItems.map((item, index) => (
                <div key={index} className="border rounded-lg p-4 space-y-4 bg-gray-50">
                  <div className="grid grid-cols-1 md:grid-cols-6 gap-2">
                    <div>
                      <Label>Product *</Label>
                      <SearchableCombobox
                        options={products.map(product => ({
                          id: product.id,
                          name: product.name
                        }))}
                        value={item.product_id}
                        onChange={(value) => updatePurchaseReturnItem(index, "product_id", value)}
                        placeholder="Select product"
                      />
                    </div>

                    <div>
                      <Label>Quantity *</Label>
                      <Input
                        type="number"
                        step="0.01"
                        value={item.quantity}
                        onChange={(e) => updatePurchaseReturnItem(index, "quantity", parseFloat(e.target.value))}
                      />
                    </div>

                    <div>
                      <Label>Unit Cost *</Label>
                      <Input
                        type="number"
                        step="0.01"
                        value={item.unit_cost}
                        onChange={(e) => updatePurchaseReturnItem(index, "unit_cost", parseFloat(e.target.value))}
                      />
                    </div>

                    <div>
                      <Label>Description</Label>
                      <Textarea
                        value={item.description}
                        onChange={(e) => {
                          updatePurchaseReturnItem(index, "description", e.target.value);
                          e.target.style.height = 'auto';
                          e.target.style.height = e.target.scrollHeight + 'px';
                        }}
                        className="min-h-[30px] resize-none overflow-hidden"
                        rows={1}
                      />
                    </div>

                    <div>
                      <Label>Subtotal</Label>
                      <div className="p-2 bg-white rounded mt-2 text-right">
                        {(item.quantity * item.unit_cost).toFixed(2)}
                      </div>
                    </div>

                    <div className="md:col-span-1 flex items-center justify-center">
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="text-red-500"
                        onClick={() => removePurchaseReturnItem(index)}
                      >
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
                </div>
              ))}

              {purchaseReturnAccounts.map((accountItem, index) => (
                <div key={`account-${index}`} className="border rounded-lg p-4 space-y-4 bg-gray-50">
                  <div className="grid grid-cols-1 md:grid-cols-7 gap-2">
                    <div>
                      <Label>Account *</Label>
                      <SearchableCombobox
                        options={accounts.map(account => ({
                          id: account.id,
                          name: account.account_name
                        }))}
                        value={accountItem.account_id}
                        onChange={(value) => {
                          const updatedAccounts = [...purchaseReturnAccounts];
                          updatedAccounts[index].account_id = value;
                          setPurchaseReturnAccounts(updatedAccounts);
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
                          const updatedAccounts = [...purchaseReturnAccounts];
                          updatedAccounts[index].product_name = e.target.value;
                          setPurchaseReturnAccounts(updatedAccounts);
                          setData("product_name", [
                            ...purchaseReturnItems.map(item => item.product_name),
                            ...updatedAccounts.map(account => account.product_name || "")
                          ]);
                        }}
                        placeholder="Enter line name"
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
                          const updatedAccounts = [...purchaseReturnAccounts];
                          updatedAccounts[index].unit_cost = parseFloat(e.target.value);
                          setPurchaseReturnAccounts(updatedAccounts);
                          setData("unit_cost", updatedAccounts.map(account => account.unit_cost));
                        }}
                      />
                    </div>

                    <div>
                      <Label>Quantity *</Label>
                      <Input
                        type="number"
                        step="0.01"
                        value={accountItem.quantity}
                        onChange={(e) => {
                          const updatedAccounts = [...purchaseReturnAccounts];
                          updatedAccounts[index].quantity = parseFloat(e.target.value);
                          setPurchaseReturnAccounts(updatedAccounts);
                          setData("quantity", [
                            ...purchaseReturnItems.map(item => item.quantity),
                            ...updatedAccounts.map(account => account.quantity)
                          ]);
                        }}
                      />
                    </div>

                    <div>
                      <Label>Description</Label>
                      <Textarea
                        value={accountItem.description || ""}
                        onChange={(e) => {
                          const updatedAccounts = [...purchaseReturnAccounts];
                          updatedAccounts[index].description = e.target.value;
                          setPurchaseReturnAccounts(updatedAccounts);
                          setData("description", [
                            ...purchaseReturnItems.map(item => item.description),
                            ...updatedAccounts.map(account => account.description)
                          ]);
                          e.target.style.height = 'auto';
                          e.target.style.height = e.target.scrollHeight + 'px';
                        }}
                        className="min-h-[30px] resize-none overflow-hidden"
                        rows={1}
                      />
                    </div>
                    <div>
                      <Label>Subtotal</Label>
                      <div className="p-2 bg-white rounded mt-1 text-right">
                        {(accountItem.quantity * accountItem.unit_cost).toFixed(2)}
                      </div>
                    </div>

                    <div className="md:col-span-1 flex items-center justify-center">
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="text-red-500"
                        onClick={() => removePurchaseReturnAccount(index)}
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
                    placeholder="Select tax rates"
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
                      { id: "1", name: "Fixed amount" }
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
                Message to supplier
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
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2 space-y-2 md:w-1/2 w-full">
                <Attachment
                  files={attachments}
                  onAdd={files => setAttachments(prev => [...prev, ...files])}
                  onRemove={idx => setAttachments(prev => prev.filter((_, i) => i !== idx))}
                  maxSize={20}
                />
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
                    setPurchaseReturnItems([{
                      product_id: "",
                      product_name: "",
                      description: "",
                      quantity: 1,
                      unit_cost: 0,
                      account_id: inventory.id,
                    }]);
                    setPurchaseReturnAccounts([]);
                    setAttachments([]);
                  }}
                >
                  Reset
                </Button>
                <Button type="submit" disabled={processing}>
                  Create purchase return
                </Button>
              </div>
            </div>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
