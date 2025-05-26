import { Button } from "@/Components/ui/button";
import { SidebarInset } from "@/Components/ui/sidebar";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";
import PageHeader from "@/Components/PageHeader";
import { useState, useEffect, useMemo } from "react";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import TableActions from "@/Components/shared/TableActions";
import { Pencil, ChevronUp, ChevronDown } from "lucide-react";
import { Input } from "@/Components/ui/input";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";

export default function DefaultList() {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [search, setSearch] = useState("");
    const [perPage, setPerPage] = useState(10);
    const [currentPage, setCurrentPage] = useState(1);
    const [sorting, setSorting] = useState({ column: "id", direction: "desc" });

    const pages = [
        { id: 1, title: "Home Page", route: "home" },
        { id: 2, title: "About Page", route: "about" },
        { id: 3, title: "Feature Page", route: "features" },
        { id: 4, title: "Pricing Page", route: "pricing" },
        { id: 5, title: "Blog Page", route: "blogs" },
        { id: 6, title: "FAQ Page", route: "faq" },
        { id: 7, title: "Contact Page", route: "contact" },
    ];

    useEffect(() => {
        if (flash && flash.success) {
            toast({
                title: "Success",
                description: flash.success,
            });
        }

        if (flash && flash.error) {
            toast({
                variant: "destructive",
                title: "Error",
                description: flash.error,
            });
        }
    }, [flash, toast]);

    // Filter and sort pages
    const filteredAndSortedPages = useMemo(() => {
        let result = [...pages];

        // Apply search filter
        if (search) {
            const searchLower = search.toLowerCase();
            result = result.filter(
                page =>
                    page.title.toLowerCase().includes(searchLower) ||
                    page.route.toLowerCase().includes(searchLower)
            );
        }

        // Apply sorting
        result.sort((a, b) => {
            const aValue = a[sorting.column];
            const bValue = b[sorting.column];
            
            if (sorting.direction === "asc") {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });

        return result;
    }, [pages, search, sorting]);

    // Calculate pagination
    const totalPages = Math.ceil(filteredAndSortedPages.length / perPage);
    const paginatedPages = useMemo(() => {
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        return filteredAndSortedPages.slice(start, end);
    }, [filteredAndSortedPages, currentPage, perPage]);

    const handleSearch = (e) => {
        setSearch(e.target.value);
        setCurrentPage(1); // Reset to first page on search
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        setCurrentPage(1); // Reset to first page when changing items per page
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
    };

    const renderSortIcon = (column) => {
        const isActive = sorting.column === column;
        return (
            <span className="inline-flex flex-col ml-1">
                <ChevronUp
                    className={`w-3 h-3 ${
                        isActive && sorting.direction === "asc"
                            ? "text-gray-800"
                            : "text-gray-300"
                    }`}
                />
                <ChevronDown
                    className={`w-3 h-3 -mt-1 ${
                        isActive && sorting.direction === "desc"
                            ? "text-gray-800"
                            : "text-gray-300"
                    }`}
                />
            </span>
        );
    };

    const renderPageNumbers = () => {
        const pages = [];
        const maxPagesToShow = 5;

        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = startPage + maxPagesToShow - 1;

        if (endPage > totalPages) {
            endPage = totalPages;
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            pages.push(
                <Button
                    key={i}
                    variant={i === currentPage ? "default" : "outline"}
                    size="sm"
                    onClick={() => handlePageChange(i)}
                    className="mx-1"
                >
                    {i}
                </Button>
            );
        }

        return pages;
    };

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <PageHeader page="Website" subpage="List" url="pages.index" />
                <div className="p-4">
                    <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                        <div></div>
                        <div className="flex flex-col md:flex-row gap-4 md:items-center">
                            <Input
                                placeholder="Search pages..."
                                value={search}
                                onChange={(e) => handleSearch(e)}
                                className="w-full md:w-80"
                            />
                        </div>
                    </div>

                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead
                                        className="cursor-pointer"
                                        onClick={() => handleSort("id")}
                                    >
                                        ID {renderSortIcon("id")}
                                    </TableHead>
                                    <TableHead
                                        className="cursor-pointer"
                                        onClick={() => handleSort("title")}
                                    >
                                        Title {renderSortIcon("title")}
                                    </TableHead>
                                    <TableHead
                                        className="cursor-pointer"
                                        onClick={() => handleSort("route")}
                                    >
                                        Route {renderSortIcon("route")}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {paginatedPages.length > 0 ? (
                                    paginatedPages.map((page) => (
                                        <TableRow key={page.id}>
                                            <TableCell>{page.id}</TableCell>
                                            <TableCell>{page.title}</TableCell>
                                            <TableCell>{page.route}</TableCell>
                                            <TableCell className="text-right">
                                                <TableActions
                                                    actions={[
                                                        {
                                                            label: "Edit",
                                                            icon: (
                                                                <Pencil className="h-4 w-4" />
                                                            ),
                                                            href: route(
                                                                "pages.default_pages",
                                                                page.route
                                                            ),
                                                        },
                                                    ]}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="h-24 text-center"
                                        >
                                            No pages found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {filteredAndSortedPages.length > 0 && (
                        <div className="flex items-center justify-between mt-4">
                            <div className="text-sm text-gray-500">
                                Showing {(currentPage - 1) * perPage + 1} to{" "}
                                {Math.min(currentPage * perPage, filteredAndSortedPages.length)} of{" "}
                                {filteredAndSortedPages.length} entries
                            </div>
                            <div className="flex items-center space-x-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handlePageChange(1)}
                                    disabled={currentPage === 1}
                                >
                                    First
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handlePageChange(currentPage - 1)}
                                    disabled={currentPage === 1}
                                >
                                    Previous
                                </Button>
                                {renderPageNumbers()}
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handlePageChange(currentPage + 1)}
                                    disabled={currentPage === totalPages}
                                >
                                    Next
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handlePageChange(totalPages)}
                                    disabled={currentPage === totalPages}
                                >
                                    Last
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
