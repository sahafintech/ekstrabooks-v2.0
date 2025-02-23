import { useState } from "react";
import { Button } from "@/Components/ui/button";
import { Switch } from "@/Components/ui/switch";
import {
    CheckCircle2,
    ArrowRight,
} from "lucide-react";

export default function PricingSection({ showTitle = true }) {
    const [isAnnual, setIsAnnual] = useState(true);
    const [currency, setCurrency] = useState('USD');

    const plans = [
        {
            name: "Starter",
            description: "Perfect for small businesses just getting started",
            price: {
                USD: { monthly: 15, annual: 12 },
                EUR: { monthly: 14, annual: 11 },
                GBP: { monthly: 12, annual: 10 }
            },
            features: [
                "Up to 100 monthly transactions",
                "Basic financial reports",
                "Single user access",
                "Email support",
                "Mobile app access",
                "Basic integrations"
            ]
        },
        {
            name: "Professional",
            description: "Ideal for growing businesses with more needs",
            price: {
                USD: { monthly: 35, annual: 29 },
                EUR: { monthly: 32, annual: 27 },
                GBP: { monthly: 28, annual: 24 }
            },
            popular: true,
            features: [
                "Up to 1,000 monthly transactions",
                "Advanced financial reports",
                "Multi-user access (up to 5)",
                "Priority email & chat support",
                "Mobile app access",
                "Advanced integrations",
                "Expense tracking",
                "Customizable dashboard",
                "Invoice customization"
            ]
        },
        {
            name: "Enterprise",
            description: "For large organizations with complex requirements",
            price: {
                USD: { monthly: 99, annual: 89 },
                EUR: { monthly: 89, annual: 79 },
                GBP: { monthly: 79, annual: 69 }
            },
            features: [
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
                "Custom training sessions"
            ]
        }
    ];

    const currencies = [
        { code: 'USD', symbol: '$' },
        { code: 'EUR', symbol: '€' },
        { code: 'GBP', symbol: '£' }
    ];

    const formatPrice = (price) => {
        const curr = currencies.find(c => c.code === currency);
        return `${curr.symbol}${price}`;
    };

    return (
        <section className="py-24">
            <div className="container px-4 mx-auto">
                {showTitle && (
                    <div className="max-w-3xl mx-auto text-center mb-12">
                        <h2 className="text-4xl font-bold tracking-tight mb-4">
                            Simple, transparent pricing
                        </h2>
                        <p className="text-xl text-muted-foreground">
                            Choose the perfect plan for your business needs
                        </p>
                    </div>
                )}

                {/* Pricing Toggle */}
                <div className="flex justify-center items-center gap-8 mb-12">
                    <div className="flex items-center gap-4">
                        <select
                            value={currency}
                            onChange={(e) => setCurrency(e.target.value)}
                            className="bg-transparent border rounded-md px-2 py-1"
                        >
                            {currencies.map((curr) => (
                                <option key={curr.code} value={curr.code}>
                                    {curr.code}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex items-center gap-4">
                        <span className={!isAnnual ? 'font-semibold' : 'text-muted-foreground'}>
                            Monthly
                        </span>
                        <Switch
                            checked={isAnnual}
                            onCheckedChange={setIsAnnual}
                        />
                        <span className={isAnnual ? 'font-semibold' : 'text-muted-foreground'}>
                            Annually
                        </span>
                        <span className="text-sm text-primary ml-2">
                            Save up to 20%
                        </span>
                    </div>
                </div>

                {/* Pricing Cards */}
                <div className="grid md:grid-cols-3 gap-8">
                    {plans.map((plan, index) => (
                        <div 
                            key={index}
                            className={`relative rounded-xl border bg-card p-8 ${
                                plan.popular ? 'border-primary shadow-lg' : ''
                            }`}
                        >
                            {plan.popular && (
                                <div className="absolute -top-4 left-1/2 -translate-x-1/2">
                                    <div className="bg-primary text-primary-foreground text-sm font-medium px-3 py-1 rounded-full">
                                        Most Popular
                                    </div>
                                </div>
                            )}
                            <div className="mb-8">
                                <h3 className="text-xl font-bold mb-2">{plan.name}</h3>
                                <p className="text-muted-foreground">{plan.description}</p>
                            </div>
                            <div className="mb-8">
                                <div className="flex items-baseline gap-2">
                                    <span className="text-4xl font-bold">
                                        {formatPrice(
                                            isAnnual 
                                                ? plan.price[currency].annual 
                                                : plan.price[currency].monthly
                                        )}
                                    </span>
                                    <span className="text-muted-foreground">/month</span>
                                </div>
                                {isAnnual && (
                                    <div className="text-sm text-primary mt-2">
                                        Billed annually
                                    </div>
                                )}
                            </div>
                            <div className="space-y-4 mb-8">
                                {plan.features.map((feature, featureIndex) => (
                                    <div key={featureIndex} className="flex items-center gap-2">
                                        <CheckCircle2 className="h-5 w-5 text-primary flex-shrink-0" />
                                        <span className="text-sm">{feature}</span>
                                    </div>
                                ))}
                            </div>
                            <Button className="w-full" size="lg">
                                Get Started
                                <ArrowRight className="ml-2 h-4 w-4" />
                            </Button>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
