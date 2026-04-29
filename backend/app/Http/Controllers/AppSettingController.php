<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppSettingRequest;
use App\Http\Resources\AppSettingResource;
use App\Models\AppSetting;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AppSettingController extends Controller
{
    #[HeaderParameter('Authorization')]
    public static function get()
    {
        $setting = AppSetting::query()
            ->where('branch_id', Auth::user()->branch_id)->first();
//        $setting->logo = asset('storage') . '/' . $setting->logo;
        if (!$setting) {
            throw new HttpResponseException(response([
                'errors' => [
                    'message' => [
                        'setting tidak ditemukan.',
                    ],
                ],
            ], 404));
        }

        return new AppSettingResource($setting);
    }

    #[HeaderParameter('Authorization')]
    public function update(AppSettingRequest $request, int $id)
    {
        $data = $request->validated();

        // Ambil atau buat record settings tunggal
        $setting = AppSetting::findOrFail($id);
        if (!$setting) {
            $setting = new AppSetting();
        }

        // Update field non-file
        foreach ($data as $key => $value) {
            if ($key !== 'logo') {
                $setting->{$key} = $value;
            }
        }

        // Jika ada file logo baru
        if ($request->hasFile('logo')) {
            // Hapus logo lama jika ada
            if ($setting->logo && Storage::disk('public')->exists($setting->logo)) {
                Storage::disk('public')->delete($setting->logo);
            }

            // Simpan logo baru ke disk 'public' -> returns relative path
            $path = $request->file('logo')->store('logo-sekolah', 'public');
            // Simpan relative path ke DB agar konsisten dengan loader (Storage::disk('public')->path(...))
            $setting->logo = $path;
        }

        $setting->branch_id = Auth::user()->branch_id;
        $setting->save();

        return (new AppSettingResource($setting))->response()->setStatusCode(200);
    }
}
