import React from 'react';
import { Link } from '@inertiajs/react';

export default function SettingsNavigation({ activeTab, businessId }) {
    const tabs = [
        { id: 'general', label: 'General Settings', icon: '🔧' },
        { id: 'currency', label: 'Currency Settings', icon: '💱' },
        { id: 'credit_invoice', label: 'Credit Invoice Settings', icon: '📃' },
        { id: 'cash_invoice', label: 'Cash Invoice Settings', icon: '💵' },
        { id: 'bill_invoice', label: 'Bill Invoice Settings', icon: '📄' },
        { id: 'sales_return', label: 'Sales Return Settings', icon: '🔄' },
        { id: 'purchase_return', label: 'Purchase Return Settings', icon: '🛒' },
    ];

    return (
        <div className="bg-white shadow mb-6 overflow-x-auto">
            <div className="flex space-x-1 px-2 py-2">
                {tabs.map((tab) => (
                    <Link
                        key={tab.id}
                        href={route('business.settings', [businessId, tab.id])}
                        className={`px-4 py-2 text-sm font-medium rounded-md whitespace-nowrap flex items-center ${
                            activeTab === tab.id
                                ? 'bg-indigo-100 text-indigo-700'
                                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                        }`}
                    >
                        <span className="mr-2">{tab.icon}</span>
                        {tab.label}
                    </Link>
                ))}
            </div>
        </div>
    );
}
import React from 'react';
import { Link } from '@inertiajs/react';

export default function SettingsNavigation({ activeTab, businessId }) {
    const tabs = [
        { id: 'general', label: 'General Settings', icon: '🔧' },
        { id: 'currency', label: 'Currency Settings', icon: '💱' },
        { id: 'credit_invoice', label: 'Credit Invoice Settings', icon: '📃' },
        { id: 'cash_invoice', label: 'Cash Invoice Settings', icon: '💵' },
        { id: 'bill_invoice', label: 'Bill Invoice Settings', icon: '📄' },
        { id: 'sales_return', label: 'Sales Return Settings', icon: '🔄' },
        { id: 'purchase_return', label: 'Purchase Return Settings', icon: '🛒' },
    ];

    return (
        <div className="bg-white shadow mb-6 overflow-x-auto">
            <div className="flex space-x-1 px-2 py-2">
                {tabs.map((tab) => (
                    <Link
                        key={tab.id}
                        href={route('business.settings', [businessId, tab.id])}
                        className={`px-4 py-2 text-sm font-medium rounded-md whitespace-nowrap flex items-center ${
                            activeTab === tab.id
                                ? 'bg-indigo-100 text-indigo-700'
                                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                        }`}
                    >
                        <span className="mr-2">{tab.icon}</span>
                        {tab.label}
                    </Link>
                ))}
            </div>
        </div>
    );
}
