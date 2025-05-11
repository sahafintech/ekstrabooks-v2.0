import { useState, useEffect } from "react";
import { Head, router } from "@inertiajs/react";
import PageHeader from "@/Components/PageHeader";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import { Checkbox } from "@/Components/ui/checkbox";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import {
    ReportTable,
    TableHeader,
    TableBody,
    TableRow,
    TableHead,
    TableCell,
} from "@/Components/shared/ReportTable";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import { usePage } from "@inertiajs/react";

export default function Create({
    permissions,
    role_id,
    permission_list,
    roles,
}) {
    const [selectedRole, setSelectedRole] = useState(role_id || "");
    const [moduleStates, setModuleStates] = useState({});
    const [permissionStates, setPermissionStates] = useState({});
    const { toast } = useToast();
    const { flash } = usePage().props;

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

    useEffect(() => {
        // Initialize permission states from permission_list
        const initialStates = {};
        Object.entries(permissions).forEach(([key, val], moduleIndex) => {
            Object.entries(val).forEach(([name]) => {
                initialStates[name] = permission_list.includes(name);
            });
        });
        setPermissionStates(initialStates);
    }, [permissions, permission_list]);

    const handleSubmit = (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append("role_id", selectedRole);

        // Add all checked permissions to formData
        Object.entries(permissionStates).forEach(([name, checked]) => {
            if (checked) {
                formData.append("permissions[]", name);
            }
        });

        router.post(route("permission.store"), formData);
    };

    const checkAll = (checked, moduleId) => {
        const modulePermissions = Object.entries(permissions)[moduleId][1];
        const newPermissionStates = { ...permissionStates };

        Object.keys(modulePermissions).forEach((permissionName) => {
            newPermissionStates[permissionName] = checked;
        });

        setPermissionStates(newPermissionStates);
        setModuleStates((prev) => ({
            ...prev,
            [moduleId]: checked,
        }));
    };

    const handlePermissionChange = (permissionName, moduleId) => {
        const newPermissionStates = {
            ...permissionStates,
            [permissionName]: !permissionStates[permissionName],
        };
        setPermissionStates(newPermissionStates);

        // Check if all permissions in the module are checked
        const modulePermissions = Object.entries(permissions)[moduleId][1];
        const allChecked = Object.keys(modulePermissions).every(
            (name) => newPermissionStates[name]
        );

        setModuleStates((prev) => ({
            ...prev,
            [moduleId]: allChecked,
        }));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Create Role & Permissions" />
            <Toaster />
            <SidebarInset>
                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <PageHeader
                        page="Roles"
                        subpage="Permission"
                        url="roles.index"
                    />

                    <form
                        method="post"
                        id="permissions"
                        className="validate"
                        autoComplete="off"
                        onSubmit={handleSubmit}
                    >
                        <div className="grid grid-cols-12">
                            <div className="col-span-12">
                                <div className="flex items-center my-2">
                                    <h5>Access Control</h5>
                                </div>
                                <div className="box-body">
                                    <div className="col-span-12">
                                        <Label value="Select Role" />
                                        <SearchableCombobox
                                            options={roles.map((role) => ({
                                                id: role.id,
                                                name: role.name,
                                            }))}
                                            value={selectedRole}
                                            onChange={(e) =>
                                                setSelectedRole(e.target.value)
                                            }
                                            className="xl:w-1/2 w-full"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {selectedRole && (
                            <div className="grid grid-cols-12">
                                <div className="col-span-12 mt-3">
                                    <ReportTable>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Module</TableHead>
                                                <TableHead>
                                                    Permissions
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {Object.entries(permissions).map(
                                                ([key, val], index) => {
                                                    const string =
                                                        key.split("\\")[4] ||
                                                        key.split("\\")[3];
                                                    const moduleName = string
                                                        .replace(
                                                            "Controller",
                                                            ""
                                                        )
                                                        .split(/(?=[A-Z])/)
                                                        .join(" ");

                                                    return (
                                                        <TableRow key={index}>
                                                            <TableCell>
                                                                <h5>
                                                                    <div className="flex items-center gap-2">
                                                                        <Checkbox
                                                                            id={`customCheckAll${index}`}
                                                                            checked={
                                                                                moduleStates[
                                                                                    index
                                                                                ] ||
                                                                                false
                                                                            }
                                                                            onCheckedChange={(
                                                                                checked
                                                                            ) =>
                                                                                checkAll(
                                                                                    checked,
                                                                                    index
                                                                                )
                                                                            }
                                                                        />
                                                                        <label
                                                                            className="custom-control-label"
                                                                            htmlFor={`customCheckAll${index}`}
                                                                        >
                                                                            {
                                                                                moduleName
                                                                            }
                                                                        </label>
                                                                    </div>
                                                                </h5>
                                                            </TableCell>
                                                            <TableCell>
                                                                {Object.entries(
                                                                    val
                                                                ).map(
                                                                    (
                                                                        [
                                                                            name,
                                                                            url,
                                                                        ],
                                                                        subIndex
                                                                    ) => {
                                                                        const display =
                                                                            name
                                                                                .replace(
                                                                                    "index",
                                                                                    "list"
                                                                                )
                                                                                .split(
                                                                                    "."
                                                                                )
                                                                                .reverse()[0]
                                                                                .toUpperCase()
                                                                                .replace(
                                                                                    "_",
                                                                                    " "
                                                                                );

                                                                        return (
                                                                            <div
                                                                                key={
                                                                                    subIndex
                                                                                }
                                                                                className="custom-control custom-checkbox permission-checkbox"
                                                                            >
                                                                                <Checkbox
                                                                                    id={`customCheck${index}${subIndex}`}
                                                                                    checked={
                                                                                        permissionStates[
                                                                                            name
                                                                                        ] ||
                                                                                        false
                                                                                    }
                                                                                    onCheckedChange={() =>
                                                                                        handlePermissionChange(
                                                                                            name,
                                                                                            index
                                                                                        )
                                                                                    }
                                                                                />
                                                                                <label
                                                                                    className="custom-control-label"
                                                                                    htmlFor={`customCheck${index}${subIndex}`}
                                                                                >
                                                                                    {
                                                                                        display
                                                                                    }
                                                                                </label>
                                                                            </div>
                                                                        );
                                                                    }
                                                                )}
                                                            </TableCell>
                                                        </TableRow>
                                                    );
                                                }
                                            )}
                                        </TableBody>
                                    </ReportTable>
                                </div>

                                <div className="col-span-12 mt-4">
                                    <Button type="submit" variant="default">
                                        Save Permission
                                    </Button>
                                </div>
                            </div>
                        )}
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
