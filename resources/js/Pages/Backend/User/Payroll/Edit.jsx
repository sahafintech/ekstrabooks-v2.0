import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { useEffect, useState } from "react";
import { Input } from "@/Components/ui/input";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";
import { CalendarIcon, Plus, Trash2 } from "lucide-react";
import { cn, formatCurrency } from "@/lib/utils";
import { Calendar } from "@/Components/ui/calendar";
import { Textarea } from "@/Components/ui/textarea";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { format } from "date-fns";
import DateTimePicker from "@/Components/DateTimePicker";

export default function Edit({
    payroll,
    employee_benefits,
    accounts,
    decimalPlace,
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        advance: payroll.advance || "",
        advance_description: payroll.advance_description || "",
        deductions: [],
        allowances: [],
        _method: "PUT",
    });

    const [allowanceItems, setAllowanceItems] = useState([]);
    const [deductionItems, setDeductionItems] = useState([]);
    const [netSalary, setNetSalary] = useState(0);

    const addAllowance = () => {
        const newItem = {
            date: "",
            description: "",
            amount: 0,
        };

        const updatedItems = [...allowanceItems, newItem];
        setAllowanceItems(updatedItems);
        setData("allowances", updatedItems);
    };

    const addDeduction = () => {
        const newItem = {
            date: "",
            description: "",
            amount: 0,
            account_id: "",
        };

        const updatedItems = [...deductionItems, newItem];
        setDeductionItems(updatedItems);
        setData("deductions", updatedItems);
    };

    const submit = (e) => {
        e.preventDefault();
        post(route("payslips.update", payroll.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Payroll updated successfully");
                reset();
            },
        });
    };

    const removeAllowanceItem = (index) => {
        const updatedItems = allowanceItems.filter((_, i) => i !== index);
        setAllowanceItems(updatedItems);
        setData("allowances", updatedItems);
    };

    const removeDeductionItem = (index) => {
        const updatedItems = deductionItems.filter((_, i) => i !== index);
        setDeductionItems(updatedItems);
        setData("deductions", updatedItems);
    };

    useEffect(() => {
        const allowances =
            employee_benefits?.salary_benefits?.filter(
                (benefit) => benefit.type === "add"
            ) ?? [];
        const deductions =
            employee_benefits?.salary_benefits?.filter(
                (benefit) => benefit.type === "deduct"
            ) ?? [];

        const formattedAllowances = allowances.map((allowance) => ({
            date: allowance.date,
            description: allowance.description,
            amount: parseFloat(allowance.amount) || 0,
            account_id: allowance.account_id,
        }));

        const formattedDeductions = deductions.map((deduction) => ({
            date: deduction.date,
            description: deduction.description,
            amount: parseFloat(deduction.amount) || 0,
            account_id: deduction.account_id,
        }));

        setAllowanceItems(
            formattedAllowances.length > 0
                ? formattedAllowances
                : [
                      {
                          date: "",
                          description: "",
                          amount: 0,
                      },
                  ]
        );

        setDeductionItems(
            formattedDeductions.length > 0
                ? formattedDeductions
                : [
                      {
                          date: "",
                          description: "",
                          amount: 0,
                          account_id: "",
                      },
                  ]
        );

        setData((prev) => ({
            ...prev,
            deductions: formattedDeductions,
            allowances: formattedAllowances,
            advance: payroll.advance || "",
            advance_description: payroll.advance_description || "",
        }));

        setNetSalary(payroll.net_salary || 0);
    }, [payroll, employee_benefits]);

    useEffect(() => {
        const currentSalary = parseFloat(payroll.current_salary) || 0;

        const totalAllowances = Array.isArray(data.allowances)
            ? data.allowances.reduce(
                  (sum, item) => sum + (parseFloat(item.amount) || 0),
                  0
              )
            : 0;

        const totalDeductions = Array.isArray(data.deductions)
            ? data.deductions.reduce(
                  (sum, item) => sum + (parseFloat(item.amount) || 0),
                  0
              )
            : 0;

        const advance = parseFloat(data.advance) || 0;

        const calculatedNetSalary =
            currentSalary + totalAllowances - totalDeductions - advance;
        setNetSalary(calculatedNetSalary);
    }, [
        data.allowances,
        data.deductions,
        data.advance,
        payroll.current_salary,
    ]);

    const updateAllowance = (index, field, value) => {
        const updatedItems = [...allowanceItems];

        if (field === "amount") {
            updatedItems[index][field] =
                value === "" ? "" : parseFloat(value) || 0;
        } else {
            updatedItems[index][field] = value;
        }

        setAllowanceItems(updatedItems);
        setData("allowances", updatedItems);
    };

    const updateDeduction = (index, field, value) => {
        const updatedItems = [...deductionItems];

        if (field === "amount") {
            updatedItems[index][field] =
                value === "" ? "" : parseFloat(value) || 0;
        } else {
            updatedItems[index][field] = value;
        }

        setDeductionItems(updatedItems);
        setData("deductions", updatedItems);
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Payrolls"
                    subpage="Edit"
                    url="payslips.index"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <div className="flex flex-col gap-1">
                        <div className="text-sm font-medium text-muted-foreground">
                            Employee Id:{" "}
                            <span className="ml-2 text-primary">
                                {payroll?.staff?.employee_id}
                            </span>
                        </div>
                        <div className="text-sm font-medium text-muted-foreground">
                            Employee Name:{" "}
                            <span className="ml-2 text-primary">
                                {payroll?.staff?.name}
                            </span>
                        </div>
                        <div className="text-sm font-medium text-muted-foreground">
                            Period:{" "}
                            <span className="ml-2 text-primary">
                                {payroll?.month}/{payroll?.year}
                            </span>
                        </div>
                        <div className="text-sm font-medium text-muted-foreground">
                            Basic Salary:{" "}
                            <span className="ml-2 text-primary">
                                {formatCurrency({
                                    amount: payroll?.current_salary,
                                })}
                            </span>
                        </div>
                        <div className="text-sm font-medium text-muted-foreground">
                            Net Salary:{" "}
                            <span className="ml-2 text-primary">
                                {formatCurrency({ amount: netSalary })}
                            </span>
                        </div>
                    </div>
                    <form onSubmit={submit}>
                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="advance"
                                className="md:col-span-2 col-span-12"
                            >
                                Advance
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    type="number"
                                    value={data.advance}
                                    onChange={(e) =>
                                        setData("advance", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError
                                    message={errors.advance}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="net_salary"
                                className="md:col-span-2 col-span-12"
                            >
                                Advance Description
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea
                                    value={data.advance_description}
                                    onChange={(e) =>
                                        setData(
                                            "advance_description",
                                            e.target.value
                                        )
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError
                                    message={errors.advance_description}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="space-y-4 mt-5">
                            <div className="flex justify-between items-center">
                                <h3 className="text-lg font-medium">
                                    Allowances
                                </h3>
                                <Button
                                    variant="secondary"
                                    type="button"
                                    onClick={addAllowance}
                                >
                                    <Plus className="w-4 h-4 mr-2" />
                                    Add Item
                                </Button>
                            </div>

                            {allowanceItems.map((item, index) => (
                                <div
                                    key={index}
                                    className="border rounded-lg p-4 space-y-4 bg-gray-50"
                                >
                                    {/* First Row */}
                                    <div className="grid grid-cols-12 gap-2">
                                        <div className="col-span-12 sm:col-span-3">
                                            <Label>Date *</Label>
                                            <DateTimePicker
                                                value={item.date}
                                                onChange={(date) =>
                                                    updateAllowance(
                                                        index,
                                                        "date",
                                                        date
                                                    )
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="col-span-12 sm:col-span-4">
                                            <Label>Descriptions *</Label>
                                            <Textarea
                                                className="w-full"
                                                value={item.description}
                                                onChange={(e) =>
                                                    updateAllowance(
                                                        index,
                                                        "description",
                                                        e.target.value
                                                    )
                                                }
                                            />
                                        </div>

                                        <div className="col-span-12 sm:col-span-4">
                                            <Label>Amount *</Label>
                                            <Input
                                                type="number"
                                                step="0.01"
                                                value={item.amount}
                                                onChange={(e) =>
                                                    updateAllowance(
                                                        index,
                                                        "amount",
                                                        e.target.value
                                                    )
                                                }
                                            />
                                        </div>

                                        <div className="flex items-center justify-end col-span-12 sm:col-span-1">
                                            {allowanceItems.length > 1 && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-red-500"
                                                    onClick={() =>
                                                        removeAllowanceItem(
                                                            index
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="space-y-4 mt-5">
                            <div className="flex justify-between items-center">
                                <h3 className="text-lg font-medium">
                                    Deductions
                                </h3>
                                <Button
                                    variant="secondary"
                                    type="button"
                                    onClick={addDeduction}
                                >
                                    <Plus className="w-4 h-4 mr-2" />
                                    Add Item
                                </Button>
                            </div>

                            {deductionItems.map((item, index) => (
                                <div
                                    key={index}
                                    className="border rounded-lg p-4 space-y-4 bg-gray-50"
                                >
                                    {/* First Row */}
                                    <div className="grid grid-cols-12 gap-2">
                                        <div className="col-span-12 sm:col-span-2">
                                            <Label>Date *</Label>
                                            <DateTimePicker
                                                value={item.date}
                                                onChange={(date) =>
                                                    updateDeduction(
                                                        index,
                                                        "date",
                                                        date
                                                    )
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="col-span-12 sm:col-span-3">
                                            <Label>Descriptions *</Label>
                                            <Textarea
                                                className="w-full"
                                                value={item.description}
                                                onChange={(e) =>
                                                    updateDeduction(
                                                        index,
                                                        "description",
                                                        e.target.value
                                                    )
                                                }
                                            />
                                        </div>

                                        <div className="col-span-12 sm:col-span-3">
                                            <Label>Amount *</Label>
                                            <Input
                                                type="number"
                                                step="0.01"
                                                value={item.amount}
                                                onChange={(e) =>
                                                    updateDeduction(
                                                        index,
                                                        "amount",
                                                        e.target.value
                                                    )
                                                }
                                            />
                                        </div>

                                        {/* account */}
                                        <div className="col-span-12 sm:col-span-3">
                                            <Label>Account *</Label>
                                            <SearchableCombobox
                                                options={accounts.map(
                                                    (account) => ({
                                                        id: account.id,
                                                        name: account.account_name,
                                                    })
                                                )}
                                                value={item.account_id}
                                                onChange={(value) =>
                                                    updateDeduction(
                                                        index,
                                                        "account_id",
                                                        value
                                                    )
                                                }
                                                placeholder="Select Account"
                                            />
                                        </div>

                                        <div className="flex items-center justify-end col-span-12 sm:col-span-1">
                                            {deductionItems.length > 1 && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-red-500"
                                                    onClick={() =>
                                                        removeDeductionItem(
                                                            index
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <Button
                            type="submit"
                            disabled={processing}
                            className="mt-4"
                        >
                            {processing ? "Updating..." : "Update Payroll"}
                        </Button>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
