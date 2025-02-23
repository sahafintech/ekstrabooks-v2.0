import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from "@/Components/ui/accordion";

export default function FAQ({ heading, subHeading, items = [] }) {
    // If no FAQ items are provided, use default ones
    const defaultFaqs = [
        {
            question: "How does the AI categorization work?",
            answer: "Our AI engine uses advanced machine learning algorithms to analyze transaction descriptions, amounts, and patterns to automatically categorize them into the appropriate accounting categories."
        },
        {
            question: "Is my data secure?",
            answer: "Yes, we take security seriously. All data is encrypted both in transit and at rest, and we use bank-level security measures to protect your information."
        },
        {
            question: "Can I export to my accounting software?",
            answer: "Yes, we support exports to major accounting software including DATEV, QuickBooks, and Xero. The export process is simple and can be done with just a few clicks."
        },
        {
            question: "How accurate is the AI categorization?",
            answer: "Our AI typically achieves 90%+ accuracy in categorization. You can always review and adjust any categorizations before exporting."
        }
    ];

    const faqItems = items.length > 0 ? items : defaultFaqs;

    return (
        <section className="container py-20">
            <div className="text-center mb-12">
                <h2 className="text-3xl font-bold mb-4">Frequently Asked Questions</h2>
                {heading && <h3 className="text-2xl mb-2">{heading}</h3>}
                {subHeading && <p className="text-muted-foreground">{subHeading}</p>}
            </div>
            
            <div className="max-w-3xl mx-auto">
                <Accordion type="single" collapsible>
                    {faqItems.map((faq, index) => (
                        <AccordionItem key={index} value={`item-${index}`}>
                            <AccordionTrigger>{faq.question}</AccordionTrigger>
                            <AccordionContent>{faq.answer}</AccordionContent>
                        </AccordionItem>
                    ))}
                </Accordion>
            </div>
        </section>
    );
}
