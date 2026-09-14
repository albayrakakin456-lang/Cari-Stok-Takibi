<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Doğrulama (Validation) Dil Satırları
    |--------------------------------------------------------------------------
    |
    | Aşağıdaki dil satırları kural sınıfları tarafından kullanılan varsayılan hata
    | mesajlarını içerir. Bu kuralların bazılarının birden çok sürümü vardır.
    |
    */

    'accepted' => ':attribute kabul edilmelidir.',
    'accepted_if' => ':attribute, :other :value olduğunda kabul edilmelidir.',
    'active_url' => ':attribute geçerli bir URL adresi olmalıdır.',
    'after' => ':attribute mutlaka :date tarihinden sonra olmalıdır.',
    'after_or_equal' => ':attribute mutlaka :date tarihinden sonra veya eşit olmalıdır.',
    'alpha' => ':attribute sadece harflerden oluşmalıdır.',
    'alpha_dash' => ':attribute sadece harfler, rakamlar, tire ve alt çizgilerden oluşmalıdır.',
    'alpha_num' => ':attribute sadece harfler ve rakamlar içerebilir.',
    'any_of' => 'Seçilen :attribute geçersiz.',
    'array' => ':attribute mutlaka bir dizi olmalıdır.',
    'ascii' => ':attribute sadece tek baytlık alfasayısal karakterler ve semboller içerebilir.',
    'before' => ':attribute mutlaka :date tarihinden önce olmalıdır.',
    'before_or_equal' => ':attribute mutlaka :date tarihinden önce veya eşit olmalıdır.',
    'between' => [
        'array' => ':attribute mutlaka :min ile :max arasında öğe içermelidir.',
        'file' => ':attribute mutlaka :min ile :max kilobayt arasında olmalıdır.',
        'numeric' => ':attribute mutlaka :min ile :max arasında olmalıdır.',
        'string' => ':attribute mutlaka :min ile :max karakter arasında olmalıdır.',
    ],
    'boolean' => ':attribute alanı doğru (true) veya yanlış (false) olmalıdır.',
    'can' => ':attribute alanı yetkisiz bir değer içeriyor.',
    'confirmed' => ':attribute tekrarı eşleşmiyor.',
    'contains' => ':attribute alanında zorunlu bir değer eksik.',
    'current_password' => 'Mevcut şifre hatalı.',
    'date' => ':attribute geçerli bir tarih olmalıdır.',
    'date_equals' => ':attribute mutlaka :date ile aynı tarihte olmalıdır.',
    'date_format' => ':attribute mutlaka :format biçimiyle eşleşmelidir.',
    'decimal' => ':attribute alanı :decimal ondalık basamağa sahip olmalıdır.',
    'declined' => ':attribute alanı reddedilmelidir.',
    'declined_if' => ':attribute alanı, :other :value olduğunda reddedilmelidir.',
    'different' => ':attribute ile :other birbirinden farklı olmalıdır.',
    'digits' => ':attribute mutlaka :digits basamaklı olmalıdır.',
    'digits_between' => ':attribute mutlaka :min ile :max basamak arasında olmalıdır.',
    'dimensions' => ':attribute geçersiz görsel boyutlarına sahip.',
    'distinct' => ':attribute alanında yinelenen bir değer var.',
    'doesnt_contain' => ':attribute alanı şunları içeremez: :values.',
    'doesnt_end_with' => ':attribute şunlardan biriyle bitemez: :values.',
    'doesnt_start_with' => ':attribute şunlardan biriyle başlayamaz: :values.',
    'email' => ':attribute geçerli bir e-posta adresi olmalıdır.',
    'encoding' => ':attribute alanı :encoding biçiminde kodlanmalıdır.',
    'ends_with' => ':attribute mutlaka şunlardan biriyle bitmelidir: :values.',
    'enum' => 'Seçilen :attribute geçersiz.',
    'exists' => 'Seçilen :attribute geçersiz veya sistemde bulunamadı.',
    'extensions' => ':attribute şu uzantılardan birine sahip olmalıdır: :values.',
    'file' => ':attribute mutlaka bir dosya olmalıdır.',
    'filled' => ':attribute alanı bir değer içermelidir.',
    'gt' => [
        'array' => ':attribute, :value öğeden fazla olmalıdır.',
        'file' => ':attribute, :value kilobayttan büyük olmalıdır.',
        'numeric' => ':attribute, :value sayısından büyük olmalıdır.',
        'string' => ':attribute, :value karakterden uzun olmalıdır.',
    ],
    'gte' => [
        'array' => ':attribute en az :value öğe içermelidir.',
        'file' => ':attribute en az :value kilobayt olmalıdır.',
        'numeric' => ':attribute en az :value veya daha büyük olmalıdır.',
        'string' => ':attribute en az :value karakter olmalıdır.',
    ],
    'hex_color' => ':attribute geçerli bir onaltılık renk kodu olmalıdır.',
    'image' => ':attribute mutlaka bir görsel olmalıdır.',
    'in' => 'Seçilen :attribute geçersiz.',
    'in_array' => ':attribute alanı :other içinde bulunmuyor.',
    'integer' => ':attribute mutlaka bir tam sayı olmalıdır.',
    'ip' => ':attribute geçerli bir IP adresi olmalıdır.',
    'ipv4' => ':attribute geçerli bir IPv4 adresi olmalıdır.',
    'ipv6' => ':attribute geçerli bir IPv6 adresi olmalıdır.',
    'json' => ':attribute geçerli bir JSON dizesi olmalıdır.',
    'list' => ':attribute alanı bir liste olmalıdır.',
    'lowercase' => ':attribute küçük harf olmalıdır.',
    'lt' => [
        'array' => ':attribute, :value öğeden az olmalıdır.',
        'file' => ':attribute, :value kilobayttan küçük olmalıdır.',
        'numeric' => ':attribute, :value sayısından küçük olmalıdır.',
        'string' => ':attribute, :value karakterden kısa olmalıdır.',
    ],
    'lte' => [
        'array' => ':attribute en fazla :value öğe içerebilir.',
        'file' => ':attribute en fazla :value kilobayt olabilir.',
        'numeric' => ':attribute en fazla :value veya daha küçük olmalıdır.',
        'string' => ':attribute en fazla :value karakter olabilir.',
    ],
    'mac_address' => ':attribute geçerli bir MAC adresi olmalıdır.',
    'max' => [
        'array' => ':attribute en fazla :max öğe içerebilir.',
        'file' => ':attribute en fazla :max kilobayt olabilir.',
        'numeric' => ':attribute en fazla :max olabilir.',
        'string' => ':attribute en fazla :max karakter olabilir.',
    ],
    'max_digits' => ':attribute en fazla :max basamağa sahip olabilir.',
    'mimes' => ':attribute mutlaka :values türünde bir dosya olmalıdır.',
    'mimetypes' => ':attribute mutlaka :values türünde bir dosya olmalıdır.',
    'min' => [
        'array' => ':attribute en az :min öğe içermelidir.',
        'file' => ':attribute en az :min kilobayt olmalıdır.',
        'numeric' => ':attribute en az :min olmalıdır.',
        'string' => ':attribute en az :min karakter olmalıdır.',
    ],
    'min_digits' => ':attribute en az :min basamağa sahip olmalıdır.',
    'missing' => ':attribute alanı eksik olmalıdır.',
    'missing_if' => ':other :value olduğunda :attribute alanı eksik olmalıdır.',
    'missing_unless' => ':other :value olmadığı sürece :attribute alanı eksik olmalıdır.',
    'missing_with' => ':values mevcut olduğunda :attribute alanı eksik olmalıdır.',
    'missing_with_all' => ':values mevcut olduğunda :attribute alanı eksik olmalıdır.',
    'multiple_of' => ':attribute mutlaka :value katı olmalıdır.',
    'not_in' => 'Seçilen :attribute geçersiz.',
    'not_regex' => ':attribute biçimi geçersiz.',
    'numeric' => ':attribute bir sayı olmalıdır.',
    'password' => [
        'letters' => ':attribute en az bir harf içermelidir.',
        'mixed' => ':attribute en az bir büyük ve bir küçük harf içermelidir.',
        'numbers' => ':attribute en az bir rakam içermelidir.',
        'symbols' => ':attribute en az bir sembol içermelidir.',
        'uncompromised' => 'Girilen :attribute bir veri sızıntısında görüldü. Lütfen farklı bir :attribute seçin.',
    ],
    'present' => ':attribute alanı mevcut olmalıdır.',
    'present_if' => ':other :value olduğunda :attribute alanı mevcut olmalıdır.',
    'present_unless' => ':other :value olmadığı sürece :attribute alanı mevcut olmalıdır.',
    'present_with' => ':values mevcut olduğunda :attribute alanı mevcut olmalıdır.',
    'present_with_all' => ':values mevcut olduğunda :attribute alanı mevcut olmalıdır.',
    'prohibited' => ':attribute alanına izin verilmiyor.',
    'prohibited_if' => ':other :value olduğunda :attribute alanına izin verilmiyor.',
    'prohibited_unless' => ':other :values içinde olmadığı sürece :attribute alanına izin verilmiyor.',
    'prohibits' => ':attribute alanı :other alanının bulunmasını engelliyor.',
    'regex' => ':attribute biçimi geçersiz.',
    'required' => ':attribute alanı zorunludur.',
    'required_array_keys' => ':attribute alanı şu anahtarları içermelidir: :values.',
    'required_if' => ':attribute alanı, :other :value olduğunda zorunludur.',
    'required_if_accepted' => ':attribute alanı, :other kabul edildiğinde zorunludur.',
    'required_if_declined' => ':attribute alanı, :other reddedildiğinde zorunludur.',
    'required_unless' => ':attribute alanı, :other :values içinde olmadığı sürece zorunludur.',
    'required_with' => ':attribute alanı, :values varken zorunludur.',
    'required_with_all' => ':attribute alanı, :values varken zorunludur.',
    'required_without' => ':attribute alanı, :values yokken zorunludur.',
    'required_without_all' => ':attribute alanı, :values değerlerinin hiçbiri yokken zorunludur.',
    'same' => ':attribute ile :other eşleşmelidir.',
    'size' => [
        'array' => ':attribute mutlaka :size öğe içermelidir.',
        'file' => ':attribute mutlaka :size kilobayt olmalıdır.',
        'numeric' => ':attribute mutlaka :size olmalıdır.',
        'string' => ':attribute mutlaka :size karakter olmalıdır.',
    ],
    'starts_with' => ':attribute mutlaka şunlardan biriyle başlamalıdır: :values.',
    'string' => ':attribute mutlaka bir metin olmalıdır.',
    'timezone' => ':attribute geçerli bir saat dilimi olmalıdır.',
    'unique' => 'Bu :attribute daha önce kaydedilmiş veya zaten kullanımda.',
    'uploaded' => ':attribute yüklenirken bir hata oluştu.',
    'uppercase' => ':attribute büyük harf olmalıdır.',
    'url' => ':attribute geçerli bir bağlantı adresi (URL) olmalıdır.',
    'ulid' => ':attribute geçerli bir ULID olmalıdır.',
    'uuid' => ':attribute geçerli bir UUID olmalıdır.',

    /*
    |--------------------------------------------------------------------------
    | Özelleştirilmiş Doğrulama Dil Satırları
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Özelleştirilmiş Alan (Attribute) İsimleri
    |--------------------------------------------------------------------------
    |
    | Doğrulama mesajlarında ":attribute" yer tutucusu yerine görünecek 
    | Türkçe dostu alan isimleri. Örneğin "password" yerine "Şifre" görünür.
    |
    */

    'attributes' => [
        'name'                  => 'Ad Soyad',
        'email'                 => 'E-posta Adresi',
        'password'              => 'Şifre',
        'password_confirmation' => 'Şifre Tekrarı',
        'current_password'      => 'Mevcut Şifre',
        'phone'                 => 'Telefon Numarası',
        'address'               => 'Adres',
        'type'                  => 'Tür',
        'note'                  => 'Not',
        'balance'               => 'Bakiye',
        'code'                  => 'Stok Kodu',
        'barcode'               => 'Barkod',
        'purchase_price'        => 'Alış Fiyatı',
        'sale_price'            => 'Satış Fiyatı',
        'tax_rate'              => 'KDV Oranı',
        'stock'                 => 'Stok Miktarı',
        'min_stock'             => 'Kritik Stok Sınırı',
        'contact_id'            => 'Cari (Müşteri / Tedarikçi)',
        'product_id'            => 'Ürün',
        'quantity'              => 'Miktar',
        'unit_price'            => 'Birim Fiyat',
        'amount'                => 'Tutar',
        'description'           => 'Açıklama',
        'invoice_number'        => 'Fatura Numarası',
        'is_paid'               => 'Ödendi Durumu',
    ],

];