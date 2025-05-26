import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { Head } from "@inertiajs/react";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";

export default function Edit({ currency }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: currency.name,
        description: currency.description,
        exchange_rate: currency.exchange_rate,
        base_currency: currency.base_currency.toString(),
        status: currency.status.toString(),
        _method: "PUT",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("currency.update", currency.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Currency updated successfully");
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Currency" subpage="Edit" url="currency.index" />

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

                        <div className="grid grid-cols-12 mt-4">
                            <Label htmlFor="description" className="md:col-span-2 col-span-12">
                                Description *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="description"
                                    type="text"
                                    value={data.description}
                                    onChange={(e) => setData("description", e.target.value)}
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError message={errors.description} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-4">
                            <Label htmlFor="exchange_rate" className="md:col-span-2 col-span-12">
                                Exchange Rate *
                            </Label>
                            <div className="md:col-span-10 col-span-12">
                                <Input
                                    id="exchange_rate"
                                    type="number"
                                    value={data.exchange_rate}
                                    onChange={(e) => setData("exchange_rate", e.target.value)}
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError message={errors.exchange_rate} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-4">
                            <Label htmlFor="base_currency" className="md:col-span-2 col-span-12">
                                Base Currency *
                            </Label>
                            <div className="md:col-span-10 col-span-12">
                                <div className="md:w-1/2 w-full">
                                    <Select
                                        value={data.base_currency}
                                        onValueChange={(value) => setData("base_currency", value)}
                                        className="md:w-1/2 w-full"
                                    >
                                        <SelectTrigger id="base_currency" className="w-full">
                                            <SelectValue value={data.base_currency} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">Yes</SelectItem>
                                            <SelectItem value="0">No</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <InputError message={errors.base_currency} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-4">
                            <Label htmlFor="status" className="md:col-span-2 col-span-12">
                                Status *
                            </Label>
                            <div className="md:col-span-10 col-span-12">
                                <div className="md:w-1/2 w-full">
                                    <Select
                                        value={data.status}
                                        onValueChange={(value) => setData("status", value)}
                                        className="md:w-1/2 w-full"
                                    >
                                        <SelectTrigger id="status" className="w-full">
                                            <SelectValue value={data.status} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">Active</SelectItem>
                                            <SelectItem value="0">Disabled</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <InputError message={errors.status} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-4">
                            <div className="md:col-span-2 col-span-12"></div>
                            <div className="md:col-span-10 col-span-12">
                                <Button type="submit" disabled={processing}>
                                    {processing ? "Updating..." : "Update Currency"}
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </SidebarInset >
        </AuthenticatedLayout >
    );
}
