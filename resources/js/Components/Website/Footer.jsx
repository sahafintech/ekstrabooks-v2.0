import { Link } from "@inertiajs/react";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import {
    Twitter,
    Linkedin,
    Github,
    Youtube,
    ArrowRight,
    CheckCircle2
} from "lucide-react";

export default function Footer() {
    const productLinks = [
        { name: "Features", href: "/features" },
        { name: "Pricing", href: "/pricing" },
        { name: "Integrations", href: "/integrations" },
        { name: "Enterprise", href: "/enterprise" },
        { name: "Security", href: "/security" },
    ];

    const companyLinks = [
        { name: "About", href: "/about" },
        { name: "Blog", href: "/blog" },
        { name: "Careers", href: "/careers" },
        { name: "Customers", href: "/customers" },
        { name: "Partners", href: "/partners" },
    ];

    const resourceLinks = [
        { name: "Documentation", href: "/docs" },
        { name: "API Reference", href: "/api" },
        { name: "Community", href: "/community" },
        { name: "Help Center", href: "/help" },
        { name: "Status", href: "/status" },
    ];

    const legalLinks = [
        { name: "Privacy", href: "/privacy" },
        { name: "Terms", href: "/terms" },
        { name: "Cookie Policy", href: "/cookies" },
        { name: "Licenses", href: "/licenses" },
    ];

    const socialLinks = [
        {
            name: "Twitter",
            href: "https://twitter.com/ekstrabooks",
            icon: Twitter
        },
        {
            name: "LinkedIn",
            href: "https://linkedin.com/company/ekstrabooks",
            icon: Linkedin
        },
        {
            name: "GitHub",
            href: "https://github.com/ekstrabooks",
            icon: Github
        },
        {
            name: "YouTube",
            href: "https://youtube.com/ekstrabooks",
            icon: Youtube
        },
    ];

    return (
        <footer className="border-t bg-muted/30 flex items-center justify-center">
            <div>
                {/* Main Footer */}
                <div className="container px-4 py-12">
                    <div className="grid grid-cols-2 md:grid-cols-12 gap-8">
                        {/* Brand Column */}
                        <div className="col-span-2 md:col-span-3">
                            <Link href="/">
                                <img
                                    src="/images/logo.svg"
                                    alt="Ekstrabooks"
                                    className="h-8 w-auto"
                                />
                            </Link>
                            <p className="mt-4 text-sm text-muted-foreground">
                                Making accounting simple and efficient for modern businesses.
                            </p>
                            <div className="mt-4 flex gap-4">
                                {socialLinks.map((social) => (
                                    <a
                                        key={social.name}
                                        href={social.href}
                                        className="text-muted-foreground hover:text-foreground transition-colors"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <social.icon className="h-5 w-5" />
                                        <span className="sr-only">{social.name}</span>
                                    </a>
                                ))}
                            </div>
                        </div>

                        {/* Links Columns */}
                        <div className="col-span-1 md:col-span-2">
                            <h3 className="font-semibold mb-3">Product</h3>
                            <ul className="space-y-2">
                                {productLinks.map((link) => (
                                    <li key={link.name}>
                                        <Link
                                            href={link.href}
                                            className="text-sm text-muted-foreground hover:text-foreground transition-colors"
                                        >
                                            {link.name}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div className="col-span-1 md:col-span-2">
                            <h3 className="font-semibold mb-3">Company</h3>
                            <ul className="space-y-2">
                                {companyLinks.map((link) => (
                                    <li key={link.name}>
                                        <Link
                                            href={link.href}
                                            className="text-sm text-muted-foreground hover:text-foreground transition-colors"
                                        >
                                            {link.name}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div className="col-span-1 md:col-span-2">
                            <h3 className="font-semibold mb-3">Resources</h3>
                            <ul className="space-y-2">
                                {resourceLinks.map((link) => (
                                    <li key={link.name}>
                                        <Link
                                            href={link.href}
                                            className="text-sm text-muted-foreground hover:text-foreground transition-colors"
                                        >
                                            {link.name}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div className="col-span-1 md:col-span-2">
                            <h3 className="font-semibold mb-3">Legal</h3>
                            <ul className="space-y-2">
                                {legalLinks.map((link) => (
                                    <li key={link.name}>
                                        <Link
                                            href={link.href}
                                            className="text-sm text-muted-foreground hover:text-foreground transition-colors"
                                        >
                                            {link.name}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>

                {/* Bottom Bar */}
                <div className="border-t">
                    <div className="container px-4 py-6">
                        <div className="flex flex-col md:flex-row justify-between items-center gap-4">
                            <p className="text-sm text-muted-foreground">
                                &copy; {new Date().getFullYear()} Ekstrabooks. All rights reserved.
                            </p>
                            <div className="flex items-center gap-4">
                                <img
                                    src="/website/assets/payment_gateways/visa.jpg"
                                    alt="Visa"
                                    className="h-6"
                                />
                                <img
                                    src="/website/assets/payment_gateways/mastercard.svg"
                                    alt="Mastercard"
                                    className="h-6"
                                />
                                <img
                                    src="/website/assets/payment_gateways/zaad.png"
                                    alt="Zaad"
                                    className="h-6"
                                />
                                <img
                                    src="/website/assets/payment_gateways/edahab.png"
                                    alt="Edahab"
                                    className="h-6"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    );
}
