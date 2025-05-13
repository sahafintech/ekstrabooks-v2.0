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
import { Textarea } from "@/Components/ui/textarea";
import { Trash2 } from "lucide-react";

export default function UpdateContactPage({ pageData, languages }) {
    const { data, setData, post, processing, errors } = useForm({
        pageTitle: pageData?.title || "",
        language:
            pageData?.language || (languages.length ? languages[0].code : ""),
        contactFormHeading: pageData?.contact_form_heading || "",
        contactFormSubHeading: pageData?.contact_form_sub_heading || "",
        contactInfo:
            pageData?.contact_info_heading?.map((heading, index) => ({
                heading,
                content: pageData.contact_info_content[index],
            })) || [],
        facebookLink: pageData?.facebook_link || "",
        linkedinLink: pageData?.linkedin_link || "",
        twitterLink: pageData?.twitter_link || "",
        youtubeLink: pageData?.youtube_link || "",
    });

    const addContactInfoRow = () => {
        setData("contactInfo", [
            ...data.contactInfo,
            { heading: "", content: "" },
        ]);
    };

    const removeContactInfoRow = (index) => {
        setData(
            "contactInfo",
            data.contactInfo.filter((_, i) => i !== index)
        );
    };

    const submit = (e) => {
        e.preventDefault();
        post(route("pages.default_pages.store", "home"), {
            preserveScroll: true,
            onSuccess: () => toast.success("Contact Page updated successfully"),
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

                        {/* Contact Form Heading */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="contactFormHeading"
                                className="md:col-span-2 col-span-12"
                            >
                                Contact Form Heading
                            </Label>
                            <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                <Input
                                    id="contactFormHeading"
                                    type="text"
                                    name="contactFormHeading"
                                    value={data.contactFormHeading}
                                    onChange={(e) =>
                                        setData(
                                            "contactFormHeading",
                                            e.target.value
                                        )
                                    }
                                    required
                                />
                                {errors.contactFormHeading && (
                                    <InputError
                                        message={errors.contactFormHeading}
                                        className="text-sm"
                                    />
                                )}
                            </div>
                        </div>

                        {/* Contact Form Sub Heading */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="contactFormSubHeading"
                                className="md:col-span-2 col-span-12"
                            >
                                Contact Form Sub Heading
                            </Label>
                            <div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
                                <Textarea
                                    id="contactFormSubHeading"
                                    name="contactFormSubHeading"
                                    value={data.contactFormSubHeading}
                                    className="h-36"
                                    onChange={(e) =>
                                        setData(
                                            "contactFormSubHeading",
                                            e.target.value
                                        )
                                    }
                                    required
                                />
                                {errors.contactFormSubHeading && (
                                    <InputError
                                        message={errors.contactFormSubHeading}
                                        className="text-sm"
                                    />
                                )}
                            </div>
                        </div>

                        {/* Contact Informations */}
                        <div className="col-span-12 my-4 flex items-center justify-between">
                            <h5>
                                <b>Contact Informations</b>
                            </h5>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={addContactInfoRow}
                            >
                                Add Row
                            </Button>
                        </div>

                        {data.contactInfo.map((info, index) => (
                            <div
                                key={index}
                                className="grid grid-cols-12 gap-4 mt-2"
                            >
                                <div className="col-span-12">
                                    <div className="flex justify-between">
                                        <Label>Heading</Label>
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            onClick={() =>
                                                removeContactInfoRow(index)
                                            }
                                        >
                                            Remove
                                        </Button>
                                    </div>
                                    <div className="md:w-1/2 w-full">
                                        <Input
                                            type="text"
                                            name={`contactInfo[${index}].heading`}
                                            value={info.heading}
                                            onChange={(e) =>
                                                setData("contactInfo", [
                                                    ...data.contactInfo.slice(
                                                        0,
                                                        index
                                                    ),
                                                    {
                                                        ...info,
                                                        heading: e.target.value,
                                                    },
                                                    ...data.contactInfo.slice(
                                                        index + 1
                                                    ),
                                                ])
                                            }
                                            required
                                        />
                                    </div>
                                </div>
                                <div className="col-span-12 mt-2">
                                    <Label>Content</Label>
                                    <Textarea
                                        className="w-full, h-36"
                                        name={`contactInfo[${index}].content`}
                                        value={info.content}
                                        onChange={(e) =>
                                            setData("contactInfo", [
                                                ...data.contactInfo.slice(
                                                    0,
                                                    index
                                                ),
                                                {
                                                    ...info,
                                                    content: e.target.value,
                                                },
                                                ...data.contactInfo.slice(
                                                    index + 1
                                                ),
                                            ])
                                        }
                                        required
                                    />
                                </div>
                            </div>
                        ))}

                        {/* Social Links */}
                        <div className="grid grid-cols-12 mt-4">
                            <Label
                                htmlFor="facebookLink"
                                className="md:col-span-2 col-span-12"
                            >
                                Facebook Link
                            </Label>
                            <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                <Input
                                    id="facebookLink"
                                    type="text"
                                    name="facebookLink"
                                    value={data.facebookLink}
                                    onChange={(e) =>
                                        setData("facebookLink", e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-4">
                            <Label
                                htmlFor="linkedinLink"
                                className="md:col-span-2 col-span-12"
                            >
                                Linkedin Link
                            </Label>
                            <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                <Input
                                    id="linkedinLink"
                                    type="text"
                                    name="linkedinLink"
                                    value={data.linkedinLink}
                                    onChange={(e) =>
                                        setData("linkedinLink", e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-4">
                            <Label
                                htmlFor="twitterLink"
                                className="md:col-span-2 col-span-12"
                            >
                                Twitter Link
                            </Label>
                            <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                <Input
                                    id="twitterLink"
                                    type="text"
                                    name="twitterLink"
                                    value={data.twitterLink}
                                    onChange={(e) =>
                                        setData("twitterLink", e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-4">
                            <Label
                                htmlFor="youtubeLink"
                                className="md:col-span-2 col-span-12"
                            >
                                Youtube Link
                            </Label>
                            <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                <Input
                                    id="youtubeLink"
                                    type="text"
                                    name="youtubeLink"
                                    value={data.youtubeLink}
                                    onChange={(e) =>
                                        setData("youtubeLink", e.target.value)
                                    }
                                />
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