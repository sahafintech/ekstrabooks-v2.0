import { useEffect, useState } from "react";
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
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from "@/Components/ui/table";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";
import Attachment from "@/Components/ui/attachment";

export default function Edit({ payment, vendors = [], accounts, methods, theAttachments }) {

    const [attachments, setAttachments] = useState([]);

    const { data, setData, post, processing, errors, reset } = useForm({
        vendor_id: payment.vendor_id,
        trans_date: parseDateObject(payment.date),
        account_id: payment.account_id,
        method: payment.payment_method,
        reference: payment.reference,
        attachments: [],
        invoices: [],
        _method: "PUT"
    });

    useEffect(() => {
        setData("attachments", attachments);
    }, [attachments, setData]);

    // Populate invoices from the payment prop on mount/update.
    useEffect(() => {
        if (payment?.purchases && Array.isArray(payment.purchases)) {
            const initialInvoices = payment.purchases.map(invoice => ({
                invoice_id: invoice.id,
                amount: invoice.pivot.amount
            }));
            setData("invoices", initialInvoices);
        }

        theAttachments.forEach(attachment => {
            const row = attachment.path;
            (attachments).push(row);
        });
    }, [payment, setData, theAttachments]);

    // Update the amount for a given invoice in the form state.
    const handleAmountChange = (id, value) => {
        const updatedInvoices = (Array.isArray(data.invoices) ? data.invoices : []).map(invoice =>
            invoice.invoice_id === id ? { ...invoice, amount: Number(value) } : invoice
        );
        setData("invoices", updatedInvoices);
    };

    // Retrieve the updated amount from state for an invoice.
    const getInvoiceAmount = (invoice) => {
        const invoicesArray = Array.isArray(data.invoices) ? data.invoices : [];
        const invoiceData = invoicesArray.find(item => item.invoice_id === invoice.id);
        // Fall back to the original pivot amount if no updated amount exists.
        return invoiceData ? invoiceData.amount : invoice.pivot.amount;
    };

    // Calculate the computed total amount using the updated invoice amounts.
    const computedTotalAmount = payment.purchases.reduce(
        (total, invoice) => total + Number(getInvoiceAmount(invoice) || 0),
        0
    );

    // Submit form handler.
    const submit = (e) => {
        e.preventDefault();
        post(route("bill_payments.update", payment.id), data, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Invoice created successfully");
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Bill Payments" subpage="Create New" url="bill_payments.index" />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        {/* Customer Selection */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="vendor_id" className="md:col-span-2 col-span-12">
                                Supplier *
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
                                        placeholder="Select supplier"
                                    />
                                </div>
                                <InputError message={errors.vendor_id} className="text-sm" />
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
                                        {formatCurrency(computedTotalAmount)}
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
                                        <TableHead>Description</TableHead>
                                        <TableHead>Due Date</TableHead>
                                        <TableHead>Grand Total</TableHead>
                                        <TableHead>Due Amount</TableHead>
                                        <TableHead>Amount</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payment.purchases.map((invoice) => (
                                        <TableRow key={invoice.id}>
                                            <TableCell>
                                                {`Bill Invoice ${invoice.purchase_number} (${invoice.purchase_date})`}
                                            </TableCell>
                                            <TableCell>{invoice.due_date}</TableCell>
                                            <TableCell>{invoice.grand_total}</TableCell>
                                            <TableCell>{invoice.grand_total - invoice.paid}</TableCell>
                                            <TableCell>
                                                <Input type="number"
                                                    value={getInvoiceAmount(invoice)}
                                                    onChange={(e) => handleAmountChange(invoice.id, e.target.value)}
                                                    className="w-full"
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
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
                                    databaseAttachments={theAttachments}
                                    maxSize={20}
                                />
                                <InputError message={errors.attachments} className="text-sm" />
                            </div>
                        </div>

                        {/* Submit Button */}
                        <Button type="submit" disabled={processing} className="mt-5">
                            Update Payment
                        </Button>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
