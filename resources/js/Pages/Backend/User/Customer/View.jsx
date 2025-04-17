import { useState, useEffect } from "react";
import { Link, usePage, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PageHeader from "@/Components/PageHeader";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/Components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Badge } from "@/Components/ui/badge";
import { DataTable } from "@/Components/ui/data-table/data-table";
import { Eye, FileText, Download, Edit, Clock, CreditCard, User, Mail, Phone, MapPin, Building, FileUp, FileDown, Briefcase, Calendar, DollarSign } from "lucide-react";
import { 
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Avatar, AvatarFallback, AvatarImage } from "@/Components/ui/avatar";
import { Progress } from "@/Components/ui/progress";

export default function View({ customer, invoice, recent_transactions, invoices, receipts, quotations, transactions, activeTab = "overview" }) {
  const [currentTab, setCurrentTab] = useState(activeTab);
  
  // Handle tab change
  const handleTabChange = (value) => {
    setCurrentTab(value);
    router.get(route('customers.show', customer.id), { tab: value }, { preserveState: true });
  };

  // Format currency
  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(amount || 0);
  };

  // Format date
  const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  // Calculate payment percentage
  const calculatePaymentPercentage = () => {
    if (!invoice || !invoice.total_amount) return 0;
    return Math.min(Math.round((invoice.total_paid / invoice.total_amount) * 100), 100);
  };

  // Invoice columns
  const invoiceColumns = [
    {
      accessorKey: "invoice_number",
      header: "Invoice Number",
      cell: ({ row }) => (
        <Link href={route('invoices.show', row.original.id)} className="text-blue-600 hover:underline">
          {row.original.invoice_number}
        </Link>
      ),
    },
    {
      accessorKey: "invoice_date",
      header: "Date",
      cell: ({ row }) => formatDate(row.original.invoice_date),
    },
    {
      accessorKey: "due_date",
      header: "Due Date",
      cell: ({ row }) => formatDate(row.original.due_date),
    },
    {
      accessorKey: "grand_total",
      header: "Amount",
      cell: ({ row }) => formatCurrency(row.original.grand_total),
    },
    {
      accessorKey: "paid",
      header: "Paid",
      cell: ({ row }) => formatCurrency(row.original.paid),
    },
    {
      accessorKey: "status",
      header: "Status",
      cell: ({ row }) => {
        const statuses = {
          0: { label: "Draft", class: "bg-gray-200 text-gray-800" },
          1: { label: "Pending", class: "bg-yellow-200 text-yellow-800" },
          2: { label: "Paid", class: "bg-green-200 text-green-800" },
          3: { label: "Partially Paid", class: "bg-blue-200 text-blue-800" },
          4: { label: "Cancelled", class: "bg-red-200 text-red-800" },
        };
        const status = statuses[row.original.status] || statuses[0];
        return (
          <Badge className={status.class}>{status.label}</Badge>
        );
      },
    },
    {
      id: "actions",
      cell: ({ row }) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" className="h-8 w-8 p-0">
              <span className="sr-only">Open menu</span>
              <Eye className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem asChild>
              <Link href={route('invoices.show', row.original.id)}>
                View
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <Link href={route('invoices.edit', row.original.id)}>
                Edit
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <a href={`/invoices/pdf/${row.original.id}`} target="_blank" rel="noopener noreferrer">
                Download PDF
              </a>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ];

  // Transaction columns
  const transactionColumns = [
    {
      accessorKey: "trans_date",
      header: "Date",
      cell: ({ row }) => formatDate(row.original.trans_date),
    },
    {
      accessorKey: "account.account_name",
      header: "Account",
    },
    {
      accessorKey: "amount",
      header: "Amount",
      cell: ({ row }) => formatCurrency(row.original.amount),
    },
    {
      accessorKey: "type",
      header: "Type",
      cell: ({ row }) => {
        const type = row.original.type;
        return (
          <Badge className={type === 'income' ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'}>
            {type === 'income' ? 'Income' : 'Expense'}
          </Badge>
        );
      },
    },
    {
      accessorKey: "description",
      header: "Description",
      cell: ({ row }) => (
        <div className="max-w-[200px] truncate" title={row.original.description}>
          {row.original.description}
        </div>
      ),
    },
  ];

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Customers" subpage="View Customer" url="customers.index" />

        <div className="p-4 flex flex-col gap-6">
          {/* Customer Profile Header */}
          <div className="flex flex-col md:flex-row gap-6 items-start">
            <Card className="w-full md:w-2/3">
              <CardHeader className="pb-4">
                <div className="flex justify-between items-center">
                  <div className="flex items-center gap-4">
                    <Avatar className="h-16 w-16">
                      <AvatarImage src={customer.profile_picture ? `/uploads/profile/${customer.profile_picture}` : null} />
                      <AvatarFallback className="text-xl">{customer.name?.charAt(0)}</AvatarFallback>
                    </Avatar>
                    <div>
                      <CardTitle className="text-2xl">{customer.name}</CardTitle>
                      {customer.company_name && (
                        <CardDescription className="text-base flex items-center mt-1">
                          <Building className="h-4 w-4 mr-1" /> {customer.company_name}
                        </CardDescription>
                      )}
                    </div>
                  </div>
                  <div className="flex gap-2">
                    <Button variant="outline" asChild>
                      <Link href={route('customers.edit', customer.id)}>
                        <Edit className="h-4 w-4 mr-2" /> Edit
                      </Link>
                    </Button>
                    <Button variant="default" asChild>
                      <Link href={route('invoices.create', { customer_id: customer.id })}>
                        <FileText className="h-4 w-4 mr-2" /> New Invoice
                      </Link>
                    </Button>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="border-t pt-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {customer.email && (
                    <div className="flex items-center gap-2">
                      <Mail className="h-4 w-4 text-gray-500" />
                      <span className="text-sm text-gray-500">Email:</span>
                      <a href={`mailto:${customer.email}`} className="text-blue-600 hover:underline">
                        {customer.email}
                      </a>
                    </div>
                  )}
                  {customer.mobile && (
                    <div className="flex items-center gap-2">
                      <Phone className="h-4 w-4 text-gray-500" />
                      <span className="text-sm text-gray-500">Phone:</span>
                      <span>{customer.mobile}</span>
                    </div>
                  )}
                  {customer.address && (
                    <div className="flex items-center gap-2">
                      <MapPin className="h-4 w-4 text-gray-500" />
                      <span className="text-sm text-gray-500">Address:</span>
                      <span>{customer.address}</span>
                    </div>
                  )}
                  {customer.city && (
                    <div className="flex items-center gap-2">
                      <Building className="h-4 w-4 text-gray-500" />
                      <span className="text-sm text-gray-500">City:</span>
                      <span>{customer.city}{customer.zip ? `, ${customer.zip}` : ''}</span>
                    </div>
                  )}
                  {customer.country && (
                    <div className="flex items-center gap-2">
                      <MapPin className="h-4 w-4 text-gray-500" />
                      <span className="text-sm text-gray-500">Country:</span>
                      <span>{customer.country}</span>
                    </div>
                  )}
                  {customer.vat_id && (
                    <div className="flex items-center gap-2">
                      <Briefcase className="h-4 w-4 text-gray-500" />
                      <span className="text-sm text-gray-500">VAT ID:</span>
                      <span>{customer.vat_id}</span>
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>

            {/* Financial Summary Card */}
            {invoice && (
              <Card className="w-full md:w-1/3">
                <CardHeader>
                  <CardTitle className="text-lg">Financial Summary</CardTitle>
                </CardHeader>
                <CardContent className="space-y-6">
                  <div className="space-y-2">
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-500">Total Invoiced</span>
                      <span className="font-medium">{formatCurrency(invoice.total_amount)}</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-500">Total Paid</span>
                      <span className="font-medium text-green-600">{formatCurrency(invoice.total_paid)}</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-500">Outstanding</span>
                      <span className="font-medium text-red-600">{formatCurrency(invoice.total_amount - invoice.total_paid)}</span>
                    </div>
                  </div>
                  
                  <div className="space-y-2">
                    <div className="flex justify-between text-sm items-center">
                      <span className="text-gray-500">Payment Progress</span>
                      <span className="font-medium">{calculatePaymentPercentage()}%</span>
                    </div>
                    <Progress value={calculatePaymentPercentage()} className="h-2" />
                  </div>

                  <div className="pt-2 border-t">
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-500">Total Invoices</span>
                      <span className="font-medium">{invoice.total_invoice}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            )}
          </div>

          {/* Tabs Section */}
          <Tabs defaultValue={currentTab} onValueChange={handleTabChange} className="w-full">
            <TabsList className="grid grid-cols-4 md:w-[600px]">
              <TabsTrigger value="overview">Overview</TabsTrigger>
              <TabsTrigger value="invoices">Invoices</TabsTrigger>
              <TabsTrigger value="transactions">Transactions</TabsTrigger>
              <TabsTrigger value="quotations">Quotations</TabsTrigger>
            </TabsList>

            {/* Overview Tab */}
            <TabsContent value="overview" className="space-y-6">
              {/* Recent Transactions */}
              <Card>
                <CardHeader>
                  <div className="flex justify-between items-center">
                    <CardTitle className="text-lg flex items-center">
                      <Clock className="h-5 w-5 mr-2" /> Recent Transactions
                    </CardTitle>
                    <Button variant="outline" asChild size="sm">
                      <Link href={route('customers.show', { id: customer.id, tab: 'transactions' })}>
                        View All
                      </Link>
                    </Button>
                  </div>
                </CardHeader>
                <CardContent>
                  {recent_transactions && recent_transactions.length > 0 ? (
                    <div className="space-y-4">
                      {recent_transactions.map((transaction) => (
                        <div key={transaction.id} className="flex items-center justify-between p-3 rounded-md border">
                          <div className="flex items-center gap-3">
                            <div className={`p-2 rounded-full ${transaction.dr_cr === 'dr' ? 'bg-green-100' : 'bg-red-100'}`}>
                              {transaction.dr_cr === 'dr' ? (
                                <DollarSign className="h-4 w-4 text-green-600" />
                              ) : (
                                <DollarSign className="h-4 w-4 text-red-600" />
                              )}
                            </div>
                            <div>
                              <div className="font-medium">{transaction.description}</div>
                              <div className="text-sm text-gray-500">{transaction.trans_date}</div>
                            </div>
                          </div>
                          <div className={`font-medium ${transaction.dr_cr === 'dr' ? 'text-green-600' : 'text-red-600'}`}>
                            {transaction.dr_cr === 'dr' ? '+' : '-'}{transaction.transaction_amount}
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-6 text-gray-500">No recent transactions found</div>
                  )}
                </CardContent>
              </Card>

              {/* Activity Timeline */}
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg flex items-center">
                    <Calendar className="h-5 w-5 mr-2" /> Activity Timeline
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="relative border-l-2 border-gray-200 ml-3 space-y-6 py-2">
                    {/* Customer created */}
                    <div className="relative">
                      <div className="absolute -left-[9px] mt-1.5 h-4 w-4 rounded-full border-2 border-white bg-gray-300"></div>
                      <div className="pl-6">
                        <p className="font-medium">Customer Created</p>
                        <p className="text-sm text-gray-500">{formatDate(customer.created_at)}</p>
                      </div>
                    </div>

                    {/* Latest invoice if available */}
                    {invoice && invoice.total_invoice > 0 && (
                      <div className="relative">
                        <div className="absolute -left-[9px] mt-1.5 h-4 w-4 rounded-full border-2 border-white bg-blue-500"></div>
                        <div className="pl-6">
                          <p className="font-medium">Latest Invoice Generated</p>
                          <p className="text-sm text-gray-500">Total Amount: {formatCurrency(invoice.total_amount)}</p>
                        </div>
                      </div>
                    )}

                    {/* Latest payment if available */}
                    {invoice && invoice.total_paid > 0 && (
                      <div className="relative">
                        <div className="absolute -left-[9px] mt-1.5 h-4 w-4 rounded-full border-2 border-white bg-green-500"></div>
                        <div className="pl-6">
                          <p className="font-medium">Latest Payment Received</p>
                          <p className="text-sm text-gray-500">Amount Paid: {formatCurrency(invoice.total_paid)}</p>
                        </div>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            {/* Invoices Tab */}
            <TabsContent value="invoices">
              <Card>
                <CardHeader>
                  <div className="flex justify-between items-center">
                    <CardTitle className="text-lg">Invoices</CardTitle>
                    <Button variant="default" asChild>
                      <Link href={route('invoices.create', { customer_id: customer.id })}>
                        <FileText className="h-4 w-4 mr-2" /> New Invoice
                      </Link>
                    </Button>
                  </div>
                </CardHeader>
                <CardContent>
                  {invoices && invoices.length > 0 ? (
                    <DataTable columns={invoiceColumns} data={invoices} />
                  ) : (
                    <div className="text-center py-10 text-gray-500">
                      <FileText className="h-12 w-12 mx-auto mb-4 text-gray-400" />
                      <p className="mb-2">No invoices found for this customer</p>
                      <Button variant="outline" asChild>
                        <Link href={route('invoices.create', { customer_id: customer.id })}>
                          Create First Invoice
                        </Link>
                      </Button>
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>

            {/* Transactions Tab */}
            <TabsContent value="transactions">
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg">Transactions</CardTitle>
                </CardHeader>
                <CardContent>
                  {transactions && transactions.length > 0 ? (
                    <DataTable columns={transactionColumns} data={transactions} />
                  ) : (
                    <div className="text-center py-10 text-gray-500">
                      <CreditCard className="h-12 w-12 mx-auto mb-4 text-gray-400" />
                      <p>No transactions found for this customer</p>
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>

            {/* Quotations Tab */}
            <TabsContent value="quotations">
              <Card>
                <CardHeader>
                  <div className="flex justify-between items-center">
                    <CardTitle className="text-lg">Quotations</CardTitle>
                    <Button variant="default" asChild>
                      <Link href={route('quotations.create', { customer_id: customer.id })}>
                        <FileText className="h-4 w-4 mr-2" /> New Quotation
                      </Link>
                    </Button>
                  </div>
                </CardHeader>
                <CardContent>
                  {quotations && quotations.length > 0 ? (
                    <DataTable 
                      columns={[
                        {
                          accessorKey: "quotation_number",
                          header: "Quotation #",
                          cell: ({ row }) => (
                            <Link href={route('quotations.show', row.original.id)} className="text-blue-600 hover:underline">
                              {row.original.quotation_number}
                            </Link>
                          ),
                        },
                        {
                          accessorKey: "quotation_date",
                          header: "Date",
                          cell: ({ row }) => formatDate(row.original.quotation_date),
                        },
                        {
                          accessorKey: "grand_total",
                          header: "Amount",
                          cell: ({ row }) => formatCurrency(row.original.grand_total),
                        },
                        {
                          accessorKey: "status",
                          header: "Status",
                          cell: ({ row }) => {
                            const statuses = {
                              0: { label: "Pending", class: "bg-yellow-200 text-yellow-800" },
                              1: { label: "Approved", class: "bg-green-200 text-green-800" },
                              2: { label: "Rejected", class: "bg-red-200 text-red-800" },
                            };
                            const status = statuses[row.original.status] || statuses[0];
                            return (
                              <Badge className={status.class}>{status.label}</Badge>
                            );
                          },
                        },
                        {
                          id: "actions",
                          cell: ({ row }) => (
                            <DropdownMenu>
                              <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="h-8 w-8 p-0">
                                  <span className="sr-only">Open menu</span>
                                  <Eye className="h-4 w-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                <DropdownMenuItem asChild>
                                  <Link href={route('quotations.show', row.original.id)}>
                                    View
                                  </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                  <Link href={route('quotations.edit', row.original.id)}>
                                    Edit
                                  </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                  <a href={`/quotations/pdf/${row.original.id}`} target="_blank" rel="noopener noreferrer">
                                    Download PDF
                                  </a>
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          ),
                        },
                      ]} 
                      data={quotations} 
                    />
                  ) : (
                    <div className="text-center py-10 text-gray-500">
                      <FileText className="h-12 w-12 mx-auto mb-4 text-gray-400" />
                      <p className="mb-2">No quotations found for this customer</p>
                      <Button variant="outline" asChild>
                        <Link href={route('quotations.create', { customer_id: customer.id })}>
                          Create First Quotation
                        </Link>
                      </Button>
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
