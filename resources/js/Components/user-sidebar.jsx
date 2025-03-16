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

const data = {
    user: {
        name: "shadcn",
        email: "m@example.com",
        avatar: "/avatars/shadcn.jpg",
    },
};

export function UserSidebar({ businesses, active_business, ...props }) {
    const { url } = usePage();

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

    const isRoute = (name) => {
        return route().current(name) || url.startsWith(route(name));
    };

    const dashboardItems = [
        {
            name: "Dashboard",
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
                },
                {
                    title: "Main Categories",
                    url: route("main_categories.index"),
                },
                {
                    title: "Sub Categories",
                    url: route("sub_categories.index"),
                },
                {
                    title: "Brands",
                    url: route("brands.index"),
                },
                {
                    title: "Units",
                    url: route("product_units.index"),
                },
                {
                    title: "Inventory Adjustment",
                    url: route("inventory_adjustments.index"),
                }
            ],
        },
        {
            title: "Suppliers",
            url: "#",
            icon: Users,
            isActive: isRoute("vendors.index"),
            items: [
                {
                    title: "All Suppliers",
                    url: route("vendors.index"),
                },
                {
                    title: "Purchase Order",
                    url: route("purchase_orders.index"),
                },
                {
                    title: "Cash Purchase",
                    url: route("cash_purchases.index"),
                },
                {
                    title: "Bill Invoice",
                    url: route("bill_invoices.index"),
                },
                {
                    title: "Pay Bills",
                    url: route("billI_invoices.pay_bill"),
                },
                {
                    title: "Purchase Return",
                    url: route("purchase_returns.index"),
                }
            ],
        },
        {
            title: "Customers",
            url: "#",
            icon: Users,
            isActive: isRoute("customers.index"),
            items: [
                {
                    title: "All Customers",
                    url: route("customers.index"),
                },
                {
                    title: "Cash Invoice",
                    url: route("receipts.index"),
                },
                {
                    title: "Credit Invoice",
                    url: route("invoices.index"),
                },
                {
                    title: "Medical Records",
                    url: route("medical_records.index"),
                },
                {
                    title: "Prescriptions",
                    url: route("prescriptions.index"),
                },
                {
                    title: "Deffered Invoice",
                    url: route("deffered_invoices.index"),
                },
                {
                    title: "Received Payment",
                    url: route("receive_payments.index"),
                },
                {
                    title: "Sales Return",
                    url: route("sales_returns.index"),
                },
                {
                    title: "Quotations",
                    url: route("quotations.index"),
                }
            ]
        }
    ];

    const navManagementItems = [
        {
            title: "HR & Payroll",
            url: "#",
            icon: GroupIcon,
            items: [
                {
                    title: "Staff Management",
                    url: route("staffs.index"),
                },
                {
                    title: "Attendance",
                    url: route("attendance.index"),
                },
                {
                    title: "Departments",
                    url: route("departments.index"),
                },
                {
                    title: "Designations",
                    url: route("designations.index"),
                },
                {
                    title: "Manage Payroll",
                    url: route("payslips.index"),
                },
                {
                    title: "Accrue Payroll",
                    url: route("payslips.accrue"),
                },
                {
                    title: "Make Payment",
                    url: route("payslips.make_payment"),
                },
                {
                    title: "Holidays",
                    url: route("holidays.index"),
                },
                {
                    title: "Leave Management",
                    url: route("leaves.index"),
                },
                {
                    title: "Awards",
                    url: route("awards.index"),
                }
            ],
        },
        {
            title: "Accounting",
            url: "#",
            icon: ChartPieIcon,
            items: [
                {
                    title: "Chart of Accounts",
                    url: route("chart_of_accounts.list_chart_of_accounts"),
                },
                {
                    title: "Journal Entry",
                    url: route("journals.index"),
                },
                {
                    title: "Transaction Methods",
                    url: route("transaction_methods.index"),
                }
            ]
        },
        {
            title: "Business",
            url: "#",
            icon: Building2Icon,
            items: [
                {
                    title: "Manage Businesses",
                    url: route("business.index"),
                },
                {
                    title: "Roles & Permissions",
                    url: route("roles.index"),
                },
                {
                    title: "Business Settings",
                    url: route("business.settings", active_business.id),
                },
                {
                    title: "Tax Settings",
                    url: route("taxes.index"),
                },
                {
                    title: "Currency Settings",
                    url: route("currency.index")
                },
                {
                    title: "Audit Logs",
                    url: route("audit_logs.index")
                }
            ]
        },
        {
            name: "Reports",
            url: "#",
            icon: FileText,
        }
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
