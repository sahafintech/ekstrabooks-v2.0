import { useForm } from "@inertiajs/react";
import { useEffect, useRef } from "react";

/**
 * Custom hook for handling form submissions with Inertia
 * @param {Object} initialData - Initial form data
 * @param {string|null} defaultRoute - Default route to submit the form to (optional)
 * @param {Object} options - Additional options for form submission
 * @returns {Object} Form utilities and state
 */
export function useFormSubmit(initialData, defaultRoute = null, options = {}) {
    const form = useForm(initialData);
    
    // Keep track of whether this is the first render
    const isFirstRender = useRef(true);
    // Store previous initial data for comparison
    const prevInitialData = useRef(initialData);

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        // Check if initialData has actually changed
        const hasChanged = Object.entries(initialData).some(
            ([key, value]) => prevInitialData.current[key] !== value
        );

        if (hasChanged) {
            prevInitialData.current = initialData;
            form.setData(initialData);
        }
    }, [initialData]);

    const handleSubmit = (e, method = "post", route = defaultRoute) => {
        e.preventDefault();

        if (!route) {
            console.error("No route provided for form submission");
            return;
        }

        const submitFn = {
            post: form.post,
            put: form.put,
            delete: form.delete,
        }[method];

        if (!submitFn) {
            console.error(`Invalid form submission method: ${method}`);
            return;
        }

        submitFn(route, {
            preserveScroll: true,
            onSuccess: () => {
                if (options.onSuccess) options.onSuccess();
                if (options.resetOnSuccess) {
                    form.reset();
                    prevInitialData.current = initialData;
                }
            },
            onError: options.onError,
            onFinish: () => {
                if (options.onFinish) options.onFinish();
            },
        });
    };

    return {
        data: form.data,
        setData: form.setData,
        processing: form.processing,
        errors: form.errors,
        handleSubmit,
        reset: () => {
            form.reset();
            prevInitialData.current = initialData;
        },
        clearErrors: form.clearErrors,
    };
}
