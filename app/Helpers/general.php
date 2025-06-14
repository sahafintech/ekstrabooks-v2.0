<?php

use App\Models\Account;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

if (!function_exists('_lang')) {
	function _lang($string = '')
	{

		$target_lang = get_language();

		if ($target_lang == '') {
			$target_lang = "language";
		}

		if (file_exists(resource_path() . "/language/$target_lang.php")) {
			include resource_path() . "/language/$target_lang.php";
		} else {
			include resource_path() . "/language/language.php";
		}

		if (array_key_exists($string, $language)) {
			return $language[$string];
		} else {
			return $string;
		}
	}
}

if (!function_exists('_dlang')) {
	function _dlang($string = '')
	{

		//Get Target language
		$target_lang = get_language();

		if ($target_lang == '') {
			$target_lang = 'language';
		}

		if (file_exists(resource_path() . "/language/$target_lang.php")) {
			include resource_path() . "/language/$target_lang.php";
		} else {
			include resource_path() . "/language/language.php";
		}

		if (array_key_exists($string, $language)) {
			return $language[$string];
		} else {
			return $string;
		}
	}
}

if (!function_exists('startsWith')) {
	function startsWith($haystack, $needle)
	{
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}
}

if (!function_exists('get_initials')) {
	function get_initials($string)
	{
		$words = explode(" ", $string);
		$initials = null;
		foreach ($words as $w) {
			$initials .= $w[0];
		}
		return $initials;
	}
}

if (!function_exists('create_option')) {
	function create_option($table, $value, $display, $selected = '', $where = NULL, $concat = ' ')
	{
		$options = '';
		$condition = '';
		if ($where != NULL) {
			$condition .= "WHERE ";
			foreach ($where as $key => $v) {
				$condition .= $key . "'" . $v . "' ";
			}
		}

		if (is_array($display)) {
			$display_array = $display;
			$display = $display_array[0];
			$display1 = $display_array[1];
		}

		$query = DB::select("SELECT * FROM $table $condition ORDER BY $display asc");
		foreach ($query as $d) {
			if ($selected != '' && $selected == $d->$value) {
				if (!isset($display_array)) {
					$options .= "<option value='" . $d->$value . "' selected='true'>" . ucwords($d->$display) . "</option>";
				} else {
					$options .= "<option value='" . $d->$value . "' selected='true'>" . ucwords($d->$display . $concat . $d->$display1) . "</option>";
				}
			} else {
				if (!isset($display_array)) {
					$options .= "<option value='" . $d->$value . "'>" . ucwords($d->$display) . "</option>";
				} else {
					$options .= "<option value='" . $d->$value . "'>" . ucwords($d->$display . $concat . $d->$display1) . "</option>";
				}
			}
		}

		echo $options;
	}
}

if (!function_exists('object_to_string')) {
	function object_to_string($object, $col, $quote = false)
	{
		$string = "";
		foreach ($object as $data) {
			if ($quote == true) {
				$string .= "'" . $data->$col . "', ";
			} else {
				$string .= $data->$col . ", ";
			}
		}
		$string = substr_replace($string, "", -2);
		return $string;
	}
}

if (!function_exists('get_table')) {
	function get_table($table, $where = NULL)
	{
		$condition = "";
		if ($where != NULL) {
			$condition .= "WHERE ";
			foreach ($where as $key => $v) {
				$condition .= $key . "'" . $v . "' ";
			}
		}
		$query = DB::select("SELECT * FROM $table $condition");
		return $query;
	}
}

if (!function_exists('user_count')) {
	function user_count($user_type)
	{
		$count = \App\Models\User::where("user_type", $user_type)
			->selectRaw("COUNT(id) as total")
			->first()->total;
		return $count;
	}
}

if (!function_exists('has_permission')) {
	function has_permission($name)
	{
		$permission_list = request()->permissionList;
		$permission = $permission_list->firstWhere('permission', $name);

		if ($permission != null) {
			return true;
		}
		return false;
	}
}

if (!function_exists('get_logo')) {
	function get_logo()
	{
		$logo = get_option("logo");
		if ($logo == "") {
			return asset("/backend/images/company-logo.png");
		}
		return asset("/uploads/media/$logo");
	}
}

if (!function_exists('get_favicon')) {
	function get_favicon()
	{
		$favicon = get_option("favicon");
		if ($favicon == "") {
			return asset("/backend/images/favicon.png");
		}
		return asset("/uploads/media/$favicon");
	}
}

if (!function_exists('profile_picture')) {
	function profile_picture($profile_picture = '')
	{
		if ($profile_picture == '') {
			$profile_picture = Auth::user()->profile_picture;
		}

		if ($profile_picture == '') {
			return asset('/backend/images/avatar.png');
		}

		return asset('/uploads/profile/' . $profile_picture);
	}
}

if (!function_exists('get_option')) {
	function get_option($name, $optional = '')
	{
		$value = Cache::get($name);

		if ($value == "") {
			$setting = DB::table('settings')->where('name', $name)->get();
			if (!$setting->isEmpty()) {
				$value = $setting[0]->value;
				Cache::put($name, $value);
			} else {
				$value = $optional;
			}
		}
		return $value;
	}
}

if (!function_exists('get_business_option')) {
	function get_business_option($name, $optional = '', $businessId = '')
	{
		global ${$name};

		if (${$name} != null) {
			return ${$name};
		}

		if ($businessId == '') {
			if (isset(request()->activeBusiness->id)) {
				$setting = \App\Models\BusinessSetting::withoutGlobalScopes()->where('name', $name)
					->where('business_id', request()->activeBusiness->id)
					->first();
			} else {
				$setting = \App\Models\BusinessSetting::where('name', $name)->first();
			}
		} else {
			$setting = \App\Models\BusinessSetting::withoutGlobalScopes()->where('name', $name)
				->where('business_id', $businessId)
				->first();
		}

		if ($setting) {
			$value = $setting->value;
		} else {
			$value = $optional;
		}

		${$name} = $value;

		return $value;
	}
}

if (!function_exists('get_setting')) {
	function get_setting($settings, $name, $optional = '')
	{
		$row = $settings->firstWhere('name', $name);
		if ($row != null) {
			return $row->value;
		}
		return $optional;
	}
}

if (!function_exists('get_trans_option')) {
	function get_trans_option($name, $optional = '')
	{
		$setting = \App\Models\Setting::where('name', $name)->first();

		if ($setting) {
			$value = $setting->translation->value;
		} else {
			$value = $optional;
		}

		return $value;
	}
}

if (!function_exists('navigationTree')) {

	function navigationTree($object, $currentParent, $controller, $currLevel = 0, $prevLevel = -1)
	{
		foreach ($object as $menu) {
			if ($currentParent == $menu->parent_id) {
				if ($currLevel > $prevLevel) {
					echo "<ol id='menutree' class='dd-list'>";
				}

				if ($currLevel == $prevLevel) {
					echo "</li>";
				}

				echo '<li class="dd-item" data-id="' . $menu->id . '"><div class="dd-handle">' . $menu->translation->name . '</div><a class="edit_menu" href="' . action("$controller@edit", $menu->id) . '"><i class="ti-trash"></i></a>
					<a class="btn-remove-2 remove_menu" href="' . action("$controller@destroy", $menu->id) . '"><i class="ti-trash"></i></a>';
				if ($currLevel > $prevLevel) {
					$prevLevel = $currLevel;
				}
				$currLevel++;
				navigationTree($object, $menu->id, $controller, $currLevel, $prevLevel);
				$currLevel--;
			}
		}
		if ($currLevel == $prevLevel) {
			echo "</li> </ol>";
		}
	}
}

if (!function_exists('show_navigation')) {
	function show_navigation($nav_id, $main_class = '', $nav_link_class = '', $dp_1_class = '', $dp_2_class = '', $icon_type = 'down')
	{

		$navigation = \App\Models\Navigation::where('id', $nav_id)
			->where('status', 1)->first();
		if ($navigation) {
			$navigation_items = $navigation->navigationItems()->where('status', 1)->get();
			buildNavigation($navigation_items, $main_class, $nav_link_class, $dp_1_class, $dp_2_class, $icon_type);
		}
	}
}

if (!function_exists('buildNavigation')) {

	function buildNavigation($navigation_items, $main_class = '', $nav_link_class = '', $dropdown_1_class = '', $dropdown_2_class = '', $icon_type = 'down', $currentParent = 0, $currLevel = 0, $prevLevel = -1)
	{
		foreach ($navigation_items as $nav_item) {
			if ($currentParent == $nav_item->parent_id) {

				if ($currLevel > $prevLevel && $currLevel == 0) {
					echo "<ul class='$main_class'>";
				} else if ($currLevel > $prevLevel && $currLevel == 1) {
					echo "<ul class='$dropdown_1_class'>";
				} else if ($currLevel > $prevLevel && $currLevel == 2) {
					echo "<ul class='$dropdown_2_class'>";
				}

				if ($currLevel == $prevLevel) {
					echo "</li>";
				}

				if ($nav_item->type == 'dynamic_url') {
					$url = url($nav_item->url);
				} else if ($nav_item->type == 'page') {
					$url = url('/' . $nav_item->page->slug);
				} else if ($nav_item->type == 'category') {
					$url = url('/categories/' . $nav_item->category->slug);
				} else if ($nav_item->type == 'custom_url') {
					$url = $nav_item->url;
				}

				$icon = $nav_item->icon;
				$target = $nav_item->target;
				$active = $url == url()->current() ? 'active' : '';
				$css_class = $nav_item->css_class != '' ? "class='$nav_item->css_class $active $nav_link_class'" : "class='$active $nav_link_class'";
				$css_id = $nav_item->css_id != '' ? "id='$nav_item->css_id'" : "";

				$has_child = '';

				if ($nav_item->child_items->count() > 0 && $currLevel == 0) {
					$has_child = $icon_type == 'down' ? ' <i class="bi bi-chevron-down"></i>' : ' <i class="bi bi-chevron-right"></i>';
				} else if ($nav_item->child_items->count() > 0 && $currLevel == 1) {
					$has_child = ' <i class="bi bi-chevron-right"></i>';
				}

				echo '<li class="nav-item"><a target="' . $target . '" href="' . $url . '" ' . $css_class . ' ' . $css_id . '>' . $icon . ' ' . $nav_item->translation->name . ' ' . $has_child . '</a>';

				if ($currLevel > $prevLevel) {
					$prevLevel = $currLevel;
				}

				$currLevel++;
				buildNavigation($navigation_items, $main_class, $nav_link_class, $dropdown_1_class, $dropdown_2_class, $icon_type, $nav_item->id, $currLevel, $prevLevel);
				$currLevel--;
			}
		}
		if ($currLevel == $prevLevel) {
			echo "</li> </ul>";
		}
	}
}

if (!function_exists('get_array_option')) {
	function get_array_option($name, $key = '', $optional = '')
	{
		if ($key == '') {
			if (session('language') == '') {
				$key = get_option('language');
				session(['language' => $key]);
			} else {
				$key = session('language');
			}
		}
		$setting = DB::table('settings')->where('name', $name)->get();
		if (!$setting->isEmpty()) {

			$value = $setting[0]->value;
			if (@unserialize($value) !== false) {
				$value = @unserialize($setting[0]->value);

				return isset($value[$key]) ? $value[$key] : $value[array_key_first($value)];
			}

			return $value;
		}
		return $optional;
	}
}

if (!function_exists('get_array_data')) {
	function get_array_data($data, $key = '')
	{
		if ($key == '') {
			if (session('language') == '') {
				$key = get_option('language');
				session(['language' => $key]);
			} else {
				$key = session('language');
			}
		}

		if (@unserialize($data) !== false) {
			$value = @unserialize($data);
			return isset($value[$key]) ? $value[$key] : $value[array_key_first($value)];
		}

		return $data;
	}
}

if (!function_exists('update_option')) {
	function update_option($name, $value)
	{
		date_default_timezone_set(get_option('timezone', 'Asia/Dhaka'));

		$data = array();
		$data['value'] = $value;
		$data['updated_at'] = \Carbon\Carbon::now();
		if (\App\Models\Setting::where('name', $name)->exists()) {
			\App\Models\Setting::where('name', $name)->update($data);
		} else {
			$data['name'] = $name;
			$data['created_at'] = \Carbon\Carbon::now();
			\App\Models\Setting::insert($data);
		}
		Cache::put($name, $value);
	}
}

if (!function_exists('update_business_option')) {
	function update_business_option($name, $value, $businessId = '')
	{

		$data = array();
		$data['value'] = $value;
		if ($businessId != '') {
			$data['business_id'] = $businessId;
		}
		$data['updated_at'] = \Carbon\Carbon::now();

		$setting = \App\Models\BusinessSetting::where('name', $name)
			->when($businessId, function (Builder $query, string $businessId) {
				$query->where('business_id', $businessId);
			});

		if ($setting->exists()) {
			\App\Models\BusinessSetting::where('name', $name)
				->when($businessId, function (Builder $query, string $businessId) {
					$query->where('business_id', $businessId);
				})->update($data);
		} else {
			$data['name'] = $name;
			$data['created_at'] = \Carbon\Carbon::now();
			\App\Models\BusinessSetting::insert($data);
		}
	}
}

if (!function_exists('timezone_list')) {

	function timezone_list()
	{
		$zones_array = array();
		$timestamp = time();
		foreach (timezone_identifiers_list() as $key => $zone) {
			date_default_timezone_set($zone);
			$zones_array[$key]['ZONE'] = $zone;
			$zones_array[$key]['GMT'] = 'UTC/GMT ' . date('P', $timestamp);
		}
		return $zones_array;
	}
}

if (!function_exists('create_timezone_option')) {

	function create_timezone_option($old = "")
	{
		$option = "";
		$timestamp = time();
		foreach (timezone_identifiers_list() as $key => $zone) {
			date_default_timezone_set($zone);
			$selected = $old == $zone ? "selected" : "";
			$option .= '<option value="' . $zone . '"' . $selected . '>' . 'GMT ' . date('P', $timestamp) . ' ' . $zone . '</option>';
		}
		echo $option;
	}
}

if (!function_exists('get_timezone_list')) {

	function get_timezone_list()
	{
		$timezones = [];
		$timestamp = time();
		foreach (timezone_identifiers_list() as $zone) {
			date_default_timezone_set($zone);
			$timezones[] = [
				'id' => $zone,
				'value' => $zone,
				'label' => $zone,
				'name' => 'GMT ' . date('P', $timestamp) . ' ' . $zone
			];
		}

		// Reset to default timezone
		date_default_timezone_set(config('app.timezone'));

		return $timezones;
	}
}

if (!function_exists('load_language')) {
	function load_language($active = '')
	{
		$path = resource_path() . "/language";
		$files = scandir($path);
		$options = array();

		foreach ($files as $file) {
			$name = pathinfo($file, PATHINFO_FILENAME);
			if ($name == "." || $name == "" || $name == "language") {
				continue;
			}

			$options[] = [
				'code' => $name,
				'label' => _lang($name)
			];
		}
		return $options;
	}
}

if (!function_exists('get_language_list')) {
	function get_language_list()
	{
		$path = resource_path() . "/language";
		$files = scandir($path);
		$array = array();

		foreach ($files as $file) {
			$name = pathinfo($file, PATHINFO_FILENAME);
			if ($name == "." || $name == "" || $name == "language" || $name == "flags") {
				continue;
			}

			$array[] = $name;
		}
		return $array;
	}
}

if (!function_exists('process_string')) {

	function process_string($search_replace, $string)
	{
		$result = $string;
		foreach ($search_replace as $key => $value) {
			$result = str_replace($key, $value, $result);
		}
		return $result;
	}
}

if (!function_exists('permission_list')) {
	function permission_list()
	{

		$permission_list = \App\Models\AccessControl::where("role_id", Auth::user()->role_id)
			->pluck('permission')->toArray();
		return $permission_list;
	}
}

if (!function_exists('get_country_list')) {
	function get_country_list($old_data = '')
	{
		if ($old_data == '') {
			echo file_get_contents(app_path() . '/Helpers/country.txt');
		} else {
			$pattern = '<option value="' . $old_data . '">';
			$replace = '<option value="' . $old_data . '" selected="selected">';
			$country_list = file_get_contents(app_path() . '/Helpers/country.txt');
			$country_list = str_replace($pattern, $replace, $country_list);
			echo $country_list;
		}
	}
}

if (!function_exists('status')) {
	function status($status)
	{
		if ($status == 0) {
			return "<span class='text-danger'>" . _lang('Deactivated') . "</span>";
		} else if ($status == 1) {
			return "<span class='text-success'>" . _lang('Active') . "</span>";
		}
	}
}

if (!function_exists('transaction_status')) {
	function transaction_status($status)
	{
		if ($status == 0) {
			return "<span class='badge badge-warning'>" . _lang('Pending') . "</span>";
		} else if ($status == 1) {
			return "<span class='badge badge-danger'>" . _lang('Cancelled') . "</span>";
		} else if ($status == 2) {
			return "<span class='badge badge-success'>" . _lang('Completed') . "</span>";
		}
	}
}

if (!function_exists('leave_status')) {
	function leave_status($status)
	{
		if ($status == 0) {
			return "<span class='text-primary'>" . _lang('Pending') . "</span>";
		} else if ($status == 1) {
			return "<span class='text-secondary'>" . _lang('Approved') . "</span>";
		} else if ($status == 2) {
			return "<span class='text-danger'>" . _lang('Cancelled') . "</span>";
		}
	}
}

if (!function_exists('payroll_status')) {
	function payroll_status($status)
	{
		if ($status == 0) {
			return "<span class='text-gray-400'>" . _lang('Draft') . "</span>";
		} else if ($status == 1) {
			return "<span class='text-secondary'>" . _lang('Approved') . "</span>";
		} else if ($status == 2) {
			return "<span class='text-danger'>" . _lang('Accrued') . "</span>";
		} else if ($status == 3) {
			return "<span class='text-success'>" . _lang('Paid') . "</span>";
		}
	}
}

if (!function_exists('invoice_status')) {
	function invoice_status($invoice)
	{
		if ($invoice->status == 0) {
			return "<span class='text-secondary'>" . _lang('Draft') . "</span>";
		} else if ($invoice->status == 1 && $invoice->getRawOriginal('due_date') < date('Y-m-d')) {
			return "<span class='text-danger'>" . _lang('Overdue') . "</span>";
		} else if ($invoice->status == 1 && $invoice->getRawOriginal('due_date') >= date('Y-m-d')) {
			return "<span class='text-primary'>" . _lang('Active') . "</span>";
		} else if ($invoice->status == 2) {
			return "<span class='text-success'>" . _lang('Paid') . "</span>";
		} else if ($invoice->status == 3) {
			return "<span class='text-info'>" . _lang('Partial Paid') . "</span>";
		}
	}
}

if (!function_exists('policy_status')) {
	function policy_status($invoice)
	{
		if ($invoice->getRawOriginal('deffered_end') < date('Y-m-d')) {
			return "<span class='text-danger'>" . _lang('Expired') . "</span>";
		} else {
			return "<span class='text-success'>" . _lang('Active') . "</span>";
		}
	}
}

if (!function_exists('recurring_invoice_status')) {
	function recurring_invoice_status($status)
	{
		if ($status == 0) {
			return "<span class='badge badge-secondary'>" . _lang('Draft') . "</span>";
		} else if ($status == 1) {
			return "<span class='badge badge-primary'>" . _lang('Active') . "</span>";
		} else if ($status == 2) {
			return "<span class='badge badge-danger'>" . _lang('Ended') . "</span>";
		}
	}
}

if (!function_exists('quotation_status')) {
	function quotation_status($quotation)
	{
		if ($quotation->getRawOriginal('expired_date') < date('Y-m-d')) {
			return "<span class='text-danger'>" . _lang('Expired') . "</span>";
		} else {
			return "<span class='text-success'>" . _lang('Active') . "</span>";
		}
	}
}

if (!function_exists('purchase_status')) {
	function purchase_status($invoice)
	{
		if ($invoice->status == 0 && $invoice->getRawOriginal('due_date') < date('Y-m-d')) {
			return "<span class='text-danger'>" . _lang('Overdue') . "</span>";
		}
		if ($invoice->status == 0 && $invoice->getRawOriginal('due_date') >= date('Y-m-d')) {
			return "<span class='text-secondary'>" . _lang('Unpaid') . "</span>";
		} else if ($invoice->status == 1) {
			return "<span class='text-info'>" . _lang('Partial Paid') . "</span>";
		} else if ($invoice->status == 2) {
			return "<span class='text-success'>" . _lang('Paid') . "</span>";
		}
	}
}

if (!function_exists('show_status')) {
	function show_status($value, $status)
	{
		return "<span class='text-$status'>" . $value . "</span>";
	}
}

if (!function_exists('user_status')) {
	function user_status($status)
	{
		if ($status == 1) {
			return "<span class='text-success'>" . _lang('Active') . "</span>";
		} else if ($status == 0) {
			return "<span class='text-danger'>" . _lang('In Active') . "</span>";
		}
	}
}

if (!function_exists('get_account_details')) {
	function get_account_details()
	{
		$business_id = request()->activeBusiness->id;

		$result = DB::select("SELECT acc.*,(SELECT IFNULL(SUM(base_currency_amount), 0)
            FROM transactions WHERE dr_cr = 'cr' AND account_id = acc.id) - (SELECT IFNULL(SUM(base_currency_amount), 0)
            FROM transactions WHERE dr_cr = 'dr' AND account_id = acc.id) as balance
            FROM accounts as acc WHERE acc.business_id = '$business_id'");

		return $result;
	}
}

if (!function_exists('get_account')) {
	function get_account($name)
	{
		$result = Account::where('account_name', $name)->first();

		return $result;
	}
}

if (!function_exists('get_account_by_type')) {
	function get_account_by_type($type)
	{
		$result = Account::where('account_type', $type)->first();

		return $result;
	}
}

if (!function_exists('get_account_balance')) {
	function get_account_balance($accountId)
	{
		$business_id = request()->activeBusiness->id;

		$account = Account::where('id', $accountId)->first();

		if ($account->dr_cr == 'dr') {
			if ($account->currency == NULL) {
				$result = DB::select("SELECT (SELECT IFNULL( SUM(base_currency_amount), 0)
            FROM transactions WHERE dr_cr = 'dr' AND account_id = $accountId AND transactions.business_id = $business_id) - (SELECT IFNULL( SUM(base_currency_amount), 0)
            FROM transactions WHERE dr_cr = 'cr' AND account_id = $accountId AND transactions.business_id = $business_id) as balance");

				return $result[0]->balance ?? 0;
			} else {
				$result = DB::select("SELECT (SELECT IFNULL( SUM(transaction_amount), 0)
            FROM transactions WHERE dr_cr = 'dr' AND account_id = $accountId AND transactions.business_id = $business_id) - (SELECT IFNULL( SUM(transaction_amount), 0)
            FROM transactions WHERE dr_cr = 'cr' AND account_id = $accountId AND transactions.business_id = $business_id) as balance");

				return $result[0]->balance ?? 0;
			}
		} else {
			if ($account->currency == NULL) {
				$result = DB::select("SELECT (SELECT IFNULL( SUM(base_currency_amount), 0)
            FROM transactions WHERE dr_cr = 'cr' AND account_id = $accountId AND transactions.business_id = $business_id) - (SELECT IFNULL( SUM(base_currency_amount), 0)
            FROM transactions WHERE dr_cr = 'dr' AND account_id = $accountId AND transactions.business_id = $business_id) as balance");

				return $result[0]->balance ?? 0;
			} else {
				$result = DB::select("SELECT (SELECT IFNULL( SUM(transaction_amount), 0)
            FROM transactions WHERE dr_cr = 'cr' AND account_id = $accountId AND transactions.business_id = $business_id) - (SELECT IFNULL( SUM(transaction_amount), 0)
            FROM transactions WHERE dr_cr = 'dr' AND account_id = $accountId AND transactions.business_id = $business_id) as balance");

				return $result[0]->balance ?? 0;
			}
		}
	}
}

if (!function_exists('is_decimal')) {
	function is_decimal($n)
	{
		return is_numeric($n) && floor($n) != $n;
	}
}

if (!function_exists('file_icon')) {
	function file_icon($mime_type)
	{
		static $font_awesome_file_icon_classes = [
			// Images
			'image' => 'fa-file-image',
			// Audio
			'audio' => 'fa-file-audio',
			// Video
			'video' => 'fa-file-video',
			// Documents
			'application/pdf' => 'fa-file-pdf',
			'application/msword' => 'fa-file-word',
			'application/vnd.ms-word' => 'fa-file-word',
			'application/vnd.oasis.opendocument.text' => 'fa-file-word',
			'application/vnd.openxmlformats-officedocument.wordprocessingml' => 'fa-file-word',
			'application/vnd.ms-excel' => 'fa-file-excel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml' => 'fa-file-excel',
			'application/vnd.oasis.opendocument.spreadsheet' => 'fa-file-excel',
			'application/vnd.ms-powerpoint' => 'fa-file-powerpoint',
			'application/vnd.openxmlformats-officedocument.presentationml' => 'ffa-file-powerpoint',
			'application/vnd.oasis.opendocument.presentation' => 'fa-file-powerpoint',
			'text/plain' => 'fa-file-alt',
			'text/html' => 'fa-file-code',
			'application/json' => 'fa-file-code',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fa-file-excel',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'fa-file-powerpoint',
			// Archives
			'application/gzip' => 'fa-file-archive',
			'application/zip' => 'fa-file-archive',
			'application/x-zip-compressed' => 'fa-file-archive',
			// Misc
			'application/octet-stream' => 'fa-file-archive',
		];

		if (isset($font_awesome_file_icon_classes[$mime_type])) {
			return $font_awesome_file_icon_classes[$mime_type];
		}

		$mime_group = explode('/', $mime_type, 2)[0];
		return (isset($font_awesome_file_icon_classes[$mime_group])) ? $font_awesome_file_icon_classes[$mime_group] : 'fa-file';
	}
}

if (!function_exists('update_currency_exchange_rate')) {
	function update_currency_exchange_rate($force = false)
	{
		@ini_set('max_execution_time', 0);
		@set_time_limit(0);

		date_default_timezone_set(get_option('timezone', 'Asia/Dhaka'));

		$start = new \Carbon\Carbon(get_option('currency_update_time', date("Y-m-d H:i:s", strtotime('-24 hours', time()))));
		$end = \Carbon\Carbon::now();

		$last_run = $start->diffInHours($end);

		if ($last_run >= 12 || $force == true) {

			if (get_option('currency_converter') == 'fixer') {
				// Set API Endpoint and API key
				$endpoint = 'latest';
				$access_key = get_option('fixer_api_key');

				// Initialize CURL:
				$ch = curl_init('http://data.fixer.io/api/' . $endpoint . '?access_key=' . $access_key . '');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				// Store the data:
				$response = curl_exec($ch);
				curl_close($ch);

				// Decode JSON response:
				$response = json_decode($response, true);

				if ($response['success'] == false) {
					return false;
				}
			} else if (get_option('currency_converter') == 'apilayer') {

				$access_key = get_option('apilayer_api_key');
				$curl = curl_init();

				curl_setopt_array($curl, array(
					CURLOPT_URL => "https://api.apilayer.com/exchangerates_data/latest?base=USD",
					CURLOPT_HTTPHEADER => array(
						"Content-Type: text/plain",
						"apikey: $access_key",
					),
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 0,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "GET",
				));

				$response = curl_exec($curl);

				curl_close($curl);

				$response = json_decode($response, true);
				if ($response['success'] == false) {
					return false;
				}
			} else {
				//Manual Currency Rate
				return false;
			}

			$currency_list = \App\Models\Currency::all();
			$system_base_currency = get_base_currency();
			//$api_base_currency =  $response['base'];

			DB::beginTransaction();

			foreach ($response['rates'] as $currency => $rate) {
				$existingCurrency = $currency_list->firstWhere('name', $currency);
				if ($existingCurrency) {
					$existingCurrency->exchange_rate = $response['rates'][$currency] / $response['rates'][$system_base_currency];
					$existingCurrency->save();
				} else {
					$new_currency[] = array(
						"name" => $currency,
						"exchange_rate" => $response['rates'][$currency] / $response['rates'][$system_base_currency],
						"base_currency" => $system_base_currency == $currency ? 1 : 0,
						"status" => 1,
						"created_at" => date('Y-m-d H:i:s'),
						"updated_at" => date('Y-m-d H:i:s'),
					);
				}
			}

			if (isset($new_currency) && count($new_currency) > 0) {
				\App\Models\Currency::insert($new_currency);
			}

			//Store Last Update time
			update_option("currency_update_time", \Carbon\Carbon::now());

			DB::commit();
		}
	}
}

if (!function_exists('business_id')) {
	function business_id()
	{
		if (isset(request()->activeBusiness->id)) {
			return request()->activeBusiness->id;
		}
		return null;
	}
}

if (!function_exists('get_country_codes')) {
	function get_country_codes()
	{
		return json_decode(file_get_contents(app_path() . '/Helpers/country.json'), true);
	}
}

if (!function_exists('xss_clean')) {
	function xss_clean($data)
	{
		// Fix &entity\n;
		$data = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $data);
		$data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
		$data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
		$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

		// Remove any attribute starting with "on" or xmlns
		$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

		// Remove javascript: and vbscript: protocols
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

		// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

		// Remove namespaced elements (we do not need them)
		$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

		do {
			// Remove really unwanted tags
			$old_data = $data;
			$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
		} while ($old_data !== $data);

		// we are done...
		return $data;
	}
}

// convert seconds into time
if (!function_exists('time_from_seconds')) {
	function time_from_seconds($seconds)
	{
		$h = floor($seconds / 3600);
		$m = floor(($seconds % 3600) / 60);
		$s = $seconds - ($h * 3600) - ($m * 60);
		return sprintf('%02d:%02d:%02d', $h, $m, $s);
	}
}

/* Intelligent Functions */
if (!function_exists('get_language')) {
	function get_language($force = false)
	{

		if (isset(request()->model_language)) {
			return request()->model_language;
		}

		$language = $force == false ? session('language') : '';

		if ($language == '') {
			if (isset(request()->activeBusiness)) {
				$language = get_business_option('language', Cache::get('language'));
			} else {
				$language = Cache::get('language');
			}
		}

		if ($language == '') {
			$language = get_option('language');
			if ($language == '') {
				\Cache::put('language', 'language');
			} else {
				\Cache::put('language', $language);
			}
		}
		return $language;
	}
}

//** Currency Related Functions **//
if (!function_exists('get_currency_list')) {
	function get_currency_list()
	{
		return view('backend.user.currency.currency_list');
		// $currency_list = file_get_contents(app_path() . '/Helpers/currency.txt');

		// if ($old_data == "") {
		// 	echo $currency_list;
		// } else {
		// 	if ($serialize == true) {
		// 		$old_data = unserialize($old_data);
		// 		for ($i = 0; $i < count($old_data); $i++) {
		// 			$pattern = '<option value="' . $old_data[$i] . '">';
		// 			$replace = '<option value="' . $old_data[$i] . '" selected="selected">';
		// 			$currency_list = str_replace($pattern, $replace, $currency_list);
		// 		}
		// 		echo $currency_list;
		// 	} else {
		// 		$pattern = '<option value="' . $old_data . '">';
		// 		$replace = '<option value="' . $old_data . '" selected="selected">';
		// 		$currency_list = str_replace($pattern, $replace, $currency_list);
		// 		echo $currency_list;
		// 	}
		// }
	}
}

if (!function_exists('get_account_types_list')) {
	function get_account_types_list($old_data = '', $serialize = false)
	{
		$account_types_list = file_get_contents(app_path() . '/Helpers/account_types.txt');

		if ($old_data == "") {
			echo $account_types_list;
		} else {
			if ($serialize == true) {
				$old_data = unserialize($old_data);
				for ($i = 0; $i < count($old_data); $i++) {
					$pattern = '<option value="' . $old_data[$i] . '">';
					$replace = '<option value="' . $old_data[$i] . '" selected="selected">';
					$account_types_list = str_replace($pattern, $replace, $account_types_list);
				}
				echo $account_types_list;
			} else {
				$pattern = '<option value="' . $old_data . '">';
				$replace = '<option value="' . $old_data . '" selected="selected">';
				$account_types_list = str_replace($pattern, $replace, $account_types_list);
				echo $account_types_list;
			}
		}
	}
}

if (!function_exists('get_fiscal_year_list')) {
	function get_fiscal_year_list($old_data = '', $serialize = false)
	{
		$fiscal_year_list = file_get_contents(app_path() . '/Helpers/fiscal_year.txt');

		if ($old_data == "") {
			echo $fiscal_year_list;
		} else {
			if ($serialize == true) {
				$old_data = unserialize($old_data);
				for ($i = 0; $i < count($old_data); $i++) {
					$pattern = '<option value="' . $old_data[$i] . '">';
					$replace = '<option value="' . $old_data[$i] . '" selected="selected">';
					$fiscal_year_list = str_replace($pattern, $replace, $fiscal_year_list);
				}
				echo $fiscal_year_list;
			} else {
				$pattern = '<option value="' . $old_data . '">';
				$replace = '<option value="' . $old_data . '" selected="selected">';
				$fiscal_year_list = str_replace($pattern, $replace, $fiscal_year_list);
				echo $fiscal_year_list;
			}
		}
	}
}

if (!function_exists('decimalPlace')) {
	function decimalPlace($number, $symbol = '')
	{

		if ($symbol == '') {
			return money_format_2($number);
		}

		if (get_currency_position() == 'right') {
			return money_format_2($number) . ' ' . get_currency_symbol($symbol);
		} else {
			return get_currency_symbol($symbol) . ' ' . money_format_2($number);
		}
	}
}

if (!function_exists('money_format_2')) {
	function money_format_2($floatcurr)
	{
		$decimal_place = get_option('decimal_places', 2);
		$decimal_sep = get_option('decimal_sep', '.');
		$thousand_sep = get_option('thousand_sep', ',');

		$decimal_sep = $decimal_sep == '' ? ' ' : $decimal_sep;
		$thousand_sep = $thousand_sep == '' ? ' ' : $thousand_sep;

		return number_format($floatcurr, $decimal_place, $decimal_sep, $thousand_sep);
	}
}

if (!function_exists('get_currency_position')) {
	function get_currency_position()
	{
		$currency_position = Cache::get('currency_position');

		if ($currency_position == '') {
			$currency_position = get_option('currency_position');
			\Cache::put('currency_position', $currency_position);
		}

		return $currency_position;
	}
}

if (!function_exists('get_currency_symbol')) {
	function get_currency_symbol($currency_code)
	{
		include app_path() . '/Helpers/currency_symbol.php';

		if (array_key_exists($currency_code, $currency_symbols)) {
			return $currency_symbols[$currency_code];
		}
		return $currency_code;
	}
}

if (!function_exists('currency_symbol')) {
	function currency_symbol($currency = '')
	{
		if ($currency == '') {
			$currency = get_option('currency', 'USD');
		}
		return html_entity_decode(get_currency_symbol($currency), ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('currency')) {
	function currency()
	{
		$currency = get_option('currency', 'USD');
		return $currency;
	}
}

if (!function_exists('convert_currency')) {
	function convert_currency($from_currency, $to_currency, $amount)
	{
		if ($from_currency == $to_currency || $amount == 0) {
			return (float) $amount;
		}
		$currency1 = \App\Models\Currency::where('name', $from_currency)->first()->exchange_rate;
		$currency2 = \App\Models\Currency::where('name', $to_currency)->first()->exchange_rate;

		$converted_output = ($amount / $currency1) * $currency2;
		return $converted_output;
	}
}

if (!function_exists('convert_currency_2')) {
	function convert_currency_2($currency_1_rate, $currency_2_rate, $amount)
	{
		$converted_output = ($amount / $currency_1_rate) * $currency_2_rate;
		return $converted_output;
	}
}

if (!function_exists('get_base_currency')) {
	function get_base_currency()
	{
		$currency = \App\Models\Currency::where("base_currency", 1)->first();
		if (!$currency) {
			$currency = \App\Models\Currency::all()->first();
		}
		return $currency->name;
	}
}

/** Currency function for business **/
if (!function_exists('formatAmount')) {
	function formatAmount($number, $symbol = '', $businessId = '')
	{

		if ($businessId == '') {
			$businessId = request()->activeBusiness->id;
		}

		if ($symbol == '') {
			return business_money_format($number, $businessId);
		}

		if (get_biz_currency_position($businessId) == 'right') {
			return business_money_format($number, $businessId) . ' ' . get_currency_symbol($symbol);
		} else {
			return get_currency_symbol($symbol) . ' ' . business_money_format($number, $businessId);
		}
	}
}

if (!function_exists('business_money_format')) {
	function business_money_format($number, $businessId = '')
	{
		$decimal_place = get_business_option('decimal_places', 2, $businessId);
		$decimal_sep = get_business_option('decimal_sep', '.', $businessId);
		$thousand_sep = get_business_option('thousand_sep', ',', $businessId);

		$decimal_sep = $decimal_sep == '' ? ' ' : $decimal_sep;
		$thousand_sep = $thousand_sep == '' ? ' ' : $thousand_sep;

		return number_format($number, $decimal_place, $decimal_sep, $thousand_sep);
	}
}

if (!function_exists('get_biz_currency_position')) {
	function get_biz_currency_position($businessId = '')
	{
		$currency_position = get_business_option('currency_position', 'left', $businessId);
		return $currency_position;
	}
}
//** End Currency Functions **//

if (!function_exists('package')) {
	function package($owner_id = '')
	{
		if (request()->activeBusiness) {
			$package = request()->activeBusiness->user->package;
		} else {
			$package = User::find($owner_id)->package;
		}
		return $package;
	}
}

if (!function_exists('has_limit')) {
	function has_limit($table, $packageColumn, $totalLimit = true, $filter = null)
	{
		$user = request()->activeBusiness->user;
		$package = request()->activeBusiness->user->package;
		$packageLimit = $package->{$packageColumn};

		if ($packageLimit == '-1') {
			return 999;
		}

		$filter = $filter == null ? "user_id = $user->id" : $filter;

		if ($totalLimit == true) {
			$query = DB::select("SELECT COUNT(id) as total FROM $table WHERE $filter");
		} else {
			$subscription_date = $user->getRawOriginal('subscription_date');
			$query = DB::select("SELECT COUNT(id) as total FROM $table WHERE date(created_at) >= '$subscription_date' AND $filter");
		}

		return $packageLimit - $query[0]->total;
	}
}

if (!function_exists('update_membership_date')) {
	function update_membership_date($package, $subscription_date)
	{
		if ($package->package_type == 'monthly') {
			$newDate = date('Y-m-d', strtotime($subscription_date . ' + 1 months'));
		} else if ($package->package_type == 'yearly') {
			$newDate = date('Y-m-d', strtotime($subscription_date . ' + 1 years'));
		} else if ($package->package_type == 'lifetime') {
			$newDate = date('Y-m-d', strtotime($subscription_date . ' + 25 years'));
		}
		return $newDate;
	}
}

if (!function_exists('get_date_format')) {
	function get_date_format()
	{
		if (isset(request()->activeBusiness->id)) {
			$date_format = get_business_option('date_format', 'Y-m-d');
			return $date_format;
		}

		$date_format = Cache::get('date_format');

		if ($date_format == '') {
			$date_format = get_option('date_format', 'Y-m-d');
			\Cache::put('date_format', $date_format);
		}

		return $date_format;
	}
}

if (!function_exists('get_time_format')) {
	function get_time_format()
	{
		if (isset(request()->activeBusiness->id)) {
			$time_format = get_business_option('time_format', 'H:i');
			$time_format = $time_format == 24 ? 'H:i' : 'h:i A';
			return $time_format;
		}

		$time_format = Cache::get('time_format');

		if ($time_format == '') {
			$time_format = get_option('time_format', 'H:i');
			\Cache::put('time_format', $time_format);
		}

		$time_format = $time_format == 24 ? 'H:i' : 'h:i A';

		return $time_format;
	}
}

if (!function_exists('processShortCode')) {
	function processShortCode($body, $replaceData = [])
	{
		$message = $body;
		foreach ($replaceData as $key => $value) {
			$message = str_replace('{{' . $key . '}}', $value, $message);
		}
		return $message;
	}
}

if (!function_exists('get_page')) {
	function get_page_title($slug)
	{
		$defaultPages = ['home', 'about', 'features', 'pricing', 'blogs', 'faq', 'contact'];
		if (in_array($slug, $defaultPages)) {
			$string = ucwords($slug);
			return _dlang($string);
		}
		$page = Page::where('slug', $slug)->first();
		return $page ? $page->translation->title : ucwords($slug);
	}
}

/* Create Option Field */
if (!function_exists('create_option_field')) {
	function create_option_field($option_fields)
	{
		if ($option_fields != null) {
			$form = '<form action="" method="post">';
			foreach ($option_fields as $name => $val) {

				$column = 'col-md-12';
				if (isset($val['column'])) {
					$column = $val['column'];
				}

				$required = '';
				if ($val['required'] == true) {
					$required = 'required';
				}

				if ($val['type'] == 'text') {
					$form .= '<div class="' . $column . '"><div class="form-group">';
					$form .= '<label>' . $val['label'] . '</label>';
					$form .= '<input type="text" class="form-control ' . $name . '" name="' . $name . '" value="' . $val['value'] . '" data-change-class="' . $val['change']['class'] . '" data-change-action="' . $val['change']['action'] . '" ' . $required . '>';
					$form .= '</div></div>';
				} else if ($val['type'] == 'textarea') {
					$form .= '<div class="' . $column . '"><div class="form-group">';
					$form .= '<label>' . $val['label'] . '</label>';
					$form .= '<textarea class="form-control ' . $name . '" name="' . $name . '" data-change-class="' . $val['change']['class'] . '" data-change-action="' . $val['change']['action'] . '" ' . $required . '>' . $val['value'] . '</textarea>';
					$form .= '</div></div>';
				} else if ($val['type'] == 'html') {
					$form .= '<div class="' . $column . '"><div class="form-group">';
					$form .= '<label>' . $val['label'] . '</label>';
					$form .= '<textarea class="form-control ' . $name . '" name="' . $name . '" data-change-class="' . $val['change']['class'] . '" data-change-action="' . $val['change']['action'] . '" rows="8" ' . $required . '>' . $val['value'] . '</textarea>';
					$form .= '</div></div>';
				} else if ($val['type'] == 'select') {
					$form .= '<div class="' . $column . '"><div class="form-group">';
					$form .= '<label>' . $val['label'] . '</label>';
					$form .= '<select class="form-control ' . $name . '" name="' . $name . '" data-change-class="' . $val['change']['class'] . '" data-change-action="' . $val['change']['action'] . '" ' . $required . '>';
					foreach ($val['options'] as $option => $display) {
						$selectedOption = $val['value'] == $option ? 'selected' : '';
						$form .= '<option value="' . $option . '"' . $selectedOption . '>' . $display . '</option>';
					}
					$form .= '</select>';
					$form .= '</div></div>';
				}
			}
			$form .= '<div class="col-md-12 mt-2"><button type="submit" class="btn btn-primary btn-block"><i class="ti-check-box mr-1"></i>' . _lang('Save Setting') . '</button></div></form>';
			$script = '</script>';

			return $form;
		} else {
			$form = '<form action="" method="post"><div class="col-12"><h5 class="text-center">' . _lang('No option available') . '</h5></div></form>';
			return $form;
		}
	}
}
