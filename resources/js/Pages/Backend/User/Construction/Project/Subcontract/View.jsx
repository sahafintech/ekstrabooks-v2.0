import { Head, Link, router, useForm, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { formatCurrency } from "@/lib/utils";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import PageHeader from "@/Components/PageHeader";
import {
    PrinterIcon,
    MailIcon,
    DownloadIcon,
    MoreVertical,
    ShareIcon,
    Edit,
    Facebook,
    MessageCircle,
    Copy,
} from "lucide-react";
import { useState, useEffect } from "react";
import {
    Table,
    TableBody,
    TableHeader,
    TableRow,
    TableHead,
    TableCell,
} from "@/Components/ui/table";
import Modal from "@/Components/Modal";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import InputError from "@/Components/InputError";
import RichTextEditor from "@/Components/RichTextEditor";
import { QRCodeSVG } from "qrcode.react";
import PaymentList from "./PaymentList";

export default function View({
    projectSubcontract,
    attachments,
    email_templates,
    methods,
    accounts,
    activeTab,
}) {
    const [currentTab, setCurrentTab] = useState(activeTab);
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [isLoading, setIsLoading] = useState({
        print: false,
        email: false,
        pdf: false,
    });
    const [isEmailModalOpen, setIsEmailModalOpen] = useState(false);
    const [isShareModalOpen, setIsShareModalOpen] = useState(false);
    const [shareLink, setShareLink] = useState("");

    const { data, setData, post, processing, errors, reset } = useForm({
        email: projectSubcontract?.vendor?.email || "",
        subject: "",
        message: "",
        template: "",
    });

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

    const handlePrint = () => {
        setIsLoading((prev) => ({ ...prev, print: true }));
        setTimeout(() => {
            window.print();
            setIsLoading((prev) => ({ ...prev, print: false }));
        }, 300);
    };

    const handleEmailSubcontract = () => {
        setIsEmailModalOpen(true);
    };

    const handleEmailSubmit = (e) => {
        e.preventDefault();
        post(route("project_subcontracts.send_email", projectSubcontract.id), {
            preserveScroll: true,
            onSuccess: () => {
                setIsEmailModalOpen(false);
                reset();
            },
        });
    };

    // Handle tab change
    const handleTabChange = (value) => {
        setCurrentTab(value);
        router.get(
            route("project_subcontracts.show", projectSubcontract.id),
            { tab: value },
            { preserveState: true }
        );
    };

    const handleTemplateChange = (templateSlug) => {
        const template = email_templates.find((t) => t.slug === templateSlug);
        if (template) {
            setData("template", templateSlug);
            setData("subject", template.subject);
            setData("message", template.email_body);
        }
    };

    const handleDownloadPDF = async () => {
        setIsLoading((prev) => ({ ...prev, pdf: true }));
        try {
            const html2canvas = (await import("html2canvas")).default;
            const { jsPDF } = await import("jspdf");

            const content = document.querySelector(".print-container");

            const canvas = await html2canvas(content, {
                scale: 4,
                useCORS: true,
                logging: false,
                windowWidth: content.scrollWidth,
                windowHeight: content.scrollHeight,
                allowTaint: true,
                backgroundColor: "#ffffff",
            });

            const imgWidth = 210;
            const pageHeight = 297;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            const pdf = new jsPDF("p", "mm", "a4");
            let heightLeft = imgHeight;
            let position = 0;
            let pageData = canvas.toDataURL("image/jpeg", 1.0);

            pdf.addImage(pageData, "JPEG", 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(
                    pageData,
                    "JPEG",
                    0,
                    position,
                    imgWidth,
                    imgHeight
                );
                heightLeft -= pageHeight;
            }

            pdf.save(`Subcontract_${projectSubcontract.subcontract_no}.pdf`);
        } catch (error) {
            console.error("Error generating PDF:", error);
            toast({
                variant: "destructive",
                title: "Error",
                description: "Failed to generate PDF. Please try again.",
            });
        } finally {
            setIsLoading((prev) => ({ ...prev, pdf: false }));
        }
    };

    const handleShareLink = () => {
        const link = route(
            "project_subcontracts.show_public_project_subcontract",
            projectSubcontract.short_code
        );
        setShareLink(link);
        setIsShareModalOpen(true);
    };

    const handleCopyLink = () => {
        navigator.clipboard.writeText(shareLink);
        toast({
            title: "Success",
            description: "Link copied to clipboard",
        });
    };

    const handleWhatsAppShare = () => {
        const text = `Check out this subcontract: ${shareLink}`;
        window.open(
            `https://wa.me/?text=${encodeURIComponent(text)}`,
            "_blank"
        );
    };

    const handleFacebookShare = () => {
        window.open(
            `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(
                shareLink
            )}`,
            "_blank"
        );
    };

    return (
        <AuthenticatedLayout>
            <Toaster />

            <SidebarInset>
                <div className="space-y-4">
                    <PageHeader
                        page="Subcontracts"
                        subpage={`Subcontract #${projectSubcontract.subcontract_no}`}
                        url="project_subcontracts.index"
                    />

                    <Tabs
                        defaultValue={currentTab}
                        className="w-full"
                        onValueChange={handleTabChange}
                    >
                        <div className="p-4">
                            <TabsList>
                                <TabsTrigger value="contract">
                                    Contract
                                </TabsTrigger>
                                <TabsTrigger value="payments">
                                    Payments
                                </TabsTrigger>
                            </TabsList>
                        </div>
                        <TabsContent value="contract">
                            <div className="flex items-center justify-end space-x-2 mb-4">
                                <Button
                                    variant="outline"
                                    onClick={handlePrint}
                                    disabled={isLoading.print}
                                    className="flex items-center"
                                >
                                    <PrinterIcon className="mr-2 h-4 w-4" />
                                    {isLoading.print ? "Printing..." : "Print"}
                                </Button>

                                <Button
                                    variant="outline"
                                    onClick={handleEmailSubcontract}
                                    disabled={isLoading.email}
                                    className="flex items-center"
                                >
                                    <MailIcon className="mr-2 h-4 w-4" />
                                    {isLoading.email ? "Sending..." : "Email"}
                                </Button>

                                <Button
                                    variant="outline"
                                    onClick={handleDownloadPDF}
                                    disabled={isLoading.pdf}
                                    className="flex items-center"
                                >
                                    <DownloadIcon className="mr-2 h-4 w-4" />
                                    {isLoading.pdf
                                        ? "Downloading..."
                                        : "Download PDF"}
                                </Button>

                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="outline" size="icon">
                                            <MoreVertical className="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            onClick={handleShareLink}
                                        >
                                            <ShareIcon className="mr-2 h-4 w-4" />
                                            <span>Share Link</span>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem asChild>
                                            <Link
                                                href={route(
                                                    "project_subcontracts.edit",
                                                    projectSubcontract.id
                                                )}
                                                className="flex items-center"
                                            >
                                                <Edit className="mr-2 h-4 w-4" />
                                                <span>Edit Subcontract</span>
                                            </Link>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            <div className="print-container">
                                <div className="p-6 sm:p-8">
                                    {/* Subcontract Header */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                        <div>
                                            {projectSubcontract.business
                                                ?.logo && (
                                                <div className="mb-3">
                                                    <img
                                                        src={`/uploads/media/${projectSubcontract.business.logo}`}
                                                        alt="Business Logo"
                                                        className="max-h-32 object-contain"
                                                    />
                                                </div>
                                            )}
                                            <h2 className="text-2xl font-bold text-primary">
                                                {
                                                    projectSubcontract.business
                                                        ?.business_name
                                                }
                                            </h2>
                                            <div className="mt-2 text-sm">
                                                <p>
                                                    {
                                                        projectSubcontract
                                                            .business?.address
                                                    }
                                                </p>
                                                <p>
                                                    {
                                                        projectSubcontract
                                                            .business?.email
                                                    }
                                                </p>
                                                <p>
                                                    {
                                                        projectSubcontract
                                                            .business?.phone
                                                    }
                                                </p>
                                            </div>
                                        </div>
                                        <div className="md:text-right">
                                            <h1 className="text-2xl font-bold">
                                                Subcontract Agreement
                                            </h1>
                                            <div className="mt-2 text-sm">
                                                <p>
                                                    <span className="font-medium">
                                                        Subcontract #:
                                                    </span>{" "}
                                                    {
                                                        projectSubcontract.subcontract_no
                                                    }
                                                </p>
                                                <p>
                                                    <span className="font-medium">
                                                        Start Date:
                                                    </span>{" "}
                                                    {
                                                        projectSubcontract.start_date
                                                    }
                                                </p>
                                                <p>
                                                    <span className="font-medium">
                                                        End Date:
                                                    </span>{" "}
                                                    {
                                                        projectSubcontract.end_date
                                                    }
                                                </p>
                                                <p>
                                                    <span className="font-medium">
                                                        Project:
                                                    </span>{" "}
                                                    {
                                                        projectSubcontract
                                                            .project
                                                            ?.project_name
                                                    }
                                                </p>
                                            </div>
                                            <div className="mt-4 md:flex md:justify-end">
                                                <QRCodeSVG
                                                    value={route(
                                                        "project_subcontracts.show_public_project_subcontract",
                                                        projectSubcontract.short_code
                                                    )}
                                                    size={100}
                                                    level="H"
                                                    includeMargin={true}
                                                    className="print:block"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <SidebarSeparator className="my-6" />

                                    {/* Vendor Information */}
                                    <div className="mb-8">
                                        <h3 className="font-medium text-lg mb-2">
                                            Vendor Information:
                                        </h3>
                                        <div className="text-sm">
                                            <p className="font-medium">
                                                {
                                                    projectSubcontract.vendor
                                                        ?.name
                                                }
                                            </p>
                                            <p>
                                                {
                                                    projectSubcontract.vendor
                                                        ?.address
                                                }
                                            </p>
                                            <p>
                                                {
                                                    projectSubcontract.vendor
                                                        ?.email
                                                }
                                            </p>
                                            <p>
                                                {
                                                    projectSubcontract.vendor
                                                        ?.mobile
                                                }
                                            </p>
                                        </div>
                                    </div>

                                    <p className="text-sm">
                                        {projectSubcontract.description}
                                    </p>

                                    <SidebarSeparator className="my-6" />

                                    {/* Contract Items */}
                                    <div className="mb-8">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Task</TableHead>
                                                    <TableHead>
                                                        Description
                                                    </TableHead>
                                                    <TableHead>
                                                        Cost Code
                                                    </TableHead>
                                                    <TableHead>UOM</TableHead>
                                                    <TableHead className="text-right">
                                                        Quantity
                                                    </TableHead>
                                                    <TableHead className="text-right">
                                                        Unit Cost
                                                    </TableHead>
                                                    <TableHead className="text-right">
                                                        Total
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {projectSubcontract.tasks?.map(
                                                    (item, index) => (
                                                        <TableRow key={index}>
                                                            <TableCell className="font-medium">
                                                                {
                                                                    item.task
                                                                        ?.task_code
                                                                }
                                                            </TableCell>
                                                            <TableCell>
                                                                {
                                                                    item.task
                                                                        ?.description
                                                                }
                                                            </TableCell>
                                                            <TableCell>
                                                                {
                                                                    item
                                                                        .cost_code
                                                                        ?.code
                                                                }
                                                            </TableCell>
                                                            <TableCell>
                                                                {item.uom}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {item.quantity}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {formatCurrency(
                                                                    item.unit_cost,
                                                                    projectSubcontract.currency
                                                                )}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                {formatCurrency(
                                                                    item.quantity *
                                                                        item.unit_cost,
                                                                    projectSubcontract.currency
                                                                )}
                                                            </TableCell>
                                                        </TableRow>
                                                    )
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>

                                    {/* Subcontract Summary */}
                                    <div className="flex justify-end">
                                        <div className="w-full md:w-1/2 lg:w-1/3 space-y-2">
                                            <div className="flex justify-between py-2 border-t">
                                                <span className="font-medium">
                                                    Subtotal:
                                                </span>
                                                <span>
                                                    {formatCurrency(
                                                        projectSubcontract.sub_total,
                                                        projectSubcontract.currency
                                                    )}
                                                </span>
                                            </div>

                                            {/* Tax details */}
                                            {projectSubcontract.taxes?.map(
                                                (tax, index) => (
                                                    <div
                                                        key={index}
                                                        className="flex justify-between py-2"
                                                    >
                                                        <span>{tax.name}:</span>
                                                        <span>
                                                            {formatCurrency(
                                                                tax.amount,
                                                                projectSubcontract.currency
                                                            )}
                                                        </span>
                                                    </div>
                                                )
                                            )}

                                            {/* Discount */}
                                            {projectSubcontract.discount >
                                                0 && (
                                                <div className="flex justify-between py-2">
                                                    <span>Discount</span>
                                                    <span>
                                                        -
                                                        {formatCurrency(
                                                            projectSubcontract.discount,
                                                            projectSubcontract.currency
                                                        )}
                                                    </span>
                                                </div>
                                            )}

                                            {/* Total */}
                                            <div className="flex justify-between py-3 border-t border-b font-bold text-lg">
                                                <span>Total:</span>
                                                <span>
                                                    {formatCurrency(
                                                        projectSubcontract.grand_total,
                                                        projectSubcontract.currency
                                                    )}
                                                </span>
                                            </div>

                                            {/* Base currency equivalent if different currency */}
                                            {projectSubcontract.currency !==
                                                projectSubcontract.business
                                                    ?.currency && (
                                                <div className="flex justify-between py-2 text-gray-500 text-sm">
                                                    <span>Exchange Rate:</span>
                                                    <span>
                                                        1{" "}
                                                        {
                                                            projectSubcontract
                                                                .business
                                                                ?.currency
                                                        }{" "}
                                                        ={" "}
                                                        {formatCurrency(
                                                            projectSubcontract.exchange_rate,
                                                            projectSubcontract.currency
                                                        )}
                                                    </span>
                                                </div>
                                            )}

                                            {/* Base currency equivalent total */}
                                            {projectSubcontract.currency !==
                                                projectSubcontract.business
                                                    ?.currency && (
                                                <div className="flex justify-between py-2 text-sm text-gray-600">
                                                    <span>Equivalent to:</span>
                                                    <span>
                                                        {formatCurrency(
                                                            projectSubcontract.converted_total,
                                                            projectSubcontract.currency
                                                        )}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Notes & Terms */}
                                    {(projectSubcontract.note ||
                                        projectSubcontract.terms) && (
                                        <div className="mt-8 space-y-4">
                                            {projectSubcontract.note && (
                                                <div>
                                                    <h3 className="font-medium mb-1">
                                                        Notes:
                                                    </h3>
                                                    <p className="text-sm">
                                                        {
                                                            projectSubcontract.note
                                                        }
                                                    </p>
                                                </div>
                                            )}

                                            {projectSubcontract.terms && (
                                                <div>
                                                    <h3 className="font-medium mb-1">
                                                        Terms & Conditions:
                                                    </h3>
                                                    <p className="text-sm">
                                                        {
                                                            projectSubcontract.terms
                                                        }
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {/* Signatures */}
                                    <div className="mt-12 grid grid-cols-2 gap-8">
                                        <div>
                                            <div className="border-t border-gray-300 w-48 mb-2"></div>
                                            <p className="font-medium">
                                                Project Manager
                                            </p>
                                            <p className="text-sm text-gray-600">
                                                {projectSubcontract.project
                                                    ?.project_manager?.name ||
                                                    "________________"}
                                            </p>
                                        </div>
                                        <div>
                                            <div className="border-t border-gray-300 w-48 mb-2"></div>
                                            <p className="font-medium">
                                                Supplier
                                            </p>
                                            <p className="text-sm text-gray-600">
                                                {projectSubcontract.vendor
                                                    ?.name ||
                                                    "________________"}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Attachments - hidden when printing */}
                                    {attachments && attachments.length > 0 && (
                                        <div className="mt-8 print:hidden">
                                            <h3 className="font-medium mb-4">
                                                Attachments:
                                            </h3>
                                            <div className="overflow-hidden border rounded-md">
                                                <table className="min-w-full divide-y divide-gray-200">
                                                    <thead className="bg-gray-50">
                                                        <tr>
                                                            <th
                                                                scope="col"
                                                                className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                            >
                                                                File Name
                                                            </th>
                                                            <th
                                                                scope="col"
                                                                className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                            >
                                                                File
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="bg-white divide-y divide-gray-200">
                                                        {attachments.map(
                                                            (
                                                                attachment,
                                                                index
                                                            ) => (
                                                                <tr
                                                                    key={index}
                                                                    className={
                                                                        index %
                                                                            2 ===
                                                                        0
                                                                            ? ""
                                                                            : "bg-gray-50"
                                                                    }
                                                                >
                                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                                        {
                                                                            attachment.file_name
                                                                        }
                                                                    </td>
                                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                        <a
                                                                            href={`${attachment.path}`}
                                                                            target="_blank"
                                                                            className="text-blue-600 hover:text-blue-800 hover:underline flex items-center"
                                                                            download
                                                                        >
                                                                            <svg
                                                                                xmlns="http://www.w3.org/2000/svg"
                                                                                className="h-4 w-4 mr-1"
                                                                                fill="none"
                                                                                viewBox="0 0 24 24"
                                                                                stroke="currentColor"
                                                                            >
                                                                                <path
                                                                                    strokeLinecap="round"
                                                                                    strokeLinejoin="round"
                                                                                    strokeWidth={
                                                                                        2
                                                                                    }
                                                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                                                                />
                                                                            </svg>
                                                                            Download
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            )
                                                        )}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </TabsContent>
                        <TabsContent value="payments">
                            <PaymentList
                                projectSubcontract={projectSubcontract}
                                methods={methods}
                                accounts={accounts}
                            />
                        </TabsContent>
                    </Tabs>

                    {/* Email Modal */}
                    <Modal
                        show={isEmailModalOpen}
                        onClose={() => setIsEmailModalOpen(false)}
                        maxWidth="3xl"
                    >
                        <div className="mb-6">
                            <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Send Subcontract Email
                            </h2>
                        </div>

                        <form
                            onSubmit={handleEmailSubmit}
                            className="space-y-4"
                        >
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="email">
                                        Recipient Email
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData("email", e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.email}
                                        className="text-sm"
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="template">
                                        Email Template
                                    </Label>
                                    <SearchableCombobox
                                        options={email_templates?.map(
                                            (template) => ({
                                                id: template.slug,
                                                name: template.name,
                                            })
                                        )}
                                        value={data.template}
                                        onChange={handleTemplateChange}
                                        placeholder="Select a template"
                                        emptyMessage="No templates found"
                                    />
                                    <InputError
                                        message={errors.template}
                                        className="text-sm"
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="subject">Subject</Label>
                                    <Input
                                        id="subject"
                                        value={data.subject}
                                        onChange={(e) =>
                                            setData("subject", e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.subject}
                                        className="text-sm"
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Shortcode</Label>
                                    <div className="bg-gray-100 text-xs font-mono p-3 rounded border">
                                        {
                                            "{{vendorName}} {{subcontractNumber}} {{startDate}} {{endDate}} {{totalAmount}} {{subcontractLink}}"
                                        }
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="message">Message</Label>
                                    <RichTextEditor
                                        value={data.message}
                                        onChange={(content) =>
                                            setData("message", content)
                                        }
                                        height={250}
                                    />
                                    <InputError
                                        message={errors.message}
                                        className="text-sm"
                                    />
                                </div>
                            </div>

                            <div className="mt-6 flex justify-end space-x-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsEmailModalOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? "Sending..." : "Send Email"}
                                </Button>
                            </div>
                        </form>
                    </Modal>

                    {/* Share Modal */}
                    <Modal
                        show={isShareModalOpen}
                        onClose={() => setIsShareModalOpen(false)}
                        maxWidth="2xl"
                    >
                        <div className="mb-6">
                            <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Share Subcontract
                            </h2>
                        </div>

                        <div className="space-y-4">
                            <div className="flex items-center space-x-2">
                                <Input
                                    value={shareLink}
                                    readOnly
                                    className="flex-1"
                                />
                                <Button
                                    variant="outline"
                                    onClick={handleCopyLink}
                                    className="flex items-center"
                                >
                                    <Copy className="mr-2 h-4 w-4" />
                                    Copy
                                </Button>
                            </div>

                            <div className="flex justify-center space-x-4 pt-4">
                                <Button
                                    variant="outline"
                                    onClick={handleWhatsAppShare}
                                    className="flex items-center"
                                >
                                    <MessageCircle className="mr-2 h-4 w-4" />
                                    WhatsApp
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={handleFacebookShare}
                                    className="flex items-center"
                                >
                                    <Facebook className="mr-2 h-4 w-4" />
                                    Facebook
                                </Button>
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsShareModalOpen(false)}
                            >
                                Close
                            </Button>
                        </div>
                    </Modal>
                </div>
            </SidebarInset>

            {/* Print Styles */}
            <style jsx global>{`
                @media print {
                    body * {
                        visibility: hidden;
                    }
                    .print-container,
                    .print-container * {
                        visibility: visible;
                    }
                    .print-container {
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100%;
                    }

                    /* Hide action buttons when printing */
                    button,
                    .dropdown,
                    .flex.space-x-2 {
                        display: none !important;
                    }
                }
            `}</style>
        </AuthenticatedLayout>
    );
}
