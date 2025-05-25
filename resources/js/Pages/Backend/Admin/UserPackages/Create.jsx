import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Label } from "@/Components/ui/label";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { Checkbox } from "@/Components/ui/checkbox";
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/Components/ui/select";

export default function Create({ users }) {
	const { data, setData, post, processing, errors, reset } = useForm({
		user_id: "",
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
		storage_limit: "",
		medical_record: 0,
		prescription: 0,
		construction_module: 0,
		time_sheet_module: 0,
	});

	const submit = (e) => {
		e.preventDefault();
		post(route("user_packages.store"), {
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
				<PageHeader
					page="User Packages"
					subpage="Create Package"
					url="user_packages.index"
				/>
				<div className="flex flex-col p-4 gap-4">
					<form onSubmit={submit}>
						<div className="grid grid-cols-12 mt-2">
							<Label htmlFor="user_id" className="md:col-span-2 col-span-12">
								User
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
								<Select
									id="user_id"
									name="user_id"
									value={data.user_id}
									onValueChange={(val) => setData("user_id", val)}
								>
									<SelectTrigger>
										<SelectValue>
											{data.user_id
												? `${users.find((u) => String(u.id) === String(data.user_id))?.email} - ${
														users.find((u) => String(u.id) === String(data.user_id))?.name
												  }`
												: "Select One"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										{users.map((u) => (
											<SelectItem key={u.id} value={String(u.id)}>
												{`${u.email} - ${u.name}`}
											</SelectItem>
										))}
									</SelectContent>
								</Select>
								<InputError message={errors.user_id} className="text-sm" />
							</div>
						</div>

						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="name"
								className="md:col-span-2 col-span-12"
							>
								Package Name
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
								<Input
									id="name"
									type="text"
									name="name"
									value={data.name}
									onChange={(e) =>
										setData("name", e.target.value)
									}
									className="md:w-1/2 w-full"
								/>
								<InputError
									message={errors.name}
									className="text-sm"
								/>
							</div>
						</div>

						<div className="grid grid-cols-12 mt-2">
							<Label htmlFor="package_type" className="md:col-span-2 col-span-12">
								Package Type
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
								<Select
									id="package_type"
									name="package_type"
									value={data.package_type}
									onValueChange={(val) => setData("package_type", val)}
								>
									<SelectTrigger>
										<SelectValue>
											{data.package_type === "monthly"
												? "Monthly"
												: data.package_type === "yearly"
												? "Yearly"
												: data.package_type === "lifetime"
												? "Lifetime"
												: "Select One"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="monthly">Monthly</SelectItem>
										<SelectItem value="yearly">Yearly</SelectItem>
										<SelectItem value="lifetime">Lifetime</SelectItem>
									</SelectContent>
								</Select>
								<InputError message={errors.package_type} className="text-sm" />
							</div>
						</div>
						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="cost"
								className="md:col-span-2 col-span-12"
							>
								Cost
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
								<Input
									id="cost"
									type="text"
									name="cost"
									value={data.cost}
									onChange={(e) =>
										setData("cost", e.target.value)
									}
									className="md:w-1/2 w-full"
								/>
								<InputError
									message={errors.cost}
									className="text-sm"
								/>
							</div>
						</div>

						{/* Discount */}
						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="discount"
								className="md:col-span-2 col-span-12"
							>
								Discount (%)
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
								<Input
									id="discount"
									type="number"
									name="discount"
									value={data.discount}
									onChange={(e) =>
										setData("discount", e.target.value)
									}
									className="md:w-1/2 w-full"
								/>
								<InputError
									message={errors.discount}
									className="text-sm"
								/>
							</div>
						</div>

						{/* Trial Days */}
						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="trial_days"
								className="md:col-span-2 col-span-12"
							>
								Trial Days
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
								<Input
									id="trial_days"
									type="number"
									name="trial_days"
									value={data.trial_days}
									onChange={(e) =>
										setData("trial_days", e.target.value)
									}
									className="md:w-1/2 w-full"
								/>
								<InputError
									message={errors.trial_days}
									className="text-sm"
								/>
							</div>
						</div>

						<div className="grid grid-cols-12 mt-2">
							<Label htmlFor="status" className="md:col-span-2 col-span-12">
								Status
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
								<Select
									id="status"
									name="status"
									value={String(data.status)}
									onValueChange={(val) => setData("status", parseInt(val))}
								>
									<SelectTrigger>
										<SelectValue>
											{data.status === 1 ? "Active" : "Disabled"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="1">Active</SelectItem>
										<SelectItem value="0">Disabled</SelectItem>
									</SelectContent>
								</Select>
								<InputError message={errors.status} className="text-sm" />
							</div>
						</div>

						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="is_popular"
								className="md:col-span-2 col-span-12"
							>
								Is Popular
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
								<Select
									id="is_popular"
									name="is_popular"
									value={String(data.is_popular)}
									onValueChange={(val) =>
										setData("is_popular", parseInt(val))
									}
								>
									<SelectTrigger>
										<SelectValue>
											{data.is_popular === 1
												? "Yes"
												: "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
								<InputError
									message={errors.is_popular}
									className="text-sm"
								/>
							</div>
						</div>

						<SidebarSeparator className="my-4" />

						{/* System User Limit */}
						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="user_limit"
								className="md:col-span-2 col-span-12"
							>
								System User Limit
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 flex items-center gap-4">
								<Input
									id="user_limit"
									type="number"
									name="user_limit"
									value={data.user_limit}
									onChange={(e) =>
										setData("user_limit", e.target.value)
									}
									placeholder="5"
									className="md:w-1/2 w-full"
									disabled={data.user_limit === "-1"}
								/>
								<div className="flex items-center gap-2">
									<Checkbox
										id="unlimited_users"
										checked={data.user_limit === "-1"}
										onCheckedChange={(checked) => {
											setData("user_limit", checked ? "-1" : "")
										}}
									/>
									<Label htmlFor="unlimited_users" className="text-sm">Unlimited</Label>
								</div>
								<InputError
									message={errors.user_limit}
									className="text-sm"
								/>
							</div>
						</div>

						{/* Invoice Limit */}
						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="invoice_limit"
								className="md:col-span-2 col-span-12"
							>
								Invoice Limit
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 flex items-center gap-4">
								<Input
									id="invoice_limit"
									type="number"
									name="invoice_limit"
									value={data.invoice_limit}
									onChange={(e) =>
										setData("invoice_limit", e.target.value)
									}
									placeholder="100"
									className="md:w-1/2 w-full"
									disabled={data.invoice_limit === "-1"}
								/>
								<div className="flex items-center gap-2">
									<Checkbox
										id="unlimited_invoices"
										checked={data.invoice_limit === "-1"}
										onCheckedChange={(checked) => {
											setData("invoice_limit", checked ? "-1" : "")
										}}
									/>
									<Label htmlFor="unlimited_invoices" className="text-sm">Unlimited</Label>
								</div>
								<InputError
									message={errors.invoice_limit}
									className="text-sm"
								/>
							</div>
						</div>

						{/* Quotation Limit */}
						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="quotation_limit"
								className="md:col-span-2 col-span-12"
							>
								Quotation Limit
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 flex items-center gap-4">
								<Input
									id="quotation_limit"
									type="number"
									name="quotation_limit"
									value={data.quotation_limit}
									onChange={(e) =>
										setData("quotation_limit", e.target.value)
									}
									placeholder="150"
									className="md:w-1/2 w-full"
									disabled={data.quotation_limit === "-1"}
								/>
								<div className="flex items-center gap-2">
									<Checkbox
										id="unlimited_quotations"
										checked={data.quotation_limit === "-1"}
										onCheckedChange={(checked) => {
											setData("quotation_limit", checked ? "-1" : "")
										}}
									/>
									<Label htmlFor="unlimited_quotations" className="text-sm">Unlimited</Label>
								</div>
								<InputError
									message={errors.quotation_limit}
									className="text-sm"
								/>
							</div>
						</div>

						{/* Recurring Limit */}
						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="recurring_invoice"
								className="md:col-span-2 col-span-12"
							>
								Recurring Limit
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
								<Select
									id="recurring_invoice"
									name="recurring_invoice"
									value={String(data.recurring_invoice)}
									onValueChange={(val) =>
										setData("recurring_invoice", parseInt(val))
									}
								>
									<SelectTrigger>
										<SelectValue>
											{data.recurring_invoice === 1
												? "Yes"
												: "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
								<InputError
									message={errors.recurring_invoice}
									className="text-sm"
								/>
							</div>
						</div>

						{/* Deffered Limit */}
						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="deffered_invoice"
								className="md:col-span-2 col-span-12"
							>
								Deffered Limit
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
								<Select
									id="deffered_invoice"
									name="deffered_invoice"
									value={String(data.deffered_invoice)}
									onValueChange={(val) =>
										setData("deffered_invoice", parseInt(val))
									}
								>
									<SelectTrigger>
										<SelectValue>
											{data.deffered_invoice === 1
												? "Yes"
												: "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
								<InputError
									message={errors.deffered_invoice}
									className="text-sm"
								/>
							</div>
						</div>

						{/* Customer Limit */}
						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="customer_limit"
								className="md:col-span-2 col-span-12"
							>
								Customer Limit
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 flex items-center gap-4">
								<Input
									id="customer_limit"
									type="number"
									name="customer_limit"
									value={data.customer_limit}
									onChange={(e) =>
										setData("customer_limit", e.target.value)
									}
									placeholder="100"
									className="md:w-1/2 w-full"
									disabled={data.customer_limit === "-1"}
								/>
								<div className="flex items-center gap-2">
									<Checkbox
										id="unlimited_customers"
										checked={data.customer_limit === "-1"}
										onCheckedChange={(checked) => {
											setData("customer_limit", checked ? "-1" : "")
										}}
									/>
									<Label htmlFor="unlimited_customers" className="text-sm">Unlimited</Label>
								</div>
								<InputError
									message={errors.customer_limit}
									className="text-sm"
								/>
							</div>
						</div>

						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="business_limit"
								className="md:col-span-2 col-span-12"
							>
								Business Limit
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 flex items-center gap-4">
								<Input
									id="business_limit"
									type="number"
									name="business_limit"
									value={data.business_limit}
									onChange={(e) =>
										setData("business_limit", e.target.value)
									}
									placeholder="10"
									className="md:w-1/2 w-full"
									disabled={data.business_limit === "-1"}
								/>
								<div className="flex items-center gap-2">
									<Checkbox
										id="unlimited_businesses"
										checked={data.business_limit === "-1"}
										onCheckedChange={(checked) => {
											setData("business_limit", checked ? "-1" : "")
										}}
									/>
									<Label htmlFor="unlimited_businesses" className="text-sm">Unlimited</Label>
								</div>
								<InputError
									message={errors.business_limit}
									className="text-sm"
								/>
							</div>
						</div>

						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="invoice_builder"
								className="md:col-span-2 col-span-12"
							>
								Invoice Template Maker
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
								<Select
									id="invoice_builder"
									name="invoice_builder"
									value={String(data.invoice_builder)}
									onValueChange={(val) => setData("invoice_builder", parseInt(val))}
								>
									<SelectTrigger>
										<SelectValue>
											{data.invoice_builder === 1 ? "Yes" : "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
								<InputError message={errors.invoice_builder} className="text-sm" />
							</div>
						</div>
						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="online_invoice_payment"
								className="md:col-span-2 col-span-12"
							>
								Online Invoice Payment
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
								<Select
									id="online_invoice_payment"
									name="online_invoice_payment"
									value={String(data.online_invoice_payment)}
									onValueChange={(val) => setData("online_invoice_payment", parseInt(val))}
								>
									<SelectTrigger>
										<SelectValue>
											{data.online_invoice_payment === 1 ? "Yes" : "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
								<InputError
									message={errors.online_invoice_payment}
									className="text-sm"
								/>
							</div>
						</div>

						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="pos"
								className="md:col-span-2 col-span-12"
							>
								POS
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
								<Select
									id="pos"
									name="pos"
									value={String(data.pos)}
									onValueChange={(val) =>
										setData("pos", parseInt(val))
									}
								>
									<SelectTrigger>
										<SelectValue>
											{data.pos === 1 ? "Yes" : "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
								<InputError
									message={errors.pos}
									className="text-sm"
								/>
							</div>
						</div>

						{/* HR and Payroll Module */}
						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="payroll_module"
								className="md:col-span-2 col-span-12"
							>
								HR and Payroll Module
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
								<Select
									id="payroll_module"
									name="payroll_module"
									value={String(data.payroll_module)}
									onValueChange={(val) =>
										setData("payroll_module", parseInt(val))
									}
								>
									<SelectTrigger>
										<SelectValue>
											{data.payroll_module === 1
												? "Yes"
												: "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
								<InputError
									message={errors.payroll_module}
									className="text-sm"
								/>
							</div>
						</div>

						<SidebarSeparator className="my-4" />

						{/* Storage Limit */}
						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="storage_limit"
								className="md:col-span-2 col-span-12"
							>
								Storage Limit
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 flex items-center gap-4">
								<Input
									id="storage_limit"
									type="number"
									name="storage_limit"
									value={data.storage_limit}
									onChange={(e) =>
										setData("storage_limit", e.target.value)
									}
									placeholder="100"
									className="md:w-1/2 w-full"
									disabled={data.storage_limit === "-1"}
								/>
								<div className="flex items-center gap-2">
									<Checkbox
										id="unlimited_storage"
										checked={data.storage_limit === "-1"}
										onCheckedChange={(checked) => {
											setData("storage_limit", checked ? "-1" : "")
										}}
									/>
									<Label htmlFor="unlimited_storage" className="text-sm">Unlimited</Label>
								</div>
								<InputError
									message={errors.storage_limit}
									className="text-sm"
								/>
							</div>
						</div>

						{/* Medical Record */}
						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="medical_record"
								className="md:col-span-2 col-span-12"
							>
								Medical Record
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
								<Select
									id="medical_record"
									name="medical_record"
									value={String(data.medical_record)}
									onValueChange={(val) =>
										setData("medical_record", parseInt(val))
									}
								>
									<SelectTrigger>
										<SelectValue>
											{data.medical_record === 1
												? "Yes"
												: "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
								<InputError
									message={errors.medical_record}
									className="text-sm"
								/>
							</div>
						</div>

						{/* Prescription */}
						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="prescription"
								className="md:col-span-2 col-span-12"
							>
								Prescription
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
								<Select
									id="prescription"
									name="prescription"
									value={String(data.prescription)}
									onValueChange={(val) =>
										setData("prescription", parseInt(val))
									}
								>
									<SelectTrigger>
										<SelectValue>
											{data.prescription === 1
												? "Yes"
												: "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
								<InputError
									message={errors.prescription}
									className="text-sm"
								/>
							</div>
						</div>

						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="construction_module"
								className="md:col-span-2 col-span-12"
							>
								Construction Module
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
								<Select
									id="construction_module"
									name="construction_module"
									value={String(data.construction_module)}
									onValueChange={(val) =>
										setData("construction_module", parseInt(val))
									}
								>
									<SelectTrigger>
										<SelectValue>
											{data.construction_module === 1
												? "Yes"
												: "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
								<InputError
									message={errors.construction_module}
									className="text-sm"
								/>
							</div>
						</div>

						<div className="grid grid-cols-12 mt-2">
							<Label
								htmlFor="time_sheet_module"
								className="md:col-span-2 col-span-12"
							>
								Time Sheet Module
							</Label>
							<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
								<Select
									id="time_sheet_module"
									name="time_sheet_module"
									value={String(data.time_sheet_module)}
									onValueChange={(val) =>
										setData("time_sheet_module", parseInt(val))
									}
								>
									<SelectTrigger>
										<SelectValue>
											{data.time_sheet_module === 1
												? "Yes"
												: "No"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="0">No</SelectItem>
										<SelectItem value="1">Yes</SelectItem>
									</SelectContent>
								</Select>
								<InputError
									message={errors.time_sheet_module}
									className="text-sm"
								/>
							</div>
						</div>

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
