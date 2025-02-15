import { Label } from "./ui/label";

export default function InputLabel({
    value,
    className = '',
    children,
    ...props
}) {
    return (
        <Label
            {...props}
            className={className}
        >
            {value ? value : children}
        </Label>
    );
}
