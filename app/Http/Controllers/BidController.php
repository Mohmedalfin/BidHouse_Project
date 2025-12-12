<?php

namespace App\Http\Controllers;

use App\Models\ItemLelang;
use App\Models\BidsLelang;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\BidStoreRequest; 
use App\Http\Resources\BidResource; 

class BidController extends Controller
{
    /**
     * POST /items/{item}/bid
     */
    public function store(BidStoreRequest $request, ItemLelang $item)
    {
        // User yang sedang login
        $user = $request->user();

        $validated = $request->validated(); 

        try {
            
            // 1. Validasi Kepemilikan
            if ($item->user_id === $user->id) {
                return response()->json(['message' => 'Admin tidak boleh menawar barang sendiri'], 403);
            }

            // 2. Validasi Status
            if ($item->status !== 'open') {
                return response()->json(['message' => 'Lelang sudah ditutup'], 400);
            }

            // 3. Validasi Harga (Logic vs Database)
            if ($validated['amount'] <= $item->current_price) {
                return response()->json([
                    'message' => 'Tawaran harus lebih tinggi dari harga saat ini',
                    'current_price' => $item->current_price
                ], 400);
            }

            // --- EKSEKUSI (Transaction) ---
            $bid = DB::transaction(function () use ($validated, $item, $user) {
                
                // Simpan Bid
                $newBid = BidsLelang::create([
                    'user_id' => $user->id,
                    'item_id' => $item->id,
                    'amount'  => $validated['amount']
                ]);

                // Update Harga Barang
                $item->update([
                    'current_price' => $validated['amount']
                ]);

                return $newBid;
            });

           
            return BidResource::make($bid)->additional([
                'status' => 'success',
                'message' => 'Penawaran berhasil masuk!'
            ])->response()->setStatusCode(201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal melakukan bid',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}