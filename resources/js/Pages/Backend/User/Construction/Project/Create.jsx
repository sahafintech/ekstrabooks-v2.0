import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { Textarea } from "@/Components/ui/textarea";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";
export default function Create({
    project_groups = [],
    customers = [],
    employees = [],
    currencies = [],
    base_currency,
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        project_group_id: "",
        project_code: "",
        project_name: "",
        customer_id: "",
        project_manager_id: "",
        start_date: "",
        end_date: "",
        status: "",
        priority: "",
        project_currency: base_currency,
        description: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("projects.store"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Project created successfully");
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Projects"
                    subpage="Add New"
                    url="projects.index"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="project_code"
                                className="md:col-span-2 col-span-12"
                            >
                                Project Code *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="project_code"
                                    value={data.project_code}
                                    onChange={(e) =>
                                        setData("project_code", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError
                                    message={errors.project_code}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="project_name"
                                className="md:col-span-2 col-span-12"
                            >
                                Project Name *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="project_name"
                                    value={data.project_name}
                                    onChange={(e) =>
                                        setData("project_name", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError
                                    message={errors.project_name}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="project_group_id"
                                className="md:col-span-2 col-span-12"
                            >
                                Project Group
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <SearchableCombobox
                                    options={project_groups.map((group) => ({
                                        id: group.id,
                                        name: group.group_name,
                                    }))}
                                    value={data.project_group_id}
                                    onChange={(value) =>
                                        setData("project_group_id", value)
                                    }
                                    placeholder="Select Project Group"
                                    className="md:w-1/2 w-full"
                                />
                                <InputError
                                    message={errors.project_group_id}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="customer_id"
                                className="md:col-span-2 col-span-12"
                            >
                                Customer
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <SearchableCombobox
                                    options={customers.map((customer) => ({
                                        id: customer.id,
                                        name: customer.name,
                                    }))}
                                    value={data.customer_id}
                                    onChange={(value) =>
                                        setData("customer_id", value)
                                    }
                                    className="md:w-1/2 w-full"
                                    placeholder="Select Customer"
                                />
                                <InputError
                                    message={errors.customer_id}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="project_manager_id"
                                className="md:col-span-2 col-span-12"
                            >
                                Project Manager
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <SearchableCombobox
                                    options={employees.map((employee) => ({
                                        id: employee.id,
                                        name: employee.name,
                                    }))}
                                    value={data.project_manager_id}
                                    onChange={(value) =>
                                        setData("project_manager_id", value)
                                    }
                                    placeholder="Select Project Manager"
                                    className="md:w-1/2 w-full"
                                />
                                <InputError
                                    message={errors.project_manager_id}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="start_date"
                                className="md:col-span-2 col-span-12"
                            >
                                Start Date
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker
                                    value={data.start_date}
                                    onChange={(date) =>
                                        setData("start_date", date)
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError
                                    message={errors.start_date}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="end_date"
                                className="md:col-span-2 col-span-12"
                            >
                                End Date
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker
                                    value={data.end_date}
                                    onChange={(date) =>
                                        setData("end_date", date)
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError
                                    message={errors.end_date}
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
                                <SearchableCombobox
                                    options={[
                                        { id: "Planning", name: "Planning" },
                                        {
                                            id: "In Progress",
                                            name: "In Progress",
                                        },
                                        { id: "Completed", name: "Completed" },
                                        { id: "Cancelled", name: "Cancelled" },
                                        { id: "On Hold", name: "On Hold" },
                                        { id: "Archived", name: "Archived" },
                                    ]}
                                    value={data.status}
                                    onChange={(value) =>
                                        setData("status", value)
                                    }
                                    placeholder="Select Status"
                                    className="md:w-1/2 w-full"
                                />
                                <InputError
                                    message={errors.status}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="priority"
                                className="md:col-span-2 col-span-12"
                            >
                                Priority
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <SearchableCombobox
                                    options={[
                                        "Low",
                                        "Medium",
                                        "High",
                                        "Critical",
                                    ]}
                                    value={data.priority}
                                    onChange={(value) =>
                                        setData("priority", value)
                                    }
                                    placeholder="Select Priority"
                                    className="md:w-1/2 w-full"
                                />
                                <InputError
                                    message={errors.priority}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="currency"
                                className="md:col-span-2 col-span-12"
                            >
                                Project Currency *
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
                                        value={data.project_currency}
                                        onChange={(selectedValue) => {
                                            console.log(
                                                "Currency selected:",
                                                selectedValue
                                            );
                                            setData("project_currency", selectedValue);
                                            handleCurrencyChange(selectedValue);
                                        }}
                                        placeholder="Select currency"
                                    />
                                </div>
                                <InputError
                                    message={errors.project_currency}
                                    className="text-sm"
                                />
                            </div>
                        </div>

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
                                />
                                <InputError
                                    message={errors.description}
                                    className="text-sm"
                                />
                            </div>
                        </div>

                        <Button
                            type="submit"
                            disabled={processing}
                            className="mt-4"
                        >
                            {processing ? "Creating..." : "Create Project"}
                        </Button>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
