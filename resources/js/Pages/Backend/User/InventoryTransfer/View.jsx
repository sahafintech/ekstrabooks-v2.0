import { Head, Link, useForm, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
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
    Send,
    CheckCircle,
    XCircle,
    Ban,
    Package,
    User,
    Calendar,
    MessageSquare,
    AlertTriangle
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
import { Badge } from "@/Components/ui/badge";

const printStyles = `
@media print {
      body * {
          visibility: hidden;
          -webkit-print-color-adjust: exact;
          print-color-adjust: exact;
      }

      #printable-area, #printable-area * {
          visibility: visible;
      }

      #printable-area {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          margin: 0;
          padding: 0;
          border: none;
          height: 100%;
      }

      .group.peer.hidden.text-sidebar-foreground {
          display: none !important;
      }

      @page {
          size: auto;
          margin: 10mm;
      }

      body {
          margin: 0;
          padding: 0;
      }
  }
`;

// Receive Transfer Modal
const ReceiveTransferModal = ({ transfer, isOpen, onClose, onConfirm, processing }) => {
  const [countedQuantities, setCountedQuantities] = useState({});

  useEffect(() => {
    if (isOpen && transfer.items) {
      const initial = {};
      transfer.items.forEach(item => {
        initial[item.id] = item.requested_quantity;
      });
      setCountedQuantities(initial);
    }
  }, [isOpen, transfer.items]);

  const handleQuantityChange = (itemId, quantity) => {
    setCountedQuantities(prev => ({
      ...prev,
      [itemId]: parseFloat(quantity) || 0
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    onConfirm(countedQuantities);
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-4xl">
        <DialogHeader>
          <DialogTitle>Receive Transfer</DialogTitle>
          <DialogDescription>
            Count the actual quantities received and confirm the transfer.
          </DialogDescription>
        </DialogHeader>
        
        <form onSubmit={handleSubmit}>
          <div className="max-h-96 overflow-y-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Product</TableHead>
                  <TableHead>Requested</TableHead>
                  <TableHead>Counted</TableHead>
                  <TableHead>Unit</TableHead>
                  <TableHead>Notes</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {transfer.items?.map((item) => (
                  <TableRow key={item.id}>
                    <TableCell className="font-medium">
                      {item.product.name}
                    </TableCell>
                    <TableCell>
                      {item.requested_quantity}
                    </TableCell>
                    <TableCell>
                      <Input
                        type="number"
                        step="0.01"
                        min="0"
                        value={countedQuantities[item.id] || ''}
                        onChange={(e) => handleQuantityChange(item.id, e.target.value)}
                        className="w-24"
                        required
                      />
                    </TableCell>
                    <TableCell>
                      {item.product.product_unit?.unit || 'N/A'}
                    </TableCell>
                    <TableCell>
                      {item.notes || '-'}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
          
          <DialogFooter className="mt-4">
            <Button type="button" variant="outline" onClick={onClose}>
              Cancel
            </Button>
            <Button type="submit" disabled={processing}>
              {processing ? 'Receiving...' : 'Confirm Receipt'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};

// Reject Transfer Modal
const RejectTransferModal = ({ isOpen, onClose, onConfirm, processing }) => {
  const [comment, setComment] = useState('');

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!comment.trim()) return;
    onConfirm(comment);
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Reject Transfer</DialogTitle>
          <DialogDescription>
            Please provide a reason for rejecting this transfer.
          </DialogDescription>
        </DialogHeader>
        
        <form onSubmit={handleSubmit}>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="comment">Rejection Reason</Label>
              <Textarea
                id="comment"
                placeholder="Enter reason for rejection..."
                value={comment}
                onChange={(e) => setComment(e.target.value)}
                rows={4}
                required
              />
            </div>
          </div>
          
          <DialogFooter className="mt-4">
            <Button type="button" variant="outline" onClick={onClose}>
              Cancel
            </Button>
            <Button type="submit" variant="destructive" disabled={processing || !comment.trim()}>
              {processing ? 'Rejecting...' : 'Reject Transfer'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};

export default function View({
    transfer,
    attachments,
    canEdit = false, 
    canSend = false, 
    canReceive = false, 
    canReject = false, 
    canCancel = false 
}) {
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
    const [showReceiveModal, setShowReceiveModal] = useState(false);
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [processing, setProcessing] = useState(false);

    const { data, setData, post, processing: emailProcessing, errors, reset } = useForm({
        email: transfer?.from_entity?.email || "",
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

    const handleEmailTransfer = () => {
        setIsEmailModalOpen(true);
    };

    const handleEmailSubmit = (e) => {
        e.preventDefault();
        post(route("inventory_transfers.send_email", transfer.id), {
            preserveScroll: true,
            onSuccess: () => {
                setIsEmailModalOpen(false);
                reset();
            },
        });
    };

    const handleDownloadPDF = async () => {
        setIsLoading((prev) => ({ ...prev, pdf: true }));
        try {
            // Dynamically import the required libraries
            const html2canvas = (await import("html2canvas")).default;
            const { jsPDF } = await import("jspdf");

            // Get the content element
            const content = document.querySelector("#printable-area");

            // Create a canvas from the content
            const canvas = await html2canvas(content, {
                scale: 4,
                useCORS: true, // Enable CORS for images
                logging: false,
                windowWidth: content.scrollWidth,
                windowHeight: content.scrollHeight,
                allowTaint: true,
                backgroundColor: "#ffffff",
            });

            // Calculate dimensions
            const imgWidth = 210; // A4 width in mm
            const pageHeight = 297; // A4 height in mm
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            // Create PDF with higher quality
            const pdf = new jsPDF("p", "mm", "a4");
            let heightLeft = imgHeight;
            let position = 0;
            let pageData = canvas.toDataURL("image/jpeg", 1.0);

            // Add first page
            pdf.addImage(pageData, "JPEG", 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            // Add subsequent pages if content is longer than one page
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

            // Save the PDF
            pdf.save(`Transfer_${transfer.transfer_number}.pdf`);
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
        const link = route("inventory_transfers.show_public", transfer.short_code);
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
        const text = `Check out this transfer: ${shareLink}`;
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

  const handleSend = () => {
    if (confirm('Are you sure you want to send this transfer?')) {
      setProcessing(true);
      router.post(route('inventory_transfers.send', transfer.id), {}, {
        onFinish: () => setProcessing(false)
      });
    }
  };

  const handleReceive = (countedQuantities) => {
    setProcessing(true);
    router.post(route('inventory_transfers.receive', transfer.id), {
      counted_quantities: countedQuantities
    }, {
      onSuccess: () => {
        setShowReceiveModal(false);
      },
      onFinish: () => setProcessing(false)
    });
  };

  const handleReject = (comment) => {
    setProcessing(true);
    router.post(route('inventory_transfers.reject', transfer.id), {
      comment: comment
    }, {
      onSuccess: () => {
        setShowRejectModal(false);
      },
      onFinish: () => setProcessing(false)
    });
  };

  const handleCancel = () => {
    if (confirm('Are you sure you want to cancel this transfer?')) {
      setProcessing(true);
      router.post(route('inventory_transfers.cancel', transfer.id), {}, {
        onFinish: () => setProcessing(false)
      });
    }
  };

  const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
  };

  const formatDateTime = (datetime) => {
    return new Date(datetime).toLocaleString();
  };

  const currentBusinessId = auth.user?.business_ids?.[0];
  const isOutgoing = transfer.from_entity_id === currentBusinessId;

    return (
        <AuthenticatedLayout>
            <Toaster />

            <SidebarInset>
                <style dangerouslySetInnerHTML={{ __html: printStyles }} />
                <div className="space-y-4">
                    <PageHeader
                        page="Inventory Transfers"
                        subpage={`Transfer #${transfer.transfer_number}`}
                        url="inventory_transfers.index"
                    />

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
                            onClick={handleEmailTransfer}
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
                            {isLoading.pdf ? "Downloading..." : "Download PDF"}
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="icon">
                                    <MoreVertical className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem onClick={handleShareLink}>
                                    <ShareIcon className="mr-2 h-4 w-4" />
                                    <span>Share Link</span>
                                </DropdownMenuItem>
                                {canEdit && (
                                    <DropdownMenuItem asChild>
                                        <Link
                                            href={route(
                                                "inventory_transfers.edit",
                                                transfer.id
                                            )}
                                            className="flex items-center"
                                        >
                                            <Edit className="mr-2 h-4 w-4" />
                                            <span>Edit Transfer</span>
                                        </Link>
                                    </DropdownMenuItem>
                                )}
                                {canSend && (
                                    <DropdownMenuItem onClick={handleSend}>
                                        <Send className="mr-2 h-4 w-4" />
                                        <span>Send Transfer</span>
                                    </DropdownMenuItem>
                                )}
                                {canReceive && (
                                    <DropdownMenuItem onClick={() => setShowReceiveModal(true)}>
                                        <CheckCircle className="mr-2 h-4 w-4" />
                                        <span>Receive</span>
                                    </DropdownMenuItem>
                                )}
                                {canReject && (
                                    <DropdownMenuItem onClick={() => setShowRejectModal(true)}>
                                        <XCircle className="mr-2 h-4 w-4" />
                                        <span>Reject</span>
                                    </DropdownMenuItem>
                                )}
                                {canCancel && (
                                    <DropdownMenuItem onClick={handleCancel}>
                                        <Ban className="mr-2 h-4 w-4" />
                                        <span>Cancel</span>
                                    </DropdownMenuItem>
                                )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div id="printable-area" className="lg:w-[210mm] min-h-[297mm] mx-auto rounded-md border p-4">
                        <div className="p-6 sm:p-8">
                            {/* Transfer Header */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                                <div>
                                    {transfer.from_entity.logo && (
                                        <div className="mb-3">
                                            <img
                                                src={`/uploads/media/${transfer.from_entity.logo}`}
                                                alt="Business Logo"
                                                className="max-h-32 object-contain"
                                            />
                                        </div>
                                    )}
                                    <h2 className="text-2xl font-bold text-primary">
                                        {transfer.from_entity.name}
                                    </h2>
                                    <div className="mt-2 text-sm">
                                        <p>{transfer.from_entity.address}</p>
                                        <p>{transfer.from_entity.email}</p>
                                        <p>{transfer.from_entity.phone}</p>
                                    </div>
                                </div>
                                <div className="sm:text-right">
                                    <h1 className="text-2xl font-bold">
                                        Inventory Transfer
                                    </h1>
                                    <div className="mt-2 text-sm">
                                        <p>
                                            <span className="font-medium">
                                                Transfer #:
                                            </span>{" "}
                                            {transfer.transfer_number}
                                        </p>
                                        <p>
                                            <span className="font-medium">
                                                Transfer Date:
                                            </span>{" "}
                                            {formatDate(transfer.transfer_date)}
                                        </p>
                                        <p>
                                            <span className="font-medium">
                                                Status:
                                            </span>{" "}
                                            <Badge variant={
                                                transfer.status === "received" ? "default" : 
                                                transfer.status === "sent" ? "secondary" : 
                                                transfer.status === "rejected" ? "destructive" : "outline"
                                            }>
                                                {transfer.status.charAt(0).toUpperCase() + transfer.status.slice(1)}
                                            </Badge>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div className="mt-4 grid grid-cols-2 sm:grid-cols-2 gap-4">
                                {/* Transfer details */}
                                <div>
                                    <h3 className="font-medium text-lg mb-2">
                                        Transfer Details:
                                    </h3>
                                    <div className="text-sm bg-gray-100 p-2 rounded-md">
                                        <p>From: <strong>{transfer.from_entity?.name}</strong></p>
                                        <p>To: <strong>{transfer.to_entity?.name}</strong></p>
                                        <p>Created by: <strong>{transfer.created_user?.name}</strong></p>
                                    </div>
                                </div>
                                <div className="flex justify-end">
                                    <QRCodeSVG
                                        value={route(
                                            "inventory_transfers.show",
                                            transfer.id
                                        )}
                                        size={100}
                                        level="H"
                                        includeMargin={true}
                                        margin={10}
                                        className="print:block"
                                    />
                                </div>
                            </div>

                            <SidebarSeparator className="my-6" />

                            {/* Destination Information */}
                            <div className="mb-8">
                                <h3 className="font-medium text-lg mb-2">
                                    Transfer To:
                                </h3>
                                <div className="text-sm">
                                    <p className="font-medium">
                                        {transfer.to_entity?.name}
                                    </p>
                                    <p>{transfer.to_entity?.address}</p>
                                    <p>{transfer.to_entity?.email}</p>
                                    <p>{transfer.to_entity?.phone}</p>
                                </div>
                            </div>

                            {/* Transfer Items */}
                            <div className="mb-8">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Item</TableHead>
                                            <TableHead>Notes</TableHead>
                                            <TableHead className="text-right">
                                                Requested Qty
                                            </TableHead>
                                            {transfer.status === 'received' && (
                                                <TableHead className="text-right">
                                                    Counted Qty
                                                </TableHead>
                                            )}
                                            {transfer.status === 'received' && (
                                                <TableHead className="text-right">
                                                    Difference
                                                </TableHead>
                                            )}
                                            <TableHead className="text-right">
                                                Unit
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {transfer.items?.map((item, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium">
                                                    {item.product?.name}
                                                </TableCell>
                                                <TableCell>
                                                    {item.notes || '-'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.requested_quantity}
                                                </TableCell>
                                                {transfer.status === 'received' && (
                                                    <TableCell className="text-right">
                                                        {item.counted_quantity || item.requested_quantity}
                                                    </TableCell>
                                                )}
                                                {transfer.status === 'received' && (
                                                    <TableCell className="text-right">
                                                        {(() => {
                                                            const diff = (item.counted_quantity || item.requested_quantity) - item.requested_quantity;
                                                            return (
                                                                <span className={diff === 0 ? '' : diff > 0 ? 'text-green-600' : 'text-red-600'}>
                                                                    {diff > 0 ? '+' : ''}{diff}
                                                                </span>
                                                            );
                                                        })()}
                                                    </TableCell>
                                                )}
                                                <TableCell className="text-right">
                                                    {item.product?.product_unit?.unit || 'N/A'}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Notes & Remarks */}
                            {(transfer.remarks || transfer.comments?.length > 0) && (
                                <div className="mt-8 space-y-4">
                                    {transfer.remarks && (
                                        <div>
                                            <h3 className="font-medium mb-1">
                                                Remarks:
                                            </h3>
                                            <p className="text-sm">
                                                {transfer.remarks}
                                            </p>
                                        </div>
                                    )}

                                    {transfer.comments?.length > 0 && (
                                        <div>
                                            <h3 className="font-medium mb-1">
                                                Comments:
                                            </h3>
                                            <div className="space-y-2">
                                                {transfer.comments.map((comment) => (
                                                    <div key={comment.id} className="border-l-4 border-l-red-500 pl-4 py-2 text-sm">
                                                        <div className="flex items-center gap-2 mb-1">
                                                            <Badge variant="destructive" className="text-xs">
                                                                {comment.type}
                                                            </Badge>
                                                            <span className="text-xs text-muted-foreground">
                                                                by {comment.created_user?.name} on {formatDateTime(comment.created_at)}
                                                            </span>
                                                        </div>
                                                        <p className="text-sm">{comment.comment}</p>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Attachments - hidden when printing */}
                            {attachments && attachments.length > 0 && (
                                <div className="mt-8 print:hidden">
                                    <h3 className="font-medium mb-4">Attachments:</h3>
                                    <div className="overflow-hidden border rounded-md">
                                        <table className="min-w-full divide-y divide-gray-200">
                                            <thead className="bg-gray-50">
                                                <tr>
                                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        File Name
                                                    </th>
                                                    <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        File
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white divide-y divide-gray-200">
                                                {attachments.map((attachment, index) => (
                                                    <tr key={index} className={index % 2 === 0 ? '' : 'bg-gray-50'}>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            {attachment.file_name}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <a
                                                                href={attachment.download_url || attachment.path}
                                                                target="_blank"
                                                                className="text-blue-600 hover:text-blue-800 hover:underline flex items-center"
                                                                download
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                                </svg>
                                                                Download
                                                            </a>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </SidebarInset>

            {/* Modals */}
            <ReceiveTransferModal
                transfer={transfer}
                isOpen={showReceiveModal}
                onClose={() => setShowReceiveModal(false)}
                onConfirm={handleReceive}
                processing={processing}
            />

            <RejectTransferModal
                isOpen={showRejectModal}
                onClose={() => setShowRejectModal(false)}
                onConfirm={handleReject}
                processing={processing}
            />
        </AuthenticatedLayout>
    );
}
