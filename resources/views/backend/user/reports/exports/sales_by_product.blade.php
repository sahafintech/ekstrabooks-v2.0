<table>
    <thead>
        <tr>
            <th>{{ _lang('Code') }}</th>
            <th>{{ _lang('Product Name') }}</th>
            <th>{{ _lang('Unit Price') }}</th>
            <th>{{ _lang('Average Price') }}</th>
            <th>{{ _lang('Quantity Sold') }}</th>
            <th>{{ _lang('Total Sales') }}</th>
            <th>{{ _lang('Unit Cost') }}</th>
            <th>{{ _lang('Gross Profit') }}</th>
            <th>{{ _lang('Profit Margin (%)') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $category)
        <tr style="background-color: lightgray;">
            <td colspan="2">
                <p style="font-weight: bold;">{{ $category['category_name'] }}</p>
            </td>
            <td>
                <p style="font-weight: bold;">
                    {{ formatAmount($category['total_sold'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
                </p>
            </td>
            <td>
                <p style="font-weight: bold;">
                    {{ formatAmount($category['average_price'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
                </p>
            </td>
            <td>
                <p style="font-weight: bold;">
                    {{ $category['total_sold'] }}
                </p>
            </td>
            <td>
                <p style="font-weight: bold;">
                    {{ formatAmount($category['total_sales'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
                </p>
            </td>
            <td>
                <p style="font-weight: bold;">
                    {{ formatAmount($category['average_price'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
                </p>
            </td>
            <td>
                <p style="font-weight: bold;">
                    {{ formatAmount($category['gross_profit'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
                </p>
            </td>
            <td>
                <p style="font-weight: bold;">
                    {{ number_format($category['profit_margin'], 2) }}%
                </p>
            </td>
        </tr>

        @foreach($category['brands'] as $brand)
        <tr class="bg-gray-100">
            <td></td>
            <td>
                <p class="font-bold text-md">{{ $brand['brand_name'] }}</p>
            </td>
            <td>
                <p class="font-bold text-md">
                    {{ formatAmount($brand['total_sold'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
                </p>
            </td>
            <td>
                <p class="font-bold text-md">
                    {{ formatAmount($brand['average_price'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
                </p>
            </td>
            <td>
                <p class="font-bold text-md">
                    {{ $brand['total_sold'] }}
                </p>
            </td>
            <td>
                <p class="font-bold text-md">
                    {{ formatAmount($brand['total_sales'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
                </p>
            </td>
            <td>
                <p class="font-bold text-md">
                    {{ formatAmount($brand['average_price'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
                </p>
            </td>
            <td>
                <p class="font-bold text-md">
                    {{ formatAmount($brand['gross_profit'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
                </p>
            </td>
            <td>
                <p class="font-bold text-md">
                    {{ number_format($brand['profit_margin'], 2) }}%
                </p>
            </td>
        </tr>

        @foreach($brand['products'] as $product)
        <tr>
            <td>{{ $product['code'] }}</td>
            <td>{{ $product['name'] }}</td>
            <td>
                {{ formatAmount($product['selling_price'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
            </td>
            <td>
                {{ formatAmount($product['average_price'] ?? $product['selling_price'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
            </td>
            <td>{{ $product['total_sold'] }}</td>
            <td>
                {{ formatAmount($product['total_sales'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
            </td>
            <td>
                {{ formatAmount($product['purchase_cost'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
            </td>
            <td>
                {{ formatAmount($product['gross_profit'], currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
            </td>
            <td>
                {{ number_format($product['profit_margin'], 2) }}%
            </td>
        </tr>
        @endforeach
        @endforeach
        @empty
        <tr>
            <td colspan="9" style="text-align: center;">{{ _lang('No products available.') }}</td>
        </tr>
        @endforelse
    </tbody>
</table>