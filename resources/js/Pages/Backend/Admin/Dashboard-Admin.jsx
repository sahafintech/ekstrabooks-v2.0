import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PageHeader from "@/Components/PageHeader";
import { SidebarInset } from "@/components/ui/sidebar";
import * as React from "react";
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { TrendingUp, MoreVertical } from "lucide-react";
import { Link } from "@inertiajs/react";
import {
    Bar,
    BarChart,
    CartesianGrid,
    XAxis,
    Label,
    Pie,
    PieChart,
} from "recharts";
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from "@/components/ui/chart";
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Button } from "@/Components/ui/button";
import TableWrapper from "@/Components/shared/TableWrapper";
import TableActions from "@/Components/shared/TableActions";
import { Eye, Pencil, Trash2 } from "lucide-react";

const DashboardWidgets = ({ data }) => (
    <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
        <div className="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div className="flex flex-col gap-1 rounded-lg bg-background p-4 shadow-sm">
                <div className="text-sm text-muted-foreground">Total Users</div>
                <div className="flex items-end gap-2">
                    <div className="text-3xl font-semibold">{data.total_user}</div>
                    <div className="text-sm text-muted-foreground">All Users</div>
                </div>
            </div>
            <div className="flex flex-col gap-1 rounded-lg bg-background p-4 shadow-sm">
                <div className="text-sm text-muted-foreground">Total Sales</div>
                <div className="flex items-end gap-2">
                    <div className="text-3xl font-semibold">{data.total_owner}</div>
                    <div className="text-sm text-muted-foreground">Total Revenue</div>
                </div>
            </div>
            <div className="flex flex-col gap-1 rounded-lg bg-background p-4 shadow-sm">
                <div className="text-sm text-muted-foreground">Trial Users</div>
                <div className="flex items-end gap-2">
                    <div className="text-3xl font-semibold">{data.trial_users}</div>
                    <div className="text-sm text-muted-foreground">Trial Accounts</div>
                </div>
            </div>
            <div className="flex flex-col gap-1 rounded-lg bg-background p-4 shadow-sm">
                <div className="text-sm text-muted-foreground">Expired Users</div>
                <div className="flex items-end gap-2">
                    <div className="text-3xl font-semibold">{data.expired_users}</div>
                    <div className="text-sm text-muted-foreground">Expired Accounts</div>
                </div>
            </div>
        </div>
        <div className="min-h-[100vh] flex-1 rounded-xl bg-muted/50 md:min-h-min" />
    </div>
);

const chartData = [
    { month: "January", desktop: 186, mobile: 80 },
    { month: "February", desktop: 305, mobile: 200 },
    { month: "March", desktop: 237, mobile: 120 },
    { month: "April", desktop: 73, mobile: 190 },
    { month: "May", desktop: 209, mobile: 130 },
    { month: "June", desktop: 214, mobile: 140 },
];

const chartConfig = {
    desktop: {
        label: "Desktop",
        color: "hsl(var(--chart-1))",
    },
    mobile: {
        label: "Mobile",
        color: "hsl(var(--chart-2))",
    },
};

const chartData2 = [
    { browser: "chrome", visitors: 275, fill: "var(--color-chrome)" },
    { browser: "safari", visitors: 200, fill: "var(--color-safari)" },
    { browser: "firefox", visitors: 287, fill: "var(--color-firefox)" },
    { browser: "edge", visitors: 173, fill: "var(--color-edge)" },
    { browser: "other", visitors: 190, fill: "var(--color-other)" },
];

const chartConfig2 = {
    visitors: {
        label: "Visitors",
    },
    chrome: {
        label: "Chrome",
        color: "hsl(var(--chart-1))",
    },
    safari: {
        label: "Safari",
        color: "hsl(var(--chart-2))",
    },
    firefox: {
        label: "Firefox",
        color: "hsl(var(--chart-3))",
    },
    edge: {
        label: "Edge",
        color: "hsl(var(--chart-4))",
    },
    other: {
        label: "Other",
        color: "hsl(var(--chart-5))",
    },
};

const DashboardCharts = ({ totalVisitors }) => (
    <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
        <div className="grid auto-rows-min gap-4 md:grid-cols-2">
            <div className="flex flex-col gap-2 rounded-lg bg-background p-4 shadow-sm">
                <div>
                    <div className="text-lg font-semibold">Bar Chart - Multiple</div>
                    <div className="text-sm text-muted-foreground">January - June 2024</div>
                </div>
                <div className="flex-1">
                    <ChartContainer config={chartConfig}>
                        <BarChart accessibilityLayer data={chartData}>
                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="month"
                                tickLine={false}
                                tickMargin={10}
                                axisLine={false}
                                tickFormatter={(value) => value.slice(0, 3)}
                            />
                            <ChartTooltip
                                cursor={false}
                                content={
                                    <ChartTooltipContent indicator="dashed" />
                                }
                            />
                            <Bar
                                dataKey="desktop"
                                fill="var(--color-desktop)"
                                radius={4}
                            />
                            <Bar
                                dataKey="mobile"
                                fill="var(--color-mobile)"
                                radius={4}
                            />
                        </BarChart>
                    </ChartContainer>
                </div>
                <div className="flex flex-col gap-1 text-sm">
                    <div className="flex gap-2 font-medium leading-none">
                        Trending up by 5.2% this month{" "}
                        <TrendingUp className="h-4 w-4" />
                    </div>
                    <div className="leading-none text-muted-foreground">
                        Showing total visitors for the last 6 months
                    </div>
                </div>
            </div>

            <div className="flex flex-col gap-2 rounded-lg bg-background p-4 shadow-sm">
                <div className="text-center">
                    <div className="text-lg font-semibold">Pie Chart - Donut with Text</div>
                    <div className="text-sm text-muted-foreground">January - June 2024</div>
                </div>
                <div className="flex-1">
                    <ChartContainer
                        config={chartConfig2}
                        className="mx-auto aspect-square max-h-[250px]"
                    >
                        <PieChart>
                            <ChartTooltip
                                cursor={false}
                                content={<ChartTooltipContent hideLabel />}
                            />
                            <Pie
                                data={chartData2}
                                dataKey="visitors"
                                nameKey="browser"
                                innerRadius={60}
                                strokeWidth={5}
                            >
                                <Label
                                    content={({ viewBox }) => {
                                        if (
                                            viewBox &&
                                            "cx" in viewBox &&
                                            "cy" in viewBox
                                        ) {
                                            return (
                                                <text
                                                    x={viewBox.cx}
                                                    y={viewBox.cy}
                                                    textAnchor="middle"
                                                    dominantBaseline="middle"
                                                >
                                                    <tspan
                                                        x={viewBox.cx}
                                                        y={viewBox.cy}
                                                        className="fill-foreground text-3xl font-bold"
                                                    >
                                                        {totalVisitors.toLocaleString()}
                                                    </tspan>
                                                    <tspan
                                                        x={viewBox.cx}
                                                        y={
                                                            (viewBox.cy || 0) +
                                                            24
                                                        }
                                                        className="fill-muted-foreground"
                                                    >
                                                        Visitors
                                                    </tspan>
                                                </text>
                                            );
                                        }
                                    }}
                                />
                            </Pie>
                        </PieChart>
                    </ChartContainer>
                </div>
                <div className="flex flex-col gap-1 text-sm">
                    <div className="flex items-center justify-center gap-2 font-medium leading-none">
                        Trending up by 5.2% this month{" "}
                        <TrendingUp className="h-4 w-4" />
                    </div>
                    <div className="text-center leading-none text-muted-foreground">
                        Showing total visitors for the last 6 months
                    </div>
                </div>
            </div>
        </div>
    </div>
);

const DashboardTables = ({ data }) => {
    const getRowActions = (user) => [
        {
            label: "View",
            icon: Eye,
            onClick: () => window.location = route("users.show", user.id)
        },
        {
            label: "Edit",
            icon: Pencil,
            onClick: () => window.location = route("users.edit", user.id)
        },
        {
            label: "Delete",
            icon: Trash2,
            onClick: () => confirmUserDeletion(user.id),
            className: "text-destructive focus:text-destructive"
        }
    ];

    return (
        <>
            <div className="p-4 pt-0">
                <TableWrapper>
                    <Table>
                        <TableCaption>A list of your recent users.</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Package</TableHead>
                                <TableHead>Membership</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Valid Until</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.newUsers.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell>{user.name}</TableCell>
                                    <TableCell>{user.package?.name || "-"}</TableCell>
                                    <TableCell>{user.package?.membership_type || "-"}</TableCell>
                                    <TableCell>{user.status}</TableCell>
                                    <TableCell>{user.valid_to}</TableCell>
                                    <TableCell>
                                        <TableActions actions={getRowActions(user)} />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </TableWrapper>
            </div>

            <div className="p-4 pt-0">
                <TableWrapper>
                    <Table>
                        <TableCaption>Recent Subscription Payments</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead>User</TableHead>
                                <TableHead>Order Id</TableHead>
                                <TableHead>Method</TableHead>
                                <TableHead>Package</TableHead>
                                <TableHead>Amount</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.recentPayments.map((payment) => (
                                <TableRow key={payment.id}>
                                    <TableCell>{payment.user.name}</TableCell>
                                    <TableCell>{payment.order_id}</TableCell>
                                    <TableCell>{payment.payment_method}</TableCell>
                                    <TableCell>{payment.package.name}</TableCell>
                                    <TableCell>{payment.amount}</TableCell>
                                    <TableCell>{payment.status}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </TableWrapper>
            </div>
        </>
    );
};

export default function Dashboard({ data }) {
    const totalVisitors = React.useMemo(() => {
        return chartData2.reduce((acc, curr) => acc + curr.visitors, 0);
    }, []);
    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Dashboard"
                    subpage="Dashboard"
                    url="dashboard.index"
                />
                <DashboardWidgets data={data} />
                <DashboardCharts totalVisitors={totalVisitors} />
                <DashboardTables data={data} />
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
