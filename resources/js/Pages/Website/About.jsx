import { Head } from "@inertiajs/react";
import WebsiteLayout from "@/Layouts/WebsiteLayout";
import { Button } from "@/Components/ui/button";
import {
    Users,
    BarChart3,
    Globe2,
    Award,
    ArrowRight,
} from "lucide-react";

export default function About({ pageData }) {
    const stats = [
        {
            value: "10K+",
            label: "Active Users",
            description: "Trusted by thousands of businesses globally"
        },
        {
            value: "99.9%",
            label: "Uptime",
            description: "Ensuring your business never stops"
        },
        {
            value: "24/7",
            label: "Support",
            description: "Round-the-clock expert assistance"
        },
        {
            value: "50+",
            label: "Countries",
            description: "Serving businesses worldwide"
        }
    ];

    const values = [
        {
            icon: Users,
            title: "Customer First",
            description: "We put our customers at the heart of everything we do, ensuring their success is our success."
        },
        {
            icon: BarChart3,
            title: "Innovation",
            description: "Continuously pushing boundaries to deliver cutting-edge accounting solutions."
        },
        {
            icon: Globe2,
            title: "Global Impact",
            description: "Making accounting accessible and efficient for businesses worldwide."
        },
        {
            icon: Award,
            title: "Excellence",
            description: "Committed to delivering the highest quality in every aspect of our service."
        }
    ];

    const team = [
        {
            name: "Sarah Johnson",
            role: "CEO & Co-founder",
            image: "/images/team/sarah.jpg",
            bio: "15+ years of fintech experience"
        },
        {
            name: "Michael Chen",
            role: "CTO",
            image: "/images/team/michael.jpg",
            bio: "Former Senior Engineer at Square"
        },
        {
            name: "Emma Williams",
            role: "Head of Product",
            image: "/images/team/emma.jpg",
            bio: "Product leader with startup experience"
        },
        {
            name: "David Kumar",
            role: "Head of Customer Success",
            image: "/images/team/david.jpg",
            bio: "10+ years in customer experience"
        }
    ];

    return (
        <WebsiteLayout>            
            {/* Hero Section */}
            <section className="relative overflow-hidden">
                <div className="absolute inset-0 bg-grid-pattern opacity-5" />
                <div className="container px-4 py-24 mx-auto relative">
                    <div className="max-w-3xl mx-auto text-center">
                        <h1 className="text-4xl font-bold tracking-tight sm:text-5xl mb-6">
                            Making accounting simple for modern businesses
                        </h1>
                        <p className="text-xl text-muted-foreground mb-8">
                            We're on a mission to revolutionize how businesses manage their finances,
                            making professional accounting accessible to everyone.
                        </p>
                        <Button size="lg" asChild>
                            <a href="/contact">
                                Get in Touch
                                <ArrowRight className="ml-2 h-4 w-4" />
                            </a>
                        </Button>
                    </div>
                </div>
            </section>

            {/* Stats Section */}
            <section className="border-y bg-muted/30">
                <div className="container px-4 py-16 mx-auto">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
                        {stats.map((stat, index) => (
                            <div key={index} className="text-center">
                                <div className="text-3xl font-bold mb-2">{stat.value}</div>
                                <div className="font-medium mb-1">{stat.label}</div>
                                <div className="text-sm text-muted-foreground">{stat.description}</div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Story Section */}
            <section className="py-24">
                <div className="container px-4 mx-auto">
                    <div className="grid md:grid-cols-2 gap-12 items-center">
                        <div>
                            <h2 className="text-3xl font-bold tracking-tight mb-4">Our Story</h2>
                            <p className="text-lg text-muted-foreground mb-6">
                                Founded in 2020, Ekstrabooks emerged from a simple observation: small businesses
                                needed better accounting tools. What started as a simple solution has grown into
                                a comprehensive platform trusted by thousands of businesses worldwide.
                            </p>
                            <p className="text-lg text-muted-foreground">
                                Today, we're proud to be at the forefront of financial technology, helping
                                businesses of all sizes streamline their accounting processes and make better
                                financial decisions.
                            </p>
                        </div>
                        <div className="relative aspect-square rounded-xl overflow-hidden">
                            <img 
                                src="/images/about/office.jpg" 
                                alt="Our Office" 
                                className="object-cover w-full h-full"
                            />
                        </div>
                    </div>
                </div>
            </section>

            {/* Values Section */}
            <section className="py-24 bg-muted/30">
                <div className="container px-4 mx-auto">
                    <div className="max-w-3xl mx-auto text-center mb-16">
                        <h2 className="text-3xl font-bold tracking-tight mb-4">Our Values</h2>
                        <p className="text-lg text-muted-foreground">
                            The principles that guide everything we do
                        </p>
                    </div>
                    <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                        {values.map((value, index) => (
                            <div key={index} className="text-center">
                                <div className="w-12 h-12 mx-auto mb-4 rounded-lg bg-primary/10 flex items-center justify-center">
                                    <value.icon className="h-6 w-6 text-primary" />
                                </div>
                                <h3 className="font-semibold mb-2">{value.title}</h3>
                                <p className="text-muted-foreground">{value.description}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Team Section */}
            <section className="py-24">
                <div className="container px-4 mx-auto">
                    <div className="max-w-3xl mx-auto text-center mb-16">
                        <h2 className="text-3xl font-bold tracking-tight mb-4">Meet Our Team</h2>
                        <p className="text-lg text-muted-foreground">
                            The people behind Ekstrabooks
                        </p>
                    </div>
                    <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                        {team.map((member, index) => (
                            <div key={index} className="text-center">
                                <div className="relative aspect-square rounded-xl overflow-hidden mb-4">
                                    <img 
                                        src={member.image} 
                                        alt={member.name}
                                        className="object-cover w-full h-full"
                                    />
                                </div>
                                <h3 className="font-semibold mb-1">{member.name}</h3>
                                <div className="text-primary mb-1">{member.role}</div>
                                <p className="text-sm text-muted-foreground">{member.bio}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA Section */}
            <section className="py-24 bg-muted/30">
                <div className="container px-4 mx-auto text-center">
                    <div className="max-w-3xl mx-auto">
                        <h2 className="text-3xl font-bold tracking-tight mb-4">
                            Ready to transform your accounting?
                        </h2>
                        <p className="text-lg text-muted-foreground mb-8">
                            Join thousands of businesses that trust Ekstrabooks for their accounting needs.
                        </p>
                        <div className="flex gap-4 justify-center">
                            <Button size="lg" asChild>
                                <a href="/register">
                                    Start Free Trial
                                    <ArrowRight className="ml-2 h-4 w-4" />
                                </a>
                            </Button>
                            <Button size="lg" variant="outline" asChild>
                                <a href="/contact">Contact Sales</a>
                            </Button>
                        </div>
                    </div>
                </div>
            </section>
        </WebsiteLayout>
    );
}
