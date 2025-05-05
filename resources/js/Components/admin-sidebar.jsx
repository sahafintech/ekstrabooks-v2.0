"use client";

import * as React from "react";
import {
    PieChart,
    Users,
    Gift,
    CreditCard,
    BadgeDollarSign,
    BookOpen,
    Settings2,
    Globe,
} from "lucide-react";
import { usePage } from "@inertiajs/react";

import { NavMain } from "@/components/nav-main";
import { NavSettings } from "@/components/nav-settings";
import { NavUser } from "@/components/nav-user";
import { NavAdminDashboard } from "@/Components/nav-admin-dashboard";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarRail,
} from "@/components/ui/sidebar";

export function AdminSidebar({ ...props }) {
    const page = usePage();
    const url = page.url;
    const auth = page.props.auth;

    const user = {
        id: auth.user.id,
        name: auth.user.name,
        email: auth.user.email,
        avatar: "/uploads/media/" + auth.user.profile_picture,
    };

    // ─── BASE PATH CONSTANTS ────────────────────────────────────────
    const dashboardBase = "/dashboard";
    const packagesBase = "/admin/packages";
    const userPackagesBase = "/admin/user_packages";
    const usersBase = "/admin/users";
    const paymentsBase = "/admin/subscription_payments";
    const gatewaysBase = "/admin/payment_gateways";
    const pagesDefaultBase = "/admin/pages/default_pages";
    const pagesCustomBase = "/admin/pages"; // catches /admin/pages and /admin/pages/*

    // ─── DASHBOARD MENU ────────────────────────────────────────────
    const dashboardItems = [
        {
            name: "Dashboard",
            url: route("dashboard.index"),
            icon: PieChart,
            isActive: url.startsWith(dashboardBase),
        },
    ];

    // ─── MAIN NAV ITEMS ─────────────────────────────────────────────
    const navMainItems = [
        {
            title: "Packages",
            url: "#",
            icon: Gift,
            isActive:
                url.startsWith(packagesBase) ||
                url.startsWith(userPackagesBase),
            items: [
                {
                    title: "System Packages",
                    url: route("packages.index"),
                    isActive: url.startsWith(packagesBase),
                },
                {
                    title: "User Packages",
                    url: route("user_packages.index"),
                    isActive: url.startsWith(userPackagesBase),
                },
            ],
        },
        {
            name: "Users",
            url: route("users.index"),
            icon: Users,
            isActive: url.startsWith(usersBase),
        },
        {
            name: "Payments",
            url: route("subscription_payments.index"),
            icon: CreditCard,
            isActive: url.startsWith(paymentsBase),
        },
        {
            name: "Payment Gateways",
            url: route("payment_gateways.index"),
            icon: BadgeDollarSign,
            isActive: url.startsWith(gatewaysBase),
        },
        {
            title: "Website Management",
            url: "#",
            icon: BookOpen,
            isActive:
                url.startsWith(pagesDefaultBase) ||
                url.startsWith(pagesCustomBase),
            items: [
                {
                    title: "Default Pages",
                    url: route("pages.default_pages"),
                    isActive: url.startsWith(pagesDefaultBase),
                },
                {
                    title: "Custom Pages",
                    url: route("pages.index"),
                    isActive:
                        url === pagesCustomBase ||
                        url.startsWith(pagesCustomBase + "/"),
                },
                { title: "Manage FAQ", url: "#" },
                { title: "Manage Features", url: "#" },
                { title: "Manage Testimonials", url: "#" },
                { title: "Blog Posts", url: "#" },
                { title: "Manage Teams", url: "#" },
                { title: "Header & Footer Settings", url: "#" },
                { title: "GDPR Cookie Consent", url: "#" },
            ],
        },
        {
            title: "Settings",
            url: "#",
            icon: Settings2,
            items: [
                { title: "General", url: "#" },
                { title: "Team", url: "#" },
                { title: "Billing", url: "#" },
                { title: "Limits", url: "#" },
            ],
        },
    ];

    // ─── SETTINGS NAV ───────────────────────────────────────────────
    const settingsItems = [
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
                { title: "Business Types", url: "#" },
                { title: "Email Subscribers", url: "#" },
                { title: "Notification Templates", url: "#" },
                { title: "Database Backups", url: "#" },
                { title: "Account Types", url: "#" },
            ],
        },
    ];

    return (
        <Sidebar collapsible="icon" {...props}>
            <SidebarContent>
                <NavAdminDashboard items={dashboardItems} />
                <NavMain items={navMainItems} />
                <NavSettings settings={settingsItems} />
            </SidebarContent>
            <SidebarFooter>
                <NavUser user={user} />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
