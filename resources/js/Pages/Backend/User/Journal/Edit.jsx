import { Link, useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import InputError from "@/Components/InputError";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { useToast } from "@/hooks/use-toast";
import PageHeader from "@/Components/PageHeader";
import DateTimePicker from "@/Components/DateTimePicker";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import { PlusCircle, Trash } from "lucide-react";

export default function Edit({ currencies = [], accounts = [], customers = [], vendors = [], transactions = [], journal, }) {

  const { toast } = useToast();


  const { data, setData, post, processing, errors } = useForm({
    date: parseDateObject(journal.date),
    journal_number: journal.journal_number,
    trans_currency: journal.transaction_currency,
    journal_entries: transactions.map(transaction => ({
      account_id: transaction.account_id,
      debit: transaction.dr_cr === 'dr' ? transaction.transaction_amount : '',
      credit: transaction.dr_cr === 'cr' ? transaction.transaction_amount : '',
      date: parseDateObject(transaction.trans_date),
      description: transaction.description,
      customer_id: transaction.customer_id,
      vendor_id: transaction.vendor_id,
    })),
    _method: "PUT"
  });

  // Add row function
  const addRow = () => {
    const updatedEntries = [...data.journal_entries, {
      account_id: "",
      debit: "",
      credit: "",
      date: new Date(),
      description: "",
      customer_id: "",
      vendor_id: "",
    }];

    setData("journal_entries", updatedEntries);
  };

  // Remove row function
  const removeRow = (index) => {
    if (data.journal_entries.length <= 1) {
      toast({
        variant: "destructive",
        title: "Error",
        description: "Journal entry must have at least one line"
      });
      return;
    }

    const updatedEntries = data.journal_entries.filter((_, i) => i !== index);
    setData("journal_entries", updatedEntries);
  };

  // Function to handle currency change
  const handleCurrencyChange = (selectedValue) => {
    // Any additional logic needed when currency changes
    setData("trans_currency", selectedValue);
  };

  // Handle form submission
  const submit = (e) => {
    e.preventDefault();

    post(route("journals.update", journal.id), {
      onSuccess: () => {
        toast({
          title: "Success",
          description: "Journal entry updated successfully"
        });
      }
    });
  };

  // Check for form balance
  const isBalanced = () => {
    const totalDebit = data.journal_entries
      .map(entry => parseFloat(entry.debit || 0))
      .reduce((acc, val) => acc + val, 0);

    const totalCredit = data.journal_entries
      .map(entry => parseFloat(entry.credit || 0))
      .reduce((acc, val) => acc + val, 0);

    return Math.abs(totalDebit - totalCredit) < 0.001;
  };

  // Calculate totals
  const calculateTotals = () => {
    const totalDebit = data.journal_entries
      .map(entry => parseFloat(entry.debit || 0))
      .reduce((acc, val) => acc + val, 0);

    const totalCredit = data.journal_entries
      .map(entry => parseFloat(entry.credit || 0))
      .reduce((acc, val) => acc + val, 0);

    return { totalDebit, totalCredit, difference: totalDebit - totalCredit };
  };

  // Handle field change (ensure debit/credit are mutually exclusive)
  const handleFieldChange = (index, field, value) => {
    const updatedEntries = [...data.journal_entries];

    // If changing debit and value is not empty, clear credit for this row
    if (field === "debit" && value !== "") {
      updatedEntries[index].debit = value;
      updatedEntries[index].credit = "";
    }
    // If changing credit and value is not empty, clear debit for this row
    else if (field === "credit" && value !== "") {
      updatedEntries[index].credit = value;
      updatedEntries[index].debit = "";
    }
    // Otherwise just update the field normally
    else {
      updatedEntries[index][field] = value;
    }

    setData("journal_entries", updatedEntries);
  };

  // Calculate totals for display
  const totals = calculateTotals();
  const isFormBalanced = isBalanced();

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Journals"
            subpage="Edit journal entry"
            url="journals.index"
          />
          <div>
            {/* foreach errors */}
            {errors && Object.entries(errors).map(([key, value]) => (
              <p key={key} className="text-red-500 text-sm mt-2">
                {value}
              </p>
            ))}
          </div>
          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            <form onSubmit={submit}>
              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="date" className="md:col-span-2 col-span-12">
                  Journal Date *
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <DateTimePicker
                    value={data.date}
                    onChange={(date) => setData("date", date)}
                    className="md:w-1/2 w-full"
                    required
                  />
                  <InputError message={errors.date} className="mt-2" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="journal_number" className="md:col-span-2 col-span-12">
                  Journal Number *
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    id="journal_number"
                    value={data.journal_number}
                    onChange={(e) => setData("journal_number", e.target.value)}
                    className="md:w-1/2 w-full"
                    readOnly
                  />
                  <InputError message={errors.journal_number} className="mt-2" />
                </div>
              </div>

              <div className="grid grid-cols-12 mt-2">
                <Label htmlFor="trans_currency" className="md:col-span-2 col-span-12">
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
                      value={data.trans_currency}
                      onChange={(selectedValue) => {
                        setData("trans_currency", selectedValue);
                        handleCurrencyChange(selectedValue);
                      }}
                      placeholder="Select currency"
                    />
                  </div>
                  <InputError message={errors.trans_currency} className="text-sm" />
                </div>
              </div>

              {/* Journal Entries */}
              <div className="mt-6 overflow-x-auto">
                <h3 className="text-lg font-medium">Journal Entry Lines</h3>
                <div className="mt-4 min-w-full">
                  {/* Header row is now split into two rows */}
                  <div className="grid grid-cols-7 gap-2 mb-2 font-medium text-sm">
                    <div className="col-span-2">Date</div>
                    <div className="col-span-3">Account</div>
                    <div className="col-span-1">Debit</div>
                    <div className="col-span-1">Credit</div>
                  </div>
                  <div className="grid grid-cols-7 gap-2 mb-2 font-medium text-sm">
                    <div className="col-span-4">Description</div>
                    <div className="col-span-2">Customer/Vendor</div>
                    <div className="col-span-1 text-center">Actions</div>
                  </div>

                  {data.journal_entries.map((entry, index) => (
                    <div key={index} className="mb-6 border p-2 rounded-md bg-gray-50 relative">
                      {/* First row: Date, Account, Debit, Credit */}
                      <div className="grid grid-cols-7 gap-2 mb-2 items-start">
                        {/* Transaction Date */}
                        <div className="col-span-2">
                          <DateTimePicker
                            value={entry.date}
                            onChange={(date) => handleFieldChange(index, "date", date)}
                            required
                          />
                          <InputError message={errors[`journal_entries.${index}.date`]} className="mt-1" />
                        </div>

                        {/* Account */}
                        <div className="col-span-3">
                          <SearchableCombobox
                            value={entry.account_id}
                            onChange={(value) => handleFieldChange(index, "account_id", value)}
                            placeholder="Select an account"
                            options={accounts.map((account) => ({
                              id: account.id,
                              name: account.account_name
                            }))}
                            className="max-w-full"
                          />
                          <InputError message={errors[`journal_entries.${index}.account_id`]} className="mt-1" />
                        </div>

                        {/* Debit */}
                        <div className="col-span-1">
                          <Input
                            type="number"
                            step="0.01"
                            min="0"
                            className="text-right max-w-full"
                            value={entry.debit || ""}
                            onChange={(e) => handleFieldChange(index, "debit", e.target.value)}
                          />
                          <InputError message={errors[`journal_entries.${index}.debit`]} className="mt-1" />
                        </div>

                        {/* Credit */}
                        <div className="col-span-1">
                          <Input
                            type="number"
                            step="0.01"
                            min="0"
                            className="text-right max-w-full"
                            value={entry.credit || ""}
                            onChange={(e) => handleFieldChange(index, "credit", e.target.value)}
                          />
                          <InputError message={errors[`journal_entries.${index}.credit`]} className="mt-1" />
                        </div>
                      </div>

                      {/* Second row: Description, Customer/Vendor, Actions */}
                      <div className="grid grid-cols-7 gap-2 items-start">
                        {/* Description */}
                        <div className="col-span-4">
                          <Textarea
                            placeholder="Description"
                            className="min-h-[60px] max-w-full text-sm resize-vertical"
                            value={entry.description || ""}
                            onChange={(e) => handleFieldChange(index, "description", e.target.value)}
                          />
                          <InputError message={errors[`journal_entries.${index}.description`]} className="mt-1" />
                        </div>

                        {/* Customer/Vendor */}
                        <div className="col-span-2">
                          <div className="space-y-2">
                            <SearchableCombobox
                              value={entry.customer_id || ""}
                              onChange={(value) => {
                                // If selecting a customer, clear vendor
                                const updatedEntries = [...data.journal_entries];
                                updatedEntries[index].customer_id = value;
                                updatedEntries[index].vendor_id = "";
                                setData("journal_entries", updatedEntries);
                              }}
                              placeholder="Customer"
                              options={customers.map((customer) => ({
                                id: customer.id,
                                name: customer.name
                              }))}
                              className="max-w-full"
                            />
                            <InputError message={errors[`journal_entries.${index}.customer_id`]} className="mt-1" />

                            <SearchableCombobox
                              value={entry.vendor_id || ""}
                              onChange={(value) => {
                                // If selecting a vendor, clear customer
                                const updatedEntries = [...data.journal_entries];
                                updatedEntries[index].vendor_id = value;
                                updatedEntries[index].customer_id = "";
                                setData("journal_entries", updatedEntries);
                              }}
                              placeholder="Vendor"
                              options={vendors.map((vendor) => ({
                                id: vendor.id,
                                name: vendor.name
                              }))}
                              className="max-w-full"
                            />
                            <InputError message={errors[`journal_entries.${index}.vendor_id`]} className="mt-1" />
                          </div>
                        </div>

                        {/* Actions */}
                        <div className="col-span-1 flex justify-center items-center h-full">
                          <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            onClick={() => removeRow(index)}
                            className="text-red-500"
                          >
                            <Trash className="h-5 w-5" />
                            <span className="sr-only">Remove row</span>
                          </Button>
                        </div>
                      </div>
                    </div>
                  ))}
                  {/* Add Row Button */}
                  <div className="mt-4">
                    <Button
                      type="button"
                      variant="outline"
                      className="w-full"
                      onClick={addRow}
                    >
                      <PlusCircle className="mr-2 h-4 w-4" />
                      Add Line
                    </Button>
                  </div>
                </div>
              </div>

              {/* Totals and Submit */}
              <div className="flex flex-col md:flex-row gap-4 justify-between mt-8">
                <div>
                  <div className="flex space-x-4">
                    <Button variant="outline" asChild>
                      <Link href={route("journals.index")}>Cancel</Link>
                    </Button>
                    <Button
                      type="submit"
                      disabled={processing || !isFormBalanced || totals.totalDebit === 0}
                    >
                      Update Journal Entry
                    </Button>
                  </div>
                  {!isFormBalanced && (
                    <p className="text-red-500 text-sm mt-2">
                      Journal entry must be balanced (Debit = Credit)
                    </p>
                  )}
                  {totals.totalDebit === 0 && (
                    <p className="text-red-500 text-sm mt-2">
                      Journal entry must have values greater than zero
                    </p>
                  )}
                </div>

                <div className={`p-4 rounded-md ${isFormBalanced ? "bg-green-50 border border-green-200" : "bg-red-50 border border-red-200"}`}>
                  <div className="grid grid-cols-2 gap-4">
                    <div className="font-medium text-right">Total Debit:</div>
                    <div className="text-right font-medium">
                      {formatCurrency({ amount: totals.totalDebit, currency: data.trans_currency })}
                    </div>
                    <div className="font-medium text-right">Total Credit:</div>
                    <div className="text-right font-medium">
                      {formatCurrency({ amount: totals.totalCredit, currency: data.trans_currency })}
                    </div>
                    <div className="font-medium text-right">Difference:</div>
                    <div className={`text-right font-medium ${isFormBalanced ? "text-green-600" : "text-red-600"}`}>
                      {formatCurrency({ amount: totals.difference, currency: data.trans_currency })}
                    </div>
                  </div>
                </div>
              </div>

            </form>
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
