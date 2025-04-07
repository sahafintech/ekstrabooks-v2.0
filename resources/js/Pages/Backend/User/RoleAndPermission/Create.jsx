import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { Head } from "@inertiajs/react";

export default function Create() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        description: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("roles.store"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Role created successfully");
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Add New Role" />
            <SidebarInset>
                <PageHeader page="Roles" subpage="Add New" url="roles.index" />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit} encType="multipart/form-data">
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="name" className="md:col-span-2 col-span-12">
                                Name *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData("name", e.target.value)}
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError message={errors.name} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="description" className="md:col-span-2 col-span-12">
                                Description
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData("description", e.target.value)}
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.description} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-4">
                            <div className="md:col-span-2 col-span-12"></div>
                            <div className="md:col-span-10 col-span-12">
                                <Button type="submit" disabled={processing}>
                                    {processing ? "Creating..." : "Create Role"}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
