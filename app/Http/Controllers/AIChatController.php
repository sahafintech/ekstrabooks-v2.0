<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ReceivePayment;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Account;
use App\Models\Purchase;
use App\Models\SubCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class AIChatController extends Controller
{
    public function index()
    {
        return Inertia::render('Backend/User/AI/Chat', [
            'csrf_token' => csrf_token()
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $userMessage = trim($request->input('message'));

        // Basic validation
        if (empty($userMessage)) {
            return response()->json([
                'response' => 'Please enter a valid question.',
                'type' => 'error'
            ], 422);
        }

        if (empty(config('services.gemini.api_key'))) {
            return response()->json([
                'response' => 'AI service is not properly configured. Please contact your administrator.',
                'type' => 'error'
            ], 500);
        }

        if (!$this->isFinancialQuestion($userMessage)) {
            return response()->json([
                'response' => '🚫 This info is not about your financial data',
                'type' => 'success'
            ]);
        }

        $context = $this->prepareFinancialContext();

        $systemPrompt = $this->createSystemPrompt();

        $response = $this->callGeminiAPI($systemPrompt, $userMessage, $context);

        return response()->json([
            'response' => $response,
            'type' => 'success'
        ]);
    }

    private function isFinancialQuestion(string $message): bool
    {
        // Keywords that indicate financial/business questions
        $financialKeywords = [
            'customer',
            'customers',
            'invoice',
            'invoices',
            'payment',
            'payments',
            'expense',
            'expenses',
            'revenue',
            'income',
            'profit',
            'loss',
            'sales',
            'purchase',
            'bill',
            'bills',
            'account',
            'accounts',
            'balance',
            'total',
            'amount',
            'money',
            'financial',
            'finance',
            'business',
            'vendor',
            'vendors',
            'product',
            'products',
            'transaction',
            'transactions',
            'overdue',
            'paid',
            'unpaid',
            'outstanding',
            'receivable',
            'payable',
            'tax',
            'taxes',
            'quotation',
            'quotations',
            'receipt',
            'receipts',
            'cash',
            'bank',
            'credit',
            'debit',
            'due',
            'owed',
            'owing',
            'hi',
            'thanks',
            'thank you'
        ];

        $messageLower = strtolower($message);

        foreach ($financialKeywords as $keyword) {
            if (strpos($messageLower, $keyword) !== false) {
                return true;
            }
        }

        // Check for question patterns that might be financial
        $questionPatterns = [
            '/how much.*owe/i',
            '/what.*total/i',
            '/show.*report/i',
            '/list.*top/i',
            '/who.*best/i',
            '/which.*highest/i',
            '/when.*due/i'
        ];

        foreach ($questionPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    private function prepareFinancialContext(): array
    {
        // Get customers with their balance information
        $customers = Customer::select('id', 'name', 'company_name', 'email', 'balance', 'city', 'country')
            ->with(['invoices' => function ($query) {
                $query->select('id', 'customer_id', 'invoice_number', 'grand_total', 'paid', 'status', 'invoice_date', 'due_date');
            }])
            ->get()
            ->map(function ($customer) {
                $statusMap = [
                    0 => 'Draft',
                    1 => 'Active',
                    2 => 'Paid',
                    3 => 'Partial Paid',
                ];

                if ($customer->invoices) {
                    $customer->invoices->map(function ($invoice) use ($statusMap) {
                        $invoice->due_date = Carbon::createFromFormat(get_date_format(), $invoice->due_date)->format('Y-m-d');
                        $invoice->invoice_date = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->format('Y-m-d');
                        
                        $invoice->status = $statusMap[$invoice->status] ?? $invoice->status;
                        
                        return $invoice;
                    });
                }

                return $customer;
            });

        // Get recent invoices with customer and items
        $invoices = Invoice::select('id', 'customer_id', 'invoice_number', 'title', 'invoice_date', 'due_date', 'sub_total', 'grand_total', 'paid', 'status', 'currency')
            ->with([
                'customer:id,name,company_name',
                'items:id,invoice_id,product_name,quantity,unit_cost,sub_total'
            ])
            ->orderBy('invoice_date', 'desc')
            ->get()
            ->map(function ($invoice) {
                $statusMap = [
                    0 => 'Draft',
                    1 => 'Active',
                    2 => 'Paid',
                    3 => 'Partial Paid',
                ];

                $invoice->due_date = Carbon::createFromFormat(get_date_format(), $invoice->due_date)->format('Y-m-d');
                $invoice->invoice_date = Carbon::createFromFormat(get_date_format(), $invoice->invoice_date)->format('Y-m-d');

                $invoice->status = $statusMap[$invoice->status] ?? $invoice->status;

                return $invoice;
            });

        // Get purchases with vendor information
        $purchases = Purchase::select('id', 'vendor_id', 'bill_no', 'purchase_date', 'due_date', 'grand_total', 'paid', 'status', 'currency')
            ->with([
                'vendor:id,name',
                'items:id,purchase_id,product_name,quantity,unit_cost,sub_total'
            ])
            ->orderBy('purchase_date', 'desc')
            ->get()
            ->map(function ($purchase) {
                $statusMap = [
                    0 => 'Unpaid',
                    1 => 'Partial Paid',
                    2 => 'Paid',
                ];

                $purchase->due_date = Carbon::createFromFormat(get_date_format(), $purchase->due_date)->format('Y-m-d');
                $purchase->purchase_date = Carbon::createFromFormat(get_date_format(), $purchase->purchase_date)->format('Y-m-d');

                $purchase->status = $statusMap[$purchase->status] ?? $purchase->status;

                return $purchase;
            });

        // Get payments
        $payments = ReceivePayment::select('id', 'customer_id', 'date', 'amount', 'payment_method', 'reference')
            ->with([
                'customer:id,name',
                'invoices:id,invoice_number,grand_total'
            ])
            ->orderBy('date', 'desc')
            ->get();

        // Get vendors
        $vendors = Vendor::select('id', 'name', 'company_name', 'email')
            ->get();

        // Get products
        $products = Product::take(100)
            ->get();

        // get product categories
        $productCategories = SubCategory::select('id', 'name')
            ->get();

        // Get account balances
        $accounts = Account::get();

        return [
            'customers' => $customers->toArray(),
            'invoices' => $invoices->toArray(),
            'purchases' => $purchases->toArray(),
            'payments' => $payments->toArray(),
            'vendors' => $vendors->toArray(),
            'products' => $products->toArray(),
            'productCategories' => $productCategories->toArray(),
            'accounts' => $accounts->toArray(),
            'business_currency' => get_business_option('base_currency', 'USD'),
            'current_date' => now()->toDateString()
        ];
    }

    private function createSystemPrompt(): string
    {
        return "You are an AI assistant for eKstraBooks, an accounting software. You have access to the user's financial data including customers, invoices, expenses, payments, vendors, products, and accounts.

IMPORTANT RULES:
1. ONLY answer questions related to the provided financial data
2. Be conversational but professional
3. Always reference specific data from the context when answering
4. Show clear numbers, names, and summaries
5. If asked about anything outside financial data, respond ONLY with: '🚫 This info is not about your financial data'
6. Format monetary amounts clearly with the business currency
7. When showing lists, limit to top 5-10 items unless specifically asked for more
8. Always ground your responses in the actual data provided

You can help with:
- Customer analysis (top customers, balances, etc.)
- Invoice status (overdue, paid, unpaid)
- Financial summaries and reports
- Payment tracking
- Expense analysis
- Vendor information
- Product performance
- Account balances

Answer in a helpful, conversational tone while being precise with the data.";
    }

    private function callGeminiAPI(string $systemPrompt, string $userMessage, array $context): string
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.0-flash');
        $baseUrl = config('services.gemini.url', 'https://generativelanguage.googleapis.com/v1beta/models');

        $url = "{$baseUrl}/{$model}:generateContent";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $systemPrompt . "\n\nUser Question: " . $userMessage . "\n\nFinancial Data Context: " . json_encode($context)
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
            ]
        ];

        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($url . '?key=' . $apiKey, $payload);

        if (!$response->successful()) {
            throw new \Exception('Gemini API error: ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Invalid response format from Gemini API');
        }

        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
}
