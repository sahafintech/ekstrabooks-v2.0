import { useForm, usePage } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast, Toaster } from 'sonner'
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Textarea } from "@/Components/ui/textarea";
import DateTimePicker from "@/Components/DateTimePicker";
import { formatCurrency } from "@/lib/utils";
import { useState, useEffect } from "react";
import { Plus, Trash2, X } from "lucide-react";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/Components/ui/tooltip";
import Attachment from "@/Components/ui/attachment";

export default function Create({ vendors = [], products = [], currencies = [], taxes = [], accounts = [], purchase_title, inventory, base_currency, projects = [], cost_codes = [], construction_module, methods = [] }) {
  const { flash = {} } = usePage().props;
  const [purchaseItems, setPurchaseItems] = useState([{
    product_id: "",
    product_name: "",
    description: "",
    quantity: 1,
    unit_cost: 0,
    project_id: null,
    project_task_id: null,
    cost_code_id: null
  }]);

  const [purchaseAccounts, setPurchaseAccounts] = useState([]);
  const [exchangeRate, setExchangeRate] = useState(1);
  const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);
  const [attachments, setAttachments] = useState([]);

  // State for managing multiple payment entries
  const [paymentEntries, setPaymentEntries] = useState([]);
  const [paymentTotal, setPaymentTotal] = useState(0);

  const { data, setData, post, processing, errors, reset } = useForm({
    vendor_id: "",
    title: purchase_title,
    bill_no: "",
    po_so_number: "",
    purchase_date: new Date(),
    currency: base_currency,
    exchange_rate: 1,
    converted_total: 0,
    discount_type: "0",
    discount_value: 0,
    template: "",
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
    project_id: [],
    project_task_id: [],
    cost_code_id: [],
    benificiary: "",
    credit_account_id: null,
    // Multiple payment accounts support
    payment_accounts: [],
  });

  // ------------------------------------------------
  // Keep Inertia form arrays in sync with our two local lists
  const syncFormArrays = () => {
    setData("product_id", purchaseItems.map(i => i.product_id));
    setData("product_name", purchaseItems.map(i => i.product_name)
      .concat(purchaseAccounts.map(a => a.product_name || "")));
    setData("account_id", purchaseAccounts.map(a => a.account_id)
      .concat(purchaseItems.map(a => a.account_id || "")));
    setData("description", purchaseItems.map(i => i.description)
      .concat(purchaseAccounts.map(a => a.description || "")));
    setData("quantity", purchaseItems.map(i => i.quantity)
      .concat(purchaseAccounts.map(a => a.quantity || 1)));
    setData("unit_cost", purchaseItems.map(i => i.unit_cost)
      .concat(purchaseAccounts.map(a => a.unit_cost)));
    setData("project_id", purchaseItems.map(i => i.project_id)
      .concat(purchaseAccounts.map(a => a.project_id)));
    setData("project_task_id", purchaseItems.map(i => i.project_task_id)
      .concat(purchaseAccounts.map(a => a.project_task_id)));
    setData("cost_code_id", purchaseItems.map(i => i.cost_code_id)
      .concat(purchaseAccounts.map(a => a.cost_code_id)));

    setData("attachments", attachments);
  };

  useEffect(() => {
    syncFormArrays();
  }, [purchaseItems, purchaseAccounts, attachments]);
  // ------------------------------------------------

  // Helper functions for multiple payment entries
  const addPaymentEntry = () => {
    const newEntry = {
      id: Date.now(),
      account_id: "",
      amount: 0,
      method: "",
      reference: "",
    };
    setPaymentEntries([...paymentEntries, newEntry]);
  };

  const addQuickPayment = () => {
    const remaining = getRemainingAmount();
    if (remaining > 0) {
      const newEntry = {
        id: Date.now(),
        account_id: "",
        amount: remaining,
        method: "",
        reference: "",
      };
      setPaymentEntries([...paymentEntries, newEntry]);
    }
  };

  const removePaymentEntry = (id) => {
    setPaymentEntries(paymentEntries.filter(entry => entry.id !== id));
  };

  const updatePaymentEntry = (id, field, value) => {
    setPaymentEntries(paymentEntries.map(entry => 
      entry.id === id ? { ...entry, [field]: value } : entry
    ));
  };

  const calculatePaymentTotal = () => {
    return paymentEntries.reduce((sum, entry) => sum + (parseFloat(entry.amount) || 0), 0);
  };

  const getRemainingAmount = () => {
    const total = calculateTotal();
    const paid = calculatePaymentTotal();
    return total - paid;
  };

  const isPaymentComplete = () => {
    const total = calculateTotal();
    const paid = calculatePaymentTotal();
    return Math.abs(total - paid) < 0.01; // Allow for small floating point differences
  };

  // Update payment total whenever entries change
  useEffect(() => {
    setPaymentTotal(calculatePaymentTotal());
  }, [paymentEntries]);

  // Update form data with payment accounts
  useEffect(() => {
    setData('payment_accounts', paymentEntries);
  }, [paymentEntries]);

  const addPurchaseItem = () => {
    setPurchaseItems([...purchaseItems, {
      product_id: "",
      product_name: "",
      description: "",
      quantity: 1,
      unit_cost: 0,
      account_id: inventory.id,
      project_id: null,
      project_task_id: null,
      cost_code_id: null
    }]);
  };

  const addPurchaseAccount = () => {
    setPurchaseAccounts([...purchaseAccounts, {
      account_id: "",
      unit_cost: 0,
      quantity: 1,
      description: "",
      product_name: "",
      project_id: null,
      project_task_id: null,
      cost_code_id: null
    }]);
  };

  const removePurchaseAccount = (index) => {
    const updatedAccounts = purchaseAccounts.filter((_, i) => i !== index);
    setPurchaseAccounts(updatedAccounts);
  };

  const removeInvoiceItem = (index) => {
    const updatedItems = purchaseItems.filter((_, i) => i !== index);
    setPurchaseItems(updatedItems);
  };

  const updateInvoiceItem = (index, field, value) => {
    const updatedItems = [...purchaseItems];
    updatedItems[index][field] = value;

    if (field === "product_id") {
      const product = products.find(p => p.id === parseInt(value, 10));
      if (product) {
        console.log("Selected product:", product);
        updatedItems[index].product_name = product.name;
        updatedItems[index].unit_cost = product.selling_price;
        updatedItems[index].account_id = inventory.id;
        updatedItems[index].project_id = null;
        updatedItems[index].project_task_id = null;
        updatedItems[index].cost_code_id = null;
        // Also update the description if it's empty
        if (!updatedItems[index].description) {
          updatedItems[index].description = product.description || "";
        }
      } else {
      }
    }

    setPurchaseItems(updatedItems);
  };

  const calculateSubtotal = () => {
    const productSubtotal = purchaseItems.reduce((sum, item) => sum + (item.quantity * item.unit_cost), 0);
    const accountSubtotal = purchaseAccounts.reduce((sum, account) => sum + parseFloat(account.quantity * account.unit_cost || 0), 0);
    return productSubtotal + accountSubtotal;
  };

  // build this once, outside of calculateTaxes
  const taxRateMap = new Map(taxes.map(t => [t.id, Number(t.rate)]));

  const calculateTaxes = () => {
    // merge both lists into one
    const allLines = [...purchaseItems, ...purchaseAccounts];

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
  }, [data.currency, data.discount_type, data.discount_value, exchangeRate, purchaseItems, purchaseAccounts]);

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

  useEffect(() => {
    if (flash && flash.success) {
      toast("Success Message", {
        description: flash.success,
        action: {
          label: "Close",
          onClick: () => {
            toast.dismiss();
          }
        }
      });
    }

    if (flash && flash.error) {
      toast("Error Message", {
        description: flash.error,
        action: {
          label: "Close",
          onClick: () => {
            toast.dismiss();
          }
        }
      });
    }
  }, [flash, toast]);

  const submit = (e) => {
    e.preventDefault();

    // Find the selected currency object to get its name
    const selectedCurrency = currencies.find(c => c.name === data.currency);

    if (!selectedCurrency) {
      toast.error("Please select a valid currency");
      return;
    }

    // Validate payment entries
    if (paymentEntries.length === 0) {
      toast.error("Please add at least one payment entry");
      return;
    }
    
    if (!isPaymentComplete()) {
      toast.error(`Payment total (${formatCurrency({ amount: paymentTotal, currency: data.currency })}) must equal purchase total (${formatCurrency({ amount: calculateTotal(), currency: data.currency })})`);
      return;
    }
    
    // Validate all payment entries have required fields
    const invalidEntries = paymentEntries.filter(entry => 
      !entry.account_id || !entry.amount || !entry.method
    );
    
    if (invalidEntries.length > 0) {
      toast.error("Please fill in all required fields for payment entries");
      return;
    }

    // Create a new data object with all the required fields
    const formData = {
      ...data,
      currency: selectedCurrency.name,
      exchange_rate: exchangeRate,
      product_id: purchaseItems.map(item => item.product_id),
      product_name: purchaseItems.map(item => item.product_name),
      description: purchaseItems.map(item => item.description).concat(
        purchaseAccounts.map(account => account.description || "")
      ),
      quantity: purchaseItems.map(item => item.quantity).concat(
        purchaseAccounts.map(account => account.quantity || 1)
      ),
      unit_cost: purchaseItems.map(item => item.unit_cost),
      account_id: [
        ...purchaseItems.map(item => item.account_id || "Inventory"),
        ...purchaseAccounts.map(account => account.account_id)
      ],
      unit_cost: [
        ...purchaseItems.map(item => item.unit_cost * item.quantity),
        ...purchaseAccounts.map(account => account.unit_cost)
      ]
    };

    // Log the data being sent to help debug
    console.log("Submitting form with data:", formData);

    // Post the form data directly instead of using setData first
    post(route("cash_purchases.store"), formData, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Cash Purchase created successfully");
        reset();
        setPurchaseItems([{
          product_id: "",
          product_name: "",
          description: "",
          quantity: 1,
          unit_cost: 0,
          account_id: inventory.id,
          project_id: null,
          project_task_id: null,
          cost_code_id: null
        }]);
        setPurchaseAccounts([]);
        setAttachments([]);
        setPaymentEntries([]);
        setPaymentTotal(0);
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <Toaster position="top-center" />
      <SidebarInset>
        <PageHeader page="Cash Purchases" subpage="Create New" url="cash_purchases.index" />

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
              <Label htmlFor="payment_accounts" className="md:col-span-2 col-span-12">
                Payment Accounts
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full space-y-4">
                  {/* Multiple Payment Entries */}
                  <div className="space-y-4">
                    <div className="flex justify-between items-center">
                      <Label className="text-sm font-medium">Payment Entries</Label>
                      <div className="flex gap-2">
                        {getRemainingAmount() > 0 && (
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addQuickPayment}
                          >
                            Quick Payment
                          </Button>
                        )}
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          onClick={addPaymentEntry}
                        >
                          Add Payment
                        </Button>
                      </div>
                    </div>
                    
                    {/* Payment Entries */}
                    {paymentEntries.length > 0 && (
                      <div className="space-y-3">
                        {paymentEntries.map((entry, index) => (
                          <div key={entry.id} className="border rounded-md p-3 space-y-3 bg-gray-50">
                            <div className="flex justify-between items-center">
                              <span className="text-sm font-medium">Payment {index + 1}</span>
                              <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => removePaymentEntry(entry.id)}
                                className="text-red-500"
                              >
                                <Trash2 className="h-4 w-4" />
                              </Button>
                            </div>
                            
                            <div className="grid grid-cols-2 gap-3">
                              <div>
                                <Label className="text-xs">Account</Label>
                                <SearchableCombobox
                                  options={accounts.map(account => ({
                                    id: account.id,
                                    name: account.account_name,
                                  }))}
                                  value={entry.account_id}
                                  onChange={(selectedValue) => {
                                    updatePaymentEntry(entry.id, 'account_id', selectedValue);
                                  }}
                                  placeholder="Select account"
                                />
                              </div>
                              
                              <div>
                                <Label className="text-xs">Amount</Label>
                                <Input
                                  type="number"
                                  step="0.01"
                                  min="0"
                                  value={entry.amount}
                                  onChange={(e) => {
                                    updatePaymentEntry(entry.id, 'amount', parseFloat(e.target.value) || 0);
                                  }}
                                  placeholder="0.00"
                                />
                              </div>
                              
                              <div>
                                <Label className="text-xs">Method</Label>
                                <SearchableCombobox
                                  options={methods.map(method => ({
                                    id: method.name,
                                    name: method.name,
                                  }))}
                                  value={entry.method}
                                  onChange={(selectedValue) => {
                                    updatePaymentEntry(entry.id, 'method', selectedValue);
                                  }}
                                  placeholder="Select method"
                                />
                              </div>
                              
                              <div>
                                <Label className="text-xs">Reference</Label>
                                <Input
                                  type="text"
                                  value={entry.reference}
                                  onChange={(e) => {
                                    updatePaymentEntry(entry.id, 'reference', e.target.value);
                                  }}
                                  placeholder="Reference"
                                />
                              </div>
                            </div>
                          </div>
                        ))}
                        
                        {/* Payment Summary */}
                        <div className="border-t pt-3 space-y-2">
                          <div className="flex justify-between text-sm">
                            <span>Total Amount:</span>
                            <span className="font-medium">{formatCurrency({ amount: calculateTotal(), currency: data.currency })}</span>
                          </div>
                          <div className="flex justify-between text-sm">
                            <span>Paid Amount:</span>
                            <span className="font-medium">{formatCurrency({ amount: paymentTotal, currency: data.currency })}</span>
                          </div>
                          <div className="flex justify-between text-sm">
                            <span>Remaining:</span>
                            <span className={`font-medium ${getRemainingAmount() < 0 ? 'text-red-500' : getRemainingAmount() > 0 ? 'text-orange-500' : 'text-green-500'}`}>
                              {formatCurrency({ amount: getRemainingAmount(), currency: data.currency })}
                            </span>
                          </div>
                          {isPaymentComplete() && (
                            <div className="flex items-center gap-2 text-green-600 text-sm">
                              <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                              </svg>
                              <span>Payment Complete</span>
                            </div>
                          )}
                        </div>
                      </div>
                    )}
                    
                    {/* Quick Payment Options */}
                    {paymentEntries.length === 0 && (
                      <div className="text-center py-4 text-gray-500 border rounded-md">
                        <p className="text-sm">No payment entries added yet.</p>
                        <p className="text-xs">Click "Add Payment" to add payment accounts.</p>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="benificiary" className="md:col-span-2 col-span-12">
                Benificiary
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
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <Button
                        type="button"
                        variant="secondary"
                        onClick={addPurchaseItem}
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
                        onClick={addPurchaseAccount}
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
                  <div className={`grid grid-cols-1 md:grid-cols-${construction_module == 1 ? '9' : '6'} gap-2`}>
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
                        step="0.01"
                        value={item.quantity}
                        onChange={(e) => updateInvoiceItem(index, "quantity", parseFloat(e.target.value))}
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

                    <div>
                      <Label>Description</Label>
                      <Textarea
                        value={item.description}
                        onChange={(e) => {
                          updateInvoiceItem(index, "description", e.target.value);
                          e.target.style.height = 'auto';
                          e.target.style.height = e.target.scrollHeight + 'px';
                        }}
                        className="min-h-[30px] resize-none overflow-hidden"
                        rows={1}
                      />
                    </div>

                    {construction_module == 1 && (
                      <>
                        <div>
                          <Label>Project</Label>
                            <SearchableCombobox
                              options={projects.map(project => ({
                                id: project.id,
                                name: project.project_code + " - " + project.project_name
                              }))}
                              value={item.project_id}
                              onChange={(value) => updateInvoiceItem(index, "project_id", value)}
                              placeholder="Select project"
                            />
                        </div>
                        <div>
                          <Label>Project Task</Label>
                            <SearchableCombobox
                              options={projects.find(p => p.id === Number(item.project_id))?.tasks?.map(task => ({
                                id: task.id,
                                name: task.task_code + " - " + task.description
                              }))}
                              value={item.project_task_id}
                              onChange={(value) => updateInvoiceItem(index, "project_task_id", value)}
                              placeholder="Select project task"
                            />
                        </div>
                        <div>
                          <Label>Cost Code</Label>
                            <SearchableCombobox
                              options={cost_codes.map(cost_code => ({
                                id: cost_code.id,
                                name: cost_code.code + " - " + cost_code.description
                              }))}
                              value={item.cost_code_id}
                              onChange={(value) => updateInvoiceItem(index, "cost_code_id", value)}
                              placeholder="Select cost code"
                            />
                        </div>
                      </>
                    )}

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
                        onClick={() => removeInvoiceItem(index)}
                      >
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
                </div>
              ))}

              {purchaseAccounts.map((accountItem, index) => (
                <div key={`account-${index}`} className="border rounded-lg p-4 space-y-4 bg-gray-50">
                  <div className={`grid grid-cols-1 md:grid-cols-${construction_module == 1 ? '10' : '7'} gap-2`}>
                    <div>
                      <Label>Account *</Label>
                      <SearchableCombobox
                        options={accounts.map(account => ({
                          id: account.id,
                          name: account.account_name
                        }))}
                        value={accountItem.account_id}
                        onChange={(value) => {
                          const updatedAccounts = [...purchaseAccounts];
                          updatedAccounts[index].account_id = value;
                          setPurchaseAccounts(updatedAccounts);
                          setData("account_id", updatedAccounts.map(account => account.account_id));
                        }}
                        placeholder="Select account"
                      />
                    </div>

                    <div>
                      <Label>Item or Service Name *</Label>
                      <Input
                        type="text"
                        value={accountItem.product_name || ""}
                        onChange={(e) => {
                          const updatedAccounts = [...purchaseAccounts];
                          updatedAccounts[index].product_name = e.target.value;
                          setPurchaseAccounts(updatedAccounts);
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
                          const updatedAccounts = [...purchaseAccounts];
                          updatedAccounts[index].quantity = parseFloat(e.target.value);
                          setPurchaseAccounts(updatedAccounts);
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
                          const updatedAccounts = [...purchaseAccounts];
                          updatedAccounts[index].unit_cost = parseFloat(e.target.value);
                          setPurchaseAccounts(updatedAccounts);
                          setData("unit_cost", updatedAccounts.map(account => account.unit_cost));
                        }}
                      />
                    </div>

                    <div>
                      <Label>Description</Label>
                      <Textarea
                        value={accountItem.description || ""}
                        onChange={(e) => {
                          const updatedAccounts = [...purchaseAccounts];
                          updatedAccounts[index].description = e.target.value;
                          setPurchaseAccounts(updatedAccounts);
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

                    {construction_module == 1 && (
                      <>
                        <div>
                          <Label>Project</Label>
                            <SearchableCombobox
                              options={projects.map(project => ({
                                id: project.id,
                                name: project.project_code + " - " + project.project_name
                              }))}
                              value={accountItem.project_id}
                              onChange={(value) => {
                                const updatedAccounts = [...purchaseAccounts];
                                updatedAccounts[index].project_id = value;
                                setPurchaseAccounts(updatedAccounts);
                                setData("project_id", [
                                  ...purchaseItems.map(item => item.project_id),
                                  ...updatedAccounts.map(account => account.project_id)
                                ]);
                              }}
                              placeholder="Select project"
                            />
                        </div>

                        <div>
                          <Label>Project Task</Label>
                            <SearchableCombobox
                              options={projects.find(p => p.id === Number(accountItem.project_id))?.tasks?.map(task => ({
                                id: task.id,
                                name: task.task_code + " - " + task.description
                              }))}
                              value={accountItem.project_task_id}
                              onChange={(value) => {
                                const updatedAccounts = [...purchaseAccounts];
                                updatedAccounts[index].project_task_id = value;
                                setPurchaseAccounts(updatedAccounts);
                                setData("project_task_id", [
                                  ...purchaseItems.map(item => item.project_task_id),
                                  ...updatedAccounts.map(account => account.project_task_id)
                                ]);
                              }}
                              placeholder="Select project task"
                            />
                        </div>

                        <div>
                          <Label>Cost Code</Label>
                            <SearchableCombobox
                              options={cost_codes.map(cost_code => ({
                                id: cost_code.id,
                                name: cost_code.code + " - " + cost_code.description
                              }))}
                              value={accountItem.cost_code_id}
                              onChange={(value) => {
                                const updatedAccounts = [...purchaseAccounts];
                                updatedAccounts[index].cost_code_id = value;
                                setPurchaseAccounts(updatedAccounts);
                                setData("cost_code_id", [
                                  ...purchaseItems.map(item => item.cost_code_id),
                                  ...updatedAccounts.map(account => account.cost_code_id)
                                ]);
                              }}
                              placeholder="Select cost code"
                            />
                        </div>
                      </>
                    )}
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
                        onClick={() => removePurchaseAccount(index)}
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
                Your message to supplier
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
                    setPurchaseItems([{
                      product_id: "",
                      product_name: "",
                      description: "",
                      quantity: 1,
                      unit_cost: 0,
                      account_id: inventory.id,
                      project_id: null,
                      project_task_id: null,
                      cost_code_id: null
                    }]);
                    setPurchaseAccounts([]);
                    setAttachments([]);
                    setPaymentEntries([]);
                    setPaymentTotal(0);
                  }}
                >
                  Reset
                </Button>
                <Button type="submit" disabled={processing}>
                  Create Cash Purchase
                </Button>
              </div>
            </div>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
