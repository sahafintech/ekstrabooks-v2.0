import { useState } from "react";
import { Head, Link, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/Components/ui/card";
import { Download, Edit, Printer, Send, Trash2 } from "lucide-react";
import { format } from "date-fns";
import Modal from "@/Components/Modal";
import { toast } from "sonner";

const DeleteReceiptModal = ({ show, onClose, onConfirm, processing }) => (
  <Modal show={show} onClose={onClose}>
    <form onSubmit={onConfirm} className="p-6">
      <h2 className="text-lg font-medium">
        Are you sure you want to delete this receipt?
      </h2>
      <div className="mt-6 flex justify-end">
        <Button
          type="button"
          variant="secondary"
          onClick={onClose}
          className="mr-3"
        >
          Cancel
        </Button>
        <Button
          type="submit"
          variant="destructive"
          disabled={processing}
        >
          Delete Receipt
        </Button>
      </div>
    </form>
  </Modal>
);

export default function View({ receipt, business }) {
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [processing, setProcessing] = useState(false);

  const parseDate = (dateString) => {
    if (!dateString) return "";
    try {
      const date = new Date(dateString);
      return format(date, "dd/MM/yyyy");
    } catch (error) {
      console.error("Error parsing date:", error);
      return dateString;
    }
  };

  const formatCurrency = (amount, currencyCode) => {
    return `${currencyCode} ${parseFloat(amount).toFixed(2)}`;
  };

  const openDeleteModal = () => {
    setShowDeleteModal(true);
  };

  const closeDeleteModal = () => {
    setShowDeleteModal(false);
  };

  const confirmDelete = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route("receipts.destroy", receipt.id), {
      onSuccess: () => {
        toast.success("Receipt deleted successfully");
        router.visit(route("receipts.index"));
      },
      onError: () => {
        toast.error("Failed to delete receipt");
        setProcessing(false);
        closeDeleteModal();
      },
    });
  };

  const printReceipt = () => {
    window.open(route("receipts.pdf.export", receipt.id), "_blank");
  };

  const downloadReceipt = () => {
    window.location.href = route("receipts.pdf.export", receipt.id);
  };

  const sendEmail = () => {
    router.post(route("receipts.email", receipt.id), {}, {
      onSuccess: () => {
        toast.success("Email sent successfully");
      },
      onError: () => {
        toast.error("Failed to send email");
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <Head title={`Receipt #${receipt.receipt_number}`} />

      <SidebarInset>
        <PageHeader page="Receipts" subpage={`View Receipt #${receipt.receipt_number}`} url="receipts.index" />

        <div className="p-4">
          <div className="mb-6 flex justify-between items-center">
            <h1 className="text-2xl font-bold">Receipt #{receipt.receipt_number}</h1>
            <div className="flex space-x-2">
              <Button variant="outline" size="sm" onClick={printReceipt}>
                <Printer className="h-4 w-4 mr-2" />
                Print
              </Button>
              <Button variant="outline" size="sm" onClick={downloadReceipt}>
                <Download className="h-4 w-4 mr-2" />
                Download
              </Button>
              <Button variant="outline" size="sm" onClick={sendEmail}>
                <Send className="h-4 w-4 mr-2" />
                Email
              </Button>
              <Button variant="outline" size="sm" asChild>
                <Link href={route("receipts.edit", receipt.id)}>
                  <Edit className="h-4 w-4 mr-2" />
                  Edit
                </Link>
              </Button>
              <Button variant="destructive" size="sm" onClick={openDeleteModal}>
                <Trash2 className="h-4 w-4 mr-2" />
                Delete
              </Button>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
            <div className="md:col-span-12">
              <Card>
                <CardHeader>
                  <div className="flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div>
                      <CardTitle className="text-xl">{receipt.title}</CardTitle>
                      <CardDescription>
                        Order #: {receipt.order_number || "N/A"}
                      </CardDescription>
                    </div>
                    <div className="mt-4 md:mt-0 text-right">
                      <div className="text-sm text-muted-foreground">Receipt Date</div>
                      <div className="font-medium">{parseDate(receipt.receipt_date)}</div>
                    </div>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                      <h3 className="font-semibold text-sm mb-2">From</h3>
                      <div className="text-sm">
                        <p className="font-semibold">{business?.name || "Your Business"}</p>
                        <p>{business?.address || ""}</p>
                        <p>{business?.email || ""}</p>
                        <p>{business?.phone || ""}</p>
                      </div>
                    </div>
                    <div>
                      <h3 className="font-semibold text-sm mb-2">To</h3>
                      <div className="text-sm">
                        <p className="font-semibold">{receipt.customer?.name || "Customer"}</p>
                        <p>{receipt.customer?.address || ""}</p>
                        <p>{receipt.customer?.email || ""}</p>
                        <p>{receipt.customer?.phone || ""}</p>
                      </div>
                    </div>
                  </div>

                  <div className="mt-6">
                    <div className="rounded-md border">
                      <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                          <tr>
                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              Item
                            </th>
                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              Description
                            </th>
                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              Quantity
                            </th>
                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              Unit Price
                            </th>
                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              Total
                            </th>
                          </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                          {receipt.items.map((item) => (
                            <tr key={item.id}>
                              <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {item.product_name}
                              </td>
                              <td className="px-6 py-4 text-sm text-gray-500">
                                {item.description}
                              </td>
                              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {item.quantity}
                              </td>
                              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {formatCurrency(item.unit_cost, receipt.currency)}
                              </td>
                              <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {formatCurrency(item.sub_total, receipt.currency)}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div className="mt-6 flex justify-end">
                    <div className="w-full md:w-1/3 space-y-2">
                      <div className="flex justify-between text-sm">
                        <span>Subtotal:</span>
                        <span>{formatCurrency(receipt.sub_total, receipt.currency)}</span>
                      </div>
                      {receipt.discount > 0 && (
                        <div className="flex justify-between text-sm">
                          <span>Discount ({receipt.discount_type === "percentage" ? `${receipt.discount_value}%` : "Fixed"}):</span>
                          <span>-{formatCurrency(receipt.discount, receipt.currency)}</span>
                        </div>
                      )}
                      <div className="flex justify-between font-semibold">
                        <span>Total:</span>
                        <span>{formatCurrency(receipt.grand_total, receipt.currency)}</span>
                      </div>
                      {receipt.exchange_rate !== 1 && receipt.converted_total !== 0 && (
                        <div className="flex justify-between text-sm text-muted-foreground">
                          <span>Base Currency Equivalent:</span>
                          <span>{formatCurrency(receipt.converted_total, "Base")}</span>
                        </div>
                      )}
                    </div>
                  </div>
                </CardContent>
                <CardFooter>
                  {receipt.note && (
                    <div className="w-full mt-4">
                      <h3 className="font-medium text-sm mb-1">Note:</h3>
                      <p className="text-sm text-muted-foreground">{receipt.note}</p>
                    </div>
                  )}
                  {receipt.footer && (
                    <div className="w-full mt-4">
                      <p className="text-sm text-muted-foreground text-center">{receipt.footer}</p>
                    </div>
                  )}
                </CardFooter>
              </Card>
            </div>
          </div>
        </div>
      </SidebarInset>

      <DeleteReceiptModal
        show={showDeleteModal}
        onClose={closeDeleteModal}
        onConfirm={confirmDelete}
        processing={processing}
      />
    </AuthenticatedLayout>
  );
}
