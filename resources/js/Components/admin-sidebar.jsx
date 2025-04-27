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

    const navMainItems = [
        {
            title: "Packages",
            url: "#",
            icon: Gift,
            isActive: isRoute("packages.index") || isRoute("user_packages.index"),
            items: [
                {
                    title: "System Packages",
                    url: route("packages.index"),
                },
                {
                    title: "User Packages",
                    url: route("user_packages.index"),
                },
            ],
        },
        {
            name: "Users",
            url: route("users.index"),
            icon: Users,
            isActive: isRoute("users.index"),
        },
        {
            name: "Payments",
            url: route("subscription_payments.index"),
            icon: CreditCard,
            isActive: isRoute("subscription_payments.index"),
        },
        {
            name: "Payment Gateways",
            url: route("payment_gateways.index"),
            icon: BadgeDollarSign,
            isActive: isRoute("payment_gateways.index"),
        },
        {
            title: "Website Management",
            url: "#",
            icon: BookOpen,
            isActive: isRoute("pages.default_pages") || isRoute("pages.index"),
            items: [
                {
                    title: "Default Pages",
                    url: route("pages.default_pages"),
                },
                {
                    title: "Custom Pages",
                    url: route("pages.index"),
                },
                {
                    title: "Manage FAQ",
                    url: "#",
                },
                {
                    title: "Manage Features",
                    url: "#",
                },
                {
                    title: "Manage Testimonials",
                    url: "#",
                },
                {
                    title: "Blog Posts",
                    url: "#",
                },
                {
                    title: "Manage Teams",
                    url: "#",
                },
                {
                    title: "Header & Footer Settings",
                    url: "#",
                },
                {
                    title: "GDPR Cookie Consent",
                    url: "#",
                },
            ],
        },
        {
            title: "Settings",
            url: "#",
            icon: Settings2,
            items: [
                {
                    title: "General",
                    url: "#",
                },
                {
                    title: "Team",
                    url: "#",
                },
                {
                    title: "Billing",
                    url: "#",
                },
                {
                    title: "Limits",
                    url: "#",
                },
            ],
        },
    ];

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
            <SidebarContent>
                <NavAdminDashboard items={dashboardItems} />
                <NavMain items={navMainItems} />
                <NavSettings settings={settingsItems} />
            </SidebarContent>
            <SidebarFooter>
                <NavUser user={data.user} />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
