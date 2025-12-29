import { useState } from "react";
import { Link, usePage, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PageHeader from "@/Components/PageHeader";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/Components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Badge } from "@/Components/ui/badge";
import { DataTable } from "@/Components/ui/data-table/data-table";
import { Eye, FileText, Edit, Clock, CreditCard, User, Mail, Phone, MapPin, Building, FileDown, Briefcase, Calendar, DollarSign } from "lucide-react";
import { 
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Avatar, AvatarFallback, AvatarImage } from "@/Components/ui/avatar";
import { Progress } from "@/Components/ui/progress";

export default function View({ vendor, purchase, purchases, transactions, activeTab = "overview" }) {
  const [currentTab, setCurrentTab] = useState(activeTab);
  
  // Handle tab change
  const handleTabChange = (value) => {
    setCurrentTab(value);
    router.get(route('vendors.show', vendor.id), { tab: value }, { preserveState: true });
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
    if (!purchase || !purchase.total_amount) return 0;
    return Math.min(Math.round((purchase.total_paid / purchase.total_amount) * 100), 100);
  };

  // Purchase columns
  const purchaseColumns = [
    {
      accessorKey: "reference",
      header: "Reference",
      cell: ({ row }) => (
        <Link href={route('bill_invoices.show', row.original.id)} className="text-blue-600 hover:underline">
          {row.original.reference}
        </Link>
      ),
    },
    {
      accessorKey: "purchase_date",
      header: "Date",
      cell: ({ row }) => formatDate(row.original.purchase_date),
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
      accessorKey: "payment_status",
      header: "Status",
      cell: ({ row }) => {
        const statuses = {
          "Paid": { label: "Paid", class: "bg-green-200 text-green-800" },
          "Partially Paid": { label: "Partially Paid", class: "bg-blue-200 text-blue-800" },
          "Unpaid": { label: "Unpaid", class: "bg-yellow-200 text-yellow-800" }
        };
        const status = statuses[row.original.payment_status] || { label: row.original.payment_status, class: "bg-gray-200 text-gray-800" };
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
              <Link href={route('bill_invoices.show', row.original.id)}>
                View
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <Link href={route('bill_invoices.edit', row.original.id)}>
                Edit
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <a href={`/bill_invoices/pdf/${row.original.id}`} target="_blank" rel="noopener noreferrer">
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
        <PageHeader page="Vendors" subpage="View Vendor" url="vendors.index" />

        <div className="p-4 flex flex-col gap-6">
          {/* Vendor Profile Header */}
          <div className="flex flex-col md:flex-row gap-6 items-start">
            <Card className="w-full md:w-2/3">
              <CardHeader className="pb-4">
                <div className="flex justify-between items-center">
                  <div className="flex items-center gap-4">
                    <Avatar className="h-16 w-16">
                      <AvatarImage src={vendor.profile_picture && vendor.profile_picture !== 'default.png' ? `/uploads/profile/${vendor.profile_picture}` : null} />
                      <AvatarFallback className="text-xl">{vendor.name?.charAt(0)}</AvatarFallback>
                    </Avatar>
                    <div>
                      <CardTitle className="text-2xl">{vendor.name}</CardTitle>
                      {vendor.company_name && (
                        <CardDescription className="text-base flex items-center mt-1">
                          <Building className="h-4 w-4 mr-1" /> {vendor.company_name}
                        </CardDescription>
                      )}
                    </div>
                  </div>
                  <div className="flex gap-2">
                    <Button variant="outline" asChild>
                      <Link href={route('vendors.edit', vendor.id)}>
                        <Edit className="h-4 w-4 mr-2" /> Edit
                      </Link>
                    </Button>
                    <Button variant="default" asChild>
                      <Link href={route('bill_invoices.create', { vendor_id: vendor.id })}>
                        <FileText className="h-4 w-4 mr-2" /> New Purchase
                      </Link>
                    </Button>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon">
                          <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.625 7.5C3.625 8.12132 3.12132 8.625 2.5 8.625C1.87868 8.625 1.375 8.12132 1.375 7.5C1.375 6.87868 1.87868 6.375 2.5 6.375C3.12132 6.375 3.625 6.87868 3.625 7.5ZM8.625 7.5C8.625 8.12132 8.12132 8.625 7.5 8.625C6.87868 8.625 6.375 8.12132 6.375 7.5C6.375 6.87868 6.87868 6.375 7.5 6.375C8.12132 6.375 8.625 6.87868 8.625 7.5ZM13.625 7.5C13.625 8.12132 13.1213 8.625 12.5 8.625C11.8787 8.625 11.375 8.12132 11.375 7.5C11.375 6.87868 11.8787 6.375 12.5 6.375C13.1213 6.375 13.625 6.87868 13.625 7.5Z" fill="currentColor" fillRule="evenodd" clipRule="evenodd" />
                          </svg>
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem asChild>
                          <a href={`/bill_invoices/pdf/${vendor.id}`} target="_blank" rel="noopener noreferrer">
                            <FileDown className="h-4 w-4 mr-2" /> Download PDF
                          </a>
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="border-t pt-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {vendor.email && (
                    <div className="flex items-center gap-2">
                      <Mail className="h-4 w-4 text-gray-500" />
                      <span className="text-sm text-gray-500">Email:</span>
                      <a href={`mailto:${vendor.email}`} className="text-blue-600 hover:underline">
                        {vendor.email}
                      </a>
                    </div>
                  )}
                  {vendor.mobile && (
                    <div className="flex items-center gap-2">
                      <Phone className="h-4 w-4 text-gray-500" />
                      <span className="text-sm text-gray-500">Phone:</span>
                      <span>{vendor.mobile}</span>
                    </div>
                  )}
                  {vendor.address && (
                    <div className="flex items-center gap-2">
                      <MapPin className="h-4 w-4 text-gray-500" />
                      <span className="text-sm text-gray-500">Address:</span>
                      <span>{vendor.address}</span>
                    </div>
                  )}
                  {vendor.city && (
                    <div className="flex items-center gap-2">
                      <Building className="h-4 w-4 text-gray-500" />
                      <span className="text-sm text-gray-500">City:</span>
                      <span>{vendor.city}{vendor.zip ? `, ${vendor.zip}` : ''}</span>
                    </div>
                  )}
                  {vendor.country && (
                    <div className="flex items-center gap-2">
                      <MapPin className="h-4 w-4 text-gray-500" />
                      <span className="text-sm text-gray-500">Country:</span>
                      <span>{vendor.country}</span>
                    </div>
                  )}
                  {vendor.registration_no && (
                    <div className="flex items-center gap-2">
                      <Briefcase className="h-4 w-4 text-gray-500" />
                      <span className="text-sm text-gray-500">Reg No:</span>
                      <span>{vendor.registration_no}</span>
                    </div>
                  )}
                  {vendor.vat_id && (
                    <div className="flex items-center gap-2">
                      <CreditCard className="h-4 w-4 text-gray-500" />
                      <span className="text-sm text-gray-500">VAT ID:</span>
                      <span className="uppercase">{vendor.vat_id}</span>
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>

            {/* Financial Summary Card */}
            {purchase && (
              <Card className="w-full md:w-1/3">
                <CardHeader>
                  <CardTitle className="text-lg">Financial Summary</CardTitle>
                </CardHeader>
                <CardContent className="space-y-6">
                  <div className="space-y-2">
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-500">Total Purchases</span>
                      <span className="font-medium">{formatCurrency(purchase.total_amount)}</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-500">Total Paid</span>
                      <span className="font-medium text-green-600">{formatCurrency(purchase.total_paid)}</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span className="text-gray-500">Outstanding</span>
                      <span className="font-medium text-red-600">{formatCurrency(purchase.total_amount - purchase.total_paid)}</span>
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
                      <span className="text-gray-500">Total Bills</span>
                      <span className="font-medium">{purchase.total_bill}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            )}
          </div>

          {/* Tabs Section */}
          <Tabs defaultValue={currentTab} onValueChange={handleTabChange} className="w-full">
            <TabsList className="grid grid-cols-2 md:w-[400px]">
              <TabsTrigger value="overview">Overview</TabsTrigger>
              <TabsTrigger value="transactions">Transactions</TabsTrigger>
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
                      <Link href={route('vendors.show', { id: vendor.id, tab: 'transactions' })}>
                        View All
                      </Link>
                    </Button>
                  </div>
                </CardHeader>
                <CardContent>
                  {transactions && transactions.length > 0 ? (
                    <div className="space-y-4">
                      {transactions.slice(0, 5).map((transaction) => (
                        <div key={transaction.id} className="flex items-center justify-between p-3 rounded-md border">
                          <div className="flex items-center gap-3">
                            <div className={`p-2 rounded-full ${transaction.type === 'income' ? 'bg-green-100' : 'bg-red-100'}`}>
                              {transaction.type === 'income' ? (
                                <DollarSign className="h-4 w-4 text-green-600" />
                              ) : (
                                <DollarSign className="h-4 w-4 text-red-600" />
                              )}
                            </div>
                            <div>
                              <div className="font-medium">{transaction.description}</div>
                              <div className="text-sm text-gray-500">{formatDate(transaction.trans_date)}</div>
                            </div>
                          </div>
                          <div className={`font-medium ${transaction.type === 'income' ? 'text-green-600' : 'text-red-600'}`}>
                            {transaction.type === 'income' ? '+' : '-'}{formatCurrency(transaction.amount)}
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-4 text-gray-500">No recent transactions found</div>
                  )}
                </CardContent>
              </Card>

              {/* Recent Purchases */}
              <Card>
                <CardHeader>
                  <div className="flex justify-between items-center">
                    <CardTitle className="text-lg flex items-center">
                      <CreditCard className="h-5 w-5 mr-2" /> Recent Purchases
                    </CardTitle>
                    <Button variant="outline" asChild size="sm">
                      <Link href={route('bill_invoices.index', { vendor_id: vendor.id })}>
                        View All
                      </Link>
                    </Button>
                  </div>
                </CardHeader>
                <CardContent>
                  {purchases && purchases.length > 0 ? (
                    <div className="space-y-4">
                      {purchases.slice(0, 5).map((purchase) => (
                        <div key={purchase.id} className="flex justify-between items-center border-b pb-2">
                          <div>
                            <Link href={route('bill_invoices.show', purchase.id)} className="text-blue-600 hover:underline font-medium">
                              {purchase.reference}
                            </Link>
                            <div className="text-sm text-gray-500">{formatDate(purchase.purchase_date)}</div>
                          </div>
                          <div className="text-right">
                            <div className="font-medium">{formatCurrency(purchase.grand_total)}</div>
                            <Badge className={
                              purchase.payment_status === "Paid" 
                                ? "bg-green-200 text-green-800" 
                                : purchase.payment_status === "Partially Paid" 
                                  ? "bg-blue-200 text-blue-800" 
                                  : "bg-yellow-200 text-yellow-800"
                            }>
                              {purchase.payment_status}
                            </Badge>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-4 text-gray-500">No recent purchases found</div>
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
                    {/* Vendor created */}
                    <div className="relative">
                      <div className="absolute -left-[9px] mt-1.5 h-4 w-4 rounded-full border-2 border-white bg-gray-300"></div>
                      <div className="pl-6">
                        <p className="font-medium">Vendor Created</p>
                        <p className="text-sm text-gray-500">{formatDate(vendor.created_at)}</p>
                      </div>
                    </div>

                    {/* Latest purchase if available */}
                    {purchase && purchase.total_bill > 0 && (
                      <div className="relative">
                        <div className="absolute -left-[9px] mt-1.5 h-4 w-4 rounded-full border-2 border-white bg-blue-500"></div>
                        <div className="pl-6">
                          <p className="font-medium">Latest Purchase Generated</p>
                          <p className="text-sm text-gray-500">Total Amount: {formatCurrency(purchase.total_amount)}</p>
                        </div>
                      </div>
                    )}

                    {/* Latest payment if available */}
                    {purchase && purchase.total_paid > 0 && (
                      <div className="relative">
                        <div className="absolute -left-[9px] mt-1.5 h-4 w-4 rounded-full border-2 border-white bg-green-500"></div>
                        <div className="pl-6">
                          <p className="font-medium">Latest Payment Made</p>
                          <p className="text-sm text-gray-500">Amount Paid: {formatCurrency(purchase.total_paid)}</p>
                        </div>
                      </div>
                    )}
                  </div>
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
                      <p>No transactions found for this vendor</p>
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
