<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request){
        $emp = Employee::active();
        dd($emp);
        $user = $request->user();
        return view('user')->with('user',$user);
    }
}
