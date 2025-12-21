import { AdminSidebar } from "@/Components/admin-sidebar";
import { UserSidebar } from "@/Components/user-sidebar";
import { initSettings } from '@/lib/settings';
import {
    SidebarProvider,
} from "@/Components/ui/sidebar";
import { usePage } from "@inertiajs/react";

export default function AuthenticatedLayout({ children }) {
    const { auth, decimalPlace, decimalSep, thousandSep, baseCurrency, currencyPosition, date_format } = usePage().props;

    initSettings({
        decimalPlace: decimalPlace,
        decimalSep: decimalSep,
        thousandSep: thousandSep,
        baseCurrency: baseCurrency,
        currencyPosition: currencyPosition,
        date_format: date_format,
    });

    return (
        <SidebarProvider>
            {auth.user.user_type === 'admin'
                ? <AdminSidebar />
                : <UserSidebar />
            }

            {children}
        </SidebarProvider>
    );
}
