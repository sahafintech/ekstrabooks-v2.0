import { Head, Link, useForm } from "@inertiajs/react";
import { Button } from "@/Components/ui/button";
import FormInput from "@/Components/shared/FormInput";
import {
    SocialLoginButton,
    SocialLoginIcons,
} from "@/Components/auth/SocialLogin";
import AuthLayout from "@/Layouts/AuthLayout";

const LoginForm = ({
    onSubmit,
    data,
    setData,
    errors,
    processing,
    canResetPassword,
}) => (
    <form onSubmit={onSubmit} className="p-6 md:p-8">
        <div className="flex flex-col gap-6">
            <div className="flex flex-col items-center text-center">
                <h1 className="text-2xl font-bold">Welcome back</h1>
                <p className="text-balance text-muted-foreground">
                    Login to your Ekstrabooks account
                </p>
            </div>
            <FormInput
                label="Email"
                id="email"
                type="email"
                name="email"
                value={data.email}
                autoComplete="username"
                placeholder="m@example.com"
                isFocused={true}
                onChange={(e) => setData("email", e.target.value)}
                error={errors.email}
            />
            <FormInput
                label="Password"
                id="password"
                type="password"
                name="password"
                value={data.password}
                autoComplete="current-password"
                onChange={(e) => setData("password", e.target.value)}
                error={errors.password}
            />
            <div className="flex items-center">
                {canResetPassword && (
                    <Link
                        href={route("password.request")}
                        className="ml-auto text-sm underline-offset-2 hover:underline"
                    >
                        Forgot your password?
                    </Link>
                )}
            </div>
            <Button type="submit" className="w-full" disabled={processing}>
                Login
            </Button>
            <div className="relative text-center text-sm after:absolute after:inset-0 after:top-1/2 after:z-0 after:flex after:items-center after:border-t after:border-border">
                <span className="relative z-10 bg-background px-2 text-muted-foreground">
                    Or continue with
                </span>
            </div>
            <div className="grid grid-cols-3 gap-4">
                {Object.entries(SocialLoginIcons).map(([provider, icon]) => (
                    <SocialLoginButton
                        key={provider}
                        provider={provider}
                        icon={icon}
                    />
                ))}
            </div>
            <div className="text-center text-sm">
                Don&apos;t have an account?{" "}
                <Link
                    href={route("register")}
                    className="underline underline-offset-4"
                >
                    Sign up
                </Link>
            </div>
        </div>
    </form>
);

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("login"), {
            onFinish: () => reset("password"),
        });
    };

    return (
        <AuthLayout>
            <Head title="Log in" />
            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
            <div className="flex flex-col gap-6">
                <div className="overflow-hidden bg-white">
                    <div className="grid p-0 md:grid-cols-2">
                        <LoginForm
                            onSubmit={handleSubmit}
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            canResetPassword={canResetPassword}
                        />
                        <div className="relative hidden bg-muted md:block">
                            <img
                                src="/img/login-cover.png"
                                className="absolute inset-0 h-full w-full object-cover dark:brightness-[0.2] dark:grayscale"
                                alt=""
                            />
                        </div>
                    </div>
                </div>
                <div className="text-balance text-center text-xs text-muted-foreground [&_a]:underline [&_a]:underline-offset-4 hover:[&_a]:text-primary">
                    By clicking continue, you agree to our{" "}
                    <a href={route("terms-condition")}>Terms of Service</a> and{" "}
                    <a href={route("privacy-policy")}>Privacy Policy</a>.
                </div>
            </div>
        </AuthLayout>
    );
}
