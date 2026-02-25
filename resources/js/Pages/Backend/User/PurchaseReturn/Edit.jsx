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
import { Plus, Trash2, X } from "lucide-react";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import { useState, useEffect } from "react";
import DateTimePicker from "@/Components/DateTimePicker";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/Components/ui/tooltip";
import Attachment from "@/Components/ui/attachment";

export default function Edit({ vendors = [], products = [], purchase_return, currencies = [], taxes = [], accounts = [], inventory, taxIds, theAttachments }) {
  const [purchaseItems, setPurchaseReturnItems] = useState([]);
  const [purchaseReturnAccounts, setPurchaseReturnAccounts] = useState([]);
  const [exchangeRate, setExchangeRate] = useState(1);
  const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);
  const [attachments, setAttachments] = useState([]);

  const { data, setData, post, processing, errors, reset } = useForm({
    vendor_id: purchase_return.vendor_id,
    title: purchase_return.title,
    return_number: purchase_return.return_number,
    return_date: parseDateObject(purchase_return.return_date),
    currency: purchase_return.currency,
    exchange_rate: purchase_return.exchange_rate,
    converted_total: purchase_return.converted_total,
    discount_type: purchase_return.discount_type,
    discount_value: purchase_return.discount_value,
    note: purchase_return.note,
    footer: purchase_return.footer,
    attachments: [],
    product_id: [],
    product_name: [],
    description: [],
    quantity: [],
    unit_cost: [],
    taxes: taxIds,
    account_id: [],
    _method: 'PUT'
  });

  // Initialize purchase items from existing purchase return
  useEffect(() => {
    if (!purchase_return) return;

    // split items
    const productItems = [];
    const accountItems = [];
    const attachmentItems = [];

    purchase_return.items.forEach(item => {
      const row = {
        product_id: item.product_id,
        account_id: item.account_id,
        product_name: item.product_name,
        description: item.description,
        quantity: item.quantity,
        unit_cost: item.unit_cost,
      };
      (item.product_id ? productItems : accountItems).push(row);
    });

    theAttachments.forEach(attachment => {
      const row = attachment.path;
      (attachmentItems).push(row);
    });

    // set local state only
    setPurchaseReturnItems(productItems);
    setPurchaseReturnAccounts(accountItems);
    setAttachments(attachmentItems);

    // set one‐offs
    setData("currency", purchase_return.currency);
    setData("exchange_rate", purchase_return.exchange_rate);
    setExchangeRate(purchase_return.exchange_rate);
    // …and your dates, vendor, title, etc…
  }, []);


  // ------------------------------------------------
  // Keep Inertia form arrays in sync with our two local lists
  const syncFormArrays = () => {
    setData("product_id", purchaseItems.map(i => i.product_id));
    setData("product_name", purchaseItems.map(i => i.product_name)
      .concat(purchaseReturnAccounts.map(a => a.product_name || "")));
    setData("account_id", purchaseItems.map(i => i.account_id || "")
      .concat(purchaseReturnAccounts.map(a => a.account_id)));
    setData("description", purchaseItems.map(i => i.description)
      .concat(purchaseReturnAccounts.map(a => a.description || "")));
    setData("quantity", purchaseItems.map(i => i.quantity)
      .concat(purchaseReturnAccounts.map(a => a.quantity || 1)));
    setData("unit_cost", purchaseItems.map(i => i.unit_cost)
      .concat(purchaseReturnAccounts.map(a => a.unit_cost)));

    setData("attachments", attachments);
  };

  useEffect(() => {
    syncFormArrays();
  }, [purchaseItems, purchaseReturnAccounts, attachments]);
  // ------------------------------------------------

  const addPurchaseReturnItem = () => {
    setPurchaseReturnItems([...purchaseItems, {
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
    const updatedItems = purchaseItems.filter((_, i) => i !== index);
    setPurchaseReturnItems(updatedItems);
  };

  const updatePurchaseReturnItem = (index, field, value) => {
    const updatedItems = [...purchaseItems];
    updatedItems[index][field] = value;

    if (field === "product_id") {
      const product = products.find(p => p.id === parseInt(value, 10));
      if (product) {
        console.log("Selected product:", product);
        updatedItems[index].product_name = product.name;
        updatedItems[index].unit_cost = product.purchase_cost;
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
    const productSubtotal = purchaseItems.reduce((sum, item) => sum + (item.quantity * item.unit_cost), 0);
    const accountSubtotal = purchaseReturnAccounts.reduce((sum, account) => sum + parseFloat(account.quantity * account.unit_cost || 0), 0);
    return productSubtotal + accountSubtotal;
  };

  // build this once, outside of calculateTaxes
  const taxRateMap = new Map(taxes.map(t => [t.id, Number(t.rate)]));

  const calculateTaxes = () => {
    // merge both lists into one
    const allLines = [...purchaseItems, ...purchaseReturnAccounts];

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

  // Find and set base currency on component mount
  useEffect(() => {
    // First try to find a currency with base_currency flag
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
  }, [data.currency, data.discount_type, data.discount_value, exchangeRate, purchaseItems, purchaseReturnAccounts]);

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
      product_id: purchaseItems.map(item => item.product_id),
      product_name: purchaseItems.map(item => item.product_name),
      description: [
        ...purchaseItems.map(item => item.description || ""),
        ...purchaseReturnAccounts.map(account => account.description || "")
      ],
      quantity: [
        ...purchaseItems.map(item => item.quantity || 1),
        ...purchaseReturnAccounts.map(account => account.quantity || 1)
      ],
      unit_cost: [
        ...purchaseItems.map(item => item.unit_cost),
        ...purchaseReturnAccounts.map(account => account.unit_cost)
      ],
      account_id: [
        ...purchaseItems.map(item => item.account_id || inventory.id),
        ...purchaseReturnAccounts.map(account => account.account_id)
      ],
    };

    // Post the form data directly instead of using setData first
    post(route("purchase_returns.update", purchase_return.id), formData, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Purchase Return updated successfully");
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Purchase Return" subpage="Edit Purchase Return" url="purchase_returns.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <form onSubmit={submit}>
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="vendor_id" className="md:col-span-2 col-span-12">
                Suppliers
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
                <h3 className="text-lg font-medium">Purchase Items</h3>
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
                      <p>Use "Add Item" to include physical goods or inventory <br /> items you're purchasing (e.g. lenses, equipment).</p>
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
                      <p>Use "Add Account" to record service expenses <br /> or non-inventory costs (e.g. shipping fees, professional services).</p>
                    </TooltipContent>
                  </Tooltip>
                </div>
              </div>

              {purchaseItems.map((item, index) => (
                <div key={index} className="border rounded-lg p-4 space-y-4 bg-gray-50">
                  <div className={`grid grid-cols-1 md:grid-cols-6 gap-2`}>
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
                  <div className={`grid grid-cols-1 md:grid-cols-6 gap-2`}>
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
                            ...purchaseItems.map(item => item.product_name),
                            ...updatedAccounts.map(account => account.product_name || "")
                          ]);
                        }}
                        placeholder="Enter product name"
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
                            ...purchaseItems.map(item => item.quantity),
                            ...updatedAccounts.map(account => account.quantity)
                          ]);
                        }}
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
                      <Label>Description</Label>
                      <Textarea
                        value={accountItem.description || ""}
                        onChange={(e) => {
                          const updatedAccounts = [...purchaseReturnAccounts];
                          updatedAccounts[index].description = e.target.value;
                          setPurchaseReturnAccounts(updatedAccounts);
                          setData("description", [
                            ...purchaseItems.map(item => item.description),
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
              <Label htmlFor="discount_type" className="md:col-span-2 col-span-12">
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
                Your message to supplier
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
                  databaseAttachments={theAttachments}
                  onAdd={files => setAttachments(prev => [...prev, ...files])}
                  onRemove={idx => setAttachments(prev => prev.filter((_, i) => i !== idx))}
                  maxSize={20}
                />
                <InputError message={errors.attachments} className="text-sm" />
              </div>
            </div>

            <div className="mt-6 space-y-2">
              <div className="space-y-2">
                <div className="text-sm">Subtotal: {calculateSubtotal()}</div>
                <div className="text-sm">Taxes: {calculateTaxes()}</div>
                <div className="text-sm">Discount: {calculateDiscount()}</div>
                {renderTotal()}
              </div>

              <div className="space-x-2">
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => {
                    reset();
                    setPurchaseReturnItems([]);
                    setPurchaseReturnAccounts([]);
                  }}
                >
                  Reset
                </Button>
                <Button type="submit" disabled={processing}>
                  Update Purchase Return
                </Button>
              </div>
            </div>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
