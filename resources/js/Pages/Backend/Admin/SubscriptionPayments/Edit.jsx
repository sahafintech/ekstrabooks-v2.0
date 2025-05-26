import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

export default function Edit({ users, user_packages, currency, subscriptionpayment }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        user_id: subscriptionpayment.user_id,
        payment_method: subscriptionpayment.payment_method,
        user_package_id: subscriptionpayment.user_package_id,
        order_id: subscriptionpayment.order_id,
        amount: subscriptionpayment.amount,
        status: subscriptionpayment.status,
        _method: "PUT",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("subscription_payments.update", subscriptionpayment.id), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Subscription"
                    subpage="Edit"
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
                                    <SearchableCombobox
                                        options={users.map((user) => ({
                                            id: user.id,
                                            name:
                                                user.email + " - " + user.name,
                                        }))}
                                        value={data.user_id}
                                        onChange={(value) =>
                                            setData("user_id", value)
                                        }
                                        placeholder="Select user"
                                    />
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
                                            <SelectItem value="Bank Transfer">
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
                                    <SearchableCombobox
                                        options={user_packages.map(
                                            (user_package) => ({
                                                id: user_package.id,
                                                name: user_package.name,
                                            })
                                        )}
                                        value={data.user_package_id}
                                        onChange={(value) =>
                                            setData("user_package_id", value)
                                        }
                                        placeholder="Select user package"
                                    />
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
                                Amount ({currency})
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
