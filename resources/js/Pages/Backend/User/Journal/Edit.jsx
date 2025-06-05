import { Link, useForm, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import InputError from "@/Components/InputError";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { useToast } from "@/hooks/use-toast";
import { Toaster } from "@/Components/ui/toaster";
import PageHeader from "@/Components/PageHeader";
import DateTimePicker from "@/Components/DateTimePicker";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import { PlusCircle, Trash, AlertCircle, CheckCircle2 } from "lucide-react";
import { Alert, AlertDescription } from "@/Components/ui/alert";
import { useEffect } from "react";

export default function Edit({
    currencies = [],
    accounts = [],
    customers = [],
    vendors = [],
    transactions = [],
    journal,
    projects = [],
    cost_codes = [],
    construction_module,
}) {
    const { toast } = useToast();
    const { flash } = usePage().props;
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

    const { data, setData, post, processing, errors } = useForm({
        date: parseDateObject(journal.date),
        journal_number: journal.journal_number,
        trans_currency: journal.transaction_currency,
        journal_entries: transactions.map((transaction) => ({
            account_id: transaction.account_id,
            debit:
                transaction.dr_cr === "dr"
                    ? transaction.transaction_amount
                    : "",
            credit:
                transaction.dr_cr === "cr"
                    ? transaction.transaction_amount
                    : "",
            date: parseDateObject(transaction.trans_date),
            description: transaction.description,
            customer_id: transaction.customer_id,
            vendor_id: transaction.vendor_id,
            project_id: transaction.project_id,
            project_task_id: transaction.project_task_id,
            cost_code_id: transaction.cost_code_id,
            quantity: transaction.quantity || "",
        })),
        _method: "PUT",
    });

    // Add row function
    const addRow = () => {
        const updatedEntries = [
            ...data.journal_entries,
            {
                account_id: "",
                debit: "",
                credit: "",
                date: new Date(),
                description: "",
                customer_id: "",
                vendor_id: "",
                project_id: "",
                project_task_id: "",
                cost_code_id: "",
                quantity: "",
            },
        ];

        setData("journal_entries", updatedEntries);
    };

    // Remove row function
    const removeRow = (index) => {
        if (data.journal_entries.length <= 1) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Journal entry must have at least one line",
            });
            return;
        }

        const updatedEntries = data.journal_entries.filter(
            (_, i) => i !== index
        );
        setData("journal_entries", updatedEntries);
    };

    // Function to handle currency change
    const handleCurrencyChange = (selectedValue) => {
        setData("trans_currency", selectedValue);
    };

    // Handle form submission
    const submit = (e) => {
        e.preventDefault();

        post(route("journals.update", journal.id), {
            onSuccess: () => {},
        });
    };

    // Check for form balance
    const isBalanced = () => {
        const totalDebit = data.journal_entries
            .map((entry) => parseFloat(entry.debit || 0))
            .reduce((acc, val) => acc + val, 0);

        const totalCredit = data.journal_entries
            .map((entry) => parseFloat(entry.credit || 0))
            .reduce((acc, val) => acc + val, 0);

        return Math.abs(totalDebit - totalCredit) < 0.001;
    };

    // Calculate totals
    const calculateTotals = () => {
        const totalDebit = data.journal_entries
            .map((entry) => parseFloat(entry.debit || 0))
            .reduce((acc, val) => acc + val, 0);

        const totalCredit = data.journal_entries
            .map((entry) => parseFloat(entry.credit || 0))
            .reduce((acc, val) => acc + val, 0);

        return {
            totalDebit,
            totalCredit,
            difference: totalDebit - totalCredit,
        };
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
                <Toaster />
                <div className="main-content">
                    <PageHeader
                        page="Journals"
                        subpage="Edit journal entry"
                        url="journals.index"
                    />
                    <div className="p-4">
                        {/* Error Summary */}
                        {Object.keys(errors).length > 0 && (
                            <Alert variant="destructive" className="mb-4">
                                <AlertCircle className="h-4 w-4" />
                                <AlertDescription>
                                    Please correct the following errors before
                                    proceeding:
                                    <ul className="list-disc list-inside mt-2">
                                        {Object.entries(errors).map(
                                            ([key, value]) => (
                                                <li
                                                    key={key}
                                                    className="text-sm"
                                                >
                                                    {value}
                                                </li>
                                            )
                                        )}
                                    </ul>
                                </AlertDescription>
                            </Alert>
                        )}

                        <form onSubmit={submit} className="space-y-8">
                            <div>
                                <div className="grid grid-cols-12">
                                    <Label
                                        htmlFor="date"
                                        className="md:col-span-2 col-span-12"
                                    >
                                        Journal Date *
                                    </Label>
                                    <div className="md:col-span-10 col-span-12">
                                        <DateTimePicker
                                            value={data.date}
                                            onChange={(date) =>
                                                setData("date", date)
                                            }
                                            className="md:w-1/2 w-full"
                                            required
                                        />
                                        <InputError
                                            message={errors.date}
                                            className="mt-2"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mt-3">
                                    <Label
                                        htmlFor="journal_number"
                                        className="md:col-span-2 col-span-12"
                                    >
                                        Journal Number *
                                    </Label>
                                    <div className="md:col-span-10 col-span-12">
                                        <Input
                                            id="journal_number"
                                            value={data.journal_number}
                                            onChange={(e) =>
                                                setData(
                                                    "journal_number",
                                                    e.target.value
                                                )
                                            }
                                            className="md:w-1/2 w-full bg-gray-50"
                                            readOnly
                                        />
                                        <InputError
                                            message={errors.journal_number}
                                            className="mt-2"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 mt-3">
                                    <Label
                                        htmlFor="trans_currency"
                                        className="md:col-span-2 col-span-12"
                                    >
                                        Currency *
                                    </Label>
                                    <div className="md:col-span-10 col-span-12">
                                        <div className="md:w-1/2 w-full">
                                            <SearchableCombobox
                                                className="mt-1"
                                                options={currencies.map(
                                                    (currency) => ({
                                                        id: currency.name,
                                                        value: currency.name,
                                                        label: currency.name,
                                                        name: `${currency.name} - ${currency.description} (${currency.exchange_rate})`,
                                                    })
                                                )}
                                                value={data.trans_currency}
                                                onChange={(selectedValue) => {
                                                    setData(
                                                        "trans_currency",
                                                        selectedValue
                                                    );
                                                    handleCurrencyChange(
                                                        selectedValue
                                                    );
                                                }}
                                                placeholder="Select currency"
                                            />
                                        </div>
                                        <InputError
                                            message={errors.trans_currency}
                                            className="text-sm"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Journal Entries Section */}
                            <div className="p-4">
                                <div className="flex justify-between items-center mb-4">
                                    <h2 className="text-lg font-semibold">
                                        Journal Entry Lines
                                    </h2>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={addRow}
                                        className="flex items-center gap-2"
                                    >
                                        <PlusCircle className="h-4 w-4" />
                                        Add Line
                                    </Button>
                                </div>

                                <div className="space-y-4">
                                    {/* Column Headers */}
                                    <div className="grid grid-cols-12 gap-4 text-sm font-medium text-gray-500 border-b pb-2">
                                        <div
                                            className={`${
                                                construction_module == 1
                                                    ? "col-span-1"
                                                    : "col-span-2"
                                            }`}
                                        >
                                            Date
                                        </div>
                                        <div
                                            className={`${
                                                construction_module == 1
                                                    ? "col-span-2"
                                                    : "col-span-3"
                                            }`}
                                        >
                                            Account
                                        </div>
                                        <div className="col-span-2">
                                            Description
                                        </div>
                                        <div className="col-span-2">
                                            Customer/Vendor
                                        </div>
                                        {construction_module == 1 && (
                                            <div className="col-span-2">
                                                Project/Task/Cost Code
                                            </div>
                                        )}
                                        {construction_module == 1 && (
                                            <div className="col-span-1">
                                                Quantity
                                            </div>
                                        )}
                                        <div
                                            className={`text-right ${
                                                construction_module == 1
                                                    ? "col-span-1"
                                                    : "col-span-2"
                                            }`}
                                        >
                                            Amount
                                        </div>
                                        <div className="col-span-1"></div>
                                    </div>

                                    {/* Journal Entry Lines */}
                                    {data.journal_entries.map(
                                        (entry, index) => (
                                            <div
                                                key={index}
                                                className="grid grid-cols-12 gap-2 items-start p-4 bg-gray-50 rounded-lg relative"
                                            >
                                                <div
                                                    className={`${
                                                        construction_module == 1
                                                            ? "col-span-1"
                                                            : "col-span-2"
                                                    }`}
                                                >
                                                    <DateTimePicker
                                                        value={entry.date}
                                                        onChange={(date) =>
                                                            handleFieldChange(
                                                                index,
                                                                "date",
                                                                date
                                                            )
                                                        }
                                                        required
                                                        className="w-full"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors[
                                                                `journal_entries.${index}.date`
                                                            ]
                                                        }
                                                        className="mt-1"
                                                    />
                                                </div>

                                                <div
                                                    className={`${
                                                        construction_module == 1
                                                            ? "col-span-2"
                                                            : "col-span-3"
                                                    }`}
                                                >
                                                    <SearchableCombobox
                                                        value={entry.account_id}
                                                        onChange={(value) =>
                                                            handleFieldChange(
                                                                index,
                                                                "account_id",
                                                                value
                                                            )
                                                        }
                                                        placeholder="Select an account"
                                                        options={accounts.map(
                                                            (account) => ({
                                                                id: account.id,
                                                                name: account.account_name,
                                                            })
                                                        )}
                                                        className="w-full"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors[
                                                                `journal_entries.${index}.account_id`
                                                            ]
                                                        }
                                                        className="mt-1"
                                                    />
                                                </div>

                                                <div className="col-span-2">
                                                    <Textarea
                                                        placeholder="Description"
                                                        className="min-h-[60px] w-full text-sm resize-vertical"
                                                        value={
                                                            entry.description ||
                                                            ""
                                                        }
                                                        onChange={(e) =>
                                                            handleFieldChange(
                                                                index,
                                                                "description",
                                                                e.target.value
                                                            )
                                                        }
                                                    />
                                                    <InputError
                                                        message={
                                                            errors[
                                                                `journal_entries.${index}.description`
                                                            ]
                                                        }
                                                        className="mt-1"
                                                    />
                                                </div>

                                                <div className="col-span-2">
                                                    <div className="space-y-2">
                                                        <SearchableCombobox
                                                            value={
                                                                entry.customer_id ||
                                                                ""
                                                            }
                                                            onChange={(
                                                                value
                                                            ) => {
                                                                const updatedEntries =
                                                                    [
                                                                        ...data.journal_entries,
                                                                    ];
                                                                updatedEntries[
                                                                    index
                                                                ].customer_id =
                                                                    value;
                                                                updatedEntries[
                                                                    index
                                                                ].vendor_id =
                                                                    "";
                                                                setData(
                                                                    "journal_entries",
                                                                    updatedEntries
                                                                );
                                                            }}
                                                            placeholder="Customer"
                                                            options={customers.map(
                                                                (customer) => ({
                                                                    id: customer.id,
                                                                    name: customer.name,
                                                                })
                                                            )}
                                                            className="w-full"
                                                        />
                                                        <SearchableCombobox
                                                            value={
                                                                entry.vendor_id ||
                                                                ""
                                                            }
                                                            onChange={(
                                                                value
                                                            ) => {
                                                                const updatedEntries =
                                                                    [
                                                                        ...data.journal_entries,
                                                                    ];
                                                                updatedEntries[
                                                                    index
                                                                ].vendor_id =
                                                                    value;
                                                                updatedEntries[
                                                                    index
                                                                ].customer_id =
                                                                    "";
                                                                setData(
                                                                    "journal_entries",
                                                                    updatedEntries
                                                                );
                                                            }}
                                                            placeholder="Vendor"
                                                            options={vendors.map(
                                                                (vendor) => ({
                                                                    id: vendor.id,
                                                                    name: vendor.name,
                                                                })
                                                            )}
                                                            className="w-full"
                                                        />
                                                    </div>
                                                </div>

                                                {construction_module == 1 && (
                                                    <div className="col-span-2">
                                                        <div className="space-y-2">
                                                            <SearchableCombobox
                                                                value={
                                                                    entry.project_id ||
                                                                    ""
                                                                }
                                                                onChange={(
                                                                    value
                                                                ) => {
                                                                    const updatedEntries =
                                                                        [
                                                                            ...data.journal_entries,
                                                                        ];
                                                                    updatedEntries[
                                                                        index
                                                                    ].project_id =
                                                                        value;
                                                                    setData(
                                                                        "journal_entries",
                                                                        updatedEntries
                                                                    );
                                                                }}
                                                                placeholder="Project"
                                                                options={projects.map(
                                                                    (
                                                                        project
                                                                    ) => ({
                                                                        id: project.id,
                                                                        name:
                                                                            project.project_code +
                                                                            " - " +
                                                                            project.project_name,
                                                                    })
                                                                )}
                                                                className="w-full"
                                                            />
                                                            <SearchableCombobox
                                                                value={
                                                                    entry.project_task_id ||
                                                                    ""
                                                                }
                                                                onChange={(
                                                                    value
                                                                ) => {
                                                                    const updatedEntries =
                                                                        [
                                                                            ...data.journal_entries,
                                                                        ];
                                                                    updatedEntries[
                                                                        index
                                                                    ].project_task_id =
                                                                        value;
                                                                    setData(
                                                                        "journal_entries",
                                                                        updatedEntries
                                                                    );
                                                                }}
                                                                placeholder="Project Task"
                                                                options={projects
                                                                    .find(
                                                                        (p) =>
                                                                            p.id ===
                                                                            Number(
                                                                                entry.project_id
                                                                            )
                                                                    )
                                                                    ?.tasks?.map(
                                                                        (
                                                                            task
                                                                        ) => ({
                                                                            id: task.id,
                                                                            name:
                                                                                task.task_code +
                                                                                " - " +
                                                                                task.description,
                                                                        })
                                                                    )}
                                                                className="w-full"
                                                            />
                                                            <SearchableCombobox
                                                                value={
                                                                    entry.cost_code_id ||
                                                                    ""
                                                                }
                                                                onChange={(
                                                                    value
                                                                ) => {
                                                                    const updatedEntries =
                                                                        [
                                                                            ...data.journal_entries,
                                                                        ];
                                                                    updatedEntries[
                                                                        index
                                                                    ].cost_code_id =
                                                                        value;
                                                                    setData(
                                                                        "journal_entries",
                                                                        updatedEntries
                                                                    );
                                                                }}
                                                                placeholder="Cost Code"
                                                                options={cost_codes.map(
                                                                    (
                                                                        cost_code
                                                                    ) => ({
                                                                        id: cost_code.id,
                                                                        name:
                                                                            cost_code.code +
                                                                            " - " +
                                                                            cost_code.description,
                                                                    })
                                                                )}
                                                                className="w-full"
                                                            />
                                                        </div>
                                                    </div>
                                                )}

                                                {construction_module == 1 && (
                                                    <div className="col-span-1">
                                                        <Input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            placeholder="Quantity"
                                                            className="text-right w-full"
                                                            value={
                                                                entry.quantity ||
                                                                ""
                                                            }
                                                            onChange={(e) =>
                                                                handleFieldChange(
                                                                    index,
                                                                    "quantity",
                                                                    e.target
                                                                        .value
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                )}

                                                <div className={`${construction_module == 1 ? "col-span-1" : "col-span-2"}`}>
                                                    <div className="space-y-2">
                                                        <Input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            className="text-right w-full"
                                                            value={
                                                                entry.debit ||
                                                                ""
                                                            }
                                                            onChange={(e) =>
                                                                handleFieldChange(
                                                                    index,
                                                                    "debit",
                                                                    e.target
                                                                        .value
                                                                )
                                                            }
                                                            placeholder="Debit"
                                                        />
                                                        <InputError
                                                            message={
                                                                errors[
                                                                    `journal_entries.${index}.debit`
                                                                ]
                                                            }
                                                            className="mt-1"
                                                        />

                                                        <Input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            className="text-right w-full"
                                                            value={
                                                                entry.credit ||
                                                                ""
                                                            }
                                                            onChange={(e) =>
                                                                handleFieldChange(
                                                                    index,
                                                                    "credit",
                                                                    e.target
                                                                        .value
                                                                )
                                                            }
                                                            placeholder="Credit"
                                                        />
                                                        <InputError
                                                            message={
                                                                errors[
                                                                    `journal_entries.${index}.credit`
                                                                ]
                                                            }
                                                            className="mt-1"
                                                        />
                                                    </div>
                                                </div>

                                                <div className="col-span-1 flex justify-center">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            removeRow(index)
                                                        }
                                                        className="text-red-500"
                                                    >
                                                        <Trash className="h-5 w-5" />
                                                        <span className="sr-only">
                                                            Remove row
                                                        </span>
                                                    </Button>
                                                </div>
                                            </div>
                                        )
                                    )}
                                </div>
                            </div>

                            {/* Summary and Actions Section */}
                            <div className="p-4">
                                <div className="flex flex-col md:flex-row justify-between items-start gap-6">
                                    <div className="space-y-4">
                                        <div className="flex items-center gap-2">
                                            {isFormBalanced ? (
                                                <CheckCircle2 className="h-5 w-5 text-green-500" />
                                            ) : (
                                                <AlertCircle className="h-5 w-5 text-red-500" />
                                            )}
                                            <span
                                                className={
                                                    isFormBalanced
                                                        ? "text-green-600"
                                                        : "text-red-600"
                                                }
                                            >
                                                {isFormBalanced
                                                    ? "Journal is balanced"
                                                    : "Journal is not balanced"}
                                            </span>
                                        </div>

                                        <div className="flex gap-4">
                                            <Button variant="outline" asChild>
                                                <Link
                                                    href={route(
                                                        "journals.index"
                                                    )}
                                                >
                                                    Cancel
                                                </Link>
                                            </Button>
                                            <Button
                                                type="submit"
                                                disabled={
                                                    processing ||
                                                    !isFormBalanced ||
                                                    totals.totalDebit === 0
                                                }
                                            >
                                                Update Journal Entry
                                            </Button>
                                        </div>
                                    </div>

                                    <div
                                        className={`p-4 rounded-md ${
                                            isFormBalanced
                                                ? "bg-green-50 border border-green-200"
                                                : "bg-red-50 border border-red-200"
                                        }`}
                                    >
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="font-medium text-right">
                                                Total Debit:
                                            </div>
                                            <div className="text-right font-medium">
                                                {formatCurrency({
                                                    amount: totals.totalDebit,
                                                    currency:
                                                        data.trans_currency,
                                                })}
                                            </div>
                                            <div className="font-medium text-right">
                                                Total Credit:
                                            </div>
                                            <div className="text-right font-medium">
                                                {formatCurrency({
                                                    amount: totals.totalCredit,
                                                    currency:
                                                        data.trans_currency,
                                                })}
                                            </div>
                                            <div className="font-medium text-right">
                                                Difference:
                                            </div>
                                            <div
                                                className={`text-right font-medium ${
                                                    isFormBalanced
                                                        ? "text-green-600"
                                                        : "text-red-600"
                                                }`}
                                            >
                                                {formatCurrency({
                                                    amount: totals.difference,
                                                    currency:
                                                        data.trans_currency,
                                                })}
                                            </div>
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
