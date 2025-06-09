<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use Inertia\Inertia;
use Jmrashed\Zkteco\Lib\ZKTeco;

class AttendanceLogController extends Controller
{
    public function index()
    {
        $attendanceLogs = AttendanceLog::with('staff')->orderBy('timestamp', 'desc')->get();
        return Inertia::render('Backend/User/AttendanceLog/List', [
            'attendanceLogs' => $attendanceLogs
        ]);
    }

    public function fetch() {
        $zk = new ZKTeco('192.168.100.100', 4370);

        if ($zk->connect()) {
            $attendances = $zk->getAttendance();
            $zk->disconnect();

            foreach ($attendances as $attendance) {
                $attendanceLogs = AttendanceLog::where('id', $attendance['uid'])->first();
                if (!$attendanceLogs) {
                    $attendanceLogs = new AttendanceLog();
                    $attendanceLogs->id = $attendance['uid'];
                    $attendanceLogs->employee_id = $attendance['id'];
                    $attendanceLogs->state = $attendance['state'];
                    $attendanceLogs->timestamp = $attendance['timestamp'];
                    $attendanceLogs->save();
                }
            }
        }

        return redirect()->route('attendance_logs.index')->with('success', 'Attendance logs fetched successfully');
    }
}
