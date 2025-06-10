import { useState } from "react";
import { router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { Button } from "@/Components/ui/button";
import DateTimePicker from "@/Components/DateTimePicker";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { BoxIcon, AlertTriangle, ShoppingCart, Package, ArrowUp, ArrowDown, RefreshCw } from "lucide-react";
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
    PieChart,
    Pie,
    Cell,
} from "recharts";
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent
} from "@/Components/ui/chart";
import TableWrapper from "@/Components/shared/TableWrapper";
import PageHeader from "@/Components/PageHeader";
import { formatAmount, formatCurrency } from "@/lib/utils";

export default function DashboardInventory({
    dashboard_type = 'inventory',
    custom,
    range,
    total_products,
    low_stock_products,
    out_of_stock_products,
    inventory_value,
    top_selling_products,
    most_purchased_products,
    stock_movements,
    stock_in,
    stock_out,
    closing_stock,
    stock_turnover_rate,
    daily_stock_movements,
    category_stock,
    date_aggregation,
}) {
    const [selectedRange, setSelectedRange] = useState(range || 'all');
    const [customRange, setCustomRange] = useState(custom || '');
    const [isCustom, setIsCustom] = useState(false);
    const [dashboardType, setDashboardType] = useState(dashboard_type);

    const handleRangeChange = (value) => {
        setSelectedRange(value);
        if (value === 'custom') {
            setIsCustom(true);
        } else {
            router.visit(route('dashboard.inventory'), {
                preserveState: true,
                preserveScroll: true,
                data: {
                    range: value,
                }
            });
        }
    };

    const handleDateRangeChange = (dateRange) => {
        setCustomRange(dateRange);
        router.visit(route('dashboard.inventory'), {
            preserveState: true,
            preserveScroll: true,
            data: {
                custom: dateRange,
            }
        });
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

    // Format the data for the stats cards
    const statsCards = [
        {
            title: "Total Stock Value",
            value: `${formatCurrency(inventory_value)}`,
            description: "Current inventory value",
            icon: BoxIcon,
            iconColor: "text-blue-500"
        },
        {
            title: "Low Stock Items",
            value: formatAmount(low_stock_products),
            description: "Items below threshold",
            icon: AlertTriangle,
            iconColor: "text-yellow-500"
        },
        {
            title: "Items Sold",
            value: formatAmount(stock_movements.sales),
            description: "Total units sold (days)",
            icon: ShoppingCart,
            iconColor: "text-green-500"
        },
        {
            title: "Total Products",
            value: formatAmount(total_products),
            description: "Active products",
            icon: Package,
            iconColor: "text-purple-500"
        }
    ];

    // Format data for stock metrics
    const stockMetrics = [
        {
            title: "Current Stock",
            value: formatAmount(closing_stock),
            description: "Total units in stock",
            icon: BoxIcon,
            iconColor: "text-gray-500"
        },
        {
            title: "Stock In",
            value: formatAmount(stock_in),
            description: "Total units received",
            icon: ArrowUp,
            iconColor: "text-green-500"
        },
        {
            title: "Stock Out",
            value: formatAmount(stock_out),
            description: "Total units out",
            icon: ArrowDown,
            iconColor: "text-red-500"
        },
        {
            title: "Stock Turnover",
            value: `${formatAmount(stock_turnover_rate)}%`,
            description: "Turnover rate",
            icon: RefreshCw,
            iconColor: "text-blue-500"
        }
    ];

    // Format data for stock movement chart
    const stockMovementData = Object.entries(daily_stock_movements).map(([date, data]) => ({
        date: new Date(date).toLocaleDateString('en-US', {
            month: 'short',
            year: '2-digit'
        }),
        "Stock In": data.in,
        "Stock Out": data.out
    }));

    // Format data for stock distribution pie chart
    const stockDistribution = [
        { name: "Healthy Stock", value: total_products - low_stock_products - out_of_stock_products },
        { name: "Low Stock", value: low_stock_products },
        { name: "Out of Stock", value: out_of_stock_products }
    ];

    // Format data for category-wise stock out
    const categoryStockOut = Object.entries(category_stock).map(([category, data]) => ({
        category,
        value: data.stock_out
    })).sort((a, b) => b.value - a.value);

    // Chart configurations
    const chartConfig = {
        stockIn: {
            label: "Stock In",
            color: "hsl(var(--chart-1))",
        },
        stockOut: {
            label: "Stock Out",
            color: "hsl(var(--chart-2))",
        },
        healthy: {
            label: "Healthy Stock",
            color: "#4ade80",
        },
        low: {
            label: "Low Stock",
            color: "#fbbf24",
        },
        outOfStock: {
            label: "Out of Stock",
            color: "#f87171",
        },
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Dashboard"
                    subpage="Inventory Dashboard"
                    url="dashboard.index"
                />
                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <div className="main-content space-y-8">
                        <div className="block justify-between page-header md:flex">
                            <div>
                                <h3 className="text-primary text-xl font-medium uppercase">
                                    Inventory Dashboard
                                </h3>
                            </div>

                            <div className="flex items-center justify-end space-x-2">
                                <Select value={dashboardType} onValueChange={handleDashboardTypeChange}>
                                    <SelectTrigger className="w-[200px]">
                                        <SelectValue placeholder="Select dashboard type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="accounting">Accounting Dashboard</SelectItem>
                                        <SelectItem value="inventory">Inventory Dashboard</SelectItem>
                                    </SelectContent>
                                </Select>

                                <div className="flex space-x-2">
                                    {!isCustom && (
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
                                                    router.visit(route("dashboard.inventory"), {
                                                        preserveState: true,
                                                        preserveScroll: true,
                                                        data: {
                                                            range: "all",
                                                        },
                                                    });
                                                }}
                                            >
                                                Clear
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Stats Cards */}
                        <div className="grid auto-rows-min gap-6 md:grid-cols-2 lg:grid-cols-4">
                            {statsCards.map((stat, i) => (
                                <div key={i} className="flex flex-col gap-1 rounded-lg bg-background p-4 shadow-sm">
                                    <div className="text-sm text-muted-foreground">{stat.title}</div>
                                    <div className="text-xl font-semibold">{stat.value}</div>
                                    <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                        <stat.icon className={`h-4 w-4 ${stat.iconColor}`} />
                                        <span>{stat.description}</span>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Stock Metrics */}
                        <div className="grid auto-rows-min gap-6 md:grid-cols-2 lg:grid-cols-4">
                            {stockMetrics.map((metric, i) => (
                                <div key={i} className="flex flex-col gap-1 rounded-lg bg-background p-4 shadow-sm">
                                    <div className="text-sm text-muted-foreground">{metric.title}</div>
                                    <div className="text-xl font-semibold">{metric.value}</div>
                                    <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                        <metric.icon className={`h-4 w-4 ${metric.iconColor}`} />
                                        <span>{metric.description}</span>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="grid auto-rows-min gap-2 md:grid-cols-2">
                            <div className="flex flex-col gap-2 rounded-lg bg-background p-4 shadow-sm">
                                <div>
                                    <div className="text-lg font-semibold">Stock Movement Trends</div>
                                    <div className="text-sm text-muted-foreground">Monthly Overview</div>
                                </div>
                                <div className="flex-1">
                                    <ChartContainer config={chartConfig}>
                                        <BarChart accessibilityLayer data={stockMovementData}>
                                            <CartesianGrid vertical={false} />
                                            <XAxis
                                                dataKey="date"
                                                tickLine={false}
                                                tickMargin={10}
                                                axisLine={false}
                                            />
                                            <YAxis
                                                tickLine={false}
                                                axisLine={false}
                                            />
                                            <ChartTooltip
                                                cursor={false}
                                                content={<ChartTooltipContent indicator="dashed" />}
                                            />
                                            <Legend />
                                            <Bar
                                                dataKey="Stock In"
                                                fill={chartConfig.stockIn.color}
                                                radius={4}
                                            />
                                            <Bar
                                                dataKey="Stock Out"
                                                fill={chartConfig.stockOut.color}
                                                radius={4}
                                            />
                                        </BarChart>
                                    </ChartContainer>
                                </div>
                            </div>

                            {/* Stock Distribution */}
                            <div className="flex flex-col gap-2 rounded-lg bg-background p-4 shadow-sm">
                                <div>
                                    <div className="text-lg font-semibold">Stock Distribution</div>
                                    <div className="text-sm text-muted-foreground">Current Status</div>
                                </div>
                                <div className="flex-1">
                                    <ChartContainer config={chartConfig}>
                                        <PieChart accessibilityLayer>
                                            <Pie
                                                data={stockDistribution}
                                                dataKey="value"
                                                nameKey="name"
                                                cx="50%"
                                                cy="50%"
                                                outerRadius={100}
                                                labelLine={false}
                                                label={({ name, percent }) => `${name} ${(percent * 100).toFixed(1)}%`}
                                            >
                                                <Cell fill={chartConfig.healthy.color} />
                                                <Cell fill={chartConfig.low.color} />
                                                <Cell fill={chartConfig.outOfStock.color} />
                                            </Pie>
                                            <ChartTooltip
                                                cursor={false}
                                                content={<ChartTooltipContent indicator="dashed" />}
                                            />
                                            <Legend />
                                        </PieChart>
                                    </ChartContainer>
                                </div>
                            </div>
                        </div>

                        <div className="grid auto-rows-min gap-2 md:grid-cols-2">
                            <div className="flex flex-col gap-2 rounded-lg bg-background p-4 shadow-sm">
                                <div>
                                    <div className="text-lg font-semibold">Daily Stock Movement</div>
                                    <div className="text-sm text-muted-foreground">Daily Trends</div>
                                </div>
                                <div className="flex-1">
                                    <ChartContainer config={chartConfig}>
                                        <LineChart accessibilityLayer data={stockMovementData}>
                                            <CartesianGrid vertical={false} />
                                            <XAxis
                                                dataKey="date"
                                                tickLine={false}
                                                tickMargin={10}
                                                axisLine={false}
                                            />
                                            <YAxis
                                                tickLine={false}
                                                axisLine={false}
                                            />
                                            <ChartTooltip
                                                cursor={false}
                                                content={<ChartTooltipContent indicator="dashed" />}
                                            />
                                            <Legend />
                                            <Line
                                                type="monotone"
                                                dataKey="Stock In"
                                                stroke={chartConfig.stockIn.color}
                                                strokeWidth={2}
                                                dot={false}
                                            />
                                            <Line
                                                type="monotone"
                                                dataKey="Stock Out"
                                                stroke={chartConfig.stockOut.color}
                                                strokeWidth={2}
                                                dot={false}
                                            />
                                        </LineChart>
                                    </ChartContainer>
                                </div>
                            </div>

                            {/* Category-wise Stock Out */}
                            <div className="flex flex-col gap-2 rounded-lg bg-background p-4 shadow-sm">
                                <div>
                                    <div className="text-lg font-semibold">Category-wise Stock Out</div>
                                    <div className="text-sm text-muted-foreground">By Category</div>
                                </div>
                                <div className="flex-1">
                                    <ChartContainer config={chartConfig}>
                                        <BarChart accessibilityLayer data={category_stock} layout="vertical">
                                            <CartesianGrid horizontal={false} />
                                            <XAxis
                                                type="number"
                                                tickLine={false}
                                                axisLine={false}
                                                tickMargin={10}
                                            />
                                            <YAxis
                                                dataKey="name"
                                                type="category"
                                                tickLine={false}
                                                axisLine={false}
                                                width={100}
                                            />
                                            <ChartTooltip
                                                cursor={false}
                                                content={<ChartTooltipContent indicator="dashed" />}
                                            />
                                            <Bar
                                                dataKey="value"
                                                fill="#60a5fa"
                                                radius={4}
                                            />
                                        </BarChart>
                                    </ChartContainer>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            {/* Low Stock Alert */}
                            <div className="rounded-lg bg-background p-6 shadow-sm">
                                <div className="mb-4">
                                    <div className="text-lg font-semibold">Low Stock Alert</div>
                                    <div className="text-sm text-muted-foreground">Products below threshold</div>
                                </div>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>PRODUCT</TableHead>
                                            <TableHead>CURRENT STOCK</TableHead>
                                            <TableHead>MINIMUM LEVEL</TableHead>
                                            <TableHead>STATUS</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {(Array.isArray(top_selling_products) ? top_selling_products : []).map((product) => (
                                            <TableRow key={product.id}>
                                                <TableCell>{product.name}</TableCell>
                                                <TableCell>{product.stock}</TableCell>
                                                <TableCell>{product.minimum_stock}</TableCell>
                                                <TableCell>
                                                    <span className={`px-2 py-1 rounded-full text-xs ${product.status === 'Out of Stock'
                                                        ? 'bg-red-100 text-red-800'
                                                        : product.status === 'Low Stock'
                                                            ? 'bg-yellow-100 text-yellow-800'
                                                            : 'bg-green-100 text-green-800'
                                                        }`}>
                                                        {product.status}
                                                    </span>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Top Selling Products */}
                            <div className="rounded-lg bg-background p-6 shadow-sm">
                                <div>
                                    <div className="text-lg font-semibold">Top Selling Products</div>
                                    <div className="text-sm text-muted-foreground">Most sold items</div>
                                </div>
                                <div className="flex-1 h-[300px]">
                                    <ChartContainer config={chartConfig}>
                                        <BarChart accessibilityLayer data={top_selling_products} layout="vertical">
                                            <CartesianGrid horizontal={false} />
                                            <XAxis
                                                type="number"
                                                tickLine={false}
                                                axisLine={false}
                                                tickMargin={10}
                                            />
                                            <YAxis
                                                dataKey="name"
                                                type="category"
                                                tickLine={false}
                                                axisLine={false}
                                                width={120}
                                            />
                                            <ChartTooltip
                                                cursor={false}
                                                content={<ChartTooltipContent indicator="dashed" />}
                                            />
                                            <Bar
                                                dataKey="invoice_items_sum_quantity"
                                                fill="#60a5fa"
                                                radius={4}
                                            />
                                        </BarChart>
                                    </ChartContainer>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}