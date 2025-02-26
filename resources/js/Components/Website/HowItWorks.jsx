import { Button } from "@/Components/ui/button";

export default function HowItWorks() {
    const steps = [
        {
            number: "01",
            title: "Upload documents",
            description: "Upload your documents via drag & drop, email, or scanner. We support all common formats.",
            image: "/images/upload-docs.png"
        },
        {
            number: "02",
            title: "AI processes documents",
            description: "Our AI extracts all relevant information and automatically assigns the correct account.",
            image: "/images/ai-process.png"
        },
        {
            number: "03",
            title: "Review & export",
            description: "Review the results and export them to DATEV with one click.",
            image: "/images/review-export.png"
        }
    ];

    return (
        <section className="py-24 bg-background flex justify-center">
            <div className="container px-4 md:px-6">
                <div className="flex flex-col items-center text-center mb-12">
                    <h2 className="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl">
                        How it works
                    </h2>
                    <p className="mt-4 text-lg text-muted-foreground md:text-xl max-w-3xl">
                        Get started in minutes and let AI handle your accounting tasks
                    </p>
                </div>

                <div className="grid gap-16">
                    {steps.map((step, index) => (
                        <div 
                            key={index} 
                            className={`grid items-center gap-8 ${
                                index % 2 === 0 ? 'md:grid-cols-[2fr,3fr]' : 'md:grid-cols-[3fr,2fr] md:flex-row-reverse'
                            }`}
                        >
                            <div className={`space-y-4 ${index % 2 === 0 ? 'md:pr-12' : 'md:pl-12'}`}>
                                <div className="inline-block rounded-lg bg-muted px-4 py-1.5 text-sm font-semibold">
                                    Step {step.number}
                                </div>
                                <h3 className="text-2xl font-bold md:text-3xl">
                                    {step.title}
                                </h3>
                                <p className="text-lg text-muted-foreground">
                                    {step.description}
                                </p>
                            </div>
                            <div className={`relative ${index % 2 === 0 ? 'md:order-last' : 'md:order-first'}`}>
                                <div className="relative aspect-video overflow-hidden rounded-xl border bg-muted/50">
                                    <img
                                        src={step.image}
                                        alt={step.title}
                                        className="object-cover w-full h-full"
                                    />
                                    {/* Gradient overlay */}
                                    <div className="absolute inset-0 bg-gradient-to-t from-background/20 to-background/0" />
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="mt-16 flex justify-center">
                    <Button size="lg" className="bg-primary hover:bg-primary/90">
                        Start Free Trial
                    </Button>
                </div>
            </div>
        </section>
    );
}
