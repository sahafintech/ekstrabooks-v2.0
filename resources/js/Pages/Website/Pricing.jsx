import { Head } from "@inertiajs/react";
import WebsiteLayout from "@/Layouts/WebsiteLayout";
import { Button } from "@/Components/ui/button";
import { ArrowRight } from "lucide-react";
import PricingSection from "@/Components/Website/PricingSection";

export default function Pricing({ pageData }) {
    const faqs = [
        {
            question: "Can I change plans later?",
            answer: "Yes, you can upgrade or downgrade your plan at any time. Changes will be reflected in your next billing cycle."
        },
        {
            question: "What payment methods do you accept?",
            answer: "We accept all major credit cards (Visa, MasterCard, American Express) and PayPal. For Enterprise plans, we also support wire transfers."
        },
        {
            question: "Is there a free trial?",
            answer: "Yes, we offer a 14-day free trial on all plans. No credit card required to start."
        },
        {
            question: "What happens after my trial?",
            answer: "After your trial ends, you can choose to subscribe to any of our plans. Your data will be preserved."
        }
    ];

    return (
        <WebsiteLayout>
            <Head title={pageData?.title || 'Pricing'} />
            
            {/* Hero Section */}
            <section className="relative overflow-hidden">
                <div className="absolute inset-0 bg-grid-pattern opacity-5" />
                <div className="container px-4 py-24 mx-auto relative">
                    <div className="max-w-3xl mx-auto text-center">
                        <h1 className="text-4xl font-bold tracking-tight sm:text-5xl mb-6">
                            Simple, transparent pricing
                        </h1>
                        <p className="text-xl text-muted-foreground">
                            Choose the perfect plan for your business needs
                        </p>
                    </div>
                </div>
            </section>

            {/* Pricing Section */}
            <PricingSection showTitle={false} />

            {/* Enterprise Section */}
            <section className="py-24 bg-muted/30">
                <div className="container px-4 mx-auto">
                    <div className="max-w-4xl mx-auto text-center">
                        <h2 className="text-3xl font-bold tracking-tight mb-4">
                            Need a custom solution?
                        </h2>
                        <p className="text-lg text-muted-foreground mb-8">
                            Let's talk about your specific requirements and create a tailored plan for your organization.
                        </p>
                        <Button size="lg" variant="outline" asChild>
                            <a href="/contact">Contact Sales</a>
                        </Button>
                    </div>
                </div>
            </section>

            {/* FAQs */}
            <section className="py-24">
                <div className="container px-4 mx-auto">
                    <div className="max-w-3xl mx-auto">
                        <h2 className="text-3xl font-bold tracking-tight text-center mb-12">
                            Frequently Asked Questions
                        </h2>
                        <div className="space-y-8">
                            {faqs.map((faq, index) => (
                                <div key={index}>
                                    <h3 className="font-semibold mb-2">{faq.question}</h3>
                                    <p className="text-muted-foreground">{faq.answer}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* CTA Section */}
            <section className="py-24 bg-muted/30">
                <div className="container px-4 mx-auto text-center">
                    <div className="max-w-3xl mx-auto">
                        <h2 className="text-3xl font-bold tracking-tight mb-4">
                            Ready to get started?
                        </h2>
                        <p className="text-lg text-muted-foreground mb-8">
                            Try Ekstrabooks free for 14 days, no credit card required.
                        </p>
                        <div className="flex gap-4 justify-center">
                            <Button size="lg" asChild>
                                <a href="/register">
                                    Start Free Trial
                                    <ArrowRight className="ml-2 h-4 w-4" />
                                </a>
                            </Button>
                            <Button size="lg" variant="outline" asChild>
                                <a href="/contact">Contact Sales</a>
                            </Button>
                        </div>
                    </div>
                </div>
            </section>
        </WebsiteLayout>
    );
}
