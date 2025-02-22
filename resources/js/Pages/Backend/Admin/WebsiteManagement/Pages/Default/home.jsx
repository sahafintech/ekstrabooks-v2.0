import React from "react";
import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Label } from "@/components/ui/label";
import { Sidebar, SidebarContent } from "@/components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/components/ui/button";
import { toast } from "sonner";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

export default function Home({ pageData = {}, pageMedia = {} }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: pageData.title || "",
        model_language: "",
        hero_heading: pageData.hero_heading || "",
        hero_sub_heading: pageData.hero_sub_heading || "",
        get_started_text: pageData.get_started_text || "",
        get_started_link: pageData.get_started_link || "",
        features_status: pageData.features_status || "1",
        features_heading: pageData.features_heading || "",
        features_sub_heading: pageData.features_sub_heading || "",
        pricing_status: pageData.pricing_status || "1",
        pricing_heading: pageData.pricing_heading || "",
        pricing_sub_heading: pageData.pricing_sub_heading || "",
        blog_status: pageData.blog_status || "1",
        blog_heading: pageData.blog_heading || "",
        blog_sub_heading: pageData.blog_sub_heading || "",
        testimonials_status: pageData.testimonials_status || "1",
        testimonials_heading: pageData.testimonials_heading || "",
        testimonials_sub_heading: pageData.testimonials_sub_heading || "",
        newsletter_status: pageData.newsletter_status || "1",
        newsletter_heading: pageData.newsletter_heading || "",
        newsletter_sub_heading: pageData.newsletter_sub_heading || "",
        hero_bg_image: null,
        newsletter_image: null,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("pages.default_pages.store", "home"), {
            onSuccess: () => {
                toast.success("Home page updated.");
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Sidebar>
                <SidebarContent>
                    <PageHeader
                        page="Website"
                        subpage="Home"
                        url="pages.default_pages"
                    />
                    <div className="flex flex-col p-4 gap-4">
                        <form onSubmit={submit} encType="multipart/form-data">
                            {/* Title */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="title"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Title
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
                                    <Input
                                        id="title"
                                        type="text"
                                        name="title"
                                        value={data.title}
                                        onChange={(e) =>
                                            setData("title", e.target.value)
                                        }
                                        className="md:w-1/2 w-full"
                                    />
                                    <InputError message={errors.title} />
                                </div>
                            </div>

                            {/* Language */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="model_language"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Language
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                    <Select
                                        id="model_language"
                                        name="model_language"
                                        value={data.model_language}
                                        onValueChange={(val) =>
                                            setData("model_language", val)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select One" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {/* Example items (replace with real data) */}
                                            <SelectItem value="en">English</SelectItem>
                                            <SelectItem value="ar">Arabic</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.model_language} />
                                </div>
                            </div>

                            {/* Hero Heading */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="hero_heading"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Hero Heading
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
                                    <Input
                                        id="hero_heading"
                                        type="text"
                                        name="hero_heading"
                                        value={data.hero_heading}
                                        onChange={(e) =>
                                            setData("hero_heading", e.target.value)
                                        }
                                        className="md:w-1/2 w-full"
                                    />
                                    <InputError message={errors.hero_heading} />
                                </div>
                            </div>

                            {/* Hero Sub Heading */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="hero_sub_heading"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Hero Sub Heading
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
                                    <Input
                                        id="hero_sub_heading"
                                        type="text"
                                        name="hero_sub_heading"
                                        value={data.hero_sub_heading}
                                        onChange={(e) =>
                                            setData(
                                                "hero_sub_heading",
                                                e.target.value
                                            )
                                        }
                                        className="md:w-1/2 w-full"
                                    />
                                    <InputError
                                        message={errors.hero_sub_heading}
                                    />
                                </div>
                            </div>

                            {/* Get Started Text */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="get_started_text"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Get Started Text
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
                                    <Input
                                        id="get_started_text"
                                        type="text"
                                        name="get_started_text"
                                        value={data.get_started_text}
                                        onChange={(e) =>
                                            setData(
                                                "get_started_text",
                                                e.target.value
                                            )
                                        }
                                        className="md:w-1/2 w-full"
                                    />
                                    <InputError
                                        message={errors.get_started_text}
                                    />
                                </div>
                            </div>

                            {/* Get Started Link */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="get_started_link"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Get Started Link
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
                                    <Input
                                        id="get_started_link"
                                        type="text"
                                        name="get_started_link"
                                        value={data.get_started_link}
                                        onChange={(e) =>
                                            setData(
                                                "get_started_link",
                                                e.target.value
                                            )
                                        }
                                        className="md:w-1/2 w-full"
                                    />
                                    <InputError
                                        message={errors.get_started_link}
                                    />
                                </div>
                            </div>

                            {/* Hero Image */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="hero_bg_image"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Hero Image
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
                                    <Input
                                        id="hero_bg_image"
                                        type="file"
                                        name="hero_bg_image"
                                        onChange={(e) =>
                                            setData(
                                                "hero_bg_image",
                                                e.target.files[0]
                                            )
                                        }
                                        className="md:w-1/2 w-full"
                                    />
                                    <InputError
                                        message={errors.hero_bg_image}
                                    />
                                </div>
                            </div>

                            {/* Features Status */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="features_status"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Features Section
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0 md:w-1/2 w-full">
                                    <Select
                                        id="features_status"
                                        name="features_status"
                                        value={String(data.features_status)}
                                        onValueChange={(val) =>
                                            setData("features_status", val)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">Active</SelectItem>
                                            <SelectItem value="0">Disabled</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.features_status}
                                    />
                                </div>
                            </div>

                            {/* Features Heading */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="features_heading"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Features Heading
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
                                    <Input
                                        id="features_heading"
                                        type="text"
                                        name="features_heading"
                                        value={data.features_heading}
                                        onChange={(e) =>
                                            setData(
                                                "features_heading",
                                                e.target.value
                                            )
                                        }
                                        className="md:w-1/2 w-full"
                                    />
                                    <InputError
                                        message={errors.features_heading}
                                    />
                                </div>
                            </div>

                            {/* Newsletter Image */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="newsletter_image"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Newsletter Image
                                </Label>
                                <div className="md:col-span-10 col-span-12 mt-2 md:mt-0">
                                    <Input
                                        id="newsletter_image"
                                        type="file"
                                        name="newsletter_image"
                                        onChange={(e) =>
                                            setData(
                                                "newsletter_image",
                                                e.target.files[0]
                                            )
                                        }
                                        className="md:w-1/2 w-full"
                                    />
                                    <InputError
                                        message={errors.newsletter_image}
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
                </SidebarContent>
            </Sidebar>
        </AuthenticatedLayout>
    );
}
}
}
}
}
}
}