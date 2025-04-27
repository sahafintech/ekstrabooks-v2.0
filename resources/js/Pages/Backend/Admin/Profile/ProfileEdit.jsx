import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";

export default function Create({ profile }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: profile.name,
        email: profile.email,
        phone: profile.phone,
        city: profile.city,
        zip: profile.zip,
        state: profile.state,
        address: profile.address,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("profile.update"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Profile updated successfully");
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Profile" subpage="Edit" url="profile.index" />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
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
                            <Label htmlFor="email" className="md:col-span-2 col-span-12">
                                Email *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData("email", e.target.value)}
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError message={errors.email} className="text-sm" />
                            </div>
                        </div>

                        {/* phone */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="phone" className="md:col-span-2 col-span-12">
                                Phone
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="phone"
                                    type="text"
                                    value={data.phone}
                                    onChange={(e) => setData("phone", e.target.value)}
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.phone} className="text-sm" />
                            </div>
                        </div>

                        {/* city */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="city" className="md:col-span-2 col-span-12">
                                City
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="city"
                                    type="text"
                                    value={data.city}
                                    onChange={(e) => setData("city", e.target.value)}
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.city} className="text-sm" />
                            </div>
                        </div>

                        {/* state */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="state" className="md:col-span-2 col-span-12">
                                State
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="state"
                                    type="text"
                                    value={data.state}
                                    onChange={(e) => setData("state", e.target.value)}
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.state} className="text-sm" />
                            </div>
                        </div>

                        {/* zip */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="zip" className="md:col-span-2 col-span-12">
                                Zip
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="zip"
                                    type="text"
                                    value={data.zip}
                                    onChange={(e) => setData("zip", e.target.value)}
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.zip} className="text-sm" />
                            </div>
                        </div>

                        {/* address */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="address" className="md:col-span-2 col-span-12">
                                Address
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="address"
                                    type="text"
                                    value={data.address}
                                    onChange={(e) => setData("address", e.target.value)}
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.address} className="text-sm" />
                            </div>
                        </div>

                        {/* profile picture */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="profile_picture" className="md:col-span-2 col-span-12">
                                Profile Picture
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="profile_picture"
                                    type="file"
                                    onChange={(e) => setData("profile_picture", e.target.files[0])}
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.profile_picture} className="text-sm" />
                            </div>
                        </div>
                        <Button type="submit" disabled={processing} className="mt-4">
                            {processing ? "Updating..." : "Update Profile"}
                        </Button>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
