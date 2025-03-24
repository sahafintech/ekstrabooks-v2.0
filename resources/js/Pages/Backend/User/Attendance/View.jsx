import React from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { format } from "date-fns";
import { ArrowLeft, Download, Edit } from "lucide-react";
import PageHeader from "@/Components/PageHeader";
import { Separator } from "@/Components/ui/separator";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/Components/ui/table";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";

export default function View({ date, attendanceRecords = [], stats = {} }) {
  const { auth } = usePage().props;
  const formattedDate = date ? format(new Date(date), "PPP") : "";

  const getStatusBadge = (status) => {
    if (status === 0 || status === "0") {
      return <span className="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Absent</span>;
    } else if (status === 1 || status === "1") {
      return <span className="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Present</span>;
    } else {
      return <span className="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Leave</span>;
    }
  };

  return (
    <AuthenticatedLayout>
      <Head title={`Attendance for ${formattedDate}`} />
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Attendance Management"
            subpage={`Attendance for ${formattedDate}`}
            url="attendance.index"
          />
          <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-2">
                <Link href={route("attendance.index")}>
                  <Button variant="outline" size="sm">
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back to List
                  </Button>
                </Link>
                <Link href={route("attendance.create", { date: date })}>
                  <Button variant="outline" size="sm">
                    <Edit className="mr-2 h-4 w-4" />
                    Edit Attendance
                  </Button>
                </Link>
              </div>
              
              <Button variant="outline" size="sm">
                <Download className="mr-2 h-4 w-4" />
                Export
              </Button>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-medium">Total Attendance</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{stats.total_attendance || attendanceRecords.length}</div>
                </CardContent>
              </Card>
              
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-medium">Present</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold text-green-600">{stats.present || attendanceRecords.filter(a => a.status === 1 || a.status === "1").length}</div>
                </CardContent>
              </Card>
              
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-medium">Absent</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold text-red-600">{stats.absent || attendanceRecords.filter(a => a.status === 0 || a.status === "0").length}</div>
                </CardContent>
              </Card>
            </div>
            
            <div className="rounded-md border mt-4">
              <div className="p-4 border-b">
                <h3 className="text-lg font-semibold">Attendance Details</h3>
              </div>
              
              <div className="overflow-hidden rounded-md">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Employee ID</TableHead>
                      <TableHead>Employee Name</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Leave Type</TableHead>
                      <TableHead>Leave Duration</TableHead>
                      <TableHead>Remarks</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {attendanceRecords.length === 0 ? (
                      <TableRow>
                        <TableCell
                          colSpan={6}
                          className="text-center text-muted-foreground py-10"
                        >
                          No attendance records found for this date
                        </TableCell>
                      </TableRow>
                    ) : (
                      attendanceRecords.map((record) => (
                        <TableRow key={record.id || record.employee_id}>
                          <TableCell>{record.staff?.employee_id || record.employee_id}</TableCell>
                          <TableCell>{record.staff?.name || record.employee_name}</TableCell>
                          <TableCell>{getStatusBadge(record.status)}</TableCell>
                          <TableCell>{record.leave_type || "-"}</TableCell>
                          <TableCell>
                            {record.leave_duration ? (
                              record.leave_duration === "full_day" ? "Full Day" : "Half Day"
                            ) : "-"}
                          </TableCell>
                          <TableCell>{record.remarks || "-"}</TableCell>
                        </TableRow>
                      ))
                    )}
                  </TableBody>
                </Table>
              </div>
            </div>
            
            {auth.user.can?.manage_leave_applications && (
              <div className="rounded-md border mt-4 p-4">
                <h3 className="text-lg font-semibold mb-2">Absent Fine Configuration</h3>
                <Separator className="my-2" />
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                  <div>
                    <p className="text-sm font-medium mb-1">Full Day Absent Fine</p>
                    <p className="text-lg font-semibold">{stats.full_day_fine || "-"}</p>
                  </div>
                  <div>
                    <p className="text-sm font-medium mb-1">Half Day Absent Fine</p>
                    <p className="text-lg font-semibold">{stats.half_day_fine || "-"}</p>
                  </div>
                </div>
                
                <div className="mt-4">
                  <Link href={route("attendance.absent_fine")}>
                    <Button variant="outline" size="sm">
                      <Edit className="mr-2 h-4 w-4" />
                      Configure Absent Fine
                    </Button>
                  </Link>
                </div>
              </div>
            )}
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
