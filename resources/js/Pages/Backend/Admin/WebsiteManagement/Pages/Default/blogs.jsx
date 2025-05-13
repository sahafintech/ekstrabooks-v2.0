import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PageHeader from "@/Components/PageHeader";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Button } from "@/Components/ui/button";
import InputError from "@/Components/InputError";
import { toast } from "sonner";
import { Sidebar, SidebarContent } from "@/Components/ui/sidebar";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";

export default function UpdateBlogsPage({ pageData, languages }) {
    const { data, setData, post, processing, errors } = useForm({
        pageTitle: pageData?.title || "",
        language:
            pageData?.language || (languages.length ? languages[0].code : ""),
        blogsHeading: pageData?.blogs_heading || "",
        blogsSubHeading: pageData?.blogs_sub_heading || "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("pages.default_pages.store", "home"), {
            preserveScroll: true,
            onSuccess: () => toast.success("Blogs Page updated successfully"),
        });
    };

    return (
        <AuthenticatedLayout>
            <Sidebar>
                <SidebarContent>
                    <PageHeader
                        page="Website"
                        subpage="Default Pages"
                        url="pages.index"
                    />
                    <div className="flex flex-col p-4 gap-4">
                        <form
                            onSubmit={submit}
                            encType="multipart/form-data"
                            autoComplete="off"
                            className="validate"
                        >
                            {/* Title */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="pageTitle"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Title
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                    <Input
                                        id="pageTitle"
                                        type="text"
                                        name="pageTitle"
                                        value={data.pageTitle}
                                        onChange={(e) =>
                                            setData("pageTitle", e.target.value)
                                        }
                                        required
                                    />
                                    {errors.pageTitle && (
                                        <InputError
                                            message={errors.pageTitle}
                                            className="text-sm"
                                        />
                                    )}
                                </div>
                            </div>

                            {/* Language */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="language"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Language
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
                                    <div className="md:w-1/2 w-full">
                                        <Select
                                            id="language"
                                            name="language"
                                            value={data.language}
                                            onValueChange={(val) =>
                                                setData("language", val)
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue>
                                                    {languages.find(
                                                        (lang) =>
                                                            lang.code ===
                                                            data.language
                                                    )?.label || "Select One"}
                                                </SelectValue>
                                            </SelectTrigger>
                                            <SelectContent>
                                                {languages.map((lang) => (
                                                    <SelectItem
                                                        key={lang.code}
                                                        value={lang.code}
                                                    >
                                                        {lang.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    {errors.language && (
                                        <InputError
                                            message={errors.language}
                                            className="text-sm"
                                        />
                                    )}
                                </div>
                            </div>

                            {/* Blogs Heading */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="blogsHeading"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Blogs Heading
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                    <Input
                                        id="blogsHeading"
                                        type="text"
                                        name="blogsHeading"
                                        value={data.blogsHeading}
                                        onChange={(e) =>
                                            setData(
                                                "blogsHeading",
                                                e.target.value
                                            )
                                        }
                                        required
                                    />
                                    {errors.blogsHeading && (
                                        <InputError
                                            message={errors.blogsHeading}
                                            className="text-sm"
                                        />
                                    )}
                                </div>
                            </div>

                            {/* Blogs Sub Heading */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="blogsSubHeading"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Blogs Sub Heading
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                    <Input
                                        id="blogsSubHeading"
                                        type="text"
                                        name="blogsSubHeading"
                                        value={data.blogsSubHeading}
                                        onChange={(e) =>
                                            setData(
                                                "blogsSubHeading",
                                                e.target.value
                                            )
                                        }
                                        required
                                    />
                                    {errors.blogsSubHeading && (
                                        <InputError
                                            message={errors.blogsSubHeading}
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
                </SidebarContent>
            </Sidebar>
        </AuthenticatedLayout>
    );
}
