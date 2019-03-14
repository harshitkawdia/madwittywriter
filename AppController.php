<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\User;
use App\User_detail;
use App\role;
use App\College;
use App\Course;
use App\course_api;
use App\Assignments;
use App\enrolled_courses;
use App\Discuss_forum_question;
use App\Discuss_forum_answer;
use App\Discuss_forum_replie;
use App\Message;
use App\Mentor_mail;
use App\contact_us;
use App\project;
use App\Draft_mail;
use App\Quiz_question;
use App\reference_note;
use App\Assign_course;
use Auth;

class AppController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth');
    }

    public function FAQ()
    {
       return view('pages.faq');
    }
    public function Webinar()
    {
       return view('pages.webinar');
    }

    public function Welcome()
    {
        return view('wel');
    }

   
    public function Showdashboard(Request $request)
    {
        return view('pages.index');
    }

     public function generateRandomString() {
         $length = 6;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function searchajax(Request $request)
    {
            $ajaxout = "";
            $results = Discuss_forum_question::where('question','LIKE',"%".$request->search."%")->take(5)->get();
            if(isset($results))
            {
                foreach($results as $result)
                {
                    $ajaxout.= "<a href='". url("/dashboard/discuss-detail/$result->id") ."' class='list-group-item list-group-item-action rounded-0 text-truncate'>".$result->question."</a>";
                }
            }
            return $ajaxout;
    }

    public function TestRoute(Request $request)
    { 
        return view('test.test');
    }
    public function CourseDashboard($id)
    {
        $userid = Auth::user()->id;
        $userRole = Auth::user()->roles->first()->role_name;

        //Student Course
        if($userRole === 'student'){
            $userid = Auth::user()->id;
            $assign_course = enrolled_courses::where('course_id',$id)->where('student_id',$userid)->first();
            if(isset($assign_course)){
                $course = Course::where('id',$id)->first();
                $courseFile = null;
                $courseNotes = [];
                if(Auth::user()->roles->first()->role_name === 'admin' ){
                    if($course->notes()){
                        $courseNotes = $course->notes()->get();
                    }            
                }else if( Auth::user()->roles->first()->role_name === 'student' ){
                    $courseNotes = User_detail::where('u_institute',Auth::user()->profile()->first()->u_institute)->where('u_spec',Auth::user()->profile()->first()->u_spec)->first()->facultyReferenceNotes()->where('course_id',$id)->get();
                }else if( Auth::user()->roles->first()->role_name === 'faculty' ) {
                    $courseNotes = Auth::user()->facultyReferenceNotes()->where('course_id',$id)->get();
                }

                if($course->course_file != null ){
                    $courseFile = $course->course_file;
                }
                return view('coursedashboard.content')->with(compact('course','courseFile','courseNotes'));
            }
            else{
                return view('error.404');
            }
        }

        //Super-admin/Admin Verification for Courses
        elseif($userRole === 'admin'){
            $course = Course::where('id',$id)->first();
            $courseFile = null;
            $courseNotes = [];
            if(Auth::user()->roles->first()->role_name === 'admin' ){
                if($course->notes()){
                    $courseNotes = $course->notes()->get();
                }            
            }else if( Auth::user()->roles->first()->role_name === 'student' ){
                $courseNotes = User_detail::where('u_institute',Auth::user()->profile()->first()->u_institute)->where('u_spec',Auth::user()->profile()->first()->u_spec)->first()->facultyReferenceNotes()->where('course_id',$id)->get();
            }else if( Auth::user()->roles->first()->role_name === 'faculty' ) {
                $courseNotes = Auth::user()->facultyReferenceNotes()->where('course_id',$id)->get();
            }

            if($course->course_file != null ){
                $courseFile = $course->course_file;
            }
            return view('coursedashboard.content')->with(compact('course','courseFile','courseNotes'));
        }

        //SPOC Verification for Courses
        elseif($userRole === 'clg-admin'){
            $userid = Auth::user()->id;
            $college_id = College::where('spoc_id',$userid)->first()->id;
            $getcourseid = Assign_course::where('college_id',$college_id)->where('course_id',$id)->first();
            if($getcourseid){
                $course = Course::where('id',$id)->first();
                $courseFile = null;
                $courseNotes = [];
                if(Auth::user()->roles->first()->role_name === 'admin' ){
                    if($course->notes()){
                        $courseNotes = $course->notes()->get();
                    }            
                }else if( Auth::user()->roles->first()->role_name === 'student' ){
                    $courseNotes = User_detail::where('u_institute',Auth::user()->profile()->first()->u_institute)->where('u_spec',Auth::user()->profile()->first()->u_spec)->first()->facultyReferenceNotes()->where('course_id',$id)->get();
                }else if( Auth::user()->roles->first()->role_name === 'faculty' ) {
                    $courseNotes = Auth::user()->facultyReferenceNotes()->where('course_id',$id)->get();
                }

                if($course->course_file != null ){
                    $courseFile = $course->course_file;
                }
                return view('coursedashboard.content')->with(compact('course','courseFile','courseNotes'));
            }
            else{
               return view('error.404'); 
            }
        
        }
        
    }

    public function Discuss( )
    {
        $questions = Discuss_forum_question::all();
        return view('pages.discuss')->with(compact('questions'));
    } 
    public function showpdf($pdf)
    {
        $courseFile = $pdf;
        return view('pages.showpdf')->with(compact('courseFile'));
    }
    public function DiscussDetail($id)
    {
        $question = Discuss_forum_question::where('id',$id)->first();
        $question_user_pic = $question->user()->first()->profile()->first()->u_profile_picture_path;
        $answers = $question->answers()->get();
        return view('pages.discuss-forum-detail')->with(compact('question','question_user_pic'));
    }

    public function DiscussForumMakeAns(Request $request)
    {
        $request->validate([
            'answer' => 'required',
        ]);
        $answer = new Discuss_forum_answer;
        $answer->user_id = Auth::user()->id;
        $answer->answer_slug = Auth::user()->id.$request->question_id.$this->generateRandomString();
        $answer->question_id = $request->question_id;
        $answer->answer = $request->answer;
        $answer->save();
        return back();
    }
    public function DiscussForumMakeReply(Request $request)
    {
        $request->validate([ 
            'reply' => 'required',
        ]);

        $reply = new Discuss_forum_replie;
        $reply->answer_id = $request->answer_id;
        $reply->reply = $request->reply;
        $reply->user_id = Auth::user()->id;
        if($reply->save()){
            return back();
        }else{
            return "Something went wrong.  Please try again.";
        }
    }

    public function DiscussForumMakeQuiz(Request $request)
    {
        $request->validate([
            'title' => 'required|max:200',
            'description' => 'required',
            ]
         );
        $question = new Discuss_forum_question ;
        $question->question = $request->title;
        $question->question_description = $request->description;
        $question->published_by = Auth::user()->id;
        if($question->save()){
            $request->session()->flash('disscus_quiz_created','Question added successfully.');
        }else{
            $request->session()->flash('disscus_quiz_error','Something went wrong. Please try again.');
        }
        return redirect()->route('discuss');
    }
    public function Profile()
    {
        $user = Auth::user();
        $user_detail = Auth::user()->profile()->first();
        return view('pages.profile')->with(compact('user','user_detail'));
    }
    public function UpdateProfile(Request $request)
    {
        $user = User::where('id',Auth::user()->id)->first();
        $user_detail = User_detail::where('user_id',$user->id)->first();
        $user->name = $request->u_name;
        
        if( isset($request->old_password) && isset($request->new_password) )
        {
            if( bcrypt($request->old_password === $user->password) )
            {
                $user->password = bcrypt($request->u_password);
            }
            else{
                return back();
            }
        }
        if($request->hasfile('u_profile_picture_path'))
        {
            $payload = $request->u_profile_picture_path;
            $user_detail->u_profile_picture_path = $payload->store("/",'profile');
        }
        $user_detail->u_username = $request->u_username;
        $user_detail->u_gender = $request->u_gender;
        $user_detail->u_postal_add = $request->u_postal_add;
        $user_detail->u_bio = $request->u_bio;
        $user_detail->u_linkedin = $request->u_linkedin;
        if($user->save() && $user_detail->save())
        {
            return back();
        }
    }
   
    public function ShowStudentDetail($id)
    {
        $student = User::where('id',$id)->first();
        $profile = $student->profile()->first();
        return view('pages.studentdetailoption')->with(compact('student','profile'));
    }
   
    public function ShowChatRoom()
    {
        $students = Auth::user()->student()->get();
        return view('chatroom.chat')->with(compact('students'));
    }
    public function ShowContactUs()
    {
        $AdminRole = role::where('role_name','=','admin')->first();
        $AdminMail = $AdminRole->users()->first()->email;
        
        return view('pages.contactus')->with(compact('AdminMail','UserRole'));
    }

    public function SendContactUs( Request $request )
    {
        $request->validate([
            'contact_to' => 'required',
            'contact_subject' => 'required | max:50',
            'contact_msg' => 'required',
        ]);
        $AdminRole = role::where('role_name','=','admin')->first();
        $AdminMail = $AdminRole->users()->first()->email;
       $contact_data = new contact_us;
       $contact_data->contact_to = $request->contact_to;
       $contact_data->contact_subject = $request->contact_subject;
       $contact_data->contact_msg = $request->contact_msg;
       $contact_data->user_id = Auth::user()->id;
       if($contact_data->save()){
        $request->session()->flash('msg_send','Your Message has been sent successfuly !');
        return view('pages.contactus')->with(compact('AdminMail'));
       }else{
        return view('pages.contactus')->with(compact('AdminMail'));
       }

    } 

    public function MentorStudents(){
        
        $this->middleware('mentor');

        $students = Auth::user()->student()->get();
        return view('pages.all-student')->with(compact('students'));
    }

    public function SendMessage($sid,$msg)
    {
        $message = new Message; 
        $message->sender_id = $sid;
        $message->receiver_id = Auth::user()->id;
        $message->messages = $msg;
        if($message->save())
        {
            return "sent";
        }
    }

    public function ReceiveMessage($sid)
    {
        $rid = Auth::user()->id;
        $bucket = "";
        $mymessages = [];
        $messages = Message::where(function ($query) use ($sid,$rid) {
            $query->where('sender_id', '=', $sid)
                  ->Where('receiver_id', '=', $rid);
        })->orwhere(function ($query) use ($rid,$sid) {
            $query->where('sender_id', '=', $rid)
                  ->Where('receiver_id', '=', $sid);
        })->get();
        foreach($messages as $message)
        {
            if( $message->receiver_id == Auth::user()->id )
            {
                $bucket.=  "<div class='chat-message clearfix sender'><div class='chat-message-content clearfix'><p>".$message->messages."</p><span class='chat-time'>".$message->updated_at->diffForHumans()."</span></div></div>";
            }
            else
            {
                $bucket.=  "<div class='chat-message clearfix reciever' ><div class='chat-message-content clearfix'><p>". $message->messages."</p><span class='chat-time'>".$message->updated_at->diffForHumans()."</span></div></div>";
            }
        }
        return $bucket;
    }
    public function ShowThisProject($id)
    {
        $project = project::where('id','=',$id)->first();
        $links = explode(',',$project->project_ref_links);
        return view('pages.showprojects')->with(compact('project','links'));
    }
}

