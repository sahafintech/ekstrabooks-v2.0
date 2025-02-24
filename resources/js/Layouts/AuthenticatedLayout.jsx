import { AdminSidebar } from "@/components/admin-sidebar";
import { UserSidebar } from "@/components/user-sidebar";
import {
    SidebarProvider,
} from "@/components/ui/sidebar";
import { usePage } from "@inertiajs/react";

export default function AuthenticatedLayout({ header, children, businesses, active_business }) {
    const { props } = usePage();
    const user = props.auth.user;

    return (
        <SidebarProvider>
            {user.user_type == 'admin' ? <AdminSidebar /> : <UserSidebar businesses={businesses} active_business={active_business} />}
            {children}
        </SidebarProvider>
    );
}
