import { SidebarInset } from "@/Components/ui/sidebar";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PageHeader from "@/Components/PageHeader";
import {
    Table,
    TableBody,
    TableCell,
    TableRow,
} from "@/Components/ui/table";
import TableWrapper from "@/Components/shared/TableWrapper";

export default function UserDetails({ user }) {
    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="User" subpage="Details" url="users.index" />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <div className="box">
                        <div className="box-header">
                            <h5>User Details</h5>
                        </div>

                        <div className="box-body">
                            <div className="p-4">
                                <TableWrapper>
                                    <Table>
                                        <TableBody>
                                            <TableRow>
                                                <TableCell colSpan="2" className="text-center">
                                                    <img className="w-20 rounded-md" src={`/uploads/profile/${user.profile_picture}`} />
                                                </TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell>Name</TableCell>
                                                <TableCell>{user.name}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell>Email</TableCell>
                                                <TableCell>{user.email}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell>Status</TableCell>
                                                <TableCell>
                                                    {user.status === 1 ? (
                                                        <span className="badge bg-success rounded-md text-white">Active</span>
                                                    ) : (
                                                        <span className="badge bg-danger rounded-md text-white">Disabled</span>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell>Package</TableCell>
                                                <TableCell>{user.package?.name || "-"}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell>Membership Type</TableCell>
                                                <TableCell>{user.membership_type}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell>Membership Valid Until</TableCell>
                                                <TableCell>{user.valid_to}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell>Mobile</TableCell>
                                                <TableCell>{user.mobile}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell>City</TableCell>
                                                <TableCell>{user.city}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell>State</TableCell>
                                                <TableCell>{user.state}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell>ZIP</TableCell>
                                                <TableCell>{user.zip}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell>Address</TableCell>
                                                <TableCell>{user.address}</TableCell>
                                            </TableRow>
                                            <TableRow>
                                                <TableCell>Registered At</TableCell>
                                                <TableCell>{user.created_at}</TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </TableWrapper>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}