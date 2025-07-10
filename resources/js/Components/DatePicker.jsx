"use client"

import * as React from "react"
import { ChevronDownIcon } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Calendar } from "@/components/ui/calendar"
import { Label } from "@/components/ui/label"
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/Components/ui/popover"

/**
 * @typedef {Object} DatePickerProps
 * @property {string} [id] - HTML id for the button and label
 * @property {string} [label] - Text label shown above the input
 * @property {string} [placeholder] - Placeholder text when no date is selected
 * @property {Date} [selected] - Controlled selected date
 * @property {function(Date): void} onSelect - Callback fired when a date is chosen
 * @property {Object} [buttonProps] - Props forwarded to the trigger button
 * @property {Object} [popoverProps] - Props forwarded to the Popover wrapper
 * @property {Object} [calendarProps] - Props forwarded to the Calendar component
 */

export default function DatePicker({
  id = "date",
  placeholder = "Select date",
  selected,
  onSelect,
  buttonProps,
  popoverProps,
  calendarProps,
}) {
  const [open, setOpen] = React.useState(false)

  return (
    <div className="flex flex-col gap-1">
      <Popover open={open} onOpenChange={setOpen} {...popoverProps}>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            id={id}
            className="w-48 justify-between font-normal"
            {...buttonProps}
          >
            {selected ? selected.toLocaleDateString() : placeholder}
            <ChevronDownIcon />
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-auto overflow-hidden p-0" align="start">
          <Calendar
            mode="single"
            selected={selected}
            captionLayout="dropdown"
            onSelect={(date) => {
              onSelect(date)
              setOpen(false)
            }}
            {...calendarProps}
          />
        </PopoverContent>
      </Popover>
    </div>
  )
}
