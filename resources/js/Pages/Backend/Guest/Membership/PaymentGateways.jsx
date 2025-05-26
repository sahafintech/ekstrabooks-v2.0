import { Avatar, AvatarFallback, AvatarImage } from "@/Components/ui/avatar";
import { Button } from "@/Components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Link, usePage } from "@inertiajs/react";
import { CheckCircle2, LayoutDashboard, LogOut, PhoneCall } from "lucide-react";

export default function PaymentGateways({ showTitle = true }) {
    const enterpriseFeatures = [
        "Unlimited System Users",
        "Unlimited Invoice",
        "Unlimited Quotation",
        "HR & Payroll Module",
        "Point Of Sale",
        "Unlimited Customer Account",
        "Accept Online Payment",
        "Custom Development",
    ];

    const logo = usePage().props.logo;
    const auth = usePage().props.auth;
    return (
        <>
            <header
                className={`sticky top-0 z-50 w-full transition-all duration-200`}
            >
                <div className="container px-4 sm:px-6 lg:px-8 mx-auto">
                    <div className="flex h-16 items-center justify-between">
                        {/* Logo */}
                        <Link href="/" className="flex items-center space-x-2">
                            <img
                                src={`/uploads/media/${logo}`}
                                alt="Logo"
                                className="h-16 w-auto"
                            />
                        </Link>

                        {/* Auth Section */}
                        <div className="flex items-center space-x-4">
                            {auth?.user ? (
                                <DropdownMenu>
                                    <DropdownMenuTrigger className="focus:outline-none">
                                        <Avatar className="h-8 w-8">
                                            <AvatarImage
                                                src={auth.user.avatar}
                                            />
                                            <AvatarFallback className="bg-primary/10">
                                                {auth.user.name
                                                    ?.charAt(0)
                                                    .toUpperCase()}
                                            </AvatarFallback>
                                        </Avatar>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align="end"
                                        className="w-48"
                                    >
                                        <div className="px-2 py-1.5">
                                            <p className="text-sm font-medium">
                                                {auth.user.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {auth.user.email}
                                            </p>
                                        </div>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem asChild>
                                            <Link
                                                href={route("dashboard.index")}
                                                className="flex items-center"
                                            >
                                                <LayoutDashboard className="mr-2 h-4 w-4" />
                                                Dashboard
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem asChild>
                                            <Link
                                                href={route("logout")}
                                                method="post"
                                                className="flex items-center text-destructive w-full"
                                            >
                                                <LogOut className="mr-2 h-4 w-4" />
                                                Logout
                                            </Link>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            ) : (
                                <>
                                    <Link
                                        href={route("login")}
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
            <section className="py-24">
                <div className="container px-4 mx-auto">
                    {showTitle && (
                        <div className="max-w-3xl mx-auto text-center mb-12">
                            <h2 className="text-4xl font-bold tracking-tight mb-4">
                                Enterprise Solutions
                            </h2>
                            <p className="text-xl text-muted-foreground">
                                Tailored accounting solutions for businesses of
                                all sizes
                            </p>
                        </div>
                    )}

                    {/* Enterprise Card */}
                    <div className="max-w-4xl mx-auto">
                        <div className="relative rounded-xl border border-primary shadow-lg bg-card p-8">
                            <div className="absolute -top-4 left-1/2 -translate-x-1/2">
                                <div className="bg-primary text-primary-foreground text-sm font-medium px-3 py-1 rounded-full">
                                    Enterprise Solution
                                </div>
                            </div>
                            <div className="mb-8">
                                <h3 className="text-2xl font-bold mb-2">
                                    Custom Enterprise Plan
                                </h3>
                                <p className="text-muted-foreground">
                                    Tailored solutions for organizations with
                                    specific requirements
                                </p>
                            </div>
                            <div className="mb-8 text-center">
                                <div className="text-xl font-medium text-primary">
                                    Contact us for custom pricing
                                </div>
                                <p className="text-muted-foreground mt-2">
                                    Get a personalized quote based on your
                                    business needs
                                </p>
                            </div>

                            {/* Features in a two-column grid */}
                            <div className="grid sm:grid-cols-2 gap-x-8 gap-y-4 mb-8">
                                {enterpriseFeatures.map(
                                    (feature, featureIndex) => (
                                        <div
                                            key={featureIndex}
                                            className="flex items-center gap-2"
                                        >
                                            <CheckCircle2 className="h-5 w-5 text-primary flex-shrink-0" />
                                            <span className="text-sm">
                                                {feature}
                                            </span>
                                        </div>
                                    )
                                )}
                            </div>

                            <Link href="/contact">
                                <Button className="w-full" size="lg">
                                    Contact Us
                                    <PhoneCall className="ml-2 h-4 w-4" />
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}
