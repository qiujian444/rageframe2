<?php
namespace api\modules\v1\controllers;

use Yii;
use api\modules\v1\models\MiniProgramLoginForm;
use common\models\member\MemberInfo;
use common\models\common\AccessToken;
use common\helpers\ArrayHelper;
use common\helpers\ResultDataHelper;
use common\models\member\MemberAuth;
use common\helpers\FileHelper;

/**
 * 小程序
 *
 * Class MiniProgramController
 * @package api\modules\v1\controllers
 */
class MiniProgramController extends \yii\rest\ActiveController
{
    public $modelClass = '';

    /**
     * 小程序SDK
     *
     * @var
     */
    public $miniProgramApp;

    public function init()
    {
        $path = Yii::getAlias('@api') . '/runtime/easywechatLog/miniProgram/' . date('Y-m') . '/';
        FileHelper::mkdirs($path);

        Yii::$app->params['wechatMiniProgramConfig'] = [
            'app_id' => Yii::$app->debris->config('miniprogram_appid'),
            'secret' => Yii::$app->debris->config('miniprogram_secret'),
            // token 和 aes_key 开启消息推送后可见
            // 'token' => '',
            // 'aes_key' => ''
            'response_type' => 'array',
            'log' => [
                'level' > 'debug',
                'file' => $path . date('d') . '.log',
            ],
        ];

        $this->miniProgramApp = Yii::$app->wechat->miniProgram;
    }

    /**
     * 通过 Code 换取 SessionKey
     *
     * @return mixed
     */
    public function actionSessionKey($code)
    {
        if (!$code)
        {
            return ResultDataHelper::apiResult(422, '通信错误,请在微信重新发起请求');
        }

        try
        {
            $oauth = $this->miniProgramApp->auth->session($code);
            // 解析是否接口报错
            $oauth = Yii::$app->debris->analyWechatPortBack($oauth);

            // 缓存数据
            $auth_key = Yii::$app->security->generateRandomString() . '_' . time();
            Yii::$app->cache->set($auth_key, ArrayHelper::toArray($oauth), 7195);

            return [
                'auth_key' => $auth_key // 临时缓存token
            ];
        }
        catch (\Exception $e)
        {
            return ResultDataHelper::apiResult(422, $e->getMessage());
        }
    }

    /**
     * 加密数据进行解密 || 进行登录认证
     *
     * @return array|bool
     * @throws \yii\base\Exception
     */
    public function actionDecode()
    {
        $model = new MiniProgramLoginForm();
        $model->attributes = Yii::$app->request->post();

        if (!$model->validate())
        {
            return ResultDataHelper::apiResult(422, $this->analyErr($model->getFirstErrors()));
        }

        if (!($oauth = Yii::$app->cache->get($model->auth_key)))
        {
            return ResultDataHelper::apiResult(422, 'auth_key已过期');
        }

        $sign = sha1(htmlspecialchars_decode($model->rawData . $oauth['session_key']));
        if ($sign !== $model->signature)
        {
            return ResultDataHelper::apiResult(422, '签名错误');
        }

        $userinfo = $this->miniProgramApp->encryptor->decryptData($oauth['session_key'], $model->iv, $model->encryptedData);
        Yii::$app->cache->delete($model->auth_key);

        // 插入到用户授权表
        if (!($memberAuthInfo = MemberAuth::findOauthClient(MemberAuth::CLIENT_MINI_PROGRAM, $userinfo['openId'])))
        {
            $memberAuth = new MemberAuth();
            $memberAuthInfo = $memberAuth->add([
                'type' => MemberAuth::CLIENT_MINI_PROGRAM,
                'unionid' => isset($userinfo['unionId']) ? $userinfo['unionId'] : '',
                'openid' => $userinfo['openId'],
                'sex' => $userinfo['gender'],
                'nickname' => $userinfo['nickName'],
                'head_portrait' => $userinfo['avatarUrl'],
                'country' => $userinfo['country'],
                'province' => $userinfo['province'],
                'city' => $userinfo['city'],
                'language' => $userinfo['language'],
            ]);
        }

        // TODO 查询自己关联的用户信息并处理自己的登录请求，并返回用户数据
        // TODO 以下代码都可以替换

        // 判断是否有管理信息 数据也可以后续在绑定
        if (!($member = $memberAuthInfo->member))
        {
            $member = new MemberInfo();
            $member->attributes = [
                'sex' => $userinfo['gender'],
                'nickname' => $userinfo['nickName'],
                'head_portrait' => $userinfo['avatarUrl'],
            ];
            $member->save();

            // 关联用户
            $memberAuthInfo->member_id = $member['id'];
            $memberAuthInfo->save();
        }

        // 默认为不刷新token 如果要刷请设置第二个参数为false
        return AccessToken::getAccessToken($member, true);
    }

    /**
     * 通过openid 获取token信息
     *
     * @return array|bool
     * @throws \yii\base\Exception
     */
    public function actionFindTokenByOpenid()
    {
        $openid = Yii::$app->request->post('openid', '');
        // 查询授权信息
        if ($memberAuthInfo = MemberAuth::findOauthClient(MemberAuth::CLIENT_MINI_PROGRAM, $openid))
        {
            if (!empty($memberAuthInfo->member))
            {
                return AccessToken::getAccessToken($memberAuthInfo->member, true);
            }
        }

        return ResultDataHelper::apiResult(422, '找不到用户信息');
    }

    /**
     * 解析错误
     *
     * @param $fistErrors
     * @return string
     */
    public function analyErr($firstErrors)
    {
        return Yii::$app->debris->analyErr($firstErrors);
    }
}