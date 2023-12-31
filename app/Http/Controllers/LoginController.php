<?php

namespace App\Http\Controllers;

use App\Models\Bill_khachhang;
use App\Models\CTHD;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session as FacadesSession;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function index(){
        return view('login', [
            'title' => 'Đăng nhập hệ thống SportswearShop'
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email:filter|max:255',
            'password' => 'required|max:255'
        ]);
    
        $customer = Customer::where('email', $request->input('email'))->first();
    
        if ($customer && Hash::check($request->input('password'), $customer->password)) {
            FacadesSession::put('customerEmail', $customer->email);
            FacadesSession::put('customerName', $customer->name);
            FacadesSession::put('customerId', $customer->id);
            FacadesSession::put('phone', $customer->phone);
            FacadesSession::put('address', $customer->address);
            return redirect('/');
        } else {
            FacadesSession::flash('error', 'Đăng nhập sai, vui lòng thử lại');
            return redirect()->back();
        }
    }

    public function registerAuth(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'email' => 'required|email|max:255|unique:customers',
            'phone' => 'required',
            'password' => 'required|min:6'
        ], [
            'name.required' => 'Vui lòng nhập họ và tên',
            'phone.required' => 'Vui lòng nhập số điện thoại',
            'email.required' => 'Vui lòng nhập địa chỉ email',
            'email.unique' => 'Email đã tồn tại'
        ]);
    
        Customer::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
            'phone' => $validatedData['phone']
        ]);
    
        return redirect()->back()->with('success', 'Đăng ký thành công');
    }
    
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->back();
    }
    public function profile($customerId) {
        $customer = Customer::where('id', $customerId)->get();

        return view('profile', [
            'title' => 'Thông tin tài khoản',
            'customer' => $customer
        ]);
    }

    public function myOrder()
    {
        $customerId = auth()->id();
        $orders = Bill_khachhang::where('customer_id', $customerId)->get();
        return view('my-order', [
            'title' => 'Đơn hàng của tôi',
            'orders' => $orders,
        ]);
    }

    public function myOrderDetail($orderId) {
        return view('my-order-detail', [
            'title' => 'Chi tiết đơn hàng',
            'order' => Bill_khachhang::where('id', $orderId)->first(),
            'cthds' => CTHD::with(['product' => function($query) {
                    $query->select('id', 'name','menu_id', 'image', 'original_price', 'price_sale');}])->where('id', $orderId)->get()
        ]);
    }
}
