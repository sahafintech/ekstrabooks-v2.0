import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PageHeader from "@/Components/PageHeader";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import InputError from "@/Components/InputError";
import { Textarea } from "@/components/ui/textarea";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { toast } from "sonner";
import { Sidebar, SidebarContent } from "@/components/ui/sidebar";

export default function UpdateAboutPage({ pageData, pageMedia, languages }) {
    const { data, setData, post, processing, errors } = useForm({
        pageTitle: pageData?.title || "",
        language:
            pageData?.language || (languages.length ? languages[0].code : ""),
        section1Heading: pageData?.section1Heading || "",
        section1Content: pageData?.section_1_content || "",
        aboutImage: null,
        section2Heading: pageData?.section_2_heading || "",
        section2Content: pageData?.section_2_content || "",
        section3Heading: pageData?.section_3_heading || "",
        section3Content: pageData?.section_3_content || "",
        teamHeading: pageData?.team_heading || "",
        teamSubHeading: pageData?.team_sub_heading || "",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("pages.default_pages.store", "home"), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("About Page updated successfully");
            },
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
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
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
                                    {errors.language && (
                                        <InputError
                                            message={errors.language}
                                            className="text-sm"
                                        />
                                    )}
                                </div>
                            </div>

                            {/* Section 1 Heading */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="section1Heading"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Section 1 Heading
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                    <Input
                                        id="section1Heading"
                                        type="text"
                                        name="section1Heading"
                                        value={data.section1Heading}
                                        onChange={(e) =>
                                            setData(
                                                "section1Heading",
                                                e.target.value
                                            )
                                        }
                                        required
                                    />
                                    {errors.section1Heading && (
                                        <InputError
                                            message={errors.section1Heading}
                                            className="text-sm"
                                        />
                                    )}
                                </div>
                            </div>

                            {/* Section 1 Content */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="section1Content"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Section 1 Content
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
                                    <Textarea
                                        id="section1Content"
                                        name="section1Content"
                                        className="w-full p-2 border rounded h-56"
                                        value={data.section1Content}
                                        onChange={(e) =>
                                            setData(
                                                "section1Content",
                                                e.target.value
                                            )
                                        }
                                    />
                                    {errors.section1Content && (
                                        <InputError
                                            message={errors.section1Content}
                                            className="text-sm"
                                        />
                                    )}
                                </div>
                            </div>

                            {/* About Image */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="aboutImage"
                                    className="md:col-span-2 col-span-12"
                                >
                                    About Image
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                    <Input
                                        id="aboutImage"
                                        type="file"
                                        name="aboutImage"
                                        className="md:w-full w-1/2"
                                        onChange={(e) =>
                                            setData(
                                                "aboutImage",
                                                e.target.files[0]
                                            )
                                        }
                                    />
                                    {pageMedia?.about_image && (
                                        <img
                                            src={asset(
                                                "/uploads/media/" +
                                                    pageMedia.about_image
                                            )}
                                            alt="About"
                                            className="mt-2 h-20"
                                        />
                                    )}
                                    {errors.aboutImage && (
                                        <InputError
                                            message={errors.aboutImage}
                                            className="text-sm"
                                        />
                                    )}
                                </div>
                            </div>

                            {/* Section 2 Heading */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="section2Heading"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Section 2 Heading
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                    <Input
                                        id="section2Heading"
                                        type="text"
                                        name="section2Heading"
                                        value={data.section2Heading}
                                        onChange={(e) =>
                                            setData(
                                                "section2Heading",
                                                e.target.value
                                            )
                                        }
                                        required
                                    />
                                    {errors.section2Heading && (
                                        <InputError
                                            message={errors.section2Heading}
                                            className="text-sm"
                                        />
                                    )}
                                </div>
                            </div>

                            {/* Section 2 Content */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="section2Content"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Section 2 Content
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
                                    <Textarea
                                        id="section2Content"
                                        name="section2Content"
                                        className="w-full p-2 border rounded h-56"
                                        value={data.section2Content}
                                        onChange={(e) =>
                                            setData(
                                                "section2Content",
                                                e.target.value
                                            )
                                        }
                                    />
                                    {errors.section2Content && (
                                        <InputError
                                            message={errors.section2Content}
                                            className="text-sm"
                                        />
                                    )}
                                </div>
                            </div>

                            {/* Section 3 Heading */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="section3Heading"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Section 3 Heading
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                    <Input
                                        id="section3Heading"
                                        type="text"
                                        name="section3Heading"
                                        value={data.section3Heading}
                                        onChange={(e) =>
                                            setData(
                                                "section3Heading",
                                                e.target.value
                                            )
                                        }
                                        required
                                    />
                                    {errors.section3Heading && (
                                        <InputError
                                            message={errors.section3Heading}
                                            className="text-sm"
                                        />
                                    )}
                                </div>
                            </div>

                            {/* Section 3 Content */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="section3Content"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Section 3 Content
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
                                    <Textarea
                                        id="section3Content"
                                        name="section3Content"
                                        className="w-full p-2 border rounded h-56"
                                        value={data.section3Content}
                                        onChange={(e) =>
                                            setData(
                                                "section3Content",
                                                e.target.value
                                            )
                                        }
                                    />
                                    {errors.section3Content && (
                                        <InputError
                                            message={errors.section3Content}
                                            className="text-sm"
                                        />
                                    )}
                                </div>
                            </div>

                            {/* Team Heading */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="teamHeading"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Team Heading
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                    <Input
                                        id="teamHeading"
                                        type="text"
                                        name="teamHeading"
                                        value={data.teamHeading}
                                        onChange={(e) =>
                                            setData(
                                                "teamHeading",
                                                e.target.value
                                            )
                                        }
                                        required
                                    />
                                    {errors.teamHeading && (
                                        <InputError
                                            message={errors.teamHeading}
                                            className="text-sm"
                                        />
                                    )}
                                </div>
                            </div>

                            {/* Team Sub Heading */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="teamSubHeading"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Team Sub Heading
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                    <Input
                                        id="teamSubHeading"
                                        type="text"
                                        name="teamSubHeading"
                                        value={data.teamSubHeading}
                                        onChange={(e) =>
                                            setData(
                                                "teamSubHeading",
                                                e.target.value
                                            )
                                        }
                                    />
                                    {errors.teamSubHeading && (
                                        <InputError
                                            message={errors.teamSubHeading}
                                            className="text-sm"
                                        />
                                    )}
                                </div>
                            </div>

                            {/* Submit Button */}
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
