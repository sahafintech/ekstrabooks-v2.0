import React, { cloneElement, isValidElement } from "react";
import { Button } from "@/Components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuGroup,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { MoreVertical } from "lucide-react";
import { Link } from "@inertiajs/react";

export default function TableActions({ actions = [] }) {
    // This function safely renders the icon - if it's a valid React element
    const renderIcon = (icon) => {
        if (isValidElement(icon)) {
            return cloneElement(icon, { className: icon.props.className || "h-4 w-4" });
        }
        return null;
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">Open menu</span>
                    <MoreVertical className="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuGroup>
                    {actions.map((action, index) => (
                        action.href ? (
                            <DropdownMenuItem key={index} asChild>
                                <Link href={action.href} className={action.className}>
                                    {action.icon && (
                                        <span className="mr-2">{renderIcon(action.icon)}</span>
                                    )}
                                    {action.label}
                                </Link>
                            </DropdownMenuItem>
                        ) : (
                            <DropdownMenuItem
                                key={index}
                                onClick={action.onClick}
                                className={`${action.destructive ? 'text-destructive' : ''} ${action.className || ''}`}
                            >
                                {action.icon && (
                                    <span className="mr-2">{renderIcon(action.icon)}</span>
                                )}
                                {action.label}
                            </DropdownMenuItem>
                        )
                    ))}
                </DropdownMenuGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
