import { Head } from "@inertiajs/react";
import WebsiteLayout from "@/Layouts/WebsiteLayout";

export default function Privacy({ page }) {
    return (
        <WebsiteLayout>
            <Head title="Privacy Policy" />

            <div className="container mx-auto px-4 py-12">
                <div className="max-w-4xl mx-auto">
                    <h1 className="text-2xl font-bold mb-4">{page.title}</h1>
                    <div className="prose" dangerouslySetInnerHTML={{ __html: page.translations[0].body }} />
                </div>
            </div>
            
        </WebsiteLayout>
    );
}