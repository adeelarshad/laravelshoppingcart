## LaravelShoppingcart

A simple shoppingcart implementation for Laravel.

## Installation

Install the package through [Composer](http://getcomposer.org/). 

Add this code in your laravel project composer.json file under "require"

	"proxi/shoppingcart": "^1.0.3"

Define repositories url in your composer.json file

	"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/adeelarshad/laravelshoppingcart"
        }
    ],

Run the Composer update command from the Terminal:

    composer update

## Overview
Look at one of the following topics to learn more about LaravelShoppingcart

* [Usage](#usage)
* [Collections](#collections)
* [Session](#session)
* [Exceptions](#exceptions)
* [Example](#example)

## Usage

The shoppingcart gives you the following methods to use:

### Cart::add()

Adding an item to the cart is really simple, you just use the `add()` method, which accepts a variety of parameters.

In its most basic form you can specify the id, name, quantity, price of the product you'd like to add to the cart.

```php
Cart::add('293ad', 'Product 1', 9.99, 1);
```

**Parameters**

```php
id, name, price, qty, options (optional)
```

As an optional fifth parameter you can pass it options, so you can add multiple items with the same id, but with (for instance) a different size.

```php
Cart::add('293ad', 'Product 1', 9.99, 1, ['size' => 'large']);
```

**The `add()` method will return an CartItem instance of the item you just added to the cart.**

### Cart::update()

To update an item in the cart, you'll first need the rowId of the item.
Next you can use the `update()` method to update it.

If you simply want to update the quantity, you'll pass the update method the rowId and the new quantity:

```php
$rowId = 'da39a3ee5e6b4b0d3255bfef95601890afd80709';

Cart::update($rowId, 2); // Will update the quantity
```

If you want to update more attributes of the item, you can either pass the update method an array as the second parameter. This way you can update all information of the item with the given rowId.

```php
Cart::update($rowId, ['name' => 'Product 1']); // Will update the name

```

### Cart::remove()

To remove an item for the cart, you'll again need the rowId. This rowId you simply pass to the `remove()` method and it will remove the item from the cart.

```php
$rowId = 'da39a3ee5e6b4b0d3255bfef95601890afd80709';

Cart::remove($rowId);
```

### Cart::get()

If you want to get an item from the cart using its rowId, you can simply call the `get()` method on the cart and pass it the rowId.

```php
$rowId = 'da39a3ee5e6b4b0d3255bfef95601890afd80709';

Cart::get($rowId);
```

### Cart::content()

Of course you also want to get the carts content. This is where you'll use the `content` method. This method will return a Collection of CartItems which you can iterate over and show the content to your customers.

```php
Cart::content();
```

This method will return the content of the current cart session, if you want the content of another session, simply chain the calls.

```php
Cart::session('wishlist')->content();
```

### Cart::destroy()

If you want to completely remove the content of a cart, you can call the destroy method on the cart. This will remove all CartItems from the cart for the current cart session.

```php
Cart::destroy();
```

### Cart::total()

The `total()` method can be used to get the calculated total of all items in the cart, given there price and quantity.

```php
Cart::total();
```

The method will automatically format the result, which you can tweak using the three optional parameters

```php
Cart::total($decimals, $decimalSeperator, $thousandSeperator);
```

You can set the default number format in the config file.

### Cart::tax()

The `tax()` method can be used to get the calculated amount of tax for all items in the cart, given there price and quantity.

```php
Cart::tax();
```

The method will automatically format the result, which you can tweak using the three optional parameters

```php
Cart::tax($decimals, $decimalSeperator, $thousandSeperator);
```

You can set the default number format in the config file.

### Cart::subtotal()

The `subtotal()` method can be used to get the total of all items in the cart, minus the total amount of tax. 

```php
Cart::subtotal();
```

The method will automatically format the result, which you can tweak using the three optional parameters

```php
Cart::subtotal($decimals, $decimalSeperator, $thousandSeperator);
```

You can set the default number format in the config file.

### Cart::count()

If you want to know how many items there are in your cart, you can use the `count()` method. This method will return the total number of items in the cart. So if you've added 2 books and 1 shirt, it will return 3 items.

```php
Cart::count();
```

### Cart::search()

To find an item in the cart, you can use the `search()` method.

Behind the scenes, the method simply uses the filter method of the Laravel Collection class. This means you must pass it a Closure in which you'll specify you search terms.

If you for instance want to find all items with an id of 1:

```php
$cart->search(function ($cartItem, $rowId) {
	return $cartItem->id === 1;
});
```

As you can see the Closure will receive two parameters. The first is the CartItem to perform the check against. The second parameter is the rowId of this CartItem.

**The method will return a Collection containing all CartItems that where found**

This way of searching gives you total control over the search process and gives you the ability to create very precise and specific searches.

## Collections

On multiple instances the Cart will return to you a Collection. This is just a simple Laravel Collection, so all methods you can call on a Laravel Collection are also available on the result.

As an example, you can quicky get the number of unique products in a cart:

```php
Cart::content()->count();
```

Or you can group the content by the id of the products:

```php
Cart::content()->groupBy('id');
```

## Session

The packages supports multiple sessions of the cart. The way this works is like this:

You can set the current session of the cart by calling `Cart::session('newSession')`. From this moment, the active session of the cart will be `newSession`, so when you add, remove or get the content of the cart, you're work with the `newSession` session of the cart.
If you want to switch sessions, you just call `Cart::session('otherSession')` again, and you're working with the `otherSession` again.

So a little example:

```php
Cart::session('shopping')->add('192ao12', 'Product 1', 1, 9.99);

// Get the content of the 'shopping' cart
Cart::content();

Cart::session('wishlist')->add('sdjk922', 'Product 2', 1, 19.95, ['size' => 'medium']);

// Get the content of the 'wishlist' cart
Cart::content();

// If you want to get the content of the 'shopping' cart again
Cart::session('shopping')->content();

// And the count of the 'wishlist' cart again
Cart::session('wishlist')->count();
```

**N.B. Keep in mind that the cart stays in the last set session for as long as you don't set a different one during script execution.**

**N.B.2 The default cart session is called `cart_items`, so when you're not using sessions,`Cart::content();` is the same as `Cart::session('cart_items')->content()`.**

## Exceptions

The Cart package will throw exceptions if something goes wrong. This way it's easier to debug your code using the Cart package or to handle the error based on the type of exceptions. The Cart packages can throw the following exceptions:

| Exception                    | Reason                                                                             |
| ---------------------------- | ---------------------------------------------------------------------------------- |
| *InvalidItemException* | When the rowId that got passed doesn't exists in the current cart session or any other error |

## Example

Below is a little example of how to list the cart content in a table:

```php

// Add some items in your Controller.
Cart::add('192ao12', 'Product 1', 9.99, 1);
Cart::add('1239ad0', 'Product 2', 5.95, 2, ['size' => 'large']);

// Display the content in a View.
<table>
   	<thead>
       	<tr>
           	<th>Product</th>
           	<th>Qty</th>
           	<th>Price</th>
           	<th>Subtotal</th>
       	</tr>
   	</thead>

   	<tbody>

   		<?php foreach(Cart::content() as $row) :?>

       		<tr>
           		<td>
               		<p><strong><?php echo $row->name; ?></strong></p>
               		<p><?php echo ($row->options->has('size') ? $row->options->size : ''); ?></p>
           		</td>
           		<td><input type="text" value="<?php echo $row->qty; ?>"></td>
           		<td>$<?php echo $row->price; ?></td>
           		<td>$<?php echo $row->total; ?></td>
       		</tr>

	   	<?php endforeach;?>

   	</tbody>
   	
   	<tfoot>
   		<tr>
   			<td colspan="2">&nbsp;</td>
   			<td>Subtotal</td>
   			<td><?php echo Cart::subtotal(); ?></td>
   		</tr>
   		<tr>
   			<td colspan="2">&nbsp;</td>
   			<td>Tax</td>
   			<td><?php echo Cart::tax(); ?></td>
   		</tr>
   		<tr>
   			<td colspan="2">&nbsp;</td>
   			<td>Total</td>
   			<td><?php echo Cart::total(); ?></td>
   		</tr>
   	</tfoot>
</table>
```
