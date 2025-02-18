import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PageHeader from "@/Components/PageHeader";
import {
    SidebarInset,
} from "@/components/ui/sidebar";

const VideoPlaceholder = () => (
    <div className="aspect-video rounded-xl bg-muted/50" />
);

const DashboardContent = () => (
    <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
        <div className="grid auto-rows-min gap-4 md:grid-cols-3">
            <VideoPlaceholder />
            <VideoPlaceholder />
            <VideoPlaceholder />
        </div>
        <div className="min-h-[100vh] flex-1 rounded-xl bg-muted/50 md:min-h-min" />
    </div>
);

export default function Dashboard() {
    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Dashboard" subpage="Dashboard" url="dashboard.index" />
                <DashboardContent />
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
