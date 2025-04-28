import { AdminSidebar } from "@/components/admin-sidebar";
import { UserSidebar } from "@/components/user-sidebar";
import { StaffSidebar } from "@/components/staff-sidebar";
import {
    SidebarProvider,
} from "@/components/ui/sidebar";
import { usePage } from "@inertiajs/react";

export default function AuthenticatedLayout({ children }) {
    const { auth, isOwner } = usePage().props;

    return (
        <SidebarProvider>
            {
                auth.user.user_type === 'admin'
                    ? <AdminSidebar />
                    : auth.user.user_type === 'user' && isOwner
                        ? <UserSidebar />
                        : <StaffSidebar />
            }

            {children}
        </SidebarProvider>
    );
}
