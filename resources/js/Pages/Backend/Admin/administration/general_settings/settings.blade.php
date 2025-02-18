<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Adminstration" page="settings" subpage="update" />

		<div class="grid grid-cols-12 gap-x-6">
			<div class="col-span-12 xl:col-span-3">
				<div class="box">
					<div class="box-body pt-0">
						<nav class="flex flex-col space-y-2 mt-5" aria-label="Tabs" role="tablist" data-hs-tabs-vertical="true">
							<button type="button" class="hs-tab-active:bg-primary hs-tab-active:border-primary hs-tab-active:text-white dark:hs-tab-active:bg-primary dark:hs-tab-active:border-primary dark:hs-tab-active:text-white -mr-px py-3 px-3 inline-flex items-center gap-2 bg-gray-50 text-sm font-medium text-center border text-gray-500 rounded-sm dark:bg-black/20 dark:border-white/10 dark:text-white/70 active" id="settings-item-1" data-hs-tab="#settings-1" aria-controls="settings-1" role="tab">
								<i class="ri ri-shield-user-line"></i> General Settings
							</button>
							<button type="button" class="hs-tab-active:bg-primary hs-tab-active:border-primary hs-tab-active:text-white dark:hs-tab-active:bg-primary dark:hs-tab-active:border-primary dark:hs-tab-active:text-white -mr-px py-3 px-3 inline-flex items-center gap-2 bg-gray-50 text-sm font-medium text-center border text-gray-500 rounded-sm dark:bg-black/20 dark:border-white/10 dark:text-white/70 dark:hover:text-gray-300" id="settings-item-2" data-hs-tab="#settings-2" aria-controls="settings-2" role="tab">
								<i class="ri ri-global-line"></i> System Settings
							</button>
							<button type="button" class="hs-tab-active:bg-primary hs-tab-active:border-primary hs-tab-active:text-white dark:hs-tab-active:bg-primary dark:hs-tab-active:border-primary dark:hs-tab-active:text-white -mr-px py-3 px-3 inline-flex items-center gap-2 bg-gray-50 text-sm font-medium text-center border text-gray-500 rounded-sm dark:bg-black/20 dark:border-white/10 dark:text-white/70 dark:hover:text-gray-300" id="settings-item-3" data-hs-tab="#settings-3" aria-controls="settings-3" role="tab">
								<i class="ri-money-dollar-circle-line"></i> Currency Settings
							</button>
							<button type="button" class="hs-tab-active:bg-primary hs-tab-active:border-primary hs-tab-active:text-white dark:hs-tab-active:bg-primary dark:hs-tab-active:border-primary dark:hs-tab-active:text-white -mr-px py-3 px-3 inline-flex items-center gap-2 bg-gray-50 text-sm font-medium text-center border text-gray-500 rounded-sm dark:bg-black/20 dark:border-white/10 dark:text-white/70 dark:hover:text-gray-300" id="settings-item-4" data-hs-tab="#settings-4" aria-controls="settings-4" role="tab">
								<i class="ri-mail-send-line"></i> Email Settings
							</button>
							<button type="button" class="hs-tab-active:bg-primary hs-tab-active:border-primary hs-tab-active:text-white dark:hs-tab-active:bg-primary dark:hs-tab-active:border-primary dark:hs-tab-active:text-white -mr-px py-3 px-3 inline-flex items-center gap-2 bg-gray-50 text-sm font-medium text-center border text-gray-500 rounded-sm dark:bg-black/20 dark:border-white/10 dark:text-white/70 dark:hover:text-gray-300" id="settings-item-5" data-hs-tab="#settings-5" aria-controls="settings-5" role="tab">
								<i class="ri-lock-line"></i> GOOGLE RECAPTCHA V3
							</button>
							<button type="button" class="hs-tab-active:bg-primary hs-tab-active:border-primary hs-tab-active:text-white dark:hs-tab-active:bg-primary dark:hs-tab-active:border-primary dark:hs-tab-active:text-white -mr-px py-3 px-3 inline-flex items-center gap-2 bg-gray-50 text-sm font-medium text-center border text-gray-500 rounded-sm dark:bg-black/20 dark:border-white/10 dark:text-white/70 dark:hover:text-gray-300" id="settings-item-6" data-hs-tab="#settings-6" aria-controls="settings-6" role="tab">
								<i class="ri-time-line"></i> Cron Jobs
							</button>
							<button type="button" class="hs-tab-active:bg-primary hs-tab-active:border-primary hs-tab-active:text-white dark:hs-tab-active:bg-primary dark:hs-tab-active:border-primary dark:hs-tab-active:text-white -mr-px py-3 px-3 inline-flex items-center gap-2 bg-gray-50 text-sm font-medium text-center border text-gray-500 rounded-sm dark:bg-black/20 dark:border-white/10 dark:text-white/70 dark:hover:text-gray-300" id="settings-item-7" data-hs-tab="#settings-7" aria-controls="settings-7" role="tab">
								<i class="ri-contrast-drop-2-line"></i> Logo and Favicon
							</button>
							<button type="button" class="hs-tab-active:bg-primary hs-tab-active:border-primary hs-tab-active:text-white dark:hs-tab-active:bg-primary dark:hs-tab-active:border-primary dark:hs-tab-active:text-white -mr-px py-3 px-3 inline-flex items-center gap-2 bg-gray-50 text-sm font-medium text-center border text-gray-500 rounded-sm dark:bg-black/20 dark:border-white/10 dark:text-white/70 dark:hover:text-gray-300" id="settings-item-8" data-hs-tab="#settings-8" aria-controls="settings-8" role="tab">
								<i class="ri-server-fill"></i> Cache Control
							</button>
						</nav>
					</div>
				</div>
			</div>
			@php $settings = \App\Models\Setting::all(); @endphp
			<div class="col-span-12 xl:col-span-9">
				<div class="box">
					<div class="box-body p-0">
						<div id="settings-1" role="tabpanel" aria-labelledby="settings-item-1">
							<div class="box border-0 shadow-none mb-0">
								<div class="box-header">
									<h5 class="box-title leading-none flex"><i class="ri ri-shield-user-line ml-3"></i> {{ _lang('General Settings') }}</h5>
								</div>
								<div class="box-body">
									<form method="post" class="settings-submit params-panel" autocomplete="off" action="{{ route('settings.update_settings','store') }}" enctype="multipart/form-data">
										{{ csrf_field() }}
										<div class="grid grid-cols-12 gap-x-5">
											<div class="col-span-12 md:col-span-6">
												<x-input-label class="w-full" value="{{ _lang('Company Name') }}" />
												<x-text-input type="text" name="company_name" value="{{ get_setting($settings, 'company_name') }}" required />
											</div>

											<div class="col-span-12 md:col-span-6">
												<x-input-label class="w-full" value="{{ _lang('Site Title') }}" />
												<x-text-input type="text" name="site_title" value="{{ get_setting($settings, 'site_title') }}" required />
											</div>

											<div class="col-span-12 md:col-span-6 mt-3">
												<x-input-label class="w-full" value="{{ _lang('Phone') }}" />
												<x-text-input type="text" name="phone" value="{{ get_setting($settings, 'phone') }}" required />
											</div>

											<div class="col-span-12 md:col-span-6 mt-3">
												<x-input-label class="w-full" value="{{ _lang('Email') }}" />
												<x-text-input type="email" name="email" value="{{ get_setting($settings, 'email') }}" required />
											</div>

											<div class="col-span-12 md:col-span-6 mt-3">
												<x-input-label class="w-full" value="{{ _lang('Timezone') }}" />
												<select class="w-full selectize" name="timezone" required>
													<option value="">{{ _lang('-- Select One --') }}</option>
													{{ create_timezone_option(get_setting($settings, 'timezone')) }}
												</select>
											</div>

											<div class="col-span-12 md:col-span-6 mt-3">
												<x-input-label class="w-full" value="{{ _lang('Language') }}" />
												<select class="w-full selectize" name="language">
													<option value="">{{ _lang('-- Select One --') }}</option>
													{{ load_language( get_setting($settings, 'language') ) }}
												</select>
											</div>

											<div class="col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('Address') }}" />
												<textarea class="w-full" name="address">{{ get_setting($settings, 'address') }}</textarea>
											</div>

											<div class="col-span-12 mt-4">
												<x-primary-button type="submit" class="submit-btn">
													{{ _lang('Save Settings') }}
												</x-primary-button>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
						<div id="settings-2" class="hidden" role="tabpanel" aria-labelledby="settings-item-2">
							<div class="box border-0 shadow-none mb-0">
								<div class="box-header">
									<h5 class="box-title leading-none flex"><i class="ri ri-shield-user-line ltr:mr-2 rtl:ml-2"></i> {{ _lang('System Settings') }}</h5>
								</div>
								<div class="box-body">
									<form method="post" class="settings-submit params-panel" autocomplete="off" action="{{ route('settings.update_settings','store') }}" enctype="multipart/form-data">
										{{ csrf_field() }}
										<div class="grid grid-cols-12 gap-x-5">
											<div class="col-span-12">
												<x-input-label class="w-full" value="{{ _lang('Currency') }}" />
												<select class="w-full selectize" name="currency" required>
													<option value="">{{ _lang('Select One') }}</option>
													{{ get_currency_list(get_setting($settings, 'currency')) }}
												</select>
											</div>

											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('Backend Direction') }}" />
												<select class="w-full" name="backend_direction" required>
													<option value="ltr" {{ get_setting($settings, 'backend_direction') == 'ltr' ? 'selected' : '' }}>{{ _lang('LTR') }}</option>
													<option value="rtl" {{ get_setting($settings, 'backend_direction') == 'rtl' ? 'selected' : '' }}>{{ _lang('RTL') }}</option>
												</select>
											</div>

											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('Date Format') }}" />
												<select class="w-full" name="date_format" data-selected="{{ get_setting($settings, 'date_format','Y-m-d') }}" required>
													<option value="Y-m-d">{{ date('Y-m-d') }}</option>
													<option value="d-m-Y">{{ date('d-m-Y') }}</option>
													<option value="d/m/Y">{{ date('d/m/Y') }}</option>
													<option value="m-d-Y">{{ date('m-d-Y') }}</option>
													<option value="m.d.Y">{{ date('m.d.Y') }}</option>
													<option value="m/d/Y">{{ date('m/d/Y') }}</option>
													<option value="d.m.Y">{{ date('d.m.Y') }}</option>
													<option value="d/M/Y">{{ date('d/M/Y') }}</option>
													<option value="d/M/Y">{{ date('M/d/Y') }}</option>
													<option value="d M, Y">{{ date('d M, Y') }}</option>
												</select>
											</div>

											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('Time Format') }}" />
												<select class="w-full" name="time_format" data-selected="{{ get_setting($settings, 'time_format',24) }}" required>
													<option value="24">{{ _lang('24 Hours') }}</option>
													<option value="12">{{ _lang('12 Hours') }}</option>
												</select>
											</div>

											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label value="{{ _lang('Member Sign Up') }}" />
												<select class="w-full" name="member_signup" required>
													<option value="0" {{ get_setting($settings, 'member_signup') == '0' ? 'selected' : '' }}>{{ _lang('Disabled') }}</option>
													<option value="1" {{ get_setting($settings, 'member_signup') == '1' ? 'selected' : '' }}>{{ _lang('Enabled') }}</option>
												</select>
											</div>

											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('Email Verification') }}" />
												<select class="w-full" name="email_verification" required>
													<option value="0" {{ get_setting($settings, 'email_verification') == '0' ? 'selected' : '' }}>{{ _lang('Disabled') }}</option>
													<option value="1" {{ get_setting($settings, 'email_verification') == '1' ? 'selected' : '' }}>{{ _lang('Enabled') }}</option>
												</select>
											</div>

											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('Landing Page') }}" />
												<select class="w-full" name="website_enable" required>
													<option value="1" {{ get_setting($settings, 'website_enable') == '1' ? 'selected' : '' }}>{{ _lang('Enabled') }}</option>
													<option value="0" {{ get_setting($settings, 'website_enable') == '0' ? 'selected' : '' }}>{{ _lang('Disabled') }}</option>
												</select>
											</div>

											<div class="col-span-12 mt-4">
												<x-primary-button type="submit" class="submit-btn">{{ _lang('Save Settings') }}</x-primary-button>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
						<div id="settings-3" class="hidden" role="tabpanel" aria-labelledby="settings-item-3">
							<div class="box border-0 shadow-none mb-0">
								<div class="box-header">
									<h5 class="box-title leading-none flex"><i class="ri ri-shield-user-line ml-2"></i> {{ _lang('Currency Settings') }}</h5>
								</div>
								<div class="box-body">
									<form method="post" class="settings-submit params-panel" autocomplete="off" action="{{ route('settings.update_settings','store') }}" enctype="multipart/form-data">
										{{ csrf_field() }}
										<div class="grid grid-cols-12 gap-x-5">

											<div class="md:col-span-6 col-span-12">
												<x-input-label class="w-full" value="{{ _lang('Currency Converter') }}" />
												<select class="w-full" data-selected="{{ get_setting($settings, 'currency_converter', 'manual') }}" name="currency_converter" id="currency_converter" required>
													<option value="manual">{{ _lang('Manual') }}</option>
													<option value="apilayer">{{ _lang('ApiLayer') }}</option>
													<option value="fixer">{{ _lang('Fixer') }}</option>
												</select>
											</div>

											<div class="md:col-span-6 col-span-12 fixer {{ get_setting($settings, 'currency_converter') != 'fixer' ? 'hidden' : '' }}">
												<x-input-label class="float-left" value="{{ _lang('Fixer API Key') }}" />
												<a href="https://fixer.io/" target="_blank" class="text-blue-500 float-right">{{ _lang('GET API KEY') }}</a>
												<input type="text" class="w-full" name="fixer_api_key" value="{{ get_setting($settings, 'fixer_api_key') }}">
											</div>

											<div class="md:col-span-6 col-span-12 apilayer {{ get_setting($settings, 'currency_converter') != 'apilayer' ? 'hidden' : '' }}">
												<x-input-label class="float-left" value="{{ _lang('Apilayer API Key') }}" />
												<a href="https://apilayer.com/" target="_blank" class="text-blue-500 float-right">{{ _lang('GET API KEY') }}</a>
												<input type="text" class="w-full" name="apilayer_api_key" value="{{ get_setting($settings, 'apilayer_api_key') }}">
											</div>

											<div class="md:col-span-6 col-span-12">
												<x-input-label class="w-full" value="{{ _lang('Currency Position') }}" />
												<select class="w-full" data-selected="{{ get_setting($settings, 'currency_position','left') }}" name="currency_position" required>
													<option value="left">{{ _lang('Left') }}</option>
													<option value="right">{{ _lang('Right') }}</option>
												</select>
											</div>


											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('Thousand Seperator') }}" />
												<input type="text" class="w-full" name="thousand_sep" value="{{ get_setting($settings, 'thousand_sep',',') }}">
											</div>

											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('Decimal Seperator') }}" />
												<input type="text" class="w-full" name="decimal_sep" value="{{ get_setting($settings, 'decimal_sep','.') }}">
											</div>

											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('Decimal Seperator') }}" />
												<input type="text" class="w-full" name="decimal_places" value="{{ get_setting($settings, 'decimal_places',2) }}">
											</div>

											<div class="col-span-12 mt-4">
												<x-primary-button type="submit" class="submit-btn">{{ _lang('Save Settings') }}</x-primary-button>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
						<div id="settings-4" class="hidden" role="tabpanel" aria-labelledby="settings-item-4">
							<div class="box border-0 shadow-none mb-0">
								<div class="box-header">
									<h5 class="box-title leading-none flex"><i class="ri ri-shield-user-line ltr:mr-2 rtl:ml-2"></i> {{ _lang('Email Settings') }}</h5>
								</div>
								<div class="box-body">
									<form method="post" class="settings-submit params-panel" autocomplete="off" action="{{ route('settings.update_settings','store') }}" enctype="multipart/form-data">
										{{ csrf_field() }}
										<div class="grid grid-cols-12 gap-x-5">
											<div class="md:col-span-6 col-span-12">
												<x-input-label class="w-full" value="{{ _lang('Mail Type') }}" />
												<label class="control-label"></label>
												<select class="w-full" name="mail_type" id="mail_type" required>
													<option value="smtp" {{ get_setting($settings, 'mail_type')=="smtp" ? "selected" : "" }}>{{ _lang('SMTP') }}</option>
													<option value="sendmail" {{ get_setting($settings, 'mail_type')=="sendmail" ? "selected" : "" }}>{{ _lang('Sendmail') }}</option>
												</select>
											</div>

											<div class="md:col-span-6 col-span-12">
												<x-input-label class="w-full" value="{{ _lang('From Email') }}" />
												<x-text-input type="text" name="from_email" value="{{ get_setting($settings, 'from_email') }}" required />
											</div>

											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('From Name') }}" />
												<x-text-input type="text" name="from_name" value="{{ get_setting($settings, 'from_name') }}" required />
											</div>

											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('SMTP Host') }}" />
												<x-text-input type="text" name="smtp_host" value="{{ get_setting($settings, 'smtp_host') }}" />
											</div>

											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('SMTP Port') }}" />
												<x-text-input type="text" name="smtp_port" value="{{ get_setting($settings, 'smtp_port') }}" />
											</div>

											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('SMTP Username') }}" />
												<x-text-input type="text" name="smtp_username" value="{{ get_setting($settings, 'smtp_username') }}" autocomplete="off" />
											</div>

											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('SMTP Password') }}" />
												<x-text-input type="password" name="smtp_password" value="{{ get_setting($settings, 'smtp_password') }}" autocomplete="off" />
											</div>

											<div class="md:col-span-6 col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('SMTP Encryption') }}" />
												<select class="w-full" name="smtp_encryption">
													<option value="">{{ _lang('None') }}</option>
													<option value="ssl" {{ get_setting($settings, 'smtp_encryption')=="ssl" ? "selected" : "" }}>{{ _lang('SSL') }}</option>
													<option value="tls" {{ get_setting($settings, 'smtp_encryption')=="tls" ? "selected" : "" }}>{{ _lang('TLS') }}</option>
												</select>
											</div>

											<div class="col-span-12 mt-4">
												<x-primary-button type="submit" class="submit-btn">{{ _lang('Save Settings') }}</x-primary-button>
											</div>
										</div>
									</form>
								</div>
							</div>
							<div class="box">
								<div class="box-header">
									<h5>{{ _lang('Send Test Email') }}</h5>
								</div>

								<div class="box-body">
									<form action="{{ route('settings.send_test_email') }}" class="settings-submit params-panel" method="post">
										<div class="grid grid-cols-12 gap-x-5">
											@csrf
											<div class="md:col-span-12">
												<x-input-label class="w-full" value="{{ _lang('Email To') }}" />
												<x-text-input type="email" name="email_address" required />
											</div>

											<div class="md:col-span-12 mt-3">
												<x-input-label class="w-full" value="{{ _lang('Message') }}" />
												<textarea class="w-full" name="message" required></textarea>
											</div>

											<div class="col-span-12 mt-2">
												<x-primary-button type="submit" class="submit-btn"><i class="ri-send-plane-line"></i>{{ _lang('Send Test Email') }}</x-primary-button>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
						<div id="settings-5" class="hidden" role="tabpanel" aria-labelledby="settings-item-5">
							<div class="box border-0 shadow-none mb-0">
								<div class="box-header">
									<h5 class="box-title leading-none flex"><i class="ri ri-shield-user-line ltr:mr-2 rtl:ml-2"></i> {{ _lang('GOOGLE RECAPTCHA V3') }}</h5>
								</div>
								<div class="box-body">
									<form method="post" class="settings-submit params-panel" autocomplete="off" action="{{ route('settings.update_settings','store') }}">
										{{ csrf_field() }}
										<div>
											<div class="grid grid-cols-12 xl:items-center mt-3">
												<x-input-label class="xl:col-span-4 col-span-12" value="{{ _lang('Enable Recaptcha v3') }}" />
												<select class="w-full xl:col-span-8 col-span-12" data-selected="{{ get_setting($settings, 'enable_recaptcha', 0) }}" name="enable_recaptcha" required>
													<option value="0">{{ _lang('No') }}</option>
													<option value="1">{{ _lang('Yes') }}</option>
												</select>
											</div>

											<div class="grid grid-cols-12 mt-3">
												<x-input-label class="xl:col-span-4 col-span-12" value="{{ _lang('RECAPTCHA SITE KEY') }}" />
												<x-text-input class="xl:col-span-8 col-span-12" type="text" name="recaptcha_site_key" value="{{ get_setting($settings, 'recaptcha_site_key') }}" />
											</div>

											<div class="grid grid-cols-12 mt-3">
												<x-input-label class="xl:col-span-4 col-span-12" value="{{ _lang('RECAPTCHA SECRET KEY') }}" />
												<x-text-input class="xl:col-span-8 col-span-12" type="text" name="recaptcha_secret_key" value="{{ get_setting($settings, 'recaptcha_secret_key') }}" />
											</div>

											<div class="col-span-12 mt-4">
												<x-primary-button type="submit" class="submit-btn">{{ _lang('Save Settings') }}</x-primary-button>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
						<div id="settings-6" class="hidden" role="tabpanel" aria-labelledby="settings-item-6">
							<div class="box border-0 shadow-none mb-0">
								<div class="box-header">
									<h5 class="box-title leading-none flex"><i class="ri ri-shield-user-line ltr:mr-2 rtl:ml-2"></i> {{ _lang('Cron Jobs') }}</h5>
									<span>{{ get_option('cornjob_runs_at') != null ? _lang('Last Runs At').' ('.date(get_date_format().' '.get_time_format(), strtotime(get_option('cornjob_runs_at'))).' UTC)' : '' }}</span>
								</div>
								<div class="box-body">
									<div class="bg-yellow-100 border border-gray-200 alert">
										<div class="flex">
											<div class="flex-shrink-0">
												<i class="ri-information-line"></i>
											</div>
											<p class="text-sm text-gray-700">
												{{ _lang('Run Cronjobs at least every').' 5 '._lang('minutes') }}
											</p>
										</div>
									</div>
									<div class="grid grid-cols-12">
										<div class="md:col-span-12">
											<x-input-label value="{{ _lang('Schedule Task Command') }}" />
											<div class="border bg-gray-100 p-2 rounded-md">cd /<span class="text-danger">your-project-path</span> && php artisan schedule:run >> /dev/null 2>&1</div>
										</div>

										<div class="md:col-span-12 mt-3">
											<x-input-label value="{{ _lang('Cronjobs Command example 1 for cPanel') }}" />
											<div class="border bg-gray-100 p-2 rounded">{{ '/usr/local/bin/php ' . base_path() . '/artisan schedule:run >> /dev/null 2>&1' }}</div>
										</div>

										<div class="md:col-span-12 mt-3">
											<x-input-label value="{{ _lang('Cronjobs Command example 2 for cPanel') }}" />
											<div class="border bg-gray-100 p-2 rounded">{{ 'cd ' . base_path() .  ' && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1' }}</div>
										</div>

										<div class="md:col-span-12 mt-3">
											<x-input-label value="{{ _lang('Schedule Task Command example for Plesk') }}" />
											<div class="border bg-gray-100 p-2 rounded">{{ 'cd ' . base_path() .  ' && /opt/plesk/php/'. substr(phpversion(), 0, 3) .'/bin/php artisan schedule:run >> /dev/null 2>&1' }}</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div id="settings-7" class="hidden" role="tabpanel" aria-labelledby="settings-item-7">
							<div class="box border-0 shadow-none mb-0">
								<div class="box-header">
									<h5 class="box-title leading-none flex"><i class="ri ri-shield-user-line ltr:mr-2 rtl:ml-2"></i> {{ _lang('Logo and Favicon') }}</h5>
								</div>
								<div class="box-body">
									<div class="grid grid-cols-12 gap-x-5">
										<div class="md:col-span-6 col-span-12">
											<form method="post" class="settings-submit params-panel" autocomplete="off" action="{{ route('settings.uplaod_logo') }}" enctype="multipart/form-data">
												{{ csrf_field() }}
												<div class="grid grid-cols-12">
													<div class="col-span-12">
														<x-input-label value="{{ _lang('Upload Logo') }}" />
														<x-text-input type="file" class="dropify" name="logo" data-max-file-size="8M" data-allowed-file-extensions="png jpg jpeg PNG JPG JPEG" data-default-file="{{ get_logo() }}" required />
													</div>

													<br>

													<div class="col-span-12">
														<x-primary-button class="w-full" type="submit">{{ _lang('Upload') }}</x-primary-button>
													</div>
												</div>
											</form>
										</div>

										<div class="md:col-span-6 col-span-12">
											<form method="post" class="settings-submit params-panel" autocomplete="off" action="{{ route('settings.update_settings','store') }}" enctype="multipart/form-data">
												{{ csrf_field() }}
												<div class="grid grid-cols-12">
													<div class="col-span-12">
														<x-input-label value="{{ _lang('Upload Favicon') }} (PNG)" />
														<x-text-input type="file" class="dropify" name="favicon" data-max-file-size="2M" data-allowed-file-extensions="png" data-default-file="{{ get_favicon() }}" required />
													</div>

													<br>

													<div class="col-span-12">
														<x-primary-button class="w-full" type="submit">{{ _lang('Upload') }}</x-primary-button>
													</div>
												</div>
											</form>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div id="settings-8" class="hidden" role="tabpanel" aria-labelledby="settings-item-8">
							<div class="box border-0 shadow-none mb-0">
								<div class="box-header">
									<h5 class="box-title leading-none flex"><i class="ri ri-shield-user-line ltr:mr-2 rtl:ml-2"></i> {{ _lang('Cache Control') }}</h5>
								</div>
								<div class="box-body">
									<form method="post" class="params-panel" autocomplete="off" action="{{ route('settings.remove_cache') }}">
										{{ csrf_field() }}
										<div class="grid grid-cols-12">
											<div class="col-span-12">
												<input type="checkbox" class="custom-control-input" name="cache[view_cache]" value="view_cache" id="view_cache">
												<label class="custom-control-label" for="view_cache">{{ _lang('View Cache') }}</label>
											</div>

											<div class="col-span-12 mt-3">
												<input type="checkbox" class="custom-control-input" name="cache[application_cache]" value="application_cache" id="application_cache">
												<label class="custom-control-label" for="application_cache">{{ _lang('Application Cache') }}</label>
											</div>

											<div class="col-span-12 mt-4">
												<x-primary-button type="submit" class="submit-btn">{{ _lang('Remove Cache') }}</x-primary-button>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>

<script>
	$(document).on('change', '#currency_converter', function() {
		if ($(this).val() == 'fixer') {
			$('.fixer').removeClass('hidden');
			$('.apilayer').addClass('hidden');
		} else if ($(this).val() == 'apilayer') {
			$('.apilayer').removeClass('hidden');
			$('.fixer').addClass('hidden');
		} else {
			$('.fixer').addClass('hidden');
			$('.apilayer').addClass('hidden');
		}
	});
</script>