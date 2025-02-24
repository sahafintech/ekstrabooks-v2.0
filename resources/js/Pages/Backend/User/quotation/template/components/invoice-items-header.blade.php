<tr>
    @if(isset($quotationColumns->name->status))
    @if($quotationColumns->name->status != '0')
    <th>{{ isset($quotationColumns->name->label) ? $quotationColumns->name->label : _lang('Name') }}</th>
    @endif
    @else
    <th>{{ _lang('Name') }}</th>
    @endif

    @if(isset($quotationColumns->quantity->status))
    @if($quotationColumns->quantity->status != '0')
    <th class="text-center">{{ isset($quotationColumns->quantity->label) ? $quotationColumns->quantity->label : _lang('Quantity') }}</th>
    @endif
    @else
    <th class="text-center">{{ _lang('Quantity') }}</th>
    @endif

    @if(isset($quotationColumns->price->status))
    @if($quotationColumns->price->status != '0')
    <th class="text-right">{{ isset($quotationColumns->price->label) ? $quotationColumns->price->label : _lang('Price') }}</th>
    @endif
    @else
    <th class="text-right">{{ _lang('Price') }}</th>
    @endif

    @if(isset($quotationColumns->amount->status))
    @if($quotationColumns->amount->status != '0')
    <th class="text-right">{{ isset($quotationColumns->amount->label) ? $quotationColumns->amount->label : _lang('Amount') }}</th>
    @endif
    @else
    <th class="text-right">{{ _lang('Amount') }}</th>
    @endif
</tr>