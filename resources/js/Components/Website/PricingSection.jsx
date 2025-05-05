import { Button } from "@/Components/ui/button";
import {
    CheckCircle2,
    PhoneCall,
} from "lucide-react";

export default function PricingSection({ showTitle = true }) {
    const enterpriseFeatures = [
        "Unlimited transactions",
        "Custom financial reports",
        "Unlimited users",
        "24/7 priority support",
        "Mobile app access",
        "Premium integrations",
        "Advanced expense tracking",
        "Custom dashboard",
        "Custom invoice templates",
        "API access",
        "Dedicated account manager",
        "Custom training sessions",
        "Multi-currency support",
        "Advanced tax management",
        "Role-based access control",
        "Custom workflow automation"
    ];

    return (
        <section className="py-24">
            <div className="container px-4 mx-auto">
                {showTitle && (
                    <div className="max-w-3xl mx-auto text-center mb-12">
                        <h2 className="text-4xl font-bold tracking-tight mb-4">
                            Enterprise Solutions
                        </h2>
                        <p className="text-xl text-muted-foreground">
                            Tailored accounting solutions for businesses of all sizes
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
                            <h3 className="text-2xl font-bold mb-2">Custom Enterprise Plan</h3>
                            <p className="text-muted-foreground">Tailored solutions for organizations with specific requirements</p>
                        </div>
                        <div className="mb-8 text-center">
                            <div className="text-xl font-medium text-primary">
                                Contact us for custom pricing
                            </div>
                            <p className="text-muted-foreground mt-2">
                                Get a personalized quote based on your business needs
                            </p>
                        </div>
                        
                        {/* Features in a two-column grid */}
                        <div className="grid sm:grid-cols-2 gap-x-8 gap-y-4 mb-8">
                            {enterpriseFeatures.map((feature, featureIndex) => (
                                <div key={featureIndex} className="flex items-center gap-2">
                                    <CheckCircle2 className="h-5 w-5 text-primary flex-shrink-0" />
                                    <span className="text-sm">{feature}</span>
                                </div>
                            ))}
                        </div>
                        
                        <Button className="w-full" size="lg">
                            Contact Us
                            <PhoneCall className="ml-2 h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </section>
    );
}
