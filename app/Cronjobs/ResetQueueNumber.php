<?php

namespace App\Cronjobs;

use App\Models\BusinessSetting;

class ResetQueueNumber
{
    public function __invoke()
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        BusinessSetting::where('name', 'queue_number')->update(['value' => 1]);
    }
}
