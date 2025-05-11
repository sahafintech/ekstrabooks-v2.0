import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PageHeader from "@/Components/PageHeader";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import InputError from "@/Components/InputError";
import { Textarea } from "@/Components/ui/textarea";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { toast } from "sonner";
import { SidebarInset } from "@/Components/ui/sidebar";

export default function UpdateFeaturesPage({ pageData, languages }) {
    const { data, setData, post, processing, errors } = useForm({
        pageTitle: pageData?.title || "",
        language: pageData?.language || (languages.length ? languages[0].code : ""),
        featuresHeading: pageData?.features_heading || "",
        featuresSubHeading: pageData?.features_sub_heading || "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("pages.default_pages.store", "home"), {
            preserveScroll: true,
            onSuccess: () => toast.success("Features Page updated successfully"),
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Website"
                    subpage="Default Pages"
                    url="pages.index"
                />
                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        {/* Title */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="pageTitle" className="md:col-span-2 col-span-12">
                                Title *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="pageTitle"
                                    type="text"
                                    value={data.pageTitle}
                                    onChange={(e) => setData("pageTitle", e.target.value)}
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError message={errors.pageTitle} className="text-sm" />
                            </div>
                        </div>

                        {/* Language */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="language" className="md:col-span-2 col-span-12">
                                Language *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <Select
                                        value={data.language}
                                        onValueChange={(val) => setData("language", val)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue>
                                                {languages.find(
                                                    (lang) => lang.code === data.language
                                                )?.label || "Select One"}
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
                                <InputError message={errors.language} className="text-sm" />
                            </div>
                        </div>

                        {/* Features Heading */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="featuresHeading" className="md:col-span-2 col-span-12">
                                Features Heading *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="featuresHeading"
                                    type="text"
                                    value={data.featuresHeading}
                                    onChange={(e) => setData("featuresHeading", e.target.value)}
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError message={errors.featuresHeading} className="text-sm" />
                            </div>
                        </div>

                        {/* Features Sub Heading */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="featuresSubHeading" className="md:col-span-2 col-span-12">
                                Features Sub Heading *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea
                                    id="featuresSubHeading"
                                    value={data.featuresSubHeading}
                                    onChange={(e) => setData("featuresSubHeading", e.target.value)}
                                    className="md:w-1/2 w-full h-32"
                                    required
                                />
                                <InputError message={errors.featuresSubHeading} className="text-sm" />
                            </div>
                        </div>

                        <Button type="submit" disabled={processing} className="mt-4">
                            {processing ? "Saving..." : "Save Changes"}
                        </Button>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
