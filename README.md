Yii2 Gii
=========
gii`ActiveQuery`扩展生成各个字段的基本筛选函数

## 1、安装

安装这个扩展的首选方式是通过 [composer](http://getcomposer.org/download/).

执行

```
composer require --prefer-dist myweishanli/yii2-gii
```
或添加

```
"myweishanli/yii2-gii": "~1.0.0"
```

## 2、配置

`@app/config/main-local.php`

```php
if (!YII_ENV_TEST) {
    // ...
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        'generators' => [
            'model' => ['class' => 'wsl\gii\generators\model\Generator'],
        ],
    ];
}
```

## Demo

**SQL:**

```sql
CREATE TABLE `user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户id',
  `nickname` varchar(255) CHARACTER SET latin1 NOT NULL COMMENT '昵称',
  `age` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '年龄',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
```

**访问:**

http://{Your domain}/gii/model

或

http://{Your domain}/index.php?r=gii/model

勾选`Generate ActiveQuery`

点击`Preview`按钮

`models\UserQuery.php`预览代码

```php
<?php

namespace common\models;

/**
 * This is the ActiveQuery class for [[User]].
 *
 * @see User
 */
class UserQuery extends \yii\db\ActiveQuery
{
    /*public function inactive()
    {
        $this->andWhere('[[status]]=0');
        return $this;
    }

    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    public function delete()
    {
        $this->andWhere('[[status]]=2');
        return $this;
    }

    public function normal()
    {
        $this->andWhere('[[status]]=0 or [[status]]=1');
        return $this;
    }*/

    /**
     * Find by [[user_id]]
     *
     * @param integer $userId [[user_id]]
     * @return $this
     */
    public function findByUserId($userId)
    {
        $this->andWhere(['[[user_id]]' => $userId]);
        return $this;
    }
    
    /**
     * Find by [[nickname]]
     *
     * @param string $nickname [[nickname]]
     * @return $this
     */
    public function findByNickname($nickname)
    {
        $this->andWhere(['[[nickname]]' => $nickname]);
        return $this;
    }
    
    /**
     * Find by like [[nickname]]
     *
     * @param string $nickname [[nickname]]
     * @return $this
     */
    public function findByLikeNickname($nickname)
    {
        $this->andWhere('[[nickname]] like :nickname', [
            ':nickname' => '%' . $nickname . '%',
        ]);
        return $this;
    }

    /**
     * Find by like left [[nickname]]
     *
     * @param string $nickname [[nickname]]
     * @return $this
     */
    public function findByLeftLikeNickname($nickname)
    {
        $this->andWhere('[[nickname]] like :nickname', [
            ':nickname' => '%' . $nickname,
        ]);
        return $this;
    }

    /**
     * Find by like right [[nickname]]
     *
     * @param string $nickname [[nickname]]
     * @return $this
     */
    public function findByRightLikeNickname($nickname)
    {
        $this->andWhere('[[nickname]] like :nickname', [
            ':nickname' => $nickname . '%',
        ]);
        return $this;
    }
    
    /**
     * Find by [[age]]
     *
     * @param integer $age [[age]]
     * @return $this
     */
    public function findByAge($age)
    {
        $this->andWhere(['[[age]]' => $age]);
        return $this;
    }
    
    /**
     * @inheritdoc
     * @return User[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return User|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

}
```

更新日志
------------

### Version Dev Master

- 优化字段类型

### Version 1.0.0 (2016.1.11)

- 首个版本
