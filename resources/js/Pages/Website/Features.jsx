import { Head } from "@inertiajs/react";
import WebsiteLayout from "@/Layouts/WebsiteLayout";
import { Button } from "@/Components/ui/button";
import {
    BarChart3,
    Receipt,
    CreditCard,
    Users2,
    FileSpreadsheet,
    Wallet,
    ArrowRight,
    CheckCircle2,
    Sparkles,
    Building2,
    Smartphone,
    Globe2,
    Clock,
    Shield,
    Zap
} from "lucide-react";

export default function Features() {
    const mainFeatures = [
        {
            icon: <BarChart3 className="w-6 h-6" />,
            title: "Real-time Financial Insights",
            description: "Get instant visibility into your business performance with live dashboards and reports.",
            benefits: [
                "Live cash flow monitoring",
                "Customizable dashboards",
                "Trend analysis and forecasting",
                "Performance metrics"
            ]
        },
        {
            icon: <Receipt className="w-6 h-6" />,
            title: "Smart Invoicing",
            description: "Create and send professional invoices in seconds, with automated payment reminders.",
            benefits: [
                "Custom invoice templates",
                "Automatic reminders",
                "Multi-currency support",
                "Payment tracking"
            ]
        },
        {
            icon: <CreditCard className="w-6 h-6" />,
            title: "Seamless Payments",
            description: "Accept payments online and reconcile them automatically with your accounts.",
            benefits: [
                "Multiple payment methods",
                "Automatic reconciliation",
                "Payment scheduling",
                "Direct bank feeds"
            ]
        },
        {
            icon: <Users2 className="w-6 h-6" />,
            title: "Team Collaboration",
            description: "Work together efficiently with role-based access and real-time updates.",
            benefits: [
                "Role-based permissions",
                "Activity tracking",
                "Comments and notes",
                "Shared workspaces"
            ]
        }
    ];

    const additionalFeatures = [
        {
            icon: <FileSpreadsheet />,
            title: "Financial Reports",
            description: "Generate comprehensive financial reports with just a few clicks."
        },
        {
            icon: <Wallet />,
            title: "Expense Tracking",
            description: "Track and categorize expenses automatically with smart rules."
        },
        {
            icon: <Building2 />,
            title: "Multi-branch Support",
            description: "Manage multiple locations or branches from a single dashboard."
        },
        {
            icon: <Smartphone />,
            title: "Mobile Access",
            description: "Access your accounts anytime, anywhere with our mobile app."
        },
        {
            icon: <Globe2 />,
            title: "Multi-currency",
            description: "Handle transactions in multiple currencies with ease."
        },
        {
            icon: <Clock />,
            title: "Automated Tasks",
            description: "Save time with automated reconciliation and data entry."
        },
        {
            icon: <Shield />,
            title: "Bank-grade Security",
            description: "Your data is protected with enterprise-level security measures."
        },
        {
            icon: <Zap />,
            title: "API Access",
            description: "Integrate with your favorite business tools via our API."
        }
    ];

    const integrations = [
        { name: "Zaad", logo: "/website/assets/payment_gateways/zaad.png" },
        { name: "eDahab", logo: "/website/assets/payment_gateways/edahab.png" },
        { name: "Mastercard", logo: "/website/assets/payment_gateways/mastercard.svg" },
        { name: "Visa", logo: "/website/assets/payment_gateways/visa.jpg" }
    ];

    return (
        <WebsiteLayout>
            <Head title="Features" />

            {/* Hero Section */}
            <section className="relative overflow-hidden bg-muted/30">
                <div className="absolute inset-0 bg-grid-pattern opacity-5" />
                <div className="container px-4 py-24 mx-auto relative">
                    <div className="max-w-4xl mx-auto text-center">
                        <div className="mb-6 inline-block">
                            <div className="p-3 rounded-2xl bg-primary/10">
                                <Sparkles className="w-10 h-10 text-primary" />
                            </div>
                        </div>
                        <h1 className="text-4xl font-bold tracking-tight sm:text-5xl mb-6">
                            Everything you need to manage your business finances
                        </h1>
                        <p className="text-xl text-muted-foreground mb-8">
                            Powerful features designed to help you grow your business and manage your finances with confidence.
                        </p>
                        <div className="flex gap-4 justify-center">
                            <Button size="lg" asChild>
                                <a href="/register">
                                    Get Started
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

            {/* Main Features */}
            <section className="py-24">
                <div className="container px-4 mx-auto">
                    <div className="grid md:grid-cols-2 gap-12">
                        {mainFeatures.map((feature, index) => (
                            <div
                                key={index}
                                className="relative p-8 rounded-2xl border bg-card transition-shadow hover:shadow-lg"
                            >
                                <div className="p-3 rounded-xl bg-primary/10 inline-block mb-4">
                                    {feature.icon}
                                </div>
                                <h3 className="text-xl font-bold mb-3">{feature.title}</h3>
                                <p className="text-muted-foreground mb-6">{feature.description}</p>
                                <ul className="space-y-3">
                                    {feature.benefits.map((benefit, benefitIndex) => (
                                        <li key={benefitIndex} className="flex items-center gap-2">
                                            <CheckCircle2 className="h-5 w-5 text-primary flex-shrink-0" />
                                            <span>{benefit}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Additional Features Grid */}
            <section className="py-24 bg-muted/30">
                <div className="container px-4 mx-auto">
                    <div className="max-w-3xl mx-auto text-center mb-16">
                        <h2 className="text-3xl font-bold tracking-tight mb-4">
                            Packed with powerful features
                        </h2>
                        <p className="text-lg text-muted-foreground">
                            Everything you need to take control of your business finances
                        </p>
                    </div>
                    <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                        {additionalFeatures.map((feature, index) => (
                            <div
                                key={index}
                                className="p-6 rounded-xl border bg-card transition-all hover:scale-105"
                            >
                                <div className="p-2 rounded-lg bg-primary/10 inline-block mb-4">
                                    {feature.icon}
                                </div>
                                <h3 className="font-semibold mb-2">{feature.title}</h3>
                                <p className="text-sm text-muted-foreground">{feature.description}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Integrations Section */}
            <section className="py-24">
                <div className="container px-4 mx-auto">
                    <div className="max-w-3xl mx-auto text-center mb-16">
                        <h2 className="text-3xl font-bold tracking-tight mb-4">
                            Works with your favorite payment methods
                        </h2>
                        <p className="text-lg text-muted-foreground">
                            Seamlessly integrate with popular payment gateways and financial services
                        </p>
                    </div>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-8 items-center justify-items-center">
                        {integrations.map((integration, index) => (
                            <div
                                key={index}
                                className="p-6 rounded-xl border bg-card w-full h-24 flex items-center justify-center"
                            >
                                <img
                                    src={integration.logo}
                                    alt={integration.name}
                                    className="max-h-12 w-auto"
                                />
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA Section */}
            <section className="py-24 bg-muted/30">
                <div className="container px-4 mx-auto text-center">
                    <div className="max-w-3xl mx-auto">
                        <h2 className="text-3xl font-bold tracking-tight mb-4">
                            Ready to streamline your business finances?
                        </h2>
                        <p className="text-lg text-muted-foreground mb-8">
                            Join thousands of businesses using Ekstrabooks to manage their finances.
                        </p>
                        <div className="flex gap-4 justify-center">
                            <Button size="lg" asChild>
                                <a href="/register">
                                    Start Free Trial
                                    <ArrowRight className="ml-2 h-4 w-4" />
                                </a>
                            </Button>
                            <Button size="lg" variant="outline" asChild>
                                <a href="/contact">Schedule Demo</a>
                            </Button>
                        </div>
                    </div>
                </div>
            </section>
        </WebsiteLayout>
    );
}
