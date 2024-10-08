<?php

namespace Proxi\ShoppingCart;

use Validator;
use Proxi\ShoppingCart\Exceptions\InvalidItemException;
use Proxi\ShoppingCart\Services\DatabaseService;

/**
 * Class Cart
 * @package Proxi\ShoppingCart
 */
class Cart
{
    /**
     * the item storage
     *
     * @var
     */
    protected $session;

    /**
     * the event dispatcher
     *
     * @var
     */
    protected $events;

    /**
     * the cart session key
     *
     * @var
     */
    protected $instanceName;

    /**
     * the session key use to persist cart items
     *
     * @var
     */
    protected $sessionKeyCartItems;

    /**
     * Configuration to pass to ItemCollection
     *
     * @var
     */
    protected $config;

    /**
     * Instance for DB model
     *
     * @var
     */
    protected $databaseService;

    /**
     * our object constructor
     *
     * @param $session
     * @param $events
     * @param $instanceName
     * @param $session_key
     * @param $config
     */
    public function __construct($session, $events, $instanceName, $config)
    {
        $this->session = $session;
        $this->events = $events;
        $this->instanceName = $instanceName;
        $this->sessionKeyCartItems = 'cart_items';
        $this->config = $config;
        $this->databaseService = new DatabaseService();
    }

    /**
     * sets the session key
     *
     * @param string $sessionKey the session key or identifier
     * @return $this|bool
     * @throws InvalidItemException
     */
    public function session($sessionKey)
    {
        if(!$sessionKey) throw new InvalidItemException("Session key is required.");

        $this->sessionKeyCartItems = $sessionKey;

        return $this;
    }

    /**
     * add item to the cart
     *
     * @param string $id
     * @param string $name
     * @param float $price
     * @param int $quantity
     * @param array $attributes
     * @return $this
     * @throws InvalidItemException
     */
    public function add($id, $name = null, $price = null, $qty = null, $options = [])
    {
        // validate data
        $item = $this->validate(array(
            'id' => $id,
            'name' => $name,
            'price' => $this->normalizePrice($price),
            'qty' => $qty,
            'options' => $options
        ));

        $cartItem = $this->createCartItem($id, $name, $price, $qty, $options);

        // get the cart
        $cart = $this->getContent();

        // if the item is already in the cart we will just update it
        if ($cart->has($cartItem->rowId)) {
            $new_qty = $cartItem->qty + $cart->get($cartItem->rowId)->qty;
            if ( $new_qty > $options['total_stock'] ) $new_qty = $options['total_stock'];

            $cartItem->qty = $new_qty;
        }

        $this->addRow($cartItem->rowId, $cartItem);

        $this->databaseService->add($cartItem);

        return $cartItem->rowId;
    }

    /**
     * validate Item data
     *
     * @param $item
     * @return array $item;
     * @throws InvalidItemException
     */
    protected function validate($item)
    {
        $rules = array(
            'id' => 'required',
            'price' => 'required|numeric',
            'qty' => 'required|numeric|min:1',
            'name' => 'required',
        );

        $validator = Validator::make($item, $rules);

        if ($validator->fails()) {
            throw new InvalidItemException($validator->messages()->first());
        }

        return $item;
    }

    /**
     * add row to cart collection
     *
     * @param $id
     * @param $item
     * @return bool
     */
    protected function addRow($id, $item)
    {
        $cart = $this->getContent();
        $cart->put($id, $item);

        $this->save($cart);

        return true;
    }

    /**
     * update a cart
     *
     * @param $id
     * @param $data
     *
     * the $data will be an associative array or qty, you don't need to pass all the data, only the key value
     * of the item you want to update on it
     * @return bool
     */
    public function update($id, $data)
    {
        $cart = $this->getContent();
        $item = $cart->pull($id);

        // if data is array
        if ( is_array($data) && !empty($data) ) {

            foreach ($data as $key => $value) {

                if ($key == 'options') {
                    $item->{$key} = new CartItemOptions($value);
                } else {
                    $item->{$key} = $value;
                }
            }

        } else {

            if ( empty($data) )
                throw new InvalidItemException("Please supply a valid quantity.");

            $item->qty = $data;
        }

        $cart->put($id, $item);

        $this->databaseService->update($item);

        $this->save($cart);

        return true;
    }

    /**
     * Create a new CartItem from the supplied attributes.
     *
     * @param mixed     $id
     * @param mixed     $name
     * @param int|float $qty
     * @param float     $price
     * @param array     $options
     * @return \Proxi\ShoppingCart\CartItem
     */
    private function createCartItem($id, $name, $price, $qty, $options = [])
    {
        $cartItem = CartItem::fromAttributes($id, $name, $price, $qty, $options);
        $cartItem->setTaxRate(config('cart.tax'));

        return $cartItem;
    }

    /**
     * Get the carts content, if there is no cart content set yet, return a new empty Collection
     *
     * @return \Illuminate\Support\Collection
     */
    public function getContent()
    {
        return (collect($this->session->get($this->sessionKeyCartItems)));
    }

    /**
     * Get the content of the cart.
     *
     * @return Collection
     */
    public function content()
    {
        if (is_null($this->session->get($this->sessionKeyCartItems))) {
            return collect([]);
        }

        return $this->session->get($this->sessionKeyCartItems);
    }

    /**
     * save the cart
     *
     * @param $cart Collection
     */
    protected function save($cart)
    {
        $this->session->put($this->sessionKeyCartItems, $cart);
    }

    /**
     * Remove the cart item with the given rowId from the cart.
     *
     * @param string $rowId
     * @return void
     */
    public function remove($rowId)
    {
        $cartItem = $this->get($rowId);

        $content = $this->getContent();

        $content->pull($cartItem->rowId);

        $this->save($content);

        $this->databaseService->remove($rowId);
    }

    /**
     * check if an item exists by item ID
     *
     * @param $itemId
     * @return bool
     */
    public function has($itemId)
    {
        return $this->getContent()->has($itemId);
    }

    /**
     * Get a cart item from the cart by its rowId.
     *
     * @param string $rowId
     * @return \Proxi\ShoppingCart\CartItem
     */
    public function get($rowId)
    {
        $content = $this->getContent();

        if ( ! $content->has($rowId))
            throw new InvalidItemException("The cart does not contain rowId {$rowId}.");

        return $content->get($rowId);
    }

    /**
     * Destroy the current cart instance.
     *
     * @return void
     */
    public function destroy()
    {
        $this->session->forget($this->sessionKeyCartItems);
        $this->databaseService->destroy();
    }

    /**
     * Search the cart content for a cart item matching the given search closure.
     *
     * @param \Closure $search
     * @return \Illuminate\Support\Collection
     */
    public function search(Closure $search)
    {
        $content = $this->getContent();

        return $content->filter($search);
    }

    /**
     * normalize price
     *
     * @param $price
     * @return float
     */
    public function normalizePrice($price)
    {
        return (is_string($price)) ? floatval($price) : $price;
    }

    /**
     * Get the number of items in the cart.
     *
     * @return int|float
     */
    public function count()
    {
        $content = $this->getContent();

        return $content->sum('qty');
    }

    /**
     * Set cart from Database.
     *
     */
    public function setCartFromDatabase($items)
    {
        $this->session->forget($this->sessionKeyCartItems);
        $cart = $this->getContent();

        foreach ($items as $item) {
            $cartItem = $this->createCartItem($item->product_id, $item->title, $item->price, $item->qty, $item->options);
            $cart->put($cartItem->rowId, $cartItem);
        }

        $this->session->put($this->sessionKeyCartItems, $cart);
    }

    /**
     * Get the total price of the items in the cart.
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeperator
     * @return string
     */
    public function total($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        $content = $this->getContent();

        $total = $content->reduce(function ($total, CartItem $cartItem) {
            if ( isset($cartItem->options['discount']) ) $discount = $cartItem->options['discount']; else $discount = 0;
            return $total + ( ($cartItem->qty * $cartItem->priceTax) - $discount );
        }, 0);

        return $this->numberFormat($total, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the price discount total of the items in the cart.
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeperator
     * @return string
     */
    public function discount($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        $content = $this->getContent();

        $total = $content->reduce(function ($total, CartItem $cartItem) {
            if ( isset($cartItem->options['discount']) ) $discount = $cartItem->options['discount']; else $discount = 0;
            return $total + $discount;
        }, 0);

        return $this->numberFormat($total, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the total tax of the items in the cart.
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeperator
     * @return float
     */
    public function tax($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        $content = $this->getContent();

        $tax = $content->reduce(function ($tax, CartItem $cartItem) {
            return $tax + ($cartItem->qty * $cartItem->tax);
        }, 0);

        return $this->numberFormat($tax, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the subtotal (total - tax) of the items in the cart.
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeperator
     * @return float
     */
    public function subtotal($decimals = null, $decimalPoint = null, $thousandSeperator = null)
    {
        $content = $this->getContent();

        $subTotal = $content->reduce(function ($subTotal, CartItem $cartItem) {
            return $subTotal + ($cartItem->qty * $cartItem->price);
        }, 0);

        return $this->numberFormat($subTotal, $decimals, $decimalPoint, $thousandSeperator);
    }

    /**
     * Get the Formated number
     *
     * @param $value
     * @param $decimals
     * @param $decimalPoint
     * @param $thousandSeperator
     * @return string
     */
    private function numberFormat($value, $decimals, $decimalPoint, $thousandSeperator)
    {
        if(is_null($decimals)){
            $decimals = is_null(config('cart.format.decimals')) ? 2 : config('cart.format.decimals');
        }
        if(is_null($decimalPoint)){
            $decimalPoint = is_null(config('cart.format.decimal_point')) ? '.' : config('cart.format.decimal_point');
        }
        if(is_null($thousandSeperator)){
            $thousandSeperator = is_null(config('cart.format.thousand_seperator')) ? ',' : config('cart.format.thousand_seperator');
        }

        return number_format($value, $decimals, $decimalPoint, $thousandSeperator);
    }
}
