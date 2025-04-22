import React, { useState } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/Components/ui/table";
import { Link } from "@inertiajs/react";
import { 
  Card, 
  CardHeader, 
  CardTitle, 
  CardContent, 
} from "@/Components/ui/card";
import { Badge } from "@/Components/ui/badge";
import { FileText, Pencil } from "lucide-react";
import { format, parseISO } from "date-fns";

export default function Show({ account, combined_transactions }) {
  const [searchQuery, setSearchQuery] = useState("");
  const [filterType, setFilterType] = useState("all");
  
  // Format currency with proper symbol based on ISO 4217 codes
  const formatCurrency = (amount, currency) => {
    // Extract the ISO 4217 currency code from the currency string
    const currencyCode = currency ? currency.split(' ')[0] : account.currency ? account.currency.split(' ')[0] : 'USD';
    const symbol = getCurrencySymbol(currencyCode);
    return `${symbol}${parseFloat(amount).toFixed(2)}`;
  };
  
  // Get currency symbol from ISO 4217 currency code
  const getCurrencySymbol = (code) => {
    // More comprehensive mapping of ISO 4217 codes to symbols
    const symbols = {
      USD: '$',
      EUR: '€',
      GBP: '£',
      JPY: '¥',
      AUD: 'A$',
      CAD: 'C$',
      CHF: 'CHF',
      CNY: '¥',
      HKD: 'HK$',
      NZD: 'NZ$',
      SEK: 'kr',
      KRW: '₩',
      SGD: 'S$',
      NOK: 'kr',
      MXN: '$',
      INR: '₹',
      RUB: '₽',
      ZAR: 'R',
      TRY: '₺',
      BRL: 'R$',
      TWD: 'NT$',
      DKK: 'kr',
      PLN: 'zł',
      THB: '฿',
      IDR: 'Rp',
      HUF: 'Ft',
      CZK: 'Kč',
      ILS: '₪',
      CLP: '$',
      PHP: '₱',
      AED: 'د.إ',
      COP: '$',
      SAR: '﷼',
      MYR: 'RM',
      RON: 'lei',
    };
    
    return symbols[code] || code;
  };

  // Filter transactions based on search query and type
  const filteredTransactions = combined_transactions.filter((transaction) => {
    const searchLower = searchQuery.toLowerCase();
    const matchesSearch = 
      transaction.description?.toLowerCase().includes(searchLower) ||
      transaction.ref_type?.toLowerCase().includes(searchLower) ||
      transaction.payee_name?.toLowerCase().includes(searchLower);
    
    if (filterType === "all") return matchesSearch;
    if (filterType === "debit") return matchesSearch && transaction.dr_cr === "dr";
    if (filterType === "credit") return matchesSearch && transaction.dr_cr === "cr";
    
    return matchesSearch;
  });

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader 
          page="Chart of Accounts" 
          subpage={`Account Details - ${account.account_name}`} 
          url="accounts.index" 
        />

        <div className="p-4 pt-0">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium">Account Information</CardTitle>
              </CardHeader>
              <CardContent>
                <dl className="divide-y divide-gray-100">
                  <div className="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt className="text-sm font-medium text-gray-500">Account Code</dt>
                    <dd className="mt-1 text-sm sm:col-span-2 sm:mt-0">{account.account_code}</dd>
                  </div>
                  <div className="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt className="text-sm font-medium text-gray-500">Account Name</dt>
                    <dd className="mt-1 text-sm sm:col-span-2 sm:mt-0">{account.account_name}</dd>
                  </div>
                  <div className="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt className="text-sm font-medium text-gray-500">Account Type</dt>
                    <dd className="mt-1 text-sm sm:col-span-2 sm:mt-0">
                      <Badge variant="outline">{account.account_type}</Badge>
                    </dd>
                  </div>
                  <div className="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt className="text-sm font-medium text-gray-500">Currency</dt>
                    <dd className="mt-1 text-sm sm:col-span-2 sm:mt-0">{account.currency || "Default Currency"}</dd>
                  </div>
                  <div className="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt className="text-sm font-medium text-gray-500">Opening Date</dt>
                    <dd className="mt-1 text-sm sm:col-span-2 sm:mt-0">
                      {account.opening_date ? format(parseISO(account.opening_date), "PPP") : "N/A"}
                    </dd>
                  </div>
                  {account.account_number && (
                    <div className="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                      <dt className="text-sm font-medium text-gray-500">Account Number</dt>
                      <dd className="mt-1 text-sm sm:col-span-2 sm:mt-0">{account.account_number}</dd>
                    </div>
                  )}
                  {account.description && (
                    <div className="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                      <dt className="text-sm font-medium text-gray-500">Description</dt>
                      <dd className="mt-1 text-sm sm:col-span-2 sm:mt-0">{account.description}</dd>
                    </div>
                  )}
                </dl>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium">Balance Summary</CardTitle>
              </CardHeader>
              <CardContent>
                <dl className="divide-y divide-gray-100">
                  <div className="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt className="text-sm font-medium text-gray-500">Current Balance</dt>
                    <dd className="mt-1 text-sm font-semibold sm:col-span-2 sm:mt-0">
                      {formatCurrency(
                        combined_transactions.reduce((total, transaction) => {
                          if (transaction.dr_cr === "dr") {
                            return total + parseFloat(transaction.base_currency_amount);
                          } else {
                            return total - parseFloat(transaction.base_currency_amount);
                          }
                        }, 0),
                        account.currency
                      )}
                    </dd>
                  </div>
                  <div className="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt className="text-sm font-medium text-gray-500">Total Debits</dt>
                    <dd className="mt-1 text-sm sm:col-span-2 sm:mt-0">
                      {formatCurrency(
                        combined_transactions
                          .filter(t => t.dr_cr === "dr")
                          .reduce((total, transaction) => total + parseFloat(transaction.base_currency_amount), 0),
                        account.currency
                      )}
                    </dd>
                  </div>
                  <div className="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt className="text-sm font-medium text-gray-500">Total Credits</dt>
                    <dd className="mt-1 text-sm sm:col-span-2 sm:mt-0">
                      {formatCurrency(
                        combined_transactions
                          .filter(t => t.dr_cr === "cr")
                          .reduce((total, transaction) => total + parseFloat(transaction.base_currency_amount), 0),
                        account.currency
                      )}
                    </dd>
                  </div>
                  <div className="px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt className="text-sm font-medium text-gray-500">Transaction Count</dt>
                    <dd className="mt-1 text-sm sm:col-span-2 sm:mt-0">{combined_transactions.length}</dd>
                  </div>
                </dl>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium">Actions</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex flex-col space-y-2">
                  <Button asChild variant="outline" className="justify-start">
                    <Link href={route("accounts.edit", account.id)}>
                      <Pencil className="h-4 w-4 mr-2" />
                      Edit Account
                    </Link>
                  </Button>
                  <Button asChild variant="outline" className="justify-start">
                    <Link href={route("accounts.account_statement", account.id)}>
                      <FileText className="h-4 w-4 mr-2" />
                      Account Statement
                    </Link>
                  </Button>
                </div>
              </CardContent>
            </Card>
          </div>

          <SidebarSeparator className="my-4" />

          <div className="mb-4">
            <h2 className="text-xl font-semibold mb-4">Transaction History</h2>
            
            <div className="flex flex-wrap gap-2 mb-4 justify-between items-center">
              <div className="flex gap-2">
                <Button
                  variant={filterType === "all" ? "default" : "outline"}
                  onClick={() => setFilterType("all")}
                  className="text-sm"
                >
                  All
                </Button>
                <Button
                  variant={filterType === "debit" ? "default" : "outline"}
                  onClick={() => setFilterType("debit")}
                  className="text-sm"
                >
                  Debits
                </Button>
                <Button
                  variant={filterType === "credit" ? "default" : "outline"}
                  onClick={() => setFilterType("credit")}
                  className="text-sm"
                >
                  Credits
                </Button>
              </div>
              
              <input
                type="text"
                placeholder="Search transactions..."
                className="px-4 py-2 border rounded-md"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
            
            <div className="border rounded-md overflow-hidden">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Date</TableHead>
                    <TableHead>Description</TableHead>
                    <TableHead>Ref Type</TableHead>
                    <TableHead>Payee</TableHead>
                    <TableHead>Amount</TableHead>
                    <TableHead>Type</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredTransactions.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center py-4 text-gray-500">
                        No transactions found
                      </TableCell>
                    </TableRow>
                  ) : (
                    filteredTransactions.map((transaction, index) => (
                      <TableRow key={index}>
                        <TableCell>
                          {transaction.trans_date ? format(new Date(transaction.trans_date), "MMM dd, yyyy") : "N/A"}
                        </TableCell>
                        <TableCell>{transaction.description || "N/A"}</TableCell>
                        <TableCell>
                          <Badge variant="outline">{transaction.ref_type || "N/A"}</Badge>
                        </TableCell>
                        <TableCell>{transaction.payee_name || "N/A"}</TableCell>
                        <TableCell>
                          {formatCurrency(transaction.base_currency_amount, account.currency)}
                        </TableCell>
                        <TableCell>
                          <Badge className={transaction.dr_cr === "dr" ? "bg-blue-100 text-blue-800" : "bg-green-100 text-green-800"}>
                            {transaction.dr_cr === "dr" ? "Debit" : "Credit"}
                          </Badge>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
