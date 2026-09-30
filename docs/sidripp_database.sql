-- 1. Tabel User
create table "user" (
    "id_user" serial primary key,
    nama varchar(100) not null,
    "jenis_kelamin" varchar(15) not null,
    "username" varchar(50) not null unique,
    "password" varchar(255) not null,
    "role" varchar(20) not null
);

-- 2. Tabel Category
create table "category" (
    "id_category" serial primary key,
    "nama_category" varchar(50) not null
);

-- 3. Tabel Products
create table "products" (
    "id_product" varchar(20) primary key,
    "nama_barang" varchar(150) not null,
    "harga_beli" integer not null,
    "harga_jual" integer not null,
    "stok_aktual" integer not null default 0,
    "category_id_category" integer not null,
    foreign key ("category_id_category") references "category"("id_category") on delete cascade
);

-- 4. Tabel Restocks
create table "restocks" (
    "id_restock" serial primary key,
    "waktu_masuk" timestamp default current_timestamp,
    "nama_vendor" varchar(100) not null,
    "kuantitas" integer not null,
    "product_id_product" varchar(20) not null,
    "user_id_user" integer not null,
    foreign key ("product_id_product") references "products"("id_product") on delete cascade,
    foreign key ("user_id_user") references "user"("id_user") on delete cascade
);

-- 5. Tabel Defects
create table "defects" (
    "id_defect" serial primary key,
    "waktu_lapor" timestamp default current_timestamp,
    "kuantitas" integer not null,
    "keterangan_rusak" varchar(255),
    "product_id_product" varchar(20) not null,
    "user_id_user" integer not null,
    foreign key ("product_id_product") references "products"("id_product") on delete cascade,
    foreign key ("user_id_user") references "user"("id_user") on delete cascade
);

-- 6. Tabel Stock_Opname
create table "stock_opname" (
    "id_opname" serial primary key,
    "waktu_opname" timestamp default current_timestamp,
    "stok_sistem" integer not null,
    "stok_fisik" integer not null,
    "selisih" integer,
    "status_validasi" varchar(255) not null,
    "user_id_user" integer not null,
    "product_id_product" varchar(20) not null,
    foreign key ("product_id_product") references "products"("id_product") on delete cascade,
    foreign key ("user_id_user") references "user"("id_user") on delete cascade
);

-- 7. Tabel Transactions
create table "transactions" (
    "id_transaksi" varchar(30) primary key,
    "waktu_transaksi" timestamp default current_timestamp,
    "total_harga" integer not null,
    "user_id_user" integer not null,
    "nama_pelanggan" varchar(50),
    "kontak_pelanggan" varchar(15),
    "status_pembayaran" smallint default 0,
    "jatuh_tempo" date,
    "diskon" integer,
    foreign key ("user_id_user") references "user"("id_user") on delete cascade
);

-- 8. Tabel Transaction_Details
create table "transaction_details" (
    "id_detail" serial primary key,
    "kuantitas" integer not null,
    "subtotal_harga" integer not null,
    "transactions_id_transaksi" varchar(30) not null,
    "product_id_product" varchar(20) not null,
    foreign key ("transactions_id_transaksi") references "transactions"("id_transaksi") on delete cascade,
    foreign key ("product_id_product") references "products"("id_product") on delete cascade
);

-- DATA DUMMY
insert into "user" (nama, "jenis_kelamin", "username", "password", "role") values 
('Olivia Rodrigo', 'Perempuan', 'admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1m29PvhYf1rU3v3Q7sA/K8YyO9mQ.Sm', 'super_admin'),
('Asep Tongkang', 'Laki-Laki', 'kasir1', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1m29PvhYf1rU3v3Q7sA/K8YyO9mQ.Sm', 'kasir'),
('Supardi Messi', 'Laki-Laki', 'gudang1', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1m29PvhYf1rU3v3Q7sA/K8YyO9mQ.Sm', 'staf_gudang'),
('Amin Kekebalan', 'Laki-Laki', 'admin2', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1m29PvhYf1rU3v3Q7sA/K8YyO9mQ.Sm', 'admin'),
('Taylor Swift', 'Perempuan', 'kasir2', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1m29PvhYf1rU3v3Q7sA/K8YyO9mQ.Sm', 'kasir');

insert into "category" ("nama_category") values ('Milo'), ('Matcha Powder');

insert into "products" ("id_product", "nama_barang", "harga_beli", "harga_jual", "stok_aktual", "category_id_category") values
('PRD-001', 'Berry Syroup', 18000, 22000, 50, 1),
('PRD-002', 'Matcha Powder', 60000, 72000, 20, 2);