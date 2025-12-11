<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ItemLelang;
use Illuminate\Support\Facades\DB;

class CloseExpiredAuctions extends Command
{
    // 1. Nama perintah robotnya
    protected $signature = 'lelang:tutup-otomatis';

    // 2. Deskripsi tugas robot
    protected $description = 'Mengecek dan menutup lelang yang waktunya sudah habis secara otomatis';

    public function handle()
    {
        $this->info('Sedang mengecek lelang yang expired...');

        // 3. Cari barang yang status 'open' TAPI waktunya sudah lewat (<= now)
        $expiredItems = ItemLelang::where('status', 'open')
                                  ->where('end_at', '<=', now())
                                  ->get();

        if ($expiredItems->isEmpty()) {
            $this->info('Tidak ada lelang yang perlu ditutup.');
            return;
        }

        // 4. Loop (Proses satu per satu)
        foreach ($expiredItems as $item) {
            
            DB::transaction(function () use ($item) {
                // Cari bid tertinggi
                $highestBid = $item->bids()->orderBy('amount', 'desc')->first();

                // Update Status
                $item->status = 'closed';

                // Tentukan Pemenang (Jika ada)
                if ($highestBid) {
                    $item->winner_id = $highestBid->user_id;
                    $item->current_price = $highestBid->amount;
                    
                    // (Opsional) Di sini nanti bisa kirim Email/Notif ke pemenang
                    // Mail::to($highestBid->user->email)->send(new WinnerNotification($item));
                }

                $item->save();
            });

            $this->info("Berhasil menutup lelang: {$item->name} (Slug: {$item->slug})");
        }

        $this->info('Selesai memproses semua lelang.');
    }
}