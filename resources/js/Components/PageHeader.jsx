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
import { Button } from "@/Components/ui/button";
import { Link, usePage } from "@inertiajs/react";

const PageHeader = ({
  page,
  subpage,
  url,
  params,
}) => {

  const userPackage = usePage().props.userPackage;

  return (
    <header className="flex h-16 shrink-0 items-center gap-2 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12">
      <div className="flex items-center justify-between w-full">
        <div className="flex items-center gap-2 px-4">
          <SidebarTrigger className="-ml-1" />
          <Separator orientation="vertical" className="mr-2 h-4" />
          <Breadcrumb>
            <BreadcrumbList>
              <BreadcrumbItem className="hidden md:block">
                <BreadcrumbLink href={params ? route(url, params) : route(url)}>
                  {page}
                </BreadcrumbLink>
              </BreadcrumbItem>
              <BreadcrumbSeparator className="hidden md:block" />
              <BreadcrumbItem>
                <BreadcrumbPage>{subpage}</BreadcrumbPage>
              </BreadcrumbItem>
            </BreadcrumbList>
          </Breadcrumb>
        </div>
        {userPackage?.pos == 1 && (
          <Link href={route('receipts.pos')} className="mr-4">
            <Button variant="outline">POS</Button>
          </Link>
        )}
      </div>
    </header>
  );
};

export default PageHeader;
