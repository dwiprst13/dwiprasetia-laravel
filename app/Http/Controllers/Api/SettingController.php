<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function show()
    {
        $setting = Setting::query()->first();

        if (! $setting) {
            $setting = Setting::create([
                'site_name' => config('app.name', 'My Blog'),
                'about' => null,
                'site_logo' => null,
            ]);
        }

        return SettingResource::make($setting);
    }

    public function update(UpdateSettingRequest $request)
    {
        $setting = Setting::query()->firstOrCreate([], [
            'site_name' => config('app.name', 'My Blog'),
        ]);

        $data = $request->validated();

        $siteLogoPath = $setting->site_logo;

        if ($request->hasFile('site_logo')) {
            $siteLogoPath = $request->file('site_logo')->store('settings', 'public');

            if ($setting->site_logo) {
                Storage::disk('public')->delete($setting->site_logo);
            }
        }

        $setting->site_name = $data['site_name'];

        if (array_key_exists('about', $data)) {
            $setting->about = $data['about'];
        }

        $setting->site_logo = $siteLogoPath;
        $setting->save();

        return SettingResource::make($setting->fresh());
    }
}
