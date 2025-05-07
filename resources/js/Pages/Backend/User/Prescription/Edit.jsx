import { useState, useEffect } from "react";
import { useForm, Link } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Checkbox } from "@/Components/ui/checkbox";
import { Textarea } from "@/Components/ui/textarea";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";

export default function Edit({ prescription, customers = [], products = [], prescriptionProducts = [] }) {
    // Prepare initial data from prescription
    const initialData = {
        customer_id: prescription.customer_id || "",
        date: prescription.date || new Date(),
        result_date: prescription.result_date || new Date(),
        dist_sph_re: prescription.dist_sph_re || "",
        dist_cyl_re: prescription.dist_cyl_re || "",
        dist_axis_re: prescription.dist_axis_re || "",
        dist_va_re: prescription.dist_va_re || "",
        dist_sph_le: prescription.dist_sph_le || "",
        dist_cyl_le: prescription.dist_cyl_le || "",
        dist_axis_le: prescription.dist_axis_le || "",
        dist_va_le: prescription.dist_va_le || "",
        near_sph_re: prescription.near_sph_re || "",
        near_cyl_re: prescription.near_cyl_re || "",
        near_axis_re: prescription.near_axis_re || "",
        near_va_re: prescription.near_va_re || "",
        near_sph_le: prescription.near_sph_le || "",
        near_cyl_le: prescription.near_cyl_le || "",
        near_axis_le: prescription.near_axis_le || "",
        near_va_le: prescription.near_va_le || "",
        ipd: prescription.ipd || "",
        glasses: prescription.glasses == 1,
        plastic: prescription.plastic == 1,
        polycarbonate: prescription.polycarbonate == 1,
        progressive_lenses: prescription.progressive_lenses == 1,
        photochromatic_lenses: prescription.photochromatic_lenses == 1,
        contact_lenses: prescription.contact_lenses == 1,
        anti_reflection_coating: prescription.anti_reflection_coating == 1,
        white_lenses: prescription.white_lenses == 1,
        high_index_lenses: prescription.high_index_lenses == 1,
        bi_focal_lenses: prescription.bi_focal_lenses == 1,
        single_vision: prescription.single_vision == 1,
        blue_cut: prescription.blue_cut == 1,
        prescription_description: prescription.description || "",
        // Product arrays (should be filled from prescription.products if available)
        product_id: prescriptionProducts.items ? prescriptionProducts.items.map(p => p.product_id) : [],
        product_name: prescriptionProducts.items ? prescriptionProducts.items.map(p => p.product_name) : [],
        description: prescriptionProducts.items ? prescriptionProducts.items.map(p => p.description) : [],
        quantity: prescriptionProducts.items ? prescriptionProducts.items.map(p => p.quantity) : [],
        unit_cost: prescriptionProducts.items ? prescriptionProducts.items.map(p => p.unit_cost) : [],
    };

    const { data, setData, put, processing, errors } = useForm(initialData);

    // For UI display of products
    const [selectedProducts, setSelectedProducts] = useState(
        prescriptionProducts.items
            ? prescriptionProducts.items.map((p, idx) => ({
                  id: idx,
                  product_id: p.product_id,
                  product_name: p.product_name,
                  description: p.description,
                  quantity: p.quantity,
                  unit_cost: p.unit_cost,
                  sub_total: parseFloat(p.quantity) * parseFloat(p.unit_cost),
              }))
            : []
    );

    // For adding/editing a product
    const [productForm, setProductForm] = useState({
        product_id: "",
        product_name: "",
        description: "",
        quantity: 1,
        unit_cost: 0,
    });

    // Calculate totals
    const calculateTotals = () => {
        let subTotal = 0;
        selectedProducts.forEach((product) => {
            subTotal += parseFloat(product.sub_total || 0);
        });
        return {
            subTotal: subTotal.toFixed(2),
            grandTotal: subTotal.toFixed(2),
        };
    };

    // Handle product selection change
    const handleProductChange = (productId) => {
        const selectedProduct = products.find(
            (p) => p.id.toString() === productId
        );
        if (selectedProduct) {
            setProductForm({
                product_id: selectedProduct.id.toString(),
                product_name: selectedProduct.name,
                description: selectedProduct.description || "",
                quantity: 1,
                unit_cost: selectedProduct.selling_price || 0,
            });
        }
    };

    // Add a product to the list
    const addProduct = () => {
        if (productForm.product_id) {
            const sub_total =
                parseFloat(productForm.quantity) *
                parseFloat(productForm.unit_cost);
            setSelectedProducts([
                ...selectedProducts,
                {
                    id: Date.now(),
                    ...productForm,
                    sub_total,
                },
            ]);
            setData({
                ...data,
                product_id: [...data.product_id, productForm.product_id],
                product_name: [...data.product_name, productForm.product_name],
                description: [...data.description, productForm.description],
                quantity: [...data.quantity, productForm.quantity],
                unit_cost: [...data.unit_cost, productForm.unit_cost],
            });
            setProductForm({
                product_id: "",
                product_name: "",
                description: "",
                quantity: 1,
                unit_cost: 0,
            });
        }
    };

    // Remove a product from the list
    const removeProduct = (index) => {
        const newSelectedProducts = [...selectedProducts];
        newSelectedProducts.splice(index, 1);
        setSelectedProducts(newSelectedProducts);
        setData({
            ...data,
            product_id: data.product_id.filter((_, i) => i !== index),
            product_name: data.product_name.filter((_, i) => i !== index),
            description: data.description.filter((_, i) => i !== index),
            quantity: data.quantity.filter((_, i) => i !== index),
            unit_cost: data.unit_cost.filter((_, i) => i !== index),
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const dataToSend = {
            ...data,
            glasses: data.glasses ? 1 : 0,
            plastic: data.plastic ? 1 : 0,
            polycarbonate: data.polycarbonate ? 1 : 0,
            progressive_lenses: data.progressive_lenses ? 1 : 0,
            photochromatic_lenses: data.photochromatic_lenses ? 1 : 0,
            contact_lenses: data.contact_lenses ? 1 : 0,
            anti_reflection_coating: data.anti_reflection_coating ? 1 : 0,
            white_lenses: data.white_lenses ? 1 : 0,
            high_index_lenses: data.high_index_lenses ? 1 : 0,
            bi_focal_lenses: data.bi_focal_lenses ? 1 : 0,
            single_vision: data.single_vision ? 1 : 0,
            blue_cut: data.blue_cut ? 1 : 0,
        };
        put(route("prescriptions.update", prescription.id), dataToSend);
    };

    const totals = calculateTotals();

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Prescription"
                    subpage="Edit"
                    url="prescriptions.index"
                />
                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <h2 className="text-lg font-medium p-4">Edit Prescription</h2>
                    <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                        <form onSubmit={handleSubmit}>
                            {/* Customer */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="prescription_date"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Customer
                                </Label>
                                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                    <div className="md:w-1/2 w-full">
                                        <SearchableCombobox
                                            options={customers.map(
                                                (customer) => ({
                                                    id: customer.id.toString(),
                                                    name: customer.name,
                                                })
                                            )}
                                            value={data.customer_id}
                                            onChange={(value) =>
                                                setData("customer_id", value)
                                            }
                                            placeholder="Select a customer"
                                        />
                                        {errors.customer_id && (
                                            <p className="text-sm text-red-600 mt-1">
                                                {errors.customer_id}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>
                            {/* Prescription Date */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="prescription_date"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Prescription Date
                                </Label>
                                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                    <DateTimePicker
                                        value={data.date}
                                        onChange={(date) =>
                                            setData("date", date)
                                        }
                                        className="md:w-1/2 w-full"
                                        required
                                    />
                                    {errors.date && (
                                        <p className="text-sm text-red-600 mt-1">
                                            {errors.date}
                                        </p>
                                    )}
                                </div>
                            </div>
                            {/* Result Date */}
                            <div className="grid grid-cols-12 mt-2">
                                <Label
                                    htmlFor="result_date"
                                    className="md:col-span-2 col-span-12"
                                >
                                    Result Date
                                </Label>
                                <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                    <DateTimePicker
                                        value={data.result_date}
                                        onChange={(date) =>
                                            setData("result_date", date)
                                        }
                                        className="md:w-1/2 w-full"
                                        required
                                    />
                                    {errors.result_date && (
                                        <p className="text-sm text-red-600 mt-1">
                                            {errors.result_date}
                                        </p>
                                    )}
                                </div>
                            </div>
                            {/* Prescription Table */}
                            <div className="grid grid-cols-1 gap-2">
                                <div className="grid grid-cols-2 gap-2 mt-4">
                                    <div className="flex items-center justify-center font-medium">
                                        <div className="text-sm mr-2">RX</div>
                                        <div className="text-sm">RIGHT EYE</div>
                                    </div>
                                    <div className="flex items-center justify-center font-medium">
                                        <div className="text-sm">LEFT EYE</div>
                                    </div>
                                </div>
                                {/* SPH CYL AXIS VA Headers */}
                                <div className="grid grid-cols-[70px_repeat(8,1fr)] gap-2">
                                    <div></div>
                                    <div className="text-center text-xs uppercase">
                                        SPH
                                    </div>
                                    <div className="text-center text-xs uppercase">
                                        CYL
                                    </div>
                                    <div className="text-center text-xs uppercase">
                                        AXIS
                                    </div>
                                    <div className="text-center text-xs uppercase">
                                        VA
                                    </div>
                                    <div className="text-center text-xs uppercase">
                                        SPH
                                    </div>
                                    <div className="text-center text-xs uppercase">
                                        CYL
                                    </div>
                                    <div className="text-center text-xs uppercase">
                                        AXIS
                                    </div>
                                    <div className="text-center text-xs uppercase">
                                        VA
                                    </div>
                                </div>
                                {/* Distance */}
                                <div className="grid grid-cols-[70px_repeat(8,1fr)] gap-2 items-center">
                                    <div className="text-xs">Dist</div>
                                    <Input
                                        value={data.dist_sph_re}
                                        onChange={(e) =>
                                            setData(
                                                "dist_sph_re",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                    <Input
                                        value={data.dist_cyl_re}
                                        onChange={(e) =>
                                            setData(
                                                "dist_cyl_re",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                    <Input
                                        value={data.dist_axis_re}
                                        onChange={(e) =>
                                            setData(
                                                "dist_axis_re",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                    <Input
                                        value={data.dist_va_re}
                                        onChange={(e) =>
                                            setData(
                                                "dist_va_re",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                    <Input
                                        value={data.dist_sph_le}
                                        onChange={(e) =>
                                            setData(
                                                "dist_sph_le",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                    <Input
                                        value={data.dist_cyl_le}
                                        onChange={(e) =>
                                            setData(
                                                "dist_cyl_le",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                    <Input
                                        value={data.dist_axis_le}
                                        onChange={(e) =>
                                            setData(
                                                "dist_axis_le",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                    <Input
                                        value={data.dist_va_le}
                                        onChange={(e) =>
                                            setData(
                                                "dist_va_le",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                </div>
                                {/* Near */}
                                <div className="grid grid-cols-[70px_repeat(8,1fr)] gap-2 items-center">
                                    <div className="text-xs">Near</div>
                                    <Input
                                        value={data.near_sph_re}
                                        onChange={(e) =>
                                            setData(
                                                "near_sph_re",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                    <Input
                                        value={data.near_cyl_re}
                                        onChange={(e) =>
                                            setData(
                                                "near_cyl_re",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                    <Input
                                        value={data.near_axis_re}
                                        onChange={(e) =>
                                            setData(
                                                "near_axis_re",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                    <Input
                                        value={data.near_va_re}
                                        onChange={(e) =>
                                            setData(
                                                "near_va_re",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                    <Input
                                        value={data.near_sph_le}
                                        onChange={(e) =>
                                            setData(
                                                "near_sph_le",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                    <Input
                                        value={data.near_cyl_le}
                                        onChange={(e) =>
                                            setData(
                                                "near_cyl_le",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                    <Input
                                        value={data.near_axis_le}
                                        onChange={(e) =>
                                            setData(
                                                "near_axis_le",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                    <Input
                                        value={data.near_va_le}
                                        onChange={(e) =>
                                            setData(
                                                "near_va_le",
                                                e.target.value
                                            )
                                        }
                                        className="h-9 text-sm"
                                    />
                                </div>
                                {/* IPD */}
                                <div className="my-4">
                                    <div className="grid grid-cols-[70px_1fr] gap-2 items-center">
                                        <div className="text-xs uppercase">
                                            IPD
                                        </div>
                                        <div className="md:w-1/2 w-full">
                                            <Input
                                                value={data.ipd}
                                                onChange={(e) =>
                                                    setData(
                                                        "ipd",
                                                        e.target.value
                                                    )
                                                }
                                                className="h-9 text-sm"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {/* Lens Options Checkboxes */}
                            <div className="grid grid-cols-3 gap-4 mt-2">
                                {/* Repeat for all lens options, same as in Create.jsx */}
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="glasses"
                                        checked={data.glasses}
                                        onCheckedChange={(checked) =>
                                            setData("glasses", checked)
                                        }
                                    />
                                    <Label
                                        htmlFor="glasses"
                                        className="text-xs"
                                    >
                                        Glasses
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="photochromatic_lenses"
                                        checked={data.photochromatic_lenses}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                "photochromatic_lenses",
                                                checked
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor="photochromatic_lenses"
                                        className="text-xs"
                                    >
                                        Photochromatic Lenses
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="high_index_lenses"
                                        checked={data.high_index_lenses}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                "high_index_lenses",
                                                checked
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor="high_index_lenses"
                                        className="text-xs"
                                    >
                                        High Index Lenses
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="polycarbonate"
                                        checked={data.polycarbonate}
                                        onCheckedChange={(checked) =>
                                            setData("polycarbonate", checked)
                                        }
                                    />
                                    <Label
                                        htmlFor="polycarbonate"
                                        className="text-xs"
                                    >
                                        Polycarbonate
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="contact_lenses"
                                        checked={data.contact_lenses}
                                        onCheckedChange={(checked) =>
                                            setData("contact_lenses", checked)
                                        }
                                    />
                                    <Label
                                        htmlFor="contact_lenses"
                                        className="text-xs"
                                    >
                                        Contact Lenses
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="bi_focal_lenses"
                                        checked={data.bi_focal_lenses}
                                        onCheckedChange={(checked) =>
                                            setData("bi_focal_lenses", checked)
                                        }
                                    />
                                    <Label
                                        htmlFor="bi_focal_lenses"
                                        className="text-xs"
                                    >
                                        Bi-Focal Lenses
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="progressive_lenses"
                                        checked={data.progressive_lenses}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                "progressive_lenses",
                                                checked
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor="progressive_lenses"
                                        className="text-xs"
                                    >
                                        Progressive Lenses
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="anti_reflection_coating"
                                        checked={data.anti_reflection_coating}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                "anti_reflection_coating",
                                                checked
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor="anti_reflection_coating"
                                        className="text-xs"
                                    >
                                        Anti-Reflection Coating
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="single_vision"
                                        checked={data.single_vision}
                                        onCheckedChange={(checked) =>
                                            setData("single_vision", checked)
                                        }
                                    />
                                    <Label
                                        htmlFor="single_vision"
                                        className="text-xs"
                                    >
                                        Single Vision
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="plastic"
                                        checked={data.plastic}
                                        onCheckedChange={(checked) =>
                                            setData("plastic", checked)
                                        }
                                    />
                                    <Label
                                        htmlFor="plastic"
                                        className="text-xs"
                                    >
                                        Plastic
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="white_lenses"
                                        checked={data.white_lenses}
                                        onCheckedChange={(checked) =>
                                            setData("white_lenses", checked)
                                        }
                                    />
                                    <Label
                                        htmlFor="white_lenses"
                                        className="text-xs"
                                    >
                                        White Lenses
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="blue_cut"
                                        checked={data.blue_cut}
                                        onCheckedChange={(checked) =>
                                            setData("blue_cut", checked)
                                        }
                                    />
                                    <Label
                                        htmlFor="blue_cut"
                                        className="text-xs"
                                    >
                                        Blue Cut
                                    </Label>
                                </div>
                            </div>
                            {/* Description */}
                            <div className="mt-4">
                                <Label className="text-xs mb-1 block">
                                    Description
                                </Label>
                                <Textarea
                                    value={data.prescription_description}
                                    onChange={(e) =>
                                        setData(
                                            "prescription_description",
                                            e.target.value
                                        )
                                    }
                                    rows={4}
                                />
                            </div>
                            {/* Product Selection */}
                            <div className="mt-4">
                                <Label className="text-xs mb-1 block">
                                    Select Product
                                </Label>
                                <div className="md:w-1/2 w-full">
                                    <div className="grid grid-cols-4 gap-4">
                                        <div className="col-span-2">
                                            <SearchableCombobox
                                                options={products.map(
                                                    (product) => ({
                                                        id: product.id.toString(),
                                                        name: product.name,
                                                    })
                                                )}
                                                value={productForm.product_id}
                                                onChange={(value) =>
                                                    handleProductChange(value)
                                                }
                                                placeholder="Select a product"
                                            />
                                        </div>
                                        <div>
                                            <Input
                                                type="number"
                                                placeholder="Quantity"
                                                value={productForm.quantity}
                                                onChange={(e) =>
                                                    setProductForm({
                                                        ...productForm,
                                                        quantity:
                                                            e.target.value,
                                                    })
                                                }
                                                min="1"
                                                className="h-9"
                                            />
                                        </div>
                                        <div>
                                            <Input
                                                type="number"
                                                placeholder="Price"
                                                value={productForm.unit_cost}
                                                onChange={(e) =>
                                                    setProductForm({
                                                        ...productForm,
                                                        unit_cost:
                                                            e.target.value,
                                                    })
                                                }
                                                min="0"
                                                step="0.01"
                                                className="h-9"
                                            />
                                        </div>
                                    </div>
                                    <div className="mt-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={addProduct}
                                            disabled={!productForm.product_id}
                                            className="text-xs"
                                        >
                                            Add Product
                                        </Button>
                                    </div>
                                </div>
                            </div>
                            {/* Product Table */}
                            <div className="mt-4">
                                <div className="md:w-1/2 w-full">
                                    <div className="grid grid-cols-5 gap-2 border-b pb-2">
                                        <div className="text-xs uppercase font-medium">
                                            Name
                                        </div>
                                        <div className="text-xs uppercase font-medium">
                                            Description
                                        </div>
                                        <div className="text-xs uppercase font-medium">
                                            Quantity
                                        </div>
                                        <div className="text-xs uppercase font-medium">
                                            Price
                                        </div>
                                        <div className="text-xs uppercase font-medium">
                                            Amount
                                        </div>
                                    </div>
                                    {selectedProducts.length > 0 ? (
                                        <>
                                            {selectedProducts.map(
                                                (product, index) => (
                                                    <div
                                                        key={product.id}
                                                        className="grid grid-cols-5 gap-2 py-2 border-b items-center"
                                                    >
                                                        <div className="text-sm">
                                                            {
                                                                product.product_name
                                                            }
                                                        </div>
                                                        <div className="text-sm">
                                                            {
                                                                product.description
                                                            }
                                                        </div>
                                                        <div className="text-sm">
                                                            {product.quantity}
                                                        </div>
                                                        <div className="text-sm">
                                                            {parseFloat(
                                                                product.unit_cost
                                                            ).toFixed(2)}
                                                        </div>
                                                        <div className="flex justify-between">
                                                            <span className="text-sm">
                                                                {parseFloat(
                                                                    product.sub_total
                                                                ).toFixed(2)}
                                                            </span>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                className="h-6 w-6 p-0 text-red-500"
                                                                onClick={() =>
                                                                    removeProduct(
                                                                        index
                                                                    )
                                                                }
                                                            >
                                                                &times;
                                                            </Button>
                                                        </div>
                                                    </div>
                                                )
                                            )}
                                            {/* Totals */}
                                            <div className="mt-4 flex justify-end">
                                                <div className="w-1/2">
                                                    <div className="flex justify-between py-1">
                                                        <span className="text-sm font-medium">
                                                            Subtotal:
                                                        </span>
                                                        <span className="text-sm">
                                                            {totals.subTotal}
                                                        </span>
                                                    </div>
                                                    <div className="flex justify-between py-1 border-t border-t-gray-200 font-semibold">
                                                        <span>Total:</span>
                                                        <span>
                                                            {totals.grandTotal}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </>
                                    ) : (
                                        <div className="py-4 text-center text-gray-500 text-sm">
                                            No products added
                                        </div>
                                    )}
                                </div>
                            </div>
                            {/* Submit Button */}
                            <div className="mt-6">
                                <Button type="submit" disabled={processing}>
                                    Save Changes
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
