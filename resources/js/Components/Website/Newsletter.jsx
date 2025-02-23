import { useState } from "react";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Loader2 } from "lucide-react";
import { useForm } from "@inertiajs/react";

export default function Newsletter({ heading, subHeading }) {
    const [status, setStatus] = useState("idle");
    const { data, setData, post, processing } = useForm({
        email: "",
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        setStatus("submitting");
        
        post(route("newsletter.subscribe"), {
            onSuccess: () => {
                setStatus("success");
                setData("email", "");
            },
            onError: () => {
                setStatus("error");
            },
        });
    };

    return (
        <section className="relative overflow-hidden border-t">
            {/* Background Pattern */}
            <div className="absolute inset-0 grid grid-cols-2 -space-x-52 opacity-40 dark:opacity-20">
                <div className="blur-[106px] h-56 bg-gradient-to-br from-primary to-purple-400 dark:from-blue-700"></div>
                <div className="blur-[106px] h-32 bg-gradient-to-r from-cyan-400 to-sky-300 dark:to-indigo-600"></div>
            </div>

            <div className="container relative px-4 py-24 mx-auto">
                <div className="flex flex-col items-center max-w-2xl mx-auto text-center">
                    {/* Icon */}
                    <div className="p-3 mb-8 border rounded-2xl bg-background">
                        <svg
                            className="w-6 h-6 text-primary"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            />
                        </svg>
                    </div>

                    {/* Heading */}
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl">
                        {heading || "Stay up to date"}
                    </h2>

                    {/* Description */}
                    <p className="max-w-xl mx-auto mt-4 text-lg text-muted-foreground">
                        {subHeading || "Get notified about the latest features and updates, no spam."}
                    </p>

                    {/* Form */}
                    <form onSubmit={handleSubmit} className="flex flex-col sm:flex-row gap-4 w-full mt-10">
                        <div className="flex-1">
                            <Input
                                type="email"
                                placeholder="Enter your email"
                                value={data.email}
                                onChange={e => setData("email", e.target.value)}
                                className="w-full px-5 py-6 text-base"
                                disabled={processing}
                                required
                            />
                        </div>
                        <Button 
                            type="submit" 
                            size="lg"
                            className="w-full sm:w-auto"
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Subscribing...
                                </>
                            ) : (
                                "Subscribe"
                            )}
                        </Button>
                    </form>

                    {/* Status Messages */}
                    {status === "success" && (
                        <p className="mt-4 text-sm text-green-600 dark:text-green-500">
                            Thanks for subscribing! Check your email for confirmation.
                        </p>
                    )}
                    {status === "error" && (
                        <p className="mt-4 text-sm text-red-600 dark:text-red-500">
                            Something went wrong. Please try again.
                        </p>
                    )}

                    {/* Trust Badges */}
                    <div className="flex flex-col items-center mt-10 pt-10 border-t w-full">
                        <p className="text-sm text-muted-foreground mb-6">
                            Trusted by teams worldwide
                        </p>
                        <div className="flex flex-wrap justify-center gap-8 opacity-75 grayscale">
                            <img src="/website/assets/logos/amanah.png" alt="Amanah Insurance" className="h-8" />
                            <img src="/website/assets/logos/geosol.png" alt="Geosol" className="h-8" />
                            <img src="/website/assets/logos/amazon.jpeg" alt="Amazon" className="h-8" />
                            <img src="/website/assets/logos/meta.png" alt="Meta" className="h-8" />
                        </div>
                    </div>

                    {/* Privacy Note */}
                    <p className="mt-8 text-xs text-muted-foreground">
                        By subscribing, you agree to our{" "}
                        <a href="/privacy" className="underline underline-offset-2 hover:text-foreground">
                            Privacy Policy
                        </a>
                        . We respect your privacy and will never share your information.
                    </p>
                </div>
            </div>
        </section>
    );
}
