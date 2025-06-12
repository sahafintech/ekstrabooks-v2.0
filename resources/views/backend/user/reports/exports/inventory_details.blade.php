<table>
    <thead>
        <tr>
            <th></th>
            <th></th>
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
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('id') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Code') }}</th>
            <th style="background-color: lightgray; font-size: 12px;">{{ _lang('Product Name') }}</th>
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
            <td colspan="2" style="background-color: lightgray; font-size: 12px;">
                {{ $category['category_name'] }}
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                {{ $category['brands']->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('initial_stock') }}
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                {{ $category['brands']->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_in') }}
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                {{ $category['brands']->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_sold') }}
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                {{ $category['brands']->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_adjustment_added') }}
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                {{ $category['brands']->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_adjustment_deducted') }}
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                {{ $category['brands']->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('stock') }}
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                {{ $category['brands']->flatMap(function($brand) {
                        return $brand['products'];
                    })->sum('total_stock_cost') }}
            </td>
        </tr>

        {{-- Brands within Category --}}
        @foreach($category['brands'] as $brand)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"></td>
            <td style="background-color: lightgray; font-size: 12px;">
                {{ $brand['brand_name'] }}
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                {{ $brand['products']->sum('initial_stock') }}
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                {{ $brand['products']->sum('total_stock_in') }}
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                {{ $brand['products']->sum('total_sold') }}
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                {{ $brand['products']->sum('total_stock_adjustment_added') }}
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                {{ $brand['products']->sum('total_stock_adjustment_deducted') }}
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                {{ $brand['products']->sum('stock') }}
            </td>
            <td style="background-color: lightgray; font-size: 12px;">

                {{ $brand['products']->sum('total_stock_cost') }}
            </td>
        </tr>

        {{-- Products within Brand --}}
        @foreach($brand['products'] as $product)
        <tr>
            <td>{{ $product->id }}</td>
            <td>{{ $product->code }}</td>
            <td>{{ $product->name }}</td>
            <td>{{ $product->initial_stock }}</td>
            <td>{{ $product->total_stock_in }}</td>
            <td>{{ $product->total_sold }}</td>
            <td>{{ $product->total_stock_adjustment_added }}</td>
            <td>{{ $product->total_stock_adjustment_deducted }}</td>
            <td>{{ $product->stock }}</td>
            <td>{{ $product->stock * $product->purchase_cost }}</td>
        </tr>
        @endforeach
        @endforeach
        @endforeach
    </tbody>
</table>