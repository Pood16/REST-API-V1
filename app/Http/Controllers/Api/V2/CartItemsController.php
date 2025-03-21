<?php

namespace App\Http\Controllers\Api\V2;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Jobs\DeleteProductJob;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Models\Order;
use App\Models\Payment;
use App\Notifications\newOrderNotification;
use App\Notifications\NewUserNotification;
use App\Notifications\orderPlacedNotification;
use GuzzleHttp\Psr7\Response;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Stripe\Customer;
use Stripe\Stripe;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CartItemsController extends Controller
{
    public function add(Request $request)
    {
        try {
            $data = [
                'session_id' => null,
                'user_id' => null
            ];
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'required|integer|min:1|max:' . Product::find($request->product_id)->stock,
                'session_id' => 'nullable',
            ]);
            $data = array_merge($data, $validated);

            if (!AuthController::validToken($request->bearerToken())) {
                if (!$data['session_id']) {
                    $data['session_id'] = uniqid('cart_', true);
                }
            } else {
                $data['user_id'] = PersonalAccessToken::findToken($request->bearerToken())->tokenable->id;
                $data['session_id'] = null;
            }

            $existingItem = CartItem::where([
                'session_id' => $data['session_id'],
                'product_id' => $data['product_id']
            ])->first();

            if ($existingItem) {
                $newQuantity = $existingItem->quantity + $data['quantity'];
                $product = Product::find($data['product_id']);

                if ($newQuantity > $product->stock) {
                    return response()->json(['message' => 'Requested quantity exceeds available stock'], 422);
                }

                $existingItem->update(['quantity' => $newQuantity]);
            } else {
                $cart = CartItem::create($data);
                DeleteProductJob::dispatch($cart->id)->delay(Carbon::now()->addHours(48));
            }

            return response()->json(['message' => 'Item added to cart', 'session_id' => $data['session_id']]);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Error adding item to cart', 'error' => $th->getMessage()]);
        }
    }

    public function update(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'required|integer|min:1|max:' . Product::find($request->product_id)->stock,
                'session_id' => 'nullable',
            ]);

            $conditions = [];
            if (!AuthController::validToken($request->bearerToken())) {
                if (!$validated['session_id']) {
                    return response()->json(['message' => 'Session ID is required'], 422);
                }
                $conditions['session_id'] = $validated['session_id'];
            } else {
                $conditions['user_id'] = PersonalAccessToken::findToken($request->bearerToken())->tokenable->id;
            }

            $conditions['product_id'] = $validated['product_id'];
            $cartItem = CartItem::where($conditions)->first();

            if (!$cartItem) {
                return response()->json(['message' => 'Cart item not found'], 404);
            }

            $product = Product::find($validated['product_id']);
            if ($validated['quantity'] > $product->stock) {
                return response()->json(['message' => 'Requested quantity exceeds available stock'], 422);
            }

            $cartItem->update(['quantity' => $validated['quantity']]);
            return response()->json(['message' => 'Item updated successfully', 'cart_item' => $cartItem]);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Error updating item', 'error' => $th->getMessage()], 500);
        }
    }


    public function clear(Request $request)
    {
        try {
            $conditions = [];
            if (!AuthController::validToken($request->bearerToken())) {
                $validated = $request->validate([
                    'session_id' => 'required',
                ]);
                $conditions['session_id'] = $validated['session_id'];
            } else {
                $conditions['user_id'] = PersonalAccessToken::findToken($request->bearerToken())->tokenable->id;
            }

            CartItem::where($conditions)->delete();
            return response()->json(['message' => 'Cart cleared successfully']);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Error clearing cart', 'error' => $th->getMessage()], 500);
        }
    }

    public function items(Request $request)
    {
        // dd($request->bearerToken());
        try {
            $conditions = [];
            if (!AuthController::validToken($request->bearerToken())) {
                // dd(true);
                $validated = $request->validate([
                    'session_id' => 'required',
                ]);
                $conditions['session_id'] = $validated['session_id'];
                $total = $this->calculateTotal(null, $conditions['session_id'], 0, 0, 0);
            } else {
                $conditions['user_id'] = PersonalAccessToken::findToken($request->bearerToken())->tokenable->id;
                $total = $this->calculateTotal($conditions['user_id'], null, 0, 0, 0);
            }

            $items = CartItem::where($conditions)
                ->with('product')
                ->get();



            return response()->json([
                'message' => 'Cart items retrieved successfully',
                'items' => $items,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Error retrieving cart items', 'error' => $th->getMessage()], 500);
        }
    }


    public static function mergeCartItems($sessionId, $userId)
    {

        $sessionCartItems = CartItem::whereNull('user_id')
            ->where('session_id', $sessionId)
            ->get();
        // dd($sessionCartItems);

        foreach ($sessionCartItems as $sessionItem) {

            $existingItem = CartItem::where('user_id', $userId)
                ->where('product_id', $sessionItem->product_id)
                ->first();


            if ($existingItem) {
                $existingItem->quantity += $sessionItem->quantity;
                $existingItem->save();
                CartItem::whereNull('user_id')->where('session_id', $sessionId)->delete();
            } else {
                $sessionItem->user_id = $userId;
                $sessionItem->session_id = null;
                $sessionItem->save();
            }
        }
        return response()->json(['message' => 'Cart items merged']);
    }

    public function removeFromCart($id)
    {
        $cartItem = CartItem::find($id);
        if (!$cartItem) {
            return response()->json([
                'message' => 'Cart item not found'
            ], 404);
        }
        $cartItem->delete();
        return response()->json([
            'message' => 'Item removed from cart',
        ]);
    }
    public function calculateTotal($user, $sessionid, $livraison, $tva, $discount)
    {
        if ($discount < 0 || $discount > 100) {
            $discount = 0;
        }

        $cartItems = $user
            ? CartItem::where("user_id", $user)->with('product')->get()
            : CartItem::where("session_id", $sessionid)->with('product')->get();

        $subtotal = 0;
        $totalQuantity = 0;
        $totalDiscount = 0;

        foreach ($cartItems as $item) {
            $quantity = $item->quantity;
            $price = $item->product->price ?? 0;
            $itemTotal = $price * $quantity;
            $discountAmount = ($itemTotal * $discount) / 100;
            $subtotal += $itemTotal - $discountAmount;
            $totalDiscount += $discountAmount;
            $totalQuantity += $quantity;
        }
        $tva = $subtotal * $tva;

        $totalPrice = $subtotal + $tva + $livraison;

        return [
            'subtotal' => round($subtotal, 2),
            'total_discount' => round($totalDiscount, 2),
            'tva' => round($tva, 2),
            'shipping_fee' => round($livraison, 2),
            'total_price' => round($totalPrice, 2),
            'total_quantity' => $totalQuantity
        ];
    }

    public function discount() {}
    public function index()
    {

        $t = $this->calculateTotal(null, 1, 2, 0.4, 2);
        dd($t);
    }

    public function checkout()
    {

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $userId = auth()->user()->id;
        $cartItem = CartItem::with('product')->where('user_id', $userId)->get();
        if ($cartItem->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }
        $lineItems = [];
        $total = 0;
        foreach ($cartItem as $item) {
            $total += $item->product->price * $item->quantity;
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->product->name,
                    ],
                    'unit_amount' => $item->product->price * 100,
                ],
                'quantity' => $item->quantity,
            ];
        }

        $session = $stripe->checkout->sessions->create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('success', [], true) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('cancel', [], true) . '?session_id={CHECKOUT_SESSION_ID}',
        ]);
        DB::beginTransaction();
        try {
            // Create order
            $order = Order::create([
                "user_id" => $userId,
                "total_price" => $total,
                "session_id" => $session->id
            ]);
            $notifiable_admin = User::role('super_admin')->first();
            // $user = User::where('id', 1);
            $notifiable_admin->notify(new newOrderNotification());

            $user = auth()->user();
            $user->notify(new orderPlacedNotification());
            // create oder items
            foreach ($cartItem as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'price' => $item->product->price,
                ]);
            }

            // Create payment
            $payment = Payment::create([
                "order_id" => $order->id,
                "payment_type" => "stripe",
                "transaction_id" => $session->id
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Checkout failed',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'sessionId' => $session->id,
            'url' => $session->url,
            'message' => 'success'
        ]);
    }

    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $session = $stripe->checkout->sessions->retrieve($sessionId);



        DB::beginTransaction();
        try {
            $myOrder = Order::with('items')->where('session_id', $sessionId)->first();
            $myPayment = Payment::where('transaction_id', $sessionId)->first();
            $cartItems = CartItem::where('user_id', $myOrder->user_id);
            $myPayment->status = "completed";
            $myPayment->save();
            $myOrder->status = "in process";
            $myOrder->save();
            foreach ($myOrder->items as $orderItem) {
                $cartItem = $cartItems->where('product_id', $orderItem->product_id)->first();
                $product = Product::find($orderItem->product_id);
                $product->stock -= $cartItem->quantity;
                $product->save();
            }
            $cartItems->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Checkout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function failure()
    {
        return response()->json([
            'message' => 'Checkout failed'
        ], 500);
    }
}
