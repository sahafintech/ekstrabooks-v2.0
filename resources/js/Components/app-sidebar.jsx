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

import { NavMain } from "@/components/nav-main";
import { NavSettings } from "@/components/nav-settings";
import { NavUser } from "@/components/nav-user";
import { TeamSwitcher } from "@/components/team-switcher";
import { NavDashboard } from "@/components/nav-dashboard";
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
    teams: [
        {
            name: "Acme Inc",
            logo: GalleryVerticalEnd,
            plan: "Enterprise",
        },
        {
            name: "Acme Corp.",
            logo: AudioWaveform,
            plan: "Startup",
        },
        {
            name: "Evil Corp.",
            logo: Command,
            plan: "Free",
        },
    ],
    dashboard: [
        {
            name: "Dashboard",
            url: route("dashboard.index"),
            icon: PieChart,
            isActive: route().current("dashboard.index"),
        },
    ],
    navMain: [
        {
            title: "Packages",
            url: "#",
            icon: Gift,
            isActive:
                route().current("user_packages.index") ||
                route().current("packages.index"),
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
            isActive: route().current("users.index"),
        },
        {
            name: "Payments",
            url: route("subscription_payments.index"),
            icon: CreditCard,
            isActive: route().current("subscription_payments.index"),
        },
        {
            name: "Payment Gateways",
            url: "#",
            icon: BadgeDollarSign,
        },
        {
            title: "Website Management",
            url: "#",
            icon: BookOpen,
            items: [
                {
                    title: "Default Pages",
                    url: "#",
                },
                {
                    title: "Custom Pages",
                    url: "#",
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
    ],
    settings: [
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
    ],
};

export function AppSidebar({ ...props }) {
    return (
        <Sidebar collapsible="icon" {...props}>
            <SidebarHeader>
                <TeamSwitcher teams={data.teams} />
            </SidebarHeader>
            <SidebarContent>
                <NavDashboard items={data.dashboard} />
                <NavMain items={data.navMain} />
                <NavSettings settings={data.settings} />
            </SidebarContent>
            <SidebarFooter>
                <NavUser user={data.user} />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
