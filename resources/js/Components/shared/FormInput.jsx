import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import InputError from "@/Components/InputError";
import { cn } from "@/lib/utils";
import PropTypes from "prop-types";

/**
 * A reusable form input component with label and error handling
 * @param {Object} props - Component props
 */
const FormInput = ({
    label,
    id,
    type = "text",
    name,
    value,
    onChange,
    error,
    autoComplete,
    placeholder,
    isFocused = false,
    className = "",
    required = false,
    disabled = false,
    description,
    leftIcon,
    rightIcon,
}) => {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id} className={cn(required && "after:content-['*'] after:ml-0.5 after:text-red-500")}>
                {label}
            </Label>
            <div className="relative">
                {leftIcon && (
                    <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-muted-foreground">
                        {leftIcon}
                    </div>
                )}
                <Input
                    id={id}
                    type={type}
                    name={name}
                    value={value}
                    autoComplete={autoComplete}
                    placeholder={placeholder}
                    autoFocus={isFocused}
                    onChange={onChange}
                    disabled={disabled}
                    required={required}
                    className={cn(
                        leftIcon && "pl-10",
                        rightIcon && "pr-10",
                        error && "border-destructive focus-visible:ring-destructive",
                        className
                    )}
                    aria-describedby={description ? `${id}-description` : undefined}
                    aria-invalid={error ? "true" : undefined}
                />
                {rightIcon && (
                    <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-muted-foreground">
                        {rightIcon}
                    </div>
                )}
            </div>
            {description && !error && (
                <p id={`${id}-description`} className="text-sm text-muted-foreground">
                    {description}
                </p>
            )}
            <InputError message={error} className="text-sm" />
        </div>
    );
};

FormInput.propTypes = {
    label: PropTypes.string.isRequired,
    id: PropTypes.string.isRequired,
    type: PropTypes.oneOf([
        "text",
        "email",
        "password",
        "number",
        "tel",
        "url",
        "search",
        "date",
        "time",
        "datetime-local"
    ]),
    name: PropTypes.string.isRequired,
    value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    onChange: PropTypes.func.isRequired,
    error: PropTypes.string,
    autoComplete: PropTypes.string,
    placeholder: PropTypes.string,
    isFocused: PropTypes.bool,
    className: PropTypes.string,
    required: PropTypes.bool,
    disabled: PropTypes.bool,
    description: PropTypes.string,
    leftIcon: PropTypes.node,
    rightIcon: PropTypes.node,
};

export default FormInput;
