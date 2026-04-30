<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetTransactionsRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\PaginatedResource;
use App\Http\Resources\TransactionResource;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(GetTransactionsRequest $request)
    {
        $transactions = Transaction::with(['customer', 'items.product'])
            ->search($request->search)
            ->latest()
            ->paginate($request->limit ?? 10);

        return ApiResponse::success(
            new PaginatedResource($transactions, TransactionResource::class),
            'Transactions list'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionRequest $request)
    {
        try {
            DB::beginTransaction();

            // Load products and validate stock
            $items = $request->items;
            $productIds = collect($items)->pluck('product_id')->unique();
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);

                if (!$product) {
                    DB::rollBack();
                    return ApiResponse::error(
                        "Product with id {$item['product_id']} not found",
                        Response::HTTP_NOT_FOUND
                    );
                }

                if ($product->stock < $item['quantity']) {
                    DB::rollBack();
                    return ApiResponse::error(
                        "Insufficient stock for product '{$product->name}'. Available: {$product->stock}, Requested: {$item['quantity']}",
                        Response::HTTP_UNPROCESSABLE_ENTITY
                    );
                }
            }

            // Calculate totals
            $subtotal = 0;
            $transactionItems = [];

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $itemSubtotal = $product->price * $item['quantity'];
                $subtotal += $itemSubtotal;

                $transactionItems[] = [
                    'product_id' => $product->id,
                    'price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal,
                ];
            }

            $tax = $subtotal * 0.11;
            $total = $subtotal + $tax;

            // Generate transaction code
            $code = 'TRX-' . now()->format('Ymd') . '-' . str_pad(
                Transaction::whereDate('created_at', now()->toDateString())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            // Create transaction
            $transaction = Transaction::create([
                'code' => $code,
                'customer_id' => $request->customer_id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            // Create transaction items and reduce stock
            foreach ($transactionItems as $item) {
                $transaction->items()->create($item);

                $product = $products->get($item['product_id']);
                $product->decrement('stock', $item['quantity']);
            }

            DB::commit();

            return ApiResponse::success(
                new TransactionResource($transaction->load(['customer', 'items.product'])),
                'Transaction created successfully',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::error(
                'Transaction failed: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $transaction = Transaction::with(['customer', 'items.product'])->find($id);

        if (!$transaction) {
            return ApiResponse::error(
                'Transaction not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return ApiResponse::success(
            new TransactionResource($transaction),
            'Transaction details'
        );
    }
}
