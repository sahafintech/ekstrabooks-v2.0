import { AdminSidebar } from "@/components/admin-sidebar";
import { UserSidebar } from "@/components/user-sidebar";
import {
    SidebarProvider,
} from "@/components/ui/sidebar";
import { usePage } from "@inertiajs/react";

export default function AuthenticatedLayout({ children }) {
    const { props } = usePage();
    const { user } = props.auth;

    return (
        <SidebarProvider>
            {user.user_type === 'admin' ? 
                <AdminSidebar /> : 
                <UserSidebar />
            }
            {children}
        </SidebarProvider>
    );
}
