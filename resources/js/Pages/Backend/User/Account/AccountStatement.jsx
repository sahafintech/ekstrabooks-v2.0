import React, { useState, useEffect } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { Badge } from "@/Components/ui/badge";
import { CalendarIcon, ArrowLeft, ArrowRight, Loader2, Download, Printer, RefreshCw } from "lucide-react";
import { format, parse, addDays, subDays } from "date-fns";
import { Calendar } from "@/Components/ui/calendar";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/Components/ui/popover";
import { cn } from "@/lib/utils";

// MyDatepicker component using the same style as in Create.jsx
const MyDatepicker = ({ id, label, value, onChange, className }) => {
  return (
    <div className="space-y-2">
      <Label htmlFor={id}>{label}</Label>
      <Popover>
        <PopoverTrigger asChild>
          <Button
            id={id}
            variant={"outline"}
            className={cn(
              "w-full justify-start text-left font-normal",
              !value && "text-muted-foreground",
              className
            )}
          >
            <CalendarIcon className="mr-2 h-4 w-4" />
            {value ? (
              format(new Date(value), "PPP")
            ) : (
              <span>Pick a date</span>
            )}
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-auto p-0" align="start">
          <Calendar
            mode="single"
            selected={value ? new Date(value) : undefined}
            onSelect={(date) =>
              onChange(date ? format(date, "yyyy-MM-dd") : "")
            }
            initialFocus
          />
        </PopoverContent>
      </Popover>
    </div>
  );
};

export default function AccountStatement({ account, transactions, balances, dateRange }) {
  const { auth } = usePage().props;
  const [fromDate, setFromDate] = useState(dateRange?.from || format(subDays(new Date(), 30), 'yyyy-MM-dd'));
  const [toDate, setToDate] = useState(dateRange?.to || format(new Date(), 'yyyy-MM-dd'));
  const [isLoading, setIsLoading] = useState(false);

  // Format currency with proper ISO 4217 code
  const formatCurrency = (amount, currencyCode = 'USD') => {
    return new Intl.NumberFormat('en-US', { 
      style: 'currency', 
      currency: currencyCode.split(' ')[0] // Extract ISO code if it has a description
    }).format(amount);
  };

  const handleDateChange = () => {
    setIsLoading(true);
    router.get(route('accounts.account_statement', account.id), 
      { date1: fromDate, date2: toDate },
      { 
        preserveState: true,
        onSuccess: () => {
          setIsLoading(false);
        },
        onError: () => {
          setIsLoading(false);
        }
      }
    );
  };

  const handlePrint = () => {
    window.print();
  };

  const handleExport = () => {
    window.location.href = route('accounts.export_account_statement', account.id);
  };

  // Quick date selection shortcuts
  const setDateRange = (range) => {
    const today = new Date();
    let newFromDate;
    
    switch(range) {
      case 'today':
        newFromDate = format(today, 'yyyy-MM-dd');
        setFromDate(newFromDate);
        setToDate(format(today, 'yyyy-MM-dd'));
        break;
      case 'yesterday':
        const yesterday = subDays(today, 1);
        newFromDate = format(yesterday, 'yyyy-MM-dd');
        setFromDate(newFromDate);
        setToDate(newFromDate);
        break;
      case 'last7days':
        newFromDate = format(subDays(today, 6), 'yyyy-MM-dd');
        setFromDate(newFromDate);
        setToDate(format(today, 'yyyy-MM-dd'));
        break;
      case 'last30days':
        newFromDate = format(subDays(today, 29), 'yyyy-MM-dd');
        setFromDate(newFromDate);
        setToDate(format(today, 'yyyy-MM-dd'));
        break;
      case 'thisMonth':
        const firstDayThisMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        newFromDate = format(firstDayThisMonth, 'yyyy-MM-dd');
        setFromDate(newFromDate);
        setToDate(format(today, 'yyyy-MM-dd'));
        break;
      case 'lastMonth':
        const firstDayLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const lastDayLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
        newFromDate = format(firstDayLastMonth, 'yyyy-MM-dd');
        setFromDate(newFromDate);
        setToDate(format(lastDayLastMonth, 'yyyy-MM-dd'));
        break;
      default:
        break;
    }
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <div className="main-content">
          <PageHeader 
            page='Chart of accounts'
            subpage={account?.account_code}
            url='accounts.index'
          />

          <div className="flex flex-1 flex-col gap-4 p-4 pt-0 print:p-0">
            {/* Account summary card */}
            <Card className="print:shadow-none print:border-none">
              <CardHeader className="print:pb-0">
                <CardTitle className="flex justify-between items-center">
                  <div>Account Details</div>
                  <Badge className="ml-2">{account?.account_type}</Badge>
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <div className="text-sm font-medium text-muted-foreground">Account Code</div>
                    <div className="font-medium">{account?.account_code}</div>
                  </div>
                  <div>
                    <div className="text-sm font-medium text-muted-foreground">Currency</div>
                    <div className="font-medium">{account?.currency || "Default Currency"}</div>
                  </div>
                  <div>
                    <div className="text-sm font-medium text-muted-foreground">Opening Balance</div>
                    <div className="font-medium">
                      {account?.opening_balance ? 
                        formatCurrency(account.opening_balance, account.currency || 'USD') : 
                        formatCurrency(0, 'USD')}
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Date Range Filter Card */}
            <Card className="print:hidden">
              <CardHeader>
                <CardTitle>Date Range</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                  <div className="flex-1">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <MyDatepicker id="fromDate" label="From Date" value={fromDate} onChange={(value) => setFromDate(value)} />
                      <MyDatepicker id="toDate" label="To Date" value={toDate} onChange={(value) => setToDate(value)} />
                    </div>
                  </div>
                  <div className="flex-1">
                    <Label>Quick Select</Label>
                    <div className="grid grid-cols-3 gap-2 mt-2">
                      <Button variant="outline" size="sm" onClick={() => setDateRange('today')}>Today</Button>
                      <Button variant="outline" size="sm" onClick={() => setDateRange('yesterday')}>Yesterday</Button>
                      <Button variant="outline" size="sm" onClick={() => setDateRange('last7days')}>Last 7 Days</Button>
                      <Button variant="outline" size="sm" onClick={() => setDateRange('last30days')}>Last 30 Days</Button>
                      <Button variant="outline" size="sm" onClick={() => setDateRange('thisMonth')}>This Month</Button>
                      <Button variant="outline" size="sm" onClick={() => setDateRange('lastMonth')}>Last Month</Button>
                    </div>
                  </div>
                  <div className="flex items-end">
                    <Button 
                      onClick={handleDateChange} 
                      disabled={isLoading}
                      className="w-full"
                    >
                      {isLoading ? <Loader2 className="h-4 w-4 mr-2 animate-spin" /> : <RefreshCw className="h-4 w-4 mr-2" />}
                      Apply
                    </Button>
                  </div>
                </div>
                <div className="flex justify-end space-x-2 mt-4">
                  <Button variant="outline" onClick={handlePrint} size="sm">
                    <Printer className="h-4 w-4 mr-2" />
                    Print
                  </Button>
                  <Button variant="outline" onClick={handleExport} size="sm">
                    <Download className="h-4 w-4 mr-2" />
                    Export
                  </Button>
                </div>
              </CardContent>
            </Card>

            {/* Transactions Table */}
            <Card className="print:shadow-none print:border-none">
              <CardHeader className="print:py-2">
                <CardTitle>Transaction History</CardTitle>
              </CardHeader>
              <CardContent className="p-0">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Date</TableHead>
                      <TableHead>Reference</TableHead>
                      <TableHead>Description</TableHead>
                      <TableHead className="text-right">Debit</TableHead>
                      <TableHead className="text-right">Credit</TableHead>
                      <TableHead className="text-right">Balance</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {transactions && transactions.length > 0 ? (
                      <>
                        <TableRow className="font-medium bg-muted">
                          <TableCell colSpan={5}>Opening Balance</TableCell>
                          <TableCell className="text-right">
                            {formatCurrency(balances?.opening || 0, account?.currency || 'USD')}
                          </TableCell>
                        </TableRow>
                        {transactions.map((transaction, index) => (
                          <TableRow key={transaction.id}>
                            <TableCell>{format(new Date(transaction.trans_date), 'dd MMM yyyy')}</TableCell>
                            <TableCell>
                              {transaction.ref_id && 
                                (transaction.ref_type ? 
                                  <Badge variant="outline" className="font-normal">
                                    {transaction.ref_type.toUpperCase()} #{transaction.ref_id}
                                  </Badge> : 
                                  transaction.ref_id)
                              }
                            </TableCell>
                            <TableCell>{transaction.description}</TableCell>
                            <TableCell className="text-right">
                              {transaction.dr_cr === 'dr' ? 
                                formatCurrency(transaction.amount, account?.currency || 'USD') : 
                                formatCurrency(0, account?.currency || 'USD')}
                            </TableCell>
                            <TableCell className="text-right">
                              {transaction.dr_cr === 'cr' ? 
                                formatCurrency(transaction.amount, account?.currency || 'USD') : 
                                formatCurrency(0, account?.currency || 'USD')}
                            </TableCell>
                            <TableCell className="text-right">
                              {formatCurrency(balances?.running[index] || 0, account?.currency || 'USD')}
                            </TableCell>
                          </TableRow>
                        ))}
                        <TableRow className="font-medium bg-muted">
                          <TableCell colSpan={5}>Closing Balance</TableCell>
                          <TableCell className="text-right">
                            {formatCurrency(balances?.closing || 0, account?.currency || 'USD')}
                          </TableCell>
                        </TableRow>
                      </>
                    ) : (
                      <TableRow>
                        <TableCell colSpan={6} className="text-center py-10">
                          No transactions found for the selected period
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
