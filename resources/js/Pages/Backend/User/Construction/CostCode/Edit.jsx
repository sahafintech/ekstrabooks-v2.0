import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { Textarea } from "@/Components/ui/textarea";
export default function Edit({ cost_code }) {
  const { data, setData, post, processing, errors, reset } = useForm({
    code: cost_code.code,
    description: cost_code.description,
    _method: "PUT",
  });

  const submit = (e) => {
    e.preventDefault();
    post(route("cost_codes.update", cost_code.id), {
      preserveScroll: true,
      onSuccess: () => {
        reset();
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Cost Codes" subpage="Edit" url="cost_codes.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <form onSubmit={submit}>
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="code" className="md:col-span-2 col-span-12">
                Code *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="code"
                  type="text"
                  value={data.code}
                  onChange={(e) => setData("code", e.target.value)}
                  className="md:w-1/2 w-full"
                  required
                />
                <InputError message={errors.code} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="description" className="md:col-span-2 col-span-12">
                Description
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Textarea
                  id="description"
                  value={data.description}
                  onChange={(e) => setData("description", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.description} className="text-sm" />
              </div>
            </div>

            <Button type="submit" disabled={processing} className="mt-4">
              {processing ? "Creating..." : "Create Cost Code"}
            </Button>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
