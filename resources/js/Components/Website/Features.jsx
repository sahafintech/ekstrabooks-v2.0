import { Card, CardContent } from "@/Components/ui/card";
import { ReceiptIcon, CircleDollarSign, LucideReceiptText, Cloud, BarChart, AppWindow, DollarSign, ShieldCheck } from "lucide-react";

export default function Features() {
    const features = [
        {
            title: "Invoicing",
            description: "involves generating bills for goods or services, detailing amounts due, payment terms, and customer information.",
            icon: <ReceiptIcon />,
        },
        {
            title: "Online Payment",
            description: "offer convenience and speed, enabling secure transactions for goods and services through digital platforms.",
            icon: <CircleDollarSign />,
        },
        {
            title: "Tax Calculation",
            description: "involves determining the amount of tax owed based on income, deductions, credits, and applicable tax rates.",
            icon: <LucideReceiptText /> ,
        },
        {
            title: "Cloud Based-Access",
            description: "enables users to securely access applications and data from anywhere, using any internet-connected device.",
            icon: <Cloud />,
        },
        {
            title: "Advanced Reporting",
            description: "leverages sophisticated tools and analytics to provide in-depth, actionable insights for data-driven decision-making.",
            icon: <BarChart />,
        },
        {
            title: "Point Of Sale",
            description: "(POS) system streamlines sales transactions, inventory management, and customer data processing efficiently.",
            icon: <AppWindow />,
        },
        {
            title: "Multi Currency",
            description: "allow businesses to handle transactions in multiple currencies, facilitating global trade and financial operations.",
            icon: <DollarSign />,
        },
        {
            title: "System Access Controlls",
            description: "ensures authorized users can access resources while preventing unauthorized access, enhancing security.",
            icon: <ShieldCheck />,
        },
    ];

    return (
        <section className="bg-muted/30 py-24 flex justify-center">
            <div className="container px-4 md:px-6">
                <div className="flex flex-col items-center justify-center space-y-4 text-center">
                    <div className="space-y-2">
                        <h2 className="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl">
                            Explore Our Features
                        </h2>
                        <p className="mx-auto max-w-[700px] text-muted-foreground md:text-xl/relaxed lg:text-base/relaxed xl:text-xl/relaxed">
                            There are many variations of features that we offer
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
