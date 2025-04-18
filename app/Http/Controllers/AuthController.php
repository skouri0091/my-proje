<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * التحقق من صحة كود الدعوة
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyInvitationCode(Request $request)
    {
        $request->validate([
            'ref_by' => 'required|string'
        ]);

        // التحقق من وجود الكود في حقل ref_id
        $exists = User::where('ref_id', $request->ref_by)->exists();

        return response()->json([
            'valid' => $exists
        ]);
    }

    /**
     * تسجيل مستخدم جديد
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|unique:users,phone',
            'password' => 'required|min:6',
            'withdraw_password' => 'required|min:6',
            'ref_by' => 'required|exists:users,ref_id' // التحقق من ref_id
        ]);

        // إنشاء المستخدم
        $user = User::create([
            'phone' => $validated['phone'],
            'password' => bcrypt($validated['password']),
            'withdraw_password' => bcrypt($validated['withdraw_password']),
            'ref_id' => \Str::random(10), // كود دعوة فريد
            'referred_by' => $validated['ref_by']
        ]);

        // تسجيل الدخول أو إعادة توجيه
        return redirect()->route('dashboard');
    }
}