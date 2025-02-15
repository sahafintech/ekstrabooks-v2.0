<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Select2Controller extends Controller {

    public function __construct() {
        date_default_timezone_set(get_option('timezone', 'Asia/Dhaka'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_table_data(Request $request) {

        if (isset($request->activeBusiness)) {
            $data_where = array(
                '1' => array('user_id' => auth()->id()),
                '2' => array('user_type' => 'user'),
                '3' => array('business_id' => $request->activeBusiness->id),
                '4' => array('business_id' => $request->activeBusiness->id, 'type' => 'income'),
                '5' => array('business_id' => $request->activeBusiness->id, 'type' => 'expense'),
                '6' => array('business_id' => $request->activeBusiness->id, 'allow_for_selling' => 1, 'status' => 1),
                '7' => array('business_id' => $request->activeBusiness->id, 'allow_for_purchasing' => 1, 'status' => 1),
                '8' => array('business_id' => $request->activeBusiness->id, 'status' => 1),
            );
        } else {
            $data_where = array(
                '1' => array('user_id' => auth()->id()),
                '2' => array('user_type' => 'user'),
            );
        }

        $table    = $request->get('table');
        $value    = $request->get('value');
        $display  = $request->get('display');
        $display2 = $request->get('display2');
        $divider  = $request->get('divider');
        $where    = $request->get('where');

        $q = $request->get('q');

        $display_option = "$display as text";
        if ($display2 != '') {
            $display_option = "CONCAT($display,' $divider ',$display2) AS text";
        }

        if ($where != 'undefined') {
            $result = DB::table($table)
                ->select("$value as id", DB::raw($display_option))
                ->where($display, 'LIKE', "$q%")
                ->where($data_where[$where])
                ->get();
        } else {
            $result = DB::table($table)
                ->select("$value as id", DB::raw($display_option))
                ->where($display, 'LIKE', "$q%")
                ->get();
        }

        return $result;
    }

}
