import { useEffect } from "react";
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
import { Calendar } from "@/Components/ui/calendar";
import { Popover, PopoverContent, PopoverTrigger } from "@/Components/ui/popover";
import { CalendarIcon } from "lucide-react";
import { format } from "date-fns";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from "@/Components/ui/table";
import DateTimePicker from "@/Components/DateTimePicker";

export default function Edit({ payment, customers = [], accounts, methods }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        customer_id: payment.customer_id,
        trans_date: parseDateObject(payment.date),
        account_id: payment.account_id,
        method: payment.payment_method,
        reference: payment.reference,
        attachment: payment.attachment,
        invoices: [],
        _method: "PUT"
    });

    // Populate invoices from the payment prop on mount/update.
    useEffect(() => {
        if (payment?.invoices && Array.isArray(payment.invoices)) {
            const initialInvoices = payment.invoices.map(invoice => ({
                invoice_id: invoice.id,
                amount: invoice.pivot.amount
            }));
            setData("invoices", initialInvoices);
        }
    }, [payment, setData]);

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
    const computedTotalAmount = payment.invoices.reduce(
        (total, invoice) => total + Number(getInvoiceAmount(invoice) || 0),
        0
    );

    // Submit form handler.
    const submit = (e) => {
        e.preventDefault();
        post(route("receive_payments.update", payment.id), data, {
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
                                    {payment.invoices.map((invoice) => (
                                        <TableRow key={invoice.id}>
                                            <TableCell>
                                                {`Credit Invoice ${invoice.invoice_number} (${invoice.invoice_date})`}
                                            </TableCell>
                                            <TableCell>{invoice.due_date}</TableCell>
                                            <TableCell>{invoice.grand_total}</TableCell>
                                            <TableCell>{invoice.grand_total - invoice.paid}</TableCell>
                                            <TableCell>
                                                <Input
                                                    type="number"
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

                        {/* Attachment Input */}
                        <div className="grid grid-cols-12 mt-5">
                            <Label htmlFor="attachment" className="md:col-span-2 col-span-12">
                                Attachment
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="attachment"
                                    type="file"
                                    onChange={(e) => {
                                        if (e.target.files && e.target.files[0]) {
                                            setData("attachment", e.target.files[0]);
                                        }
                                    }}
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.attachment} className="text-sm" />
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
