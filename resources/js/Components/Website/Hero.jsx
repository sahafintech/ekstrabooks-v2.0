import { Button } from "@/Components/ui/button";

export default function Hero({ heading, subHeading }) {
    return (
        <section className="relative overflow-hidden bg-background pt-[5.75rem]">
            <div className="relative mx-auto w-full px-4 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-[85rem]">
                    <div className="text-center">
                        <h1 className="block text-4xl font-bold text-gray-800 sm:text-5xl md:text-6xl lg:text-7xl dark:text-white">
                            World-Class Accounting Software For Your Business
                        </h1>

                        <p className="mt-6 text-lg text-muted-foreground md:text-xl">
                            Discover the Future of Financial Management: Advanced, User-Friendly Accounting Software Tailored for Modern Businesses.
                        </p>

                        <div className="mt-8 flex justify-center gap-3">
                            <Button size="lg" className="bg-primary hover:bg-primary/90">
                                Start Free Trial
                            </Button>
                            <Button size="lg" variant="outline">
                                Schedule Demo
                            </Button>
                        </div>

                        {/* Trust badges */}
                        <div className="mt-12 flex flex-wrap items-center justify-center gap-x-12 gap-y-6">
                            <div className="flex items-center gap-x-2">
                                <svg className="h-5 w-5 text-primary" viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M12 2L1 21h22L12 2zm0 3.99L19.53 19H4.47L12 5.99zM13 16h-2v2h2v-2zm0-6h-2v4h2v-4z" />
                                </svg>
                                <span className="text-sm text-muted-foreground">DATEV certified</span>
                            </div>
                            <div className="flex items-center gap-x-2">
                                <svg className="h-5 w-5 text-primary" viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12c5.16-1.26 9-6.45 9-12V5l-9-4zm0 2.18l7 3.12v4.7c0 4.38-2.85 8.51-7 9.79c-4.15-1.28-7-5.41-7-9.79V6.3l7-3.12z" />
                                </svg>
                                <span className="text-sm text-muted-foreground">ISO 27001 certified</span>
                            </div>
                            <div className="flex items-center gap-x-2">
                                <svg className="h-5 w-5 text-primary" viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10s10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8s8 3.59 8 8s-3.59 8-8 8zm3.88-11.71L10 14.17l-1.88-1.88a.996.996 0 1 0-1.41 1.41l2.59 2.59c.39.39 1.02.39 1.41 0L17.3 9.7a.996.996 0 1 0-1.41-1.41z" />
                                </svg>
                                <span className="text-sm text-muted-foreground">100% GDPR compliant</span>
                            </div>
                        </div>

                        {/* Screenshot */}
                        <div className="mt-16 relative">
                            <div className="absolute inset-0 flex items-center" aria-hidden="true">
                                <div className="w-full border-t border-muted"></div>
                            </div>
                            <div className="relative">
                                <div className="mx-auto rounded-xl shadow-2xl overflow-hidden">
                                    <img
                                        src="/uploads/media/new_ekstrabooks_dashboard.jpg"
                                        alt="Dashboard Preview"
                                        className="w-full"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
