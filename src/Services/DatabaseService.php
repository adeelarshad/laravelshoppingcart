<?php

namespace Proxi\ShoppingCart\Services;

use App\CartItemModel;
use App\CartModel;
use Illuminate\Support\Facades\Log;
use Proxi\ShoppingCart\CartItem;
use Proxi\ShoppingCart\Facades\Cart;

class DatabaseService
{
    public ?CartModel $cartModel = null;

    public function __construct()
    {
        $this->cartModel = $this->getCartModel();
    }

    public function add(CartItem $item)
    {
        if (! $this->cartModel) {
            return;
        }

        try {
            $cartItem = CartItemModel::query()
                ->where('cart_id', $this->cartModel->id)
                ->where('row_id', $item->rowId)
                ->where('product_id', $item->id)
                ->firstOrNew();

            $cartItem->cart_id = $this->cartModel->id;
            $cartItem->row_id = $item->rowId;
            $cartItem->product_id = $item->id;
            $cartItem->title = $item->name;
            $cartItem->price = $item->price;
            $cartItem->qty = $item->qty;
            $cartItem->options = $item->options;

            $cartItem->save();
        } catch (\Throwable $th) {
            Log::alert('Error in adding item to cart: ' . $th->getMessage());
        }
    }

    public function update(CartItem $item)
    {
        if (! $this->cartModel) {
            return;
        }

        try {
            $cartItem = CartItemModel::query()
                ->where('cart_id', $this->cartModel->id)
                ->where('row_id', $item->rowId)
                ->first();

            $cartItem->title = $item->name;
            $cartItem->price = $item->price;
            $cartItem->qty = $item->qty;
            $cartItem->options = $item->options;

            $cartItem->save();
        } catch (\Throwable $th) {
            Log::alert('Error in updating cart item: ' . $th->getMessage());
        }
    }

    public function remove($rowId)
    {
        if (! $this->cartModel) {
            return;
        }

        try {
            CartItemModel::query()
                ->where('cart_id', $this->cartModel->id)
                ->where('row_id', $rowId)
                ->delete();

            $this->cartModel->touch();
        } catch (\Throwable $th) {
            Log::alert('Error in deleting cart item: ' . $th->getMessage());
        }
    }

    public function destroy()
    {
        if (! $this->cartModel) {
            return;
        }

        try {
            $this->cartModel->delete();
        } catch (\Throwable $th) {
            Log::alert('Error in deleting cart model: ' . $th->getMessage());
        }
    }

    public function loadCartFromDatabase()
    {
        try {
            if (!auth()->guard('shop')->check() || !empty(Cart::count())) {
                return;
            }

            request()->session()->put('load_cart_check', true);

            $customer = auth()->guard('shop')->user();
            $cart = CartModel::where('user_id', $customer->id)->with('items')->first();

            if (! $cart || $cart->items->isEmpty()) {
                return;
            }

            Cart::setCartFromDatabase($cart->items);
        } catch (\Throwable $th) {
            Log::alert('Error in loading cart from DB: ' . $th->getMessage());
        }
    }

    protected function getCartModel()
    {
        if (! config('cart.database')) {
            return;
        }

        try {
            $cartModel = CartModel::query()
                ->when(auth()->guard('shop')->user(), function ($query, $user) {
                    $query->where('user_id', $user->id);
                }, function ($query) {
                    $query->where('session_id', request()->session()->getId());
                })
                ->firstOrNew();

            if (auth()->guard('shop')->check()) {
                $cartModel->user_id = auth()->guard('shop')->user()->id;
            }

            $cartModel->session_id = request()->session()->getId();

            $cartModel->save();

            return $cartModel;
        } catch (\Throwable $th) {
            Log::alert('Error in saving cart model: ' . $th->getMessage());
        }
    }
}
