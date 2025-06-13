<?php

namespace App\Http\Controllers;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Billing;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Razorpay\Api\Api;
use Illuminate\Support\Str;
class ProductController extends Controller
{
    public function index() {                                               
        $products = Product::orderBy('created_at','DESC')->get();

        return view('products.list',[
            'products' => $products
        ]);
    }

    public function create() {
        return view('products.create');
    }


    public function store(Request $request) {
        $rules = [
            'name' => 'required|min:5',
            'sku' => 'required|min:3',
            'price' => 'required|numeric'            
        ];

        if ($request->image != "") {
            $rules['image'] = 'image';
        }

        $validator = Validator::make($request->all(),$rules);

        if ($validator->fails()) {
            return redirect()->route('products.create')->withInput()->withErrors($validator);
        }
      // here we will insert product in db
      $product = new Product();
      $product->name = $request->name;
      $product->sku = $request->sku;
      $product->price = $request->price;
      $product->description = $request->description;
      $product->save();

      if ($request->image != "") {
          // here we will store image
          $image = $request->image;
          $ext = $image->getClientOriginalExtension();
          $imageName = time().'.'.$ext; // Unique image name

          // Save image to products directory
          $image->move(public_path('uploads/products'),$imageName);

          // Save image name in database
          $product->image = $imageName;
          $product->save();
      }        

      return redirect()->route('products.index')->with('success','Product added successfully.');
  }

    // This method will show edit product page
    public function edit($id) {
        $product = Product::findOrFail($id);
        return view('products.edit',[
            'product' => $product
        ]);
    }

    // This method will update a product
    public function update($id, Request $request) {

        $product = Product::findOrFail($id);

        $rules = [
            'name' => 'required|min:5',
            'sku' => 'required|min:3',
            'price' => 'required|numeric'            
        ];

        if ($request->image != "") {
            $rules['image'] = 'image';
        }

        $validator = Validator::make($request->all(),$rules);

        if ($validator->fails()) {
            return redirect()->route('products.edit',$product->id)->withInput()->withErrors($validator);
        }

        // here we will update product
        $product->name = $request->name;
        $product->sku = $request->sku;
        $product->price = $request->price;
        $product->description = $request->description;
        $product->save();

        if ($request->image != "") {

            // delete old image
            File::delete(public_path('uploads/products/'.$product->image));

            // here we will store image
            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = time().'.'.$ext; // Unique image name

            // Save image to products directory
            $image->move(public_path('uploads/products'),$imageName);

            // Save image name in database
            $product->image = $imageName;
            $product->save();
        }        

        return redirect()->route('products.index')->with('success','Product updated successfully.');
    }

    // This method will delete a product
    public function destroy($id) {
        $product = Product::findOrFail($id);

       // delete image
       File::delete(public_path('uploads/products/'.$product->image));

       // delete product from database
       $product->delete();

       return redirect()->route('products.index')->with('success','Product deleted successfully.');
    }

    public function productDetails($id) {
        $product = Product::findOrFail($id);
        return view('products.productDetails',[
            'product' => $product
        ]);
    }

    public function addToCart($id)
    {
        $product = Product::find($id);

        if ($product) {
            // Save to carts table
            $cartItem = Cart::create([
                'prod_id' => $product->id,
                'price' => $product->price,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Store only the cart item ID in session
            $cart = Session::get('cart', []);
            $cart[] = $cartItem->id;
            Session::put('cart', $cart);

            return redirect()->route('products.cart')->with('success', 'Product added to cart!');
        }

        return redirect()->route('products.cart')->with('error', 'Product not found!');
    }

    public function removeFromCart($cartId)
    {
        $cart = Session::get('cart', []);

        if (empty($cart)) {
            return redirect()->route('products.cart')->with('error', 'Your cart is empty!');
        }

        // Remove from session
        $cart = array_filter($cart, function ($id) use ($cartId) {
            return $id != $cartId;
        });

        $cart = array_values($cart); // reindex array
        Session::put('cart', $cart);

        // Remove from DB
        Cart::where('id', $cartId)->delete();

        return redirect()->route('products.cart')->with('success', 'Product removed from cart!');
    }

    public function viewCart()
    {
        $cartIds = Session::get('cart', []);

        // Ensure it's a flat array
        $flatCartIds = array_map(function ($item) {
            return is_array($item) ? ($item['id'] ?? null) : $item;
        }, $cartIds);

        $flatCartIds = array_filter($flatCartIds);

        $cartItems = Cart::with('product')->whereIn('id', $flatCartIds)->get();

        return view('products.cart', ['cart' => $cartItems]);
    }

    public function checkoutForm($id)
    {
        $cartItem = Cart::with('product')->findOrFail($id);
        return view('products.checkout', compact('cartItem'));
    }

   

    public function storeBilling(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'full_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'address' => 'required',
            'payment_mode' => 'required',
        ]);

        $billing = Billing::create($request->all());
        $cart = Cart::with('product')->findOrFail($request->cart_id);
        $invoiceNo = 'INV-' . strtoupper(Str::random(10));
        if ($request->payment_mode == 'offline') {
            Order::create([
                'cart_id' => $cart->id,
                'billing_id' => $billing->id,
                'prod_id' => $cart->prod_id,
                'invoice_no' => $invoiceNo,
                'amount' => $cart->product->price,
                'placed_date' => now(),
                'payment_status' => 'Success',
                'payment_mode' => 'Offline',
            ]);
            Session::forget('cart');
            return redirect()->route('products.success')->with('invoice_no', $invoiceNo);
        }
        
        if ($request->payment_mode == 'online') {
            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
            $order = $api->order->create([
                'receipt' => 'ORD-' . time(),
                'amount' => $cart->product->price * 100, 
                'currency' => 'INR'
            ]);
            
            Session::put('razorpay_order_id', $order['id']);
            Session::put('billing_id', $billing->id);
            Session::put('cart_id', $cart->id);
            Session::put('prod_id', $cart->prod_id);
            Session::put('amount', $cart->product->price);

            return view('products.razorpay_payment', [
                'order_id' => $order['id'],
                'amount' => $cart->product->price * 100,
                'key' => env('RAZORPAY_KEY'),
                'email' => $request->email,
                'name' => $request->full_name
            ]);
        }
    }

    public function razorpayCallback(Request $request)
    {
        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
        $payment = $api->payment->fetch($request->razorpay_payment_id);
        
        if ($payment->status == 'captured') {
            Order::create([
                'cart_id' => Session::get('cart_id'),
                'billing_id' => Session::get('billing_id'),
                'prod_id' => Session::get('prod_id'),
                'invoice_no' => 'INV-' . time(),
                'amount' => Session::get('amount'),
                'placed_date' => now(),
                'payment_status' => 'Paid',
            ]);
            return redirect()->route('products.cart')->with('success', 'Payment successful, order placed!');
        }
        return redirect()->route('products.cart')->with('error', 'Payment failed!');
    }
}