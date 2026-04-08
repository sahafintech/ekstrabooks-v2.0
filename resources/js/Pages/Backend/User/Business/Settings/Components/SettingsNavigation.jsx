import React from "react";
import { Link } from "@inertiajs/react";
import { cn } from "@/lib/utils";

const tabs = [
    { id: "general", label: "General Settings", icon: "⚙️" },
    { id: "currency", label: "Currency", icon: "💰" },
    { id: "invoice", label: "Invoice", icon: "📄" },
    { id: "cash_invoice", label: "Cash Invoice", icon: "💵" },
    { id: "bill_invoice", label: "Bill", icon: "📑" },
    { id: "hospital_purchase", label: "Hospital Purchase", icon: "🏥" },
    { id: "sales_return", label: "Sales Return", icon: "🔄" },
    { id: "purchase_return", label: "Purchase Return", icon: "⬅️" },
    { id: "pos_settings", label: "POS Settings", icon: "🖥️" },
    { id: "payroll", label: "Payroll", icon: "💳" },
    { id: "approvals", label: "Approvals", icon: "✅" },
    { id: "checkers", label: "Checkers", icon: "🕵️" },
];

export default function SettingsNavigation({ activeTab, businessId }) {
    return (
        <div className="mr-8">
            {tabs.map((tab) => (
                <Link
                    key={tab.id}
                    href={route("business.settings", [businessId, tab.id])}
                    className={cn(
                        "w-full text-left px-4 py-3 flex items-center rounded-md transition-colors mb-2",
                        activeTab === tab.id
                            ? "bg-gray-200 text-gray-700 font-medium"
                            : "hover:bg-gray-100 text-gray-700 font-medium"
                    )}
                >
                    <span className="mr-2">{tab.icon}</span>
                    <span className="text-sm md:text-base">{tab.label}</span>
                </Link>
            ))}
        </div>
    );
}

export { tabs };
