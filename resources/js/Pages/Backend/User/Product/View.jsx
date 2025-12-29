import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Link } from '@inertiajs/react';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from "@/Components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { formatCurrency } from '@/lib/utils';
import { Package, ShoppingCart, TrendingUp, Truck, BarChart3, DollarSign, Box, Calendar } from 'lucide-react';

const ProductView = ({ product, transactions, suppliers, id, activeTab }) => {

  const [currentTab, setCurrentTab] = useState(activeTab);

  const ProductStatusBadge = ({ status }) => {
    const statusMap = {
      1: { label: "Active", variant: "success" },
      0: { label: "Disabled", variant: "destructive" },
    };

    return (
      <Badge variant={statusMap[status]?.variant || 'secondary'}>
        {statusMap[status]?.label || 'Unknown'}
      </Badge>
    );
  };

  // Handle tab change
  const handleTabChange = (value) => {
    setCurrentTab(value);
    router.get(
      route("products.show", id),
      { tab: value },
      { preserveState: true }
    );
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Products" subpage="View" url="products.index" />

        <div className="flex flex-1 flex-col gap-6 p-4 pt-0">
          {/* Header Card with Product Image and Quick Info */}
          <Card className="overflow-hidden">
            <CardContent className="p-6">
              <div className="flex flex-col md:flex-row gap-6">
                {/* Product Image - Small and Rounded */}
                <div className="flex-shrink-0">
                  <div className="relative">
                    <img
                      src={`/uploads/media/${product.image}`}
                      alt={product.name}
                      className="w-24 h-24 md:w-32 md:h-32 object-cover rounded border-4 border-primary/10 shadow-lg"
                    />
                    <div className="absolute -bottom-1 -right-1">
                      <ProductStatusBadge status={product.status} />
                    </div>
                  </div>
                </div>

                {/* Product Quick Info */}
                <div className="flex-1 min-w-0">
                  <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div>
                      <h2 className="text-2xl font-bold text-foreground truncate">{product.name}</h2>
                      <div className="flex flex-wrap items-center gap-2 mt-2">
                        {product.code && (
                          <Badge variant="outline" className="font-mono">
                            {product.code}
                          </Badge>
                        )}
                        <Badge variant="secondary" className="capitalize">
                          {product.type}
                        </Badge>
                        {product.category?.name && (
                          <Badge variant="outline">
                            {product.category.name}
                          </Badge>
                        )}
                        {product.brand?.name && (
                          <Badge variant="outline">
                            {product.brand.name}
                          </Badge>
                        )}
                      </div>
                      {product.descriptions && (
                        <p className="text-sm text-muted-foreground mt-3 line-clamp-2">
                          {product.descriptions}
                        </p>
                      )}
                    </div>

                    {/* Action Buttons */}
                    <div className="flex gap-2 flex-shrink-0">
                      <Link href={route('products.edit', id)}>
                        <Button variant="outline" size="sm">Edit Product</Button>
                      </Link>
                      <Link href={route('products.index')}>
                        <Button size="sm">Back to Products</Button>
                      </Link>
                    </div>
                  </div>

                  {/* Quick Stats Row */}
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                    <div className="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                      <div className="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                        <Box className="w-4 h-4 text-blue-600 dark:text-blue-400" />
                      </div>
                      <div>
                        <p className="text-xs text-muted-foreground">Stock</p>
                        <p className="text-lg font-semibold">{product.stock}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                      <div className="p-2 bg-green-100 dark:bg-green-900/30 rounded-full">
                        <DollarSign className="w-4 h-4 text-green-600 dark:text-green-400" />
                      </div>
                      <div>
                        <p className="text-xs text-muted-foreground">Selling Price</p>
                        <p className="text-lg font-semibold">{formatCurrency({ amount: product.selling_price })}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                      <div className="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-full">
                        <ShoppingCart className="w-4 h-4 text-orange-600 dark:text-orange-400" />
                      </div>
                      <div>
                        <p className="text-xs text-muted-foreground">Purchase Cost</p>
                        <p className="text-lg font-semibold">{formatCurrency({ amount: product.purchase_cost })}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-3 p-3 bg-muted/50 rounded-lg">
                      <div className="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                        <TrendingUp className="w-4 h-4 text-purple-600 dark:text-purple-400" />
                      </div>
                      <div>
                        <p className="text-xs text-muted-foreground">Units Sold</p>
                        <p className="text-lg font-semibold">{Number(product.invoice_items_sum_quantity || 0) + Number(product.receipt_items_sum_quantity || 0)}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Tabs Section */}
          <Tabs defaultValue={currentTab} onValueChange={handleTabChange} className="w-full">
            <TabsList className="grid w-full grid-cols-3 lg:w-auto lg:inline-grid">
              <TabsTrigger value="details" className="flex items-center gap-2">
                <Package className="w-4 h-4" />
                <span className="hidden sm:inline">Product Details</span>
                <span className="sm:hidden">Details</span>
              </TabsTrigger>
              <TabsTrigger value="transactions" className="flex items-center gap-2">
                <BarChart3 className="w-4 h-4" />
                <span className="hidden sm:inline">Transactions</span>
                <span className="sm:hidden">Trans.</span>
              </TabsTrigger>
              <TabsTrigger value="suppliers" className="flex items-center gap-2">
                <Truck className="w-4 h-4" />
                <span>Suppliers</span>
              </TabsTrigger>
            </TabsList>

            {/* Product Details Tab */}
            <TabsContent value="details" className="mt-6">
              <div className="grid gap-6 md:grid-cols-2">
                {/* Basic Information Card */}
                <Card>
                  <CardHeader className="pb-3">
                    <CardTitle className="text-base flex items-center gap-2">
                      <Package className="w-4 h-4" />
                      Basic Information
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="pt-0">
                    <Table>
                      <TableBody>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground w-1/3">Name</TableCell>
                          <TableCell>{product.name}</TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Type</TableCell>
                          <TableCell className="capitalize">{product.type}</TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Code</TableCell>
                          <TableCell>{product.code || 'N/A'}</TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Unit</TableCell>
                          <TableCell>{product.product_unit?.unit || 'N/A'}</TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Category</TableCell>
                          <TableCell>{product.category?.name || 'N/A'}</TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Brand</TableCell>
                          <TableCell>{product.brand?.name || 'N/A'}</TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Status</TableCell>
                          <TableCell><ProductStatusBadge status={product.status} /></TableCell>
                        </TableRow>
                      </TableBody>
                    </Table>
                  </CardContent>
                </Card>

                {/* Stock & Inventory Card */}
                <Card>
                  <CardHeader className="pb-3">
                    <CardTitle className="text-base flex items-center gap-2">
                      <Box className="w-4 h-4" />
                      Stock & Inventory
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="pt-0">
                    <Table>
                      <TableBody>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground w-1/3">Stock Management</TableCell>
                          <TableCell>
                            <Badge variant={product.stock_management ? 'success' : 'secondary'}>
                              {product.stock_management ? 'Enabled' : 'Disabled'}
                            </Badge>
                          </TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Current Stock</TableCell>
                          <TableCell className="font-semibold">{product.stock}</TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Initial Stock</TableCell>
                          <TableCell>{product.initial_stock}</TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Reorder Point</TableCell>
                          <TableCell>{product.reorder_point || 'Not set'}</TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Expiry Date</TableCell>
                          <TableCell className="flex items-center gap-2">
                            {product.expiry_date ? (
                              <>
                                <Calendar className="w-4 h-4 text-muted-foreground" />
                                {product.expiry_date}
                              </>
                            ) : 'N/A'}
                          </TableCell>
                        </TableRow>
                      </TableBody>
                    </Table>
                  </CardContent>
                </Card>

                {/* Pricing & Sales Card */}
                <Card>
                  <CardHeader className="pb-3">
                    <CardTitle className="text-base flex items-center gap-2">
                      <DollarSign className="w-4 h-4" />
                      Pricing & Sales
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="pt-0">
                    <Table>
                      <TableBody>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground w-1/3">Selling</TableCell>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <Badge variant={product.allow_for_selling ? 'success' : 'secondary'}>
                                {product.allow_for_selling ? 'Enabled' : 'Disabled'}
                              </Badge>
                            </div>
                          </TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Selling Price</TableCell>
                          <TableCell className="font-semibold text-green-600 dark:text-green-400">
                            {formatCurrency({ amount: product.selling_price })}
                          </TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Income Account</TableCell>
                          <TableCell>{product.income_account?.account_name || 'N/A'}</TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Purchasing</TableCell>
                          <TableCell>
                            <Badge variant={product.allow_for_purchasing ? 'success' : 'secondary'}>
                              {product.allow_for_purchasing ? 'Enabled' : 'Disabled'}
                            </Badge>
                          </TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Purchase Cost</TableCell>
                          <TableCell className="font-semibold text-orange-600 dark:text-orange-400">
                            {formatCurrency({ amount: product.purchase_cost })}
                          </TableCell>
                        </TableRow>
                        <TableRow>
                          <TableCell className="font-medium text-muted-foreground">Expense Account</TableCell>
                          <TableCell>{product.expense_account?.account_name || 'N/A'}</TableCell>
                        </TableRow>
                      </TableBody>
                    </Table>
                  </CardContent>
                </Card>

                {/* Summary Statistics Card */}
                <Card>
                  <CardHeader className="pb-3">
                    <CardTitle className="text-base flex items-center gap-2">
                      <BarChart3 className="w-4 h-4" />
                      Summary Statistics
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="pt-0">
                    <div className="space-y-4">
                      {/* Sales Summary */}
                      <div>
                        <p className="text-sm font-medium text-muted-foreground mb-2">Sales Performance</p>
                        <div className="grid grid-cols-2 gap-3">
                          <div className="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-100 dark:border-green-900/30">
                            <p className="text-xs text-muted-foreground">Units Sold</p>
                            <p className="text-xl font-bold text-green-600 dark:text-green-400">
                              {Number(product.invoice_items_sum_quantity || 0) + Number(product.receipt_items_sum_quantity || 0)}
                            </p>
                          </div>
                          <div className="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-100 dark:border-green-900/30">
                            <p className="text-xs text-muted-foreground">Total Revenue</p>
                            <p className="text-xl font-bold text-green-600 dark:text-green-400">
                              {formatCurrency({ amount: Number(product.invoice_items_sum_sub_total || 0) + Number(product.receipt_items_sum_sub_total || 0) })}
                            </p>
                          </div>
                        </div>
                      </div>

                      {/* Purchase Summary */}
                      <div>
                        <p className="text-sm font-medium text-muted-foreground mb-2">Purchase History</p>
                        <div className="grid grid-cols-2 gap-3">
                          <div className="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-100 dark:border-orange-900/30">
                            <p className="text-xs text-muted-foreground">Units Purchased</p>
                            <p className="text-xl font-bold text-orange-600 dark:text-orange-400">
                              {product.purchase_items_sum_quantity || 0}
                            </p>
                          </div>
                          <div className="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-100 dark:border-orange-900/30">
                            <p className="text-xs text-muted-foreground">Total Cost</p>
                            <p className="text-xl font-bold text-orange-600 dark:text-orange-400">
                              {formatCurrency({ amount: product.purchase_items_sum_sub_total || 0 })}
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </div>
            </TabsContent>

            {/* Transactions Tab */}
            <TabsContent value="transactions" className="mt-6">
              <Card>
                <CardHeader>
                  <CardTitle className="text-base flex items-center gap-2">
                    <BarChart3 className="w-4 h-4" />
                    Transaction History
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  {transactions && transactions.length > 0 ? (
                    <div className="rounded-md border overflow-hidden">
                      <Table>
                        <TableHeader>
                          <TableRow className="bg-muted/50">
                            <TableHead>Date</TableHead>
                            <TableHead>Reference</TableHead>
                            <TableHead className="text-right">Quantity</TableHead>
                            <TableHead className="text-right">Unit Price</TableHead>
                            <TableHead className="text-right">Total</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {transactions.map((transaction, index) => (
                            <TableRow key={index} className="hover:bg-muted/30">
                              <TableCell className="font-medium">{transaction.date}</TableCell>
                              <TableCell>
                                <Link href={transaction.reference_url} className="text-primary hover:underline font-medium">
                                  {transaction.type} - {transaction.reference}
                                </Link>
                              </TableCell>
                              <TableCell className="text-right">{transaction.quantity}</TableCell>
                              <TableCell className="text-right">{formatCurrency({ amount: transaction.unit_price })}</TableCell>
                              <TableCell className="text-right font-semibold">{formatCurrency({ amount: transaction.total })}</TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </div>
                  ) : (
                    <div className="flex flex-col items-center justify-center py-12 text-center">
                      <div className="p-4 bg-muted rounded-full mb-4">
                        <BarChart3 className="w-8 h-8 text-muted-foreground" />
                      </div>
                      <p className="text-muted-foreground">No transactions found for this product.</p>
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>

            {/* Suppliers Tab */}
            <TabsContent value="suppliers" className="mt-6">
              <Card>
                <CardHeader>
                  <CardTitle className="text-base flex items-center gap-2">
                    <Truck className="w-4 h-4" />
                    Supplier Information
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  {suppliers && suppliers.length > 0 ? (
                    <div className="rounded-md border overflow-hidden">
                      <Table>
                        <TableHeader>
                          <TableRow className="bg-muted/50">
                            <TableHead>Supplier Name</TableHead>
                            <TableHead className="text-right">Purchase Count</TableHead>
                            <TableHead className="text-right">Total Quantity</TableHead>
                            <TableHead className="text-right">Average Price</TableHead>
                            <TableHead>Last Supply Date</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {suppliers.map((supplier, index) => (
                            <TableRow key={index} className="hover:bg-muted/30">
                              <TableCell className="font-medium">{supplier.name}</TableCell>
                              <TableCell className="text-right">{supplier.purchase_count || 0}</TableCell>
                              <TableCell className="text-right">{supplier.total_quantity || 0}</TableCell>
                              <TableCell className="text-right">{formatCurrency({ amount: supplier.avg_unit_cost || 0 })}</TableCell>
                              <TableCell>{supplier.last_purchase_date || 'N/A'}</TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </div>
                  ) : (
                    <div className="flex flex-col items-center justify-center py-12 text-center">
                      <div className="p-4 bg-muted rounded-full mb-4">
                        <Truck className="w-8 h-8 text-muted-foreground" />
                      </div>
                      <p className="text-muted-foreground">No suppliers found for this product.</p>
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
};

export default ProductView;
