import { Card, CardContent } from "@/Components/ui/card";

export default function Features() {
    const features = [
        {
            title: "Become efficiency leaders",
            description: "Eliminate manual routine tasks and be among the most productive firms.",
            icon: (
                <svg className="h-8 w-8 text-primary" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M3.5 18.49l6-6.01l4 4L22 6.92L20.59 5.5l-8.09 8.09l-4-4L2 16.99z"/>
                </svg>
            ),
        },
        {
            title: "Time for financial advice",
            description: "Focus on more profitable, value-adding services for your clients.",
            icon: (
                <svg className="h-8 w-8 text-primary" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8s8 3.58 8 8s-3.58 8-8 8zm.5-13H11v6l5.25 3.15l.75-1.23l-4.5-2.67z"/>
                </svg>
            ),
        },
        {
            title: "Acquire modern clients",
            description: "The best clients expect innovative and digital solutions.",
            icon: (
                <svg className="h-8 w-8 text-primary" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5S5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05c1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                </svg>
            ),
        },
        {
            title: "No installation necessary",
            description: "Onboard in under 45min and export bookings to DATEV with just one click.",
            icon: (
                <svg className="h-8 w-8 text-primary" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5l-5-5l1.41-1.41L11 12.67V3h2z"/>
                </svg>
            ),
        },
    ];

    return (
        <section className="bg-muted/30 py-24">
            <div className="container px-4 md:px-6">
                <div className="flex flex-col items-center justify-center space-y-4 text-center">
                    <div className="space-y-2">
                        <h2 className="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl">
                            Features that make a difference
                        </h2>
                        <p className="mx-auto max-w-[700px] text-muted-foreground md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                            Our AI-powered platform streamlines your accounting workflow
                        </p>
                    </div>
                </div>

                <div className="mx-auto grid max-w-5xl grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4 mt-12">
                    {features.map((feature, i) => (
                        <Card key={i} className="bg-background">
                            <CardContent className="p-6">
                                <div className="mb-4">
                                    {feature.icon}
                                </div>
                                <h3 className="font-semibold text-xl mb-2">
                                    {feature.title}
                                </h3>
                                <p className="text-muted-foreground">
                                    {feature.description}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Additional Features */}
                <div className="mt-20">
                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="space-y-4">
                            <h3 className="text-2xl font-bold">Leverage e-Invoice potential</h3>
                            <p className="text-muted-foreground">
                                Unlock the power of X-Invoice and ZUGFeRD from day one. Our platform automatically processes and validates electronic invoices.
                            </p>
                        </div>
                        <div className="space-y-4">
                            <h3 className="text-2xl font-bold">AI-powered automation</h3>
                            <p className="text-muted-foreground">
                                Our AI learns from your corrections and gets better over time, reducing manual work and increasing accuracy.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
