-- 1. tabel master
create table users (
    id_user serial primary key,
    nama varchar(100) not null,
    jenis_kelamin varchar(15) not null,
    username varchar(50) not null unique,
    password varchar(255) not null,
    role varchar(20) not null
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
    stok_aktual integer not null check (stok_aktual >= 0),
    categoryid_category integer references category(id_category) on delete cascade
);

-- 2. tabel transaksi
create table transactions (
    id_transaksi varchar(30) primary key,
    waktu_transaksi timestamp default current_timestamp,
    total_harga integer not null,
    userid_user integer references users(id_user),
    nama_pelanggan varchar(50),
    kontak_pelanggan varchar(15),
    status_pembayaran smallint not null,
    jatuh_tempo date,
    diskon integer
);

create table transaction_details (
    id_detail serial primary key,
    kuantitas integer not null,
    subtotal_harga integer not null,
    transactionsid_transaksi varchar(30) references transactions(id_transaksi) on delete cascade,
    productsid_product varchar(20) references products(id_product) on delete cascade
);

-- 3. tabel inventory
create table restocks (
    id_restock serial primary key,
    waktu_masuk timestamp default current_timestamp,
    nama_vendor varchar(100) not null,
    kuantitas integer not null,
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

-- 4. requirement pbl: view dan join multi-table
create view v_laporan_penjualan as
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

-- 5. requirement pbl: trigger pemotongan stok otomatis
create or replace function kurangi_stok_otomatis()
returns trigger as $$
begin
    update products
    set stok_aktual = stok_aktual - new.kuantitas
    where id_product = new.productsid_product;
    return new;
end;
$$ language plpgsql;

create trigger trigger_kurangi_stok
after insert on transaction_details
for each row
execute function kurangi_stok_otomatis();