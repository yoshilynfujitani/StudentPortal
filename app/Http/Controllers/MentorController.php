<?php

namespace App\Http\Controllers;

use App\Models\User;

use Illuminate\Http\Request;
use App\Models\mentorAppointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MentorController extends Controller
{
    //
    public function signUp(Request $request){
   
        if(User::where('email', $request->email)->exists()){
            return;
        }
        $user = new User;

        $user->name = $request->name;
        $user->email = $request->email;
        $user->field = $request->field;
        $user->role = 2;
        $user->password = Hash::make($request->password);
    
        $res = $user->save();

        return $res;
    }
    public function getMentors(Request $request){
        $query = DB::connection('admin')->table('users')
            ->orderBy('created_at')
            ->join('userstatus', 'users.verified', '=', 'userstatus.statusId')
            ->join('userfields', 'users.field', '=', 'userfields.fieldId')
            ->select('users.*', 'userstatus.statusName', 'userfields.fieldName')
            ->where('users.role', 2);
    
        if ($request->fetchMentorBy == "active") {
            return $query->where('users.verified', 1)->get();
        }
    
        if ($request->fetchMentorBy == "pending") {
            return $query->where('users.verified', 0)->get();
        }
    
        if ($request->fetchMentorBy == "all") {
            return $query->get();
        }
    }
    

    public function getMentorsStudent(Request $request){
        $query = DB::connection('admin')->table('users')
        ->join('userfields', 'userfields.fieldId', '=', 'users.field')
        ->where('users.role', 2)
        ->select('users.name', 'users.email', 'users.course', 'userfields.fieldName', 'users.id' );

        if($request->allowedToAppoint == 0){
            return $query->get();
        }
        if($request->allowedToAppoint == 1){
            return $query ->where('users.field', $request->fieldToTake)->get();
        }
        
    
       
    }

    public function getMentorAppointment(){
        return mentorAppointment::where('appointmentdetails.mentorId', Auth::id())
        ->join('appointmentstatus', 'appointmentstatus.statusId', '=', 'appointmentdetails.status')
        ->join(DB::raw('adminportal.users AS users'), 'users.id', '=', 'appointmentdetails.studentId')
        ->select('appointmentdetails.*', 'appointmentstatus.*', 'users.name', 'users.course')
        ->get();
    }

    public function verifyRequest(Request $request){
        $appointment = mentorAppointment::find($request->appointmentId);
        $user = User::find($request->studentId);

        if($request->requestStatus == 1){
            $appointment->Status = 1;
            $user->allowToAppoint = 0;
            $user->fieldToTake = null;
            $user->ticketStatus = null;
            $user->save();
            $appointment->save();

        }

        if($request->requestStatus == 2){
            $appointment->Status = 2;
            $appointment->save();
        }

        return;

    }
  
}
