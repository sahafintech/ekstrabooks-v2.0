import { Button } from "@/Components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuGroup,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { MoreVertical } from "lucide-react";

export default function TableActions({ actions = [] }) {
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
                        <DropdownMenuItem
                            key={index}
                            onClick={action.onClick}
                            className={action.className}
                        >
                            {action.icon && (
                                <span className="mr-2">{action.icon}</span>
                            )}
                            {action.label}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
