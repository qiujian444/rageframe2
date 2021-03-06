<?php
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = '会员信息';
$this->params['breadcrumbs'][] = ['label' => $this->title];
?>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>会员信息</h5>
                    <div class="ibox-tools">
                        <a class="btn btn-primary btn-xs" href="<?= Url::to(['ajax-edit'])?>" data-toggle='modal' data-target='#ajaxModal'>
                            <i class="fa fa-plus"></i>  创建
                        </a>
                    </div>
                </div>
                <div class="ibox-content">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>头像</th>
                            <th>登录账号</th>
                            <th>真实姓名</th>
                            <th>手机号码</th>
                            <th>邮箱</th>
                            <th>访问次数</th>
                            <th>最后登陆</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($models as $model){ ?>
                            <tr id="<?= $model->id?>">
                                <td><?= $model->id?></td>
                                <td class="feed-element">
                                    <img src="<?php echo \common\helpers\HtmlHelper::headPortrait($model->head_portrait);?>" class="img-circle">
                                </td>
                                <td><?= $model->username?></td>
                                <td><?= $model->realname?></td>
                                <td><?= $model->mobile_phone?></td>
                                <td><?= $model->email?></td>
                                <td><?= $model->visit_count?></td>
                                <td>
                                    IP：<?= $model->last_ip?><br>
                                    最后登录：<?= Yii::$app->formatter->asDatetime($model->last_time)?><br>
                                    注册时间：<?= Yii::$app->formatter->asDatetime($model->created_at)?>
                                </td>
                                <td>
                                    <a href="<?= Url::to(['ajax-edit','id' => $model->id])?>" data-toggle='modal' data-target='#ajaxModal'><span class="btn btn-info btn-sm">账号密码</span></a>
                                    <a href="<?= Url::to(['address/index','member_id' => $model->id])?>"><span class="btn btn-info btn-sm">收货地址</span></a>
                                    <a href="<?= Url::to(['edit','id' => $model->id])?>"><span class="btn btn-info btn-sm">编辑</span></a>
                                    <?php echo \common\helpers\HtmlHelper::statusSpan($model['status']); ?>
                                    <a href="<?= Url::to(['delete','id' => $model->id])?>"  onclick="rfDelete(this);return false;"><span class="btn btn-warning btn-sm">删除</span></a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                    <div class="row">
                        <div class="col-sm-12">
                            <?= LinkPager::widget([
                                'pagination' => $pages,
                                'maxButtonCount' => 5,
                                'firstPageLabel' => "首页",
                                'lastPageLabel' => "尾页",
                                'nextPageLabel' => "下一页",
                                'prevPageLabel' => "上一页",
                            ]);?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
