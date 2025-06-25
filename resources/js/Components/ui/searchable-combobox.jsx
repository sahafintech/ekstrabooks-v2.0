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

export function SearchableCombobox({
  options = [],
  value = "",
  onChange = () => {},
  placeholder = "Search...",
  emptyMessage = "No results found.",
  className,
}) {
  const [open, setOpen] = React.useState(false)
  const [searchValue, setSearchValue] = React.useState("")

  // Ensure options is an array and has the correct structure
  const safeOptions = React.useMemo(() => {
    if (!Array.isArray(options)) return []
    return options.map(option => {
      if (typeof option === 'object' && option !== null) {
        return {
          value: String(option.id !== undefined && option.id !== null ? option.id : ''),
          label: String(option.name || option.unit || option.account_name || '')
        }
      }
      return { value: String(option), label: String(option) }
    }).filter(option => option.value !== '' && option.label !== '')
  }, [options])

  const filteredOptions = React.useMemo(() => {
    if (!searchValue) return safeOptions
    return safeOptions.filter((option) =>
      option.label.toLowerCase().includes(searchValue.toLowerCase())
    )
  }, [safeOptions, searchValue])

  const selectedLabel = React.useMemo(() => {
    const selected = safeOptions.find(option => option.value === String(value))
    return selected?.label || placeholder
  }, [safeOptions, value, placeholder])

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          className={cn(
            "w-full justify-between text-left whitespace-normal h-auto min-h-[2.5rem]",
            className
          )}
        >
          <span className="flex-1 break-words">{selectedLabel}</span>
          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50 flex-shrink-0" />
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
                {filteredOptions.map((option) => (
                  <CommandItem
                    key={option.value}
                    value={option.value}
                    onSelect={(currentValue) => {
                      onChange(currentValue === value ? "" : currentValue)
                      setOpen(false)
                      setSearchValue("")
                    }}
                    className="w-full"
                  >
                    <Check
                      className={cn(
                        "mr-2 h-4 w-4",
                        value === option.value ? "opacity-100" : "opacity-0"
                      )}
                    />
                    {option.label}
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
