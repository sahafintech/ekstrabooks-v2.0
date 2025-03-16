import * as React from "react"
import {
  flexRender,
  getCoreRowModel,
  getFacetedRowModel,
  getFacetedUniqueValues,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
} from "@tanstack/react-table"

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/Components/ui/table"

import { DataTableToolbar } from "./data-table-toolbar"
import { DataTablePagination } from "./data-table-pagination"
import TableWrapper from "@/Components/shared/TableWrapper"

export function DataTable({
  columns,
  data,
  filterableColumns = [],
  searchableColumns = [],
  totalRows = 0,
  pageCount: defaultPageCount,
  onPaginationChange,
  onTableStateChange,
  tableRef,
  meta,
  serverSide = false,
  initialState = {},
}) {
  // Use initial state if provided, otherwise set defaults
  const [rowSelection, setRowSelection] = React.useState(initialState.rowSelection || {})
  const [columnVisibility, setColumnVisibility] = React.useState(initialState.columnVisibility || {})
  const [columnFilters, setColumnFilters] = React.useState(initialState.columnFilters || [])
  const [sorting, setSorting] = React.useState(initialState.sorting || [])
  const [globalFilter, setGlobalFilter] = React.useState(initialState.globalFilter || "")

  const [{ pageIndex, pageSize }, setPagination] = React.useState(() => {
    // Use initialState pagination if provided
    if (initialState.pagination) {
      return {
        pageIndex: Number.isNaN(Number(initialState.pagination.pageIndex)) ? 0 : Number(initialState.pagination.pageIndex),
        pageSize: Number.isNaN(Number(initialState.pagination.pageSize)) ? 10 : Number(initialState.pagination.pageSize)
      };
    }
    
    // Otherwise use URL params or meta data
    const params = new URLSearchParams(window.location.search);
    const urlPageParam = params.get('page');
    const urlPage = urlPageParam ? Number(urlPageParam) : null;
    
    return {
      pageIndex: Math.max(0, Number.isNaN(Number(urlPage || meta?.current_page)) ? 0 : Number(urlPage || meta?.current_page) - 1),
      pageSize: Number.isNaN(Number(params.get('per_page') || meta?.per_page)) ? 10 : Number(params.get('per_page') || meta?.per_page)
    };
  });

  React.useEffect(() => {
    // Only update pagination state from URL/meta if not server-side
    if (!serverSide) {
      const params = new URLSearchParams(window.location.search);
      const urlPage = params.get('page');
      const metaPage = meta?.current_page;

      // Prefer URL page over meta page
      const newPageIndex = Math.max(0, Number(urlPage || metaPage || 1) - 1);

      setPagination(prev => ({
        ...prev,
        pageIndex: newPageIndex
      }));
    }
  }, [meta?.current_page, window.location.search, serverSide]);

  const pagination = React.useMemo(
    () => ({
      pageIndex,
      pageSize,
    }),
    [pageIndex, pageSize]
  )

  // Calculate page count properly
  const pageCount = React.useMemo(() => {
    if (defaultPageCount !== undefined) {
      return Number(defaultPageCount);
    }
    
    if (totalRows !== undefined && pageSize !== undefined && pageSize > 0) {
      return Math.ceil(Number(totalRows) / Number(pageSize));
    }
    
    return -1; // -1 means infinite pages
  }, [defaultPageCount, totalRows, pageSize]);

  const handlePaginationChange = React.useCallback(
    (newPagination) => {
      // Update the local state first
      setPagination(newPagination);
      
      // Then call the parent component's handler if provided
      if (onPaginationChange) {
        // Add a debugger statement to see what's happening
        console.log('DataTable handlePaginationChange:', {
          newPagination,
          globalFilter,
          columnFilters,
          sorting
        });
        
        onPaginationChange({
          ...newPagination,
          globalFilter,
          columnFilters,
          sorting
        });
      }
    },
    [onPaginationChange, globalFilter, columnFilters, sorting]
  )

  // Handle changes to column filters with server-side support
  const handleColumnFiltersChange = React.useCallback(
    (filters) => {
      setColumnFilters(filters);

      if (serverSide && onTableStateChange) {
        onTableStateChange({
          pagination,
          columnFilters: filters,
          sorting,
          globalFilter
        });
      }
    },
    [serverSide, onTableStateChange, pagination, sorting, globalFilter]
  );

  // Handle changes to sorting with server-side support
  const handleSortingChange = React.useCallback(
    (newSorting) => {
      setSorting(newSorting);

      if (serverSide && onTableStateChange) {
        onTableStateChange({
          pagination,
          columnFilters,
          sorting: newSorting,
          globalFilter
        });
      }
    },
    [serverSide, onTableStateChange, pagination, columnFilters, globalFilter]
  );

  // Handle changes to global filter (search) with server-side support
  const handleGlobalFilterChange = React.useCallback(
    (newFilter) => {
      setGlobalFilter(newFilter);

      if (serverSide && onTableStateChange) {
        onTableStateChange({
          pagination,
          columnFilters,
          sorting,
          globalFilter: newFilter
        });
      }
    },
    [serverSide, onTableStateChange, pagination, columnFilters, sorting]
  );

  const table = useReactTable({
    data,
    columns,
    state: {
      pagination: {
        pageIndex,
        pageSize,
      },
      sorting,
      columnFilters,
      rowSelection,
      columnVisibility,
      globalFilter,
    },
    pageCount: pageCount,
    enableRowSelection: true,
    manualPagination: serverSide,
    manualFiltering: serverSide,
    manualSorting: serverSide,
    onPaginationChange: handlePaginationChange,
    onSortingChange: handleSortingChange,
    onColumnFiltersChange: handleColumnFiltersChange,
    onRowSelectionChange: setRowSelection,
    onColumnVisibilityChange: setColumnVisibility,
    onGlobalFilterChange: handleGlobalFilterChange,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: serverSide ? undefined : getFilteredRowModel(),
    getPaginationRowModel: serverSide ? undefined : getPaginationRowModel(),
    getSortedRowModel: serverSide ? undefined : getSortedRowModel(),
    getFacetedRowModel: getFacetedRowModel(),
    getFacetedUniqueValues: getFacetedUniqueValues(),
  })

  // Sync server data with table state
  React.useEffect(() => {
    if (tableRef) {
      tableRef({
        resetTable: () => {
          setGlobalFilter("");
          setColumnFilters([]);
          setSorting([]);
          setPagination({
            pageIndex: 0,
            pageSize: 10,
          });
        },
        setPage: (page) => {
          setPagination(prev => ({
            ...prev,
            pageIndex: Number(page) - 1, // Convert 1-indexed to 0-indexed
          }));
        },
        getState: () => ({
          pagination: {
            pageIndex,
            pageSize,
          },
          globalFilter,
          columnFilters,
          sorting,
        }),
      });
    }
  }, [tableRef, pageIndex, pageSize, globalFilter, columnFilters, sorting]);

  return (
    <div className="space-y-4">
      <DataTableToolbar
        table={table}
        filterableColumns={filterableColumns}
        searchableColumns={searchableColumns}
        serverSide={serverSide}
      />
      <TableWrapper>
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => {
                  return (
                    <TableHead key={header.id}>
                      {header.isPlaceholder
                        ? null
                        : flexRender(
                          header.column.columnDef.header,
                          header.getContext()
                        )}
                    </TableHead>
                  )
                })}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {table.getRowModel().rows?.length ? (
              table.getRowModel().rows.map((row) => (
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && "selected"}
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>
                      {flexRender(
                        cell.column.columnDef.cell,
                        cell.getContext()
                      )}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell
                  colSpan={columns.length}
                  className="h-24 text-center"
                >
                  No results.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </TableWrapper>
      <DataTablePagination
        table={table}
        totalRows={totalRows}
      />
    </div>
  )
}
