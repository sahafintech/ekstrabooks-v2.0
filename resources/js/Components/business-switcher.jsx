"use client"

import * as React from "react"
import { ChevronsUpDown } from "lucide-react"
import { Link } from "@inertiajs/react"

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from "@/components/ui/sidebar"

import PropTypes from 'prop-types';

export function BusinessSwitcher({ businesses, active_business }) {
  const { isMobile } = useSidebar()
  const [activeBusiness, setActiveBusiness] = React.useState(() => {
    const index = businesses.findIndex(b => b.id === active_business.id);
    return businesses[index];
  });

  return (
    <SidebarMenu>
      <SidebarMenuItem>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <SidebarMenuButton
              size="lg"
              className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
            >
              <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                <activeBusiness.logo className="size-4" />
              </div>
              <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-semibold">
                  {activeBusiness.name}
                </span>
                <span className="truncate text-xs">{activeBusiness.plan}</span>
              </div>
              <ChevronsUpDown className="ml-auto" />
            </SidebarMenuButton>
          </DropdownMenuTrigger>
          <DropdownMenuContent
            className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
            align="start"
            side={isMobile ? "bottom" : "right"}
            sideOffset={4}
          >
            <DropdownMenuLabel className="text-xs text-muted-foreground">
              Businesses
            </DropdownMenuLabel>
            {businesses.map((business, index) => (
              <Link key={business.name} href={route('business.switch_business', business.id)}>
                <DropdownMenuItem
                  onClick={() => setActiveBusiness(business)}
                  className="gap-2 p-2"
                >
                  <div className="flex size-6 items-center justify-center rounded-sm border">
                    <business.logo className="size-4 shrink-0" />
                  </div>
                  {business.name}
                  <DropdownMenuShortcut>⌘{index + 1}</DropdownMenuShortcut>
                </DropdownMenuItem>
              </Link>
            ))}
          </DropdownMenuContent>
        </DropdownMenu>
      </SidebarMenuItem>
    </SidebarMenu>
  )
}

BusinessSwitcher.propTypes = {
  businesses: PropTypes.arrayOf(
    PropTypes.shape({
      id: PropTypes.number.isRequired,
      name: PropTypes.string.isRequired,
      logo: PropTypes.elementType.isRequired,
      plan: PropTypes.string.isRequired,
    })
  ).isRequired,
};