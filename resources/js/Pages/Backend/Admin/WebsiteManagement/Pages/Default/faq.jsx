import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PageHeader from "@/Components/PageHeader";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import InputError from "@/Components/InputError";
import { toast } from "sonner";
import { SidebarInset } from "@/Components/ui/sidebar";
import {

Select,
SelectContent,
SelectItem,
SelectTrigger,
SelectValue,
} from "@/Components/ui/select";

export default function UpdateFaqPage({ pageData, languages }) {
const { data, setData, post, processing, errors } = useForm({
	title: pageData?.title || "",
	language: pageData?.language || (languages.length ? languages[0].code : ""),
	faqHeading: pageData?.faq_heading || "",
	faqSubHeading: pageData?.faq_sub_heading || "",
});

const submit = (e) => {
	e.preventDefault();
	post(route("pages.default_pages.store", "home"), {
		preserveScroll: true,
		onSuccess: () => toast.success("FAQ Page updated successfully"),
	});
};

return (
	<AuthenticatedLayout>
		<SidebarInset>
			<PageHeader page="Website" subpage="Default Pages" url="pages.index" />
			<div className="flex flex-col p-4 gap-4">
				<form
					onSubmit={submit}
					encType="multipart/form-data"
					autoComplete="off"
					className="validate"
				>
					{/* Title */}
					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="title" className="md:col-span-2 col-span-12">
							Title
						</Label>
						<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
							<Input
								id="title"
								type="text"
								name="title"
								value={data.title}
								onChange={(e) => setData("title", e.target.value)}
								required
							/>
							{errors.title && (
								<InputError message={errors.title} className="text-sm" />
							)}
						</div>
					</div>

					{/* Language */}
					<div className="grid grid-cols-12 mt-2">
						<Label htmlFor="language" className="md:col-span-2 col-span-12">
							Language
						</Label>
						<div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
							<div className="md:w-1/2 w-full">
								<Select
									id="language"
									name="language"
									value={data.language}
									onValueChange={(val) => setData("language", val)}
								>
									<SelectTrigger>
										<SelectValue>
											{languages.find((lang) => lang.code === data.language)
												?.label || "Select One"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										{languages.map((lang) => (
											<SelectItem key={lang.code} value={lang.code}>
												{lang.label}
											</SelectItem>
										))}
									</SelectContent>
								</Select>
							</div>
							{errors.language && (
								<InputError message={errors.language} className="text-sm" />
							)}
						</div>
					</div>

					{/* FAQ Heading */}
					<div className="grid grid-cols-12 mt-2">
						<Label
							htmlFor="faqHeading"
							className="md:col-span-2 col-span-12"
						>
							FAQ Heading
						</Label>
						<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
							<Input
								id="faqHeading"
								type="text"
								name="faqHeading"
								value={data.faqHeading}
								onChange={(e) => setData("faqHeading", e.target.value)}
								required
							/>
							{errors.faqHeading && (
								<InputError message={errors.faqHeading} className="text-sm" />
							)}
						</div>
					</div>

					{/* FAQ Sub Heading */}
					<div className="grid grid-cols-12 mt-2">
						<Label
							htmlFor="faqSubHeading"
							className="md:col-span-2 col-span-12"
						>
							FAQ Sub Heading
						</Label>
						<div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
							<Input
								id="faqSubHeading"
								type="text"
								name="faqSubHeading"
								value={data.faqSubHeading}
								onChange={(e) => setData("faqSubHeading", e.target.value)}
								required
							/>
							{errors.faqSubHeading && (
								<InputError
									message={errors.faqSubHeading}
									className="text-sm"
								/>
							)}
						</div>
					</div>

					{/* Submit */}
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