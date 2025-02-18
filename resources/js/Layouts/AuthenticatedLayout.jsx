import { AppSidebar } from "@/components/app-sidebar";
import {
    SidebarProvider,
} from "@/components/ui/sidebar";

export default function AuthenticatedLayout({ header, children }) {
    return (
        <SidebarProvider>
            <AppSidebar />
            {children}
        </SidebarProvider>
    );
}
