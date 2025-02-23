import { Head } from "@inertiajs/react";
import WebsiteLayout from "@/Layouts/WebsiteLayout";
import Hero from "@/Components/Website/Hero";
import Features from "@/Components/Website/Features";
import Pricing from "@/Components/Website/PricingSection";
import HowItWorks from "@/Components/Website/HowItWorks";
import Newsletter from "@/Components/Website/Newsletter";

export default function Index({ pageData, pageMedia, features, packages }) {
    return (
        <WebsiteLayout>
            <Head title={pageData?.title || 'Home'} />
            
            <Hero 
                heading={pageData?.hero_heading}
                subHeading={pageData?.hero_sub_heading}
                bgImage={pageMedia?.hero_bg_image}
            />
            
            {pageData?.features_status && (
                <Features 
                    features={features}
                />
            )}
            
            {pageData?.pricing_status && (
                <Pricing />
            )}
            
            <HowItWorks />
            
            {pageData?.newsletter_status && (
                <Newsletter 
                    heading={pageData?.newsletter_heading}
                    subHeading={pageData?.newsletter_sub_heading}
                    bgImage={pageMedia?.newsletter_bg_image}
                />
            )}
        </WebsiteLayout>
    );
}