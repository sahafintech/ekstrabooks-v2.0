import { Head } from "@inertiajs/react";
import { useState } from "react";
import { Button } from "@/Components/ui/button";
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/Components/ui/accordion";
import { Input } from "@/Components/ui/input";
import { Search, MessageSquare } from "lucide-react";
import WebsiteLayout from "@/Layouts/WebsiteLayout";

export default function Faq({ pageData }) {
    const [searchQuery, setSearchQuery] = useState("");
    const [activeCategory, setActiveCategory] = useState("all");

    const categories = [
        { id: "all", name: "All Questions" },
        { id: "getting-started", name: "Getting Started" },
        { id: "pricing", name: "Pricing & Billing" },
        { id: "features", name: "Features" },
        { id: "security", name: "Security" },
        { id: "integration", name: "Integration" },
    ];

    const faqs = [
        {
            category: "getting-started",
            questions: [
                {
                    q: "How do I get started with Ekstrabooks?",
                    a: "Getting started is easy! Simply sign up for a free trial, and our onboarding team will guide you through the setup process. We'll help you import your data, configure your settings, and get your team up and running."
                },
                {
                    q: "Can I migrate data from my existing accounting software?",
                    a: "Yes, we support data migration from most popular accounting software. Our team will help you migrate your data seamlessly, ensuring no information is lost in the process."
                },
                {
                    q: "How long does it take to set up?",
                    a: "Basic setup takes just a few minutes. For more complex configurations and data migration, our team typically completes the process within 24-48 hours."
                }
            ]
        },
        {
            category: "pricing",
            questions: [
                {
                    q: "What payment methods do you accept?",
                    a: "We accept all major credit cards (Visa, MasterCard, American Express), PayPal, and bank transfers for annual plans."
                },
                {
                    q: "Can I change my plan later?",
                    a: "Yes, you can upgrade or downgrade your plan at any time. Changes take effect immediately, and we'll prorate any payments."
                },
                {
                    q: "Do you offer a free trial?",
                    a: "Yes, we offer a 14-day free trial with full access to all features. No credit card required to start."
                }
            ]
        },
        {
            category: "features",
            questions: [
                {
                    q: "What features are included in the basic plan?",
                    a: "The basic plan includes invoicing, expense tracking, bank reconciliation, basic reporting, and up to 3 user accounts."
                },
                {
                    q: "Can I customize my invoices?",
                    a: "Yes, you can fully customize your invoices with your logo, colors, and custom fields. We also offer multiple professional templates."
                },
                {
                    q: "Do you support multiple currencies?",
                    a: "Yes, our platform supports multiple currencies with real-time exchange rate updates."
                }
            ]
        },
        {
            category: "security",
            questions: [
                {
                    q: "How secure is my data?",
                    a: "We use bank-level encryption (256-bit SSL) and store data in secure, ISO-certified data centers. We also perform regular security audits and backups."
                },
                {
                    q: "Can I control user permissions?",
                    a: "Yes, you can set detailed user permissions and roles to control access to sensitive data and features."
                },
                {
                    q: "Do you comply with GDPR?",
                    a: "Yes, we are fully GDPR compliant and provide tools to help you maintain compliance with data protection regulations."
                }
            ]
        },
        {
            category: "integration",
            questions: [
                {
                    q: "What integrations do you offer?",
                    a: "We integrate with popular payment gateways, CRM systems, and business tools. Some popular integrations include Stripe, PayPal, Salesforce, and Google Workspace."
                },
                {
                    q: "Can I connect my bank account?",
                    a: "Yes, we support secure bank connections with most major banks worldwide for automatic transaction imports."
                },
                {
                    q: "Do you have an API?",
                    a: "Yes, we offer a comprehensive REST API for custom integrations and automation."
                }
            ]
        }
    ];

    const filteredFaqs = faqs
        .filter(category => 
            activeCategory === "all" || category.category === activeCategory
        )
        .flatMap(category => 
            category.questions.filter(qa =>
                searchQuery === "" ||
                qa.q.toLowerCase().includes(searchQuery.toLowerCase()) ||
                qa.a.toLowerCase().includes(searchQuery.toLowerCase())
            )
        );

    return (
        <WebsiteLayout>
            <div className="flex items-center justify-center">
                {/* Background Pattern */}
                <div className="absolute inset-0 grid grid-cols-2 -space-x-52 opacity-40 dark:opacity-20">
                    <div className="blur-[106px] h-56 bg-gradient-to-br from-primary to-purple-400 dark:from-blue-700"></div>
                    <div className="blur-[106px] h-32 bg-gradient-to-r from-cyan-400 to-sky-300 dark:to-indigo-600"></div>
                </div>

                <div className="container relative px-4 py-24">
                    {/* Header */}
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <h1 className="text-4xl font-bold tracking-tight sm:text-5xl mb-4">
                            {pageData?.faq_heading || "Frequently Asked Questions"}
                        </h1>
                        <p className="text-lg text-muted-foreground mb-8">
                            {pageData?.faq_sub_heading || 
                            "Everything you need to know about Ekstrabooks. Can't find the answer you're looking for? Feel free to contact our support team."}
                        </p>

                        {/* Search Bar */}
                        <div className="relative max-w-md mx-auto">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                type="search"
                                placeholder="Search questions..."
                                className="pl-10"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                    </div>

                    {/* Category Tabs */}
                    <div className="flex flex-wrap justify-center gap-2 mb-12">
                        {categories.map((category) => (
                            <Button
                                key={category.id}
                                variant={activeCategory === category.id ? "default" : "outline"}
                                onClick={() => setActiveCategory(category.id)}
                                className="rounded-full"
                            >
                                {category.name}
                            </Button>
                        ))}
                    </div>

                    {/* FAQ Accordion */}
                    <div className="max-w-3xl mx-auto">
                        <Accordion type="single" collapsible className="w-full space-y-4">
                            {filteredFaqs.map((faq, i) => (
                                <AccordionItem 
                                    key={i} 
                                    value={`item-${i}`}
                                    className="border rounded-lg px-4"
                                >
                                    <AccordionTrigger className="text-left">
                                        {faq.q}
                                    </AccordionTrigger>
                                    <AccordionContent className="text-muted-foreground">
                                        {faq.a}
                                    </AccordionContent>
                                </AccordionItem>
                            ))}
                        </Accordion>

                        {filteredFaqs.length === 0 && (
                            <div className="text-center py-12">
                                <p className="text-muted-foreground">
                                    No questions found. Try adjusting your search.
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Contact Support */}
                    <div className="mt-16 text-center">
                        <h2 className="text-2xl font-semibold mb-4">
                            Still have questions?
                        </h2>
                        <p className="text-muted-foreground mb-8">
                            Can't find what you're looking for? Our support team is here to help.
                        </p>
                        <Button asChild size="lg">
                            <a href="/contact">
                                <MessageSquare className="mr-2 h-4 w-4" />
                                Contact Support
                            </a>
                        </Button>
                    </div>
                </div>
            </div>
        </WebsiteLayout>
    );
}
