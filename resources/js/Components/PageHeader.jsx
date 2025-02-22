import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from "@/Components/ui/breadcrumb";
import { Separator } from "@/Components/ui/separator";
import { SidebarTrigger } from "@/Components/ui/sidebar";
import { cn } from "@/lib/utils";
import PropTypes from "prop-types";

const PageHeader = ({
    title,
    subtitle,
    breadcrumbs = [],
    actions,
    showSidebarTrigger = true,
    className,
}) => {
    return (
        <header className={cn(
            "flex flex-col gap-4 transition-[width,height] ease-linear",
            "group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12",
            className
        )}>
            <div className="flex items-center justify-between gap-4 px-4">
                <div className="flex items-center gap-2">
                    {showSidebarTrigger && (
                        <>
                            <SidebarTrigger className="-ml-1" />
                            <Separator orientation="vertical" className="mr-2 h-4" />
                        </>
                    )}
                    <Breadcrumb>
                        <BreadcrumbList>
                            {breadcrumbs.map((item, index) => (
                                <BreadcrumbItem key={index} className={cn(index < breadcrumbs.length - 1 && "hidden md:block")}>
                                    {item.url ? (
                                        <BreadcrumbLink href={route(item.url)}>{item.label}</BreadcrumbLink>
                                    ) : (
                                        <BreadcrumbPage>{item.label}</BreadcrumbPage>
                                    )}
                                    {index < breadcrumbs.length - 1 && (
                                        <BreadcrumbSeparator className="hidden md:block" />
                                    )}
                                </BreadcrumbItem>
                            ))}
                        </BreadcrumbList>
                    </Breadcrumb>
                </div>
                {actions && (
                    <div className="flex items-center gap-2">
                        {actions}
                    </div>
                )}
            </div>
            <div className="px-4">
                <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
                {subtitle && (
                    <p className="text-sm text-muted-foreground">{subtitle}</p>
                )}
            </div>
        </header>
    );
};

PageHeader.propTypes = {
    title: PropTypes.string.isRequired,
    subtitle: PropTypes.string,
    breadcrumbs: PropTypes.arrayOf(
        PropTypes.shape({
            label: PropTypes.string.isRequired,
            url: PropTypes.string,
        })
    ),
    actions: PropTypes.node,
    showSidebarTrigger: PropTypes.bool,
    className: PropTypes.string,
};

export default PageHeader;
