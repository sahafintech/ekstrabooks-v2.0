import { X } from "lucide-react"
import { Button } from "@/Components/ui/button"
import { Input } from "@/Components/ui/input"
import { DataTableViewOptions } from "./data-table-view-options"
import { DataTableFacetedFilter } from "./data-table-faceted-filter"
import { useState, useEffect } from "react"

export function DataTableToolbar({ 
  table, 
  filterableColumns = [], 
  searchableColumns = [],
  serverSide = false 
}) {
  const isFiltered = table.getState().columnFilters.length > 0
  const [searchValue, setSearchValue] = useState(table.getState().globalFilter || "")
  
  // Debounce search input to prevent excessive server requests
  useEffect(() => {
    const handler = setTimeout(() => {
      if (searchValue !== table.getState().globalFilter) {
        table.setGlobalFilter(searchValue);
      }
    }, 300); // 300ms debounce delay
    
    return () => clearTimeout(handler);
  }, [searchValue, table]);

  return (
    <div className="flex items-center justify-between p-4">
      <div className="flex flex-1 items-center space-x-2">
        {/* Global search input */}
        <Input
          placeholder="Search all columns..."
          value={searchValue}
          onChange={(event) => setSearchValue(event.target.value)}
          className="h-8 w-[150px] lg:w-[250px]"
        />
        
        {/* Column-specific search inputs */}
        {searchableColumns.length > 0 &&
          !serverSide && // Only show column-specific search inputs for client-side filtering
          searchableColumns.map(
            (column) =>
              table.getColumn(column.id) && (
                <Input
                  key={column.id}
                  placeholder={`Search ${column.title}...`}
                  value={(table.getColumn(column.id)?.getFilterValue() ?? "")}
                  onChange={(event) =>
                    table.getColumn(column.id)?.setFilterValue(event.target.value)
                  }
                  className="h-8 w-[150px] lg:w-[250px]"
                />
              )
          )}
          
        {/* Faceted filters for categorical data */}
        {filterableColumns.length > 0 &&
          filterableColumns.map(
            (column) =>
              table.getColumn(column.id) && (
                <DataTableFacetedFilter
                  key={column.id}
                  column={table.getColumn(column.id)}
                  title={column.title}
                  options={column.options}
                />
              )
          )}
          
        {/* Reset filters button */}
        {isFiltered && (
          <Button
            variant="ghost"
            onClick={() => {
              table.resetColumnFilters();
              setSearchValue("");
              table.setGlobalFilter("");
            }}
            className="h-8 px-2 lg:px-3"
          >
            Reset
            <X className="ml-2 h-4 w-4" />
          </Button>
        )}
      </div>
      <DataTableViewOptions table={table} />
    </div>
  )
}
