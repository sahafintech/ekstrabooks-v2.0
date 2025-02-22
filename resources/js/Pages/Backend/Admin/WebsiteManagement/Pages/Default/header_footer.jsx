import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Label } from "@/Components/ui/label";
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
} from "@/Components/ui/select";

export default function HeaderFooter({ pageData = {}, languages = [], pages = [] }) {
const { data, setData, post, processing, errors, reset } = useForm({
	top_header_color: pageData.top_header_color ?? "#5034fc",
	footer_color: pageData.footer_color ?? "#061E5C",
	widget_1_heading: pageData.widget_1_heading ?? "",
	widget_1_content: pageData.widget_1_content ?? "",
	widget_2_heading: pageData.widget_2_heading ?? "",
	widget_2_menus: pageData.widget_2_menus ?? [],
	widget_2_content: "",
	widget_3_heading: pageData.widget_3_heading ?? "",
	widget_3_menus: pageData.widget_3_menus ?? [],
	model_language: "",
	copyright_text: pageData.copyright_text ?? "",
	payment_gateway_image: null,
	custom_css: pageData.custom_css ?? "",
	custom_js: pageData.custom_js ?? "",
});

const submit = (e) => {
	e.preventDefault();
	post(route("pages.default_pages.store", "header_footer"), {
		onSuccess: () => {
			toast.success("Saved successfully");
			reset();
		},
	});
};

return (
	<AuthenticatedLayout>
		<SidebarInset>
			<PageHeader
				page="Default Pages"
				subpage="Header & Footer"
				url="pages.default_pages.index"
			/>
			<form onSubmit={submit} className="p-4 space-y-4">
				<div className="grid grid-cols-12 gap-4">
					<Label className="col-span-12 md:col-span-2" htmlFor="top_header_color">
						Top Header Color
					</Label>
					<div className="col-span-12 md:col-span-10">
						<Input
							id="top_header_color"
							name="top_header_color"
							type="text"
							value={data.top_header_color}
							onChange={(e) => setData("top_header_color", e.target.value)}
							className="md:w-1/2 w-full"
						/>
						<InputError message={errors.top_header_color} className="mt-1" />
					</div>
				</div>

				<div className="grid grid-cols-12 gap-4">
					<Label className="col-span-12 md:col-span-2" htmlFor="footer_color">
						Footer Color
					</Label>
					<div className="col-span-12 md:col-span-10">
						<Input
							id="footer_color"
							name="footer_color"
							type="text"
							value={data.footer_color}
							onChange={(e) => setData("footer_color", e.target.value)}
							className="md:w-1/2 w-full"
						/>
						<InputError message={errors.footer_color} className="mt-1" />
					</div>
				</div>

				<SidebarSeparator />

				<div className="grid grid-cols-12 gap-4">
					<Label className="col-span-12 md:col-span-2" htmlFor="widget_1_heading">
						Widget 1 Heading
					</Label>
					<div className="col-span-12 md:col-span-10">
						<Input
							id="widget_1_heading"
							name="widget_1_heading"
							type="text"
							value={data.widget_1_heading}
							onChange={(e) => setData("widget_1_heading", e.target.value)}
							className="md:w-1/2 w-full"
						/>
						<InputError message={errors.widget_1_heading} className="mt-1" />
					</div>
				</div>

				<div className="grid grid-cols-12 gap-4">
					<Label className="col-span-12 md:col-span-2" htmlFor="widget_1_content">
						Widget 1 Content
					</Label>
					<div className="col-span-12 md:col-span-10">
						<textarea
							id="widget_1_content"
							name="widget_1_content"
							value={data.widget_1_content}
							onChange={(e) => setData("widget_1_content", e.target.value)}
							className="w-full md:w-1/2 border p-2 rounded"
						/>
						<InputError message={errors.widget_1_content} className="mt-1" />
					</div>
				</div>

				<div className="grid grid-cols-12 gap-4">
					<Label className="col-span-12 md:col-span-2" htmlFor="widget_2_heading">
						Widget 2 Heading
					</Label>
					<div className="col-span-12 md:col-span-10">
						<Input
							id="widget_2_heading"
							name="widget_2_heading"
							type="text"
							value={data.widget_2_heading}
							onChange={(e) => setData("widget_2_heading", e.target.value)}
							className="md:w-1/2 w-full"
						/>
						<InputError message={errors.widget_2_heading} className="mt-1" />
					</div>
				</div>

				{/* Example multi-select approach (adjust as needed) */}
				<div className="grid grid-cols-12 gap-4">
					<Label className="col-span-12 md:col-span-2" htmlFor="widget_2_menus">
						Widget 2 Menus
					</Label>
					<div className="col-span-12 md:col-span-10">
						<Select
							value=""
							onValueChange={(val) =>
								setData("widget_2_menus", [...data.widget_2_menus, val])
							}
						>
							<SelectTrigger>
								<SelectValue placeholder="Select Pages" />
							</SelectTrigger>
							<SelectContent>
								<SelectItem value="home">Home</SelectItem>
								<SelectItem value="about">About</SelectItem>
								{/* ...other static options */}
								{pages.map((p) => (
									<SelectItem key={p.slug} value={p.slug}>
										{p.translation?.title || p.slug}
									</SelectItem>
								))}
							</SelectContent>
						</Select>
						<div className="mt-2">
							{data.widget_2_menus.map((m, i) => (
								<span key={i} className="inline-block mr-2">
									{m}
								</span>
							))}
						</div>
						<InputError message={errors.widget_2_menus} className="mt-1" />
					</div>
				</div>

				{/* Repeat for widget_3_heading, widget_3_menus, etc. */}

				<div className="grid grid-cols-12 gap-4">
					<Label className="col-span-12 md:col-span-2" htmlFor="model_language">
						Language
					</Label>
					<div className="col-span-12 md:col-span-10">
						<Select
							value={data.model_language}
							onValueChange={(val) => setData("model_language", val)}
						>
							<SelectTrigger>
								<SelectValue placeholder="Select Language" />
							</SelectTrigger>
							<SelectContent>
								{languages.map((lang) => (
									<SelectItem key={lang.code} value={lang.code}>
										{lang.name}
									</SelectItem>
								))}
							</SelectContent>
						</Select>
						<InputError message={errors.model_language} className="mt-1" />
					</div>
				</div>

				<SidebarSeparator />

				<div className="grid grid-cols-12 gap-4">
					<Label className="col-span-12 md:col-span-2" htmlFor="copyright_text">
						Copyright Text
					</Label>
					<div className="col-span-12 md:col-span-10">
						<Input
							id="copyright_text"
							name="copyright_text"
							type="text"
							value={data.copyright_text}
							onChange={(e) => setData("copyright_text", e.target.value)}
							className="md:w-1/2 w-full"
						/>
						<InputError message={errors.copyright_text} className="mt-1" />
					</div>
				</div>

				<div className="grid grid-cols-12 gap-4">
					<Label className="col-span-12 md:col-span-2" htmlFor="payment_gateway_image">
						Payment Gateway Image
					</Label>
					<div className="col-span-12 md:col-span-10">
						<Input
							id="payment_gateway_image"
							name="payment_gateway_image"
							type="file"
							onChange={(e) => setData("payment_gateway_image", e.target.files[0])}
							className="w-full"
						/>
						<InputError message={errors.payment_gateway_image} className="mt-1" />
					</div>
				</div>

				<div className="grid grid-cols-12 gap-4">
					<Label className="col-span-12 md:col-span-2" htmlFor="custom_css">
						Custom CSS
					</Label>
					<div className="col-span-12 md:col-span-10">
						<textarea
							id="custom_css"
							name="custom_css"
							rows={4}
							value={data.custom_css}
							onChange={(e) => setData("custom_css", e.target.value)}
							className="w-full border p-2 rounded"
						/>
						<InputError message={errors.custom_css} className="mt-1" />
					</div>
				</div>

				<div className="grid grid-cols-12 gap-4">
					<Label className="col-span-12 md:col-span-2" htmlFor="custom_js">
						Custom JS
					</Label>
					<div className="col-span-12 md:col-span-10">
						<textarea
							id="custom_js"
							name="custom_js"
							rows={4}
							value={data.custom_js}
							onChange={(e) => setData("custom_js", e.target.value)}
							className="w-full border p-2 rounded"
							placeholder="Write code without <script> tags"
						/>
						<InputError message={errors.custom_js} className="mt-1" />
					</div>
				</div>

				<div className="mt-4">
					<Button type="submit" disabled={processing}>
						Save Changes
					</Button>
				</div>
			</form>
		</SidebarInset>
	</AuthenticatedLayout>
);
}