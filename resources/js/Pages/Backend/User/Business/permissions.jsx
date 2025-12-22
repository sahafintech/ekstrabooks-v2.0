import React, { useMemo, useEffect } from "react";
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

    // Show flash messages
    useEffect(() => {
        if (flash.success) {
            toast({
                title: "Success",
                description: flash.success,
            });
        }
        if (flash.error) {
            toast({
                title: "Error",
                description: flash.error,
                variant: "destructive",
            });
        }
    }, [flash, toast]);

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
            <SidebarInset>
                <PageHeader page="Settings" subpage="Permissions" url="business.permissions" />
                <div className="p-6 space-y-6">
                    {permissions.length === 0 ? (
                        <div className="bg-card rounded-lg border border-border shadow-sm p-8">
                            <p className="text-center text-muted-foreground">
                                No permissions found.
                            </p>
                        </div>
                    ) : (
                        permissions.map((group) => (
                            <div key={group.group} className="bg-card rounded-lg border border-border shadow-sm">
                                {/* Header */}
                                <div className="p-4 border-b">
                                    <h2 className="text-lg font-bold capitalize">
                                        {group.group}
                                    </h2>
                                </div>

                                {/* Table */}
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Permission</TableHead>
                                                <TableHead>Assigned Roles</TableHead>
                                                <TableHead>Role Count</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {group.permissions.length === 0 ? (
                                                <TableRow>
                                                    <TableCell colSpan={3} className="text-center py-8 text-muted-foreground">
                                                        No permissions in this group.
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                group.permissions.map((permission) => {
                                                    const permissionName = permission.name;
                                                    const parts = permissionName.split(".");
                                                    const displayName = parts.length > 1 ? parts[1] : permissionName;

                                                    return (
                                                        <TableRow key={permission.id}>
                                                            <TableCell>
                                                                <span className="text-sm">
                                                                    {displayName}
                                                                </span>
                                                            </TableCell>
                                                            <TableCell>
                                                                {permission.roles.length === 0 ? (
                                                                    <span className="text-sm text-muted-foreground">
                                                                        No roles
                                                                    </span>
                                                                ) : (
                                                                    <div className="flex flex-wrap gap-1">
                                                                        {permission.roles.slice(0, 3).map((role) => (
                                                                            <Badge key={role} variant="secondary">
                                                                                {role}
                                                                            </Badge>
                                                                        ))}
                                                                        {permission.roles.length > 3 && (
                                                                            <Badge variant="default">
                                                                                +{permission.roles.length - 3} more
                                                                            </Badge>
                                                                        )}
                                                                    </div>
                                                                )}
                                                            </TableCell>
                                                            <TableCell>
                                                                <span className="text-sm text-muted-foreground">
                                                                    {permission.roles_count} roles
                                                                </span>
                                                            </TableCell>
                                                        </TableRow>
                                                    );
                                                })
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </SidebarInset>

            <Toaster />
        </AuthenticatedLayout>
    );
}

