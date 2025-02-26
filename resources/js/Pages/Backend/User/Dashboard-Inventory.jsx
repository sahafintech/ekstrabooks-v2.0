import { useState } from "react";
import { Head, usePage, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
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
    ChartTooltipContent
} from "@/components/ui/chart";
import TableWrapper from "@/Components/shared/TableWrapper";
import PageHeader from "@/Components/PageHeader";

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
    const [dashboardType, setDashboardType] = useState(dashboard_type);

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

    // Format the data for the stats cards
    const statsCards = [
        {
            title: "Total Stock Value",
            value: `${inventory_value}`,
            description: "Current inventory value",
            icon: BoxIcon,
            iconColor: "text-blue-500"
        },
        {
            title: "Low Stock Items",
            value: low_stock_products,
            description: "Items below threshold",
            icon: AlertTriangle,
            iconColor: "text-yellow-500"
        },
        {
            title: "Items Sold",
            value: stock_movements.sales,
            description: "Total units sold (days)",
            icon: ShoppingCart,
            iconColor: "text-green-500"
        },
        {
            title: "Total Products",
            value: total_products,
            description: "Active products",
            icon: Package,
            iconColor: "text-purple-500"
        }
    ];

    // Format data for stock metrics
    const stockMetrics = [
        {
            title: "Current Stock",
            value: closing_stock,
            description: "Total units in stock",
            icon: BoxIcon,
            iconColor: "text-gray-500"
        },
        {
            title: "Stock In",
            value: stock_in,
            description: "Total units received",
            icon: ArrowUp,
            iconColor: "text-green-500"
        },
        {
            title: "Stock Out",
            value: stock_out,
            description: "Total units out",
            icon: ArrowDown,
            iconColor: "text-red-500"
        },
        {
            title: "Stock Turnover",
            value: `${stock_turnover_rate}%`,
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

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-7">
                            {/* Stock Movement Trends */}
                            <div className="col-span-4 flex flex-col rounded-lg bg-background p-6 shadow-sm mt-3">
                                <div>
                                    <div className="text-lg font-semibold">Stock Movement Trends</div>
                                    <div className="text-sm text-muted-foreground">Monthly Overview</div>
                                </div>
                                <div className="flex-1">
                                    <ResponsiveContainer width="100%" height={300}>
                                        <BarChart data={stockMovementData}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="date" />
                                            <YAxis />
                                            <Tooltip />
                                            <Legend />
                                            <Bar dataKey="Stock In" fill={chartConfig.stockIn.color} />
                                            <Bar dataKey="Stock Out" fill={chartConfig.stockOut.color} />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                            </div>

                            {/* Stock Distribution */}
                            <div className="col-span-3 flex flex-col rounded-lg bg-background p-6 shadow-sm mt-3">
                                <div>
                                    <div className="text-lg font-semibold">Stock Distribution</div>
                                    <div className="text-sm text-muted-foreground">Current Status</div>
                                </div>
                                <div className="flex-1">
                                    <ResponsiveContainer width="100%" height={300}>
                                        <PieChart>
                                            <Pie
                                                data={stockDistribution}
                                                dataKey="value"
                                                nameKey="name"
                                                cx="50%"
                                                cy="50%"
                                                outerRadius={100}
                                                label
                                            >
                                                <Cell fill={chartConfig.healthy.color} />
                                                <Cell fill={chartConfig.low.color} />
                                                <Cell fill={chartConfig.outOfStock.color} />
                                            </Pie>
                                            <Tooltip />
                                            <Legend />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-7">
                            {/* Daily Stock Movement */}
                            <div className="col-span-4 flex flex-col rounded-lg bg-background p-6 shadow-sm mt-3">
                                <div>
                                    <div className="text-lg font-semibold">Daily Stock Movement</div>
                                    <div className="text-sm text-muted-foreground">Daily Trends</div>
                                </div>
                                <div className="flex-1">
                                    <ResponsiveContainer width="100%" height={300}>
                                        <LineChart data={stockMovementData}>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis dataKey="date" />
                                            <YAxis />
                                            <Tooltip />
                                            <Legend />
                                            <Line type="monotone" dataKey="Stock In" stroke={chartConfig.stockIn.color} />
                                            <Line type="monotone" dataKey="Stock Out" stroke={chartConfig.stockOut.color} />
                                        </LineChart>
                                    </ResponsiveContainer>
                                </div>
                            </div>

                            {/* Category-wise Stock Out */}
                            <div className="col-span-3 flex flex-col rounded-lg bg-background p-6 shadow-sm mt-3">
                                <div>
                                    <div className="text-lg font-semibold">Category-wise Stock Out</div>
                                    <div className="text-sm text-muted-foreground">By Category</div>
                                </div>
                                <div className="flex-1">
                                    <ResponsiveContainer width="100%" height={300}>
                                        <BarChart data={category_stock} layout="vertical">
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis type="number" />
                                            <YAxis dataKey="name" type="category" width={100} />
                                            <Tooltip />
                                            <Bar dataKey="value" fill="#60a5fa" />
                                        </BarChart>
                                    </ResponsiveContainer>
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
                                <TableWrapper>
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
                                                        <span className={`px-2 py-1 rounded-full text-xs ${
                                                            product.status === 'Out of Stock'
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
                                </TableWrapper>
                            </div>

                            {/* Top Selling Products */}
                            <div className="rounded-lg bg-background p-6 shadow-sm">
                                <div>
                                    <div className="text-lg font-semibold">Top Selling Products</div>
                                    <div className="text-sm text-muted-foreground">Most sold items</div>
                                </div>
                                <div className="flex-1 h-[300px]">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <BarChart data={top_selling_products} layout="vertical">
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis type="number" />
                                            <YAxis dataKey="name" type="category" width={120} />
                                            <Tooltip />
                                            <Bar dataKey="invoice_items_sum_quantity" fill="#60a5fa" />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}