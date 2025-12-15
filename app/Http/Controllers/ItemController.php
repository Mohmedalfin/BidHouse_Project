<?php

namespace App\Http\Controllers;

use App\Models\ItemLelang;
use App\Models\BindsLelang;
use Illuminate\Http\Request;
use App\Http\Requests\ItemStoreRequest; 
use App\Http\Resources\ItemResource;   
use App\Http\Resources\ItemAllResource;   
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function store(ItemStoreRequest $request)
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'message' => 'Forbidden Access: Only Admin can create items'
                ], 403);
            }

            $validated = $request->validated();

            $baseSlug = Str::slug($validated['name']);

            $randomCode = Str::random(5);

            $validated['slug'] = $baseSlug . '-' . $randomCode;

            // 3. Set Logic Harga
            $validated['current_price'] = $validated['initial_price'];

            // 4. Proses Insert
            // Data $validated sekarang sudah berisi: name, desc, price, image, end_at, DAN SLUG.
            $item = $request->user()->items()->create($validated);

            return ItemResource::make($item)->additional([
                'message' => 'Barang lelang berhasil ditambahkan',
                'status'  => 'success'
            ])->response()->setStatusCode(201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan pada server',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /items
     * Menampilkan daftar produk (Public Safe Data)
     */
    public function index(Request $request)
    {
        try {
            // 1. Mulai Query Builder (Jangan langsung ->get())
            // Kita siapkan query dasar: hanya ambil yang statusnya 'open'
            $query = ItemLelang::where('status', 'open');

            // 2. Cek apakah ada parameter search di URL?
            // Contoh URL: /api/items?search=macbook
            if ($request->filled('search')) {
                $search = $request->search;

                // Gunakan grouping (tanda kurung) agar logika AND & OR tidak bentrok
                // SQL: WHERE status = 'open' AND (name LIKE %...% OR slug LIKE %...%)
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%");
                });
            }

            // 3. Eksekusi Query (Baru ambil datanya di sini)
            $items = $query->latest()->get();

            return ItemAllResource::collection($items)->additional([
                'status'  => 'success',
                'message' => 'Daftar barang lelang berhasil diambil'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data lelang',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT/PATCH /items/{item}
     * Admin memperbarui data barang
     */
    public function update(Request $request, ItemLelang $item)
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'message' => 'Akses ditolak. Hanya Admin yang boleh mengubah data.'
                ], 403);
            }

            $validated = $request->validate([
                'name'          => 'sometimes|required|string|max:255',
                'description'   => 'sometimes|required|string',
                'initial_price' => 'sometimes|required|integer|min:0',
                'image'         => 'sometimes|required|string',
                'end_at'        => 'sometimes|required|date|after:now',
            ]);

            // Kalau kosong, berarti request body tidak kebaca / field tidak terkirim
            if (count($validated) === 0) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Tidak ada field yang dikirim untuk diupdate. Cek Body Postman (raw JSON) & header Content-Type.',
                ], 422);
            }

            // bypass fillable (biar 100% ke-set kalau fieldnya benar)
            $item->forceFill($validated);

            /// regenerate slug kalau nama berubah
            if (array_key_exists('name', $validated) && $validated['name'] !== $item->getOriginal('name')) {
                $item->slug = Str::slug($validated['name']) . '-' . Str::lower(Str::random(6));
            }

            $item->save();

            return ItemResource::make($item->refresh())->additional([
                'status'  => 'success',
                'message' => 'Data barang berhasil diperbarui',
                // ini bantu buktiin beneran berubah (boleh hapus setelah beres)
                'changed' => $item->wasChanged(),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengupdate barang',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /items/{item}
     * Admin menghapus barang dari sistem
     */
    public function destroy(Request $request, ItemLelang $item)
    {
        try {
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'message' => 'Akses ditolak. Hanya Admin yang boleh menghapus barang.'
                ], 403);
            }


            if ($item->status === 'closed' && $item->winner_id !== null) {
                return response()->json([
                    'message' => 'Barang yang sudah laku terjual tidak boleh dihapus demi arsip data.'
                ], 400);
            }

            $item->delete(); 
    

            return response()->json([
                'status'  => 'success',
                'message' => 'Barang lelang berhasil dihapus dari sistem'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus barang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /items/{item}/close
     * Fitur: Tutup Lelang & Tentukan Pemenang
     */
    public function close(Request $request, ItemLelang $item)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Admin yang boleh menutup lelang.'
            ], 403);
        }

        if ($item->status === 'closed') {
            return response()->json([
                'message' => 'Lelang ini sudah ditutup sebelumnya.',
                'winner'  => $item->winner ? $item->winner->name : 'Belum ada'
            ], 400);
        }

        // --- LOGIKA UTAMA: CARI PEMENANG ---
        try {
            // Gunakan Transaction biar aman
            DB::transaction(function () use ($item) {
                
                $highestBid = $item->bids()->orderBy('amount', 'desc')->first();

                $item->status = 'closed';

                if ($highestBid) {
                    $item->winner_id = $highestBid->user_id;
                    $item->current_price = $highestBid->amount; // Kunci harga akhir
                }

                $item->save();
            });

            $item->load('winner');

            return response()->json([
                'status' => 'success',
                'message' => 'Lelang berhasil ditutup secara resmi.',
                'data' => [
                    'item_name'   => $item->name,
                    'is_sold'     => $item->winner_id ? true : false, // Laku atau tidak?
                    'winner_name' => $item->winner ? $item->winner->name : 'Tidak ada penawar',
                    'final_price' => $item->current_price,
                    'closed_at'   => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menutup lelang',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
