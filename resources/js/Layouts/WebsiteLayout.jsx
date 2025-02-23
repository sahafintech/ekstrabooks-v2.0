import { useState, useEffect } from "react";
import { Link, usePage } from "@inertiajs/react";
import { Button } from "@/Components/ui/button";
import Footer from "@/Components/Website/Footer";
import { cn } from "@/lib/utils";

export default function WebsiteLayout({ children }) {
    const { url } = usePage();
    const [isScrolled, setIsScrolled] = useState(false);

    useEffect(() => {
        const handleScroll = () => {
            setIsScrolled(window.scrollY > 10);
        };
        window.addEventListener("scroll", handleScroll);
        return () => window.removeEventListener("scroll", handleScroll);
    }, []);

    const navLinks = [
        { href: "/features", label: "Features" },
        { href: "/pricing", label: "Pricing" },
        { href: "/about", label: "About" },
        { href: "/faq", label: "FAQ" },
        { href: "/contact", label: "Contact" }
    ];

    return (
        <div className="min-h-screen flex flex-col">
            <header 
                className={`sticky top-0 z-50 w-full transition-all duration-200 ${
                    isScrolled ? "bg-background/80 backdrop-blur-sm border-b" : "bg-transparent"
                }`}
            >
                <div className="container px-4 sm:px-6 lg:px-8 mx-auto">
                    <div className="flex h-16 items-center justify-between">
                        {/* Logo */}
                        <Link href="/" className="flex items-center space-x-2">
                            <img 
                                src="/images/logo.svg" 
                                alt="Logo" 
                                className="h-8 w-auto"
                            />
                        </Link>

                        {/* Navigation Links - Desktop */}
                        <nav className="hidden md:flex items-center space-x-8">
                            {navLinks.map((link) => (
                                <Link 
                                    key={link.href}
                                    href={link.href} 
                                    className={cn(
                                        "text-sm font-medium transition-colors",
                                        url === link.href
                                            ? "text-primary font-semibold"
                                            : "text-muted-foreground hover:text-foreground"
                                    )}
                                >
                                    {link.label}
                                </Link>
                            ))}
                        </nav>

                        {/* Auth Buttons */}
                        <div className="flex items-center space-x-4">
                            <Link 
                                href={route('login')} 
                                className={cn(
                                    "text-sm font-medium transition-colors",
                                    url === "/login"
                                        ? "text-primary font-semibold"
                                        : "text-muted-foreground hover:text-foreground"
                                )}
                            >
                                Sign In
                            </Link>
                            <Button asChild>
                                <Link href="/register">
                                    Start Free Trial
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </header>

            <main className="flex-1">
                {children}
            </main>

            <Footer />
        </div>
    );
}
