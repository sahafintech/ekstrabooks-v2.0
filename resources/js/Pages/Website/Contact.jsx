import { Head } from "@inertiajs/react";
import { useState } from "react";
import { useForm } from "@inertiajs/react";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/Components/ui/card";
import { Loader2, Mail, MapPin, Phone, Clock, MessageSquare } from "lucide-react";
import WebsiteLayout from "@/Layouts/WebsiteLayout";

export default function Contact({ pageData }) {
    const [status, setStatus] = useState("idle");
    const { data, setData, post, processing, errors } = useForm({
        name: "",
        email: "",
        subject: "",
        message: "",
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        setStatus("submitting");
        
        post(route("contact.send"), {
            onSuccess: () => {
                setStatus("success");
                setData({
                    name: "",
                    email: "",
                    subject: "",
                    message: "",
                });
            },
            onError: () => {
                setStatus("error");
            },
        });
    };

    const contactInfo = [
        {
            icon: <Mail className="h-6 w-6" />,
            title: "Email us",
            description: "Our friendly team is here to help.",
            value: "info@ekstrabooks.com",
            link: "mailto:info@ekstrabooks.com",
        },
        {
            icon: <MapPin className="h-6 w-6" />,
            title: "Office",
            description: "Come say hello at our office.",
            value: "Qaalib Building Floor 1,Hargeisa,Somalia",
            link: "https://maps.google.com",
        },
        {
            icon: <Phone className="h-6 w-6" />,
            title: "Phone",
            description: "Mon-Fri from 8am to 5pm.",
            value: "+252 63 805 9999",
            link: "tel:+252638059999",
        },
        {
            icon: <Clock className="h-6 w-6" />,
            title: "Working hours",
            description: "We're here for you",
            value: "Sunday to Thursday, 9am - 5pm CET",
        },
    ];

    return (
        <WebsiteLayout>
            <div className="relative">
                {/* Background Pattern */}
                <div className="absolute inset-0 grid grid-cols-2 -space-x-52 opacity-40 dark:opacity-20">
                    <div className="blur-[106px] h-56 bg-gradient-to-br from-primary to-purple-400 dark:from-blue-700"></div>
                    <div className="blur-[106px] h-32 bg-gradient-to-r from-cyan-400 to-sky-300 dark:to-indigo-600"></div>
                </div>

                <div className="container relative px-4 py-24">
                    {/* Header */}
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <h1 className="text-4xl font-bold tracking-tight sm:text-5xl mb-4">
                            {pageData?.contact_form_heading || "Get in touch"}
                        </h1>
                        <p className="text-lg text-muted-foreground">
                            {pageData?.contact_form_sub_heading || 
                            "Have a question? Need help? Don't hesitate, drop us a line"}
                        </p>
                    </div>

                    <div className="grid lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
                        {/* Contact Info Cards */}
                        <div className="lg:col-span-1 space-y-4">
                            {contactInfo.map((item, i) => (
                                <Card key={i} className="border-none shadow-none bg-muted/50">
                                    <CardHeader className="flex flex-row items-center gap-4 space-y-0 pb-2">
                                        <div className="w-12 h-12 flex items-center justify-center rounded-lg bg-primary/10 text-primary">
                                            {item.icon}
                                        </div>
                                        <div>
                                            <CardTitle className="text-lg">{item.title}</CardTitle>
                                            <CardDescription>{item.description}</CardDescription>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        {item.link ? (
                                            <a 
                                                href={item.link}
                                                className="text-foreground hover:text-primary transition-colors"
                                            >
                                                {item.value}
                                            </a>
                                        ) : (
                                            <span>{item.value}</span>
                                        )}
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        {/* Contact Form */}
                        <div className="lg:col-span-2">
                            <Card className="p-6">
                                <form onSubmit={handleSubmit} className="space-y-6">
                                    <div className="grid sm:grid-cols-2 gap-6">
                                        <div className="space-y-2">
                                            <label htmlFor="name" className="text-sm font-medium">
                                                Name
                                            </label>
                                            <Input
                                                id="name"
                                                name="name"
                                                value={data.name}
                                                onChange={e => setData('name', e.target.value)}
                                                placeholder="John Doe"
                                                className="w-full"
                                                disabled={processing}
                                                required
                                            />
                                            {errors.name && (
                                                <p className="text-sm text-red-500">{errors.name}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <label htmlFor="email" className="text-sm font-medium">
                                                Email
                                            </label>
                                            <Input
                                                id="email"
                                                name="email"
                                                type="email"
                                                value={data.email}
                                                onChange={e => setData('email', e.target.value)}
                                                placeholder="john@example.com"
                                                className="w-full"
                                                disabled={processing}
                                                required
                                            />
                                            {errors.email && (
                                                <p className="text-sm text-red-500">{errors.email}</p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <label htmlFor="subject" className="text-sm font-medium">
                                            Subject
                                        </label>
                                        <Select 
                                            value={data.subject} 
                                            onValueChange={(value) => setData('subject', value)}
                                            disabled={processing}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select a subject" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="general">General Inquiry</SelectItem>
                                                <SelectItem value="support">Technical Support</SelectItem>
                                                <SelectItem value="billing">Billing Question</SelectItem>
                                                <SelectItem value="partnership">Partnership</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.subject && (
                                            <p className="text-sm text-red-500">{errors.subject}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <label htmlFor="message" className="text-sm font-medium">
                                            Message
                                        </label>
                                        <Textarea
                                            id="message"
                                            name="message"
                                            value={data.message}
                                            onChange={e => setData('message', e.target.value)}
                                            placeholder="How can we help you?"
                                            className="min-h-[150px]"
                                            disabled={processing}
                                            required
                                        />
                                        {errors.message && (
                                            <p className="text-sm text-red-500">{errors.message}</p>
                                        )}
                                    </div>

                                    <div>
                                        <Button
                                            type="submit"
                                            className="w-full sm:w-auto"
                                            size="lg"
                                            disabled={processing}
                                        >
                                            {processing ? (
                                                <>
                                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                    Sending...
                                                </>
                                            ) : (
                                                <>
                                                    <MessageSquare className="mr-2 h-4 w-4" />
                                                    Send Message
                                                </>
                                            )}
                                        </Button>

                                        {/* Status Messages */}
                                        {status === "success" && (
                                            <p className="mt-4 text-sm text-green-600">
                                                Thanks for reaching out! We'll get back to you soon.
                                            </p>
                                        )}
                                        {status === "error" && (
                                            <p className="mt-4 text-sm text-red-600">
                                                Something went wrong. Please try again.
                                            </p>
                                        )}
                                    </div>
                                </form>
                            </Card>
                        </div>
                    </div>

                    {/* FAQ Section */}
                    <div className="mt-24 max-w-3xl mx-auto text-center">
                        <h2 className="text-2xl font-bold mb-2">Frequently Asked Questions</h2>
                        <p className="text-muted-foreground mb-8">
                            Can't find the answer you're looking for? Reach out to our customer support team.
                        </p>
                        <Button variant="outline" asChild>
                            <a href="/faq">Visit our FAQ page</a>
                        </Button>
                    </div>
                </div>
            </div>
        </WebsiteLayout>
    );
}
