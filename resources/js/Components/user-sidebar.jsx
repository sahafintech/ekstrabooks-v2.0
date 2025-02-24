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
            isActive: isRoute("products.index") || isRoute("categories.index") || isRoute("brands.index") || isRoute("product_units.index") || isRoute("inventory_adjustments.index"),
            items: [
                {
                    title: "All Products",
                    url: route("products.index"),
                },
                {
                    title: "Categories",
                    url: route("categories.index"),
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
                    tite: "Cash Purchase",
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
            name: "Languages",
            url: "#",
            icon: Globe,
        },
        {
            title: "System Settings",
            url: "#",
            icon: Settings2,
            items: [
                {
                    title: "Business Types",
                    url: "#",
                },
                {
                    title: "Email Subscribers",
                    url: "#",
                },
                {
                    title: "Notification Templates",
                    url: "#",
                },
                {
                    title: "Database Backups",
                    url: "#",
                },
                {
                    title: "Account Types",
                    url: "#",
                },
            ],
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
