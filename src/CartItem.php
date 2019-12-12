<?php

namespace Proxi\ShoppingCart;

use Proxi\ShoppingCart\Exceptions\InvalidItemException;

/**
 * Class CartItem
 * @package Proxi\ShoppingCart
 */
class CartItem
{
    /**
     * The rowID of the cart item.
     *
     * @var string
     */
    public $rowId;

    /**
     * The ID of the cart item.
     *
     * @var int|string
     */
    public $id;

    /**
     * The quantity for this cart item.
     *
     * @var int|float
     */
    public $qty;

    /**
     * The name of the cart item.
     *
     * @var string
     */
    public $name;

    /**
     * The price without TAX of the cart item.
     *
     * @var float
     */
    public $price;

    /**
     * The options for this cart item.
     *
     * @var array
     */
    public $options;

    /**
     * The tax rate for the cart item.
     *
     * @var int|float
     */
    private $taxRate = 0;

    /**
     * CartItem constructor.
     *
     * @param int|string $id
     * @param string     $name
     * @param float      $price
     * @param array      $options
     */
    public function __construct($id, $name, $price, $qty, $options = [])
    {
        if(empty($id)) {
            throw new InvalidItemException('Please supply a valid identifier.');
        }
        if(empty($name)) {
            throw new InvalidItemException('Please supply a valid name.');
        }
        if(strlen($price) < 0 || ! is_numeric($price)) {
            throw new InvalidItemException('Please supply a valid price.');
        }
        if(empty($qty) || ! is_numeric($qty)) {
            throw new InvalidItemException('Please supply a valid quantity.');
        }

        $this->rowId    = $this->generateRowId($id, $options);
        $this->id       = $id;
        $this->name     = $name;
        $this->price    = floatval($price);
        $this->qty      = $qty;
        $this->options  = new CartItemOptions($options);
    }

    /**
     * Set the tax rate.
     *
     * @param int|float $taxRate
     * @return \Proxi\ShoppingCart\CartItem
     */
    public function setTaxRate($taxRate)
    {
        $this->taxRate = $taxRate;
        
        return $this;
    }

    /**
     * Get an attribute from the cart item.
     *
     * @param string $attribute
     * @return mixed
     */
    public function __get($attribute)
    {
        if(property_exists($this, $attribute)) {
            return $this->{$attribute};
        }

        if($attribute === 'priceTax') {
            return $this->price + $this->tax;
        }
        
        if($attribute === 'subtotal') {
            return $this->qty * $this->price;
        }
        
        if($attribute === 'total') {
            return $this->qty * ($this->priceTax);
        }

        if($attribute === 'tax') {
            return $this->price * ($this->taxRate / 100);
        }
        
        if($attribute === 'taxTotal') {
            return $this->tax * $this->qty;
        }

        return null;
    }

    /**
     * Create a new instance from the given attributes.
     *
     * @param int|string $id
     * @param string     $name
     * @param float      $price
     * @param array      $options
     * @return \Proxi\ShoppingCart\CartItem
     */
    public static function fromAttributes($id, $name, $price, $qty, array $options = [])
    {
        return new self($id, $name, $price, $qty, $options);
    }

    /**
     * Generate a unique id for the cart item.
     *
     * @param string $id
     * @param array  $options
     * @return string
     */
    protected function generateRowId($id, array $options)
    {
        ksort($options);
        return md5($id . serialize($options));
    }

    /**
     * Get the formatted number.
     *
     * @param float  $value
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeperator
     * @return string
     */
    private function numberFormat($value, $decimals, $decimalPoint, $thousandSeperator)
    {
        if (is_null($decimals)){
            $decimals = is_null(config('cart.format.decimals')) ? 2 : config('cart.format.decimals');
        }

        if (is_null($decimalPoint)){
            $decimalPoint = is_null(config('cart.format.decimal_point')) ? '.' : config('cart.format.decimal_point');
        }

        if (is_null($thousandSeperator)){
            $thousandSeperator = is_null(config('cart.format.thousand_seperator')) ? ',' : config('cart.format.thousand_seperator');
        }

        return number_format($value, $decimals, $decimalPoint, $thousandSeperator);
    }
}
