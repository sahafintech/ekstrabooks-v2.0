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
import { formatCurrency } from "@/lib/utils";
import { useState, useEffect } from "react";
import DateTimePicker from "@/Components/DateTimePicker";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";

export default function Create({ vendors = [], projects = [], costCodes = [], taxes = [], currencies = [], base_currency, accounts = [] }) {
  const [contractItems, setContractItems] = useState([{
    project_task_id: "",
    cost_code_id: "",
    uom: "",
    quantity: 1,
    unit_cost: 0,
    account_id: "",
  }]);

  const [attachments, setAttachments] = useState([]);
  const [exchangeRate, setExchangeRate] = useState(1);
  const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);

  const { data, setData, post, processing, errors, reset } = useForm({
    vendor_id: "",
    subcontract_no: "",
    project_id: "",
    start_date: new Date(),
    end_date: "",
    description: "",
    currency: base_currency,
    exchange_rate: 1,
    converted_total: 0,
    discount_type: "0",
    discount_value: 0,
    attachments: [],
    project_task_id: [],
    cost_code_id: [],
    uom: [],
    quantity: [],
    unit_cost: [],
    account_id: [],
    taxes: [],
  });

  const addContractItem = () => {
    setContractItems([...contractItems, {
      project_task_id: "",
      cost_code_id: "",
      uom: "",
      quantity: 1,
      unit_cost: 0,
      account_id: "",
    }]);
    setData("project_task_id", [...data.project_task_id, ""]);
    setData("cost_code_id", [...data.cost_code_id, ""]);
    setData("uom", [...data.uom, ""]);
    setData("quantity", [...data.quantity, 1]);
    setData("unit_cost", [...data.unit_cost, 0]);
    setData("account_id", [...data.account_id, ""]);
  };

  const removeContractItem = (index) => {
    const updatedItems = contractItems.filter((_, i) => i !== index);
    setContractItems(updatedItems);
    setData("project_task_id", updatedItems.map(item => item.project_task_id));
    setData("cost_code_id", updatedItems.map(item => item.cost_code_id));
    setData("uom", updatedItems.map(item => item.uom));
    setData("quantity", updatedItems.map(item => item.quantity));
    setData("unit_cost", updatedItems.map(item => item.unit_cost));
    setData("account_id", updatedItems.map(item => item.account_id));
  };

  const updateContractItem = (index, field, value) => {
    const updatedItems = [...contractItems];
    updatedItems[index][field] = value;
    setContractItems(updatedItems);
    setData("project_task_id", updatedItems.map(item => item.project_task_id));
    setData("cost_code_id", updatedItems.map(item => item.cost_code_id));
    setData("uom", updatedItems.map(item => item.uom));
    setData("quantity", updatedItems.map(item => item.quantity));
    setData("unit_cost", updatedItems.map(item => item.unit_cost));
    setData("account_id", updatedItems.map(item => item.account_id));
  };

  const calculateSubtotal = () => {
    return contractItems.reduce((sum, item) => sum + (item.quantity * item.unit_cost), 0);
  };

  const taxRateMap = new Map(taxes.map(t => [t.id, Number(t.rate)]));

  const calculateTaxes = () => {
    return contractItems.reduce((sum, item) => {
      const base = Number(item.quantity) * Number(item.unit_cost);
      const itemTax = data.taxes.reduce((taxSum, taxIdStr) => {
        const taxId = Number(taxIdStr);
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
        });
    }
  };

  useEffect(() => {
    const total = calculateTotal();
    const convertedTotal = total;
    setData('converted_total', convertedTotal);
  }, [data.currency, contractItems, data.discount_type, data.discount_value, exchangeRate]);

  useEffect(() => {
    setData("attachments", attachments);
  }, [attachments]);

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

    if (baseCurrencyInfo &&
      selectedCurrency.name !== baseCurrencyInfo.name &&
      exchangeRate &&
      exchangeRate !== 1) {
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

    const formData = {
      ...data,
      currency: selectedCurrency.name,
      exchange_rate: exchangeRate,
      project_task_id: contractItems.map(item => item.project_task_id),
      cost_code_id: contractItems.map(item => item.cost_code_id),
      uom: contractItems.map(item => item.uom),
      quantity: contractItems.map(item => item.quantity),
      unit_cost: contractItems.map(item => item.unit_cost),
      account_id: contractItems.map(item => item.account_id),
    };

    post(route("project_subcontracts.store"), formData, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Subcontract created successfully");
        reset();
        setContractItems([{
          project_task_id: "",
          cost_code_id: "",
          uom: "",
          quantity: 1,
          unit_cost: 0,
          account_id: "",
        }]);
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Subcontracts" subpage="Create New" url="project_subcontracts.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <form onSubmit={submit}>
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="vendor_id" className="md:col-span-2 col-span-12">
                Vendor *
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
              <Label htmlFor="project_id" className="md:col-span-2 col-span-12">
                Project *
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

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="subcontract_no" className="md:col-span-2 col-span-12">
                Subcontract No *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="subcontract_no"
                  type="text"
                  value={data.subcontract_no}
                  onChange={(e) => setData("subcontract_no", e.target.value)}
                  className="md:w-1/2 w-full"
                  required
                />
                <InputError message={errors.subcontract_no} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="start_date" className="md:col-span-2 col-span-12">
                Start Date *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <DateTimePicker
                  value={data.start_date}
                  onChange={(date) => setData("start_date", date)}
                  className="md:w-1/2 w-full"
                  required
                />
                <InputError message={errors.start_date} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="end_date" className="md:col-span-2 col-span-12">
                End Date *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <DateTimePicker
                  value={data.end_date}
                  onChange={(date) => setData("end_date", date)}
                  className="md:w-1/2 w-full"
                  required
                />
                <InputError message={errors.end_date} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="end_date" className="md:col-span-2 col-span-12">
                Description
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Textarea
                  value={data.description}
                  onChange={(e) => setData("description", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.description} className="text-sm" />
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
                <h3 className="text-lg font-medium">Contract Items</h3>
                <Button variant="secondary" type="button" onClick={addContractItem}>
                  <Plus className="w-4 h-4 mr-2" />
                  Add Item
                </Button>
              </div>

              {contractItems.map((item, index) => (
                <div key={index} className="border rounded-lg p-4 space-y-4 bg-gray-50">
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-2">
                  <div>
                      <Label>Account *</Label>
                      <SearchableCombobox
                        options={accounts.map(account => ({
                          id: account.id,
                          name: account.account_name
                        }))}
                        value={item.account_id}
                        onChange={(value) => updateContractItem(index, "account_id", value)}
                        placeholder="Select account"
                      />
                    </div>
                    <div>
                      <Label>Project Task *</Label>
                      <SearchableCombobox
                        options={projects.find(p => p.id === Number(data.project_id))?.tasks?.map(task => ({
                          id: task.id,
                          name: task.task_code + " - " + task.description
                        }))}
                        value={item.project_task_id}
                        onChange={(value) => updateContractItem(index, "project_task_id", value)}
                        placeholder="Select task"
                      />
                    </div>

                    <div>
                      <Label>Cost Code *</Label>
                      <SearchableCombobox
                        options={costCodes.map(code => ({
                          id: code.id,
                          name: code.code + " - " + code.description
                        }))}
                        value={item.cost_code_id}
                        onChange={(value) => updateContractItem(index, "cost_code_id", value)}
                        placeholder="Select cost code"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-12 gap-2">
                  <div className="md:col-span-3">
                      <Label>UOM</Label>
                      <Input
                        type="text"
                        value={item.uom}
                        onChange={(e) => updateContractItem(index, "uom", e.target.value)}
                      />
                    </div>
                    <div className="md:col-span-3">
                      <Label>Quantity *</Label>
                      <Input
                        type="number"
                        min="1"
                        value={item.quantity}
                        onChange={(e) => updateContractItem(index, "quantity", parseInt(e.target.value))}
                      />
                    </div>

                    <div className="md:col-span-3">
                      <Label>Unit Cost *</Label>
                      <Input
                        type="number"
                        step="0.01"
                        value={item.unit_cost}
                        onChange={(e) => updateContractItem(index, "unit_cost", parseFloat(e.target.value))}
                      />
                    </div>

                    <div className="md:col-span-1 flex items-center justify-end">
                      {contractItems.length > 1 && (
                        <Button
                          type="button"
                          variant="ghost"
                          size="icon"
                          className="text-red-500"
                          onClick={() => removeContractItem(index)}
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

            <SidebarSeparator className="my-4" />

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
                    setContractItems([{
                      project_task_id: "",
                      cost_code_id: "",
                      uom: "",
                      quantity: 1,
                      unit_cost: 0,
                      account_id: "",
                    }]);
                  }}
                >
                  Reset
                </Button>
                <Button type="submit" disabled={processing}>
                  Create Subcontract
                </Button>
              </div>
            </div>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
