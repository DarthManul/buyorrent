<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\UserItem;
use App\Models\UserItemBuy;
use App\Models\UserItemExtendRent;
use App\Models\UserItemRent;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use TransactionService;

class PurchaseController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function buyItem(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'item_id' => 'required|integer'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Purchasing process failed.',
                    'status' => 422,
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        $item = Item::select('id', 'name', 'description', 'image', 'price')
            ->where('id', '=', $request->get('item_id'))
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

        return DB::transaction(function () use ($item, $request) {
            $user = $user = $request->user();

            try {
                $this->transactionService->decreaseBalance($user, $item->price);
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Insufficient balance to purchase this item.',
                        'status' => 403,
                    ]
                ], 403);
            }

            $userItem = UserItem::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'type' => 'buy',
                'price' => $item->price,
            ]);

            UserItemBuy::create([
                'user_item_id' => $userItem->id,
                'buy_date' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item purchased successfully!',
                'data' => [
                    'item' => $item->toArray(),
                    'purchase_id' => $userItem->id,
                    'balance' => $user->balance,
                ]
            ]);
        });
    }


    public function rentItem(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'item_id' => 'required|integer',
                'time' => 'required|integer|in:4,8,12,24'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Rent process failed.',
                    'status' => 422,
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        $item = Item::select('id', 'name', 'description', 'image', 'price')
            ->where('id', '=', $request->get('item_id'))
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

        return DB::transaction(function () use ($item, $request) {
            $user = $request->user();
            $rent_time = $request->get('time');
            $rent_price = $item->price / env('RENT_PRICE_MODIFYER', 48) * $rent_time;

            try {
                $this->transactionService->decreaseBalance($user, $rent_price);
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Insufficient balance to purchase this item.',
                        'status' => 403,
                    ]
                ], 403);
            }

            $start_date = now();
            $end_date = $start_date->copy()->addHours($rent_time);
            $userItem = UserItem::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'price' => $rent_price,
                'type' => 'rent',
            ]);

            UserItemRent::create([
                'user_item_id' => $userItem->id,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item rent successfully!',
                'data' => [
                    'item' => $item->toArray(),
                    'purchase_id' => $userItem->id,
                    'rent_until' => $end_date,
                    'balance' => $user->balance,
                ]
            ]);
        });
    }

    public function extendRent(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'purchase_id' => 'required|integer',
                'time' => 'required|integer|min:1'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Purchasing process failed.',
                    'status' => 422,
                    'details' => $validator->errors()
                ]
            ], 422);
        }
        $user = request()->user();

        $user_item = UserItem::select('*')
            ->where('user_items.type', '=', 'rent')
            ->where('user_items.id', '=', $request->get('purchase_id'))
            ->first();

        if (!$user_item || $user_item->user_id != $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Rent transaction not found',
                    'status' => 404,
                ]
            ], 404);
        }
        $start_date = Carbon::parse($user_item->details->start_date);
        $end_date = Carbon::parse(count($user_item->details->extensions) > 0 ?
            $user_item->details->extensions()
            ->orderBy('end_date', 'desc')
            ->first()
            ->end_date
            : $user_item->details->end_date);
        if (now()->isAfter($end_date)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Rent has been expired. You can\'t extend it anymore',
                    'status' => 403,
                ]
            ], 403);
        }

        $rent_time = request()->get('time');
        if (($diff = $end_date->diffInHours($start_date)) + $rent_time > 24) {
            $max_acceptable_diff = 24 - $diff;
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => "Max amount of hours to extend is $max_acceptable_diff",
                    'status' => 403,
                ]
            ], 403);
        }

        return DB::transaction(function () use ($user_item, $request, $end_date, $rent_time) {
            $user = $user = $request->user();
            $item = $user_item->item;
            $user_item_rent_id = $user_item->details->id;
            $rent_price = $item->price / env('RENT_PRICE_MODIFYER', 48) * $rent_time;

            try {
                $this->transactionService->decreaseBalance($user, $rent_price);
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'message' => 'Insufficient balance to purchase this item.',
                        'status' => 403,
                    ]
                ], 403);
            }

            $user_item = UserItem::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'type' => 'extend',
                'price' => $rent_price,
            ]);
            UserItemExtendRent::create([
                'user_item_id' => $user_item->id,
                'user_item_rent_id' => $user_item_rent_id,
                'start_date' => $end_date,
                'end_date' => $end_date->addHours($rent_time)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item purchased successfully!',
                'data' => [
                    'item' => $item->toArray(),
                    'purchase_id' => $user_item->id,
                    'rent_until' => $end_date,
                    'balance' => $user->balance,
                    2,
                ]
            ]);
        });
    }

    public function getStatus(Request $request, $item_id)
    {
        $user_id = $request->user()->id;
        $item = Item::select([
            'id',
            'name',
            'description',
            'image',
            'price',
            DB::raw("CAST(price / " . env('RENT_PRICE_MODIFYER', 48) . " AS DECIMAL(10, 2)) AS rent_per_hour_price")
        ])
            ->where('id', '=', $item_id)
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

        $purchases = UserItem::select('id', 'type', 'created_at as purchase_time')
            ->where('user_id', '=', $user_id)
            ->where('item_id', '=', $item_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $purchases_info = [];

        foreach ($purchases as $purchase) {
            $purchase_array = $purchase->toArray();
            if ($purchase->type !== 'buy') {
                $purchase_array['start_date'] = $purchase->details->start_date;
                $purchase_array['end_date'] = $purchase->details->end_date;
            }
            $purchases_info[] = $purchase_array;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'item_info' => $item,
                'purchases_history' => $purchases_info
            ]
        ]);
    }

    public function myPurchases(Request $request)
    {

        $user_id = $request->user()->id;
        $purchases = UserItem::select('id', 'type', 'created_at as purchase_time', 'item_id')
            ->where('user_id', '=', $user_id)
            ->with('item')
            ->orderBy('created_at', 'desc')
            ->get();

        $purchases_info = [];

        foreach ($purchases as $purchase) {

            $purchase_array = $purchase->toArray();
            $purchase_array['item'] = [
                'name' => $purchase->item->name,
                'description' => $purchase->item->description,
                'image' => $purchase->item->image
            ];

            if ($purchase->type !== 'buy') {
                $purchase_array['start_date'] = $purchase->details->start_date;
                $purchase_array['end_date'] = $purchase->details->end_date;
            }
            $purchases_info[] = $purchase_array;
        }

        return response()->json([
            'success' => true,
            'data' => $purchases_info
        ], 200);
    }
}
