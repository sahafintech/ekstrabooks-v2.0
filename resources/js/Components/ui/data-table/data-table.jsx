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
  tableRef,
  meta,
}) {
  const [rowSelection, setRowSelection] = React.useState({})
  const [columnVisibility, setColumnVisibility] = React.useState({})
  const [columnFilters, setColumnFilters] = React.useState([])
  const [sorting, setSorting] = React.useState([])
  const [{ pageIndex, pageSize }, setPagination] = React.useState(() => {
    const params = new URLSearchParams(window.location.search);
    return {
      pageIndex: Math.max(0, Number(params.get('page') || meta?.current_page || 1) - 1),
      pageSize: Number(params.get('per_page') || meta?.per_page || 10)
    };
  });

  React.useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const urlPage = params.get('page');
    const metaPage = meta?.current_page;
    
    // Prefer URL page over meta page
    const newPageIndex = Math.max(0, Number(urlPage || metaPage || 1) - 1);
    
    setPagination(prev => ({
      ...prev,
      pageIndex: newPageIndex
    }));
  }, [meta?.current_page, window.location.search]);

  const pagination = React.useMemo(
    () => ({
      pageIndex,
      pageSize,
    }),
    [pageIndex, pageSize]
  )

  const pageCount = React.useMemo(
    () => defaultPageCount || Math.ceil(totalRows / pageSize),
    [defaultPageCount, totalRows, pageSize]
  )

  const handlePaginationChange = React.useCallback(
    (newPagination) => {
      setPagination(newPagination)
      if (onPaginationChange) {
        onPaginationChange(newPagination)
      }
    },
    [onPaginationChange]
  )

  const table = useReactTable({
    data,
    columns,
    pageCount: pageCount,
    state: {
      sorting,
      columnVisibility,
      rowSelection,
      columnFilters,
      pagination,
    },
    enableRowSelection: true,
    onRowSelectionChange: setRowSelection,
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    onColumnVisibilityChange: setColumnVisibility,
    onPaginationChange: handlePaginationChange,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFacetedRowModel: getFacetedRowModel(),
    getFacetedUniqueValues: getFacetedUniqueValues(),
    manualPagination: true,
  })

  React.useEffect(() => {
    if (tableRef) {
      tableRef(table)
    }
  }, [table, tableRef])

  return (
    <div className="space-y-4">
      <DataTableToolbar
        table={table}
        filterableColumns={filterableColumns}
        searchableColumns={searchableColumns}
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
