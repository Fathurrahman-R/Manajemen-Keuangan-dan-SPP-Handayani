<?php

namespace App\Enum;

enum DefaultRoles: string
{
    case SUPERADMIN = 'superadmin';
    case DEVELOPER = 'developer';
    case KEPALA_YAYASAN = 'kepala-yayasan';
    case ADMIN = 'admin';
    case SISWA = 'siswa';
}
