import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { Textarea } from "@/Components/ui/textarea";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

export default function Edit({ user }) {
    const { data, setData, patch, processing, errors, reset } = useForm({
        name: user.name || "",
        email: user.email || "",
        password: "",
        status: user.status || 1,
        phone: user.phone || "",
        city: user.city || "",
        state: user.state || "",
        zip: user.zip || "",
        address: user.address || "",
        profile_picture: "",
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route("users.update", user.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("User updated successfully");
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Users" subpage="Edit" url="users.index" />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="name"
                                className="md:col-span-2 col-span-12"
                            >
                                Name
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="name"
                                    type="text"
                                    name="name"
                                    value={data.name}
                                    autoComplete="name"
                                    className="md:w-1/2 w-full"
                                    isFocused={true}
                                    onChange={(e) =>
                                        setData("name", e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.name}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="email"
                                className="md:col-span-2 col-span-12"
                            >
                                Email
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    autoComplete="email"
                                    className="md:w-1/2 w-full"
                                    onChange={(e) =>
                                        setData("email", e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.email}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="password"
                                className="md:col-span-2 col-span-12"
                            >
                                Password
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    autoComplete="password"
                                    className="md:w-1/2 w-full"
                                    onChange={(e) =>
                                        setData("password", e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.password}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="status"
                                className="md:col-span-2 col-span-12"
                            >
                                Status
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <Select
                                        id="status"
                                        name="status"
                                        value={data.status}
                                        onValueChange={(value) =>
                                            setData("status", value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue>
                                            {data.status === "1"
                                                    ? "Active"
                                                    : data.status === "0"
                                                    ? "Inactive"
                                                    : "Select Status"}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">
                                                Active
                                            </SelectItem>
                                            <SelectItem value="0">
                                                Inactive
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <InputError
                                    message={errors.status}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="phone"
                                className="md:col-span-2 col-span-12"
                            >
                                Phone
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="phone"
                                    type="text"
                                    name="phone"
                                    value={data.phone}
                                    autoComplete="phone"
                                    className="md:w-1/2 w-full"
                                    onChange={(e) =>
                                        setData("phone", e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.phone}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <SidebarSeparator className="my-4" />

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="city"
                                className="md:col-span-2 col-span-12"
                            >
                                City
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="city"
                                    type="text"
                                    name="city"
                                    value={data.city}
                                    autoComplete="city"
                                    className="md:w-1/2 w-full"
                                    onChange={(e) =>
                                        setData("city", e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.city}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="state"
                                className="md:col-span-2 col-span-12"
                            >
                                State
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="state"
                                    type="text"
                                    name="state"
                                    value={data.state}
                                    autoComplete="state"
                                    className="md:w-1/2 w-full"
                                    onChange={(e) =>
                                        setData("state", e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.state}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="zip"
                                className="md:col-span-2 col-span-12"
                            >
                                Zip
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="zip"
                                    type="text"
                                    name="zip"
                                    value={data.zip}
                                    autoComplete="zip"
                                    className="md:w-1/2 w-full"
                                    onChange={(e) =>
                                        setData("zip", e.target.value)
                                    }
                                />
                                <InputError
                                    message={errors.zip}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="address"
                                className="md:col-span-2 col-span-12"
                            >
                                Address
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea
                                    id="address"
                                    name="address"
                                    value={data.address}
                                    autoComplete="address"
                                    className="md:w-1/2 w-full"
                                    onChange={(e) =>
                                        setData("address", e.target.value)
                                    }
                                    placeholder="Type your address here."
                                />
                                <InputError
                                    message={errors.address}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="profile_picture"
                                className="md:col-span-2 col-span-12"
                            >
                                Profile Picture
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="profile_picture"
                                    type="file"
                                    name="profile_picture"
                                    autoComplete="profile_picture"
                                    className="md:w-1/2 w-full"
                                    onChange={(e) =>
                                        setData(
                                            "profile_picture",
                                            e.target.files[0]
                                        )
                                    }
                                />
                                <InputError
                                    message={errors.profile_picture}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <SidebarSeparator className="my-4" />

                        <div className="col-span-12 mt-4">
                            <Button type="submit" disabled={processing}>
                                Update User
                            </Button>
                        </div>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
