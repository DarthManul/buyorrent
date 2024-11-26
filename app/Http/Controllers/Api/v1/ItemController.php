<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offset' => 'integer|min:0',
            'limit' => 'integer|gt:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Validation error',
                    'status' => 422,
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 10);

        $items = Item::select([
            'id',
            'name',
            'description',
            'image',
            'price as buy_price',
            DB::raw("CAST(price / " . env('RENT_PRICE_MODIFYER', 48) . " AS DECIMAL(10, 2)) AS rent_per_hour_price")
        ])
            ->offset($offset)
            ->limit($limit)
            ->get();

        $response = [
            'success' => true,
            'data' => [
                'meta' => [
                    'offset' => $offset,
                    'limit' => $limit,
                    'total' => Item::count()
                ],
                'items' => $items
            ]
        ];

        return response()->json(
            $response,
            200
        );
    }

    public function show(Request $request, $id)
    {
        $item = Item::select([
            'id',
            'name',
            'description',
            'image',
            'price',
            DB::raw("CAST(price / " . env('RENT_PRICE_MODIFYER', 48) . " AS DECIMAL(10, 2)) AS rent_per_hour_price")
        ])
            ->where('id', '=', $id)
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Item not found',
                    'status' => 404,
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $item,
        ], 200);
    }
}
