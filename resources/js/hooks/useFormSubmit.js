import { router } from '@inertiajs/react';
import { useState } from 'react';

/**
 * Custom hook for handling form submissions with Inertia
 * @param {Object} initialData - Initial form data
 * @param {string|null} defaultRoute - Default route to submit the form to (optional)
 * @param {Object} options - Additional options for form submission
 * @returns {Object} Form utilities and state
 */
export function useFormSubmit(initialData, defaultRoute = null, options = {}) {
    const [processing, setProcessing] = useState(false);

    const submit = (method, url, opts = {}) => {
        setProcessing(true);

        const { data = {}, ...otherOptions } = opts;

        const submitFn = {
            post: router.post,
            put: router.put,
            delete: router.delete,
        }[method];

        if (!submitFn) {
            console.error(`Invalid form submission method: ${method}`);
            return;
        }

        submitFn(url, data, {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
            ...otherOptions
        });
    };

    const handleSubmit = (e, method = "post", route = defaultRoute) => {
        e.preventDefault();

        if (!route) {
            console.error("No route provided for form submission");
            return;
        }

        submit(method, route, options);
    };

    return {
        processing,
        handleSubmit,
    };
}
