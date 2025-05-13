import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";

export default function Create({ users, user_packages, currency_symbol }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        user_id: "",
        payment_method: "Cash",
        user_package_id: "",
        order_id: "",
        amount: "",
        status: "1",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("subscription_payments.store"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Payment created successfully");
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Subscription"
                    subpage="Create"
                    url="subscription_payments.index"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="user_id"
                                className="md:col-span-2 col-span-12"
                            >
                                User
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <Select
                                        id="user_id"
                                        name="user_id"
                                        value={data.user_id}
                                        onValueChange={(value) =>
                                            setData("user_id", value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue>
                                                {data.user_id
                                                    ? data.user_id
                                                    : "Select One"}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            {users.map((user) => (
                                                <SelectItem
                                                    key={user.id}
                                                    value={user.id}
                                                >
                                                    {user.email} - {user.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <InputError
                                    message={errors.user_id}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="payment_method"
                                className="md:col-span-2 col-span-12"
                            >
                                Payment Method
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <Select
                                        id="payment_method"
                                        name="payment_method"
                                        value={data.payment_method}
                                        onValueChange={(value) =>
                                            setData("payment_method", value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue>
                                                {data.payment_method}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Cash">
                                                Cash
                                            </SelectItem>
                                            <SelectItem value="Cheque">
                                                Cheque
                                            </SelectItem>
                                            <SelectItem value="Bank_Transfer">
                                                Bank Transfer
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <InputError
                                    message={errors.payment_method}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="user_package_id"
                                className="md:col-span-2 col-span-12"
                            >
                                User Package
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <Select
                                        id="user_package_id"
                                        name="user_package_id"
                                        value={data.user_package_id}
                                        onValueChange={(value) =>
                                            setData("user_package_id", value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue>
                                                {data.user_package_id
                                                    ? data.user_package_id
                                                    : "Select One"}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            {user_packages.map(
                                                (user_package) => (
                                                    <SelectItem
                                                        key={user_package.id}
                                                        value={user_package.id}
                                                    >
                                                        {user_package.name} -{" "}
                                                        {user_package.cost} -{" "}
                                                        {
                                                            user_package.package_type
                                                        }
                                                    </SelectItem>
                                                )
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <InputError
                                    message={errors.user_package_id}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="order_id"
                                className="md:col-span-2 col-span-12"
                            >
                                Order ID
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="order_id"
                                    type="text"
                                    name="order_id"
                                    value={data.order_id}
                                    onChange={(e) =>
                                        setData("order_id", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError
                                    message={errors.order_id}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="amount"
                                className="md:col-span-2 col-span-12"
                            >
                                Amount ({currency_symbol})
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="amount"
                                    type="text"
                                    name="amount"
                                    value={data.amount}
                                    onChange={(e) =>
                                        setData("amount", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError
                                    message={errors.amount}
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
                                                    ? "Completed"
                                                    : "Pending"}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">
                                                Completed
                                            </SelectItem>
                                            <SelectItem value="0">
                                                Pending
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

                        <div className="col-span-12 mt-4">
                            <Button type="submit" disabled={processing}>
                                Submit
                            </Button>
                        </div>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
