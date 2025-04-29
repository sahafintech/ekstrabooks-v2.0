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
import { formatCurrency } from "@/lib/utils";
import { useState, useEffect } from "react";
import axios from "axios";
// Ensure all necessary table components are imported
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from "@/Components/ui/table";
import { Checkbox } from "@/Components/ui/checkbox";
import DateTimePicker from "@/Components/DateTimePicker";

export default function Create({ customers = [], accounts, methods }) {
    const [invoices, setInvoices] = useState([]);
    const [selectedInvoices, setSelectedInvoices] = useState([]);

    const { data, setData, post, processing, errors, reset } = useForm({
        customer_id: "",
        trans_date: new Date(),
        account_id: "",
        method: "",
        reference: "",
        attachment: null,
        // This field will store only the selected invoices and their amounts
        invoices: [],
    });

    // Fetch invoices when a customer is selected
    useEffect(() => {
        if (data.customer_id) {
            axios.get(route("customer.get_invoices", data.customer_id))
                .then(response => {
                    // Optionally, initialize the amount field for each invoice
                    const fetchedInvoices = response.data.invoices.map(invoice => ({
                        ...invoice,
                        // Initialize amount with the remaining due: grand_total - paid.
                        amount: invoice.grand_total - invoice.paid,
                    }));
                    setInvoices(fetchedInvoices);
                    setSelectedInvoices([]); // Reset selections when customer changes
                })
                .catch(error => {
                    console.error("Fetching invoices Error:", error);
                });
        } else {
            setInvoices([]);
            setSelectedInvoices([]);
        }
    }, [data.customer_id]);

    // Update form's invoices field whenever selectedInvoices or invoice amounts change
    useEffect(() => {
        const selectedInvoiceData = invoices
            .filter(invoice => selectedInvoices.includes(invoice.id))
            .map(invoice => ({
                invoice_id: invoice.id,
                // Use the current invoice amount (or calculate a default)
                amount: invoice.amount !== undefined ? invoice.amount : (invoice.grand_total - invoice.paid),
            }));
        setData("invoices", selectedInvoiceData);
    }, [invoices, selectedInvoices, setData]);

    // Determine if all invoices are selected
    const isAllSelected = invoices.length > 0 && selectedInvoices.length === invoices.length;

    // Toggle selection for all invoices by using the new checked state
    const toggleSelectAll = (checked) => {
        if (checked) {
            setSelectedInvoices(invoices.map(invoice => invoice.id));
        } else {
            setSelectedInvoices([]);
        }
    };

    // Toggle selection for a single invoice based on the new checked state provided by the Checkbox
    const toggleSelectInvoice = (id, checked) => {
        if (checked) {
            setSelectedInvoices(prev => [...prev, id]);
        } else {
            setSelectedInvoices(prev => prev.filter(selectedId => selectedId !== id));
        }
    };

    // Update the amount for a given invoice in the invoices array; ensure numeric conversion if needed.
    const handleAmountChange = (id, value) => {
        const updatedInvoices = invoices.map(invoice => {
            if (invoice.id === id) {
                return { ...invoice, amount: Number(value) };
            }
            return invoice;
        });
        setInvoices(updatedInvoices);
    };

    // Submit handler; only submits selected invoices with their amounts
    const submit = (e) => {
        e.preventDefault();

        // The form data now already includes the correct invoices field based on the selected invoices and updated amounts.
        post(route("receive_payments.store"), data, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Invoice created successfully");
                reset();
                setInvoices([]);
                setSelectedInvoices([]);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Receive Payments" subpage="Create New" url="receive_payments.index" />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        {/* Customer Selection */}
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

                        {/* Payment Date Picker */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="trans_date" className="md:col-span-2 col-span-12">
                                Payment Date *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker
                                    value={data.trans_date}
                                    onChange={(date) => setData("trans_date", date)}
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError message={errors.trans_date} className="text-sm" />
                            </div>
                        </div>

                        {/* Payment Account */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="account_id" className="md:col-span-2 col-span-12">
                                Payment Account *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={accounts.map(account => ({
                                            id: account.id,
                                            name: account.account_name
                                        }))}
                                        value={data.account_id}
                                        onChange={(value) => setData("account_id", value)}
                                        placeholder="Select account"
                                    />
                                </div>
                                <InputError message={errors.account_id} className="text-sm" />
                            </div>
                        </div>

                        {/* Payment Method */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="method" className="md:col-span-2 col-span-12">
                                Payment Method *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={methods.map(method => ({
                                            id: method.name,
                                            name: method.name
                                        }))}
                                        value={data.method}
                                        onChange={(value) => setData("method", value)}
                                        placeholder="Select payment method"
                                    />
                                </div>
                                <InputError message={errors.method} className="text-sm" />
                            </div>
                        </div>

                        {/* Reference */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="reference" className="md:col-span-2 col-span-12">
                                Reference
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <Input
                                        id="reference"
                                        name="reference"
                                        type="text"
                                        value={data.reference}
                                        onChange={(e) => setData("reference", e.target.value)}
                                    />
                                </div>
                                <InputError message={errors.reference} className="text-sm" />
                            </div>
                        </div>

                        {/* Total Amount */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="amount" className="md:col-span-2 col-span-12">
                                Total Amount
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <span>
                                        {formatCurrency(selectedInvoices.reduce((total, invoiceId) => {
                                            const inv = invoices.find(inv => inv.id === invoiceId);
                                            // Use the current amount or the default due amount if not modified
                                            return total + (inv ? (inv.amount !== undefined ? inv.amount : (inv.grand_total - inv.paid)) : 0);
                                        }, 0))}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <SidebarSeparator className="my-4" />

                        {/* Invoices Table */}
                        <div className="space-y-4">
                            <h3 className="text-lg font-medium">Standing Invoices</h3>

                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[50px]">
                                            <Checkbox
                                                checked={isAllSelected}
                                                onCheckedChange={(checked) => toggleSelectAll(checked)}
                                            />
                                        </TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Due Date</TableHead>
                                        <TableHead>Grand Total</TableHead>
                                        <TableHead>Due Amount</TableHead>
                                        <TableHead>Amount</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {invoices.map((invoice) => (
                                        <TableRow key={invoice.id}>
                                            <TableCell>
                                                <Checkbox
                                                    checked={selectedInvoices.includes(invoice.id)}
                                                    onCheckedChange={(checked) => toggleSelectInvoice(invoice.id, checked)}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                {`Credit Invoice ${invoice.invoice_number} (${invoice.invoice_date})`}
                                            </TableCell>
                                            <TableCell>{invoice.due_date}</TableCell>
                                            <TableCell>{invoice.grand_total}</TableCell>
                                            <TableCell>{invoice.grand_total - invoice.paid}</TableCell>
                                            <TableCell>
                                                <Input
                                                    type="number"
                                                    value={invoice.amount !== undefined ? invoice.amount : (invoice.grand_total - invoice.paid)}
                                                    onChange={(e) =>
                                                        handleAmountChange(invoice.id, e.target.value)
                                                    }
                                                    className="w-full"
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Attachment Input */}
                        <div className="grid grid-cols-12 mt-5">
                            <Label htmlFor="attachment" className="md:col-span-2 col-span-12">
                                Attachment
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="attachment"
                                    type="file"
                                    onChange={(e) => setData("attachment", e.target.files[0])}
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.attachment} className="text-sm" />
                            </div>
                        </div>

                        {/* Submit Button */}
                        <Button type="submit" disabled={processing} className="mt-5">
                            Create Payment
                        </Button>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
