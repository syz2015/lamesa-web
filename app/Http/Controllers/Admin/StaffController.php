<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Restaurant;
use App\Staff;
use App\Role;

use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    function index()
    {
        $admin = Auth::guard('admin')->user();
        $restaurants = $admin->restaurants;

        $roles = Role::all();

        return view('admin.staff', compact('restaurants', 'roles'));
    }

    public function getAll()
    {
        $admin = Auth::guard('admin')->user();
        $restaurants = $admin->restaurants;

        $staffs = [];

        foreach($restaurants as $restaurant) {
            $restStaffs = $restaurant->staffs;

            foreach($restStaffs as $staff) {
                $staffs[] = $staff;
            }
        }

        $record = array();
        $res['data'] = [];

        foreach ($staffs as $staff) {
            $record['id'] = $staff->id;
            $record['name'] = $staff->name;
            $record['restaurant'] = $staff->restaurant->restName;
            $record['role'] = $staff->role->role;
            $record['contact'] = $staff->contact;

            $res['data'][] = $record;
        }
        
        $res['recordsTotal'] = count($staffs);
        $res['recordsFiltered'] = count($staffs);

        return response()->json($res, 200);
    }

    public function getOne(Request $request)
    {
        $res = [];
        $res['status'] = 0;

        if ($request->id) {
            $staff = Staff::find($request->id);
            
            if ($staff) {
                $res['status'] = 1;
                $res['data'] = $staff;
            }
        } else {
            $res['status'] = 1;
            $res['msg'] = "Failed";
        }
        
        return response()->json($res, 200);
    }

    public function submit(Request $request)
    {
        $res = [];
        $res['status'] = 0;
        $res['msg'] = "";

        try {
            if ($request->id) {     // update
                $request->validate([
                    "f_name" => "required",
                    'l_name' => "required",
                    'password' => "required",
                    'restaurant_id' => "required",
                    'role_id' => "required",
                ]);
    
                $staff = Staff::find($request->id);
            } else {                // insert
                $request->validate([
                    "f_name" => "required",
                    'l_name' => "required",
                    'name' => "required|unique:staff",
                    'password' => "required",
                    'restaurant_id' => "required",
                    'role_id' => "required",
                ]);
    
                if ($request->role_id == config('roles.manager')) {
                    $count = Staff::where('restaurant_id', $request->restaurant_id)->where('role_id', $request->role_id)->get()->count();

                    if ($count > 0) {
                        $res['status'] = 0;
                        $res['msg'] = "Manager already exists in this restaurant!";

                        return response()->json($res, 200);
                    }
                }

                $staff = new Staff;
            }
        } catch (\Throwable $th) {
            $res['status'] = 0;
            $res['msg'] = "Validation failed! User with same name exists!";

            return response()->json($res, 200);
        }
        
        $staff->f_name          = $request->f_name;
        $staff->l_name          = $request->l_name;
        $staff->name            = $request->name;
        $staff->password        = Hash::make($request->password);
        $staff->restaurant_id   = $request->restaurant_id;
        $staff->role_id         = $request->role_id;
        $staff->contact     = $request->contact;

        try {
            $staff->save();
        } catch (\Throwable $th) {
            $res['status'] = 0;
            $res['msg'] = "Validation failed! User with same name exists!";

            return response()->json($res, 200);
        }

        $res['status'] = 1;
        $res['msg'] = "success";

        return response()->json($res, 200);
    }

    public function delete(Request $request)
    {
        $staff = Staff::find($request->id);
        $staff->delete();

        $res['status'] = 1;
        $res['msg'] = "success";

        return response()->json($res, 200);
    }
}
