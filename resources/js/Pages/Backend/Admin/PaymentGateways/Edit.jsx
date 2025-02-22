import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Label } from "@/Components/ui/label";
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

export default function Edit({ paymentgateway, id }) {
const { data, setData, patch, processing, errors } = useForm({
	name: paymentgateway.name,
	status: paymentgateway.status,
	parameters: paymentgateway.parameters,
});

const submit = (e) => {
	e.preventDefault();
	patch(route("payment_gateways.update", id), {
		preserveScroll: true,
		onSuccess: () => {
			toast.success("Payment Gateway updated successfully");
		},
	});
};

return (
	<AuthenticatedLayout>
		<PageHeader
			title="Payments"
			page="payment gateway"
			subpage="edit"
		/>
		<div className="flex flex-col p-4 gap-4">
			<form onSubmit={submit} className="validate" autoComplete="off" encType="multipart/form-data">
				<div className="grid grid-cols-12">
					<Label htmlFor="name" className="xl:col-span-3 col-span-12">
						Name
					</Label>
					<Input
						id="name"
						type="text"
						value={data.name}
						readOnly
						className="xl:col-span-9 col-span-12"
					/>
				</div>

				<div className="grid grid-cols-12 mt-3">
					<Label htmlFor="status" className="xl:col-span-3 col-span-12">
						Status
					</Label>
					<Select
						id="status"
						name="status"
						value={String(data.status)}
						onValueChange={(val) => setData("status", parseInt(val))}
						className="xl:col-span-9 col-span-12"
					>
						<SelectTrigger>
							<SelectValue>
								{data.status === 1 ? "Enable" : "Disable"}
							</SelectValue>
						</SelectTrigger>
						<SelectContent>
							<SelectItem value="0">Disable</SelectItem>
							<SelectItem value="1">Enable</SelectItem>
						</SelectContent>
					</Select>
					<InputError message={errors.status} className="text-sm" />
				</div>

				{Object.entries(data.parameters).map(([key, value]) => (
					<div className="grid grid-cols-12 mt-3" key={key}>
						<Label htmlFor={key} className="xl:col-span-3 col-span-12">
							{key.replace('_', ' ').toUpperCase()}
						</Label>
						{key !== 'environment' ? (
							<Input
								id={key}
								type="text"
								value={value}
								name={`parameter_value[${key}]`}
								onChange={(e) => setData(`parameters.${key}`, e.target.value)}
								className="xl:col-span-9 col-span-12"
							/>
						) : (
							<Select
								id={key}
								name={`parameter_value[${key}]`}
								value={value}
								onValueChange={(val) => setData(`parameters.${key}`, val)}
								className="xl:col-span-9 col-span-12"
							>
								<SelectTrigger>
									<SelectValue>
										{value === "sandbox" ? "Sandbox" : "Live"}
									</SelectValue>
								</SelectTrigger>
								<SelectContent>
									<SelectItem value="sandbox">Sandbox</SelectItem>
									<SelectItem value="live">Live</SelectItem>
								</SelectContent>
							</Select>
						)}
						<InputError message={errors[`parameters.${key}`]} className="text-sm" />
					</div>
				))}

				<div className="col-span-12 mt-4">
					<Button type="submit" disabled={processing}>
						Save Changes
					</Button>
				</div>
			</form>
		</div>
	</AuthenticatedLayout>
);
}