<?php

return [

    'accepted' => 'Field :attribute harus diterima.',
    'accepted_if' => 'Field :attribute harus diterima ketika :other adalah :value.',
    'active_url' => 'Field :attribute harus berupa URL yang valid.',
    'after' => 'Field :attribute harus berupa tanggal setelah :date.',
    'after_or_equal' => 'Field :attribute harus berupa tanggal setelah atau sama dengan :date.',
    'alpha' => 'Field :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Field :attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => 'Field :attribute hanya boleh berisi huruf dan angka.',
    'any_of' => 'Field :attribute tidak valid.',
    'array' => 'Field :attribute harus berupa array.',
    'ascii' => 'Field :attribute hanya boleh berisi karakter ASCII.',
    'before' => 'Field :attribute harus berupa tanggal sebelum :date.',
    'before_or_equal' => 'Field :attribute harus berupa tanggal sebelum atau sama dengan :date.',

    'between' => [
        'array' => 'Field :attribute harus memiliki antara :min sampai :max item.',
        'file' => 'Field :attribute harus berukuran antara :min sampai :max kilobyte.',
        'numeric' => 'Field :attribute harus bernilai antara :min sampai :max.',
        'string' => 'Field :attribute harus memiliki panjang antara :min sampai :max karakter.',
    ],

    'boolean' => 'Field :attribute harus bernilai true atau false.',
    'can' => 'Field :attribute mengandung nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi field :attribute tidak cocok.',
    'contains' => 'Field :attribute kehilangan nilai yang diperlukan.',
    'current_password' => 'Password salah.',
    'date' => 'Field :attribute harus berupa tanggal yang valid.',
    'date_equals' => 'Field :attribute harus berupa tanggal yang sama dengan :date.',
    'date_format' => 'Field :attribute harus sesuai dengan format :format.',
    'decimal' => 'Field :attribute harus memiliki :decimal angka di belakang koma.',
    'declined' => 'Field :attribute harus ditolak.',
    'declined_if' => 'Field :attribute harus ditolak ketika :other adalah :value.',
    'different' => 'Field :attribute dan :other harus berbeda.',
    'digits' => 'Field :attribute harus terdiri dari :digits digit.',
    'digits_between' => 'Field :attribute harus memiliki antara :min sampai :max digit.',
    'dimensions' => 'Field :attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => 'Field :attribute memiliki nilai duplikat.',
    'doesnt_contain' => 'Field :attribute tidak boleh mengandung salah satu dari nilai berikut: :values.',
    'doesnt_end_with' => 'Field :attribute tidak boleh diakhiri dengan salah satu dari: :values.',
    'doesnt_start_with' => 'Field :attribute tidak boleh diawali dengan salah satu dari: :values.',
    'email' => 'Field :attribute harus berupa alamat email yang valid.',
    'encoding' => 'Field :attribute harus dikodekan dalam format :encoding.',
    'ends_with' => 'Field :attribute harus diakhiri dengan salah satu dari berikut: :values.',
    'enum' => 'Pilihan :attribute tidak valid.',
    'exists' => 'Pilihan :attribute tidak valid.',
    'extensions' => 'Field :attribute harus memiliki ekstensi berikut: :values.',
    'file' => 'Field :attribute harus berupa file.',
    'filled' => 'Field :attribute harus diisi.',

    'gt' => [
        'array' => 'Field :attribute harus memiliki lebih dari :value item.',
        'file' => 'Field :attribute harus lebih besar dari :value kilobyte.',
        'numeric' => 'Field :attribute harus lebih besar dari :value.',
        'string' => 'Field :attribute harus lebih panjang dari :value karakter.',
    ],

    'gte' => [
        'array' => 'Field :attribute harus memiliki minimal :value item.',
        'file' => 'Field :attribute harus lebih besar atau sama dengan :value kilobyte.',
        'numeric' => 'Field :attribute harus lebih besar atau sama dengan :value.',
        'string' => 'Field :attribute harus lebih panjang atau sama dengan :value karakter.',
    ],

    'hex_color' => 'Field :attribute harus berupa warna heksadesimal yang valid.',
    'image' => 'Field :attribute harus berupa gambar.',
    'in' => 'Pilihan :attribute tidak valid.',
    'in_array' => 'Field :attribute harus ada dalam :other.',
    'in_array_keys' => 'Field :attribute harus berisi salah satu dari key berikut: :values.',
    'integer' => 'Field :attribute harus berupa angka integer.',
    'ip' => 'Field :attribute harus berupa alamat IP yang valid.',
    'ipv4' => 'Field :attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => 'Field :attribute harus berupa alamat IPv6 yang valid.',
    'json' => 'Field :attribute harus berupa string JSON yang valid.',
    'list' => 'Field :attribute harus berupa daftar.',
    'lowercase' => 'Field :attribute harus huruf kecil semua.',

    'lt' => [
        'array' => 'Field :attribute harus memiliki kurang dari :value item.',
        'file' => 'Field :attribute harus kurang dari :value kilobyte.',
        'numeric' => 'Field :attribute harus kurang dari :value.',
        'string' => 'Field :attribute harus lebih pendek dari :value karakter.',
    ],

    'lte' => [
        'array' => 'Field :attribute tidak boleh memiliki lebih dari :value item.',
        'file' => 'Field :attribute harus kurang atau sama dengan :value kilobyte.',
        'numeric' => 'Field :attribute harus kurang atau sama dengan :value.',
        'string' => 'Field :attribute harus lebih pendek atau sama dengan :value karakter.',
    ],

    'mac_address' => 'Field :attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => 'Field :attribute tidak boleh memiliki lebih dari :max item.',
        'file' => 'Field :attribute tidak boleh melebihi :max kilobyte.',
        'numeric' => 'Field :attribute tidak boleh lebih besar dari :max.',
        'string' => 'Field :attribute tidak boleh lebih dari :max karakter.',
    ],

    'max_digits' => 'Field :attribute tidak boleh memiliki lebih dari :max digit.',
    'mimes' => 'Field :attribute harus berupa file dengan tipe: :values.',
    'mimetypes' => 'Field :attribute harus berupa file dengan tipe: :values.',

    'min' => [
        'array' => 'Field :attribute harus memiliki minimal :min item.',
        'file' => 'Field :attribute harus berukuran minimal :min kilobyte.',
        'numeric' => 'Field :attribute harus bernilai minimal :min.',
        'string' => 'Field :attribute harus memiliki minimal :min karakter.',
    ],

    'min_digits' => 'Field :attribute harus memiliki minimal :min digit.',
    'missing' => 'Field :attribute harus kosong.',
    'missing_if' => 'Field :attribute harus kosong ketika :other adalah :value.',
    'missing_unless' => 'Field :attribute harus kosong kecuali :other adalah :value.',
    'missing_with' => 'Field :attribute harus kosong ketika :values ada.',
    'missing_with_all' => 'Field :attribute harus kosong ketika seluruh :values ada.',

    'multiple_of' => 'Field :attribute harus merupakan kelipatan dari :value.',
    'not_in' => 'Pilihan :attribute tidak valid.',
    'not_regex' => 'Format field :attribute tidak valid.',
    'numeric' => 'Field :attribute harus berupa angka.',

    'password' => [
        'letters' => 'Field :attribute harus mengandung minimal satu huruf.',
        'mixed' => 'Field :attribute harus mengandung huruf kapital dan huruf kecil.',
        'numbers' => 'Field :attribute harus mengandung minimal satu angka.',
        'symbols' => 'Field :attribute harus mengandung minimal satu simbol.',
        'uncompromised' => 'Field :attribute ditemukan dalam kebocoran data. Silakan pilih password lain.',
    ],

    'present' => 'Field :attribute harus ada.',
    'present_if' => 'Field :attribute harus ada ketika :other adalah :value.',
    'present_unless' => 'Field :attribute harus ada kecuali :other adalah :value.',
    'present_with' => 'Field :attribute harus ada ketika :values ada.',
    'present_with_all' => 'Field :attribute harus ada ketika semua :values ada.',

    'prohibited' => 'Field :attribute dilarang diisi.',
    'prohibited_if' => 'Field :attribute dilarang diisi ketika :other adalah :value.',
    'prohibited_if_accepted' => 'Field :attribute dilarang diisi ketika :other diterima.',
    'prohibited_if_declined' => 'Field :attribute dilarang diisi ketika :other ditolak.',
    'prohibited_unless' => 'Field :attribute dilarang diisi kecuali :other ada dalam :values.',
    'prohibits' => 'Field :attribute melarang :other untuk ada.',

    'regex' => 'Format field :attribute tidak valid.',
    'required' => 'Field :attribute wajib diisi.',
    'required_array_keys' => 'Field :attribute harus memiliki entri untuk: :values.',
    'required_if' => 'Field :attribute wajib diisi ketika :other adalah :value.',
    'required_if_accepted' => 'Field :attribute wajib diisi ketika :other diterima.',
    'required_if_declined' => 'Field :attribute wajib diisi ketika :other ditolak.',
    'required_unless' => 'Field :attribute wajib diisi kecuali :other ada dalam :values.',
    'required_with' => 'Field :attribute wajib diisi ketika :values ada.',
    'required_with_all' => 'Field :attribute wajib diisi ketika semua :values ada.',
    'required_without' => 'Field :attribute wajib diisi ketika :values tidak ada.',
    'required_without_all' => 'Field :attribute wajib diisi ketika tidak satu pun dari :values ada.',

    'same' => 'Field :attribute harus sama dengan :other.',

    'size' => [
        'array' => 'Field :attribute harus memiliki :size item.',
        'file' => 'Field :attribute harus berukuran :size kilobyte.',
        'numeric' => 'Field :attribute harus bernilai :size.',
        'string' => 'Field :attribute harus memiliki :size karakter.',
    ],

    'starts_with' => 'Field :attribute harus dimulai dengan salah satu dari: :values.',
    'string' => 'Field :attribute harus berupa string.',
    'timezone' => 'Field :attribute harus berupa zona waktu yang valid.',
    'unique' => ':attribute sudah digunakan.',
    'uploaded' => 'Field :attribute gagal diunggah.',
    'uppercase' => 'Field :attribute harus berupa huruf kapital.',
    'url' => 'Field :attribute harus berupa URL yang valid.',
    'ulid' => 'Field :attribute harus berupa ULID yang valid.',
    'uuid' => 'Field :attribute harus berupa UUID yang valid.',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'pesan-khusus',
        ],
    ],

    'attributes' => [
        'npwp'=>'NPWP'
    ],

];
