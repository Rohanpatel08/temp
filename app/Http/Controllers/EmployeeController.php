<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Department;
use App\Models\User;
use Log;
use Exception;


class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $emp = $request->user();
        $users = User::all();
        $departments = DB::table('departments')->get();
        foreach ($users as $user) {
            if ($user->name == $emp->name) {
                $user = $user->name;
            }
        }
        $employees = DB::table('employees');
        $employees = Employee::active();
        $totalCount = Employee::count();
        if ($request->search != null) {
            $employees = $employees->where("employees.name", "LIKE", "%$request->search%")
                ->orWhere("employees.email", "LIKE", "%$request->search%");
        }
        if ($request->department != null) {
            $employees = $employees->where("employees.dept_id", $request->department);
        }

        $employees = $employees
                ->select("employees.*", "departments.name as departmentName")
                ->leftJoin("departments", "departments.dept_id", "employees.dept_id")
                ->orderByDesc('id')
                ->paginate(5);
            $employees->appends($request->only(['search', 'department']));
            $totalCount = $employees->total();

        return view('list', ['user' => $user, 'departments' => $departments])->withEmployees($employees)->withTotalCount($totalCount);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $depts = Department::all();
        return view('create', ['depts' => $depts]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $emp = new Employee;
            $depts = Department::all();
            $request->validate(
                [
                    'empName' => 'required|regex:/^[\pL\s\-]+$/u',
                    'empEmail' => 'required|email|unique:employees,email',
                    'empGender' => 'required',
                    'empDate' => 'required|date',
                    'empDept' => 'required|in:1,2,3',
                    'photo' => 'required|image'
                ],
                [
                    'empName.required' => 'Name is required.',
                    'empName.regex' => 'Name must be containing characters.',
                    'empEmail.required' => 'Email is required.',
                    'empEmail.unique' => 'This email is already in use.',
                    'empGender.required' => 'Gender is required.',
                    'empDate.required' => 'Date is required.',
                    'empDate.date' => 'Date must be in dd/mm/yyyy format.',
                    'empDept.required' => 'Department is required.',
                    'empDept.in' => 'Select at least one department.',
                    'empEmail.email' => 'Enter valid email address.',
                    'photo.required' => 'Upload image file for Avatar.',
                    'photo.image' => 'Uploaded file must be an image file.'
                ]
            );
            $imageName = time() . '.' . $request->photo->extension();

            $request->photo->move(public_path('images'), $imageName);

            $emp->name = $request['empName'];
            $emp->email = $request['empEmail'];
            $emp->gender = $request['empGender'];
            $emp->dob = $request['empDate'];
            $emp->dept_id = $request['empDept'];
            $emp->photo = $imageName;

            $emp->save();
            return redirect('/employee')->with('successCreation', 'Employee added successfully.')->with('errorCreation', 'Employee did not add successfully.');

        } catch (ValidationException $e) {
            $error = $e->validator->errors();

            if ($error->has('empName')) {
                $error->add('empName', 'Name must be less then 90 characters.');
            }
            return back()->withErrors($error)->withInput();
        } catch (\Throwable $th) {
            // \Log::error("Something went wrong while creating Employee" . $th->getMessage());
            return back()->with('errorCreation', 'Something went wrong. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $employees = DB::table('employees');
            $employees = $employees
                ->select("employees.*", "departments.name as departmentName")
                ->leftJoin("departments", "departments.dept_id", "employees.dept_id")
                ->find($id);
            return view('view', ['employees' => $employees]);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $depts = Department::all();
        return view('edit')->with('emp', $employee)->with('dept', $depts);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        try {
            $request->validate(
                [
                    'empName' => 'required|regex:/^[\pL\s\-]+$/u',
                    'empEmail' => ['required', 'email', Rule::unique('employees', 'email')->ignore($employee->id)],
                    'empGender' => 'required',
                    'empDate' => 'required|date',
                    'empDept' => 'required|in:1,2,3',
                ],
                [
                    'empName.required' => 'Name is required.',
                    'empName.regex' => 'Name must be containing characters.',
                    'empEmail.required' => 'Email is required.',
                    'empEmail.unique' => 'This email is already in use.',
                    'empGender.required' => 'Gender is required.',
                    'empDate.required' => 'Date is required.',
                    'empDate.date' => 'Date must be in date format.',
                    'empDept.required' => 'Department is required.',
                    'empDept.in' => 'Select at least one department.',
                    'empEmail.email' => 'Enter valid email address.',
                ]
            );

            $employee->name = $request['empName'];
            $employee->email = $request['empEmail'];
            $employee->gender = $request['empGender'];
            $employee->dob = $request['empDate'];
            $employee->dept_id = $request['empDept'];

            if ($request->photo) {
                $request->validate(
                    [
                        'photo' => 'required|image'
                    ],
                    [
                        'photo.required' => 'Upload image file for Avatar.',
                        'photo.image' => 'Uploaded file must be an image file.'
                    ]
                );
                if ($employee->photo) {
                    Storage::delete(public_path('images/' . $employee->photo));
                }
                $imageName = time() . '.' . $request->photo->extension();
                $request->photo->move(public_path('images'), $imageName);
                $employee->photo = $imageName;
            }

            $employee->update();
            return redirect('/employee')->with('successEdit', 'Employee details updated successfully.')->with('errorEdit', 'Employee details did not update successfully.');

        } catch (ValidationException $e) {
            // Handle validation errors
            $error = $e->validator->errors();
            if ($error->has('empName')) {
                $error->add('empName', 'Name must be 90 characters long.');
            }
            return back()->withErrors($error)->withInput();
        } catch (\Throwable $th) {
            // \Log::error('Error occurred during employee update: ' . $th->getMessage());
            // Redirect back with an error message
            return back()->with('errorEdit', 'An unexpected error occurred. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        try {
            if ($employee != null) {
                unlink(public_path('images/' . $employee->photo));
                $employee->delete();
            }
            return redirect()->back()->with('success', 'Employee deleted successfully.')->with('error', 'Employee did not delete.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
