import { useEffect, useState } from "react";
import { router, useForm, usePage } from "@inertiajs/react";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { Button } from "@/Components/ui/button";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { DollarSign, Wallet, CreditCard, TrendingUp } from "lucide-react";
import { Bar, BarChart, CartesianGrid, XAxis, Area, AreaChart } from "recharts";
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from "@/Components/ui/chart";
import TableWrapper from "@/Components/shared/TableWrapper";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";
import { Link } from "@inertiajs/react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";

export default function DashboardUser({
    dashboard_type = "accounting",
    custom,
    range,
    current_month_income,
    current_month_expense,
    AccountsReceivable,
    AccountsPayable,
    sales_overview,
    recentTransactions,
    topCustomers,
    receivables_payables,
    cashflow,
    recentCreditInvoices,
}) {
    const [selectedRange, setSelectedRange] = useState(range);
    const [customRange, setCustomRange] = useState(custom);
    const [isCustom, setIsCustom] = useState(false);
    const [dashboardType, setDashboardType] = useState(dashboard_type);

    const { flash = {} } = usePage().props;
    const { toast } = useToast();

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

    // Transform data for charts
    const monthlyData = Array.from({ length: 12 }, (_, i) => ({
        month: [
            "Jan",
            "Feb",
            "Mar",
            "Apr",
            "May",
            "Jun",
            "Jul",
            "Aug",
            "Sep",
            "Oct",
            "Nov",
            "Dec",
        ][i],
        income: sales_overview.incomes[i] || 0,
        expense: sales_overview.expenses[i] || 0,
    }));

    const cashflowData = Array.from({ length: 12 }, (_, i) => ({
        month: [
            "Jan",
            "Feb",
            "Mar",
            "Apr",
            "May",
            "Jun",
            "Jul",
            "Aug",
            "Sep",
            "Oct",
            "Nov",
            "Dec",
        ][i],
        income: cashflow.cashflow_incomes[i] || 0,
        expense: cashflow.cashflow_expenses[i] || 0,
        balance: cashflow.ending_balance[i] || 0,
    }));

    const chartConfig = {
        income: {
            label: "Income",
            color: "hsl(var(--chart-1))",
        },
        expense: {
            label: "Expense",
            color: "hsl(var(--chart-2))",
        },
    };

    const handleRangeChange = (value) => {
        setSelectedRange(value);
        if (value === "custom") {
            setIsCustom(true);
        } else {
            router.visit(route("dashboard.index"), {
                preserveState: true,
                preserveScroll: true,
                data: {
                    range: value,
                },
            });
        }
    };

    const handleDateRangeChange = (dateRange) => {
        setCustomRange(dateRange);
        router.visit(route("dashboard.index"), {
            preserveState: true,
            preserveScroll: true,
            data: {
                custom: dateRange,
            },
        });
    };

    const handleDashboardTypeChange = (value) => {
        setDashboardType(value);
        if (value === "inventory") {
            router.visit(route("dashboard.inventory"), {
                preserveState: true,
                preserveScroll: true,
                data: {
                    range: selectedRange,
                    custom: customRange,
                },
            });
        } else {
            router.visit(route("dashboard.index"), {
                preserveState: true,
                preserveScroll: true,
                data: {
                    range: selectedRange,
                    custom: customRange,
                },
            });
        }
    };

    const topCustomersArray = Array.isArray(topCustomers)
        ? topCustomers
        : Object.values(topCustomers || {});

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <Toaster />
                <PageHeader
                    page="Dashboard"
                    subpage="Dashboard"
                    url="dashboard.index"
                />
                <div className="flex flex-1 flex-col gap-2 p-4 pt-0">
                    <div className="main-content space-y-8">
                        <div className="block justify-between page-header md:flex">
                            <div>
                                <h3 className="text-primary text-xl font-medium uppercase">
                                    Dashboard
                                </h3>
                            </div>

                            <div className="flex items-center justify-end space-x-2">
                                <Select
                                    value={dashboardType}
                                    onValueChange={handleDashboardTypeChange}
                                >
                                    <SelectTrigger className="w-[200px]">
                                        <SelectValue placeholder="Select dashboard type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="accounting">
                                            Accounting Dashboard
                                        </SelectItem>
                                        <SelectItem value="inventory">
                                            Inventory Dashboard
                                        </SelectItem>
                                    </SelectContent>
                                </Select>

                                <div className="flex space-x-2">
                                    {!isCustom && (
                                        <Select
                                            value={selectedRange}
                                            onValueChange={handleRangeChange}
                                        >
                                            <SelectTrigger className="w-[200px]">
                                                <SelectValue placeholder="Select range" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    All Time
                                                </SelectItem>
                                                <SelectItem value="7">
                                                    Last 7 Days
                                                </SelectItem>
                                                <SelectItem value="30">
                                                    Last 30 Days
                                                </SelectItem>
                                                <SelectItem value="60">
                                                    Last 60 Days
                                                </SelectItem>
                                                <SelectItem value="360">
                                                    Last Year
                                                </SelectItem>
                                                <SelectItem value="custom">
                                                    Custom Range
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    )}

                                    {isCustom && (
                                        <div className="flex items-center space-x-2">
                                            <DateTimePicker
                                                value={customRange}
                                                onChange={handleDateRangeChange}
                                                className="w-[250px]"
                                                isRange={true}
                                            />
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    setIsCustom(false);
                                                    setSelectedRange("all");
                                                }}
                                            >
                                                Clear
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Dashboard Stats */}
                        <div className="grid auto-rows-min gap-2 md:grid-cols-2 lg:grid-cols-4">
                            <div className="flex flex-col gap-1 rounded-lg bg-background p-4 shadow-sm bg-neutral-200 text-primary">
                                <div className="text-sm">
                                    Total Income
                                </div>
                                <div className="text-xl font-semibold">
                                    {formatCurrency(current_month_income)}
                                </div>
                                <div className="flex items-center gap-1 text-xs">
                                    <DollarSign className="h-4 w-4" />
                                    <span>Income</span>
                                </div>
                            </div>

                            <div className="flex flex-col gap-1 rounded-lg bg-background p-4 shadow-sm bg-neutral-200 text-primary">
                                <div className="text-sm">
                                    Total Expense
                                </div>
                                <div className="text-xl font-semibold">
                                    {formatCurrency(current_month_expense)}
                                </div>
                                <div className="flex items-center gap-1 text-xs">
                                    <Wallet className="h-4 w-4" />
                                    <span>Expense</span>
                                </div>
                            </div>

                            <div className="flex flex-col gap-1 rounded-lg bg-background p-4 shadow-sm bg-neutral-200 text-primary">
                                <div className="text-sm">
                                    Receivable
                                </div>
                                <div className="text-xl font-semibold">
                                    {formatCurrency(AccountsReceivable)}
                                </div>
                                <div className="flex items-center gap-1 text-xs">
                                    <CreditCard className="h-4 w-4" />
                                    <span>Receivable</span>
                                </div>
                            </div>

                            <div className="flex flex-col gap-1 rounded-lg bg-background p-4 shadow-sm bg-neutral-200 text-primary">
                                <div className="text-sm">
                                    Payable
                                </div>
                                <div className="text-xl font-semibold">
                                    {formatCurrency(AccountsPayable)}
                                </div>
                                <div className="flex items-center gap-1 text-xs">
                                    <CreditCard className="h-4 w-4" />
                                    <span>Payable</span>
                                </div>
                            </div>
                        </div>

                        {/* Charts Section */}
                        <div className="flex flex-1 flex-col gap-2 pt-0">
                            <div className="grid auto-rows-min gap-2 md:grid-cols-2">
                                <div className="flex flex-col gap-2 rounded-lg bg-background p-4 shadow-sm">
                                    <div>
                                        <div className="text-lg font-semibold">
                                            Income vs Expense
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            Monthly Overview
                                        </div>
                                    </div>
                                    <div className="flex-1">
                                        <ChartContainer config={chartConfig}>
                                            <BarChart
                                                accessibilityLayer
                                                data={monthlyData}
                                            >
                                                <CartesianGrid
                                                    vertical={false}
                                                />
                                                <XAxis
                                                    dataKey="month"
                                                    tickLine={false}
                                                    tickMargin={10}
                                                    axisLine={false}
                                                />
                                                <ChartTooltip
                                                    cursor={false}
                                                    content={
                                                        <ChartTooltipContent indicator="dashed" />
                                                    }
                                                />
                                                <Bar
                                                    dataKey="income"
                                                    fill="var(--color-income)"
                                                    radius={4}
                                                />
                                                <Bar
                                                    dataKey="expense"
                                                    fill="var(--color-expense)"
                                                    radius={4}
                                                />
                                            </BarChart>
                                        </ChartContainer>
                                    </div>
                                    <div className="flex flex-col gap-1 text-sm">
                                        <div className="flex gap-2 font-medium leading-none">
                                            Monthly comparison of income and
                                            expenses
                                            <TrendingUp className="h-4 w-4" />
                                        </div>
                                        <div className="leading-none text-muted-foreground">
                                            Showing total transactions for all
                                            months
                                        </div>
                                    </div>
                                </div>

                                <div className="flex flex-col gap-2 rounded-lg bg-background p-4 shadow-sm">
                                    <div>
                                        <div className="text-lg font-semibold">
                                            Cash Flow
                                        </div>
                                        <div className="text-sm text-muted-foreground">
                                            Monthly Overview
                                        </div>
                                    </div>
                                    <div className="flex-1">
                                        <ChartContainer config={chartConfig}>
                                            <AreaChart data={cashflowData}>
                                                <CartesianGrid
                                                    vertical={false}
                                                />
                                                <XAxis
                                                    dataKey="month"
                                                    tickLine={false}
                                                    tickMargin={10}
                                                    axisLine={false}
                                                />
                                                <ChartTooltip
                                                    cursor={false}
                                                    content={
                                                        <ChartTooltipContent indicator="dashed" />
                                                    }
                                                />
                                                <Area
                                                    type="monotone"
                                                    dataKey="income"
                                                    stroke="var(--color-income)"
                                                    fill="var(--color-income)"
                                                    fillOpacity={0.2}
                                                    strokeWidth={2}
                                                />
                                                <Area
                                                    type="monotone"
                                                    dataKey="expense"
                                                    stroke="var(--color-expense)"
                                                    fill="var(--color-expense)"
                                                    fillOpacity={0.2}
                                                    strokeWidth={2}
                                                />
                                                <Area
                                                    type="monotone"
                                                    dataKey="balance"
                                                    stroke="var(--color-balance)"
                                                    fill="var(--color-balance)"
                                                    fillOpacity={0.2}
                                                    strokeWidth={2}
                                                />
                                            </AreaChart>
                                        </ChartContainer>
                                    </div>
                                    <div className="flex flex-col gap-1 text-sm">
                                        <div className="flex gap-2 font-medium leading-none">
                                            Monthly cash flow analysis
                                            <TrendingUp className="h-4 w-4" />
                                        </div>
                                        <div className="leading-none text-muted-foreground">
                                            Showing income, expenses and balance
                                            trends
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Recent Transactions */}
                        <div className="mt-2">
                            <TableWrapper>
                                <RecentTransactionsTable
                                    transactions={recentTransactions}
                                />
                            </TableWrapper>
                        </div>

                        <div className="flex flex-col md:flex-row gap-2 mb-8">
                            {/* Recent Invoices */}
                            <div className="flex-1 rounded-lg border bg-white">
                                <div className="border-b px-6 py-4">
                                    <h2 className="text-lg font-semibold">Recent Invoices</h2>
                                </div>
                                <div className="p-4">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Status</TableHead>
                                                <TableHead>#</TableHead>
                                                <TableHead>Customer</TableHead>
                                                <TableHead>Invoice Date</TableHead>
                                                <TableHead>Due</TableHead>
                                                <TableHead className="text-right">Total</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {recentCreditInvoices && recentCreditInvoices.length > 0 ? (
                                                recentCreditInvoices.map((invoice) => {
                                                    let due_human = "";
                                                    let due_human_color = "";
                                                    if (invoice.due_date) {
                                                        const dueDate = parseDateObject(invoice.due_date);
                                                        const now = new Date();
                                                        const diff = Math.ceil((dueDate - now) / (1000 * 60 * 60 * 24));
                                                        if (diff < 0) {
                                                            due_human = `${Math.abs(diff)} weeks ago`;
                                                            due_human_color = "text-red-500";
                                                        } else if (diff === 0) {
                                                            due_human = "Today";
                                                            due_human_color = "text-green-500";
                                                        } else if (diff < 7) {
                                                            due_human = `${diff} days from now`;
                                                            due_human_color = "text-green-500";
                                                        } else if (diff < 30) {
                                                            due_human = `${Math.round(diff / 7)} weeks from now`;
                                                            due_human_color = "text-green-500";
                                                        } else {
                                                            due_human = `${Math.round(diff / 30)} months from now`;
                                                            due_human_color = "text-green-500";
                                                        }
                                                    }
                                                    return (
                                                        <TableRow key={invoice.id}>
                                                            <TableCell>
                                                                <span
                                                                    className={
                                                                        invoice.status === "Partial Paid"
                                                                            ? "text-blue-600"
                                                                            : invoice.status === "Active"
                                                                            ? "text-pink-500"
                                                                            : invoice.status === "Overdue"
                                                                            ? "text-red-500"
                                                                            : ""
                                                                    }
                                                                >
                                                                    {invoice.status}
                                                                </span>
                                                            </TableCell>
                                                            <TableCell>
                                                                <Link
                                                                    href={route("invoices.show", invoice.id)}
                                                                    className="hover:underline"
                                                                >
                                                                    {invoice.invoice_number || invoice.id}
                                                                </Link>
                                                            </TableCell>
                                                            <TableCell className="uppercase">{invoice.customer?.name}</TableCell>
                                                            <TableCell>{invoice.invoice_date}</TableCell>
                                                            <TableCell>
                                                                <span className={due_human_color}>
                                                                    {due_human}
                                                                </span>
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {formatCurrency(invoice.grand_total)}
                                                            </TableCell>
                                                        </TableRow>
                                                    );
                                                })
                                            ) : (
                                                <TableRow>
                                                    <TableCell colSpan={6} className="text-center">
                                                        No recent invoices found.
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>

                            {/* Top Customers */}
                            <div className="flex-1 rounded-lg border bg-white">
                                <div className="border-b px-6 py-4">
                                    <h2 className="text-lg font-semibold">Top Customers</h2>
                                </div>
                                <div className="p-4">
                                    {topCustomersArray && topCustomersArray.length > 0 ? (
                                        <div>
                                            {topCustomersArray.map((customer, idx) => (
                                                <div
                                                    key={customer.id}
                                                    className="flex items-center justify-between py-4 border-b last:border-b-0"
                                                >
                                                    <div>
                                                        <div className="font-semibold uppercase">{customer.name}</div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {customer.total_invoice} {customer.total_invoice === 1 ? "Purchase" : "Purchases"}
                                                        </div>
                                                    </div>
                                                    <div className="font-semibold text-right">
                                                        {formatCurrency(customer.total_amount)}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="py-4 text-center text-gray-500 text-sm">
                                            No top customers found.
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

const RecentTransactionsTable = ({ transactions }) => (
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>Date</TableHead>
                <TableHead>Account</TableHead>
                <TableHead>Description</TableHead>
                <TableHead className="text-right">Amount</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            {transactions.map((transaction) => (
                <TableRow
                    key={transaction.id}
                    className="cursor-pointer"
                    onClick={() =>
                        (window.location.href = route(
                            "transactions.show",
                            transaction.id
                        ))
                    }
                >
                    <TableCell>{transaction.trans_date}</TableCell>
                    <TableCell>{transaction.account.account_name}</TableCell>
                    <TableCell>{transaction.description}</TableCell>
                    <TableCell className="text-right">
                        {formatCurrency(transaction.base_currency_amount)}
                    </TableCell>
                </TableRow>
            ))}
        </TableBody>
    </Table>
);
