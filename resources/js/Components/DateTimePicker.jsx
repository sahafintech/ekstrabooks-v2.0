// components/DateTimePicker.jsx
import React from "react"
import Flatpickr from "react-flatpickr"
import "flatpickr/dist/flatpickr.min.css"
import { cn } from "@/lib/utils"  // shadcn’s classnames helper
import { usePage } from "@inertiajs/react"

// match shadcn <Input> styles
const inputClasses = `
  flex h-9 w-full 
  rounded-md border border-input px-3 py-1 text-base 
  shadow-sm transition-colors file:border-0 file:bg-transparent 
  file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground 
  focus-visible:outline-none focus:border-none disabled:cursor-not-allowed 
  disabled:opacity-50 md:text-sm focus:ring-primary
`

export default function DateTimePicker({
  value,
  onChange,
  enableTime = false,
  options = {},
  className,
  ...rest
}) {
  const { date_format } = usePage().props
  return (
    <Flatpickr
      {...rest}
      className={cn(inputClasses, className)}
      value={value}
      options={{
        enableTime,
        dateFormat: date_format,
        ...options,
      }}
      // plain JSX-friendly handler:
      onChange={([d]) => {
        if (!d || isNaN(d)) return onChange(null)
      
        // shift the underlying timestamp by the offset:
        //    getTimezoneOffset() is negative for UTC+3, so subtracting it adds 3 hours.
        const zoneless = new Date(d.getTime() - d.getTimezoneOffset() * 60000)
        onChange(zoneless)
      }}
      placeholder="pick a date"
    />
  )
}
