import { useState } from "react";
import { Button } from "@/Components/ui/button";
import { Switch } from "@/Components/ui/switch";
import { Check, X } from "lucide-react";

export default function Pricing() {
    const [billingPeriod, setBillingPeriod] = useState("monthly");
    const [currency, setCurrency] = useState("EUR");

    const plans = [
        {
            name: "Basic",
            description: "Perfect for small businesses",
            monthlyPrice: {
                EUR: "9",
                USD: "10",
            },
            yearlyPrice: {
                EUR: "90",
                USD: "100",
            },
            features: [
                "Up to 50 contacts",
                "2 users",
                "Bank reconciliation",
                "Recurring transactions",
                "Customizable invoices",
                "Mobile apps",
                "Email support",
            ],
            notIncluded: [
                "Multi-currency",
                "Purchase orders",
                "Sales orders",
                "Inventory tracking",
                "Custom domain",
            ]
        },
        {
            name: "Standard",
            description: "Great for growing teams",
            monthlyPrice: {
                EUR: "19",
                USD: "20",
            },
            yearlyPrice: {
                EUR: "190",
                USD: "200",
            },
            popular: true,
            features: [
                "Up to 500 contacts",
                "3 users",
                "Everything in Basic",
                "Multi-currency",
                "Purchase orders",
                "Sales orders",
                "Inventory tracking",
                "Project time tracking",
                "Priority support",
            ],
            notIncluded: [
                "Custom domain",
                "Workflow rules",
            ]
        },
        {
            name: "Professional",
            description: "For established businesses",
            monthlyPrice: {
                EUR: "29",
                USD: "30",
            },
            yearlyPrice: {
                EUR: "290",
                USD: "300",
            },
            features: [
                "Unlimited contacts",
                "10 users",
                "Everything in Standard",
                "Custom domain",
                "Workflow rules",
                "Custom roles",
                "Analytics",
                "API access",
                "24/7 support",
            ],
            notIncluded: []
        }
    ];

    return (
        <section className="py-24 bg-muted/30">
            <div className="container px-4 md:px-6">
                {/* Header */}
                <div className="text-center mb-12">
                    <h2 className="text-3xl font-bold tracking-tighter sm:text-4xl md:text-5xl">
                        Simple, transparent pricing
                    </h2>
                    <p className="mt-4 text-lg text-muted-foreground">
                        Choose the perfect plan for your business
                    </p>
                </div>

                {/* Currency and Billing Toggle */}
                <div className="flex flex-col sm:flex-row items-center justify-center gap-8 mb-12">
                    {/* Currency Selector */}
                    <div className="flex items-center gap-2">
                        <select
                            value={currency}
                            onChange={(e) => setCurrency(e.target.value)}
                            className="bg-background border rounded-md px-3 py-1"
                        >
                            <option value="EUR">EUR</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>

                    {/* Billing Period Toggle */}
                    <div className="flex items-center gap-4">
                        <span className={billingPeriod === "monthly" ? "font-semibold" : "text-muted-foreground"}>
                            Monthly
                        </span>
                        <Switch
                            checked={billingPeriod === "yearly"}
                            onCheckedChange={(checked) => setBillingPeriod(checked ? "yearly" : "monthly")}
                        />
                        <div className="flex items-center gap-2">
                            <span className={billingPeriod === "yearly" ? "font-semibold" : "text-muted-foreground"}>
                                Yearly
                            </span>
                            <span className="text-sm text-green-600 bg-green-100 px-2 py-0.5 rounded">
                                Save 20%
                            </span>
                        </div>
                    </div>
                </div>

                {/* Pricing Cards */}
                <div className="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                    {plans.map((plan) => (
                        <div
                            key={plan.name}
                            className={`relative rounded-2xl border bg-background p-6 ${
                                plan.popular ? "border-primary shadow-lg" : ""
                            }`}
                        >
                            {plan.popular && (
                                <div className="absolute -top-4 left-1/2 -translate-x-1/2">
                                    <div className="bg-primary text-primary-foreground text-sm font-semibold px-3 py-1 rounded-full">
                                        Most Popular
                                    </div>
                                </div>
                            )}

                            {/* Plan Header */}
                            <div className="mb-8">
                                <h3 className="text-2xl font-bold">{plan.name}</h3>
                                <p className="text-muted-foreground mt-2">{plan.description}</p>
                                <div className="mt-4 flex items-baseline">
                                    <span className="text-4xl font-bold">
                                        {currency}{" "}
                                        {billingPeriod === "monthly"
                                            ? plan.monthlyPrice[currency]
                                            : plan.yearlyPrice[currency]}
                                    </span>
                                    <span className="text-muted-foreground ml-2">
                                        /{billingPeriod === "monthly" ? "month" : "year"}
                                    </span>
                                </div>
                            </div>

                            {/* CTA Button */}
                            <Button 
                                className={`w-full mb-8 ${
                                    plan.popular ? "bg-primary" : "bg-primary/90"
                                }`}
                            >
                                Start {plan.name} Plan
                            </Button>

                            {/* Features */}
                            <div className="space-y-4">
                                <div className="text-sm font-semibold">What's included:</div>
                                <ul className="space-y-3">
                                    {plan.features.map((feature, i) => (
                                        <li key={i} className="flex items-start gap-3">
                                            <Check className="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
                                            <span className="text-sm">{feature}</span>
                                        </li>
                                    ))}
                                </ul>

                                {plan.notIncluded.length > 0 && (
                                    <>
                                        <div className="text-sm font-semibold mt-6">Not included:</div>
                                        <ul className="space-y-3">
                                            {plan.notIncluded.map((feature, i) => (
                                                <li key={i} className="flex items-start gap-3">
                                                    <X className="h-5 w-5 text-red-500 flex-shrink-0 mt-0.5" />
                                                    <span className="text-sm text-muted-foreground">{feature}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </>
                                )}
                            </div>
                        </div>
                    ))}
                </div>

                {/* Additional Info */}
                <div className="mt-16 text-center">
                    <h3 className="text-xl font-semibold mb-4">All plans include:</h3>
                    <div className="grid sm:grid-cols-3 gap-4 max-w-3xl mx-auto">
                        <div className="p-4">
                            <div className="font-semibold mb-2">Security</div>
                            <p className="text-sm text-muted-foreground">
                                SSL encryption and regular backups
                            </p>
                        </div>
                        <div className="p-4">
                            <div className="font-semibold mb-2">Updates</div>
                            <p className="text-sm text-muted-foreground">
                                Free updates and new features
                            </p>
                        </div>
                        <div className="p-4">
                            <div className="font-semibold mb-2">Support</div>
                            <p className="text-sm text-muted-foreground">
                                Email and chat support
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
