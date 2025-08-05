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
import { useState, useEffect } from "react";
import DateTimePicker from "@/Components/DateTimePicker";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";

export default function Edit({ customers = [], products = [], currencies = [], taxes = [], receipt, accounts, transaction, paymentTransactions, taxIds, projects, construction_module, methods = [] }) {
  const [receiptItems, setReceiptItems] = useState([{
    product_id: "",
    product_name: "",
    description: "",
    quantity: 1,
    unit_cost: 0,
  }]);

  const [exchangeRate, setExchangeRate] = useState(1);
  const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);

  // State for managing multiple payment entries
  const [paymentEntries, setPaymentEntries] = useState([]);
  const [paymentTotal, setPaymentTotal] = useState(0);

  const { data, setData, put, processing, errors, reset } = useForm({
    customer_id: receipt.customer_id || "",
    title: receipt.title || "",
    project_id: receipt.project_id || "",
    receipt_number: receipt.receipt_number || "",
    order_number: receipt.order_number || "",
    receipt_date: parseDateObject(receipt.receipt_date) || "",
    currency: receipt.currency || "",
    exchange_rate: receipt.exchange_rate || 1,
    converted_total: receipt.converted_total || 0,
    discount_type: receipt.discount_type || "0",
    discount_value: receipt.discount_value || 0,
    template: receipt.template || "",
    note: receipt.note || "",
    footer: receipt.footer || "",
    product_id: [],
    product_name: [],
    description: [],
    quantity: [],
    unit_cost: [],
    taxes: taxIds,
    account_id: transaction.account_id,
    // Multiple payment accounts support
    payment_accounts: [],
  });

  // Initialize invoice items from existing invoice
  useEffect(() => {
    if (receipt && receipt.items && receipt.items.length > 0) {
      const formattedItems = receipt.items.map(item => ({
        product_id: item.product_id,
        product_name: item.product_name,
        description: item.description,
        quantity: item.quantity,
        unit_cost: item.unit_cost,
      }));
      setReceiptItems(formattedItems);
    }

    // Set the initial currency and exchange rate
    if (receipt && receipt.currency) {
      setData('currency', receipt.currency);
      setExchangeRate(receipt.exchange_rate);
    }

    setData({
      ...data,
      product_id: receipt.items.map(item => item.product_id),
      product_name: receipt.items.map(item => item.product_name),
      description: receipt.items.map(item => item.description),
      quantity: receipt.items.map(item => item.quantity),
      unit_cost: receipt.items.map(item => item.unit_cost),
    })

    // Initialize payment entries from existing transactions
    if (paymentTransactions && paymentTransactions.length > 0) {
      const existingPayments = paymentTransactions.map((transaction, index) => ({
        id: transaction.id || Date.now() + index,
        account_id: transaction.account_id,
        amount: transaction.transaction_amount,
        method: transaction.transaction_method,
        reference: transaction.reference || "",
      }));
      setPaymentEntries(existingPayments);
    }

  }, [receipt, paymentTransactions]);

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

  // Update payment total whenever entries change
  useEffect(() => {
    setPaymentTotal(calculatePaymentTotal());
  }, [paymentEntries]);

  // Update form data with payment accounts
  useEffect(() => {
    setData('payment_accounts', paymentEntries);
  }, [paymentEntries]);

  // Update converted_total whenever relevant values change
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

    // Validate payment entries
    if (paymentEntries.length === 0) {
      toast.error("Please add at least one payment entry");
      return;
    }
    
    if (!isPaymentComplete()) {
      toast.error(`Payment total (${formatCurrency({ amount: paymentTotal, currency: data.currency })}) must equal invoice total (${formatCurrency({ amount: calculateTotal(), currency: data.currency })})`);
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
      product_id: receiptItems.map(item => item.product_id),
      product_name: receiptItems.map(item => item.product_name),
      description: receiptItems.map(item => item.description),
      quantity: receiptItems.map(item => item.quantity),
      unit_cost: receiptItems.map(item => item.unit_cost),
    };

    // Put the form data instead of post for updating
    put(route("receipts.update", receipt.id), formData, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Invoice updated successfully");
        // Don't reset the form after updating
      },
      onError: (errors) => {
        toast.error("Failed to update invoice");
      }
    });
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Cash Invoices" subpage="Edit Cash Invoice" url="receipts.index" />

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
                  <div className="grid grid-cols-1 md:grid-cols-6 gap-2">
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

                    <div>
                      <Label>Description</Label>
                      <Textarea
                        value={item.description}
                        onChange={(e) => {
                          updateReceiptItem(index, "description", e.target.value);
                          e.target.style.height = 'auto';
                          e.target.style.height = e.target.scrollHeight + 'px';
                        }}
                        className="min-h-[30px] resize-none overflow-hidden"
                        rows={1}
                      />
                    </div>

                    <div>
                      <Label>Subtotal</Label>
                      <div className="p-2 bg-white rounded text-right">
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
                      taxes: []
                    }]);
                    setPaymentEntries([]);
                    setPaymentTotal(0);
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
