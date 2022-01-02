<?php
/**
 * Nama program   : Bot Pendaftaran Sederhana
 * Deskripsi      : Bot ini semacam formulir interaktif untuk membantu proses pendaftaran. Data pendaftar disimpan di dalam file json. Bot ini dirancang untuk tidak memerlukan mesin tambahan selain PHP saja.
 * Pengembang     : Danns Bass
 * Email          : dannsbass@gmail.com
 * Github         : https://github.com/dannsbass
 * Versi          : 1.0
 * Rilis          : 29 September 2021
 * Dependensi     : PHPTelebot, SleekDB
 * */

#######################[ PEMASANGAN ]#######################
require_once(__DIR__.'/botdaftar/vendor/autoload.php');
require_once(__DIR__.'/botdaftar/src/Tolong.php');

# sesuaikan dengan token dan username bot kamu
$token_bot = '1302787991:AAHDiQVIl8PK0OLBCnBvpYgu2S_yweperdQ';
$username_bot = 'RinggitRupiahBot';

# direktori untuk menyimpan data kamu (bisa diubah kalau mau atau biarkan saja)
$gudang = 'gudang';
$lemari = 'lemari';

# draft formulir yang akan dikirim ke user (bisa diubah isinya sesuai kebutuhan)
$draft = [
    'JumlahRinggit' => 'Berapa Ringgit yang mau ditukar?',
    'RateMbakRetno' => 'Berapa rate Mbak Retno?',
    'RateKamu' => 'Berapa rate kamu?',
    #'Sumber info' => 'Dari mana kamu tau bot ini?',
];

# biarkan saja ini
$bot = new PHPTelebot($token_bot,$username_bot);
$store = new \SleekDB\Store($lemari,$gudang);
$tolong = new Tolong($store);

#######################[ PEMROSESAN ]#######################
# jika ada pesan teks dikirim oleh user
$bot->on('text',function()use($store,$draft,$tolong){

  $idku = [685631733,1231968913];
  if(!in_array(Bot::message()['from']['id'], $idku)) return;
  
  # penyesuaian pendaftar baru
  $tolong->sesuaikanPenggunaBaru($draft,$store);
  # ambil properti pesan telegram
  $msg = Bot::message();
  # ambil id user
  $id = $msg['from']['id'];
  # ambil isi pesan yang dikirim
  $pesan = $msg['text'];
  # pengambilan data user dari database
  $data_user = $tolong->ambilDataUser();
  
  # kalau user mengirim /start
  if($pesan == '/start'){
    # kosongkan data sebelumnya kalau ada
    Tolong::ulangi($data_user,$draft,$store);
    # kirim pesan ke user, ambil item pertama dari draft formulir
    return Bot::sendMessage($draft[array_keys($draft)[0]]);
  }

  # kalau pesan bukan angka
  if(!is_numeric($pesan)) return Bot::sendMessage('Kirim angka saja');
  
  # periksa item formulir yang masih kosong dalam data user
  foreach ($draft as $key=>$value){
    # kalau ada yang kosong (false)
    if($data_user[$key] == false){
      # isi dengan pesan yang dikirim oleh user
      $data_user[$key] = $pesan;
      # update database
      $store->update($data_user);
      # hentikan pemeriksaan
      break;
    }
  }
  
  # ambil draft formulir
  foreach ($draft as $key=>$value){
    # siapkan data user dari database
    $data_user = $tolong->ambilDataUser($id,$store);
    # kalau ada item yang kosong (false)
    if($data_user[$key] == false){
      # kirim item formulir yang sesuai (value)
      return Bot::sendMessage($value);
    }
    
  }
  
  # periksa item terakhir formulir (apakah sudah terisi atau belum)
  $item_terakhir = $data_user[array_keys($data_user)[count($data_user) - 2]]; # dikurangi 2 karena index dimulai dari nol (0) dan item yang terakhir tidak disertakan karena isinya adalah no urut pendaftar yang terisi secara otomatis
  
  # kalau item terakhir sudah terisi dan tidak kosong (false)
  if($item_terakhir !== false){
    
    # biarkan karakter unicode apa adanya
    $data_user = json_decode(json_encode($data_user,JSON_UNESCAPED_UNICODE));
    
    # siapkan pesan balasan untuk dikirim ke user
    $pesan_balasan = "Berikut ini data anda:\n\n";
    # periksa data user
    foreach ($data_user as $key => $value) {
      # manipulasi tampilan pesan balasan
      switch ($key) {
        # ubah tulisan 'id'
        case 'id':
          # menjadi 'ID' (huruf besar)
          $key = 'ID';
          # ubah value-nya menjadi format kode
          $value = "<code>".$value."</code>";
          break;
        # ubah value 'Pendaftar'
        case 'Pendaftar':
          # menjadi format link
          $value = "<a href='tg://user?id=$id'>".$value."</a>";
          break;
        # ubah tulisan '_id'
        case '_id':
          # menjadi 'No. Urut'
          $key = "No. Urut";
          break;
        
        default:
          // terserah...
          break;
      }
      # rangkai / susun / gabungkan jadi satu
      $pesan_balasan .= "✅ $key: $value\n";
    }
    # tambahan saja
    $pesan_balasan .= "\nUntuk mengulang, kirim /start\n\n";

    $data_user = $tolong->ambilDataUser($id,$store);
    
    # (100×3,200)÷3,330
    $hasil_penghitungan = number_format(ceil(((int)$data_user['JumlahRinggit'] * (int)$data_user['RateKamu']) / (int)$data_user['RateMbakRetno']));

    $jumlahRinggit = $data_user['JumlahRinggit'];
    
    $rateKamu = $data_user['RateKamu'];

    $totalRupiah = number_format((int)$jumlahRinggit * (int)$rateKamu);
  
  $rateMbakRetno = number_format($data_user['RateMbakRetno']);

    $pesan_balasan .= "<code>*Rp$totalRupiah* ÷ $rateMbakRetno = *RM$hasil_penghitungan*\n\n";

    $pesan_balasan .= "Kirim ke sini ya mbak\n👇🏻\n6185142012\nBCA\nMarsel Novandi</code>";
    
    # kirim pesan balasan ke user
    return Bot::sendMessage($pesan_balasan,['parse_mode'=>'html']);
  }
});

#######################[ PENGAKTIFAN ]#######################
# jalankan bot
$bot->run();
# selesai 😁
