"use client";

import * as React from "react";
import {
    AudioWaveform,
    BookOpen,
    Command,
    GalleryVerticalEnd,
    PieChart,
    Settings2,
    Globe,
    Users,
    Gift,
    CreditCard,
    BadgeDollarSign,
    Package,
    GroupIcon,
    ChartPieIcon,
    Building2Icon,
    FileText
} from "lucide-react";

import { usePage } from "@inertiajs/react";

import { NavOperations } from "@/components/nav-operations";
import { NavManagement } from "@/components/nav-management";
import { NavUser } from "@/components/nav-user";
import { BusinessSwitcher } from "@/components/business-switcher";
import { NavUserDashboard } from "@/Components/nav-user-dashboard";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarRail,
} from "@/components/ui/sidebar";

export function UserSidebar({ businesses, active_business, ...props }) {
    const { url } = usePage();

    const { auth } = usePage().props;

    const data = {
        user: {
            id: auth.user.id,
            name: auth.user.name,
            email: auth.user.email,
            avatar: '/uploads/media/' + auth.user.profile_picture,
        }
    }

    // Transform business objects to the required format
    const businessData = businesses.length > 0 ? businesses.map(business => ({
        name: business.name,
        id: business.id,
        logo: GalleryVerticalEnd,
        plan: business.business_type_id ? "Business" : "Default"
    })) : [
        {
            name: "Default Business",
            id: 1,
            logo: GalleryVerticalEnd,
            plan: "Business"
        }
    ];

    // Enhanced isRoute function that handles routes with wildcards for parameters
    const isRoute = (name) => {
        // Check if it's exactly the current route
        if (route().current(name)) {
            return true;
        }

        // Extract the base path from the current URL
        const currentPath = window.location.pathname;

        // Get the route base
        const routeBase = route(name).split('?')[0]; // Remove any query parameters

        // Create a pattern that matches the route base followed by any parameters
        // This will match routes like /invoices/* or /customers/*
        const pattern = new RegExp(`^${routeBase}(\\/|$)`);

        return pattern.test(currentPath);
    };

    const dashboardItems = [
        {
            title: "Dashboard",
            url: route("dashboard.index"),
            icon: PieChart,
            isActive: isRoute("dashboard.index"),
        },
    ];

    const navOperationsItems = [
        {
            title: "Products",
            url: "#",
            icon: Package,
            isActive: isRoute("products.index") || isRoute("sub_categories.index") || isRoute("main_categories.index") || isRoute("brands.index") ||
                isRoute("product_units.index") || isRoute("inventory_adjustments.index") ||
                isRoute("products.create"),
            items: [
                {
                    title: "All Products",
                    url: route("products.index"),
                    isActive: isRoute("products.index") || isRoute("products.create"),
                },
                {
                    title: "Main Categories",
                    url: route("main_categories.index"),
                    isActive: isRoute("main_categories.index"),
                },
                {
                    title: "Sub Categories",
                    url: route("sub_categories.index"),
                    isActive: isRoute("sub_categories.index"),
                },
                {
                    title: "Brands",
                    url: route("brands.index"),
                    isActive: isRoute("brands.index"),
                },
                {
                    title: "Units",
                    url: route("product_units.index"),
                    isActive: isRoute("product_units.index"),
                },
                {
                    title: "Inventory Adjustment",
                    url: route("inventory_adjustments.index"),
                    isActive: isRoute("inventory_adjustments.index"),
                }
            ],
        },
        {
            title: "Suppliers",
            url: "#",
            icon: Users,
            isActive: isRoute("vendors.index") || isRoute("purchase_orders.index") || isRoute("cash_purchases.index") ||
                isRoute("bill_invoices.index") || isRoute("bill_payments.index") || isRoute("purchase_returns.index"),
            items: [
                {
                    title: "All Suppliers",
                    url: route("vendors.index"),
                    isActive: isRoute("vendors.index"),
                },
                {
                    title: "Purchase Order",
                    url: route("purchase_orders.index"),
                    isActive: isRoute("purchase_orders.index"),
                },
                {
                    title: "Cash Purchase",
                    url: route("cash_purchases.index"),
                    isActive: isRoute("cash_purchases.index"),
                },
                {
                    title: "Bill Invoice",
                    url: route("bill_invoices.index"),
                    isActive: isRoute("bill_invoices.index"),
                },
                {
                    title: "Pay Bills",
                    url: route("bill_payments.index"),
                    isActive: isRoute("bill_payments.index"),
                },
                {
                    title: "Purchase Return",
                    url: route("purchase_returns.index"),
                    isActive: isRoute("purchase_returns.index"),
                }
            ],
        },
        {
            title: "Customers",
            url: "#",
            icon: Users,
            isActive: isRoute("customers.index") || isRoute("receipts.index") || isRoute("invoices.index") ||
                isRoute("medical_records.index") || isRoute("quotations.index") || isRoute("deffered_invoices.index") ||
                isRoute("sales_returns.index") || isRoute("prescriptions.index") || isRoute("receive_payments.index"),
            items: [
                {
                    title: "All Customers",
                    url: route("customers.index"),
                    isActive: isRoute("customers.index"),
                },
                {
                    title: "Cash Invoice",
                    url: route("receipts.index"),
                    isActive: isRoute("receipts.index"),
                },
                {
                    title: "Credit Invoice",
                    url: route("invoices.index"),
                    isActive: isRoute("invoices.index"),
                },
                {
                    title: "Medical Records",
                    url: route("medical_records.index"),
                    isActive: isRoute("medical_records.index"),
                },
                {
                    title: "Prescriptions",
                    url: route("prescriptions.index"),
                    isActive: isRoute("prescriptions.index"),
                },
                {
                    title: "Deffered Invoice",
                    url: route("deffered_invoices.index"),
                    isActive: isRoute("deffered_invoices.index"),
                },
                {
                    title: "Received Payment",
                    url: route("receive_payments.index"),
                    isActive: isRoute("receive_payments.index"),
                },
                {
                    title: "Sales Return",
                    url: route("sales_returns.index"),
                    isActive: isRoute("sales_returns.index"),
                },
                {
                    title: "Quotations",
                    url: route("quotations.index"),
                    isActive: isRoute("quotations.index"),
                }
            ]
        }
    ];

    const navManagementItems = [
        {
            title: "HR & Payroll",
            url: "#",
            icon: GroupIcon,
            isActive: isRoute("staffs.index") || isRoute("attendance.index") || isRoute("departments.index") ||
                isRoute("designations.index") || isRoute("payslips.index") || isRoute("holidays.index") || isRoute("leaves.index") ||
                isRoute("awards.index"),
            items: [
                {
                    title: "Staff Management",
                    url: route("staffs.index"),
                    isActive: isRoute("staffs.index"),
                },
                {
                    title: "Attendance",
                    url: route("attendance.index"),
                    isActive: isRoute("attendance.index"),
                },
                {
                    title: "Departments",
                    url: route("departments.index"),
                    isActive: isRoute("departments.index"),
                },
                {
                    title: "Designations",
                    url: route("designations.index"),
                    isActive: isRoute("designations.index"),
                },
                {
                    title: "Manage Payroll",
                    url: route("payslips.index"),
                    isActive: isRoute("payslips.index"),
                },
                {
                    title: "Holidays",
                    url: route("holidays.index"),
                    isActive: isRoute("holidays.index"),
                },
                {
                    title: "Leave Management",
                    url: route("leaves.index"),
                    isActive: isRoute("leaves.index"),
                },
                {
                    title: "Awards",
                    url: route("awards.index"),
                    isActive: isRoute("awards.index"),
                }
            ],
        },
        {
            title: "Accounting",
            url: "#",
            icon: ChartPieIcon,
            isActive: isRoute("accounts.index") || isRoute("journals.index") || isRoute("transaction_methods.index"),
            items: [
                {
                    title: "Chart of Accounts",
                    url: route("accounts.index"),
                    isActive: isRoute("accounts.index"),
                },
                {
                    title: "Journal Entry",
                    url: route("journals.index"),
                    isActive: isRoute("journals.index"),
                },
                {
                    title: "Transaction Methods",
                    url: route("transaction_methods.index"),
                    isActive: isRoute("transaction_methods.index"),
                }
            ]
        },
        {
            title: "Business",
            url: "#",
            icon: Building2Icon,
            isActive: isRoute("business.index") || isRoute("roles.index") ||
                isRoute("taxes.index") || isRoute("currency.index") || isRoute("audit_logs.index") ||
                url.includes('/business/settings/'),
            items: [
                {
                    title: "Manage Businesses",
                    url: route("business.index"),
                    isActive: isRoute("business.index"),
                },
                {
                    title: "Roles & Permissions",
                    url: route("roles.index"),
                    isActive: isRoute("roles.index"),
                },
                {
                    title: "Business Settings",
                    url: route("business.settings", active_business.id),
                    isActive: url.includes('/business/settings/'),
                },
                {
                    title: "Tax Settings",
                    url: route("taxes.index"),
                    isActive: isRoute("taxes.index"),
                },
                {
                    title: "Currency Settings",
                    url: route("currency.index"),
                    isActive: isRoute("currency.index"),
                },
                {
                    title: "Audit Logs",
                    url: route("audit_logs.index"),
                    isActive: isRoute("audit_logs.index"),
                }
            ]
        },
        {
            title: "Reports",
            url: "#",
            icon: Building2Icon,
            isActive: isRoute("reports.journal") || isRoute("reports.ledger") || isRoute("reports.income_statement") ||
                isRoute("reports.trial_balance") || isRoute("reports.balance_sheet") || isRoute("reports.receivables") ||
                isRoute("reports.payables") || isRoute("reports.payroll_summary") || isRoute("reports.payroll_cost") || isRoute("reports.income_by_customer"),
            items: [
                {
                    title: "General Journal",
                    url: route("reports.journal"),
                    isActive: isRoute("reports.journal"),
                },
                {
                    title: "General Ledger",
                    url: route("reports.ledger"),
                    isActive: isRoute("reports.ledger"),
                },
                {
                    title: "Income Statement",
                    url: route("reports.income_statement"),
                    isActive: isRoute("reports.income_statement"),
                },
                {
                    title: "Trial Balance",
                    url: route("reports.trial_balance"),
                    isActive: isRoute("reports.trial_balance"),
                },
                {
                    title: "Balance Sheet",
                    url: route("reports.balance_sheet"),
                    isActive: isRoute("reports.balance_sheet"),
                },
                {
                    title: "Income By Customer",
                    url: route("reports.income_by_customer"),
                    isActive: isRoute("reports.income_by_customer"),
                },
                {
                    title: "Receivables",
                    url: route("reports.receivables"),
                    isActive: isRoute("reports.receivables"),
                },
                {
                    title: "Payables",
                    url: route("reports.payables"),
                    isActive: isRoute("reports.payables"),
                },
                {
                    title: "Payroll Summary",
                    url: route("reports.payroll_summary"),
                    isActive: isRoute("reports.payroll_summary"),
                },
                {
                    title: "Monthly Payroll Cost",
                    url: route("reports.payroll_cost"),
                    isActive: isRoute("reports.payroll_cost"),
                }
            ]
        },
    ];

    return (
        <Sidebar collapsible="icon" {...props}>
            <SidebarHeader>
                <BusinessSwitcher businesses={businessData} active_business={active_business} />
            </SidebarHeader>
            <SidebarContent>
                <NavUserDashboard items={dashboardItems} />
                <NavOperations items={navOperationsItems} />
                <NavManagement items={navManagementItems} />
            </SidebarContent>
            <SidebarFooter>
                <NavUser user={data.user} />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
