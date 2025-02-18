import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import {

Table,
TableBody,
TableCaption,
TableCell,
TableRow,
} from "@/components/ui/table";

export default function View({ packageData }) {
return (
	<AuthenticatedLayout>
		<SidebarInset>
			<PageHeader page="Packages" subpage="Package Details" url="user_packages.index" />

			<div className="p-4">
				<Table>
					<TableCaption>Package details</TableCaption>
					<TableBody>
						<TableRow>
							<TableCell>Package Name</TableCell>
							<TableCell>{packageData.name}</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>Package Type</TableCell>
							<TableCell>{packageData.package_type}</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>Cost</TableCell>
							<TableCell>{packageData.cost}</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>Status</TableCell>
							<TableCell>
								{packageData.status == 1 ? "Active" : "Disabled"}
							</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>Is Popular</TableCell>
							<TableCell>
								{packageData.is_popular == 1 ? "Yes" : "No"}
							</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>Discount</TableCell>
							<TableCell>{packageData.discount} %</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>Trial Days</TableCell>
							<TableCell>{packageData.trial_days}</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>User Limit</TableCell>
							<TableCell>
								{packageData.user_limit !== "-1" ? packageData.user_limit : "Unlimited"}
							</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>Invoice Limit</TableCell>
							<TableCell>
								{packageData.invoice_limit !== "-1" ? packageData.invoice_limit : "Unlimited"}
							</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>Quotation Limit</TableCell>
							<TableCell>
								{packageData.quotation_limit !== "-1"
									? packageData.quotation_limit
									: "Unlimited"}
							</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>Recurring Invoice</TableCell>
							<TableCell>
								{packageData.recurring_invoice == 1 ? "Yes" : "No"}
							</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>Customer Limit</TableCell>
							<TableCell>
								{packageData.customer_limit !== "-1"
									? packageData.customer_limit
									: "Unlimited"}
							</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>Business Limit</TableCell>
							<TableCell>
								{packageData.business_limit !== "-1"
									? packageData.business_limit
									: "Unlimited"}
							</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>Invoice Template Maker</TableCell>
							<TableCell>
								{packageData.invoice_builder == 1 ? "Yes" : "No"}
							</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>Online Invoice Payment</TableCell>
							<TableCell>
								{packageData.online_invoice_payment == 1 ? "Yes" : "No"}
							</TableCell>
						</TableRow>
						<TableRow>
							<TableCell>HR & Payroll Module</TableCell>
							<TableCell>
								{packageData.payroll_module == 1 ? "Yes" : "No"}
							</TableCell>
						</TableRow>
					</TableBody>
				</Table>
			</div>
		</SidebarInset>
	</AuthenticatedLayout>
);
}
