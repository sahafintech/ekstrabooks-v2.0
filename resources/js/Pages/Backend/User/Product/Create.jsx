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
import DateTimePicker from "@/Components/DateTimePicker";
import { useState } from "react";
import { X } from "lucide-react";

export default function Create({ productUnits = [], categories = [], brands = [], accounts = [] }) {
  const [imagePreview, setImagePreview] = useState(null);

  const { data, setData, post, processing, errors, reset } = useForm({
    name: "",
    type: "product",
    product_unit_id: "",
    code: "",
    descriptions: "",
    expiry_date: "",
    reorder_point: "",
    initial_stock: "0",
    allow_for_selling: true,
    allow_for_purchasing: false,
    purchase_cost: "0",
    selling_price: "0",
    income_account_id: "",
    expense_account_id: "",
    status: "1",
    stock_management: true,
    image: null,
    sub_category_id: "",
    brand_id: "",
  });

  const submit = (e) => {
    e.preventDefault();
    post(route("products.store"), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Product created successfully");
        reset();
      },
    });
  };

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setData("image", file);
      const reader = new FileReader();
      reader.onloadend = () => {
        setImagePreview(reader.result);
      };
      reader.readAsDataURL(file);
    }
  };

  const removeImage = () => {
    setData("image", null);
    setImagePreview(null);
    // Clear the file input value
    const fileInput = document.getElementById('image');
    if (fileInput) {
      fileInput.value = '';
    }
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Products" subpage="Add New" url="products.index" />

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
                  onChange={handleImageChange}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.image} className="text-sm" />
                
                {imagePreview && (
                  <div className="relative mt-2 md:w-1/2 w-full">
                    <img 
                      src={imagePreview} 
                      alt="Preview" 
                      className="w-full h-48 object-contain rounded-lg border"
                    />
                    <button
                      type="button"
                      onClick={removeImage}
                      className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                    >
                      <X className="h-4 w-4" />
                    </button>
                  </div>
                )}
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
              <Label htmlFor="sub_category_id" className="md:col-span-2 col-span-12">
                Category
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={categories.map(category => ({
                      id: category.id,
                      name: category.name
                    }))}
                    value={data.sub_category_id}
                    onChange={(value) => setData("sub_category_id", value)}
                    placeholder="Select category"
                  />
                </div>
                <InputError message={errors.sub_category_id} className="text-sm" />
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
                <DateTimePicker
                  value={data.expiry_date}
                  onChange={(date) => setData("axpiray_date", date)}
                  className="md:w-1/2 w-full"
                  required
                />
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
                  checked={data.allow_for_selling == 1}
                  onCheckedChange={(checked) => setData("allow_for_selling", checked ? 1 : 0)}
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
                        options={accounts}
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
                  checked={data.allow_for_purchasing == 1}
                  onCheckedChange={(checked) => setData("allow_for_purchasing", checked ? 1 : 0)}
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
                        options={accounts}
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
                      { id: "1", name: "Active" },
                      { id: "0", name: "Disabled" }
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
                  checked={data.stock_management == 1}
                  onCheckedChange={(checked) => setData("stock_management", checked ? 1 : 0)}
                />
                <InputError message={errors.stock_management} className="text-sm" />
              </div>
            </div>

            <Button type="submit" disabled={processing} className="mt-4">
              {processing ? "Creating..." : "Create Product"}
            </Button>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
