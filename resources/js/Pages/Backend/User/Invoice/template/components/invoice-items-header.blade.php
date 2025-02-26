<tr>
    @if(isset($invoiceColumns->name->status))
    @if($invoiceColumns->name->status != '0')
    <th>{{ isset($invoiceColumns->name->label) ? $invoiceColumns->name->label : _lang('Name') }}</th>
    @endif
    @else
    <th>{{ _lang('Name') }}</th>
    @endif

    @if(isset($invoiceColumns->quantity->status))
    @if($invoiceColumns->quantity->status != '0')
    <th class="text-center">{{ isset($invoiceColumns->quantity->label) ? $invoiceColumns->quantity->label : _lang('Quantity') }}</th>
    @endif
    @else
    <th class="text-center">{{ _lang('Quantity') }}</th>
    @endif

    @if(isset($invoiceColumns->price->status))
    @if($invoiceColumns->price->status != '0')
    <th class="text-right">{{ isset($invoiceColumns->price->label) ? $invoiceColumns->price->label : _lang('Price') }}</th>
    @endif
    @else
    <th class="text-right">{{ _lang('Price') }}</th>
    @endif

    @if(isset($invoiceColumns->amount->status))
    @if($invoiceColumns->amount->status != '0')
    <th class="text-right">{{ isset($invoiceColumns->amount->label) ? $invoiceColumns->amount->label : _lang('Amount') }}</th>
    @endif
    @else
    <th class="text-right">{{ _lang('Amount') }}</th>
    @endif
</tr>