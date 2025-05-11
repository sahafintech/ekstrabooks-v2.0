import React from "react";
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

export default function Home({ pageData = {} }) {
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
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Home page updated successfully");
                reset();
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Website"
                    subpage="Home"
                    url="pages.default_pages"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit} encType="multipart/form-data">
                        {/* Title */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="title"
                                className="md:col-span-2 col-span-12"
                            >
                                Title *
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="title"
                                    type="text"
                                    name="title"
                                    value={data.title}
                                    onChange={(e) =>
                                        setData("title", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                    required
                                />
                                <InputError message={errors.title} className="text-sm" />
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
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
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
                                            <SelectItem value="en">English</SelectItem>
                                            <SelectItem value="ar">Arabic</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <InputError message={errors.model_language} className="text-sm" />
                            </div>
                        </div>

                        <SidebarSeparator className="my-4" />

                        {/* Hero Section */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="hero_heading"
                                className="md:col-span-2 col-span-12"
                            >
                                Hero Heading
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
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
                                <InputError message={errors.hero_heading} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="hero_sub_heading"
                                className="md:col-span-2 col-span-12"
                            >
                                Hero Sub Heading
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="hero_sub_heading"
                                    type="text"
                                    name="hero_sub_heading"
                                    value={data.hero_sub_heading}
                                    onChange={(e) =>
                                        setData("hero_sub_heading", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.hero_sub_heading} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="get_started_text"
                                className="md:col-span-2 col-span-12"
                            >
                                Get Started Text
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="get_started_text"
                                    type="text"
                                    name="get_started_text"
                                    value={data.get_started_text}
                                    onChange={(e) =>
                                        setData("get_started_text", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.get_started_text} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="get_started_link"
                                className="md:col-span-2 col-span-12"
                            >
                                Get Started Link
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="get_started_link"
                                    type="text"
                                    name="get_started_link"
                                    value={data.get_started_link}
                                    onChange={(e) =>
                                        setData("get_started_link", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.get_started_link} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="hero_bg_image"
                                className="md:col-span-2 col-span-12"
                            >
                                Hero Image
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="hero_bg_image"
                                    type="file"
                                    name="hero_bg_image"
                                    onChange={(e) =>
                                        setData("hero_bg_image", e.target.files[0])
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.hero_bg_image} className="text-sm" />
                            </div>
                        </div>

                        <SidebarSeparator className="my-4" />

                        {/* Features Section */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="features_status"
                                className="md:col-span-2 col-span-12"
                            >
                                Features Section
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
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
                                </div>
                                <InputError message={errors.features_status} className="text-sm" />
                            </div>
                        </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="features_heading"
                                className="md:col-span-2 col-span-12"
                            >
                                Features Heading
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="features_heading"
                                    type="text"
                                    name="features_heading"
                                    value={data.features_heading}
                                    onChange={(e) =>
                                        setData("features_heading", e.target.value)
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.features_heading} className="text-sm" />
                            </div>
                        </div>

                        <SidebarSeparator className="my-4" />

                        {/* Newsletter Section */}
                        <div className="grid grid-cols-12 mt-2">
                            <Label
                                htmlFor="newsletter_image"
                                className="md:col-span-2 col-span-12"
                            >
                                Newsletter Image
                            </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="newsletter_image"
                                    type="file"
                                    name="newsletter_image"
                                    onChange={(e) =>
                                        setData("newsletter_image", e.target.files[0])
                                    }
                                    className="md:w-1/2 w-full"
                                />
                                <InputError message={errors.newsletter_image} className="text-sm" />
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
            </SidebarInset>
        </AuthenticatedLayout>
    );
}