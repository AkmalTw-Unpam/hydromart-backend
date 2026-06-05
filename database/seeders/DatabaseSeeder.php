<?php

namespace Database\Seeders;

use App\Models\{Role, User, Category, Supplier, Item, IncomingGood, OutgoingGood, StockMovement, Notification};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $roles = [
            ['name' => 'admin',   'display_name' => 'Administrator',  'description' => 'Full system access'],
            ['name' => 'manager', 'display_name' => 'Manager Gudang', 'description' => 'Manage inventory & reports'],
            ['name' => 'staff',   'display_name' => 'Staff Gudang',   'description' => 'Input transactions'],
        ];
        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }   

        // Users
        $adminRole   = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $staffRole   = Role::where('name', 'staff')->first();

        $admin = User::updateOrCreate(
            ['email' => 'admin@hydromart.id'],
            [
                'name' => 'Ahmad Fauzi',
                'password' => Hash::make('password'),
                'role_id' => $adminRole->id,
                'phone' => '081234567890',
                'department' => 'IT & System',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@hydromart.id'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'role_id' => $managerRole->id,
                'phone' => '081234567891',
                'department' => 'Manajemen Gudang',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@hydromart.id'],
            [
                'name' => 'Citra Dewi',
                'password' => Hash::make('password'),
                'role_id' => $staffRole->id,
                'phone' => '081234567892',
                'department' => 'Operasional',
                'is_active' => true,
            ]
        );

        // Categories
        $cats = [
            ['name' => 'Pipa & Fitting',     'code' => 'PPF', 'color' => '#0ABFBC'],
            ['name' => 'Pompa & Aksesoris',  'code' => 'PMP', 'color' => '#3B82F6'],
            ['name' => 'Valve & Gate',        'code' => 'VLV', 'color' => '#8B5CF6'],
            ['name' => 'Kabel & Listrik',     'code' => 'KBL', 'color' => '#F59E0B'],
            ['name' => 'Alat Keselamatan',    'code' => 'ALK', 'color' => '#EF4444'],
            ['name' => 'Bahan Kimia',         'code' => 'BKM', 'color' => '#10B981'],
            ['name' => 'Peralatan Bengkel',   'code' => 'PBK', 'color' => '#F97316'],
            ['name' => 'Suku Cadang',         'code' => 'SCK', 'color' => '#EC4899'],
        ];
        foreach ($cats as $c) Category::updateOrCreate(['code' => $c['code']], $c);

        // Suppliers
        $suppliers = [
            ['code' => 'SUP001', 'name' => 'CV. Hidro Jaya',    'contact_person' => 'Pak Jaya',   'phone' => '021-5551234', 'email' => 'jaya@hidrojaya.com',   'city' => 'Jakarta'],
            ['code' => 'SUP002', 'name' => 'PT. Aqua Pump',     'contact_person' => 'Bu Sari',    'phone' => '021-5555678', 'email' => 'sari@aquapump.com',    'city' => 'Surabaya'],
            ['code' => 'SUP003', 'name' => 'UD. Teknik Maju',   'contact_person' => 'Pak Maju',   'phone' => '021-5559012', 'email' => 'maju@teknikmaju.com',  'city' => 'Bandung'],
            ['code' => 'SUP004', 'name' => 'PT. Kabel Prima',   'contact_person' => 'Bu Prima',   'phone' => '021-5553456', 'email' => 'prima@kabelprima.com', 'city' => 'Tangerang'],
            ['code' => 'SUP005', 'name' => 'CV. Safety Nusantara', 'contact_person' => 'Pak Nusa', 'phone' => '021-5557890', 'email' => 'nusa@safetynusa.com', 'city' => 'Depok'],
        ];
        foreach ($suppliers as $s) Supplier::updateOrCreate(['code' => $s['code']], $s);

        $catIds = Category::pluck('id', 'code');
        $supIds = Supplier::pluck('id', 'code');

        // Items
        $items = [
            ['code'=>'PVC-001','name'=>'Pipa PVC 1 Inch','category_id'=>$catIds['PPF'],'supplier_id'=>$supIds['SUP001'],'unit'=>'Meter','stock'=>8,  'min_stock'=>20,'price'=>12000, 'location'=>'Rak A-01'],
            ['code'=>'PVC-002','name'=>'Pipa PVC 2 Inch','category_id'=>$catIds['PPF'],'supplier_id'=>$supIds['SUP001'],'unit'=>'Meter','stock'=>35, 'min_stock'=>20,'price'=>22000, 'location'=>'Rak A-02'],
            ['code'=>'PVC-004','name'=>'Pipa PVC 4 Inch','category_id'=>$catIds['PPF'],'supplier_id'=>$supIds['SUP001'],'unit'=>'Meter','stock'=>0,  'min_stock'=>15,'price'=>45000, 'location'=>'Rak A-04'],
            ['code'=>'FIT-001','name'=>'Elbow PVC 1 Inch','category_id'=>$catIds['PPF'],'supplier_id'=>$supIds['SUP001'],'unit'=>'Pcs','stock'=>120,'min_stock'=>50,'price'=>3500,  'location'=>'Rak A-05'],
            ['code'=>'PMP-001','name'=>'Pompa Submersible 1 HP','category_id'=>$catIds['PMP'],'supplier_id'=>$supIds['SUP002'],'unit'=>'Pcs','stock'=>3,'min_stock'=>2,'price'=>2200000,'location'=>'Rak B-01'],
            ['code'=>'PMP-002','name'=>'Pompa Centrifugal 2 HP','category_id'=>$catIds['PMP'],'supplier_id'=>$supIds['SUP002'],'unit'=>'Pcs','stock'=>1,'min_stock'=>2,'price'=>3500000,'location'=>'Rak B-02'],
            ['code'=>'VLV-001','name'=>'Ball Valve 1/2 Inch','category_id'=>$catIds['VLV'],'supplier_id'=>$supIds['SUP003'],'unit'=>'Pcs','stock'=>45,'min_stock'=>30,'price'=>35000, 'location'=>'Rak C-01'],
            ['code'=>'VLV-003','name'=>'Gate Valve 3 Inch','category_id'=>$catIds['VLV'],'supplier_id'=>$supIds['SUP003'],'unit'=>'Pcs','stock'=>2, 'min_stock'=>10,'price'=>285000,'location'=>'Rak C-03'],
            ['code'=>'KBL-006','name'=>'Kabel NYY 4x6mm','category_id'=>$catIds['KBL'],'supplier_id'=>$supIds['SUP004'],'unit'=>'Meter','stock'=>30,'min_stock'=>50,'price'=>52000, 'location'=>'Rak D-02'],
            ['code'=>'ALK-001','name'=>'Helm Safety','category_id'=>$catIds['ALK'],'supplier_id'=>$supIds['SUP005'],'unit'=>'Pcs','stock'=>12,'min_stock'=>10,'price'=>75000,'location'=>'Rak E-01'],
            ['code'=>'BKM-001','name'=>'Klorin Cair 35%','category_id'=>$catIds['BKM'],'supplier_id'=>$supIds['SUP001'],'unit'=>'Liter','stock'=>4,'min_stock'=>10,'price'=>45000,'location'=>'Gudang Kimia'],
            ['code'=>'PBK-001','name'=>'Kunci Pipa 14 Inch','category_id'=>$catIds['PBK'],'supplier_id'=>$supIds['SUP003'],'unit'=>'Pcs','stock'=>8,'min_stock'=>5,'price'=>185000,'location'=>'Rak F-01'],
        ];
        foreach ($items as $item) Item::updateOrCreate(['code' => $item['code']], $item);

        // --- PERBAIKAN: Bersihkan isi tabel transaksi lama agar tidak bentrok duplikat ---
        Schema::disableForeignKeyConstraints();
        IncomingGood::truncate();
        OutgoingGood::truncate();
        StockMovement::truncate();
        Notification::truncate();
        Schema::enableForeignKeyConstraints();

        // Generate transactions for last 30 days
        $itemModels = Item::all();
        $supplierIds = Supplier::pluck('id')->toArray();
        $users = User::all();
        $refIn = 1000; $refOut = 2000;

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = rand(1, 4);
            for ($j = 0; $j < $count; $j++) {
                $item = $itemModels->random();
                $qty  = rand(5, 50);
                $user = $users->random();
                $stockBefore = $item->stock;
                $item->stock += $qty;
                $item->save();

                IncomingGood::create([
                    'reference_no'   => 'IN-' . str_pad($refIn++, 6, '0', STR_PAD_LEFT),
                    'item_id'        => $item->id,
                    'supplier_id'    => $item->supplier_id,
                    'user_id'        => $user->id,
                    'quantity'       => $qty,
                    'price_per_unit' => $item->price,
                    'transaction_date' => $date->toDateString(),
                    'notes'          => 'Pengadaan rutin',
                ]);

                StockMovement::create([
                    'item_id'      => $item->id,
                    'user_id'      => $user->id,
                    'type'         => 'in',
                    'quantity'     => $qty,
                    'stock_before' => $stockBefore,
                    'stock_after'  => $item->stock,
                    'reference_type' => 'incoming',
                    'notes'        => 'Barang masuk',
                    'created_at'   => $date,
                    'updated_at'   => $date,
                ]);

                $item->refresh();
            }

            $countOut = rand(1, 3);
            for ($j = 0; $j < $countOut; $j++) {
                $item = $itemModels->random();
                $available = $item->stock;
                if ($available < 1) continue;
                $qty  = rand(1, min(20, (int)$available));
                $user = $users->random();
                $stockBefore = $item->stock;
                $item->stock -= $qty;
                $item->save();

                OutgoingGood::create([
                    'reference_no'   => 'OUT-' . str_pad($refOut++, 6, '0', STR_PAD_LEFT),
                    'item_id'        => $item->id,
                    'user_id'        => $user->id,
                    'quantity'       => $qty,
                    'destination'    => collect(['Proyek A','Proyek B','Maintenance','Instalasi','Workshop'])->random(),
                    'purpose'        => 'Kebutuhan operasional',
                    'transaction_date' => $date->toDateString(),
                ]);

                StockMovement::create([
                    'item_id'      => $item->id,
                    'user_id'      => $user->id,
                    'type'         => 'out',
                    'quantity'     => $qty,
                    'stock_before' => $stockBefore,
                    'stock_after'  => $item->stock,
                    'reference_type' => 'outgoing',
                    'notes'        => 'Barang keluar',
                    'created_at'   => $date,
                    'updated_at'   => $date,
                ]);

                $item->refresh();
            }
        }

        // Notifications
        $lowStockItems = Item::where('stock', '<=', \DB::raw('min_stock'))->get();
        foreach ($lowStockItems as $item) {
            Notification::create([
                'user_id' => $admin->id,
                'type'    => 'low_stock',
                'title'   => 'Stok Menipis: ' . $item->name,
                'message' => "Stok {$item->name} saat ini {$item->stock} {$item->unit}, di bawah minimum {$item->min_stock} {$item->unit}.",
                'data'    => ['item_id' => $item->id, 'item_name' => $item->name, 'stock' => $item->stock],
                'is_read' => false,
            ]);
        }
    }
}