import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Textarea } from "@/Components/ui/textarea";
import { Switch } from "@/Components/ui/switch";
import { Calendar } from "@/Components/ui/calendar";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/Components/ui/popover";
import { CalendarIcon } from "lucide-react";
import { format } from "date-fns";
import { cn } from "@/lib/utils";

export default function Edit({ auth, product, productUnits = [], categories = [], brands = [], accounts = [] }) {
  const { data, setData, put, processing, errors } = useForm({
    name: product.name,
    type: product.type,
    product_unit_id: product.product_unit_id?.toString(),
    code: product.code,
    descriptions: product.descriptions,
    expiry_date: product.expiry_date,
    reorder_point: product.reorder_point,
    allow_for_selling: product.allow_for_selling,
    allow_for_purchasing: product.allow_for_purchasing,
    purchase_cost: product.purchase_cost,
    selling_price: product.selling_price,
    income_account_id: product.income_account_id?.toString(),
    expense_account_id: product.expense_account_id?.toString(),
    status: product.status,
    stock_management: product.stock_management,
    category_id: product.category_id?.toString(),
    brand_id: product.brand_id?.toString(),
    image: null,
    _method: "PUT",
  });

  const submit = (e) => {
    e.preventDefault();
    put(route("products.update", product.id), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Product updated successfully");
      },
    });
  };

  return (
    <AuthenticatedLayout user={auth.user}>
      <SidebarInset>
        <PageHeader
          page="Products"
          subpage="Edit Product"
          url="products.index"
        />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <form onSubmit={submit}>
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="image" className="md:col-span-2 col-span-12">
                Product Image
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="image"
                  type="file"
                  accept="image/*"
                  onChange={(e) => setData("image", e.target.files[0])}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.image} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="name" className="md:col-span-2 col-span-12">
                Name *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="name"
                  type="text"
                  value={data.name}
                  onChange={(e) => setData("name", e.target.value)}
                  className="md:w-1/2 w-full"
                  required
                />
                <InputError message={errors.name} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="type" className="md:col-span-2 col-span-12">
                Type *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={[
                      { id: "product", name: "Product" },
                      { id: "service", name: "Service" }
                    ]}
                    value={data.type}
                    onChange={(value) => setData("type", value)}
                    placeholder="Select type"
                  />
                </div>
                <InputError message={errors.type} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="product_unit_id" className="md:col-span-2 col-span-12">
                Unit
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={productUnits.map(unit => ({
                      id: unit.id,
                      name: unit.unit
                    }))}
                    value={data.product_unit_id}
                    onChange={(value) => setData("product_unit_id", value)}
                    placeholder="Select unit"
                  />
                </div>
                <InputError message={errors.product_unit_id} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="category_id" className="md:col-span-2 col-span-12">
                Category
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={categories.map(category => ({
                      id: category.id,
                      name: category.name
                    }))}
                    value={data.category_id}
                    onChange={(value) => setData("category_id", value)}
                    placeholder="Select category"
                  />
                </div>
                <InputError message={errors.category_id} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="brand_id" className="md:col-span-2 col-span-12">
                Brand
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={brands.map(brand => ({
                      id: brand.id,
                      name: brand.name
                    }))}
                    value={data.brand_id}
                    onChange={(value) => setData("brand_id", value)}
                    placeholder="Select brand"
                  />
                </div>
                <InputError message={errors.brand_id} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="code" className="md:col-span-2 col-span-12">
                Code
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="code"
                  type="text"
                  value={data.code}
                  onChange={(e) => setData("code", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.code} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="descriptions" className="md:col-span-2 col-span-12">
                Description
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Textarea
                  id="descriptions"
                  value={data.descriptions}
                  onChange={(e) => setData("descriptions", e.target.value)}
                  className="md:w-1/2 w-full"
                  rows={4}
                />
                <InputError message={errors.descriptions} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="expiry_date" className="md:col-span-2 col-span-12">
                Expiry Date
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Popover>
                  <PopoverTrigger asChild>
                    <Button
                      variant={"outline"}
                      className={cn(
                        "md:w-1/2 w-full justify-start text-left font-normal",
                        !data.expiry_date && "text-muted-foreground"
                      )}
                    >
                      <CalendarIcon className="mr-2 h-4 w-4" />
                      {data.expiry_date ? (
                        format(new Date(data.expiry_date), "PPP")
                      ) : (
                        <span>Pick a date</span>
                      )}
                    </Button>
                  </PopoverTrigger>
                  <PopoverContent className="w-auto p-0" align="start">
                    <Calendar
                      mode="single"
                      selected={data.expiry_date ? new Date(data.expiry_date) : undefined}
                      onSelect={(date) =>
                        setData("expiry_date", date ? format(date, "yyyy-MM-dd") : "")
                      }
                      initialFocus
                    />
                  </PopoverContent>
                </Popover>
                <InputError message={errors.expiry_date} className="text-sm" />
              </div>
            </div>

            <SidebarSeparator className="my-4" />

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="initial_stock" className="md:col-span-2 col-span-12">
                Initial Stock
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="initial_stock"
                  type="number"
                  value={data.initial_stock}
                  onChange={(e) => setData("initial_stock", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.initial_stock} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="reorder_point" className="md:col-span-2 col-span-12">
                Reorder Point
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="reorder_point"
                  type="number"
                  value={data.reorder_point}
                  onChange={(e) => setData("reorder_point", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.reorder_point} className="text-sm" />
              </div>
            </div>

            <SidebarSeparator className="my-4" />

            <div className="grid grid-cols-12 mt-2">
              <Label className="md:col-span-2 col-span-12">Allow for Selling</Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Switch
                  checked={data.allow_for_selling}
                  onCheckedChange={(checked) => setData("allow_for_selling", checked)}
                />
                <InputError message={errors.allow_for_selling} className="text-sm" />
              </div>
            </div>

            {data.allow_for_selling && (
              <>
                <div className="grid grid-cols-12 mt-2">
                  <Label htmlFor="selling_price" className="md:col-span-2 col-span-12">
                    Selling Price *
                  </Label>
                  <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                    <Input
                      id="selling_price"
                      type="number"
                      step="0.01"
                      value={data.selling_price}
                      onChange={(e) => setData("selling_price", e.target.value)}
                      className="md:w-1/2 w-full"
                      required
                    />
                    <InputError message={errors.selling_price} className="text-sm" />
                  </div>
                </div>

                <div className="grid grid-cols-12 mt-2">
                  <Label htmlFor="income_account_id" className="md:col-span-2 col-span-12">
                    Income Account *
                  </Label>
                  <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                    <div className="md:w-1/2 w-full">
                      <SearchableCombobox
                        options={accounts.map(account => ({
                          id: account.id,
                          name: account.account_name
                        }))}
                        value={data.income_account_id}
                        onChange={(value) => setData("income_account_id", value)}
                        placeholder="Select income account"
                      />
                    </div>
                    <InputError message={errors.income_account_id} className="text-sm" />
                  </div>
                </div>
              </>
            )}

            <div className="grid grid-cols-12 mt-2">
              <Label className="md:col-span-2 col-span-12">Allow for Purchasing</Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Switch
                  checked={data.allow_for_purchasing}
                  onCheckedChange={(checked) => setData("allow_for_purchasing", checked)}
                />
                <InputError message={errors.allow_for_purchasing} className="text-sm" />
              </div>
            </div>

            {data.allow_for_purchasing && (
              <>
                <div className="grid grid-cols-12 mt-2">
                  <Label htmlFor="purchase_cost" className="md:col-span-2 col-span-12">
                    Purchase Cost *
                  </Label>
                  <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                    <Input
                      id="purchase_cost"
                      type="number"
                      step="0.01"
                      value={data.purchase_cost}
                      onChange={(e) => setData("purchase_cost", e.target.value)}
                      className="md:w-1/2 w-full"
                      required
                    />
                    <InputError message={errors.purchase_cost} className="text-sm" />
                  </div>
                </div>

                <div className="grid grid-cols-12 mt-2">
                  <Label htmlFor="expense_account_id" className="md:col-span-2 col-span-12">
                    Expense Account *
                  </Label>
                  <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                    <div className="md:w-1/2 w-full">
                      <SearchableCombobox
                        options={accounts.map(account => ({
                          id: account.id,
                          name: account.account_name
                        }))}
                        value={data.expense_account_id}
                        onChange={(value) => setData("expense_account_id", value)}
                        placeholder="Select expense account"
                      />
                    </div>
                    <InputError message={errors.expense_account_id} className="text-sm" />
                  </div>
                </div>
              </>
            )}

            <SidebarSeparator className="my-4" />

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="status" className="md:col-span-2 col-span-12">
                Status *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={[
                      { id: "active", name: "Active" },
                      { id: "inactive", name: "Inactive" }
                    ]}
                    value={data.status}
                    onChange={(value) => setData("status", value)}
                    placeholder="Select status"
                  />
                </div>
                <InputError message={errors.status} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label className="md:col-span-2 col-span-12">Stock Management</Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Switch
                  checked={data.stock_management}
                  onCheckedChange={(checked) => setData("stock_management", checked)}
                />
                <InputError message={errors.stock_management} className="text-sm" />
              </div>
            </div>

            <Button type="submit" disabled={processing} className="mt-4">
              {processing ? "Updating..." : "Update Product"}
            </Button>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
