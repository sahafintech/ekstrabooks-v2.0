import { useMemo, useEffect } from "react";
import { usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Badge } from "@/Components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";

export default function Permissions({ permissions = [], roles = [] }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();

    useEffect(() => {
        if (flash && flash.success) {
            toast({
                title: "Success",
                description: flash.success,
            });
        }

        if (flash && flash.error) {
            toast({
                variant: "destructive",
                title: "Error",
                description: flash.error,
            });
        }
    }, [flash, toast]);

    // Flatten permissions for display if needed
    const allPermissions = useMemo(() => {
        return permissions.flatMap(group =>
            group.permissions.map(perm => ({
                ...perm,
                group: group.group,
            }))
        );
    }, [permissions]);

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Settings"
                        subpage="Permissions"
                        url="settings.permissions"
                    />
                    <div className="p-4">
                        <div className="space-y-6">
                            {permissions.map((group) => (
                                <div key={group.group} className="border rounded-lg p-6">
                                    <h2 className="text-lg font-bold mb-4 capitalize">
                                        {group.group}
                                    </h2>
                                    <div className="rounded-md border">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Permission</TableHead>
                                                    <TableHead>Assigned Roles</TableHead>
                                                    <TableHead>Role Count</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {group.permissions.map((perm) => {
                                                    const parts = perm.name.split(".");
                                                    const displayName = parts.length > 1 ? parts.slice(1).join(".") : perm.name;
                                                    
                                                    return (
                                                        <TableRow key={perm.id}>
                                                            <TableCell>
                                                                <span className="font-medium">{displayName}</span>
                                                            </TableCell>
                                                            <TableCell>
                                                                {perm.roles.length === 0 ? (
                                                                    <span className="text-muted-foreground">No roles</span>
                                                                ) : (
                                                                    <div className="flex flex-wrap gap-1">
                                                                        {perm.roles.slice(0, 3).map((role) => (
                                                                            <Badge key={role} variant="secondary">
                                                                                {role}
                                                                            </Badge>
                                                                        ))}
                                                                        {perm.roles.length > 3 && (
                                                                            <Badge variant="outline">
                                                                                +{perm.roles.length - 3} more
                                                                            </Badge>
                                                                        )}
                                                                    </div>
                                                                )}
                                                            </TableCell>
                                                            <TableCell>
                                                                <span className="text-muted-foreground">
                                                                    {perm.roles_count} roles
                                                                </span>
                                                            </TableCell>
                                                        </TableRow>
                                                    );
                                                })}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </div>
                            ))}

                            {permissions.length === 0 && (
                                <div className="text-center py-12 text-muted-foreground">
                                    No permissions defined yet.
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
