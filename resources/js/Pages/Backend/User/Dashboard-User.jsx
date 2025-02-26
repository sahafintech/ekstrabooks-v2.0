import { useState } from "react";
import { Head, Link, usePage, router } from "@inertiajs/react";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { DollarSign, Wallet, CreditCard, TrendingUp } from "lucide-react";
import {
    Bar,
    BarChart,
    CartesianGrid,
    XAxis,
    YAxis,
    Tooltip,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Area,
    AreaChart,
    PieChart,
    Pie,
    Cell,
    Label,
} from "recharts";
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from "@/components/ui/chart";
import TableWrapper from "@/Components/shared/TableWrapper";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";

export default function DashboardUser({
    dashboard_type = 'accounting',
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
    transactions,
}) {
    const [selectedRange, setSelectedRange] = useState(range || 'all');
    const [customRange, setCustomRange] = useState(custom || '');
    const [dashboardType, setDashboardType] = useState(dashboard_type);

    // Transform data for charts
    const monthlyData = Array.from({ length: 12 }, (_, i) => ({
        month: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][i],
        income: sales_overview.incomes[i] || 0,
        expense: sales_overview.expenses[i] || 0
    }));

    const cashflowData = Array.from({ length: 12 }, (_, i) => ({
        month: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][i],
        income: cashflow.cashflow_incomes[i] || 0,
        expense: cashflow.cashflow_expenses[i] || 0,
        balance: cashflow.ending_balance[i] || 0
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
        if (value === 'custom') {
            // Initialize date picker
            initializeDatePicker();
        } else {
            document.getElementById('filter_form').submit();
        }
    };

    const handleDashboardTypeChange = (value) => {
        setDashboardType(value);
        if (value === 'inventory') {
            router.visit(route('dashboard.inventory'), {
                preserveState: true,
                preserveScroll: true,
                data: {
                    range: selectedRange,
                    custom: customRange
                }
            });
        } else {
            router.visit(route('dashboard.index'), {
                preserveState: true,
                preserveScroll: true,
                data: {
                    range: selectedRange,
                    custom: customRange
                }
            });
        }
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Dashboard"
                    subpage="Dashboard"
                    url="dashboard.index"
                />
                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">

                    <div className="main-content space-y-8">
                        <div className="block justify-between page-header md:flex">
                            <div>
                                <h3 className="text-primary text-xl font-medium uppercase">
                                    Dashboard
                                </h3>
                            </div>

                            <div className="flex items-center justify-end space-x-4">
                                <Select value={dashboardType} onValueChange={handleDashboardTypeChange}>
                                    <SelectTrigger className="w-[200px]">
                                        <SelectValue placeholder="Select dashboard type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="accounting">Accounting Dashboard</SelectItem>
                                        <SelectItem value="inventory">Inventory Dashboard</SelectItem>
                                    </SelectContent>
                                </Select>

                                <Select value={selectedRange} onValueChange={handleRangeChange}>
                                    <SelectTrigger className="w-[200px]">
                                        <SelectValue placeholder="Select range" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Time</SelectItem>
                                        <SelectItem value="7">Last 7 Days</SelectItem>
                                        <SelectItem value="30">Last 30 Days</SelectItem>
                                        <SelectItem value="60">Last 60 Days</SelectItem>
                                        <SelectItem value="360">Last Year</SelectItem>
                                        <SelectItem value="custom">Custom Range</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {/* Dashboard Stats */}
                        <div className="grid auto-rows-min gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <div className="flex flex-col gap-1 rounded-lg bg-background p-4 shadow-sm">
                                <div className="text-sm text-muted-foreground">Total Income</div>
                                <div className="text-xl font-semibold">{current_month_income}</div>
                                <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                    <DollarSign className="h-4 w-4" />
                                    <span>Income</span>
                                </div>
                            </div>

                            <div className="flex flex-col gap-1 rounded-lg bg-background p-4 shadow-sm">
                                <div className="text-sm text-muted-foreground">Total Expense</div>
                                <div className="text-xl font-semibold">{current_month_expense}</div>
                                <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                    <Wallet className="h-4 w-4" />
                                    <span>Expense</span>
                                </div>
                            </div>

                            <div className="flex flex-col gap-1 rounded-lg bg-background p-4 shadow-sm">
                                <div className="text-sm text-muted-foreground">Receivable</div>
                                <div className="text-xl font-semibold">{AccountsReceivable}</div>
                                <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                    <CreditCard className="h-4 w-4" />
                                    <span>Receivable</span>
                                </div>
                            </div>

                            <div className="flex flex-col gap-1 rounded-lg bg-background p-4 shadow-sm">
                                <div className="text-sm text-muted-foreground">Payable</div>
                                <div className="text-xl font-semibold">{AccountsPayable}</div>
                                <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                    <CreditCard className="h-4 w-4" />
                                    <span>Payable</span>
                                </div>
                            </div>
                        </div>

                        {/* Charts Section */}
                        <div className="flex flex-1 flex-col gap-6 p-4 pt-0">
                            <div className="grid auto-rows-min gap-6 md:grid-cols-2">
                                <div className="flex flex-col gap-2 rounded-lg bg-background p-4 shadow-sm">
                                    <div>
                                        <div className="text-lg font-semibold">Income vs Expense</div>
                                        <div className="text-sm text-muted-foreground">Monthly Overview</div>
                                    </div>
                                    <div className="flex-1">
                                        <ChartContainer config={chartConfig}>
                                            <BarChart accessibilityLayer data={monthlyData}>
                                                <CartesianGrid vertical={false} />
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
                                            Monthly comparison of income and expenses
                                            <TrendingUp className="h-4 w-4" />
                                        </div>
                                        <div className="leading-none text-muted-foreground">
                                            Showing total transactions for all months
                                        </div>
                                    </div>
                                </div>

                                <div className="flex flex-col gap-2 rounded-lg bg-background p-4 shadow-sm">
                                    <div>
                                        <div className="text-lg font-semibold">Cash Flow</div>
                                        <div className="text-sm text-muted-foreground">Monthly Overview</div>
                                    </div>
                                    <div className="flex-1">
                                        <ChartContainer config={chartConfig}>
                                            <AreaChart data={cashflowData}>
                                                <CartesianGrid vertical={false} />
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
                                            Showing income, expenses and balance trends
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Recent Transactions */}
                        <div className="mt-2">
                            <TableWrapper>
                                <RecentTransactionsTable transactions={recentTransactions} />
                            </TableWrapper>
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
                    onClick={() => window.location.href = route('transactions.show', transaction.id)}
                >
                    <TableCell>{transaction.trans_date}</TableCell>
                    <TableCell>{transaction.account.account_name}</TableCell>
                    <TableCell>{transaction.description}</TableCell>
                    <TableCell className="text-right">
                        {transaction.base_currency_amount}
                    </TableCell>
                </TableRow>
            ))}
        </TableBody>
    </Table>
);