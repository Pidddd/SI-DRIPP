-- ============================================================================
-- SKEMA DATABASE SI-DRIPP (POSTGRESQL NATIVE / SUPABASE CLOUD)
-- File: docs/sidripp_database.sql
-- Arsitektur: 9 Tabel Relasional (3NF), View, Trigger FEFO, & Fungsi Auto-Expired
-- ============================================================================

-- 1. TABEL MASTER
create table users (
    id_user serial primary key,
    nama varchar(100) not null,
    jenis_kelamin varchar(15) not null,
    username varchar(50) not null unique,
    password varchar(255) not null,
    role varchar(20) not null,
    kode_pemulihan varchar(100) default 'MKP2026'
);

create table category (
    id_category serial primary key,
    nama_category varchar(50) not null
);

create table products (
    id_product varchar(20) primary key,
    nama_barang varchar(150) not null,
    harga_beli integer not null,
    harga_jual integer not null,
    stok_aktual integer not null default 0 check (stok_aktual >= 0),
    categoryid_category integer references category(id_category) on delete cascade
);

-- Revisi Poin 6: Tabel Master Pelanggan / Mitra PT HORECA
create table customers (
    id_customer serial primary key,
    nama_pt varchar(100) not null unique,
    nama_pj varchar(100) not null,
    nik_pj varchar(20) not null,
    no_telp varchar(20) not null,
    alamat_toko text not null,
    created_at timestamp default current_timestamp
);

-- 2. TABEL TRANSAKSI KASIR (POS & PIUTANG TOP)
create table transactions (
    id_transaksi varchar(30) primary key,
    waktu_transaksi timestamp default current_timestamp,
    total_harga integer not null,
    userid_user integer references users(id_user),
    customerid_customer integer references customers(id_customer) on delete set null,
    nama_pelanggan varchar(100),
    kontak_pelanggan varchar(20),
    status_pembayaran smallint not null,
    jatuh_tempo date,
    diskon integer default 0
);

create table transaction_details (
    id_detail serial primary key,
    kuantitas integer not null,
    subtotal_harga integer not null,
    transactionsid_transaksi varchar(30) references transactions(id_transaksi) on delete cascade,
    productsid_product varchar(20) references products(id_product) on delete cascade
);

-- 3. TABEL OPERASIONAL INVENTARIS GUDANG
-- Revisi Poin 3: Mendukung Multi-Batch Expired Date per Produk (3NF)
create table restocks (
    id_restock serial primary key,
    waktu_masuk timestamp default current_timestamp,
    nama_vendor varchar(100) not null,
    kuantitas integer not null,
    kode_batch varchar(40),
    expired_date date,
    sisa_stok_batch integer not null default 0 check (sisa_stok_batch >= 0),
    status_batch varchar(20) default 'aktif',
    productsid_product varchar(20) references products(id_product) on delete cascade,
    userid_user integer references users(id_user)
);

create table defects (
    id_defect serial primary key,
    waktu_lapor timestamp default current_timestamp,
    kuantitas integer not null,
    keterangan_rusak varchar(255),
    productsid_product varchar(20) references products(id_product) on delete cascade,
    userid_user integer references users(id_user)
);

create table stock_opname (
    id_opname serial primary key,
    waktu_opname timestamp default current_timestamp,
    stok_sistem integer not null,
    stok_fisik integer not null,
    selisih integer,
    status_validasi varchar(255) not null,
    userid_user integer references users(id_user),
    productsid_product varchar(20) references products(id_product) on delete cascade
);

-- 4. REQUIREMENT PBL: VIEW DAN JOIN MULTI-TABLE
create or replace view v_laporan_penjualan as
select 
    t.waktu_transaksi,
    t.id_transaksi,
    p.nama_barang,
    td.kuantitas,
    td.subtotal_harga,
    u.nama as kasir
from transaction_details td
join transactions t on td.transactionsid_transaksi = t.id_transaksi
join products p on td.productsid_product = p.id_product
join users u on t.userid_user = u.id_user;

-- 5. REQUIREMENT PBL & REVISI POIN 3: TRIGGER POTONG STOK OTOMATIS & FEFO BATCH
create or replace function kurangi_stok_otomatis()
returns trigger as $$
declare
    sisa_potong integer := new.kuantitas;
    rec_batch record;
    potong_batch integer;
begin
    -- A. Kurangi stok_aktual agregat pada tabel products
    update products
    set stok_aktual = stok_aktual - new.kuantitas
    where id_product = new.productsid_product;

    -- B. Kurangi sisa_stok_batch pada tabel restocks secara FEFO (Expired Date Terdekat)
    for rec_batch in
        select id_restock, sisa_stok_batch
        from restocks
        where productsid_product = new.productsid_product
          and sisa_stok_batch > 0
          and coalesce(status_batch, 'aktif') = 'aktif'
          and (expired_date is null or expired_date >= current_date)
        order by expired_date asc nulls last, waktu_masuk asc, id_restock asc
        for update
    loop
        exit when sisa_potong <= 0;

        potong_batch := least(sisa_potong, rec_batch.sisa_stok_batch);

        update restocks
        set sisa_stok_batch = sisa_stok_batch - potong_batch,
            status_batch = case
                when (sisa_stok_batch - potong_batch) <= 0 then 'habis'
                else 'aktif'
            end
        where id_restock = rec_batch.id_restock;

        sisa_potong := sisa_potong - potong_batch;
    end loop;

    return new;
end;
$$ language plpgsql;

drop trigger if exists trigger_kurangi_stok on transaction_details;
create trigger trigger_kurangi_stok
after insert on transaction_details
for each row
execute function kurangi_stok_otomatis();

-- 6. REVISI POIN 3: FUNGSI OTOMATIS PENARIKAN & POTONG STOK BATCH KEDALUWARSA
create or replace function proses_batch_kedaluwarsa()
returns integer as $$
declare
    rec record;
    total_botol_expired integer := 0;
begin
    for rec in
        select id_restock, productsid_product, kode_batch, expired_date, sisa_stok_batch, userid_user
        from restocks
        where expired_date is not null
          and expired_date < current_date
          and sisa_stok_batch > 0
          and coalesce(status_batch, 'aktif') = 'aktif'
        for update
    loop
        -- 1. Kurangi stok_aktual di tabel products (jaga agar tidak negatif)
        update products
        set stok_aktual = greatest(0, stok_aktual - rec.sisa_stok_batch)
        where id_product = rec.productsid_product;

        -- 2. Catat otomatis ke tabel defects sebagai arsip barang kedaluwarsa
        insert into defects (waktu_lapor, kuantitas, keterangan_rusak, productsid_product, userid_user)
        values (
            current_timestamp,
            rec.sisa_stok_batch,
            'Otomatis Sistem: Batch ' || coalesce(rec.kode_batch, '#' || rec.id_restock::text) || ' melewati tanggal kedaluwarsa (' || to_char(rec.expired_date, 'YYYY-MM-DD') || ')',
            rec.productsid_product,
            rec.userid_user
        );

        -- 3. Tandai batch di tabel restocks menjadi expired dan sisa_stok_batch = 0
        update restocks
        set sisa_stok_batch = 0,
            status_batch = 'expired'
        where id_restock = rec.id_restock;

        total_botol_expired := total_botol_expired + rec.sisa_stok_batch;
    end loop;

    return total_botol_expired;
end;
$$ language plpgsql;
