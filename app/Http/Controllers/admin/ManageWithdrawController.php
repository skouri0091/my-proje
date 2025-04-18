<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Api\OnepayController;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminLedger;
use App\Models\User;
use App\Models\UserLedger;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class ManageWithdrawController extends Controller
{
    public function pendingWithdraw ()
    {
        $title = 'Pending';
        $withdraws = Withdrawal::with(['user', 'payment_method'])->where('status', 'pending')->orderByDesc('id')->get();
        return view('admin.pages.withdraw.list', compact('withdraws', 'title'));
    }

    public function rejectedWithdraw()
    {
        $title = 'Rejected';
        $withdraws = Withdrawal::with(['user', 'payment_method'])->where('status', 'rejected')->orderByDesc('id')->get();
        return view('admin.pages.withdraw.list', compact('withdraws', 'title'));
    }

    public function approvedWithdraw()
    {
        $title = 'Approved';
        $withdraws = Withdrawal::with(['user', 'payment_method'])->where('status', 'approved')->orderByDesc('id')->get();
        return view('admin.pages.withdraw.list', compact('withdraws', 'title'));
    }

    public function withdrawStatus(Request $request, $id)
    {
        $withdraw = Withdrawal::find($id);
        if ($request->status == 'rejected'){
            $userRe = User::find($withdraw->user_id);
            $userRe->balance = $userRe->balance + $withdraw->amount;
            $userRe->update();
        }

        $withdraw->status = $request->status;
        $withdraw->update();
        return redirect()->back()->with('success', 'Withdraw status change successfully.');
    }
}
