import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
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
} from "@/components/ui/select";

export default function Create() {
const { data, setData, post, processing, errors, reset } = useForm({
	name: "",
	package_type: "",
	cost: "",
	discount: 0,
	trial_days: 0,
	status: 1,
	is_popular: 0,
	user_limit: "",
	invoice_limit: "",
	quotation_limit: "",
	recurring_invoice: 0,
	deffered_invoice: 0,
	customer_limit: "",
	business_limit: "",
	invoice_builder: "",
	online_invoice_payment: "",
	pos: "",
	payroll_module: "",
});

const submit = (e) => {
	e.preventDefault();
	post(route("packages.store"), {
		preserveScroll: true,
		onSuccess: () => {
			toast.success("Package created successfully");
			reset();
		},
	});
};

return (
	<AuthenticatedLayout>
		<SidebarInset>
			<PageHeader page="Admin" subpage="Create Package" url="packages.index" />

			<div className="flex flex-1 flex-col gap-4 p-4 pt-0">
				<form onSubmit={submit}>
					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="name" className="md:col-span-2 col-span-12">
							Package Name
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
								onChange={(e) => setData("name", e.target.value)}
							/>
							<InputError message={errors.name} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="package_type" className="md:col-span-2 col-span-12">
							Package Type
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<div className="md:w-1/2 w-full">
								<Select
									id="package_type"
									name="package_type"
									value={data.package_type}
									onValueChange={(value) => setData("package_type", value)}
								>
									<SelectTrigger>
										<SelectValue>{data.package_type || "Select One"}</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="monthly">Monthly</SelectItem>
										<SelectItem value="yearly">Yearly</SelectItem>
										<SelectItem value="lifetime">Lifetime</SelectItem>
									</SelectContent>
								</Select>
							</div>
							<InputError message={errors.package_type} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="cost" className="md:col-span-2 col-span-12">
							Cost
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<Input
								id="cost"
								type="text"
								name="cost"
								value={data.cost}
								autoComplete="cost"
								className="md:w-1/2 w-full"
								onChange={(e) => setData("cost", e.target.value)}
							/>
							<InputError message={errors.cost} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="discount" className="md:col-span-2 col-span-12">
							Discount (%)
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<Input
								id="discount"
								type="number"
								name="discount"
								value={data.discount}
								autoComplete="discount"
								className="md:w-1/2 w-full"
								onChange={(e) => setData("discount", e.target.value)}
							/>
							<InputError message={errors.discount} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="trial_days" className="md:col-span-2 col-span-12">
							Trial Days
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<Input
								id="trial_days"
								type="number"
								name="trial_days"
								value={data.trial_days}
								autoComplete="trial_days"
								className="md:w-1/2 w-full"
								onChange={(e) => setData("trial_days", e.target.value)}
							/>
							<InputError message={errors.trial_days} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="status" className="md:col-span-2 col-span-12">
							Status
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<div className="md:w-1/2 w-full">
								<Select
									id="status"
									name="status"
									value={data.status}
									onValueChange={(value) => setData("status", value)}
								>
									<SelectTrigger>
										<SelectValue>
											{data.status === "1" ? "Active" : "Disabled"}
										</SelectValue>
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

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="is_popular" className="md:col-span-2 col-span-12">
							Is Popular
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<div className="md:w-1/2 w-full">
								<Select
									id="is_popular"
									name="is_popular"
									value={data.is_popular}
									onValueChange={(value) => setData("is_popular", value)}
								>
									<SelectTrigger>
										<SelectValue>
											{data.is_popular === "1" ? "Yes" : "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
							</div>
							<InputError message={errors.is_popular} className="text-sm" />
						</div>
					</div>

					<SidebarSeparator className="my-4" />

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="user_limit" className="md:col-span-2 col-span-12">
							System User Limit
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<Input
								id="user_limit"
								type="number"
								name="user_limit"
								value={data.user_limit}
								autoComplete="user_limit"
								className="md:w-1/2 w-full"
								onChange={(e) => setData("user_limit", e.target.value)}
								placeholder="5"
							/>
							<InputError message={errors.user_limit} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="invoice_limit" className="md:col-span-2 col-span-12">
							Invoice Limit
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<Input
								id="invoice_limit"
								type="number"
								name="invoice_limit"
								value={data.invoice_limit}
								autoComplete="invoice_limit"
								className="md:w-1/2 w-full"
								onChange={(e) => setData("invoice_limit", e.target.value)}
								placeholder="100"
							/>
							<InputError message={errors.invoice_limit} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="quotation_limit" className="md:col-span-2 col-span-12">
							Quotation Limit
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<Input
								id="quotation_limit"
								type="number"
								name="quotation_limit"
								value={data.quotation_limit}
								autoComplete="quotation_limit"
								className="md:w-1/2 w-full"
								onChange={(e) => setData("quotation_limit", e.target.value)}
								placeholder="150"
							/>
							<InputError message={errors.quotation_limit} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="recurring_invoice" className="md:col-span-2 col-span-12">
							Recurring Limit
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<div className="md:w-1/2 w-full">
								<Select
									id="recurring_invoice"
									name="recurring_invoice"
									value={data.recurring_invoice}
									onValueChange={(value) => setData("recurring_invoice", value)}
								>
									<SelectTrigger>
										<SelectValue>
											{data.recurring_invoice === "1" ? "Yes" : "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
							</div>
							<InputError message={errors.recurring_invoice} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="deffered_invoice" className="md:col-span-2 col-span-12">
							Deffered Limit
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<div className="md:w-1/2 w-full">
								<Select
									id="deffered_invoice"
									name="deffered_invoice"
									value={data.deffered_invoice}
									onValueChange={(value) => setData("deffered_invoice", value)}
								>
									<SelectTrigger>
										<SelectValue>
											{data.deffered_invoice === "1" ? "Yes" : "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
							</div>
							<InputError message={errors.deffered_invoice} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="customer_limit" className="md:col-span-2 col-span-12">
							Customer Limit
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<Input
								id="customer_limit"
								type="number"
								name="customer_limit"
								value={data.customer_limit}
								autoComplete="customer_limit"
								className="md:w-1/2 w-full"
								onChange={(e) => setData("customer_limit", e.target.value)}
								placeholder="100"
							/>
							<InputError message={errors.customer_limit} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="business_limit" className="md:col-span-2 col-span-12">
							Business Limit
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<Input
								id="business_limit"
								type="number"
								name="business_limit"
								value={data.business_limit}
								autoComplete="business_limit"
								className="md:w-1/2 w-full"
								onChange={(e) => setData("business_limit", e.target.value)}
								placeholder="10"
							/>
							<InputError message={errors.business_limit} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="invoice_builder" className="md:col-span-2 col-span-12">
							Invoice Template Maker
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<div className="md:w-1/2 w-full">
								<Select
									id="invoice_builder"
									name="invoice_builder"
									value={data.invoice_builder}
									onValueChange={(value) => setData("invoice_builder", value)}
								>
									<SelectTrigger>
										<SelectValue>
											{data.invoice_builder === "1" ? "Yes" : "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
							</div>
							<InputError message={errors.invoice_builder} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="online_invoice_payment" className="md:col-span-2 col-span-12">
							Online Invoice Payment
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<div className="md:w-1/2 w-full">
								<Select
									id="online_invoice_payment"
									name="online_invoice_payment"
									value={data.online_invoice_payment}
									onValueChange={(value) => setData("online_invoice_payment", value)}
								>
									<SelectTrigger>
										<SelectValue>
											{data.online_invoice_payment === "1" ? "Yes" : "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
							</div>
							<InputError message={errors.online_invoice_payment} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="pos" className="md:col-span-2 col-span-12">
							POS
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<div className="md:w-1/2 w-full">
								<Select
									id="pos"
									name="pos"
									value={data.pos}
									onValueChange={(value) => setData("pos", value)}
								>
									<SelectTrigger>
										<SelectValue>
											{data.pos === "1" ? "Yes" : "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
							</div>
							<InputError message={errors.pos} className="text-sm" />
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="payroll_module" className="md:col-span-2 col-span-12">
							HR and Payroll Module
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<div className="md:w-1/2 w-full">
								<Select
									id="payroll_module"
									name="payroll_module"
									value={data.payroll_module}
									onValueChange={(value) => setData("payroll_module", value)}
								>
									<SelectTrigger>
										<SelectValue>
											{data.payroll_module === "1" ? "Yes" : "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
							</div>
							<InputError message={errors.payroll_module} className="text-sm" />
						</div>
					</div>

					<SidebarSeparator className="my-4" />

					<div className="col-span-12 mt-4">
						<Button type="submit" disabled={processing}>
							Save Changes
						</Button>
					</div>
				</form>
			</div>
		</SidebarInset>
	</AuthenticatedLayout>
);
}