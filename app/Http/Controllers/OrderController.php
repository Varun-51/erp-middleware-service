<?php

namespace App\Http\Controllers;

use App\Services\OdooService;
use App\Services\ERPMapper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct(
        protected OdooService $odooService,
        protected ERPMapper $mapper,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->json()->all() ?? $request->all();
        
        $validator = Validator::make($data, [
            'external_ref' => 'required|string|max:50',
            'customer_id' => 'required|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'confirm' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $shouldConfirm = $data['confirm'] ?? false;
            $odooOrderData = $this->mapper->mapSalesOrder($data);
            $orderId = $this->odooService->createSalesOrder($odooOrderData);

            if ($shouldConfirm) {
                $this->odooService->confirmSalesOrder($orderId);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'order_id' => $orderId,
                    'state' => $shouldConfirm ? 'sale' : 'draft',
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid order data',
                'details' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Sales order creation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create sales order',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $order = $this->odooService->getSalesOrder($id);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $this->mapper->reverseMapSalesOrder($order),
            ]);
        } catch (\Exception $e) {
            Log::error('Sales order retrieval failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve order',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function customers(Request $request): JsonResponse
    {
        try {
            $customers = $this->odooService->getCustomers([], $request->get('limit', 50));

            return response()->json([
                'status' => 'success',
                'data' => [
                    'customers' => $customers,
                    'count' => count($customers),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Customer retrieval failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve customers',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function products(Request $request): JsonResponse
    {
        try {
            $products = $this->odooService->getProducts([], $request->get('limit', 50));

            return response()->json([
                'status' => 'success',
                'data' => [
                    'products' => $products,
                    'count' => count($products),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Product retrieval failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve products',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
