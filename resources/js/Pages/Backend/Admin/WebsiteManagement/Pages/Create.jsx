import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { Textarea } from "@/Components/ui/textarea";
import { SidebarInset } from "@/Components/ui/sidebar";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";

export default function Create({ languages }) {
const { data, setData, post, processing, errors, reset } = useForm({
	title: "",
	body: "",
	status: 1,
	model_language: "",
});

const submit = (e) => {
	e.preventDefault();
	post(route("pages.store"), {
		preserveScroll: true,
		onSuccess: () => {
			toast.success("Page created successfully");
			reset();
		},
	});
};

return (
	<AuthenticatedLayout>
		<SidebarInset>
			<PageHeader page="Website" subpage="Create" url="pages.index" />

			<div className="flex flex-1 flex-col gap-4 p-4 pt-0">
				<form
					onSubmit={submit}
					className="validate"
					autoComplete="off"
					encType="multipart/form-data"
				>
					<div className="grid grid-cols-12 mt-2">
						<Label
							htmlFor="title"
							className="md:col-span-2 col-span-12"
						>
							Title
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<Input
								id="title"
								type="text"
								name="title"
								value={data.title}
								className="md:w-1/2 w-full"
								required
								onChange={(e) =>
									setData("title", e.target.value)
								}
							/>
							<InputError
								message={errors.title}
								className="text-sm"
							/>
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label
							htmlFor="body"
							className="md:col-span-2 col-span-12"
						>
							Body
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<Textarea
								id="body"
								name="body"
								value={data.body}
								className="h-64"
								required
								onChange={(e) =>
									setData("body", e.target.value)
								}
							/>
							<InputError
								message={errors.body}
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
							<div className="md:w-1/2 w-full">
								<Select
									id="status"
									name="status"
									value={data.status}
									onValueChange={(value) =>
										setData("status", value)
									}
								>
									<SelectTrigger>
										<SelectValue>
											{data.status === "1"
												? "Publish"
												: "Draft"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										<SelectItem value="1">
											Publish
										</SelectItem>
										<SelectItem value="0">
											Draft
										</SelectItem>
									</SelectContent>
								</Select>
							</div>
							<InputError
								message={errors.status}
								className="text-sm"
							/>
						</div>
					</div>

					<div className="grid grid-cols-12 mt-2">
						<Label
							htmlFor="model_language"
							className="md:col-span-2 col-span-12"
						>
							Language
						</Label>
						<div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
							<div className="md:w-1/2 w-full">
								<Select
									id="model_language"
									name="model_language"
									value={data.model_language}
									onValueChange={(value) =>
										setData("model_language", value)
									}
								>
									<SelectTrigger>
										<SelectValue>
											{data.model_language ||
												"Select Language"}
										</SelectValue>
									</SelectTrigger>
									<SelectContent>
										{languages.map((language) => (
											<SelectItem
												key={language.code}
												value={language.code}
											>
												{language.label}
											</SelectItem>
										))}
									</SelectContent>
								</Select>
							</div>
							<InputError
								message={errors.model_language}
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