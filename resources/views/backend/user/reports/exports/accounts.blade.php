<table>
    <tr>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ request()->activeBusiness->name }}</b>
        </td>
    <tr>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>Chart Of Accounts</b>
        </td>
    </tr>
    <tr>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('account_code') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('account_name') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('account_type') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('description') }}</td>
    </tr>
    <tbody>
        @if(isset($accounts))
        @foreach($accounts as $account)
        <tr>
            <td>{{ $account->account_code }}</td>
            <td>{{ $account->account_name }}</td>
            <td>{{ $account->account_type }}</td>
            <td>{{ $account->description }}</td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>