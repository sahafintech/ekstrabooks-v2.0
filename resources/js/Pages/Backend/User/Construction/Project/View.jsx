import React, { useState } from "react";
import { Head, Link, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import {
    Calendar,
    Clock,
    DollarSign,
    FileText,
    ListChecks,
    Receipt,
    Users,
    CheckCircle,
} from "lucide-react";
import { formatCurrency } from "@/lib/utils";
import TaskList from "./TaskList";
import BudgetList from "./BudgetList";
import TransactionsList from "./TransactionsList";
// Summary Cards Component
const SummaryCards = ({ project = {} }) => {
    const cards = [
        {
            title: "Total Tasks",
            value: project.total_tasks || 0,
            description: "Total number of tasks",
            icon: ListChecks,
            iconColor: "text-blue-500",
        },
        {
            title: "Completed Tasks",
            value: project.completed_tasks || 0,
            description: "Tasks marked as completed",
            icon: CheckCircle,
            iconColor: "text-purple-500",
        },
        {
            title: "Original Budget",
            value: formatCurrency({ amount: project.budget || 0 }),
            description: "Total project budget",
            icon: DollarSign,
            iconColor: "text-green-500",
        },
        {
            title: "Actual Budget",
            value: formatCurrency({ amount: project.actual_budget || 0 }),
            description: "Actual project budget",
            icon: DollarSign,
            iconColor: "text-green-500",
        },
    ];

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {cards.map((card, index) => (
                <div key={index} className="bg-white rounded-lg shadow-sm p-4">
                    <div className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <h3 className="text-sm font-medium">{card.title}</h3>
                        <card.icon className={`h-4 w-4 ${card.iconColor}`} />
                    </div>
                    <div className="text-2xl font-bold">
                        {card.value}
                        <p className="text-xs text-muted-foreground">
                            {card.description}
                        </p>
                    </div>
                </div>
            ))}
        </div>
    );
};

export default function View({ project, activeTab, cost_codes, unit_of_measures, transactions }) {
    const [currentTab, setCurrentTab] = useState(activeTab);

    const ProjectStatusBadge = ({ status }) => {
        const statusMap = {
            Planning: {
                label: "Planning",
                className:
                    "text-gray-600 bg-gray-200 px-3 py-1 rounded text-xs",
            },
            "In Progress": {
                label: "In Progress",
                className:
                    "text-green-400 bg-green-200 px-3 py-1 rounded text-xs",
            },
            Completed: {
                label: "Completed",
                className:
                    "text-green-400 bg-green-200 px-3 py-1 rounded text-xs",
            },
            "On Hold": {
                label: "On Hold",
                className:
                    "text-yellow-400 bg-yellow-200 px-3 py-1 rounded text-xs",
            },
            Cancelled: {
                label: "Cancelled",
                className: "text-red-400 bg-red-200 px-3 py-1 rounded text-xs",
            },
            Archived: {
                label: "Archived",
                className:
                    "text-gray-400 bg-gray-200 px-3 py-1 rounded text-xs",
            },
        };

        return (
            <span className={statusMap[status].className}>
                {statusMap[status].label}
            </span>
        );
    };

    const ProjectPriorityBadge = ({ status }) => {
        const statusMap = {
            Low: {
                label: "Low",
                className:
                    "text-green-400 bg-green-200 px-3 py-1 rounded text-xs",
            },
            Medium: {
                label: "Medium",
                className:
                    "text-yellow-400 bg-yellow-200 px-3 py-1 rounded text-xs",
            },
            High: {
                label: "High",
                className: "text-red-400 bg-red-200 px-3 py-1 rounded text-xs",
            },
            Critical: {
                label: "Critical",
                className: "text-red-400 bg-red-200 px-3 py-1 rounded text-xs",
            },
        };

        return (
            <span className={statusMap[status].className}>
                {statusMap[status].label}
            </span>
        );
    };

    // Handle tab change
    const handleTabChange = (value) => {
        setCurrentTab(value);
        router.get(
            route("projects.show", project.id),
            { tab: value },
            { preserveState: true }
        );
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Projects"
                        subpage="View"
                        url="projects.index"
                    />

                    <div className="p-4">
                        {/* Project Overview Card */}
                        <div className="mb-6">
                            <div>
                                <div className="flex justify-between items-start">
                                    <div>
                                        <h1 className="text-2xl font-bold">
                                            {project.project_name}
                                        </h1>
                                        <p className="text-gray-500 mt-1">
                                            {project.project_code}
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
                                        <ProjectStatusBadge
                                            status={project.status}
                                        />
                                        <ProjectPriorityBadge
                                            status={project.priority}
                                        />
                                    </div>
                                </div>
                            </div>
                            {/* Project Details */}
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                                <div className="flex items-center gap-2">
                                    <Calendar className="h-5 w-5 text-gray-500" />
                                    <div>
                                        <p className="text-sm text-gray-500">
                                            Start Date
                                        </p>
                                        <p className="font-medium">
                                            {project.start_date}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Clock className="h-5 w-5 text-gray-500" />
                                    <div>
                                        <p className="text-sm text-gray-500">
                                            End Date
                                        </p>
                                        <p className="font-medium">
                                            {project.end_date}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Users className="h-5 w-5 text-gray-500" />
                                    <div>
                                        <p className="text-sm text-gray-500">
                                            Customer
                                        </p>
                                        <p className="font-medium">
                                            {project.customer?.name ||
                                                "Not Assigned"}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Users className="h-5 w-5 text-gray-500" />
                                    <div>
                                        <p className="text-sm text-gray-500">
                                            Project Manager
                                        </p>
                                        <p className="font-medium">
                                            {project.manager?.name ||
                                                "Not Assigned"}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Summary Cards */}
                        <SummaryCards project={project} />

                        {/* Tabs */}
                        <Tabs
                            defaultValue={currentTab}
                            className="w-full"
                            onValueChange={handleTabChange}
                        >
                            <TabsList className="grid grid-cols-7 mb-4">
                                <TabsTrigger
                                    value="tasks"
                                    className="flex items-center gap-2"
                                >
                                    <ListChecks className="h-4 w-4" />
                                    Tasks
                                </TabsTrigger>
                                <TabsTrigger
                                    value="budgets"
                                    className="flex items-center gap-2"
                                >
                                    <DollarSign className="h-4 w-4" />
                                    Budgets
                                </TabsTrigger>
                                <TabsTrigger
                                    value="transactions"
                                    className="flex items-center gap-2"
                                >
                                    <Receipt className="h-4 w-4" />
                                    Transactions
                                </TabsTrigger>
                                <TabsTrigger
                                    value="change-orders"
                                    className="flex items-center gap-2"
                                >
                                    <FileText className="h-4 w-4" />
                                    Change Orders
                                </TabsTrigger>
                                <TabsTrigger
                                    value="time-sheet"
                                    className="flex items-center gap-2"
                                >
                                    <Clock className="h-4 w-4" />
                                    Time Sheet
                                </TabsTrigger>
                                <TabsTrigger
                                    value="compliance"
                                    className="flex items-center gap-2"
                                >
                                    <FileText className="h-4 w-4" />
                                    Compliance
                                </TabsTrigger>
                            </TabsList>

                            <TabsContent value="tasks">
                                <TaskList project={project} />
                            </TabsContent>

                            <TabsContent value="budgets">
                                <BudgetList project={project} cost_codes={cost_codes} unit_of_measures={unit_of_measures} />
                            </TabsContent>

                            <TabsContent value="transactions">
                                <TransactionsList transactions={transactions} />
                            </TabsContent>

                            <TabsContent value="change-orders">
                                <div>
                                    <h1>Change Orders</h1>
                                </div>
                                <div>
                                    {/* Change Orders content will go here */}
                                    <p className="text-gray-500">
                                        Change order management interface will
                                        be implemented here.
                                    </p>
                                </div>
                            </TabsContent>

                            <TabsContent value="time-sheet">
                                <div>
                                    <h1>Time Sheet</h1>
                                </div>
                                <div>
                                    {/* Time Sheet content will go here */}
                                    <p className="text-gray-500">
                                        Time tracking and management interface
                                        will be implemented here.
                                    </p>
                                </div>
                            </TabsContent>

                            <TabsContent value="compliance">
                                <div>
                                    <h1>Compliance and Documentation</h1>
                                </div>
                                <div>
                                    {/* Compliance content will go here */}
                                    <p className="text-gray-500">
                                        Compliance tracking and documentation
                                        management interface will be implemented
                                        here.
                                    </p>
                                </div>
                            </TabsContent>
                        </Tabs>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
