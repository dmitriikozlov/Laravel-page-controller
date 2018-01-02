<?php

namespace App\Http\Controllers;

use App\User;
use Cmgmyr\Messenger\Models\Participant;
use Cmgmyr\Messenger\Models\Thread;
use Illuminate\Http\Request;

use App\Http\Requests;

use Auth;
use DB;
use Hashids;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $auth = User::query()
            ->with([
                'industryexperiences',

                'educations',
                'institutes',

                'positions',
                'companies',

                'locations',
                'collaborators',
                'bestdescribeds',
                'skills',
                'softwareproficiencies',
                'interests',
                'personalities',
                'abilities',
            ])
            ->whereId(Auth::id())
            ->first();

        return view('page.index', [
            'auth' => $auth,
            'user' => $auth,
        ]);
    }

    public function show(Request $request, $hash_id)
    {
        $auth = Auth::user();
        $user_id = null;

        if($hash_id === null) {
            $id = $auth->id;
        } else {
            $id = Hashids::decode($hash_id);
        }

        $user = User::query()
            ->with([
                'industryexperiences',

                'educations',
                'institutes',

                'positions',
                'companies',

                'locations',
                'collaborators',
                'bestdescribeds',
                'skills',
                'softwareproficiencies',
                'interests',
                'personalities',
                'abilities',
            ])
            ->whereId($id)
            ->first();

        return view('page.show', [
            'auth' => $auth,
            'user' => $user,
        ]);
    }

    public function edit(Request $request)
    {
        $auth = User::query()
            ->with([
                'industryexperiences',

                'educations',
                'institutes',

                'positions',
                'companies',

                'locations',
                'collaborators',
                'bestdescribeds',
                'skills',
                'softwareproficiencies',
                'interests',
                'personalities',
                'abilities',
            ])
            ->whereId(Auth::id())
            ->first();

        return view('page.edit', [
            'auth' => $auth,
            'user' => $auth,
        ]);
    }

    public function save(Request $request)
    {
        $user_id = $request->input('user_id', '');
        $user_id = Hashids::decode($user_id);

        if(!isset($user_id[0]))
            abort(500);

        $user = User::findOrFail($user_id[0]);
        $auth = Auth::user();

        DB::table('save_user')->insert([
            'user_id' => $user->id,
            'saved_user_id' => $auth->id,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function send(Request $request)
    {
        $user_id = $request->input('user_id', '');
        $user_id = Hashids::decode($user_id);

        if(!isset($user_id[0]))
            abort(500);

        $pme = Auth::user();
        $puser = User::findOrFail($user_id[0]);

        $thread = DB::query()
            ->select('t.*')
            ->from('threads AS t')
            ->join('participants AS puser', 'puser.thread_id', '=', 't.id')
            ->join('participants AS pme', 'pme.thread_id', '=', 't.id')
            ->where('puser.user_id', '=', $puser->id)
            ->where('pme.user_id', '=', $pme->id)
            ->first();

        if(!$thread) {
            $thread = new Thread();
            $thread->subject = $pme->first_name . ' ' . $pme->last_name;
            $thread->save();

            $part_me = new Participant();
            $part_me->thread_id = $thread->id;
            $part_me->user_id = $pme->id;
            $part_me->save();

            $part_user = new Participant();
            $part_user->thread_id = $thread->id;
            $part_user->user_id = $puser->id;
            $part_user->save();
        } else {
            $thread = Thread::findOrFail($thread->id);
        }

        $message = new \Cmgmyr\Messenger\Models\Message();
        $message->user_id = $pme->id;
        $message->thread_id = $thread->id;
        $message->body = $request->message;
        $message->save();

        return response()->json([
            'success' => true,
        ]);
    }
}
