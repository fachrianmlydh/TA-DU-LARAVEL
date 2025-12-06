<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with(['product', 'customer'])->latest()->paginate(10);
        return view('transactions.index', compact('transactions'));
    }

    public function create()
    {
        $products = Product::where('stock', '>', 0)->get(); // Hanya tampilkan produk yang ada stok
        $customers = Customer::all();
        return view('transactions.create', compact('products', 'customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'customer_id' => 'required|exists:customers,id',
            'quantity' => 'required|integer|min:1',
            'transaction_date' => 'required|date',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Validasi Stok
        if ($product->stock < $request->quantity) {
            return back()->withErrors(['quantity' => 'Stok tidak mencukupi! Stok saat ini: ' . $product->stock]);
        }

        // Hitung Total Harga Otomatis
        $total_price = $product->price * $request->quantity;

        // Simpan Transaksi
        Transaction::create([
            'product_id' => $request->product_id,
            'customer_id' => $request->customer_id,
            'quantity' => $request->quantity,
            'total_price' => $total_price,
            'transaction_date' => $request->transaction_date,
        ]);

        $product->decrement('stock', $request->quantity);

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil!');
    }

    public function destroy(Transaction $transaction)
    {
    
        $transaction->product->increment('stock', $transaction->quantity);

        $transaction->delete();
        return redirect()->route('transactions.index')->with('success', 'Transaksi dibatalkan & stok dikembalikan.');
    }
}
