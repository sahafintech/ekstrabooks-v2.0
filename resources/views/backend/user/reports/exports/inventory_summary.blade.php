<table>
    <thead>
        <tr>
            <th colspan="8" style="background-color: lightgray; font-size: 12px;">
                Inventory Summary Report by Category
            </th>
        </tr>
        <tr style="background-color: lightgray; font-size: 12px;">
            <th>Totals</th>
            <th>
                <p>{{ $products->flatMap(function($category) {
                        return $category['brands'];
                    })->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('initial_stock') }}</p>
            </th>
            <th>
                <p>{{ $products->flatMap(function($category) {
                        return $category['brands'];
                    })->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_in') }}</p>
            </th>
            <th>
                <p>{{ $products->flatMap(function($category) {
                        return $category['brands'];
                    })->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_sold') }}</p>
            </th>
            <th>
                <p>{{ $products->flatMap(function($category) {
                        return $category['brands'];
                    })->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_adjustment_added') }}</p>
            </th>
            <th>
                <p>{{ $products->flatMap(function($category) {
                        return $category['brands'];
                    })->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_adjustment_deducted') }}</p>
            </th>
            <th>
                <p>{{ $products->flatMap(function($category) {
                        return $category['brands'];
                    })->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('stock') }}</p>
            </th>
            <th>
                <p>{{ formatAmount($products->flatMap(function($category) {
                        return $category['brands'];
                    })->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_cost'), currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}</p>
            </th>
        </tr>
        <tr>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Category Name') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Opening Stock') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Stock In') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Stock Out') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Stock Adjustment Added') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Stock Adjustment Deducted') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Stock Balance') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Total Stock Cost') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $category)
        {{-- Category Row --}}
        <tr>
            <td>
                {{ $category['category_name'] }}
            </td>
            <td>
                {{ $category['brands']->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('initial_stock') }}
            </td>
            <td>
                {{ $category['brands']->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_in') }}
            </td>
            <td>
                {{ $category['brands']->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_sold') }}
            </td>
            <td>
                {{ $category['brands']->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_adjustment_added') }}
            </td>
            <td>
                {{ $category['brands']->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_adjustment_deducted') }}
            </td>
            <td>
                {{ $category['brands']->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('stock') }}
            </td>
            <td>
                {{ $category['brands']->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_cost') }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<tr></tr>
<tr></tr>

<table>
    <thead>
        <tr>
            <th colspan="8" style="background-color: lightgray; font-size: 12px;">
                Inventory Summary Report by Brand
            </th>
        </tr>
        <tr style="background-color: lightgray; font-size: 12px;">
            <th>Totals</th>
            <th>
                <p>{{ $products->flatMap(function($category) {
                        return $category['brands'];
                    })->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('initial_stock') }}</p>
            </th>
            <th>
                <p>{{ $products->flatMap(function($category) {
                        return $category['brands'];
                    })->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_in') }}</p>
            </th>
            <th>
                <p>{{ $products->flatMap(function($category) {
                        return $category['brands'];
                    })->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_sold') }}</p>
            </th>
            <th>
                <p>{{ $products->flatMap(function($category) {
                        return $category['brands'];
                    })->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_adjustment_added') }}</p>
            </th>
            <th>
                <p>{{ $products->flatMap(function($category) {
                        return $category['brands'];
                    })->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_adjustment_deducted') }}</p>
            </th>
            <th>
                <p>{{ $products->flatMap(function($category) {
                        return $category['brands'];
                    })->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('stock') }}</p>
            </th>
            <th>
                <p>{{ formatAmount($products->flatMap(function($category) {
                        return $category['brands'];
                    })->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_cost'), currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}</p>
            </th>
        </tr>
        <tr>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Brand Name') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Opening Stock') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Stock In') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Stock Out') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Stock Adjustment Added') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Stock Adjustment Deducted') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Stock Balance') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Total Stock Cost') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $category)
        {{-- Category Row --}}
        @foreach($category['brands'] as $brand)
        <tr>
            <td>
                {{ $brand['brand_name'] }}
            </td>
            <td>
                {{ $brand['products']->sum('initial_stock') }}
            </td>
            <td>
                {{ $brand['products']->sum('total_stock_in') }}
            </td>
            <td>
                {{ $brand['products']->sum('total_sold') }}
            </td>
            <td>
                {{ $brand['products']->sum('total_stock_adjustment_added') }}
            </td>
            <td>
                {{ $brand['products']->sum('total_stock_adjustment_deducted') }}
            </td>
            <td>
                {{ $brand['products']->sum('stock') }}
            </td>
            <td>

                {{ $brand['products']->sum('total_stock_cost') }}
            </td>
        </tr>
        @endforeach
        @endforeach
    </tbody>
</table>