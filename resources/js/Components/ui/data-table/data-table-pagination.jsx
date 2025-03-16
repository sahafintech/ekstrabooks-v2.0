import { ChevronsLeft, ChevronsRight, ChevronLeft, ChevronRight } from "lucide-react"
import { Button } from "@/Components/ui/button"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select"

export function DataTablePagination({
  table,
  pageSizeOptions = [10, 20, 30, 40, 50],
  totalRows = 0,
}) {
  // Get pagination state from table - guarantee numbers not NaN
  const paginationState = table.getState().pagination;
  const pageIndex = Number.isNaN(Number(paginationState.pageIndex)) ? 0 : Number(paginationState.pageIndex);
  const pageSize = Number.isNaN(Number(paginationState.pageSize)) ? 10 : Number(paginationState.pageSize);
  
  // Calculate current page and total pages with explicit number conversion and protection against NaN
  const currentPage = pageIndex + 1;
  
  // Get page count from table or calculate it
  let pageCount;
  try {
    pageCount = table.getPageCount();
    if (Number.isNaN(Number(pageCount)) || pageCount === undefined) {
      pageCount = Number.isNaN(Number(totalRows)) || Number.isNaN(Number(pageSize)) || Number(pageSize) === 0
        ? 1
        : Math.ceil(Number(totalRows) / Number(pageSize));
    }
  } catch (error) {
    // Fallback if getPageCount throws an error
    pageCount = Number.isNaN(Number(totalRows)) || Number.isNaN(Number(pageSize)) || Number(pageSize) === 0
      ? 1
      : Math.ceil(Number(totalRows) / Number(pageSize));
  }
  
  // Ensure we have a valid number for total pages
  const totalPages = Number.isNaN(Number(pageCount)) ? 1 : Math.max(1, Number(pageCount));

  // Handle going to specific page
  const goToPage = (pageNumber) => {
    // Make sure pageNumber is a valid number
    if (Number.isNaN(Number(pageNumber))) {
      pageNumber = 1;
    }
    
    // pageNumber is 1-indexed, but pageIndex is 0-indexed
    const newPageIndex = Math.max(0, Number(pageNumber) - 1);
    
    // Make sure we don't exceed page boundaries
    const safePageIndex = Math.min(newPageIndex, Math.max(0, totalPages - 1));
    
    // Set the page index
    table.setPageIndex(safePageIndex);
  };

  // Handle changing rows per page
  const changeRowsPerPage = (newPageSize) => {
    // Ensure newPageSize is a number
    const size = Number.isNaN(Number(newPageSize)) ? 10 : Number(newPageSize);
    table.setPageSize(size);
  };

  return (
    <div className="flex items-center justify-between px-2">
      <div className="flex-1 text-sm text-muted-foreground">
        {table.getFilteredSelectedRowModel().rows.length} of{" "}
        {totalRows} row(s) selected.
      </div>
      <div className="flex items-center space-x-6 lg:space-x-8">
        <div className="flex items-center space-x-2">
          <p className="text-sm font-medium">Rows per page</p>
          <Select
            value={`${pageSize}`}
            onValueChange={changeRowsPerPage}
          >
            <SelectTrigger className="h-8 w-[70px]">
              <SelectValue placeholder={pageSize} />
            </SelectTrigger>
            <SelectContent side="top">
              {pageSizeOptions.map((size) => (
                <SelectItem key={size} value={`${size}`}>
                  {size}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="flex w-[100px] items-center justify-center text-sm font-medium">
          Page {currentPage} of{" "}
          {totalPages}
        </div>
        <div className="flex items-center space-x-2">
          <Button
            variant="outline"
            className="hidden h-8 w-8 p-0 lg:flex"
            onClick={() => goToPage(1)}
            disabled={currentPage <= 1}
          >
            <span className="sr-only">Go to first page</span>
            <ChevronsLeft className="h-4 w-4" />
          </Button>
          <Button
            variant="outline"
            className="h-8 w-8 p-0"
            onClick={() => goToPage(currentPage - 1)}
            disabled={currentPage <= 1}
          >
            <span className="sr-only">Go to previous page</span>
            <ChevronLeft className="h-4 w-4" />
          </Button>
          <Button
            variant="outline"
            className="h-8 w-8 p-0"
            onClick={() => goToPage(currentPage + 1)}
            disabled={currentPage >= totalPages}
          >
            <span className="sr-only">Go to next page</span>
            <ChevronRight className="h-4 w-4" />
          </Button>
          <Button
            variant="outline"
            className="hidden h-8 w-8 p-0 lg:flex"
            onClick={() => goToPage(totalPages)}
            disabled={currentPage >= totalPages}
          >
            <span className="sr-only">Go to last page</span>
            <ChevronsRight className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  )
}
