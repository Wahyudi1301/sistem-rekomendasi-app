<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\CartItem;
use App\Models\ServiceCost;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CartController extends Controller
{
    private const MAX_CART_QUANTITY_PER_ITEM = 100;

    public function __construct()
    {
        $this->middleware('auth:customer');
    }

    public function index(): View
    {
        $customer = Auth::guard('customer')->user();
        /** @var \App\Models\Customer $customer */

        $cartItems = $customer->cartItems()->with(['item'])->latest()->get();
        $totalInitialPrice = 0;
        foreach ($cartItems as $cartItem) {
            if ($cartItem->item) {
                $totalInitialPrice += $cartItem->item->price * $cartItem->quantity;
            }
        }

        // Ambil biaya untuk dikirim ke view
        $costShippingOnly = ServiceCost::getCostByName('shipping_delivery_only', 0); // Default 0 jika error
        $costShippingInstallTotal = ServiceCost::getCostByName('shipping_delivery_install', 0); // Default 0 jika error

        // Debugging:
        // Log::info("Cost Shipping Only from DB: " . $costShippingOnly);
        // Log::info("Cost Shipping Install Total from DB: " . $costShippingInstallTotal);

        $viewData = [
            'cartItems' => $cartItems,
            'totalInitialPrice' => $totalInitialPrice,
            'costShippingOnly' => $costShippingOnly,
            'costShippingInstallTotal' => $costShippingInstallTotal,
        ];

        return view('customer.cart.index', $viewData);
    }

    // Metode add, update, remove sebagian besar sama, pastikan validasi merujuk ke item->price jika ada
    // dan item->stock. Untuk singkatnya, saya tidak akan menulis ulang semuanya di sini.
    // Yang penting:
    // - Saat menambah/update, cek $item->price untuk kalkulasi (jika perlu).
    // - Validasi stok $item->stock.

    public function add(Request $request)
    {
        $request->validate([
            'item_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $decodedItemId = Hashids::decode($request->input('item_id'));
        if (empty($decodedItemId)) {
            return back()->with('error', 'Format ID item tidak valid.');
        }
        $itemId = $decodedItemId[0];
        $quantityToAdd = (int) $request->input('quantity');
        $customer = Auth::guard('customer')->user();
        /** @var \App\Models\Customer $customer */

        $item = Item::find($itemId);

        if (!$item) return back()->with('error', 'Item tidak ditemukan.');
        if ($item->status !== 'available') return back()->with('error', 'Item "' . $item->name . '" sedang tidak tersedia.');
        if ($item->stock <= 0) return back()->with('error', 'Stok item "' . $item->name . '" habis.');
        if ($quantityToAdd > $item->stock) return back()->with('error', 'Jumlah (' . $quantityToAdd . ') melebihi stok (' . $item->stock . ') untuk item "' . $item->name . '".');
        if ($quantityToAdd > self::MAX_CART_QUANTITY_PER_ITEM) return back()->with('error', 'Maksimal ' . self::MAX_CART_QUANTITY_PER_ITEM . ' unit per item di keranjang.');


        DB::beginTransaction();
        try {
            $cartItem = $customer->cartItems()->where('item_id', $itemId)->first();

            if ($cartItem) {
                $newQuantity = $cartItem->quantity + $quantityToAdd;
                if ($newQuantity > self::MAX_CART_QUANTITY_PER_ITEM) {
                    DB::rollBack();
                    return back()->with('error', 'Maksimal ' . self::MAX_CART_QUANTITY_PER_ITEM . ' unit per item di keranjang.');
                }
                if ($newQuantity > $item->stock) {
                    DB::rollBack();
                    return back()->with('error', 'Jumlah total di keranjang (' . $newQuantity . ') melebihi stok (' . $item->stock . ') untuk item "' . $item->name . '".');
                }
                $cartItem->quantity = $newQuantity;
                $cartItem->save();
            } else {
                $customer->cartItems()->create([
                    'item_id' => $itemId,
                    'quantity' => $quantityToAdd,
                ]);
            }
            DB::commit();
            return redirect()->back()->with('success', 'Item berhasil ditambahkan ke keranjang!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding item to cart (DB): ' . $e->getMessage(), ['item_id' => $itemId, 'customer_id' => $customer->id]);
            return back()->with('error', 'Gagal menambahkan item ke keranjang. Silakan coba lagi.');
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'cart_item_hashid' => 'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        $decodedCartItemId = Hashids::decode($request->input('cart_item_hashid'));
        if (empty($decodedCartItemId)) {
            if ($request->expectsJson()) return response()->json(['error' => 'Item keranjang tidak valid.'], 400);
            return redirect()->route('customer.cart.index')->with('error', 'Item keranjang tidak valid.');
        }
        $cartItemId = $decodedCartItemId[0];
        $newQuantity = (int) $request->input('quantity');
        $customer = Auth::guard('customer')->user();
        /** @var \App\Models\Customer $customer */

        DB::beginTransaction();
        try {
            $cartItem = $customer->cartItems()->with('item')->findOrFail($cartItemId);

            if ($newQuantity > self::MAX_CART_QUANTITY_PER_ITEM) {
                DB::rollBack();
                if ($request->expectsJson()) return response()->json(['error' => 'Jumlah maksimal per item (' . self::MAX_CART_QUANTITY_PER_ITEM . ') terlampaui.'], 422);
                return redirect()->route('customer.cart.index')->with('error', 'Jumlah maksimal per item (' . self::MAX_CART_QUANTITY_PER_ITEM . ') terlampaui.');
            }

            if (!$cartItem->item) {
                DB::rollBack();
                $cartItem->delete();
                if ($request->expectsJson()) return response()->json(['error' => 'Item asli tidak ditemukan dan telah dihapus dari keranjang.'], 404);
                return redirect()->route('customer.cart.index')->with('warning', 'Item asli tidak ditemukan dan telah dihapus dari keranjang.');
            }
            if ($cartItem->item->stock < $newQuantity) {
                DB::rollBack();
                if ($request->expectsJson()) return response()->json(['error' => 'Jumlah (' . $newQuantity . ') melebihi stok (' . $cartItem->item->stock . ') untuk item "' . $cartItem->item->name . '".'], 422);
                return redirect()->route('customer.cart.index')->with('error', 'Jumlah (' . $newQuantity . ') melebihi stok (' . $cartItem->item->stock . ') untuk item "' . $cartItem->item->name . '".');
            }
            if ($cartItem->item->status !== 'available') {
                DB::rollBack();
                if ($request->expectsJson()) return response()->json(['error' => 'Item "' . $cartItem->item->name . '" sedang tidak tersedia.'], 422);
                return redirect()->route('customer.cart.index')->with('error', 'Item "' . $cartItem->item->name . '" sedang tidak tersedia.');
            }

            $cartItem->quantity = $newQuantity;
            $cartItem->save();
            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Jumlah item diperbarui.']);
            }
            return redirect()->route('customer.cart.index')->with('success', 'Jumlah item diperbarui.');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            if ($request->expectsJson()) return response()->json(['error' => 'Item keranjang tidak ditemukan.'], 404);
            return redirect()->route('customer.cart.index')->with('error', 'Item keranjang tidak ditemukan.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating cart item (DB): ' . $e->getMessage(), ['cart_item_id' => $cartItemId, 'customer_id' => $customer->id]);
            if ($request->expectsJson()) return response()->json(['error' => 'Gagal memperbarui jumlah item.'], 500);
            return redirect()->route('customer.cart.index')->with('error', 'Gagal memperbarui jumlah item.');
        }
    }

    public function remove(Request $request)
    {
        $request->validate([
            'cart_item_hashid' => 'required|string',
        ]);

        $decodedCartItemId = Hashids::decode($request->input('cart_item_hashid'));
        if (empty($decodedCartItemId)) {
            if ($request->expectsJson()) return response()->json(['error' => 'Item keranjang tidak valid.'], 400);
            return redirect()->route('customer.cart.index')->with('error', 'Item keranjang tidak valid.');
        }
        $cartItemId = $decodedCartItemId[0];
        $customer = Auth::guard('customer')->user();
        /** @var \App\Models\Customer $customer */

        try {
            $cartItem = $customer->cartItems()->findOrFail($cartItemId);
            $itemName = optional($cartItem->item)->name ?? 'Item';
            $cartItem->delete();

            if ($request->expectsJson()) {
                return response()->json(['message' => "{$itemName} berhasil dihapus dari keranjang."]);
            }
            return redirect()->route('customer.cart.index')->with('success', "{$itemName} berhasil dihapus dari keranjang.");
        } catch (ModelNotFoundException $e) {
            if ($request->expectsJson()) return response()->json(['error' => 'Item keranjang tidak ditemukan.'], 404);
            return redirect()->route('customer.cart.index')->with('warning', 'Item keranjang sudah tidak ditemukan.');
        } catch (\Exception $e) {
            Log::error('Error removing cart item (DB): ' . $e->getMessage(), ['cart_item_id' => $cartItemId, 'customer_id' => $customer->id]);
            if ($request->expectsJson()) return response()->json(['error' => 'Gagal menghapus item dari keranjang.'], 500);
            return redirect()->route('customer.cart.index')->with('error', 'Gagal menghapus item dari keranjang.');
        }
    }
}
