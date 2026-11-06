<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use App\Models\Staff;
use App\Http\Requests\LoginRequest;
use App\Services\AuditLogger;

class AuthController extends Controller
{
    private const LOCK_MINUTES = 30;
    private const MAX_ATTEMPT_TIMES = 5;

    private function recordFailedLogin(?Staff $staff, LoginRequest $request, AuditLogger $auditLogger){
        if($staff){  //該当のスタッフがいる
            $updateStaffArray = [];
            $isLocked = false;

            if($staff->last_failed_login_at === null ||
                //前回の失敗が nullまたは30分以上前なら回数を１にする
                $staff->last_failed_login_at->lt(now()->subMinutes(self::LOCK_MINUTES))){
                $updateStaffArray['failed_login_count'] = 1;
            }else{
                //前回の失敗が30分以内なら１増やす
                $updateStaffArray['failed_login_count'] = $staff->failed_login_count + 1;
            }

            //最後に失敗した時刻を入れる
            $updateStaffArray['last_failed_login_at'] = now();

            //５回目の失敗なら30分間ロックする。
            if( $updateStaffArray['failed_login_count'] >= self::MAX_ATTEMPT_TIMES ){
                $updateStaffArray['locked_until'] = now()->addMinutes(self::LOCK_MINUTES);

                $isLocked = true;
            }

            //ログ出力
            $logStatus = $isLocked ? 'login_locked' : 'login_failed';
            $auditLogger->log(action: $logStatus, table: 'staff', targetId: $staff->id, detail: ['login_id' => $request->login_id], ip: $request->ip());

            $staff->update($updateStaffArray);
            return $isLocked;
        }

        //ユーザーが見つからなくてもログを残す
        $auditLogger->log(action: 'login_failed', table: 'staff', detail: ['login_id' => $request->login_id], ip: $request->ip());
        return false;
    }

    public function index(){
        return Inertia::render('Auth/Login');   //resources/js/Pages/Auth/Login.vueを表示する。
    }

    public function login(LoginRequest $request, AuditLogger $auditLogger){
        $loginAttemptStaff = Staff::where('login_id', $request->login_id)->first();

        if($loginAttemptStaff && $loginAttemptStaff->locked_until?->isFuture()){
            //ログインユーザーが存在して、ロック状態の場合
            $auditLogger->log(action: 'login_locked', table: 'staff', targetId: $loginAttemptStaff->id, detail: ['login_id' => $request->login_id], ip: $request->ip());
            return back()->withErrors(['login_id' => 'アカウントがロックされています。しばらくしてから、もう一度お試しください。'])->onlyInput('login_id');
        }

        if(Auth::attempt([ 'login_id' => $request->login_id, 'password' => $request->password ])){
            //ログイン成功
            $auditLogger->log(action: 'login', staffId: $loginAttemptStaff->id, table: 'staff', targetId: $loginAttemptStaff->id, detail: ['login_id' => $request->login_id], ip: $request->ip());

            $loginAttemptStaff->update([
                'failed_login_count' => 0,
                'last_failed_login_at' => null,
                'locked_until' => null,
            ]);

            //セッションを作り直す
            $request->session()->regenerate();
            return redirect()->intended('/');
        }else{
            //ログイン不成功

            //失敗の判定を行う。ログはこの中
            if($this->recordFailedLogin($loginAttemptStaff, $request, $auditLogger)){
                //失敗5回でアカウントがロックされた
                return back()->withErrors(['login_id' => 'アカウントがロックされました。しばらくしてから、もう一度お試しください。'])->onlyInput('login_id');
            }else{
                //失敗5回未満
                return back()->withErrors(['login_id' => 'IDまたはパスワードが違います'])->onlyInput('login_id');
            }
        }
    }

    public function logout(Request $request, AuditLogger $auditLogger){
        $auditLogger->log(action: 'logout', staffId: Auth::id(), table: 'staff', targetId: Auth::id(), ip: $request->ip());
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
