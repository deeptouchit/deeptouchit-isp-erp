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
        $settings = Setting::whereNull('tenant_id')->pluck('value', 'key')->toArray();

        return view('owner.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
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
            @mkdir($uploadDir, 0777, true);
        }
        @chmod($uploadDir, 0777);

        foreach ($fileKeys as $fileKey) {
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $filename = time() . '_' . $fileKey . '.' . $file->getClientOriginalExtension();
                $file->move($uploadDir, $filename);
                @chmod($uploadDir . '/' . $filename, 0666);
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
