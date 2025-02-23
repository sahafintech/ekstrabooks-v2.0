import { Head } from "@inertiajs/react";
import WebsiteLayout from "@/Layouts/WebsiteLayout";
import { Button } from "@/Components/ui/button";
import { Shield, Mail, ArrowRight } from "lucide-react";

export default function Privacy() {
    const sections = [
        {
            id: "introduction",
            title: "Introduction",
            content: `At Ekstrabooks, we take your privacy seriously. This Privacy Policy explains how we collect, 
            use, disclose, and safeguard your information when you use our accounting software and related services. 
            Please read this privacy policy carefully. If you do not agree with the terms of this privacy policy, 
            please do not access the application.`
        },
        {
            id: "information-we-collect",
            title: "Information We Collect",
            subsections: [
                {
                    title: "Personal Information",
                    content: `We collect personal information that you voluntarily provide to us when you:
                    • Register for an account
                    • Express interest in our products
                    • Provide feedback or contact us
                    This may include your name, email, phone number, and business information.`
                },
                {
                    title: "Financial Information",
                    content: `As an accounting platform, we collect financial data necessary for providing our services:
                    • Transaction records
                    • Account balances
                    • Banking information
                    • Payment processing data
                    All financial data is encrypted and stored securely.`
                },
                {
                    title: "Usage Information",
                    content: `We automatically collect certain information about your device and how you interact with our platform:
                    • Log and usage data
                    • Device information
                    • Location information
                    • Cookies and tracking technologies`
                }
            ]
        },
        {
            id: "how-we-use-information",
            title: "How We Use Your Information",
            content: `We use the collected information for various purposes:
            • Providing and maintaining our services
            • Improving user experience
            • Processing your transactions
            • Sending administrative information
            • Providing customer support
            • Ensuring platform security
            • Complying with legal obligations`
        },
        {
            id: "data-security",
            title: "Data Security",
            content: `We implement robust security measures to protect your data:
            • End-to-end encryption
            • Regular security audits
            • Secure data centers
            • Access controls
            • Employee training
            • Incident response procedures`
        },
        {
            id: "data-sharing",
            title: "Data Sharing and Disclosure",
            content: `We may share your information with:
            • Service providers
            • Business partners
            • Legal authorities when required
            • During business transactions
            We never sell your personal information to third parties.`
        },
        {
            id: "your-rights",
            title: "Your Privacy Rights",
            content: `You have the right to:
            • Access your personal information
            • Correct inaccurate data
            • Request data deletion
            • Opt-out of marketing communications
            • Export your data
            • Lodge a complaint with supervisory authorities`
        },
        {
            id: "international-transfers",
            title: "International Data Transfers",
            content: `We may transfer your information to servers located outside your country. 
            We ensure appropriate safeguards are in place to protect your information during such transfers.`
        },
        {
            id: "retention",
            title: "Data Retention",
            content: `We retain your information for as long as necessary to:
            • Provide our services
            • Comply with legal obligations
            • Resolve disputes
            • Enforce agreements`
        },
        {
            id: "children",
            title: "Children's Privacy",
            content: `Our services are not intended for children under 13. We do not knowingly collect 
            or maintain information from children under 13 years of age.`
        },
        {
            id: "updates",
            title: "Updates to This Policy",
            content: `We may update this privacy policy from time to time. We will notify you of any changes 
            by posting the new privacy policy on this page and updating the "Last Updated" date.`
        },
        {
            id: "contact",
            title: "Contact Us",
            content: `If you have questions about this Privacy Policy, please contact us at:`
        }
    ];

    return (
        <WebsiteLayout>
            <Head title="Privacy Policy" />

            {/* Hero Section */}
            <section className="relative overflow-hidden bg-muted/30">
                <div className="absolute inset-0 bg-grid-pattern opacity-5" />
                <div className="container px-4 py-24 mx-auto relative">
                    <div className="max-w-4xl mx-auto text-center">
                        <div className="mb-6 inline-block">
                            <div className="p-3 rounded-2xl bg-primary/10">
                                <Shield className="w-10 h-10 text-primary" />
                            </div>
                        </div>
                        <h1 className="text-4xl font-bold tracking-tight sm:text-5xl mb-6">
                            Privacy Policy
                        </h1>
                        <p className="text-xl text-muted-foreground">
                            We're committed to protecting your privacy and ensuring your information is secure.
                        </p>
                        <p className="mt-4 text-sm text-muted-foreground">
                            Last Updated: February 23, 2025
                        </p>
                    </div>
                </div>
            </section>

            {/* Content Section */}
            <section className="py-16">
                <div className="container px-4 mx-auto">
                    <div className="flex flex-col lg:flex-row gap-12">
                        {/* Navigation Sidebar */}
                        <div className="lg:w-64 flex-shrink-0">
                            <div className="sticky top-24">
                                <nav className="space-y-1">
                                    {sections.map((section) => (
                                        <a
                                            key={section.id}
                                            href={`#${section.id}`}
                                            className="block px-3 py-2 text-sm rounded-lg hover:bg-muted transition-colors"
                                        >
                                            {section.title}
                                        </a>
                                    ))}
                                </nav>
                            </div>
                        </div>

                        {/* Main Content */}
                        <div className="flex-1 max-w-3xl">
                            {sections.map((section) => (
                                <div
                                    key={section.id}
                                    id={section.id}
                                    className="mb-12 scroll-mt-24"
                                >
                                    <h2 className="text-2xl font-bold mb-4">
                                        {section.title}
                                    </h2>
                                    {section.subsections ? (
                                        <div className="space-y-6">
                                            {section.subsections.map((subsection, index) => (
                                                <div key={index}>
                                                    <h3 className="text-lg font-semibold mb-2">
                                                        {subsection.title}
                                                    </h3>
                                                    <p className="text-muted-foreground whitespace-pre-line">
                                                        {subsection.content}
                                                    </p>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-muted-foreground whitespace-pre-line">
                                            {section.content}
                                        </p>
                                    )}
                                </div>
                            ))}

                            {/* Contact Section */}
                            <div className="mt-16 p-6 rounded-xl bg-muted/30 border">
                                <div className="flex items-start gap-4">
                                    <Mail className="w-6 h-6 text-primary mt-1" />
                                    <div>
                                        <h3 className="font-semibold mb-2">
                                            Have questions about our privacy policy?
                                        </h3>
                                        <p className="text-muted-foreground mb-4">
                                            Our team is here to help you understand how we protect your data.
                                        </p>
                                        <Button asChild>
                                            <a href="/contact">
                                                Contact Us
                                                <ArrowRight className="ml-2 h-4 w-4" />
                                            </a>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </WebsiteLayout>
    );
}