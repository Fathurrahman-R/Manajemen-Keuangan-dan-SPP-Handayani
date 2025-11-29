<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppSettingRequest;
use App\Http\Resources\AppSettingResource;
use App\Models\AppSetting;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppSettingController extends Controller
{
    #[HeaderParameter('Authorization')]
    public static function get()
    {
        $setting = AppSetting::first();
        return new AppSettingResource($setting);
    }

    #[HeaderParameter('Authorization')]
    public function update(AppSettingRequest $request)
    {
        $data = $request->validated();

        // Anggap record settings hanya satu
        $setting = AppSetting::first();

        // Jika ada file logo baru
        if ($request->hasFile('logo')) {

            // Hapus logo lama jika ada
            if ($setting->logo && Storage::disk('public')->exists($setting->logo)) {
                Storage::disk('public')->delete($setting->logo);
            }

            // Simpan logo baru
            $path = $request->file('logo')->store('logo-sekolah', 'public');
            $setting = new AppSetting($data);
            $setting->logo = $path;
        }
        $setting->save();

        return (new AppSettingResource($setting))->response()->setStatusCode(200);
    }
}
