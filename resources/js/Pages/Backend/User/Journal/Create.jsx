import React, { useState, useEffect } from "react";
import { Link, router, usePage, useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import InputError from "@/Components/InputError";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { format } from "date-fns";
import { CalendarIcon, MinusCircle, PlusCircle, Trash } from "lucide-react";
import { Calendar } from "@/Components/ui/calendar";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/Components/ui/popover";
import { cn } from "@/lib/utils";
import { useToast } from "@/hooks/use-toast";
import PageHeader from "@/Components/PageHeader";

export default function Create({
  currencies = [],
  accounts = [],
  customers = [],
  vendors = [],
  journal_number = ""
}) {
  const { auth, flash, errors } = usePage().props;
  const { toast } = useToast();
  const activeBusinessCurrency = auth.active_business?.currency || "USD";

  const { data, setData, post, processing, reset } = useForm({
    date: new Date(),
    journal_number: journal_number,
    trans_currency: activeBusinessCurrency,
    description: "",
    journal_entries: [
      {
        account_id: "",
        debit: "",
        credit: "",
        date: new Date(),
        description: "",
        customer_id: "",
        vendor_id: "",
      }
    ]
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

  // Handle form submission
  const submit = (e) => {
    e.preventDefault();

    // Format dates before submitting
    const formattedData = {
      ...data,
      date: format(new Date(data.date), "yyyy-MM-dd"),
      journal_entries: data.journal_entries.map(entry => ({
        ...entry,
        date: format(new Date(entry.date), "yyyy-MM-dd"),
        debit: entry.debit || "0",
        credit: entry.credit || "0"
      }))
    };

    post(route("journals.store"), {
      onSuccess: () => {
        toast({
          title: "Success",
          description: "Journal entry created successfully"
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

  // Format currency
  const formatCurrency = (amount, currency = "USD") => {
    // Ensure we use proper ISO 4217 currency code
    const currencyCode = (currency || "USD").split(' ')[0];

    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: currencyCode,
    }).format(amount);
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
            subpage="Create new journal entry"
            url="journals.index"
          />
          <div className="p-4">
            <form onSubmit={submit} className="space-y-6">
              <div className="grid grid-cols-12 gap-4">
                {/* Journal Date */}
                <Label htmlFor="date" className="md:col-span-2 col-span-12">
                  Journal Date
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Popover>
                    <PopoverTrigger asChild>
                      <Button
                        variant={"outline"}
                        className={cn(
                          "w-full md:w-[240px] pl-3 text-left font-normal",
                          !data.date && "text-muted-foreground"
                        )}
                      >
                        {data.date ? (
                          format(new Date(data.date), "PP")
                        ) : (
                          <span>Pick a date</span>
                        )}
                        <CalendarIcon className="ml-auto h-4 w-4 opacity-50" />
                      </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-auto p-0" align="start">
                      <Calendar
                        mode="single"
                        selected={new Date(data.date)}
                        onSelect={(date) => setData("date", date)}
                        initialFocus
                      />
                    </PopoverContent>
                  </Popover>
                  <InputError message={errors.date} className="mt-2" />
                </div>

                {/* Journal Number */}
                <Label htmlFor="journal_number" className="md:col-span-2 col-span-12">
                  Journal Number
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <Input
                    id="journal_number"
                    value={data.journal_number}
                    onChange={(e) => setData("journal_number", e.target.value)}
                    className="md:w-[240px] w-full"
                    readOnly
                  />
                  <InputError message={errors.journal_number} className="mt-2" />
                </div>

                {/* Currency */}
                <Label htmlFor="trans_currency" className="md:col-span-2 col-span-12">
                  Currency
                </Label>
                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                  <SearchableCombobox
                    value={data.trans_currency}
                    onValueChange={(value) => setData("trans_currency", value)}
                    placeholder="Select a currency"
                    items={currencies.map((currency) => ({
                      value: currency.code,
                      label: `${currency.code} - ${currency.name}`
                    }))}
                    className="md:w-[240px] w-full"
                  />
                  <InputError message={errors.trans_currency} className="mt-2" />
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
                          <Popover>
                            <PopoverTrigger asChild>
                              <Button
                                variant={"outline"}
                                className={cn(
                                  "w-full pl-3 text-left font-normal text-sm truncate",
                                  !entry.date && "text-muted-foreground"
                                )}
                              >
                                {entry.date ? (
                                  format(new Date(entry.date), "PP")
                                ) : (
                                  <span>Pick a date</span>
                                )}
                                <CalendarIcon className="ml-auto h-4 w-4 opacity-50 flex-shrink-0" />
                              </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-auto p-0" align="start">
                              <Calendar
                                mode="single"
                                selected={new Date(entry.date)}
                                onSelect={(date) => handleFieldChange(index, "date", date)}
                                initialFocus
                              />
                            </PopoverContent>
                          </Popover>
                          <InputError message={errors[`journal_entries.${index}.date`]} className="mt-1" />
                        </div>

                        {/* Account */}
                        <div className="col-span-3">
                          <SearchableCombobox
                            value={entry.account_id}
                            onValueChange={(value) => handleFieldChange(index, "account_id", value)}
                            placeholder="Select an account"
                            items={accounts.map((account) => ({
                              value: account.id.toString(),
                              label: account.account_name
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
                              onValueChange={(value) => {
                                // If selecting a customer, clear vendor
                                const updatedEntries = [...data.journal_entries];
                                updatedEntries[index].customer_id = value;
                                updatedEntries[index].vendor_id = "";
                                setData("journal_entries", updatedEntries);
                              }}
                              placeholder="Customer"
                              items={customers.map((customer) => ({
                                value: customer.id.toString(),
                                label: customer.display_name
                              }))}
                              className="max-w-full"
                            />
                            <InputError message={errors[`journal_entries.${index}.customer_id`]} className="mt-1" />

                            <SearchableCombobox
                              value={entry.vendor_id || ""}
                              onValueChange={(value) => {
                                // If selecting a vendor, clear customer
                                const updatedEntries = [...data.journal_entries];
                                updatedEntries[index].vendor_id = value;
                                updatedEntries[index].customer_id = "";
                                setData("journal_entries", updatedEntries);
                              }}
                              placeholder="Vendor"
                              items={vendors.map((vendor) => ({
                                value: vendor.id.toString(),
                                label: vendor.display_name
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

                      {/* Entry number indicator */}
                      <div className="absolute -top-3 -left-3 bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold">
                        {index + 1}
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
                      Save Journal Entry
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
                      {formatCurrency(totals.totalDebit, data.trans_currency)}
                    </div>
                    <div className="font-medium text-right">Total Credit:</div>
                    <div className="text-right font-medium">
                      {formatCurrency(totals.totalCredit, data.trans_currency)}
                    </div>
                    <div className="font-medium text-right">Difference:</div>
                    <div className={`text-right font-medium ${isFormBalanced ? "text-green-600" : "text-red-600"}`}>
                      {formatCurrency(totals.difference, data.trans_currency)}
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
