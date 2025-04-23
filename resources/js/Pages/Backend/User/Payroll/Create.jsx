import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

export default function Create({ years }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        month: "",
        year: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("payslips.store"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Payroll generated successfully");
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Payrolls" subpage="Add New" url="payslips.index" />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="month" className="md:col-span-2 col-span-12">
                                Month *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <SearchableCombobox
                                    options={[
                                        { name: "January", id: "01" },
                                        { name: "February", id: "02" },
                                        { name: "March", id: "03" },
                                        { name: "April", id: "04" },
                                        { name: "May", id: "05" },
                                        { name: "June", id: "06" },
                                        { name: "July", id: "07" },
                                        { name: "August", id: "08" },
                                        { name: "September", id: "09" },
                                        { name: "October", id: "10" },
                                        { name: "November", id: "11" },
                                        { name: "December", id: "12" },
                                    ]}
                                    value={data.month}
                                    onChange={(value) => setData("month", value)}
                                    placeholder="Select month"
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.month} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="year" className="md:col-span-2 col-span-12">
                                Year *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <SearchableCombobox
                                    options={years.map(year => ({ id: year, name: year }))}
                                    value={data.year}
                                    onChange={(value) => setData("year", value)}
                                    placeholder="Select year"
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.year} className="text-sm" />
                            </div>
                        </div>

                        <Button type="submit" disabled={processing} className="mt-4">
                            {processing ? "Generating..." : "Generate"}
                        </Button>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
