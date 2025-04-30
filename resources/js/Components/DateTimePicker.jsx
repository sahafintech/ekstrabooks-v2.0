import React from "react"
import Flatpickr from "react-flatpickr"
import "flatpickr/dist/flatpickr.min.css"
import { cn } from "@/lib/utils"
import { usePage } from "@inertiajs/react"

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
  isRange = false,
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
        mode: isRange ? "range" : "single",
        dateFormat: date_format,
        ...options,
      }}
      onChange={(selectedDates) => {
        if (isRange) {
          if (selectedDates.length !== 2) return // wait until both dates are selected
          if (selectedDates.some((d) => !d || isNaN(d))) {
            return onChange(null)
          }
          const zonelessDates = selectedDates.map(
            (d) => new Date(d.getTime() - d.getTimezoneOffset() * 60000)
          )
          onChange(zonelessDates)
        } else {
          const [d] = selectedDates
          if (!d || isNaN(d)) return onChange(null)
          const zoneless = new Date(d.getTime() - d.getTimezoneOffset() * 60000)
          onChange(zoneless)
        }
      }}
      placeholder={isRange ? "pick a date range" : "pick a date"}
    />
  )
}
