import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from "@/Components/ui/carousel";
import { Card, CardContent } from "@/Components/ui/card";

export default function Testimonials({ heading, subHeading, items }) {
    return (
        <section id="testimonial" className="container py-20">
            <div className="text-center mb-12">
                <h2 className="text-3xl font-bold mb-4">Testimonials</h2>
                <h3 className="text-2xl mb-2">{heading}</h3>
                <p className="text-muted-foreground">{subHeading}</p>
            </div>

            <Carousel
                opts={{
                    align: "start",
                    loop: true,
                }}
                className="w-full"
            >
                <CarouselContent>
                    {items.map((testimonial, index) => (
                        <CarouselItem key={index} className="md:basis-1/2 lg:basis-1/3">
                            <Card>
                                <CardContent className="p-6">
                                    <blockquote className="space-y-4">
                                        <p className="text-lg">"{testimonial.testimonial}"</p>
                                        <div className="flex items-center gap-4">
                                            <img
                                                src={testimonial.image}
                                                alt={testimonial.name}
                                                className="w-12 h-12 rounded-full object-cover"
                                            />
                                            <div>
                                                <p className="font-semibold">{testimonial.name}</p>
                                            </div>
                                        </div>
                                    </blockquote>
                                </CardContent>
                            </Card>
                        </CarouselItem>
                    ))}
                </CarouselContent>
                <CarouselPrevious />
                <CarouselNext />
            </Carousel>
        </section>
    );
}
