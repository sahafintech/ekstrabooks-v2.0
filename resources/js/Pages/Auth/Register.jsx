import InputError from "@/Components/InputError";
import AuthLayout from "@/Layouts/AuthLayout";
import { Link, useForm } from "@inertiajs/react";
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
import FormInput from "@/Components/shared/FormInput";

const countryCodes = [
    { value: "+62", label: "Indonesia (+62)" },
    { value: "+1", label: "United States (+1)" },
    { value: "+81", label: "Japan (+81)" },
    { value: "+44", label: "United Kingdom (+44)" },
    { value: "+86", label: "China (+86)" },
];

const RegisterForm = ({ onSubmit, data, setData, errors, processing }) => (
    <form onSubmit={onSubmit} className="p-6 md:p-8">
        <div className="flex flex-col gap-6">
            <div className="flex flex-col items-center text-center">
                <h1 className="text-2xl font-bold">Welcome</h1>
                <p className="text-balance text-muted-foreground">
                    Register a new account
                </p>
            </div>
            <FormInput
                label="Name"
                id="name"
                name="name"
                value={data.name}
                autoComplete="name"
                isFocused={true}
                placeholder="John Doe"
                onChange={(e) => setData("name", e.target.value)}
                error={errors.name}
            />
            <FormInput
                label="Email"
                id="email"
                type="email"
                name="email"
                value={data.email}
                autoComplete="username"
                placeholder="m@example.com"
                onChange={(e) => setData("email", e.target.value)}
                error={errors.email}
            />
            <div className="grid gap-2">
                <Label htmlFor="phone">Phone</Label>
                <div className="flex gap-2">
                    <Select
                        value={data.country_code}
                        onValueChange={(value) =>
                            setData("country_code", value)
                        }
                    >
                        <SelectTrigger className="w-[140px]">
                            <SelectValue placeholder="Select" />
                        </SelectTrigger>
                        <SelectContent>
                            {countryCodes.map((code) => (
                                <SelectItem key={code.value} value={code.value}>
                                    {code.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Input
                        id="phone"
                        name="phone"
                        value={data.phone}
                        autoComplete="tel"
                        placeholder="81234567890"
                        onChange={(e) => setData("phone", e.target.value)}
                        className="flex-1"
                    />
                </div>
                <InputError message={errors.phone} className="text-sm" />
            </div>
            <FormInput
                label="Password"
                id="password"
                type="password"
                name="password"
                value={data.password}
                autoComplete="new-password"
                onChange={(e) => setData("password", e.target.value)}
                error={errors.password}
            />
            <FormInput
                label="Confirm Password"
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                value={data.password_confirmation}
                autoComplete="new-password"
                onChange={(e) =>
                    setData("password_confirmation", e.target.value)
                }
                error={errors.password_confirmation}
            />
            <Button type="submit" className="w-full" disabled={processing}>
                Register
            </Button>

            <div className="text-center text-sm">
                Already have an account?{" "}
                <Link
                    href={route("login")}
                    className="underline underline-offset-4"
                >
                    Login
                </Link>
            </div>
        </div>
    </form>
);

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        email: "",
        country_code: "",
        phone: "",
        password: "",
        password_confirmation: "",
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("register"), {
            onFinish: () => reset("password", "password_confirmation"),
        });
    };

    return (
        <AuthLayout>
            <div className="flex flex-col gap-6">
                <div className="overflow-hidden bg-white">
                    <div className="grid p-0 md:grid-cols-2">
                        <RegisterForm
                            onSubmit={handleSubmit}
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                        />
                        <div className="relative hidden bg-muted md:block">
                            <img
                                src="/img/login-cover.png"
                                alt="Registration cover"
                                className="absolute inset-0 h-full w-full object-cover dark:brightness-[0.2] dark:grayscale"
                            />
                        </div>
                    </div>
                </div>
                <div className="text-balance text-center text-xs text-muted-foreground [&_a]:underline [&_a]:underline-offset-4 hover:[&_a]:text-primary">
                    By clicking continue, you agree to our{" "}
                    <Link href={route("terms-condition")}>Terms of Service</Link> and{" "}
                    <Link href={route("privacy-policy")}>Privacy Policy</Link>.
                </div>
            </div>
        </AuthLayout>
    );
}
