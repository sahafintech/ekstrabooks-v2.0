"use client";

import React, { useEffect, useState } from "react";

// shadcn/ui components (adjust import paths as needed)
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import Modal from "@/Components/Modal";
import { ScrollArea } from "@/Components/ui/scroll-area";
import { Link, usePage } from "@inertiajs/react";
import { Trash2, X } from "lucide-react";
import { formatCurrency } from "@/lib/utils";
import { useForm } from "@inertiajs/react";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Label } from "@/Components/ui/label";
import InputError from "@/Components/InputError";
import DateTimePicker from "@/Components/DateTimePicker";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";

export default function POS({ products, categories, currencies, accounts, customers, methods, baseCurrency, holdList, todayList, prescriptionProducts, pos_default_currency_change, pos_default_taxes, pos_product_image, taxes }) {
  // State to track which category is currently active
  const [activeCategory, setActiveCategory] = useState("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [lastInputTime, setLastInputTime] = useState(0);
  const { flash = {} } = usePage().props;
  const { toast } = useToast();

  const { data, setData, post, processing, errors, reset } = useForm({
    customer_id: "",
    invoice_date: new Date(),
    currency: baseCurrency.name,
    exchange_rate: 1,
    converted_total: 0,
    discount_type: "0",
    discount_value: 0,
    product_id: [],
    product_name: [],
    description: [],
    quantity: [],
    unit_cost: [],
    taxes: [],
    account_id: "",
    client_id: "",
    method: "",
    credit_cash: "cash",
    hold_pos_id: "",
    prescription_products_id: "",
    appointment: 0,
    taxes: [],
  });

  useEffect(() => {
    if (flash && flash.success) {
      toast({
        title: "Success",
        description: flash.success,
      });
    }

    if (flash && flash.error) {
      toast({
        variant: "destructive",
        title: "Error",
        description: flash.error,
      });
    }
  }, [flash, toast]);

  const handleSubmit = () => {
    post(route("receipts.pos_store"), {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        setShowPaymentModal(false);
        reset();
        setCartItems([]);
      },
    });
  };

  const handleCancel = () => {
    // Reset the form data
    reset();
    // Clear the cart
    setCartItems([]);
    // Reset to default values
    setData({
      customer_id: "",
      invoice_date: new Date(),
      currency: baseCurrency.name,
      exchange_rate: 1,
      converted_total: 0,
      discount_type: "0",
      discount_value: 0,
      product_id: [],
      product_name: [],
      description: [],
      quantity: [],
      unit_cost: [],
      taxes: [],
      account_id: "",
      client_id: "",
      method: "",
      credit_cash: "cash",
    });
  };

  // Function to handle holding the current cart
  const handleHold = () => {
    if (cartItems.length === 0) {
      toast({
        title: "Error",
        description: "Cannot hold an empty cart",
      });
      return;
    }

    // Show a modal to get description or notes about the hold
    const holdData = {
      items: cartItems,
      customer_id: data.customer_id,
      customer_name: customers.find(c => c.id === parseInt(data.customer_id))?.name || "",
      currency: data.currency,
      exchange_rate: data.exchange_rate,
      discount_type: data.discount_type,
      discount_value: data.discount_value,
      grand_total: calculateTotal(),
      description: holdDescription || `Hold from ${new Date()}`,
    };

    // empty cart
    reset();
    setCartItems([]);

    post(route("hold_pos_invoices.store"), holdData, {
      preserveScroll: true,
      onSuccess: () => {
        handleCancel(); // Clear the cart after holding
        setHoldDescription("");
      },
    });
  };

  const handleShowPaymentModal = () => {
    setShowPaymentModal(true);
    setData('appointment', 0);
  };

  const handleShowAppointmentModal = () => {
    setShowAppointmentModal(true);
    setData('appointment', 1);
  };

  // Function to load a held invoice into the cart
  const loadHeldInvoice = (holdItem) => {
    // Clear current cart first
    handleCancel();
    setData('hold_pos_id', holdItem.id);

    // Format cart items from the hold items
    const formattedCartItems = holdItem.items.map(item => ({
      id: parseInt(item.product_id),
      name: item.product_name,
      unit_cost: parseFloat(item.unit_cost),
      quantity: parseFloat(item.quantity),
      sub_total: parseFloat(item.sub_total)
    }));

    // Set the cart items
    setCartItems(formattedCartItems);

    // Set form data
    setData({
      ...data,
      customer_id: holdItem.customer_id || "",
      currency: holdItem.currency || baseCurrency.name,
      exchange_rate: parseFloat(holdItem.exchange_rate) || 1,
      discount_type: holdItem.discount_type || "0",
      discount_value: parseFloat(holdItem.discount_value) || 0,
      hold_pos_id: holdItem.id, // Store the hold ID so it can be deleted after successful checkout
      product_id: holdItem.items.map(item => parseInt(item.product_id)),
      product_name: holdItem.items.map(item => item.product_name),
      description: holdItem.items.map(item => item.description || ""),
      quantity: holdItem.items.map(item => parseFloat(item.quantity)),
      unit_cost: holdItem.items.map(item => parseFloat(item.unit_cost)),
      invoice_date: holdItem.receipt_date || new Date(),
      sub_total: holdItem.sub_total || 0,
      grand_total: holdItem.grand_total || 0
    });

    setShowHoldListModal(false);
    toast({
      title: "Success",
      description: "Held transaction loaded",
    });
  };

  // Function to load prescription items into the cart
  const loadPrescriptionItems = (prescriptionProduct) => {
    // Clear current cart first
    handleCancel();

    if (!prescriptionProduct.items || prescriptionProduct.items.length === 0) {
      toast({
        title: "Error",
        description: "No items found in this prescription",
      });
      return;
    }

    // Format cart items from the prescription items
    const formattedCartItems = prescriptionProduct.items.map(item => ({
      id: parseInt(item.product_id),
      name: item.product_name,
      unit_cost: parseFloat(item.unit_cost),
      quantity: parseFloat(item.quantity),
      sub_total: parseFloat(item.sub_total)
    }));

    // Set the cart items
    setCartItems(formattedCartItems);

    // Set prescription data regardless of customer/client
    const prescription = prescriptionProduct.prescription;
    // Always set the data, even if customer_id or client_id might be null
    setData({
      ...data,
      client_id: prescription?.customer_id || "",
      currency: prescriptionProduct.currency || baseCurrency.name,
      exchange_rate: parseFloat(prescriptionProduct.exchange_rate) || 1,
      product_id: prescriptionProduct.items.map(item => parseInt(item.product_id)),
      product_name: prescriptionProduct.items.map(item => item.product_name),
      description: prescriptionProduct.items.map(item => item.description || ""),
      quantity: prescriptionProduct.items.map(item => parseFloat(item.quantity)),
      unit_cost: prescriptionProduct.items.map(item => parseFloat(item.unit_cost)),
      invoice_date: new Date(),
      prescription_products_id: prescriptionProduct.id,
    });

    setShowPrescriptionsModal(false);
  };

  // Cart state
  const [cartItems, setCartItems] = useState([]);

  // State for modals
  const [showHoldListModal, setShowHoldListModal] = useState(false);
  const [showTodayInvoicesModal, setShowTodayInvoicesModal] = useState(false);
  const [showAppointmentModal, setShowAppointmentModal] = useState(false);
  const [showPrescriptionsModal, setShowPrescriptionsModal] = useState(false);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [showCurrencyModal, setShowCurrencyModal] = useState(false);
  const [showDiscountModal, setShowDiscountModal] = useState(false);

  // Hold description state
  const [holdDescription, setHoldDescription] = useState("");

  // Currency state
  const [exchangeRate, setExchangeRate] = useState(1);
  const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);

  const calculateSubtotal = () => {
    return cartItems.reduce((sum, item) => sum + (item.quantity * item.unit_cost), 0);
  };

  // build this once, outside of calculateTaxes
  const taxRateMap = new Map(taxes.map(t => [t.id, Number(t.rate)]));

  const calculateTaxes = () => {
    return cartItems.reduce((sum, item) => {
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
    if (!data.discount_type || !data.discount_value) {
      return 0;
    }
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

  const changeCurrency = () => {
    if (data.currency === baseCurrency.name) {
      // If current currency is base currency, switch to pos_default_currency
        setData('currency', pos_default_currency_change);
        handleCurrencyChange(pos_default_currency_change);
    } else {
      // If current currency is not base currency, switch back to base currency
      setData('currency', baseCurrency.name);
      handleCurrencyChange(baseCurrency.name);
    }

    console.log(data.currency);
  }

  const addTax = () => {
    if (pos_default_taxes) {
      setData("taxes", pos_default_taxes)
    }
  }

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

      // Update cart items with new exchange rate
      const updatedCartItems = cartItems.map(item => {
        // Find the original product to get its base selling price
        const originalProduct = products.find(p => p.id === item.id);
        if (originalProduct) {
          return {
            ...item,
            unit_cost: originalProduct.selling_price * currentRate
          };
        }
        return item;
      });
      setCartItems(updatedCartItems);

      // Update the unit_cost in form data
      setData('unit_cost', updatedCartItems.map(item => item.unit_cost));

      fetch(`/user/find_currency/${currencyObj.name}`)
        .then(response => response.json())
        .then(apiData => {
          if (apiData && apiData.exchange_rate) {
            const apiRate = parseFloat(apiData.exchange_rate);
            setExchangeRate(apiRate);
            setData('exchange_rate', apiRate);

            // Update cart items again with API exchange rate
            const updatedCartItemsWithApiRate = cartItems.map(item => {
              const originalProduct = products.find(p => p.id === item.id);
              if (originalProduct) {
                return {
                  ...item,
                  unit_cost: originalProduct.selling_price * apiRate
                };
              }
              return item;
            });
            setCartItems(updatedCartItemsWithApiRate);
            setData('unit_cost', updatedCartItemsWithApiRate.map(item => item.unit_cost));
          }
        })
        .catch(error => {
          // Already set the fallback exchange rate above
        });
    }
  };

  useEffect(() => {
    const total = calculateTotal();
    const convertedTotal = total;
    setData('converted_total', convertedTotal);
  }, [data.currency, cartItems, data.discount_type, data.discount_value, exchangeRate]);

  const renderTotal = () => {
    const total = calculateTotal();
    const selectedCurrency = currencies.find(c => c.name === data.currency);

    if (!selectedCurrency) {
      return (
        <span className="text-xl font-bold">0.00</span>
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
        <span>
          <span className="text-xl font-bold text-right">{formatCurrency({ amount: total, currency: selectedCurrency.name })}</span>
          <br />
          <span className="text-sm text-gray-600 text-right">
            Equivalent to {formatCurrency({ amount: baseCurrencyTotal, currency: baseCurrencyInfo.name })}
          </span>
        </span>
      );
    }

    return (
      <span className="text-xl font-bold">{formatCurrency({ amount: total, currency: selectedCurrency.name })}</span>
    );
  };

  // Filter products based on active category and search query

  const filteredProducts = products.filter(product => {
    // If search query exists, filter by name/code search
    if (searchQuery.trim() !== "") {
      const query = searchQuery.toLowerCase();
      return (
        (product.name && product.name.toLowerCase().includes(query)) ||
        (product.code && product.code.toLowerCase().includes(query))
      );
    }

    // If "all" category is selected, show all products
    if (activeCategory === "all") {
      return true;
    }

    // Filter by selected category
    return product.sub_category_id === parseInt(activeCategory);
  });

  // Function to determine if a category button should be active
  const isCategoryActive = (category) => {
    return activeCategory === category ? "bg-primary text-white" : "";
  };

  // Function to add product to cart
  const addToCart = (product) => {
    // Check if the product is already in the cart
    const existingItemIndex = cartItems.findIndex(item => item.id === product.id);

    if (existingItemIndex !== -1) {
      // Product already exists in cart, increment quantity
      const updatedCart = [...cartItems];
      updatedCart[existingItemIndex].quantity += 1;
      setCartItems(updatedCart);
    } else {
      // Add new product to cart with initial unit cost as selling price
      const initialUnitCost = product.selling_price * exchangeRate;
      
      const newItem = {
        id: product.id,
        name: product.name,
        unit_cost: initialUnitCost,
        quantity: 1,
        code: product.code || ''
      };
      
      setCartItems([...cartItems, newItem]);

      // Update form data
      setData('product_id', [...data.product_id, product.id]);
      setData('product_name', [...data.product_name, product.name]);
      setData('description', [...data.description, '']);
      setData('quantity', [...data.quantity, 1]);
      setData('unit_cost', [...data.unit_cost, initialUnitCost]);
    }
  };

  // Function to remove item from cart
  const removeCartItem = (itemId) => {
    setCartItems(cartItems.filter(item => item.id !== itemId));
  };

  // Function to update item price in cart
  const updateItemPrice = (itemId, newPrice) => {
    // Update cart items
    const updatedCartItems = cartItems.map(item => {
      if (item.id === itemId) {
        return { ...item, unit_cost: parseFloat(newPrice) || 0 };
      }
      return item;
    });
    setCartItems(updatedCartItems);

    // Update form data unit_cost array
    const updatedUnitCosts = data.product_id.map((pid, index) => {
      if (pid === itemId) {
        return parseFloat(newPrice) || 0;
      }
      return data.unit_cost[index];
    });
    setData('unit_cost', updatedUnitCosts);
  };

  // Function to update item quantity in cart
  const updateItemQuantity = (itemId, newQuantity) => {
    if (newQuantity < 1) return; // Prevent quantity below 1

    // Update cartItems
    const updatedCartItems = cartItems.map(item => {
      if (item.id === itemId) {
        return { ...item, quantity: newQuantity };
      }
      return item;
    });
    setCartItems(updatedCartItems);

    // Update form data quantity array to match cartItems
    const updatedQuantities = data.product_id.map((pid, idx) => {
      const cartItem = updatedCartItems.find(item => item.id === pid);
      return cartItem ? cartItem.quantity : data.quantity[idx];
    });
    setData('quantity', updatedQuantities);
  };

  // Function to cancel/remove prescription product
  const cancelPrescriptionProduct = (prescriptionProductId) => {
    if (confirm('Are you sure you want to cancel this prescription product?')) {
      post(route("prescriptions.cancel_prescription_products", { id: prescriptionProductId }), {
        preserveScroll: true,
      });
    }
  };

  return (
    // Full screen height layout in a flex column
    <div className="h-screen flex flex-col">
      <Toaster />
      {/* -------------------------
          TOP BAR: Search + Dialog Buttons
      ------------------------- */}
      <div className="p-4 space-y-4">
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <Input
            placeholder="search product / name / item code / scan bar code"
            className="flex-1"
            value={searchQuery}
            onChange={(e) => {
              setSearchQuery(e.target.value);

              // Record time of input for barcode detection
              const currentTime = new Date().getTime();
              const timeSinceLastInput = currentTime - lastInputTime;
              setLastInputTime(currentTime);

              // If typing is very fast (like from a barcode scanner)
              // and the input has sufficient length to be a barcode
              if (timeSinceLastInput < 50 && e.target.value.length > 5) {
                // Try to find exact product match for the barcode
                const exactProduct = products.find(product =>
                  product.code === e.target.value.trim()
                );

                if (exactProduct) {
                  // Found exact match, add to cart immediately
                  addToCart(exactProduct);
                  // Clear the search input
                  setTimeout(() => setSearchQuery(''), 100);
                }
              }
            }}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                // When Enter is pressed, check for exact barcode match
                const exactProduct = products.find(product =>
                  product.code === searchQuery.trim()
                );

                if (exactProduct) {
                  addToCart(exactProduct);
                  setSearchQuery('');
                  e.preventDefault();
                }
              }
            }}
          />

          <div className="flex flex-wrap items-center gap-2">
            {/* Hold List */}
            <Button
              variant="default"
              onClick={() => setShowHoldListModal(true)}
            >
              Hold List
            </Button>
            <Modal
              show={showHoldListModal}
              onClose={() => setShowHoldListModal(false)}
            >
              <div>
                <div className="flex justify-between items-center p-2 border-b">
                  <h2 className="text-lg font-medium">Hold List</h2>
                  <button
                    onClick={() => setShowHoldListModal(false)}
                    className="text-gray-500 hover:text-gray-700"
                  >
                    ×
                  </button>
                </div>

                <div className="p-4">
                  {holdList && holdList.length > 0 ? (
                    <div className="overflow-x-auto">
                      <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                          <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              INVOICE
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              CUSTOMER
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              GRAND TOTAL
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              ACTION
                            </th>
                          </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                          {holdList.map((hold) => (
                            <tr key={hold.id} className="hover:bg-gray-50">
                              <td className="px-6 py-4 whitespace-nowrap text-sm">
                                {hold.id}
                                <div className="text-xs text-gray-500">{hold.description}</div>
                              </td>
                              <td className="px-6 py-4 whitespace-nowrap text-sm">
                                {hold.customer_name}
                              </td>
                              <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                {formatCurrency({ amount: hold.grand_total, currency: hold.currency })}
                              </td>
                              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <Button
                                  variant="ghost"
                                  className="text-green-600 hover:text-green-800"
                                  onClick={() => loadHeldInvoice(hold)}
                                >
                                  <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                  </svg>
                                </Button>
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  ) : (
                    <div className="text-center py-4 text-gray-500">
                      No held transactions found.
                    </div>
                  )}
                </div>
              </div>
            </Modal>

            {/* Today Invoices */}
            <Button
              variant="default"
              onClick={() => setShowTodayInvoicesModal(true)}
            >
              Today Invoices
            </Button>
            <Modal
              show={showTodayInvoicesModal}
              onClose={() => setShowTodayInvoicesModal(false)}
              maxWidth="4xl"
            >
              <div>
                <div className="flex justify-between items-center p-2 border-b">
                  <h2 className="text-lg font-medium">Today Invoices</h2>
                  <button
                    onClick={() => setShowTodayInvoicesModal(false)}
                    className="text-gray-500 hover:text-gray-700"
                  >
                    ×
                  </button>
                </div>

                <div className="p-4">
                  {todayList && todayList.length > 0 ? (
                    <div className="overflow-x-auto">
                      <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                          <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              INVOICE
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              CUSTOMER
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              GRAND TOTAL
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              ACTION
                            </th>
                          </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                          {todayList.map((invoice) => {
                            const customer = customers.find(c => c.id === parseInt(invoice.customer_id));

                            return (
                              <tr key={invoice.id} className="hover:bg-gray-50">
                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                  {invoice.receipt_number || invoice.invoice_number}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                  {customer?.name || 'No Customer'}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                  {formatCurrency({ amount: invoice.grand_total, currency: invoice.currency })}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                  <Link
                                    href={route('receipts.invoice_pos', { id: invoice.id })}
                                    target="_blank"
                                  >
                                    <Button
                                      variant="ghost"
                                      className="text-blue-600 hover:text-blue-800"
                                    >
                                      <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0v3H7V4h6zm-6 8v4h6v-4H7z" clipRule="evenodd" />
                                      </svg>
                                    </Button>
                                  </Link>
                                </td>
                              </tr>
                            );
                          })}
                        </tbody>
                      </table>
                    </div>
                  ) : (
                    <div className="text-center py-4 text-gray-500">
                      No invoices found for today.
                    </div>
                  )}
                </div>

                {/* Footer with cancel button */}
                <div className="flex justify-end gap-2 p-4 border-t">
                  <Button
                    variant="default"
                    onClick={() => setShowTodayInvoicesModal(false)}
                    className="px-6"
                  >
                    Close
                  </Button>
                </div>
              </div>
            </Modal>

            {/* Appointment */}
            <Button
              variant="default"
              onClick={() => handleShowAppointmentModal(true)}
            >
              Appointment
            </Button>
            {/* Appointment Modal */}
            <Modal
              show={showAppointmentModal}
              onClose={() => setShowAppointmentModal(false)}
              maxWidth="4xl"
            >
              <div>
                <div className="flex justify-between items-center p-2 border-b">
                  <h2 className="text-lg">Appointment Modal</h2>
                  <button
                    onClick={() => setShowAppointmentModal(false)}
                    className="text-gray-500 hover:text-gray-700"
                  >
                    ×
                  </button>
                </div>

                <div className="p-2">
                  {/* Payment Method Options */}
                  <div className="flex gap-2 mb-6">
                    <Label className="bg-gray-100 px-4 py-2 rounded-md flex items-center">
                      <Input
                        type="radio"
                        id="cash"
                        name="paymentType"
                        checked={data.credit_cash == "cash"}
                        onChange={() => setData("credit_cash", "cash")}
                        className="h-4 w-4 mr-2 text-primary"
                      />
                      Cash
                    </Label>
                    <Label className="bg-gray-100 px-4 py-2 rounded-md flex items-center">
                      <Input
                        type="radio"
                        id="credit"
                        name="paymentType"
                        checked={data.credit_cash == "credit"}
                        onChange={() => setData("credit_cash", "credit")}
                        className="h-4 w-4 mr-2 text-primary"
                      />
                      Credit
                    </Label>
                    <Label className="bg-gray-100 px-4 py-2 rounded-md flex items-center">
                      <Input
                        type="radio"
                        id="provider"
                        name="paymentType"
                        checked={data.credit_cash == "provider"}
                        onChange={() => setData("credit_cash", "provider")}
                        className="h-4 w-4 mr-2 text-primary"
                      />
                      Provider
                    </Label>
                  </div>

                  {/* Payment Details */}
                  <div className="space-y-4">
                    <div className="flex justify-between items-center">
                      <Label>Sub Total</Label>
                      <span className="font-medium">{formatCurrency({ amount: calculateSubtotal(), currency: data.currency })}</span>
                    </div>

                    <div className="flex justify-between items-center">
                      <Label>Tax Amount</Label>
                      <span className="font-medium">{formatCurrency({ amount: calculateTaxes(), currency: data.currency })}</span>
                    </div>

                    <div className="flex justify-between items-center">
                      <Label>Discount Amount</Label>
                      <span className="font-medium">{formatCurrency({ amount: calculateDiscount(), currency: data.currency })}</span>
                    </div>

                    <div className="flex justify-between items-center">
                      <Label>Total</Label>
                      <span className="font-medium">{renderTotal()}</span>
                    </div>

                    <div className="flex justify-between items-center">
                      <Label>Client</Label>
                      <div className="w-[70%]">
                        <SearchableCombobox
                          options={customers.map(client => ({
                            id: client.id,
                            name: client.name,
                          }))}
                          value={data.client_id}
                          onChange={(selectedValue) => {
                            setData("client_id", selectedValue);
                          }}
                          placeholder="Select a client"
                        />
                      </div>
                    </div>

                    {/* Provider field - only shown when Provider payment method is selected */}
                    {data.credit_cash === "provider" && (
                      <div className="flex justify-between items-center">
                        <Label>Provider</Label>
                        <div className="w-[70%]">
                          <SearchableCombobox
                            options={customers.map(customer => ({
                              id: customer.id,
                              name: customer.name,
                            }))}
                            value={data.customer_id}
                            onChange={(selectedValue) => {
                              setData("customer_id", selectedValue);
                            }}
                            placeholder="Select a customer"
                          />
                        </div>
                      </div>
                    )}

                    <div className="flex justify-between items-center">
                      <Label>Payment Method</Label>
                      <div className="w-[70%]">
                        <SearchableCombobox
                          options={methods.map(method => ({
                            id: method.name,
                            name: method.name,
                          }))}
                          value={data.method}
                          onChange={(selectedValue) => {
                            setData("method", selectedValue);
                          }}
                          placeholder="Select payment method"
                        />
                      </div>
                    </div>

                    {data.credit_cash === "cash" && (
                      <div className="flex justify-between items-center">
                        <Label>Payment Account</Label>
                        <div className="w-[70%]">
                          <SearchableCombobox
                            options={accounts.map(account => ({
                              id: account.id,
                              name: account.account_name,
                            }))}
                            value={data.account_id}
                            onChange={(selectedValue) => {
                              setData("account_id", selectedValue);
                            }}
                            placeholder="Select payment account"
                          />
                        </div>
                      </div>
                    )}

                    <div className="flex justify-between items-center">
                      <Label>Invoice Date</Label>
                      <div className="w-[70%]">
                        <DateTimePicker
                          value={data.invoice_date}
                          onChange={(date) => setData("invoice_date", date)}
                          required
                        />
                      </div>
                    </div>

                    {(data.credit_cash === "credit" || data.credit_cash === "provider") && (
                      <div className="flex justify-between items-center">
                        <Label>Due Date</Label>
                        <div className="w-[70%]">
                          <DateTimePicker
                            value={data.due_date}
                            onChange={(date) => setData("due_date", date)}
                            required
                          />
                        </div>
                      </div>
                    )}
                  </div>
                </div>

                {/* Actions */}
                <div className="flex justify-end gap-2 p-4 border-t">
                  <Button
                    variant="outline"
                    onClick={() => setShowAppointmentModal(false)}
                    className="px-6"
                  >
                    Cancel
                  </Button>
                  <Button
                    variant="default"
                    onClick={() => {
                      // Handle save invoice logic here
                      handleSubmit();
                    }}
                    disabled={processing}
                  >
                    {processing ? "Saving..." : "Save Invoice"}
                  </Button>
                </div>
              </div>
            </Modal>

            {/* Prescriptions */}
            <Button
              variant="default"
              onClick={() => setShowPrescriptionsModal(true)}
            >
              Prescriptions
            </Button>
            <Modal
              show={showPrescriptionsModal}
              onClose={() => setShowPrescriptionsModal(false)}
              maxWidth="4xl"
            >
              <div>
                <div className="flex justify-between items-center p-2 border-b">
                  <h2 className="text-lg font-medium">Prescription Modal</h2>
                  <button
                    onClick={() => setShowPrescriptionsModal(false)}
                    className="text-gray-500 hover:text-gray-700"
                  >
                    ×
                  </button>
                </div>

                <div className="p-4">
                  {prescriptionProducts && prescriptionProducts.length > 0 ? (
                    <div className="overflow-x-auto">
                      <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                          <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              DATE
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              CUSTOMER NAME
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              PHONE
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              NUMBER OF PRODUCTS
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              GRAND TOTAL
                            </th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              ACTION
                            </th>
                          </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                          {prescriptionProducts.map((prescProduct, index) => {
                            const numProducts = prescProduct.items?.length || 0;
                            const total = parseFloat(prescProduct.grand_total || 0);
                            const prescription = prescProduct.prescription;
                            const customer = customers.find(c => prescription && c.id === parseInt(prescription.customer_id));

                            return (
                              <tr key={prescProduct.id || index} className="hover:bg-gray-50">
                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                  {prescription?.date || 'N/A'}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                  {customer?.name || 'No Customer'}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm">
                                  {customer?.phone || 'N/A'}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                  {numProducts}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                  {formatCurrency({ amount: total, currency: prescProduct.currency || baseCurrency.name })}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                  <div className="flex gap-1">
                                    <Button
                                      variant="ghost"
                                      className="text-green-600 hover:text-green-800"
                                      onClick={() => loadPrescriptionItems(prescProduct)}
                                    >
                                      <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                      </svg>
                                    </Button>
                                    <Button
                                      variant="ghost"
                                      className="text-red-600 hover:text-red-800"
                                      onClick={() => cancelPrescriptionProduct(prescProduct.id)}
                                    >
                                      <X className="h-5 w-5" />
                                    </Button>
                                  </div>
                                </td>
                              </tr>
                            );
                          })}
                        </tbody>
                      </table>
                    </div>
                  ) : (
                    <div className="text-center py-4 text-gray-500">
                      No prescription products found.
                    </div>
                  )}
                </div>

                {/* Footer with cancel button */}
                <div className="flex justify-end gap-2 p-4 border-t">
                  <Button
                    variant="default"
                    onClick={() => setShowPrescriptionsModal(false)}
                    className="px-6"
                  >
                    Cancel
                  </Button>
                </div>
              </div>
            </Modal>

            <Link href={route('dashboard.index')}>
              <Button variant="secondary">Back</Button>
            </Link>
          </div>
        </div>
      </div>

      {/* ------------------------------------------------
          MAIN CONTENT AREA (fills remaining height)
          Responsive 2-column grid: stacked on small screens,
          50/50 columns on medium and larger screens.
      ------------------------------------------------ */}
      <div className="flex-1 overflow-hidden px-4 pb-4">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 h-full">
          {/* -------------------------------
              LEFT COLUMN: CART / SELECTED ITEMS
          ------------------------------- */}
          <div className="border rounded-md p-4 flex flex-col h-full relative">
            {/* Table header (visible on md and above) */}
            <div className="hidden md:grid grid-cols-[2fr_1fr_1fr_1fr_auto] font-semibold border-b pb-2">
              <span>PRODUCT</span>
              <span>UNIT COST</span>
              <span>QTY</span>
              <span>SUBTOTAL</span>
              <span>ACTION</span>
            </div>

            {/* Cart items - scrollable section */}
            <div className="flex-1 overflow-y-auto">
              {cartItems.length === 0 ? (
                <div className="flex items-center justify-center h-full text-muted-foreground">
                  Cart is empty. Add products by clicking items on the right or use the search bar.
                </div>
              ) : (
                cartItems.map(item => (
                  <div key={item.id} className="grid grid-cols-[2fr_1fr_1fr_1fr_auto] items-center border-b py-2 gap-2">
                    <div className="flex flex-col">
                      <span className="truncate" title={item.name}>{item.name}</span>
                      {item.unit_cost == 0 && <span className="text-red-500 text-xs">Are you sure this product/service is Zero ?</span>}
                    </div>
                    <div className="relative">
                      <Input
                        type="number"
                        value={item.unit_cost}
                        className="pl-6 h-8 w-full"
                        step="0.01"
                        min="0"
                        onChange={(e) => updateItemPrice(item.id, parseFloat(e.target.value) || 0)}
                      />
                    </div>
                    <Input
                      type="number"
                      value={item.quantity}
                      className="h-8 w-full text-center"
                      min="1"
                      onChange={(e) => updateItemQuantity(item.id, parseInt(e.target.value) || 1)}
                    />
                    <span>{formatCurrency({ amount: item.unit_cost * item.quantity, currency: data.currency })}</span>
                    <Button
                      variant="ghost"
                      size="icon"
                      className="text-red-500 h-8 w-8"
                      onClick={() => removeCartItem(item.id)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                ))
              )}
            </div>

            {/* Sticky footer with totals and action buttons */}
            <div className="absolute bottom-0 left-0 right-0 p-4 bg-white border-t">
              {/* Totals section */}
              <div className="text-right space-y-1 mb-3">
                <div>Sub Total: {formatCurrency({ amount: calculateSubtotal(), currency: data.currency })}</div>
                <div>Tax: {formatCurrency({ amount: calculateTaxes(), currency: data.currency })}</div>
                <div>Discount: -{formatCurrency({ amount: calculateDiscount(), currency: data.currency })}</div>
                <div className="font-bold">Total: {renderTotal()}</div>
              </div>

              {/* Action buttons */}
              <div className="flex flex-wrap justify-end gap-2">
                <Button variant="default" onClick={() => handleShowPaymentModal()}>Pay</Button>
                <Button variant="default" onClick={() => addTax()}>Tax</Button>
                <Button variant="default" onClick={() => changeCurrency()}>Change</Button>
                <Button variant="default" onClick={() => setShowDiscountModal(true)}>Discount</Button>
                <Button variant="default" onClick={handleCancel}>Cancel</Button>
                <Button variant="default" onClick={handleHold}>Hold</Button>
              </div>

              {/* Payment Modal */}
              <Modal
                show={showPaymentModal}
                onClose={() => setShowPaymentModal(false)}
                maxWidth="4xl"
              >
                <div>
                  <div className="flex justify-between items-center p-2 border-b">
                    <h2 className="text-lg">Payment Modal</h2>
                    <button
                      onClick={() => setShowPaymentModal(false)}
                      className="text-gray-500 hover:text-gray-700"
                    >
                      ×
                    </button>
                  </div>

                  <div className="p-2">
                    {/* Payment Method Options */}
                    <div className="flex gap-2 mb-6">
                      <Label className="bg-gray-100 px-4 py-2 rounded-md flex items-center">
                        <Input
                          type="radio"
                          id="cash"
                          name="paymentType"
                          checked={data.credit_cash == "cash"}
                          onChange={() => setData("credit_cash", "cash")}
                          className="h-4 w-4 mr-2 text-primary"
                        />
                        Cash
                      </Label>
                      <Label className="bg-gray-100 px-4 py-2 rounded-md flex items-center">
                        <Input
                          type="radio"
                          id="credit"
                          name="paymentType"
                          checked={data.credit_cash == "credit"}
                          onChange={() => setData("credit_cash", "credit")}
                          className="h-4 w-4 mr-2 text-primary"
                        />
                        Credit
                      </Label>
                      <Label className="bg-gray-100 px-4 py-2 rounded-md flex items-center">
                        <Input
                          type="radio"
                          id="provider"
                          name="paymentType"
                          checked={data.credit_cash == "provider"}
                          onChange={() => setData("credit_cash", "provider")}
                          className="h-4 w-4 mr-2 text-primary"
                        />
                        Provider
                      </Label>
                    </div>

                    {/* Payment Details */}
                    <div className="space-y-4">
                      <div className="flex justify-between items-center">
                        <Label>Sub Total</Label>
                        <span className="font-medium">{formatCurrency({ amount: calculateSubtotal(), currency: data.currency })}</span>
                      </div>

                      <div className="flex justify-between items-center">
                        <Label>Discount Amount</Label>
                        <span className="font-medium">{formatCurrency({ amount: calculateDiscount(), currency: data.currency })}</span>
                      </div>

                      <div className="flex justify-between items-center">
                        <Label>Total</Label>
                        <span className="font-medium">{renderTotal()}</span>
                      </div>

                      <div className="flex justify-between items-center">
                        <Label>Client</Label>
                        <div className="w-[70%]">
                          <SearchableCombobox
                            options={customers.map(client => ({
                              id: client.id,
                              name: client.name,
                            }))}
                            value={data.client_id}
                            onChange={(selectedValue) => {
                              setData("client_id", selectedValue);
                            }}
                            placeholder="Select a client"
                          />
                        </div>
                      </div>

                      {/* Provider field - only shown when Provider payment method is selected */}
                      {data.credit_cash === "provider" && (
                        <div className="flex justify-between items-center">
                          <Label>Provider</Label>
                          <div className="w-[70%]">
                            <SearchableCombobox
                              options={customers.map(customer => ({
                                id: customer.id,
                                name: customer.name,
                              }))}
                              value={data.customer_id}
                              onChange={(selectedValue) => {
                                setData("customer_id", selectedValue);
                              }}
                              placeholder="Select a customer"
                            />
                          </div>
                        </div>
                      )}

                      <div className="flex justify-between items-center">
                        <Label>Payment Method</Label>
                        <div className="w-[70%]">
                          <SearchableCombobox
                            options={methods.map(method => ({
                              id: method.name,
                              name: method.name,
                            }))}
                            value={data.method}
                            onChange={(selectedValue) => {
                              setData("method", selectedValue);
                            }}
                            placeholder="Select payment method"
                          />
                        </div>
                      </div>

                      {data.credit_cash === "cash" && (
                        <div className="flex justify-between items-center">
                          <Label>Payment Account</Label>
                          <div className="w-[70%]">
                            <SearchableCombobox
                              options={accounts.map(account => ({
                                id: account.id,
                                name: account.account_name,
                              }))}
                              value={data.account_id}
                              onChange={(selectedValue) => {
                                setData("account_id", selectedValue);
                              }}
                              placeholder="Select payment account"
                            />
                          </div>
                        </div>
                      )}

                      <div className="flex justify-between items-center">
                        <Label>Invoice Date</Label>
                        <div className="w-[70%]">
                          <DateTimePicker
                            value={data.invoice_date}
                            onChange={(date) => setData("invoice_date", date)}
                            required
                          />
                        </div>
                      </div>

                      {(data.credit_cash === "credit" || data.credit_cash === "provider") && (
                        <div className="flex justify-between items-center">
                          <Label>Due Date</Label>
                          <div className="w-[70%]">
                            <DateTimePicker
                              value={data.invoice_date}
                              onChange={(date) => setData("invoice_date", date)}
                              required
                            />
                          </div>
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Actions */}
                  <div className="flex justify-end gap-2 p-4 border-t">
                    <Button
                      variant="outline"
                      onClick={() => setShowPaymentModal(false)}
                      className="px-6"
                    >
                      Cancel
                    </Button>
                    <Button
                      variant="default"
                      onClick={() => {
                        // Handle save invoice logic here
                        handleSubmit();
                      }}
                      disabled={processing}
                    >
                      {processing ? "Saving..." : "Save Invoice"}
                    </Button>
                  </div>
                </div>
              </Modal>
              <Modal
                show={showCurrencyModal}
                onClose={() => setShowCurrencyModal(false)}
              >
                <div>
                  <div className="flex justify-between items-center p-2 border-b">
                    <h2 className="text-lg">Currency Modal</h2>
                    <button
                      onClick={() => setShowCurrencyModal(false)}
                      className="text-gray-500 hover:text-gray-700"
                    >
                      ×
                    </button>
                  </div>

                  <div className="p-4">
                    {/* Currency Details */}
                    <div className="space-y-4">
                      {/* Currency Selection */}
                      <div className="flex justify-between items-center">
                        <Label>Currency</Label>
                        <div className="w-[70%]">
                          <SearchableCombobox
                            options={currencies.map(currency => ({
                              id: currency.name,
                              value: currency.name,
                              label: currency.name,
                              name: `${currency.name} - ${currency.description} (${currency.exchange_rate})`
                            }))}
                            value={data.currency}
                            onChange={(selectedValue) => {
                              setData("currency", selectedValue);
                              handleCurrencyChange(selectedValue);
                            }}
                            placeholder="Select currency"
                          />
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Actions */}
                  <div className="flex justify-end gap-2 p-4 border-t">
                    <Button
                      variant="outline"
                      onClick={() => setShowCurrencyModal(false)}
                      className="px-6"
                    >
                      Cancel
                    </Button>
                  </div>
                </div>
              </Modal>
              <Modal
                show={showDiscountModal}
                onClose={() => setShowDiscountModal(false)}
              >
                <div>
                  <div className="flex justify-between items-center p-2 border-b">
                    <h2 className="text-lg">Discount Modal</h2>
                    <button
                      onClick={() => setShowDiscountModal(false)}
                      className="text-gray-500 hover:text-gray-700"
                    >
                      ×
                    </button>
                  </div>

                  <div className="p-4">
                    {/* Currency Details */}
                    <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                      {/* Currency Selection */}
                      <div className="grid grid-cols-12 mt-2">
                        <Label htmlFor="discount_type" className="md:col-span-3 col-span-12">
                          Discount Type
                        </Label>
                        <div className="md:col-span-9 col-span-12 md:mt-0 mt-2">
                          <SearchableCombobox
                            options={[
                              { id: "0", name: "Percentage (%)" },
                              { id: "1", name: "Fixed Amount" }
                            ]}
                            value={data.discount_type}
                            onChange={(value) => setData("discount_type", value)}
                            placeholder="Select discount type"
                          />
                          <InputError message={errors.discount_type} className="text-sm" />
                        </div>
                      </div>

                      <div className="grid grid-cols-12 mt-2">
                        <Label htmlFor="discount_value" className="md:col-span-3 col-span-12">
                          Discount Value
                        </Label>
                        <div className="md:col-span-9 col-span-12 md:mt-0 mt-2">
                          <Input
                            id="discount_value"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.discount_value}
                            onChange={(e) => setData("discount_value", parseFloat(e.target.value))}
                          />
                          <InputError message={errors.discount_value} className="text-sm" />
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Actions */}
                  <div className="flex justify-end gap-2 p-4 border-t">
                    <Button
                      variant="outline"
                      onClick={() => setShowDiscountModal(false)}
                      className="px-6"
                    >
                      Cancel
                    </Button>
                  </div>
                </div>
              </Modal>
            </div>
          </div>

          {/* -------------------------------
              RIGHT COLUMN: PRODUCT LISTING
          ------------------------------- */}
          <div className="border rounded-md p-4 flex flex-col h-full">
            {/* Category navigation - similar to tabs but using regular buttons */}
            <div className="flex gap-2 overflow-x-auto whitespace-nowrap pb-2">
              <Button
                variant="outline"
                className={isCategoryActive("all")}
                onClick={() => setActiveCategory("all")}
              >
                All
              </Button>

              {categories.map(category => (
                <Button
                  key={category.id}
                  variant="outline"
                  className={isCategoryActive(category.id.toString())}
                  onClick={() => setActiveCategory(category.id.toString())}
                >
                  {category.name}
                </Button>
              ))}
            </div>

            {/* Content area for products */}
            <div className="flex-1 mt-4">
              <ScrollArea className="h-[calc(100vh-130px)] overflow-y-auto">
                {filteredProducts.length === 0 ? (
                  <div className="text-center py-8 text-gray-500">
                    No products found. Try a different search or category.
                  </div>
                ) : (
                  <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    {filteredProducts.map(product => (
                      <div
                        key={product.id}
                        className="border rounded-md flex flex-col items-center justify-center text-center p-2 hover:bg-neutral-300 cursor-pointer transition-colors bg-neutral-200"
                        onClick={() => addToCart(product)}
                      >
                        {pos_product_image == 1 && (
                          <div className="w-14">
                            <img src={`/uploads/media/${product.image}`} alt={product.name} />
                          </div>
                        )}
                        <div className="font-medium w-full">{product.name}</div>
                        {product.code && (
                          <div className="text-xs text-gray-500">{product.code}</div>
                        )}
                        <div className="text-sm font-semibold mt-1">
                          {formatCurrency({ amount: product.selling_price })}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </ScrollArea>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
