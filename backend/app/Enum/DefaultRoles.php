<?php

namespace App\Enum;

enum DefaultRoles:string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case USER = 'user';
    case SISWA = 'siswa';
}
