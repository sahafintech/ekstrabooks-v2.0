import { useState, useEffect } from "react";
import { Link, usePage } from "@inertiajs/react";
import { Button } from "@/Components/ui/button";
import Footer from "@/Components/Website/Footer";
import { cn } from "@/lib/utils";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { LogOut, LayoutDashboard, User } from "lucide-react";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

export default function WebsiteLayout({ children }) {
    const { url, auth } = usePage().props;
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

                        {/* Auth Section */}
                        <div className="flex items-center space-x-4">
                            {auth?.user ? (
                                <DropdownMenu>
                                    <DropdownMenuTrigger className="focus:outline-none">
                                        <Avatar className="h-8 w-8">
                                            <AvatarImage src={auth.user.avatar} />
                                            <AvatarFallback className="bg-primary/10">
                                                {auth.user.name?.charAt(0).toUpperCase()}
                                            </AvatarFallback>
                                        </Avatar>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="w-48">
                                        <div className="px-2 py-1.5">
                                            <p className="text-sm font-medium">{auth.user.name}</p>
                                            <p className="text-xs text-muted-foreground">{auth.user.email}</p>
                                        </div>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem asChild>
                                            <Link href={route('dashboard.index')} className="flex items-center">
                                                <LayoutDashboard className="mr-2 h-4 w-4" />
                                                Dashboard
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem asChild>
                                            <Link href={route('logout')} method="post" className="flex items-center text-destructive w-full">
                                                <LogOut className="mr-2 h-4 w-4" />
                                                Logout
                                            </Link>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            ) : (
                                <>
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
                                </>
                            )}
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
