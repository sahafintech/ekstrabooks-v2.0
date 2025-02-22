import { Button } from "@/Components/ui/button";
import { SidebarInset } from "@/Components/ui/sidebar";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Link } from "@inertiajs/react";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { useState } from "react";
import { useForm } from "@inertiajs/react";
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import TableWrapper from "@/Components/shared/TableWrapper";
import TableActions from "@/Components/shared/TableActions";
import { Pencil } from "lucide-react";

export default function DefaultList() {
  const [confirmingPageDeletion, setConfirmingPageDeletion] = useState(false);
  const [pageId] = useState(null);

  const { delete: destroy, processing, reset, clearErrors } = useForm({});

  const deletePage = (e) => {
    e.preventDefault();

    destroy(route("pages.destroy", pageId), {
      preserveScroll: true,
      onSuccess: () => closeModal(),
      onFinish: () => reset(),
    });
  };

  const closeModal = () => {
    setConfirmingPageDeletion(false);
    clearErrors();
    reset();
  };

  const pages = [
    { id: 1, title: "Home Page", route: "home" },
    { id: 2, title: "About Page", route: "about" },
    { id: 3, title: "Feature Page", route: "features" },
    { id: 4, title: "Pricing Page", route: "pricing" },
    { id: 5, title: "Blog Page", route: "blogs" },
    { id: 6, title: "FAQ Page", route: "faq" },
    { id: 7, title: "Contact Page", route: "contact" },
  ];

  const getRowActions = (page) => [
    {
      label: "Edit",
      icon: Pencil,
      onClick: () => window.location = route("pages.edit", page.id)
    }
  ];

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Website" subpage="List" url="pages.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <div className="flex">
            <div>
              <Link href={route("pages.create")}>
                <Button>Add New Page</Button>
              </Link>
            </div>
          </div>
          <div>
            <TableWrapper>
              <Table>
                <TableCaption>A list of your pages.</TableCaption>
                <TableHeader>
                  <TableRow>
                    <TableHead>Page</TableHead>
                    <TableHead>Action</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {pages.map((page) => (
                    <TableRow key={page.id}>
                      <TableCell>{page.title}</TableCell>
                      <TableCell>
                        <TableActions actions={getRowActions(page)} />
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableWrapper>
          </div>
        </div>
      </SidebarInset>
      <Modal show={confirmingPageDeletion} onClose={closeModal} maxWidth="sm">
        <form onSubmit={deletePage} className="p-6">
          <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
            Are you sure you want to delete this page?
          </h2>

          <div className="mt-6 flex justify-end">
            <Button type="button" variant="secondary" onClick={closeModal}>
              Cancel
            </Button>

            <Button variant="destructive" className="ms-3" disabled={processing}>
              Delete Page
            </Button>
          </div>
        </form>
      </Modal>
    </AuthenticatedLayout>
  );
}