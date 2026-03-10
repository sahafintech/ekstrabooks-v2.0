import { useEffect, useMemo, useState } from "react";
import axios from "axios";
import { Head, useForm, usePage } from "@inertiajs/react";
import {
    CheckCircle2,
    CircleAlert,
    HardDrive,
    ShieldCheck,
    TestTube2,
} from "lucide-react";
import { toast } from "sonner";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PageHeader from "@/Components/PageHeader";
import InputError from "@/Components/InputError";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";

export default function Storage({
    storageSetting,
    scope,
    diskOptions = [],
    visibilityOptions = [],
}) {
    const { flash = {} } = usePage().props;
    const [testing, setTesting] = useState(false);
    const [connectionResult, setConnectionResult] = useState(null);

    const { data, setData, post, processing, errors } = useForm({
        disk: storageSetting?.disk || "public",
        visibility: storageSetting?.visibility || "public",
        directory: storageSetting?.directory || "attachments",
        signed_url_ttl: String(storageSetting?.signed_url_ttl || 10),
    });

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }

        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    useEffect(() => {
        if (data.disk === "public" && data.visibility !== "public") {
            setData("visibility", "public");
        }
    }, [data.disk, data.visibility, setData]);

    const selectedDisk = useMemo(
        () => diskOptions.find((option) => option.value === data.disk),
        [data.disk, diskOptions]
    );

    const scopeBadgeLabel =
        scope?.type === "business"
            ? "Business Scope"
            : scope?.type === "global"
              ? "Global Scope"
              : "User Scope";

    const submit = (e) => {
        e.preventDefault();

        post(route("storage.store"), {
            preserveScroll: true,
            onSuccess: () => toast.success("Storage settings saved successfully"),
        });
    };

    const testConnection = async () => {
        setTesting(true);
        setConnectionResult(null);

        try {
            const response = await axios.post(route("storage.test_connection"), {
                disk: data.disk,
            });

            const message = normalizeConnectionMessage(
                response.data?.message || "Storage connection test completed"
            );

            setConnectionResult({
                ok: Boolean(response.data?.ok),
                message,
            });

            if (response.data?.ok) {
                toast.success(message);
            } else {
                toast.error(message);
            }
        } catch (error) {
            const message = normalizeConnectionMessage(
                error?.response?.data?.message || "Storage connection failed"
            );

            setConnectionResult({
                ok: false,
                message,
            });

            toast.error(
                message
            );
        } finally {
            setTesting(false);
        }
    };

    const normalizeConnectionMessage = (message) => {
        if (message.includes("Driver [gcs] is not supported.")) {
            return `${message} Install the Google Cloud Storage Flysystem adapter before using the gcs disk.`;
        }

        return message;
    };

    return (
        <AuthenticatedLayout>
            <Head title="Storage Settings" />

            <SidebarInset>
                <PageHeader page="Dashboard" subpage="Storage" url="dashboard.index" />

                <div className="p-4 space-y-6">
                    <div className="grid gap-6 lg:grid-cols-[1.4fr_0.9fr]">
                        <div className="p-4 border rounded-lg">
                            <div className="flex items-center justify-between gap-3">
                                <div className="space-y-1">
                                    <h2>File Storage</h2>
                                    <p className="text-sm text-muted-foreground">
                                        Choose the Laravel filesystem disk used for new uploads.
                                    </p>
                                </div>
                                <Badge variant="outline">
                                    {scopeBadgeLabel}
                                </Badge>
                            </div>

                            <div className="space-y-6">
                                <Alert>
                                    <ShieldCheck className="h-4 w-4" />
                                    <AlertTitle>Current Scope</AlertTitle>
                                    <AlertDescription>
                                        This setting is being saved for{" "}
                                        <span className="font-medium">{scope?.label}</span>.
                                    </AlertDescription>
                                </Alert>

                                <div className="grid gap-4 md:grid-cols-3">
                                    {diskOptions.map((option) => {
                                        const active = data.disk === option.value;

                                        return (
                                            <button
                                                key={option.value}
                                                type="button"
                                                onClick={() => setData("disk", option.value)}
                                                className={`rounded-xl border p-4 text-left transition ${
                                                    active
                                                        ? "border-primary bg-primary/5 shadow-sm"
                                                        : "border-border hover:border-primary/40"
                                                }`}
                                            >
                                                <div className="flex items-start justify-between gap-3">
                                                    <div>
                                                        <div className="font-semibold">
                                                            {option.label}
                                                        </div>
                                                        <div className="mt-2 text-sm text-muted-foreground">
                                                            {option.description}
                                                        </div>
                                                    </div>
                                                    {active ? (
                                                        <CheckCircle2 className="h-5 w-5 text-primary" />
                                                    ) : (
                                                        <HardDrive className="h-5 w-5 text-muted-foreground" />
                                                    )}
                                                </div>

                                                <div className="mt-4">
                                                    <Badge
                                                        variant={option.configured ? "default" : "outline"}
                                                    >
                                                        {option.configured ? "Configured" : "Env Missing"}
                                                    </Badge>
                                                </div>
                                            </button>
                                        );
                                    })}
                                </div>

                                <form onSubmit={submit} className="space-y-5">
                                    {connectionResult && (
                                        <Alert
                                            variant={
                                                connectionResult.ok ? "default" : "destructive"
                                            }
                                        >
                                            <CircleAlert className="h-4 w-4" />
                                            <AlertTitle>
                                                {connectionResult.ok
                                                    ? "Connection succeeded"
                                                    : "Connection failed"}
                                            </AlertTitle>
                                            <AlertDescription>
                                                {connectionResult.message}
                                            </AlertDescription>
                                        </Alert>
                                    )}

                                    <div className="grid gap-5 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="visibility">File Visibility</Label>
                                            <Select
                                                value={data.visibility}
                                                onValueChange={(value) => setData("visibility", value)}
                                                disabled={data.disk === "public"}
                                            >
                                                <SelectTrigger id="visibility">
                                                    <SelectValue placeholder="Select visibility" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {visibilityOptions.map((visibility) => (
                                                        <SelectItem
                                                            key={visibility}
                                                            value={visibility}
                                                        >
                                                            {visibility}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={errors.visibility} />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="directory">Base Directory</Label>
                                            <Input
                                                id="directory"
                                                value={data.directory}
                                                onChange={(e) => setData("directory", e.target.value)}
                                                placeholder="attachments"
                                            />
                                            <InputError message={errors.directory} />
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="signed_url_ttl">
                                            Signed URL TTL (minutes)
                                        </Label>
                                        <Input
                                            id="signed_url_ttl"
                                            type="number"
                                            min="1"
                                            max="1440"
                                            value={data.signed_url_ttl}
                                            onChange={(e) =>
                                                setData("signed_url_ttl", e.target.value)
                                            }
                                        />
                                        <InputError message={errors.signed_url_ttl} />
                                    </div>

                                    <div className="flex flex-wrap gap-3">
                                        <Button type="submit" disabled={processing}>
                                            {processing ? "Saving..." : "Save Storage Settings"}
                                        </Button>

                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={testConnection}
                                            disabled={testing}
                                        >
                                            <TestTube2 className="mr-2 h-4 w-4" />
                                            {testing ? "Testing..." : "Test Connection"}
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div className="space-y-6">
                            <div className="p-4 border rounded-lg">
                                <div className="mb-4">
                                    <h2 className="text-lg font-semibold">Provider Notes</h2>
                                    <p className="text-sm text-muted-foreground">
                                        Public files are directly addressable. Private cloud files should be served with temporary signed URLs.
                                    </p>
                                </div>
                                <div className="space-y-4 text-sm text-muted-foreground">
                                    <div>
                                        <div className="font-medium text-foreground">Local Public</div>
                                        <p>
                                            Uses Laravel&apos;s `public` disk. Run `php artisan storage:link`
                                            so generated URLs resolve under `/storage`.
                                        </p>
                                    </div>
                                    <div>
                                        <div className="font-medium text-foreground">S3 / GCS Public</div>
                                        <p>
                                            Suitable for public assets or documents that can be fetched with a direct object URL.
                                        </p>
                                    </div>
                                    <div>
                                        <div className="font-medium text-foreground">S3 / GCS Private</div>
                                        <p>
                                            Keep objects private and generate time-limited URLs when a user opens a file.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="p-4 border rounded-lg">
                                <div className="mb-4">
                                    <h2 className="text-lg font-semibold">Selected Disk</h2>
                                    <p className="text-sm text-muted-foreground">
                                        Runtime uploads use `Storage::disk(selected_disk)` from the saved database setting.
                                    </p>
                                </div>
                                <div className="space-y-3 text-sm">
                                    <div className="flex items-center justify-between">
                                        <span className="text-muted-foreground">Disk</span>
                                        <span className="font-medium">{selectedDisk?.label || data.disk}</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-muted-foreground">Visibility</span>
                                        <span className="font-medium">{data.visibility}</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-muted-foreground">Directory</span>
                                        <span className="font-medium">{data.directory || "attachments"}</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-muted-foreground">Signed URL TTL</span>
                                        <span className="font-medium">{data.signed_url_ttl} min</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
