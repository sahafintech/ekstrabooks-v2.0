import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Textarea } from "@/Components/ui/textarea";
import { parseDateObject } from "@/lib/utils";
import { useState, useEffect } from "react";
import DatePicker from "@/Components/DatePicker";
import DateTimePicker from "@/Components/DateTimePicker";

export default function Edit({
    account,
    openingBalance,
    currencies = [],
    accountTypes = [],
}) {
    const [showCurrency, setShowCurrency] = useState(false);

    const { data, setData, put, processing, errors } = useForm({
        account_code: account.account_code,
        account_name: account.account_name,
        account_type: account.account_type,
        opening_date: parseDateObject(account.opening_date),
        account_number: account.account_number,
        currency: account.currency,
        description: account.description,
        opening_balance: openingBalance,
    });

    // Handle account type change to determine if currency should be shown
    useEffect(() => {
        const isBankOrCash =
            data.account_type === "Bank" || data.account_type === "Cash";
        setShowCurrency(isBankOrCash);

        // Reset currency if not bank or cash
        if (!isBankOrCash) {
            setData("currency", "");
        }
    }, [data.account_type]);

    const submit = (e) => {
        e.preventDefault();
        put(route("accounts.update", account.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Account updated successfully");
            },
        });
    };

    const isAssetType = (type) => {
        return ["Bank", "Cash", "Other Current Asset", "Fixed Asset"].includes(
            type
        );
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Chart of Accounts"
                    subpage="Edit Account"
                    url="accounts.index"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="account_code"
                                className="md:col-span-2 col-span-12"
                            >
                                Account Code *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="account_code"
                                    type="text"
                                    value={data.account_code}
                                    onChange={(e) =>
                                        setData("account_code", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.account_code}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="account_name"
                                className="md:col-span-2 col-span-12"
                            >
                                Account Name *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="account_name"
                                    type="text"
                                    value={data.account_name}
                                    onChange={(e) =>
                                        setData("account_name", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.account_name}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="account_type"
                                className="md:col-span-2 col-span-12"
                            >
                                Account Type *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={accountTypes.map(
                                            (accountType) => ({
                                                id: accountType.type,
                                                name: accountType.type,
                                            })
                                        )}
                                        value={data.account_type}
                                        onChange={(value) =>
                                            setData("account_type", value)
                                        }
                                        placeholder="Select account type"
                                    />
                                </div>
                                <InputError
                                    message={errors.account_type}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        {showCurrency && (
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="currency"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Currency
                                </Label>
                                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                    <div className="md:w-1/2 w-full">
                                        <SearchableCombobox
                                            className="mt-1"
                                            options={currencies.map((currency) => ({
                                                id: currency.name,
                                                value: currency.name,
                                                label: currency.name,
                                                name: `${currency.name} - ${currency.description} (${currency.exchange_rate})`,
                                            }))}
                                            value={data.currency}
                                            onChange={(selectedValue) => {
                                                setData("currency", selectedValue);
                                            }}
                                            placeholder="Select currency"
                                        />
                                    </div>
                                    <InputError
                                        message={errors.currency}
                                        className="text-sm"
                                    />
                                </div>
                            </div>
                        )}

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="opening_date"
                                className="md:col-span-2 col-span-12"
                            >
                                Opening Date *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker
                                    value={data.opening_date}
                                    onChange={(date) =>
                                        setData("opening_date", date)
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError
                                    message={errors.opening_date}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="account_number"
                                className="md:col-span-2 col-span-12"
                            >
                                Account Number
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="account_number"
                                    type="text"
                                    value={data.account_number}
                                    onChange={(e) =>
                                        setData(
                                            "account_number",
                                            e.target.value
                                        )
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError
                                    message={errors.account_number}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        {isAssetType(data.account_type) && (
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="opening_balance"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Opening Balance
                                </Label>
                                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                    <Input
                                        id="opening_balance"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.opening_balance}
                                        onChange={(e) =>
                                            setData(
                                                "opening_balance",
                                                e.target.value
                                            )
                                        }
                                        className="md:w-1/2 w-full"
                                    />
                                    <InputError
                                        message={errors.opening_balance}
                                        className="text-sm"
                                    />
                                </div>
                            </div>
                        )}

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="description"
                                className="md:col-span-2 col-span-12"
                            >
                                Description
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) =>
                                        setData("description", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                    rows={3}
                                />
                                <InputError
                                    message={errors.description}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <SidebarSeparator className="my-4" />

                        <Button type="submit" disabled={processing}>
                            Update Account
                        </Button>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
