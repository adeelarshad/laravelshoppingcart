<?php

namespace Proxi\ShoppingCart\Services;

use App\CartItemModel;
use App\CartModel;
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
    }

    public function update(CartItem $item)
    {
        if (! $this->cartModel) {
            return;
        }

        $cartItem = CartItemModel::query()
            ->where('cart_id', $this->cartModel->id)
            ->where('row_id', $item->rowId)
            ->first();

        $cartItem->title = $item->name;
        $cartItem->price = $item->price;
        $cartItem->qty = $item->qty;
        $cartItem->options = $item->options;

        $cartItem->save();
    }

    public function remove($rowId)
    {
        if (! $this->cartModel) {
            return;
        }

        CartItemModel::query()
            ->where('cart_id', $this->cartModel->id)
            ->where('row_id', $rowId)
            ->delete();

        $checkRemaining = CartItemModel::where('cart_id', $this->cartModel->id)->count();

        if (empty($checkRemaining)) {
            $this->destroy();
        }
    }

    public function destroy()
    {
        if (! $this->cartModel) {
            return;
        }

        $this->cartModel->delete();
    }

    public function loadCartFromDatabase()
    {
        if (!auth()->guard('shop')->check() || !empty(Cart::count())) {
            return;
        }

        $customer = auth()->guard('shop')->user();
        $cart = CartModel::where('user_id', $customer->id)->with('items')->first();

        if (! $cart) {
            return;
        }

        foreach ($cart->items as $item) {
            // add option to skip database inseration
            Cart::add($item->product_id, $item->title, $item->price, $item->qty, $item->options);
        }
    }

    protected function getCartModel()
    {
        if (! config('cart.database')) {
            return;
        }

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
    }
}
