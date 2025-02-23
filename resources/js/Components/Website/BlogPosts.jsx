import { Link } from "@inertiajs/react";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";

export default function BlogPosts({ heading, subHeading, posts }) {
    return (
        <section className="container py-20">
            <div className="text-center mb-12">
                <h2 className="text-3xl font-bold mb-4">Blog</h2>
                <h3 className="text-2xl mb-2">{heading}</h3>
                <p className="text-muted-foreground">{subHeading}</p>
            </div>

            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                {posts.map((post, index) => (
                    <Card key={index} className="overflow-hidden">
                        <div className="relative aspect-video">
                            <img
                                src={post.image}
                                alt={post.title}
                                className="absolute inset-0 w-full h-full object-cover"
                            />
                        </div>
                        <CardHeader>
                            <CardTitle>
                                <Link
                                    href={`/blogs/${post.slug}`}
                                    className="hover:text-primary transition-colors"
                                >
                                    {post.title}
                                </Link>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center text-sm text-muted-foreground">
                                <span>{post.date}</span>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </section>
    );
}
