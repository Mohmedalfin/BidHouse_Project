<?php

namespace App\Http\Controllers;

use App\Models\ItemLelang;
use App\Models\BindsLelang;
use Illuminate\Http\Request;
use App\Http\Requests\ItemStoreRequest; // Request Validasi
use App\Http\Resources\ItemResource;    // Resource Format JSON
use App\Http\Resources\ItemAllResource;    // Resource Format JSON
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function store(ItemStoreRequest $request)
    {
        try {
            // 1. Cek Authorization
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'message' => 'Forbidden Access: Only Admin can create items'
                ], 403);
            }

            // 2. Ambil data validasi
            $validated = $request->validated();

            $baseSlug = Str::slug($validated['name']);

            $randomCode = Str::random(5);

            $validated['slug'] = $baseSlug . '-' . $randomCode;

            // 3. Set Logic Harga
            $validated['current_price'] = $validated['initial_price'];

            // 4. Proses Insert
            // Data $validated sekarang sudah berisi: name, desc, price, image, end_at, DAN SLUG.
            $item = $request->user()->items()->create($validated);

            // 5. Return Sukses
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

            // 4. Return menggunakan Resource
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
            // 1. CEK OTORITAS ADMIN
            // User biasa dilarang edit barang
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'message' => 'Akses ditolak. Hanya Admin yang boleh mengubah data.'
                ], 403);
            }

            // 2. VALIDASI INPUT (Partial Update)
            // Kita gunakan validasi manual di sini agar ringkas
            // 'sometimes' artinya validasi jalan CUMA KALAU datanya dikirim
            $validated = $request->validate([
                'name'          => 'sometimes|string|max:255',
                'description'   => 'sometimes|string',
                'initial_price' => 'sometimes|integer',
                'image'         => 'sometimes|string',
                'end_at'        => 'sometimes|date|after:now',
            ]);

            // Catatan: Kita TIDAK mengupdate 'slug' agar URL tidak berubah (link tetap aman)
            // Kita juga TIDAK mengupdate 'current_price' karena itu urusan sistem bidding

            // 3. EKSEKUSI UPDATE
            $item->update($validated);

            // 4. RETURN HASIL UPDATE
            // Gunakan ItemResource (Detail) agar admin bisa lihat perubahannya
            return ItemResource::make($item)->additional([
                'status'  => 'success',
                'message' => 'Data barang berhasil diperbarui'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate barang',
                'error' => $e->getMessage()
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
            // 1. CEK OTORITAS ADMIN
            if ($request->user()->role !== 'admin') {
                return response()->json([
                    'message' => 'Akses ditolak. Hanya Admin yang boleh menghapus barang.'
                ], 403);
            }

            // 2. CEK STATUS (Opsional)
            // Mencegah admin menghapus barang yang sudah ada pemenangnya (biar data aman)
            // Hapus blok if ini jika admin bebas menghapus kapan saja.
            if ($item->status === 'closed' && $item->winner_id !== null) {
                return response()->json([
                    'message' => 'Barang yang sudah laku terjual tidak boleh dihapus demi arsip data.'
                ], 400);
            }

            // 3. EKSEKUSI HAPUS
            $item->delete(); 
            // Otomatis bid terkait ikut terhapus jika di migrasi Anda set onDelete('cascade')
            // Jika tidak, bid akan tetap ada tapi jadi yatim piatu (orphan).

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
        // 1. Cek Authorization: Hanya Admin yang boleh
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Admin yang boleh menutup lelang.'
            ], 403);
        }

        // 2. Cek Validasi: Jangan tutup lelang yang sudah tutup
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
                
                // A. Cari bid tertinggi (Juara 1)
                $highestBid = $item->bids()->orderBy('amount', 'desc')->first();

                // B. Update status barang jadi 'closed'
                $item->status = 'closed';

                // C. Jika ada yang nawar, catat pemenangnya
                if ($highestBid) {
                    $item->winner_id = $highestBid->user_id;
                    $item->current_price = $highestBid->amount; // Kunci harga akhir
                }

                $item->save();
            });

            // --- PERSIAPAN RESPONSE ---
            
            // Reload data biar relasi winner & user terbaca
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
