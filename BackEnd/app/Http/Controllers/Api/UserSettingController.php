<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class UserSettingController extends Controller
{
    /**
     * 获取用户设置
     *
     * @param int $id
     * @return void
     * @author Romeo
     */
    public function show(Request $request)
    {
        $id = DB::table('userToken')
                ->where('userToken.token', $request->header('authorization'))
                ->value('uid');

        $userSetting = DB::table('userSetting')
                        ->where('uid', $id)
                        ->first();

        return json_encode($userSetting);
    }

    /**
     * 获取用户设置内容
     * @param  int      $id  用户 ID
     * @return mixed         用户设置内容
     */
    public static function getUserSettings($id)
    {
        $result = DB::table('userSetting')
                    ->where('uid', $id)
                    ->first();

        return $result;
    }

    /**
     * 设置用户设置内容
     * @param int $id 用户 ID
     */
    public function setUserSettings(Request $request)
    {
        $id = DB::table('userToken')
                ->where('userToken.token', $request->header('authorization'))
                ->value('uid');

    	$this->isNotNull(array(
          'newtranslation'  => $request->input('newtranslation'),
          'newarticle'      => $request->input('newarticle'),
          'newreview'       => $request->input('newreview'),
          'newresult'       => $request->input('newresult'),
          'id'              => $id
    		));

    	$data = array(
                'newtranslation'    => $request->input('newtranslation'),
                'newarticle'        => $request->input('newarticle'),
                'newreview'         => $request->input('newreview'),
                'newresult'         => $request->input('newresult'),
                'udate'             => date("Y-m-d H:i:s")
    		);

    	$res = DB::table('userSetting')
    			->where('uid', $id)
    			->update($data);

        if($res === false){
            return response()
                    ->json(['message' => '修改用户设置失败！'], 503);
        }

    }

    /**
     * 添加默认用户设置
     * @param  int $id 用户 ID
     * @return boolean
     */
    public static function setDefaultSettings($id)
    {
        $data = array(
                'uid'               => $id,
                'newtranslation'    => 1,
                'newreview'         => 1,
                'newarticle'        => 1,
                'newresult'         => 1,
                'udate'             => date("Y-m-d H:i:s"),
                'cdate'             => date("Y-m-d H:i:s")
            );

        return DB::table('userSetting')->insert($data);
    }

}
