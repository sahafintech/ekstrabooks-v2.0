import { useForm, usePage } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast, Toaster } from "sonner";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Textarea } from "@/Components/ui/textarea";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import { useEffect, useState } from "react";
import DateTimePicker from "@/Components/DateTimePicker";
import { Plus, Trash2 } from "lucide-react";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import Attachment from "@/Components/ui/attachment";

const createEmptyLine = () => ({
  account_id: "",
  product_name: "",
  description: "",
  quantity: 1,
  unit_cost: 0,
  project_id: null,
  project_task_id: null,
  cost_code_id: null,
});

const toNumber = (value, fallback = 0) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
};

const mapBillItemsToLines = (items = []) =>
  items.length > 0
    ? items.map((item) => ({
        account_id: item.account_id || "",
        product_name: item.product_name || "",
        description: item.description || "",
        quantity: toNumber(item.quantity, 1),
        unit_cost: toNumber(item.unit_cost),
        project_id: item.project_id,
        project_task_id: item.project_task_id,
        cost_code_id: item.cost_code_id,
      }))
    : [createEmptyLine()];

export default function Edit({
  vendors = [],
  bill,
  currencies = [],
  taxes = [],
  accounts = [],
  taxIds = [],
  theAttachments = [],
  projects = [],
  cost_codes = [],
  construction_module,
}) {
  const { flash = {} } = usePage().props;
  const [accountLines, setAccountLines] = useState(mapBillItemsToLines(bill.items));
  const [exchangeRate, setExchangeRate] = useState(toNumber(bill.exchange_rate, 1));
  const [baseCurrencyInfo, setBaseCurrencyInfo] = useState(null);
  const [attachments, setAttachments] = useState(
    theAttachments.map((attachment) => attachment.path)
  );

  const { data, setData, post, processing, errors, reset } = useForm({
    vendor_id: bill.vendor_id,
    title: bill.title,
    bill_no: bill.bill_no,
    po_so_number: bill.po_so_number,
    purchase_date: parseDateObject(bill.purchase_date),
    due_date: parseDateObject(bill.due_date),
    currency: bill.currency,
    exchange_rate: bill.exchange_rate,
    converted_total: bill.converted_total,
    discount_type: bill.discount_type,
    discount_value: bill.discount_value,
    template: bill.template,
    note: bill.note,
    footer: bill.footer,
    attachments: [],
    product_id: [],
    product_name: [],
    description: [],
    quantity: [],
    unit_cost: [],
    taxes: taxIds,
    account_id: [],
    benificiary: bill.benificiary,
    project_id: [],
    project_task_id: [],
    cost_code_id: [],
    hospital_purchase: 1,
    _method: "PUT",
  });

  useEffect(() => {
    setData("product_id", accountLines.map(() => ""));
    setData("product_name", accountLines.map((line) => line.product_name || ""));
    setData("account_id", accountLines.map((line) => line.account_id || ""));
    setData("description", accountLines.map((line) => line.description || ""));
    setData("quantity", accountLines.map((line) => toNumber(line.quantity, 1)));
    setData("unit_cost", accountLines.map((line) => toNumber(line.unit_cost)));
    setData("project_id", accountLines.map((line) => line.project_id));
    setData("project_task_id", accountLines.map((line) => line.project_task_id));
    setData("cost_code_id", accountLines.map((line) => line.cost_code_id));
    setData("attachments", attachments);
  }, [accountLines, attachments, setData]);

  useEffect(() => {
    const baseCurrency =
      currencies.find((currency) => currency.base_currency === 1) ||
      currencies[0] ||
      null;

    setBaseCurrencyInfo(baseCurrency);
  }, [currencies]);

  const calculateSubtotal = () =>
    accountLines.reduce(
      (sum, line) => sum + toNumber(line.quantity, 1) * toNumber(line.unit_cost),
      0
    );

  const taxRateMap = new Map(taxes.map((tax) => [tax.id, Number(tax.rate)]));

  const calculateTaxes = () =>
    accountLines.reduce((sum, line) => {
      const baseAmount = toNumber(line.quantity, 1) * toNumber(line.unit_cost);
      const lineTax = data.taxes.reduce((taxSum, taxId) => {
        const rate = taxRateMap.get(Number(taxId)) || 0;
        return taxSum + (baseAmount * rate) / 100;
      }, 0);

      return sum + lineTax;
    }, 0);

  const calculateDiscount = () => {
    const subtotal = calculateSubtotal();
    if (data.discount_type === "0") {
      return (subtotal * toNumber(data.discount_value)) / 100;
    }

    return toNumber(data.discount_value);
  };

  const calculateTotal = () => calculateSubtotal() + calculateTaxes() - calculateDiscount();

  useEffect(() => {
    setData("converted_total", calculateTotal());
  }, [data.currency, data.discount_type, data.discount_value, exchangeRate, accountLines, setData]);

  useEffect(() => {
    if (flash.success) {
      toast.success(flash.success);
    }

    if (flash.error) {
      toast.error(flash.error);
    }
  }, [flash]);

  const handleCurrencyChange = (currencyName) => {
    const currencyObj = currencies.find((currency) => currency.name === currencyName);

    if (!currencyObj) {
      return;
    }

    const currentRate = parseFloat(currencyObj.exchange_rate);
    setExchangeRate(currentRate);
    setData("exchange_rate", currentRate);

    fetch(`/user/find_currency/${currencyObj.name}`)
      .then((response) => response.json())
      .then((apiData) => {
        if (apiData?.exchange_rate) {
          const apiRate = parseFloat(apiData.exchange_rate);
          setExchangeRate(apiRate);
          setData("exchange_rate", apiRate);
        }
      })
      .catch(() => {});
  };

  const addAccountLine = () => {
    setAccountLines((prev) => [...prev, createEmptyLine()]);
  };

  const removeAccountLine = (index) => {
    setAccountLines((prev) => prev.filter((_, lineIndex) => lineIndex !== index));
  };

  const updateAccountLine = (index, field, value) => {
    setAccountLines((prev) =>
      prev.map((line, lineIndex) =>
        lineIndex === index
          ? {
              ...line,
              [field]: value,
              ...(field === "project_id" ? { project_task_id: null } : {}),
            }
          : line
      )
    );
  };

  const renderTotal = () => {
    const total = calculateTotal();
    const selectedCurrency = currencies.find((currency) => currency.name === data.currency);

    if (!selectedCurrency) {
      return <h2 className="text-xl font-bold">Total: 0.00</h2>;
    }

    if (
      baseCurrencyInfo &&
      selectedCurrency.name !== baseCurrencyInfo.name &&
      exchangeRate &&
      exchangeRate !== 1
    ) {
      const baseCurrencyTotal = total / exchangeRate;

      return (
        <div>
          <h2 className="text-xl font-bold">
            Total: {formatCurrency({ amount: total, currency: selectedCurrency.name })}
          </h2>
          <p className="text-sm text-gray-600">
            Equivalent to {formatCurrency({ amount: baseCurrencyTotal, currency: baseCurrencyInfo.name })}
          </p>
        </div>
      );
    }

    return (
      <h2 className="text-xl font-bold">
        Total: {formatCurrency({ amount: total, currency: selectedCurrency.name })}
      </h2>
    );
  };

  const resetForm = () => {
    reset();
    setExchangeRate(toNumber(bill.exchange_rate, 1));
    setAccountLines(mapBillItemsToLines(bill.items));
    setAttachments(theAttachments.map((attachment) => attachment.path));
  };

  const submit = (event) => {
    event.preventDefault();

    const selectedCurrency = currencies.find((currency) => currency.name === data.currency);

    if (!selectedCurrency) {
      toast.error("Please select a valid currency");
      return;
    }

    const formData = {
      ...data,
      currency: selectedCurrency.name,
      exchange_rate: exchangeRate,
      hospital_purchase: 1,
      product_id: accountLines.map(() => ""),
      product_name: accountLines.map((line) => line.product_name || ""),
      description: accountLines.map((line) => line.description || ""),
      quantity: accountLines.map((line) => toNumber(line.quantity, 1)),
      unit_cost: accountLines.map((line) => toNumber(line.unit_cost)),
      account_id: accountLines.map((line) => line.account_id || ""),
      project_id: accountLines.map((line) => line.project_id),
      project_task_id: accountLines.map((line) => line.project_task_id),
      cost_code_id: accountLines.map((line) => line.cost_code_id),
    };

    post(route("hospital_purchases.update", bill.id), formData, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Hospital purchase updated successfully");
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <Toaster position="top-center" />
      <SidebarInset>
        <PageHeader page="Hospital Purchases" subpage="Edit Hospital Purchase" url="hospital_purchases.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <form onSubmit={submit}>
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="vendor_id" className="md:col-span-2 col-span-12">
                Suppliers
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={vendors.map((vendor) => ({
                      id: vendor.id,
                      name: vendor.name,
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
                  onChange={(event) => setData("title", event.target.value)}
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
                  onChange={(event) => setData("po_so_number", event.target.value)}
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
                    options={currencies.map((currency) => ({
                      id: currency.name,
                      value: currency.name,
                      label: currency.name,
                      name: `${currency.name} - ${currency.description} (${currency.exchange_rate})`,
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

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="benificiary" className="md:col-span-2 col-span-12">
                Benificiary
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <Textarea
                    className="mt-1"
                    value={data.benificiary}
                    onChange={(event) => setData("benificiary", event.target.value)}
                    placeholder="Enter benificiary details"
                  />
                </div>
                <InputError message={errors.benificiary} className="text-sm" />
              </div>
            </div>

            <SidebarSeparator className="my-4" />

            <div className="space-y-4">
              <div className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                Hospital purchases use account lines only. Product inventory items are intentionally disabled in this flow.
              </div>

              <div className="flex justify-between items-center">
                <h3 className="text-lg font-medium">Hospital Purchase Lines</h3>
                <Button type="button" variant="secondary" onClick={addAccountLine}>
                  <Plus className="w-4 h-4 mr-2" />
                  Add Account Line
                </Button>
              </div>

              {accountLines.map((line, index) => (
                <div key={`account-${index}`} className="border rounded-lg p-4 space-y-4 bg-gray-50">
                  <div className={`grid grid-cols-1 md:grid-cols-${construction_module == 1 ? "10" : "7"} gap-2`}>
                    <div>
                      <Label>Account *</Label>
                      <SearchableCombobox
                        options={accounts.map((account) => ({
                          id: account.id,
                          name: account.account_name,
                        }))}
                        value={line.account_id}
                        onChange={(value) => updateAccountLine(index, "account_id", value)}
                        placeholder="Select account"
                      />
                    </div>

                    <div>
                      <Label>Item Name *</Label>
                      <Input
                        type="text"
                        value={line.product_name || ""}
                        onChange={(event) => updateAccountLine(index, "product_name", event.target.value)}
                        placeholder="Enter item or service name"
                      />
                    </div>

                    <div>
                      <Label>Quantity *</Label>
                      <Input
                        type="number"
                        step="0.01"
                        min="0"
                        value={line.quantity}
                        onChange={(event) => updateAccountLine(index, "quantity", toNumber(event.target.value, 1))}
                      />
                    </div>

                    <div>
                      <Label>Amount *</Label>
                      <Input
                        type="number"
                        step="0.01"
                        min="0"
                        value={line.unit_cost}
                        onChange={(event) => updateAccountLine(index, "unit_cost", toNumber(event.target.value))}
                      />
                    </div>

                    <div>
                      <Label>Description</Label>
                      <Textarea
                        value={line.description || ""}
                        onChange={(event) => updateAccountLine(index, "description", event.target.value)}
                        className="min-h-[30px] resize-none overflow-hidden"
                        rows={1}
                      />
                    </div>

                    {construction_module == 1 && (
                      <>
                        <div>
                          <Label>Project</Label>
                          <SearchableCombobox
                            options={projects.map((project) => ({
                              id: project.id,
                              name: `${project.project_code} - ${project.project_name}`,
                            }))}
                            value={line.project_id}
                            onChange={(value) => updateAccountLine(index, "project_id", value)}
                            placeholder="Select project"
                          />
                        </div>

                        <div>
                          <Label>Project Task</Label>
                          <SearchableCombobox
                            options={
                              projects
                                .find((project) => project.id === Number(line.project_id))
                                ?.tasks?.map((task) => ({
                                  id: task.id,
                                  name: `${task.task_code} - ${task.description}`,
                                })) || []
                            }
                            value={line.project_task_id}
                            onChange={(value) => updateAccountLine(index, "project_task_id", value)}
                            placeholder="Select project task"
                          />
                        </div>

                        <div>
                          <Label>Cost Code</Label>
                          <SearchableCombobox
                            options={cost_codes.map((costCode) => ({
                              id: costCode.id,
                              name: `${costCode.code} - ${costCode.description}`,
                            }))}
                            value={line.cost_code_id}
                            onChange={(value) => updateAccountLine(index, "cost_code_id", value)}
                            placeholder="Select cost code"
                          />
                        </div>
                      </>
                    )}

                    <div>
                      <Label>Subtotal</Label>
                      <div className="p-2 bg-white rounded mt-1 text-right">
                        {(toNumber(line.quantity, 1) * toNumber(line.unit_cost)).toFixed(2)}
                      </div>
                    </div>

                    <div className="md:col-span-1 flex items-center justify-center">
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="text-red-500"
                        onClick={() => removeAccountLine(index)}
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
                    options={taxes.map((tax) => ({
                      id: tax.id,
                      name: `${tax.name} (${tax.rate}%)`,
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
                      { id: "1", name: "Fixed Amount" },
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
                  onChange={(event) => setData("discount_value", toNumber(event.target.value))}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.discount_value} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="note" className="md:col-span-2 col-span-12">
                Your Message to Supplier
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Textarea
                  id="note"
                  value={data.note}
                  onChange={(event) => setData("note", event.target.value)}
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
                  onChange={(event) => setData("footer", event.target.value)}
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
                  onAdd={(files) => setAttachments((prev) => [...prev, ...files])}
                  onRemove={(index) => setAttachments((prev) => prev.filter((_, fileIndex) => fileIndex !== index))}
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
                <Button type="button" variant="secondary" onClick={resetForm}>
                  Reset
                </Button>
                <Button type="submit" disabled={processing}>
                  Update Hospital Purchase
                </Button>
              </div>
            </div>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
