import { useForm, usePage } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import { Head } from "@inertiajs/react";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import { useEffect } from "react";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

export default function Create({ roles, businesses, businessId }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const { data, setData, post, processing, errors } = useForm({
        email: "",
        role_id: "",
        business_id: [],
        message: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("system_users.send_invitation"), {
            preserveScroll: true,
        });
    };

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

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <PageHeader
                    page="Users"
                    subpage="Invite New"
                    url="business.users"
                    params={businessId}
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit} encType="multipart/form-data">
                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="email"
                                className="md:col-span-2 col-span-12"
                            >
                                Email *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData("email", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                    placeholder="Enter email"
                                />
                                <InputError
                                    message={errors.email}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="role_id"
                                className="md:col-span-2 col-span-12"
                            >
                                Role *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <SearchableCombobox
                                    id="role_id"
                                    label="Role"
                                    options={[
                                        { id: "admin", name: "Admin" },
                                        ...roles.map((role) => ({
                                            id: role.id,
                                            name: role.name,
                                        })),
                                    ]}
                                    value={data.role_id}
                                    onChange={(value) =>
                                        setData("role_id", value)
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                    placeholder="Select Role"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="business_id"
                                className="md:col-span-2 col-span-12"
                            >
                                Business *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <SearchableMultiSelectCombobox
                                    id="business_id"
                                    label="Business"
                                    options={businesses.map((business) => ({
                                        id: business.id,
                                        name: business.name,
                                    }))}
                                    value={data.business_id}
                                    onChange={(value) =>
                                        setData("business_id", value)
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                    placeholder="Select Business"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="message"
                                className="md:col-span-2 col-span-12"
                            >
                                Message
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea
                                    id="message"
                                    value={data.message}
                                    onChange={(e) =>
                                        setData("message", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                    placeholder="Enter message"
                                />
                                <InputError
                                    message={errors.message}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-4">
                            <div className="md:col-span-2 col-span-12"></div>
                            <div className="md:col-span-10 col-span-12">
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? "Sending..."
                                        : "Send Invitation"}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
