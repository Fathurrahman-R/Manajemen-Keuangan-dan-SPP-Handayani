<?php
foreach(App\Models\User::all() as $u) {
    echo $u->username . ' | ' . $u->email . ' | ' . $u->getRoleNames()->first() . PHP_EOL;
}
