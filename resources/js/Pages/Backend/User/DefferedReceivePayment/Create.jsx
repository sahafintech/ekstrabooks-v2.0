import { useEffect, useState } from "react";
import { useForm } from "@inertiajs/react";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { Checkbox } from "@/Components/ui/checkbox";
import DateTimePicker from "@/Components/DateTimePicker";
import { formatCurrency } from "@/lib/utils";
import { toast } from "sonner";

export default function Create({
    customers = [],
    accounts = [],
    methods = [],
    defaultCustomerId = "",
    defaultInvoiceId = null,
}) {
    const [standingPayments, setStandingPayments] = useState([]);
    const [selectedPayments, setSelectedPayments] = useState([]);
    const [hasAppliedInitialSelection, setHasAppliedInitialSelection] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        customer_id: defaultCustomerId ?? "",
        trans_date: new Date(),
        account_id: "",
        method: "",
        reference: "",
        payments: [],
        invoices: [],
        amount: {},
    });

    useEffect(() => {
        if (data.customer_id) {
            axios.get(route("customer.get_deffered_invoices", data.customer_id))
                .then((response) => {
                    const fetchedPayments = (response.data?.invoices || []).flatMap((invoice) =>
                        (invoice.deffered_payments || [])
                            .filter((payment) => {
                                const dueAmount = Number(payment.amount) - Number(payment.paid);
                                return dueAmount > 0 && [0, 1].includes(Number(payment.status));
                            })
                            .map((payment) => ({
                                id: payment.id,
                                invoice_id: invoice.id,
                                invoice_number: invoice.invoice_number,
                                order_number: invoice.order_number,
                                payment_date: payment.date,
                                due_date: payment.due_date,
                                amount: Number(payment.amount) - Number(payment.paid),
                                due_amount: Number(payment.amount) - Number(payment.paid),
                                scheduled_amount: Number(payment.amount),
                                paid_amount: Number(payment.paid),
                            }))
                    );

                    setStandingPayments(fetchedPayments);

                    if (!hasAppliedInitialSelection && defaultInvoiceId) {
                        const initialPayments = fetchedPayments
                            .filter((payment) => Number(payment.invoice_id) === Number(defaultInvoiceId))
                            .map((payment) => payment.id);

                        setSelectedPayments(initialPayments);
                        setHasAppliedInitialSelection(true);
                    } else {
                        setSelectedPayments([]);
                    }
                })
                .catch((error) => {
                    console.error("Fetching deferred invoices error:", error);
                });
        } else {
            setStandingPayments([]);
            setSelectedPayments([]);
        }
    }, [data.customer_id, defaultInvoiceId]);

    useEffect(() => {
        const selectedRows = standingPayments.filter((payment) =>
            selectedPayments.includes(payment.id)
        );

        setData("payments", selectedRows.map((payment) => payment.id));
        setData("invoices", selectedRows.map((payment) => payment.invoice_id));
        setData(
            "amount",
            selectedRows.reduce((carry, payment) => {
                carry[payment.id] = payment.amount;
                return carry;
            }, {})
        );
    }, [setData, standingPayments, selectedPayments]);

    const isAllSelected =
        standingPayments.length > 0 && selectedPayments.length === standingPayments.length;

    const toggleSelectAll = (checked) => {
        if (checked) {
            setSelectedPayments(standingPayments.map((payment) => payment.id));
        } else {
            setSelectedPayments([]);
        }
    };

    const toggleSelectPayment = (paymentId, checked) => {
        if (checked) {
            setSelectedPayments((prev) => [...prev, paymentId]);
        } else {
            setSelectedPayments((prev) => prev.filter((id) => id !== paymentId));
        }
    };

    const handleAmountChange = (paymentId, value) => {
        setStandingPayments((prev) =>
            prev.map((payment) =>
                payment.id === paymentId
                    ? { ...payment, amount: Number(value) }
                    : payment
            )
        );
    };

    const submit = (e) => {
        e.preventDefault();

        post(route("deffered_receive_payments.store"), data, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Deferred payment created successfully");
                reset();
                setStandingPayments([]);
                setSelectedPayments([]);
            },
        });
    };

    const totalAmount = selectedPayments.reduce((total, paymentId) => {
        const payment = standingPayments.find((item) => item.id === paymentId);
        return total + (payment ? Number(payment.amount) : 0);
    }, 0);

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Deferred Receive Payments"
                    subpage="Create New"
                    url="deffered_invoices.index"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="customer_id" className="md:col-span-2 col-span-12">
                                Customer *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={customers.map((customer) => ({
                                            id: customer.id,
                                            name: customer.name,
                                        }))}
                                        value={data.customer_id}
                                        onChange={(value) => setData("customer_id", value)}
                                        placeholder="Select customer"
                                    />
                                </div>
                                <InputError message={errors.customer_id} className="text-sm" />
                            </div>
                        </div>

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

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="account_id" className="md:col-span-2 col-span-12">
                                Payment Account *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={accounts.map((account) => ({
                                            id: account.id,
                                            name: account.account_name,
                                        }))}
                                        value={data.account_id}
                                        onChange={(value) => setData("account_id", value)}
                                        placeholder="Select account"
                                    />
                                </div>
                                <InputError message={errors.account_id} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="method" className="md:col-span-2 col-span-12">
                                Payment Method *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={methods.map((method) => ({
                                            id: method.name,
                                            name: method.name,
                                        }))}
                                        value={data.method}
                                        onChange={(value) => setData("method", value)}
                                        placeholder="Select payment method"
                                    />
                                </div>
                                <InputError message={errors.method} className="text-sm" />
                            </div>
                        </div>

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

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="amount" className="md:col-span-2 col-span-12">
                                Total Amount
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <span>{formatCurrency({ amount: totalAmount })}</span>
                                </div>
                            </div>
                        </div>

                        <SidebarSeparator className="my-4" />

                        <div className="space-y-4">
                            <h3 className="text-lg font-medium">Standing Payments</h3>

                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[50px]">
                                            <Checkbox
                                                checked={isAllSelected}
                                                onCheckedChange={toggleSelectAll}
                                            />
                                        </TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Due Date</TableHead>
                                        <TableHead>Scheduled Amount</TableHead>
                                        <TableHead>Due Amount</TableHead>
                                        <TableHead>Amount</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {standingPayments.length > 0 ? (
                                        standingPayments.map((payment) => (
                                            <TableRow key={payment.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedPayments.includes(payment.id)}
                                                        onCheckedChange={(checked) =>
                                                            toggleSelectPayment(payment.id, checked)
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {`Deferred Payment #${payment.id} - Policy ${payment.order_number || payment.invoice_number}`}
                                                </TableCell>
                                                <TableCell>{payment.due_date}</TableCell>
                                                <TableCell>
                                                    {formatCurrency({ amount: payment.scheduled_amount })}
                                                </TableCell>
                                                <TableCell>
                                                    {formatCurrency({ amount: payment.due_amount })}
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        step="any"
                                                        value={payment.amount}
                                                        onChange={(e) =>
                                                            handleAmountChange(payment.id, e.target.value)
                                                        }
                                                        className="w-full"
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={6} className="h-24 text-center">
                                                No outstanding deferred payments found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <Button type="submit" disabled={processing} className="mt-5">
                            Create Payment
                        </Button>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
