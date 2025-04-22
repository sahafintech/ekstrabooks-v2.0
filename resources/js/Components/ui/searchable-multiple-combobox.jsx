import * as React from "react"
import { Check, ChevronsUpDown } from "lucide-react"
import { cn } from "@/lib/utils"
import { Button } from "@/Components/ui/button"
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "@/Components/ui/command"
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/Components/ui/popover"

/**
 * Multi-select searchable combobox component
 *
 * Props:
 * - options: array of items (string or object with id/name)
 * - value: array of selected values (string[])
 * - onChange: (newValues: string[]) => void
 * - placeholder: placeholder text when no selection
 * - emptyMessage: shown when no options match
 * - className: additional styles for trigger button
 */
export function SearchableMultiSelectCombobox({
  options = [],
  value = [],
  onChange = () => {},
  placeholder = "Select...",
  emptyMessage = "No results found.",
  className,
}) {
  const [open, setOpen] = React.useState(false)
  const [searchValue, setSearchValue] = React.useState("")

  // Normalize options to { value, label }
  const safeOptions = React.useMemo(() => {
    if (!Array.isArray(options)) return []
    return options
      .map(option => {
        if (typeof option === 'object' && option !== null) {
          return {
            value: String(option.id ?? option.value ?? ''),
            label: String(option.name ?? option.label ?? option.unit ?? option.account_name ?? ''),
          }
        }
        return { value: String(option), label: String(option) }
      })
      .filter(opt => opt.value && opt.label)
  }, [options])

  // Filter based on search input
  const filteredOptions = React.useMemo(() => {
    if (!searchValue) return safeOptions
    return safeOptions.filter(opt =>
      opt.label.toLowerCase().includes(searchValue.toLowerCase())
    )
  }, [safeOptions, searchValue])

  // Labels of currently selected items
  const selectedLabels = React.useMemo(() => {
    const selected = safeOptions.filter(opt => value.includes(opt.value))
    if (selected.length === 0) return placeholder
    // join labels or show count
    return selected.map(opt => opt.label).join(', ')
  }, [safeOptions, value, placeholder])

  // Toggle selection for a given value
  const handleSelect = (val) => {
    let newValues
    if (value.includes(val)) {
      newValues = value.filter(v => v !== val)
    } else {
      newValues = [...value, val]
    }
    onChange(newValues)
    setSearchValue("")
    // keep popover open for additional selections
  }

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-multiselectable="true"
          aria-expanded={open}
          className={cn("w-full justify-between", className)}
        >
          {selectedLabels}
          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0" align="start">
        <Command shouldFilter={false} className="w-full">
          <CommandInput
            placeholder={placeholder}
            value={searchValue}
            onValueChange={setSearchValue}
            className="w-full"
          />
          <CommandList className="w-full max-h-[200px] overflow-y-auto">
            {filteredOptions.length === 0 ? (
              <CommandEmpty>{emptyMessage}</CommandEmpty>
            ) : (
              <CommandGroup className="w-full">
                {filteredOptions.map(opt => (
                  <CommandItem
                    key={opt.value}
                    value={opt.value}
                    onSelect={() => handleSelect(opt.value)}
                    className="w-full"
                  >
                    <Check
                      className={cn(
                        "mr-2 h-4 w-4",
                        value.includes(opt.value) ? "opacity-100" : "opacity-0"
                      )}
                    />
                    {opt.label}
                  </CommandItem>
                ))}
              </CommandGroup>
            )}
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  )
}
