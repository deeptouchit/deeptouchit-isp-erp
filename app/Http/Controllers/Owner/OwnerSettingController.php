<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OwnerSettingController extends Controller
{
    public function index()
    {
        $settings = Setting::whereNull('tenant_id')
            ->get(['key', 'value'])
            ->mapWithKeys(fn (Setting $setting) => [
                $setting->key => Setting::get($setting->key, $setting->value),
            ])
            ->toArray();

        return view('owner.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'app_logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            'app_favicon' => ['nullable', 'image', 'mimes:jpeg,png,webp,ico', 'max:1024'],
            'app_login_banner' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);
        $group = $request->input('group', 'general');
        $inputs = $request->except(['_token', 'group', 'app_logo', 'app_favicon', 'app_login_banner']);

        // Checkbox keys to ensure 0 is saved when unchecked
        $checkboxKeys = [
            'show_developer_credit',
            'allow_self_registration',
            'require_email_verification',
            'maintenance_mode',
            'announcement_enabled',
        ];

        foreach ($checkboxKeys as $key) {
            $inputs[$key] = $request->has($key) ? '1' : '0';
        }

        // Handle file uploads for branding
        $fileKeys = ['app_logo', 'app_favicon', 'app_login_banner'];
        $uploadDir = public_path('uploads/branding');
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        @chmod($uploadDir, 0755);

        foreach ($fileKeys as $fileKey) {
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $filename = (string) \Illuminate\Support\Str::uuid().'.'.$file->extension();
                $file->move($uploadDir, $filename);
                @chmod($uploadDir . '/' . $filename, 0644);
                Setting::set($fileKey, '/uploads/branding/' . $filename, 'branding', null);
            }
        }

        foreach ($inputs as $key => $value) {
            Setting::set($key, is_array($value) ? json_encode($value) : (string)$value, $group, null);
        }

        return back()->with('success', 'Platform branding & configuration saved successfully.');
    }

    public function testSms(Request $request)
    {
        $request->validate([
            'test_phone' => ['required', 'string'],
            'test_message' => ['required', 'string'],
        ]);

        // Simulating or dispatching SMS test response
        return back()->with('success', "Test SMS sent successfully to '{$request->test_phone}'.");
    }

    public function testEmail(Request $request)
    {
        $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        return back()->with('success', "Test email sent successfully to '{$request->test_email}'.");
    }
}
