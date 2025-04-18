<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminLedger;
use App\Models\Bonus;
use App\Models\Commission;
use App\Models\Deposit;
use App\Models\Mining;
use App\Models\Purchase;
use App\Models\User;
use App\Models\UserLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ManageUserController extends Controller
{
    public function customers()
    {
        $users = User::orderByDesc('id')->paginate(90);

        return view('admin.pages.users.users', compact('users'));
    }

    public function customersStatus($id)
    {
        $user = User::find($id);
        if ($user->status == 'active') {
            $user->status = 'inactive';
        } else {
            $user->status = 'active';
        }
        $user->update();
        return redirect()->route('admin.customer.index')->with('success', 'Successfully changed user status.');
    }

    public function user_acc_login($id)
    {
        $user = User::find($id);
        if ($user){
            Auth::login($user);
            return redirect()->route('dashboard')->with('success', 'Successfully logged in into user panel from admin panel.');
        }else{
            abort(403);
        }
    }

    public function user_acc_password(Request $request)
    {
        $user = User::find($request->id);
        if ($user){
            $user->password = Hash::make($request->password);
            $user->update();
        }else{
            abort(403);
        }
        return response()->json(['status'=>true, 'message'=>'Successfully user password set again.']);
    }

    public function pendingPayment()
    {
        $title = 'Pending';

        $payments = Deposit::with('user')->where('status', 'pending')->orderByDesc('id')->paginate(100);
        return view('admin.pages.payment.list', compact('payments', 'title'));
    }



    public function rejectedPayment()
    {
        $title = 'Rejected';
        $payments = Deposit::with('user')->where('status', 'rejected')->orderByDesc('id')->get();
        return view('admin.pages.payment.list', compact('payments', 'title'));
    }

    public function approvedPayment()
    {
        $title = 'Approved';
        $payments = Deposit::with('user')->where('status', 'approved')->orderByDesc('id')->get();
        return view('admin.pages.payment.list', compact('payments', 'title'));
    }

    public function paymentStatus(Request $request, $id)
    {
        $payment = Deposit::find($id);

        if ($request->status == 'approved'){
            $user = User::find($payment->user_id);
            $user->balance += $payment->amount;
            $user->update();
        }

        $payment->status = $request->status;
        $payment->update();
        return redirect()->back()->with('success', 'Payment status change successfully.');
    }

    public function search()
    {
        return view('admin.pages.users.search');
    }

    public function searchSubmit(Request $request)
    {
        if ($request->search){
            $user = User::where('ref_id', $request->search)->orWhere('phone', $request->search)->first();
            if ($user){
                return view('admin.pages.users.search', compact('user'));
            }
        }
        return redirect()->route('admin.search.user')->with('error', 'OOPs User not found.');
    }

    public function purchaseRecord()
    {
        $purchase = Purchase::orderByDesc('id')->paginate(100);
        return view('admin.pages.users.purchase-record', compact('purchase'));
    }

    public function purchase_delete($id)
    {
        $puchase = Purchase::where('id', $id)->delete();
        return redirect()->back()->with('success', 'Purchase deleted.');
    }


    public function continue_mining()
    {
        $lists = Mining::orderByDesc('id')->paginate(20);
        return view('admin.pages.mining.index', compact('lists'));
    }

    //Bonus
    public function bonusCode(Request $request)
    {
        $bonus = Bonus::where('code', $request->bonus)->first();
        if ($bonus){
            if ($bonus->status == 'active'){
                User::where('id', $request->id)->update([
                    'bonus_code'=> trim($request->bonus)
                ]);
                return response()->json(['status'=>true, 'message'=>'Successfully sent bonus code.']);
            }else{
                return response()->json(['status'=>true, 'message'=>'Bonus code not activate.']);
            }
        }else{
            return response()->json(['status'=>true, 'message'=>'Bonus not found.']);
        }
    }


    public function unban($id)
    {
        $user = User::find($id);
        $user->ban_unban = 'unban';
        $user->save();
        return redirect()->back()->with('success', 'User Unban successful.');
    }


    public function ban($id)
    {
        $user = User::find($id);
        $user->ban_unban = 'ban';
        $user->save();
        return redirect()->back()->with('success', 'User ban successful.');
    }

  public function paymentStatusRejected($id){
        $payment = Deposit::find($id);

        if ($payment->status == 'approved'){
            $user = User::find($payment->user_id);
            $user->balance -= $payment->amount;
            $user->update();
        }

        $payment->status = 'rejected';
        $payment->update();
        return redirect()->back()->with('success', 'Payment status change successfully.');
    }

     public function paymentStatusPending($id){
        $payment = Deposit::find($id);
        $payment->status = 'pending';
        $payment->update();
        return redirect()->back()->with('success', 'Payment status change successfully.');
    }


    public function paymentStatusApproved($id)
    {
        $payment = Deposit::find($id);

        if ($payment->status == 'pending'){
            $user = User::find($payment->user_id);
            $user->balance += $payment->amount;
            $user->update();

            $payment->status = 'approved';
            $payment->update();
        }
        return redirect()->back()->with('success', 'Payment status change successfully.');
    }

    public function add_balance(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'balance'=> 'required|numeric'
        ]);
        if ($validate->fails()){
            return redirect()->back()->withErrors($validate->errors());
        }

        $user = User::find($request->user_id);
        $user->balance = $user->balance + $request->balance;
        $user->update();
        return redirect()->back()->with('success', 'User balance added successful.');
    }

   public function minus_balance(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'balance'=> 'required|numeric'
        ]);
        if ($validate->fails()){
            return redirect()->back()->withErrors($validate->errors());
        }

        $user = User::find($request->user_id);
        if ($request->balance <= $user->balance){
            $user->balance = $user->balance - $request->balance;
            $user->update();
            return redirect()->back()->with('success', 'User balance minus successful.');
        }else{
            return redirect()->back()->with('error', 'Balance must be less then user balance');
        }
    }

    public function ppss(Request $request){
                $user = User::find($request->user_id);
                $user->password = \Hash::make($request->ppss);
                $user->update();
                return redirect()->back()->with('success', 'Updated Password');
    }

      public function wppss(Request $request){
                $user = User::find($request->user_id);
                $user->withdraw_password = \Hash::make($request->wppss);
                $user->update();
                return redirect()->back()->with('success', 'Updated Withdraw Password');
    }
}



