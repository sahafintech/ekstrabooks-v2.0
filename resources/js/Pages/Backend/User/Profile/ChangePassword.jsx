import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { usePage } from "@inertiajs/react";
import { useToast } from "@/hooks/use-toast";
import { useEffect } from "react";

export default function Create({ profile }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const { auth } = usePage().props;
    const { data, setData, post, processing, errors, reset } = useForm({
        old_password: "",
        password: "",
        password_confirmation: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("profile.update_password"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Password updated successfully");
                reset();
            },
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
            <SidebarInset>
                <PageHeader
                    page="Profile"
                    subpage="Change Password"
                    url="profile.index"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        {auth.has_password ? (
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="old_password"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Current Password *
                                </Label>
                                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                    <Input
                                        id="old_password"
                                        type="password"
                                        value={data.old_password}
                                        onChange={(e) =>
                                            setData(
                                                "old_password",
                                                e.target.value
                                            )
                                        }
                                        className={`md:w-1/2 w-full ${errors.old_password ? "border-red-500" : ""}`}
                                    />
                                    <InputError
                                        message={errors.old_password}
                                        className="mt-1"
                                    />
                                </div>
                            </div>
                        ) : null}
                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="password"
                                className="md:col-span-2 col-span-12"
                            >
                                New Password *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData("password", e.target.value)
                                    }
                                    className={`md:w-1/2 w-full ${errors.password ? "border-red-500" : ""}`}
                                    required
                                />
                                <InputError
                                    message={errors.password}
                                    className="mt-1"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="password_confirmation"
                                className="md:col-span-2 col-span-12"
                            >
                                Confirm Password *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) =>
                                        setData(
                                            "password_confirmation",
                                            e.target.value
                                        )
                                    }
                                    className={`md:w-1/2 w-full ${errors.password_confirmation ? "border-red-500" : ""}`}
                                    required
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                    className="mt-1"
                                />
                            </div>
                        </div>

                        <Button
                            type="submit"
                            disabled={processing}
                            className="mt-4"
                        >
                            {processing ? "Updating..." : "Update Password"}
                        </Button>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
